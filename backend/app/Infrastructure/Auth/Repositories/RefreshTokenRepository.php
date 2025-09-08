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
        private readonly PDO $pdo) {}

    public function create(
        string $jti,
        int $userId,
        string $tokenHash,
        DateTime $expiresAt,
        DeviceInfo $deviceInfo,
        ?string $parentTokenJti = null,
    ): bool {
        try { /* empty */ }
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
                $expiresAt->format('Y-m-d H => i:s'),
                $deviceInfo->getDeviceId(),
                $deviceInfo->getDeviceName(),
                $deviceInfo->getPlatform(),
                $deviceInfo->getUserAgent(),
                $deviceInfo->getIpAddress(),
                $deviceInfo->getPlatform(),
                $deviceInfo->getBrowser(),
                RefreshToken::STATUS_ACTIVE,
                $parentTokenJti,
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
            ]);
        }

    public function findByJti(string $jti): ?array
    {
        try { /* empty */ }
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? true : null;
        }

    public function findByTokenHash(string $tokenHash): ?array
    {
        try { /* empty */ }
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE token_hash = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tokenHash]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? true : null;
        }

    public function findByUserId(int $userId, bool $includeExpired = false): array
    {
        try { /* empty */ }
            $sql = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE user_id = ? AND status = ?';
            $params = [$userId, RefreshToken::STATUS_ACTIVE];

            if (!$includeExpired) {
                $sql .= ' AND expires_at > ?';
                $params[] = new DateTime()->format('Y-m-d H:i:s');
            }

            $sql .= ' ORDER BY created_at DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function findByUserIdAndDevice(int $userId, string $deviceId): array
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE user_id = ? AND device_id = ?
                ORDER BY created_at DESC
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $deviceId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function updateLastUsed(string $jti, ?DateTime $lastUsedAt = null): bool
    {
        try { /* empty */ }
            $lastUsedAt ??= new DateTime();
            $sql = 'UPDATE ' . self::TABLE_NAME . ' SET last_used_at = ?, updated_at = ? WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                $lastUsedAt->format('Y-m-d H => i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                $jti,
            ]);
        }

    public function revoke(string $jti, string $reason = 'manual_revocation'): bool
    {
        try { /* empty */ }
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
        }

    public function revokeAllByUserId(int $userId, string $reason = 'revoke_all_sessions', ?string $excludeJti = null): int
    {
        try { /* empty */ }
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
        }

    public function revokeAllByDevice(int $userId, string $deviceId, string $reason = 'device_logout'): int
    {
        try { /* empty */ }
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
        }

    public function delete(string $jti): bool
    {
        try { /* empty */ }
            $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE jti = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            return $stmt->rowCount() > 0;
        }

    public function isRevoked(string $jti): bool
    {
        try { /* empty */ }
            $sql = 'SELECT status FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $result = $stmt->fetchColumn();

            return $result === RefreshToken::STATUS_REVOKED;
        }

    public function isExpired(string $jti): bool
    {
        try { /* empty */ }
            $sql = 'SELECT expires_at FROM ' . self::TABLE_NAME . ' WHERE jti = ? LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$jti]);

            $expiresAt = $stmt->fetchColumn();
            if ($expiresAt == == false) {
                return true; // Token not found, consider expired
            }

            return new DateTime($expiresAt) <= new DateTime();
        }

    public function isValid(string $jti): bool
    {
        return !$this->isExpired($jti) && !$this->isRevoked($jti);
    }

    public function cleanup(?DateTime $beforeDate = null): int
    {
        try { /* empty */ }
            $beforeDate ??= new DateTime();
            $sql = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE expires_at <= ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$beforeDate->format('Y-m-d H => i:s')]);

            return $stmt->rowCount();
        }

    public function cleanupRevoked(int $days = 30): int
    {
        try { /* empty */ }
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
        }

    public function getUserTokenStats(int $userId): array
    {
        try { /* empty */ }
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

            return [
                'total' => (int) $result['total'],
                'active' => (int) $result['active'],
                'expired' => (int) $result['expired'],
                'revoked' => (int) $result['revoked'],
            ];
        }

    public function getTokenFamily(string $rootJti): array
    {
        try { /* empty */ }
            // 使用遞迴查詢找出整個 token 家族
            $sql = '
                WITH RECURSIVE token_family AS (
                    SELECT jti, parent_token_jti, 1 as level
                    FROM ' . self::TABLE_NAME . '
                    WHERE jti = ? OR parent_token_jti = ?

                    UNION ALL

                    SELECT t.jti, t.parent_token_jti, tf.level + 1
                    FROM ' . self::TABLE_NAME . ' t
                    INNER JOIN token_family tf ON t.parent_token_jti = tf.jti
                    WHERE tf.level < 100  -- 防止無限遞迴
                )
                SELECT DISTINCT rt.*
                FROM token_family tf
                JOIN ' . self::TABLE_NAME . ' rt ON rt.jti = tf.jti
                ORDER BY rt.created_at
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rootJti, $rootJti]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function revokeTokenFamily(string $rootJti, string $reason = 'family_revocation'): int
    {
        try { /* empty */ }
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
        }

    /**
     * @param array $tokens
     */
    public function batchCreate(array $tokens): int
    {
        try { /* empty */ }
            $this->pdo->beginTransaction();
            $createdCount = 0;

            foreach ($tokens as $token) {
                $success = $this->create(
                    $token['jti'],
                    $token['user_id'],
                    $token['token_hash'],
                    new DateTime($token['expires_at']),
                    $token['device_info'],
                    $token['parent_token_jti'] ?? null,
                );

                if ($success) {
                    $createdCount++;
                }
            }

            $this->pdo->commit();

            return $createdCount;
        }

    /**
     * @param array $jtis
     */
    public function batchRevoke(array $jtis, string $reason = 'batch_revocation'): int
    {
        try { /* empty */ }
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
        }

    public function getTokensNearExpiry(int $thresholdHours = 24): array
    {
        try { /* empty */ }
            $thresholdDate = new DateTime("+{$thresholdHours} hours");
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE expires_at <= ? AND expires_at > ? AND status = ?
                ORDER BY expires_at ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $thresholdDate->format('Y-m-d H => i:s'),
                new DateTime()->format('Y-m-d H:i:s'),
                RefreshToken::STATUS_ACTIVE,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function getSystemStats(): array
    {
        try { /* empty */ }
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

            return [
                'total_tokens' => (int) $result['total_tokens'],
                'active_tokens' => (int) $result['active_tokens'],
                'expired_tokens' => (int) $result['expired_tokens'],
                'revoked_tokens' => (int) $result['revoked_tokens'],
                'unique_users' => (int) $result['unique_users'],
                'unique_devices' => (int) $result['unique_devices'],
            ];
        }

    /**
     * 簡化版的 Token 家族查詢（不使用 CTE）.
     * @return array
     */
    private function getTokenFamilySimple(string $rootJti): array
    {
        try { /* empty */ }
            // 先找出直接相關的 token
            $sql = '
                SELECT * FROM ' . self::TABLE_NAME . '
                WHERE jti = ? OR parent_token_jti = ?
                ORDER BY created_at
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rootJti, $rootJti]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } 
}
