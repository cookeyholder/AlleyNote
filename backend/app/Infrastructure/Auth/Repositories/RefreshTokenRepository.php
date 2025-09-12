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
use Throwable;

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
            throw new RefreshTokenException('無法建立 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array|null
     */
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
            throw new RefreshTokenException('無法查詢 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array
     */
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
            throw new RefreshTokenException('無法查詢使用者的 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array|null
     */
    public function findActiveByUserAndDevice(int $userId, string $deviceId): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND device_id = ? AND status = ?
                    ORDER BY created_at DESC LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId, RefreshToken::STATUS_ACTIVE]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException('無法查詢使用者裝置的 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array
     */
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
            throw new RefreshTokenException('無法查詢使用者裝置的 Refresh Token 列表: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法更新 Refresh Token 最後使用時間: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法撤銷 Refresh Token: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法撤銷使用者的所有 Refresh Token: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法撤銷裝置的 Refresh Token: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法清理過期的 Refresh Token: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法驗證 Refresh Token: ' . $e->getMessage(), 0, $e);
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
            throw new RefreshTokenException('無法查詢使用者的活躍 Refresh Token 數量: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array
     */
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
            throw new RefreshTokenException('無法查詢最近使用的 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array|null
     */
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
            throw new RefreshTokenException('無法透過 token hash 查詢 Refresh Token: ' . $e->getMessage(), 0, $e);
        }
    }
}
