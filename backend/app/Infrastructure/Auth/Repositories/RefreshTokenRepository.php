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
 *
 * 負責 RefreshToken 實體的資料庫存取操作，實作完整的 RefreshTokenRepositoryInterface。
 * 採用 PDO 進行資料庫操作，支援交易處理與錯誤處理。
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private const TABLE_NAME = 'refresh_tokens';

    public function __construct(
        private readonly PDO $pdo
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
                'Failed to create refresh token: ' . $e->getMessage(),
                previous: $e
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

            return $result ?: null;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find refresh token by JTI: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function findByUserId(int $userId): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE user_id = ? AND status = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, RefreshToken::STATUS_ACTIVE]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find refresh tokens by user ID: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function findActiveByUserAndDevice(int $userId, string $deviceId): ?array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ? AND device_id = ? AND status = ?
                    ORDER BY created_at DESC LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId, RefreshToken::STATUS_ACTIVE]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find active refresh token by user and device: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function updateLastUsed(string $jti, DateTime $lastUsedAt): bool
    {
        try {
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
                'Failed to update last used time: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function revokeByJti(string $jti): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, updated_at = ?
                    WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $now->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to revoke refresh token: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function revokeAllByUserId(int $userId): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, updated_at = ?
                    WHERE user_id = ? AND status = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $now->format('Y-m-d H:i:s'),
                $userId,
                RefreshToken::STATUS_ACTIVE,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to revoke all refresh tokens for user: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function revokeAllByUserAndDevice(int $userId, string $deviceId): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, updated_at = ?
                    WHERE user_id = ? AND device_id = ? AND status = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $now->format('Y-m-d H:i:s'),
                $userId,
                $deviceId,
                RefreshToken::STATUS_ACTIVE,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to revoke refresh tokens for user and device: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function deleteExpired(): int
    {
        try {
            $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                    WHERE expires_at < ? OR
                          (status = ? AND updated_at < ?)';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();
            $oneWeekAgo = (clone $now)->modify('-1 week');

            $stmt->execute([
                $now->format('Y-m-d H:i:s'),
                RefreshToken::STATUS_REVOKED,
                $oneWeekAgo->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to delete expired refresh tokens: ' . $e->getMessage(),
                previous: $e
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
                'Failed to validate refresh token: ' . $e->getMessage(),
                previous: $e
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
                'Failed to count active refresh tokens: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function findRecentlyUsed(int $userId, int $limit = 10): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE user_id = ?
                    ORDER BY COALESCE(last_used_at, created_at) DESC
                    LIMIT ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find recently used refresh tokens: ' . $e->getMessage(),
                previous: $e
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

            return $result ?: null;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find refresh token by hash: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function updateStatus(string $jti, string $status): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . '
                    SET status = ?, updated_at = ?
                    WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            return $stmt->execute([
                $status,
                $now->format('Y-m-d H:i:s'),
                $jti,
            ]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to update refresh token status: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function findExpiringSoon(int $hours = 24): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE status = ? AND expires_at BETWEEN ? AND ?';
            $stmt = $this->pdo->prepare($sql);

            $now = new DateTime();
            $futureTime = (clone $now)->modify("+{$hours} hours");

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
                $futureTime->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find expiring refresh tokens: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function getStatistics(): array
    {
        try {
            $sql = 'SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked,
                        SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired
                    FROM ' . self::TABLE_NAME;

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                RefreshToken::STATUS_REVOKED,
                $now->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to get refresh token statistics: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 執行資料庫交易.
     */
    public function transaction(callable $callback): mixed
    {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw new RefreshTokenException(
                'Transaction failed: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 批次撤銷多個 Token.
     */
    public function revokeBatch(array $jtis): int
    {
        if (empty($jtis)) {
            return 0;
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = "UPDATE " . self::TABLE_NAME . "
                    SET status = ?, updated_at = ?
                    WHERE jti IN ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $params = [RefreshToken::STATUS_REVOKED, $now->format('Y-m-d H:i:s')];
            $params = array_merge($params, $jtis);

            $stmt->execute($params);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to revoke tokens in batch: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
