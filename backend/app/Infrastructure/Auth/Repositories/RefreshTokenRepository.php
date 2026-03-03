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
 *
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
                $deviceInfo->getDeviceType(),  // 修復：使用 getDeviceType()
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
                'Failed to create refresh token: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByJti(string $jti): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            /** @var array<string, mixed>|false $result */
            return is_array($result) ? $result : null;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh token by JTI: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByTokenHash(string $tokenHash): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE token_hash = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tokenHash]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            /** @var array<string, mixed>|false $result */
            return is_array($result) ? $result : null;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh token by hash: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserId(int $userId, bool $includeExpired = false): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE user_id = ? AND status = ?';
            $params = [$userId, RefreshToken::STATUS_ACTIVE];

            if (!$includeExpired) {
                $sql .= ' AND expires_at > ?';
                $params[] = new DateTime()->format('Y-m-d H:i:s');
            }

            $sql .= ' ORDER BY created_at DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> $results */
            return is_array($results) ? $results : [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh tokens by user ID: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserIdAndDevice(int $userId, string $deviceId): array
    {
        try {
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE user_id = ? AND device_id = ?
                ORDER BY created_at DESC
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> $results */
            return is_array($results) ? $results : [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh tokens by user ID and device: ' . $e->getMessage(),
            );
        }
    }

    public function updateLastUsed(string $jti, ?DateTime $lastUsedAt = null): bool
    {
        try {
            $lastUsedAt ??= new DateTime();
            $sql = 'UPDATE ' . self::TABLE_NAME . ' SET last_used_at = ?, updated_at = ? WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                $lastUsedAt->format('Y-m-d H:i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_UPDATE_FAILED,
                'Failed to update last used time: ' . $e->getMessage(),
            );
        }
    }

    public function revoke(string $jti, string $reason = 'manual_revocation'): bool
    {
        try {
            $sql = '
                UPDATE ' . self::TABLE_NAME . '
                SET status = ?, revoked_reason = ?, revoked_at = ?, updated_at = ?
                WHERE jti = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $reason,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                'Failed to revoke refresh token: ' . $e->getMessage(),
            );
        }
    }

    public function revokeAllByUserId(int $userId, string $reason = 'revoke_all_sessions', ?string $excludeJti = null): int
    {
        try {
            $sql = '
                UPDATE ' . self::TABLE_NAME . '
                SET status = ?, revoked_reason = ?, revoked_at = ?, updated_at = ?
                WHERE user_id = ? AND status = ?
            ';
            $params = [
                RefreshToken::STATUS_REVOKED,
                $reason,
                new DateTime()->format('Y-m-d H:i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                $userId,
                RefreshToken::STATUS_ACTIVE,
            ];

            if ($excludeJti !== null) {
                $sql .= ' AND jti != ?';
                $params[] = $excludeJti;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                'Failed to revoke all user tokens: ' . $e->getMessage(),
            );
        }
    }

    public function revokeAllByDevice(int $userId, string $deviceId, string $reason = 'device_logout'): int
    {
        try {
            $sql = '
                UPDATE ' . self::TABLE_NAME . '
                SET status = ?, revoked_reason = ?, revoked_at = ?, updated_at = ?
                WHERE user_id = ? AND device_id = ? AND status = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $reason,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $userId,
                $deviceId,
                RefreshToken::STATUS_ACTIVE,
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_REVOCATION_FAILED,
                'Failed to revoke device tokens: ' . $e->getMessage(),
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
                RefreshTokenException::REASON_DELETION_FAILED,
                'Failed to delete refresh token: ' . $e->getMessage(),
            );
        }
    }

    public function isRevoked(string $jti): bool
    {
        try {
            $sql = 'SELECT status FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetchColumn();

            return $result === RefreshToken::STATUS_REVOKED;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to check revocation status: ' . $e->getMessage(),
            );
        }
    }

    public function isExpired(string $jti): bool
    {
        try {
            $sql = 'SELECT expires_at FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $expiresAt = $stmt->fetchColumn();
            if ($expiresAt === false || !is_string($expiresAt)) {
                return true; // Token not found, consider expired
            }

            return new DateTime($expiresAt) <= new DateTime();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to check expiration status: ' . $e->getMessage(),
            );
        }
    }

    public function isValid(string $jti): bool
    {
        return !$this->isExpired($jti) && !$this->isRevoked($jti);
    }

    public function cleanup(?DateTime $beforeDate = null): int
    {
        try {
            $beforeDate ??= new DateTime();
            $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE expires_at <= ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$beforeDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CLEANUP_FAILED,
                'Failed to cleanup expired tokens: ' . $e->getMessage(),
            );
        }
    }

    public function cleanupRevoked(int $days = 30): int
    {
        try {
            $cutoffDate = new DateTime("-{$days} days");
            $sql = '
                DELETE FROM ' . self::TABLE_NAME . '
                WHERE status = ? AND revoked_at <= ?
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $cutoffDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CLEANUP_FAILED,
                'Failed to cleanup revoked tokens: ' . $e->getMessage(),
            );
        }
    }

    public function getUserTokenStats(int $userId): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = ? AND expires_at > ? THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked
                FROM ' . self::TABLE_NAME . '
                WHERE user_id = ?
            ';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime()->format('Y-m-d H:i:s');

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now,
                $now,
                RefreshToken::STATUS_REVOKED,
                $userId,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($result)) {
                return [
                    'total' => 0,
                    'active' => 0,
                    'expired' => 0,
                    'revoked' => 0,
                ];
            }

            return [
                'total' => isset($result['total']) && is_numeric($result['total']) ? (int) $result['total'] : 0,
                'active' => isset($result['active']) && is_numeric($result['active']) ? (int) $result['active'] : 0,
                'expired' => isset($result['expired']) && is_numeric($result['expired']) ? (int) $result['expired'] : 0,
                'revoked' => isset($result['revoked']) && is_numeric($result['revoked']) ? (int) $result['revoked'] : 0,
            ];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get user token stats: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTokenFamily(string $parentJti): array
    {
        $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE parent_token_jti = ? ORDER BY created_at ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$parentJti]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /** @var array<int, array<string, mixed>> $results */
        return is_array($results) ? $results : [];
    }

    public function revokeTokenFamily(string $rootJti, string $reason = 'family_revocation'): int
    {
        try {
            $family = $this->getTokenFamily($rootJti);
            $jtis = array_column($family, 'jti');

            if (empty($jtis)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($jtis), '?'));
            $sql = '
                UPDATE ' . self::TABLE_NAME . '
                SET status = ?, revoked_reason = ?, revoked_at = ?, updated_at = ?
                WHERE jti IN (' . $placeholders . ')
            ';

            $params = [
                RefreshToken::STATUS_REVOKED,
                $reason,
                new DateTime()->format('Y-m-d H:i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                ...$jtis,
            ];

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_FAMILY_REVOCATION_FAILED,
                'Failed to revoke token family: ' . $e->getMessage(),
            );
        }
    }

    public function batchCreate(array $tokens): int
    {
        try {
            $this->pdo->beginTransaction();
            $createdCount = 0;

            foreach ($tokens as $token) {
                if (!is_array($token)) {
                    continue;
                }

                $jti = isset($token['jti']) && is_string($token['jti']) ? $token['jti'] : '';
                $userId = isset($token['user_id']) && is_numeric($token['user_id']) ? (int) $token['user_id'] : 0;
                $tokenHash = isset($token['token_hash']) && is_string($token['token_hash']) ? $token['token_hash'] : '';
                $expiresAtStr = isset($token['expires_at']) && is_string($token['expires_at']) ? $token['expires_at'] : '';
                $deviceInfo = isset($token['device_info']) && $token['device_info'] instanceof DeviceInfo ? $token['device_info'] : null;
                $parentJti = isset($token['parent_token_jti']) && is_string($token['parent_token_jti']) ? $token['parent_token_jti'] : null;

                if ($jti === '' || $userId === 0 || $tokenHash === '' || $expiresAtStr === '' || $deviceInfo === null) {
                    continue;
                }

                $success = $this->create(
                    $jti,
                    $userId,
                    $tokenHash,
                    new DateTime($expiresAtStr),
                    $deviceInfo,
                    $parentJti,
                );

                if ($success) {
                    $createdCount++;
                }
            }

            $this->pdo->commit();

            return $createdCount;
        } catch (PDOException $e) {
            $this->pdo->rollback();

            throw new RefreshTokenException(
                RefreshTokenException::REASON_BATCH_OPERATION_FAILED,
                'Failed to batch create tokens: ' . $e->getMessage(),
            );
        }
    }

    public function batchRevoke(array $jtis, string $reason = 'batch_revocation'): int
    {
        try {
            if (empty($jtis)) {
                return 0;
            }

            $placeholders = implode(',', array_fill(0, count($jtis), '?'));
            $sql = '
                UPDATE ' . self::TABLE_NAME . '
                SET status = ?, revoked_reason = ?, revoked_at = ?, updated_at = ?
                WHERE jti IN (' . $placeholders . ')
            ';

            $params = [
                RefreshToken::STATUS_REVOKED,
                $reason,
                new DateTime()->format('Y-m-d H:i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                ...$jtis,
            ];

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_BATCH_OPERATION_FAILED,
                'Failed to batch revoke tokens: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTokensNearExpiry(int $thresholdHours = 24): array
    {
        try {
            $thresholdDate = new DateTime("+{$thresholdHours} hours");
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE expires_at <= ? AND expires_at > ? AND status = ?
                ORDER BY expires_at ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $thresholdDate->format('Y-m-d H:i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                RefreshToken::STATUS_ACTIVE,
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> $results */
            return is_array($results) ? $results : [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get tokens near expiry: ' . $e->getMessage(),
            );
        }
    }

    public function getSystemStats(): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(*) as total_tokens,
                    SUM(CASE WHEN status = ? AND expires_at > ? THEN 1 ELSE 0 END) as active_tokens,
                    SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expired_tokens,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked_tokens,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT device_id) as unique_devices
                FROM ' . self::TABLE_NAME . '
            ';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime()->format('Y-m-d H:i:s');

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now,
                $now,
                RefreshToken::STATUS_REVOKED,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($result)) {
                return [
                    'total_tokens' => 0,
                    'active_tokens' => 0,
                    'expired_tokens' => 0,
                    'revoked_tokens' => 0,
                    'unique_users' => 0,
                    'unique_devices' => 0,
                ];
            }

            return [
                'total_tokens' => isset($result['total_tokens']) && is_numeric($result['total_tokens']) ? (int) $result['total_tokens'] : 0,
                'active_tokens' => isset($result['active_tokens']) && is_numeric($result['active_tokens']) ? (int) $result['active_tokens'] : 0,
                'expired_tokens' => isset($result['expired_tokens']) && is_numeric($result['expired_tokens']) ? (int) $result['expired_tokens'] : 0,
                'revoked_tokens' => isset($result['revoked_tokens']) && is_numeric($result['revoked_tokens']) ? (int) $result['revoked_tokens'] : 0,
                'unique_users' => isset($result['unique_users']) && is_numeric($result['unique_users']) ? (int) $result['unique_users'] : 0,
                'unique_devices' => isset($result['unique_devices']) && is_numeric($result['unique_devices']) ? (int) $result['unique_devices'] : 0,
            ];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get system stats: ' . $e->getMessage(),
            );
        }
    }

    /**
     * 簡化版的 Token 家族查詢（不使用 CTE）.
     *
     * @phpstan-ignore method.unused
     */
    private function getTokenFamilySimple(string $rootJti): array
    {
        try {
            // 先找出直接相關的 token
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE jti = ? OR parent_token_jti = ?
                ORDER BY created_at
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rootJti, $rootJti]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get token family: ' . $e->getMessage(),
            );
        }
    }
}
