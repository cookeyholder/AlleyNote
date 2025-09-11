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
        $stmt->execute([$tokenHash]);

        $resu                $userId,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return [];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get user token stats: ' . $e->getMessage(),mt->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }

        return $result; 負責 RefreshToken 實體的資料庫存取操作，實作完整的 RefreshTokenRepositoryInterface。
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
                'Failed to create refresh token: ' . $e->getMessage(),
                ['database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<string, mixed>|null
     */
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

            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh token by JTI: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
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
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh tokens by user ID: ' . $e->getMessage(),
                ['user_id' => $userId, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<string, mixed>|null
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
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find active refresh token by user and device: ' . $e->getMessage(),
                ['user_id' => $userId, 'device_id' => $deviceId, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
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
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find refresh tokens by user and device: ' . $e->getMessage(),
                ['user_id' => $userId, 'device_id' => $deviceId, 'database_error' => $e->getMessage()],
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
                'Failed to update last used time: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
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
                'Failed to revoke refresh token: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
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
                'Failed to revoke all refresh tokens for user: ' . $e->getMessage(),
                ['user_id' => $userId, 'database_error' => $e->getMessage()],
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
                'Failed to revoke refresh tokens for user and device: ' . $e->getMessage(),
                ['user_id' => $userId, 'device_id' => $deviceId, 'database_error' => $e->getMessage()],
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
                'Failed to cleanup expired refresh tokens: ' . $e->getMessage(),
                ['database_error' => $e->getMessage()],
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
                'Failed to validate refresh token: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
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
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
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
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find recently used tokens: ' . $e->getMessage(),
                ['user_id' => $userId, 'database_error' => $e->getMessage()],
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
            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find refresh token by hash: ' . $e->getMessage(),
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
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result ?: [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to find expiring refresh tokens: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
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

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return [];
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                'Failed to get refresh token statistics: ' . $e->getMessage(),
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
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Transaction failed: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * 批次撤銷多個 Token.
     * @param array<string> $jtis
     */
    public function revokeBatch(array $jtis): int
    {
        if (empty($jtis)) {
            return 0;
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = 'UPDATE ' . self::TABLE_NAME . "
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
                RefreshTokenException::REASON_BATCH_OPERATION_FAILED,
                'Failed to revoke tokens in batch: ' . $e->getMessage(),
                ['jtis_count' => count($jtis), 'database_error' => $e->getMessage()],
            );
        }
    }

    public function delete(string $jti): bool
    {
        try {
            $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([$jti]);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DELETION_FAILED,
                'Failed to delete refresh token: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
            );
        }
    }

    public function isRevoked(string $jti): bool
    {
        try {
            $sql = 'SELECT status FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['status'] === RefreshToken::STATUS_REVOKED : false;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to check if token is revoked: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
            );
        }
    }

    public function isExpired(string $jti): bool
    {
        try {
            $sql = 'SELECT expires_at FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return true; // 找不到就當作過期
            }

            $expiresAt = new DateTime((string) $result['expires_at']);

            return $expiresAt < new DateTime();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to check if token is expired: ' . $e->getMessage(),
                ['jti' => $jti, 'database_error' => $e->getMessage()],
            );
        }
    }

    public function cleanupRevoked(int $days = 30): int
    {
        try {
            $beforeDate = new DateTime()->modify("-{$days} days");
            $sql = 'DELETE FROM ' . self::TABLE_NAME . '
                    WHERE status = ? AND updated_at < ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                RefreshToken::STATUS_REVOKED,
                $beforeDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_CLEANUP_FAILED,
                'Failed to cleanup revoked refresh tokens: ' . $e->getMessage(),
                ['days' => $days, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserTokenStats(int $userId): array
    {
        try {
            $sql = 'SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = ? AND expires_at > ? THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked
                    FROM ' . self::TABLE_NAME . ' WHERE user_id = ?';

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                RefreshToken::STATUS_REVOKED,
                $userId,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return [];
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get user token stats: ' . $e->getMessage(),
                ['user_id' => $userId, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTokenFamily(string $rootJti): array
    {
        try {
            // 遞迴查詢 token 家族
            $sql = 'WITH RECURSIVE token_family AS (
                        SELECT * FROM ' . self::TABLE_NAME . ' WHERE jti = ?
                        UNION ALL
                        SELECT rt.* FROM ' . self::TABLE_NAME . ' rt
                        INNER JOIN token_family tf ON rt.parent_token_jti = tf.jti
                    )
                    SELECT * FROM token_family';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rootJti]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result ?: [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get token family: ' . $e->getMessage(),
                ['root_jti' => $rootJti, 'database_error' => $e->getMessage()],
            );
        }
    }

    public function revokeTokenFamily(string $rootJti, string $reason = 'family_revocation'): int
    {
        try {
            $family = $this->getTokenFamily($rootJti);
            $jtis = array_column($family, 'jti');

            if (empty($jtis)) {
                return 0;
            }

            /** @var array<string> $typedJtis */
            $typedJtis = array_map(static fn($jti): string => (string) $jti, $jtis);

            return $this->batchRevoke($typedJtis, $reason);
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_FAMILY_REVOCATION_FAILED,
                'Failed to revoke token family: ' . $e->getMessage(),
                ['root_jti' => $rootJti, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     */
    public function batchCreate(array $tokens): int
    {
        if (empty($tokens)) {
            return 0;
        }

        try {
            $this->pdo->beginTransaction();
            $successCount = 0;

            foreach ($tokens as $token) {
                /** @var array<string, mixed> $tokenData */
                $tokenData = $token;
                /** @var array<string, mixed> $deviceInfoData */
                $deviceInfoData = is_array($tokenData['device_info']) ? $tokenData['device_info'] : [];
                $deviceInfo = DeviceInfo::fromArray($deviceInfoData);
                if ($this->create(
                    (string) $tokenData['jti'],
                    (int) $tokenData['user_id'],
                    (string) $tokenData['token_hash'],
                    new DateTime((string) $tokenData['expires_at']),
                    $deviceInfo,
                    isset($tokenData['parent_token_jti']) ? (string) $tokenData['parent_token_jti'] : null,
                )) {
                    $successCount++;
                }
            }

            $this->pdo->commit();

            return $successCount;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw new RefreshTokenException(
                RefreshTokenException::REASON_BATCH_OPERATION_FAILED,
                'Failed to batch create tokens: ' . $e->getMessage(),
                ['tokens_count' => count($tokens), 'error' => $e->getMessage()],
            );
        }
    }

    /**
     * @param array<string> $jtis
     */
    public function batchRevoke(array $jtis, string $reason = 'batch_revocation'): int
    {
        return $this->revokeBatch($jtis);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTokensNearExpiry(int $hours = 24): array
    {
        try {
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . '
                    WHERE status = ? AND expires_at > ? AND expires_at <= ?';
            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();
            $futureTime = (clone $now)->modify("+{$hours} hours");

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
                $futureTime->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, array<string, mixed>> */
            return $result ?: [];
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to find expiring refresh tokens: ' . $e->getMessage(),
                ['threshold_hours' => $hours, 'database_error' => $e->getMessage()],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getSystemStats(): array
    {
        try {
            $sql = 'SELECT
                        COUNT(*) as total_tokens,
                        SUM(CASE WHEN status = ? AND expires_at > ? THEN 1 ELSE 0 END) as active_tokens,
                        SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired_tokens,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked_tokens,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(DISTINCT device_id) as unique_devices
                    FROM ' . self::TABLE_NAME;

            $stmt = $this->pdo->prepare($sql);
            $now = new DateTime();

            $stmt->execute([
                RefreshToken::STATUS_ACTIVE,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                RefreshToken::STATUS_REVOKED,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return [];
            }

            /** @var array<string, mixed> */
            return $result;
        } catch (PDOException $e) {
            throw new RefreshTokenException(
                RefreshTokenException::REASON_DATABASE_ERROR,
                'Failed to get system stats: ' . $e->getMessage(),
                ['database_error' => $e->getMessage()],
            );
        }
    }
}
