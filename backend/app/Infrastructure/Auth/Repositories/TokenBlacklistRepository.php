<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Repositories;

use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use DateTimeImmutable;
use Exception;
use PDO;
use PDOException;

/**
 * Token 黑名單 Repository 實作.
 *
 * 提供 Token 黑名單的資料庫存取操作，包含新增、查詢、刪除等功能。
 * 負責管理被撤銷或無效 token 的黑名單記錄，確保 token 安全性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenBlacklistRepository implements TokenBlacklistRepositoryInterface
{
    /**
     * 建構 Token 黑名單 Repository.
     *
     * @param PDO $pdo 資料庫連線
     */
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * 將token加入黑名單.
     *
     * @param TokenBlacklistEntry $entry 黑名單項目
     * @return bool 是否成功
     */
    public function addToBlacklist(TokenBlacklistEntry $entry): bool
    {
        try {
            $sql = '
                INSERT INTO token_blacklist (
                    jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                ) VALUES (
                    :jti, :token_type, :user_id, :expires_at, :blacklisted_at, :reason, :device_id, :metadata
                )
            ';

            $stmt = $this->pdo->prepare($sql);

            $data = $entry->toDatabaseArray();

            return $stmt->execute([
                'jti' => $data['jti'],
                'token_type' => $data['token_type'],
                'user_id' => $data['user_id'],
                'expires_at' => $data['expires_at'],
                'blacklisted_at' => $data['blacklisted_at'],
                'reason' => $data['reason'],
                'device_id' => $data['device_id'],
                'metadata' => $data['metadata'],
            ]);
        } catch (PDOException $e) {
            throw new Exception('無法將 Token 加入黑名單: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查 token 是否在黑名單中.
     *
     * @param string $jti JWT ID
     * @return bool 在黑名單中時回傳 true
     */
    public function isBlacklisted(string $jti): bool
    {
        try {
            $currentTime = new DateTime();
            $sql = '
                SELECT COUNT(*)
                FROM token_blacklist
                WHERE jti = :jti
                AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'jti' => $jti,
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法檢查 Token 黑名單狀態: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查 token 是否在黑名單中（根據 token hash）.
     *
     * @param string $tokenHash token 的雜湊值
     * @return bool 在黑名單中時回傳 true
     */
    public function isTokenHashBlacklisted(string $tokenHash): bool
    {
        try {
            $currentTime = new DateTime();
            // 假設我們在 metadata 中儲存 token_hash
            $sql = '
                SELECT COUNT(*)
                FROM token_blacklist
                WHERE JSON_EXTRACT(metadata, "$.token_hash") = :token_hash
                AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'token_hash' => $tokenHash,
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法檢查 Token Hash 黑名單狀態: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 從黑名單中移除 token.
     *
     * @param string $jti JWT ID
     * @return bool 移除成功時回傳 true
     */
    public function removeFromBlacklist(string $jti): bool
    {
        try {
            $sql = 'DELETE FROM token_blacklist WHERE jti = :jti';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法從黑名單移除 Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據 JTI 查找黑名單項目.
     *
     * @param string $jti JWT ID
     * @return TokenBlacklistEntry|null 黑名單項目，找不到時回傳 null
     */
    public function findByJti(string $jti): ?TokenBlacklistEntry
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE jti = :jti
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return null;
            }

            /** @var array<string, mixed> $result */
            return TokenBlacklistEntry::fromArray($result);
        } catch (PDOException $e) {
            throw new Exception('無法查詢黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者的所有黑名單項目.
     *
     * @param int $userId 使用者 ID
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByUserId(int $userId, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE user_id = :user_id
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $params = ['user_id' => $userId];
            if ($limit !== null) {
                $params['limit'] = $limit;
            }
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('無法查詢使用者的黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得特定裝置的黑名單項目.
     *
     * @param string $deviceId 裝置ID
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByDeviceId(string $deviceId, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE device_id = :device_id
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $params = ['device_id' => $deviceId];
            if ($limit !== null) {
                $params['limit'] = $limit;
            }
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('無法查詢裝置的黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據黑名單原因查找項目.
     *
     * @param string $reason 黑名單原因
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByReason(string $reason, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE reason = :reason
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $params = ['reason' => $reason];
            if ($limit !== null) {
                $params['limit'] = $limit;
            }
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('無法根據原因查詢黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批次將token加入黑名單.
     *
     * @param array<TokenBlacklistEntry> $entries 黑名單項目陣列
     * @return int 成功加入的數量
     */
    public function batchAddToBlacklist(array $entries): int
    {
        if (empty($entries)) {
            return 0;
        }

        try {
            $this->pdo->beginTransaction();

            $sql = '
                INSERT INTO token_blacklist (
                    jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                ) VALUES (
                    :jti, :token_type, :user_id, :expires_at, :blacklisted_at, :reason, :device_id, :metadata
                )
            ';

            $stmt = $this->pdo->prepare($sql);
            $successCount = 0;

            foreach ($entries as $entry) {
                $data = $entry->toDatabaseArray();
                $result = $stmt->execute([
                    'jti' => $data['jti'],
                    'token_type' => $data['token_type'],
                    'user_id' => $data['user_id'],
                    'expires_at' => $data['expires_at'],
                    'blacklisted_at' => $data['blacklisted_at'],
                    'reason' => $data['reason'],
                    'device_id' => $data['device_id'],
                    'metadata' => $data['metadata'],
                ]);

                if ($result) {
                    $successCount++;
                }
            }

            $this->pdo->commit();

            return $successCount;
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new Exception('批次加入黑名單失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批次檢查token是否在黑名單中.
     *
     * @param array<string> $jtis JTI陣列
     * @return array<string, bool> JTI為key，是否在黑名單為值的陣列
     */
    public function batchIsBlacklisted(array $jtis): array
    {
        if (empty($jtis)) {
            return [];
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $currentTime = new DateTime();
            $sql = "
                SELECT jti FROM token_blacklist
                WHERE jti IN ($placeholders)
                AND expires_at > ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $params = [...$jtis, $currentTime->format('Y-m-d H:i:s')];
            $stmt->execute($params);

            $blacklistedJtis = $stmt->fetchAll(PDO::FETCH_COLUMN);
            /** @var array<int, string> $blacklistedJtis */
            $blacklistedSet = array_flip($blacklistedJtis);

            $result = [];
            foreach ($jtis as $jti) {
                $result[$jti] = isset($blacklistedSet[$jti]);
            }

            return $result;
        } catch (PDOException $e) {
            throw new Exception('批次檢查黑名單狀態失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批次從黑名單移除token.
     *
     * @param array<string> $jtis JTI陣列
     * @return int 成功移除的數量
     */
    public function batchRemoveFromBlacklist(array $jtis): int
    {
        if (empty($jtis)) {
            return 0;
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = "DELETE FROM token_blacklist WHERE jti IN ($placeholders)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($jtis);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('批次移除黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 將使用者的所有token加入黑名單.
     *
     * @param int $userId 使用者ID
     * @param string $reason 黑名單原因
     * @param string|null $excludeJti 排除的JTI
     * @return int 加入黑名單的token數量
     */
    public function blacklistAllUserTokens(int $userId, string $reason, ?string $excludeJti = null): int
    {
        try {
            // 從 refresh_tokens 表查找用戶的所有活躍 token
            $sql = 'SELECT jti, device_id, expires_at FROM refresh_tokens WHERE user_id = ? AND status = ?';
            $params = [$userId, 'active'];

            if ($excludeJti !== null) {
                $sql .= ' AND jti != ?';
                $params[] = $excludeJti;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tokens)) {
                return 0;
            }

            $entries = [];
            $currentTime = new DateTime();

            foreach ($tokens as $token) {
                $entries[] = new TokenBlacklistEntry(
                    jti: (string) $token['jti'],
                    tokenType: 'refresh_token',
                    expiresAt: new DateTimeImmutable((string) $token['expires_at']),
                    blacklistedAt: new DateTimeImmutable(),
                    reason: $reason,
                    userId: $userId,
                    deviceId: $token['device_id'] ? (string) $token['device_id'] : null,
                );
            }

            return $this->batchAddToBlacklist($entries);
        } catch (PDOException $e) {
            throw new Exception('將用戶所有 token 加入黑名單失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 將特定裝置的所有token加入黑名單.
     *
     * @param string $deviceId 裝置ID
     * @param string $reason 黑名單原因
     * @return int 加入黑名單的token數量
     */
    public function blacklistAllDeviceTokens(string $deviceId, string $reason): int
    {
        try {
            // 從 refresh_tokens 表查找裝置的所有活躍 token
            $sql = 'SELECT jti, user_id, expires_at FROM refresh_tokens WHERE device_id = ? AND status = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$deviceId, 'active']);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tokens)) {
                return 0;
            }

            $entries = [];

            foreach ($tokens as $token) {
                $entries[] = new TokenBlacklistEntry(
                    jti: (string) $token['jti'],
                    tokenType: 'refresh_token',
                    expiresAt: new DateTimeImmutable((string) $token['expires_at']),
                    blacklistedAt: new DateTimeImmutable(),
                    reason: $reason,
                    userId: (int) $token['user_id'],
                    deviceId: $deviceId,
                );
            }

            return $this->batchAddToBlacklist($entries);
        } catch (PDOException $e) {
            throw new Exception('將裝置所有 token 加入黑名單失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理可清理的黑名單項目.
     *
     * @return int 清理的記錄數量
     */
    public function cleanupExpiredEntries(): int
    {
        try {
            $currentTime = new DateTime();
            $sql = 'DELETE FROM token_blacklist WHERE expires_at <= ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentTime->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('清理過期黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理舊的黑名單項目.
     *
     * @param int $days 保留天數，預設90天
     * @return int 清理的記錄數量
     */
    public function cleanupOldEntries(int $days = 90): int
    {
        try {
            $cutoffDate = new DateTime();
            $cutoffDate->modify("-{$days} days");

            $sql = 'DELETE FROM token_blacklist WHERE blacklisted_at < ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cutoffDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('清理舊黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得黑名單統計資訊.
     *
     * @return array<string, mixed> 統計資訊
     */
    public function getBlacklistStats(): array
    {
        try {
            $currentTime = new DateTime();
            $sql = '
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expired,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT device_id) as unique_devices,
                    COUNT(CASE WHEN reason LIKE "%security%" OR reason LIKE "%breach%" THEN 1 END) as security_related,
                    COUNT(CASE WHEN reason LIKE "%user%" OR reason LIKE "%manual%" THEN 1 END) as user_initiated,
                    COUNT(CASE WHEN reason LIKE "%system%" OR reason LIKE "%auto%" THEN 1 END) as system_initiated
                FROM token_blacklist
            ';

            $stmt = $this->pdo->prepare($sql);
            $nowFormatted = $currentTime->format('Y-m-d H:i:s');
            $stmt->execute([$nowFormatted, $nowFormatted]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // 按 token 類型分組統計
            $typeStatsSql = 'SELECT token_type, COUNT(*) as count FROM token_blacklist GROUP BY token_type';
            $typeStmt = $this->pdo->prepare($typeStatsSql);
            $typeStmt->execute();
            $typeStats = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // 按原因分組統計
            $reasonStatsSql = 'SELECT reason, COUNT(*) as count FROM token_blacklist GROUP BY reason ORDER BY count DESC LIMIT 10';
            $reasonStmt = $this->pdo->prepare($reasonStatsSql);
            $reasonStmt->execute();
            $reasonStats = $reasonStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            return [
                'total' => (int) $result['total'],
                'active' => (int) $result['active'],
                'expired' => (int) $result['expired'],
                'unique_users' => (int) $result['unique_users'],
                'unique_devices' => (int) $result['unique_devices'],
                'security_related' => (int) $result['security_related'],
                'user_initiated' => (int) $result['user_initiated'],
                'system_initiated' => (int) $result['system_initiated'],
                'by_token_type' => $typeStats,
                'by_reason' => $reasonStats,
            ];
        } catch (PDOException $e) {
            throw new Exception('取得黑名單統計資訊失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得特定使用者的黑名單統計.
     *
     * @param int $userId 使用者ID
     * @return array<string, mixed> 使用者統計資訊
     */
    public function getUserBlacklistStats(int $userId): array
    {
        try {
            $currentTime = new DateTime();
            $sql = '
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expired,
                    COUNT(DISTINCT device_id) as unique_devices
                FROM token_blacklist
                WHERE user_id = ?
            ';

            $stmt = $this->pdo->prepare($sql);
            $nowFormatted = $currentTime->format('Y-m-d H:i:s');
            $stmt->execute([$nowFormatted, $nowFormatted, $userId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int) $result['total'],
                'active' => (int) $result['active'],
                'expired' => (int) $result['expired'],
                'unique_devices' => (int) $result['unique_devices'],
            ];
        } catch (PDOException $e) {
            throw new Exception('取得用戶黑名單統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得最近的黑名單項目.
     *
     * @param int $limit 限制數量，預設100
     * @param DateTime|null $since 起始時間
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getRecentBlacklistEntries(int $limit = 100, ?DateTime $since = null): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE 1=1
            ';

            $params = [];

            if ($since !== null) {
                $sql .= ' AND blacklisted_at >= ?';
                $params[] = $since->format('Y-m-d H:i:s');
            }

            $sql .= ' ORDER BY blacklisted_at DESC LIMIT ?';
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('取得最近黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得高優先級的黑名單項目.
     *
     * @param int $limit 限制數量，預設50
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getHighPriorityEntries(int $limit = 50): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE reason LIKE "%security%"
                   OR reason LIKE "%breach%"
                   OR reason LIKE "%suspicious%"
                ORDER BY blacklisted_at DESC
                LIMIT ?
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('取得高優先級黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 搜尋黑名單項目.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     * @param int|null $limit 限制數量
     * @param int $offset 偏移量
     * @return array<TokenBlacklistEntry> 搜尋結果
     */
    public function search(array $criteria, ?int $limit = null, int $offset = 0): array
    {
        try {
            $sql = 'SELECT * FROM token_blacklist WHERE 1=1';
            $params = [];

            if (isset($criteria['user_id'])) {
                $sql .= ' AND user_id = ?';
                $params[] = $criteria['user_id'];
            }

            if (isset($criteria['device_id'])) {
                $sql .= ' AND device_id = ?';
                $params[] = $criteria['device_id'];
            }

            if (isset($criteria['token_type'])) {
                $sql .= ' AND token_type = ?';
                $params[] = $criteria['token_type'];
            }

            if (isset($criteria['reason'])) {
                $sql .= ' AND reason LIKE ?';
                $params[] = '%' . (string) $criteria['reason'] . '%';
            }

            if (isset($criteria['date_from'])) {
                $sql .= ' AND blacklisted_at >= ?';
                $params[] = $criteria['date_from'];
            }

            if (isset($criteria['date_to'])) {
                $sql .= ' AND blacklisted_at <= ?';
                $params[] = $criteria['date_to'];
            }

            $sql .= ' ORDER BY blacklisted_at DESC';

            if ($limit !== null) {
                $sql .= ' LIMIT ? OFFSET ?';
                $params[] = $limit;
                $params[] = $offset;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                /** @var array<string, mixed> $result */
                $entries[] = TokenBlacklistEntry::fromArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('搜尋黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算搜尋結果總數.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     * @return int 總數
     */
    public function countSearch(array $criteria): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM token_blacklist WHERE 1=1';
            $params = [];

            if (isset($criteria['user_id'])) {
                $sql .= ' AND user_id = ?';
                $params[] = $criteria['user_id'];
            }

            if (isset($criteria['device_id'])) {
                $sql .= ' AND device_id = ?';
                $params[] = $criteria['device_id'];
            }

            if (isset($criteria['token_type'])) {
                $sql .= ' AND token_type = ?';
                $params[] = $criteria['token_type'];
            }

            if (isset($criteria['reason'])) {
                $sql .= ' AND reason LIKE ?';
                $params[] = '%' . (string) $criteria['reason'] . '%';
            }

            if (isset($criteria['date_from'])) {
                $sql .= ' AND blacklisted_at >= ?';
                $params[] = $criteria['date_from'];
            }

            if (isset($criteria['date_to'])) {
                $sql .= ' AND blacklisted_at <= ?';
                $params[] = $criteria['date_to'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception('計算搜尋結果總數失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查黑名單大小是否超過限制.
     *
     * @param int $maxSize 最大大小，預設100000
     * @return bool 超過限制時回傳true
     */
    public function isSizeExceeded(int $maxSize = 100000): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM token_blacklist';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $count = (int) $stmt->fetchColumn();

            return $count > $maxSize;
        } catch (PDOException $e) {
            throw new Exception('檢查黑名單大小失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得黑名單大小資訊.
     *
     * @return array<string, mixed> 大小資訊
     */
    public function getSizeInfo(): array
    {
        try {
            $currentTime = new DateTime();
            $nowFormatted = $currentTime->format('Y-m-d H:i:s');

            $sql = '
                SELECT
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) as active_entries,
                    SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) as expired_entries
                FROM token_blacklist
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nowFormatted, $nowFormatted]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $cleanableEntries = (int) $result['expired_entries'];
            $estimatedSizeMb = ((int) $result['total_entries'] * 500) / (1024 * 1024); // 假設每筆記錄約 500 bytes

            return [
                'total_entries' => (int) $result['total_entries'],
                'active_entries' => (int) $result['active_entries'],
                'expired_entries' => (int) $result['expired_entries'],
                'cleanable_entries' => $cleanableEntries,
                'estimated_size_mb' => round($estimatedSizeMb, 2),
            ];
        } catch (PDOException $e) {
            throw new Exception('取得黑名單大小資訊失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 最佳化黑名單儲存.
     *
     * @return array<string, mixed> 最佳化結果
     */
    public function optimize(): array
    {
        $startTime = microtime(true);

        try {
            $this->pdo->beginTransaction();

            // 清理過期項目
            $cleanedEntries = $this->cleanupExpiredEntries();

            // 重整資料表
            $this->pdo->exec('VACUUM');

            $this->pdo->commit();

            $executionTime = microtime(true) - $startTime;
            $sizeInfo = $this->getSizeInfo();

            return [
                'cleaned_entries' => $cleanedEntries,
                'compacted_size' => $sizeInfo['estimated_size_mb'],
                'execution_time' => round($executionTime, 2),
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            throw new Exception('最佳化黑名單儲存失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理過期的黑名單項目.
     *
     * @param DateTime|null $beforeDate 指定清理此日期前的項目，若為 null 則清理所有過期項目
     * @return int 清理的項目數量
     */
    public function cleanup(?DateTime $beforeDate = null): int
    {
        try {
            $beforeDate ??= new DateTime();
            $sql = 'DELETE FROM token_blacklist WHERE expires_at < :before_date';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['before_date' => $beforeDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('無法清理過期的黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }
}
