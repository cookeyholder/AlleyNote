<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Repositories;

use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Entities\RefreshToken;
use App\Domains\Auth\Exceptions\RefreshTokenException;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use PDO;
use PDOException;

/**
 * RefreshToken Repository 實作類別.
 * 負責 RefreshToken 實體的資料庫存取操作，實作完整的 RefreshTokenRepositoryInterface。
 * 採用 PDO 進行資料庫操作，支援交易處理與錯誤處理。
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private const TABLE_NAME = 'refresh_tokens';

    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function create(
        string $jti,
        int $userId,
        string $tokenHash,
        DateTime $expiresAt,
        DeviceInfo $deviceInfo,
        ?string $parentTokenJti = null,
    ): bool {
        try {
            $sql = '
                INSERT INTO ' . self::TABLE_NAME . ' (
                    jti, user_id, token_hash, expires_at,
                    device_id, device_name, device_type, user_agent, ip_address, platform, browser,
                    status, parent_token_jti, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                $jti,
                $userId,
                $tokenHash,
                $expiresAt->format('Y-m-d H:i:s'),
                $deviceInfo->getDeviceId(),
                $deviceInfo->getDeviceName(),
                $deviceInfo->getDeviceType(),
                $deviceInfo->getUserAgent(),
                $deviceInfo->getIpAddress(),
                $deviceInfo->getPlatform(),
                $deviceInfo->getBrowser(),
                RefreshToken::STATUS_ACTIVE,
                $parentTokenJti,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CREATION_FAILED,
                '無法建立 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findByJti(string $jti): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findByUserId(int $userId, bool $includeExpired = false): array
    {
        try {
            if ($includeExpired) {
                $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE user_id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId]);
            } else {
                $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE user_id = ? AND status = ? AND expires_at > NOW()';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId, RefreshToken::STATUS_ACTIVE]);
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢使用者的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findActiveByUserAndDevice(int $userId, string $deviceId): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND device_id = ? AND status = ?
                    ORDER BY created_at DESC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId, RefreshToken::STATUS_ACTIVE]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢使用者裝置的活躍 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findByUserIdAndDevice(int $userId, string $deviceId): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND device_id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢使用者裝置的 Refresh Token 列表: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function updateLastUsed(string $jti, ?DateTime $lastUsedAt = null): bool
    {
        try {
            $lastUsedAt ??= new DateTime();
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET last_used_at = ?, updated_at = ?
                    WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                $lastUsedAt->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_UPDATE_FAILED,
                '無法更新 Refresh Token 最後使用時間: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function revoke(string $jti, string $reason = 'manual_revocation'): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, revoked_reason = ?, updated_at = ?
                    WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $reason,
                $now->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                '無法撤銷 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function revokeAllByUserId(int $userId, string $reason = 'revoke_all_sessions', ?string $excludeJti = null): int
    {
        try {
            if ($excludeJti !== null) {
                $sql = 'UPDATE ' . self::TABLE_NAME . '
                        SET status = ?, revoked_reason = ?, updated_at = ?
                        WHERE user_id = ? AND status = ? AND jti != ?';
                $params = [
                    RefreshToken::STATUS_REVOKED,
                    $reason,
                    new DateTime()->format('Y-m-d H:i:s'),
                    $userId,
                    RefreshToken::STATUS_ACTIVE,
                    $excludeJti,
                ];
            } else {
                $sql = 'UPDATE ' . self::TABLE_NAME . '
                        SET status = ?, revoked_reason = ?, updated_at = ?
                        WHERE user_id = ? AND status = ?';
                $params = [
                    RefreshToken::STATUS_REVOKED,
                    $reason,
                    new DateTime()->format('Y-m-d H:i:s'),
                    $userId,
                    RefreshToken::STATUS_ACTIVE,
                ];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                '無法撤銷使用者的所有 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function revokeAllByDevice(int $userId, string $deviceId, string $reason = 'device_logout'): int
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, revoked_reason = ?, updated_at = ?
                    WHERE user_id = ? AND device_id = ? AND status = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $reason,
                $now->format('Y-m-d H:i:s'),
                $userId,
                $deviceId,
                RefreshToken::STATUS_ACTIVE,
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                '無法撤銷裝置的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function cleanup(?DateTime $beforeDate = null): int
    {
        try {
            $beforeDate ??= new DateTime();
            $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                    WHERE expires_at < ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$beforeDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CLEANUP_FAILED,
                '無法清理過期的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function isValid(string $jti): bool
    {
        try {
            $sql = 'SELECT 1 FROM ' . self::TABLE_NAME . '
                    WHERE jti = ? AND status = ? AND expires_at > ?
                    LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                $jti,
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法驗證 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function countActiveByUser(int $userId): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND status = ? AND expires_at > ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                $userId,
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢使用者的活躍 Refresh Token 數量: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findRecentlyUsed(int $userId, int $limit = 10): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND last_used_at IS NOT NULL
                    ORDER BY last_used_at DESC LIMIT ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result ?: [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢最近使用的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE token_hash = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tokenHash]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法透過 token hash 查詢 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function delete(string $jti): bool
    {
        try {
            $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法刪除 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function isRevoked(string $jti): bool
    {
        try {
            $sql = 'SELECT 1 FROM ' . self::TABLE_NAME . '
                    WHERE jti = ? AND status = ?
                    LIMIT 1';
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                $jti,
                RefreshToken::STATUS_REVOKED,
            ]);

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢 Refresh Token 撤銷狀態: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function isExpired(string $jti): bool
    {
        try {
            $sql = 'SELECT 1 FROM ' . self::TABLE_NAME . '
                    WHERE jti = ? AND expires_at < ?
                    LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                $jti,
                $now->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢 Refresh Token 過期狀態: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function cleanupRevoked(int $days = 30): int
    {
        try {
            $cutoffDate = new DateTime();
            $cutoffDate->modify("-{$days} days");

            $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                    WHERE status = ? AND updated_at < ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $cutoffDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CLEANUP_FAILED,
                '無法清理已撤銷的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function getUserTokenStats(int $userId): array
    {
        try {
            $sql = 'SELECT
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = ? AND expires_at > ? THEN 1 END) as active,
                        COUNT(CASE WHEN expires_at <= ? THEN 1 END) as expired,
                        COUNT(CASE WHEN status = ? THEN 1 END) as revoked
                    FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ?';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();
            $nowFormatted = $now->format('Y-m-d H:i:s');

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $nowFormatted,
                $nowFormatted,
                RefreshToken::STATUS_REVOKED,
                $userId,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int) $result['total'],
                'active' => (int) $result['active'],
                'expired' => (int) $result['expired'],
                'revoked' => (int) $result['revoked'],
            ];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法獲取使用者 Token 統計資訊: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function getTokenFamily(string $rootJti): array
    {
        try {
            // 使用遞歸 CTE 查找 token 家族
            $sql = 'WITH RECURSIVE token_family AS (
                        -- 查找根 token
                        SELECT jti, parent_token_jti, 0 as level
                        FROM ' . self::TABLE_NAME . '
                        WHERE jti = ?
                        
                        UNION ALL
                        
                        -- 查找子 token
                        SELECT t.jti, t.parent_token_jti, tf.level + 1
                        FROM ' . self::TABLE_NAME . ' t
                        INNER JOIN token_family tf ON t.parent_token_jti = tf.jti
                    )
                    SELECT rt.* FROM token_family tf
                    INNER JOIN ' . self::TABLE_NAME . ' rt ON tf.jti = rt.jti
                    ORDER BY tf.level, rt.created_at';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rootJti]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢 Token 家族: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function revokeTokenFamily(string $rootJti, string $reason = 'family_revocation'): int
    {
        try {
            // 首先查找整個 token 家族
            $familyTokens = $this->getTokenFamily($rootJti);

            if (empty($familyTokens)) {
                return 0;
            }

            $jtis = array_column($familyTokens, 'jti');

            return $this->batchRevoke($jtis, $reason);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                '無法撤銷 Token 家族: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function batchCreate(array $tokens): int
    {
        if (empty($tokens)) {
            return 0;
        }

        try {
            $this->pdo->beginTransaction();

            $successCount = 0;
            foreach ($tokens as $token) {
                $result = $this->create(
                    $token['jti'],
                    $token['user_id'],
                    $token['token_hash'],
                    new DateTime($token['expires_at']),
                    new DeviceInfo(
                        $token['device_id'],
                        $token['device_name'] ?? '',
                        $token['device_type'] ?? '',
                        $token['user_agent'] ?? '',
                        $token['ip_address'] ?? '',
                        $token['platform'] ?? '',
                        $token['browser'] ?? '',
                    ),
                    $token['parent_token_jti'] ?? null,
                );

                if ($result) {
                    $successCount++;
                }
            }

            $this->pdo->commit();

            return $successCount;
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new RefreshTokenException(
                RefreshTokenException::REASON_CREATION_FAILED,
                '批次建立 Refresh Token 失敗: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function batchRevoke(array $jtis, string $reason = 'batch_revocation'): int
    {
        if (empty($jtis)) {
            return 0;
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, revoked_reason = ?, updated_at = ?
                    WHERE jti IN (' . $placeholders . ') AND status = ?';

            $stmt = $this->pdo->prepare($sql);
            $params = [
                RefreshToken::STATUS_REVOKED,
                $reason,
                new DateTime()->format('Y-m-d H:i:s'),
                ...$jtis,
                RefreshToken::STATUS_ACTIVE,
            ];

            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                '批次撤銷 Refresh Token 失敗: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function getTokensNearExpiry(int $thresholdHours = 24): array
    {
        try {
            $thresholdDate = new DateTime();
            $thresholdDate->modify("+{$thresholdHours} hours");

            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE status = ? AND expires_at > ? AND expires_at <= ?
                    ORDER BY expires_at ASC';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
                $thresholdDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法查詢即將過期的 Refresh Token: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }

    public function getSystemStats(): array
    {
        try {
            $sql = 'SELECT
                        COUNT(*) as total_tokens,
                        COUNT(CASE WHEN status = ? AND expires_at > ? THEN 1 END) as active_tokens,
                        COUNT(CASE WHEN expires_at <= ? THEN 1 END) as expired_tokens,
                        COUNT(CASE WHEN status = ? THEN 1 END) as revoked_tokens,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(DISTINCT device_id) as unique_devices
                    FROM ' . self::TABLE_NAME;

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();
            $nowFormatted = $now->format('Y-m-d H:i:s');

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $nowFormatted,
                $nowFormatted,
                RefreshToken::STATUS_REVOKED,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_tokens' => (int) $result['total_tokens'],
                'active_tokens' => (int) $result['active_tokens'],
                'expired_tokens' => (int) $result['expired_tokens'],
                'revoked_tokens' => (int) $result['revoked_tokens'],
                'unique_users' => (int) $result['unique_users'],
                'unique_devices' => (int) $result['unique_devices'],
            ];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                '無法獲取系統統計資訊: ' . $e->getMessage(),
                ['pdo_error' => $e->getMessage()],
            );
        }
    }
}
