<?php

declare(strict_types=1);

namespace AlleyNote\Infrastructure\Auth\Repositories;

use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;

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
     * 將 token 加入黑名單.
     *
     * @param TokenBlacklistEntry $entry 黑名單項目
     * @return bool 加入成功時回傳 true
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
            // 處理重複鍵值錯誤
            if ($this->isDuplicateKeyError($e)) {
                return false;
            }

            throw $e;
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
            $sql = '
                SELECT COUNT(*) 
                FROM token_blacklist 
                WHERE jti = :jti 
                AND expires_at > NOW()
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
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
            // 假設我們在 metadata 中儲存 token_hash
            $sql = '
                SELECT COUNT(*) 
                FROM token_blacklist 
                WHERE JSON_EXTRACT(metadata, "$.token_hash") = :token_hash 
                AND expires_at > NOW()
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['token_hash' => $tokenHash]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
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
        } catch (PDOException) {
            return false;
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
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE jti = :jti
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            return $this->createEntryFromRow($row);
        } catch (PDOException) {
            return null;
        }
    }

    /**
     * 取得使用者的所有黑名單項目.
     *
     * @param int $userId 使用者 ID
     * @param int|null $limit 限制數量，null 時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByUserId(int $userId, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE user_id = :user_id
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);

            if ($limit !== null) {
                $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 取得特定裝置的黑名單項目.
     *
     * @param string $deviceId 裝置 ID
     * @param int|null $limit 限制數量，null 時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByDeviceId(string $deviceId, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE device_id = :device_id
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('device_id', $deviceId, PDO::PARAM_STR);

            if ($limit !== null) {
                $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 根據 token 類型查找項目.
     *
     * @param string $tokenType token 類型
     * @param int|null $limit 限制數量，null 時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByTokenType(string $tokenType, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE token_type = :token_type
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('token_type', $tokenType, PDO::PARAM_STR);

            if ($limit !== null) {
                $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 根據黑名單原因查找項目.
     *
     * @param string $reason 黑名單原因
     * @param int|null $limit 限制數量，null 時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByReason(string $reason, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE reason = :reason
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('reason', $reason, PDO::PARAM_STR);

            if ($limit !== null) {
                $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 批次將 token 加入黑名單.
     *
     * @param array<int, TokenBlacklistEntry> $entries 黑名單項目陣列
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
                try {
                    $data = $entry->toDatabaseArray();
                    if ($stmt->execute([
                        'jti' => $data['jti'],
                        'token_type' => $data['token_type'],
                        'user_id' => $data['user_id'],
                        'expires_at' => $data['expires_at'],
                        'blacklisted_at' => $data['blacklisted_at'],
                        'reason' => $data['reason'],
                        'device_id' => $data['device_id'],
                        'metadata' => $data['metadata'],
                    ])) {
                        $successCount++;
                    }
                } catch (PDOException $e) {
                    // 忽略重複鍵值錯誤，繼續處理其他項目
                    if (!$this->isDuplicateKeyError($e)) {
                        throw $e;
                    }
                }
            }

            $this->pdo->commit();

            return $successCount;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    /**
     * 批次檢查 token 是否在黑名單中.
     *
     * @param array<int, string> $jtis JTI 陣列
     * @return array<string, bool> JTI 為 key，是否在黑名單為值的陣列
     */
    public function batchIsBlacklisted(array $jtis): array
    {
        if (empty($jtis)) {
            return [];
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = "
                SELECT jti 
                FROM token_blacklist 
                WHERE jti IN ({$placeholders}) 
                AND expires_at > NOW()
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($jtis);

            $blacklistedJtis = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $blacklistedSet = array_flip($blacklistedJtis);

            $result = [];
            foreach ($jtis as $jti) {
                $result[$jti] = isset($blacklistedSet[$jti]);
            }

            return $result;
        } catch (PDOException) {
            // 發生錯誤時，預設所有都不在黑名單中
            return array_fill_keys($jtis, false);
        }
    }

    /**
     * 批次從黑名單移除 token.
     *
     * @param array<int, string> $jtis JTI 陣列
     * @return int 成功移除的數量
     */
    public function batchRemoveFromBlacklist(array $jtis): int
    {
        if (empty($jtis)) {
            return 0;
        }

        try {
            $placeholders = str_repeat('?,', count($jtis) - 1) . '?';
            $sql = "DELETE FROM token_blacklist WHERE jti IN ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($jtis);

            return $stmt->rowCount();
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * 將使用者的所有 token 加入黑名單.
     *
     * @param int $userId 使用者 ID
     * @param string $reason 黑名單原因
     * @param string|null $excludeJti 排除的 JTI
     * @return int 加入黑名單的 token 數量
     */
    public function blacklistAllUserTokens(int $userId, string $reason, ?string $excludeJti = null): int
    {
        try {
            // 先查找該使用者所有的 refresh token
            $selectSql = '
                SELECT jti 
                FROM refresh_tokens 
                WHERE user_id = :user_id 
                AND revoked = 0 
                AND expires_at > NOW()
            ';

            if ($excludeJti !== null) {
                $selectSql .= ' AND jti != :exclude_jti';
            }

            $stmt = $this->pdo->prepare($selectSql);
            $params = ['user_id' => $userId];
            if ($excludeJti !== null) {
                $params['exclude_jti'] = $excludeJti;
            }
            $stmt->execute($params);

            $jtis = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($jtis)) {
                return 0;
            }

            // 建立黑名單項目
            $entries = [];
            $now = new DateTimeImmutable();
            $futureExpiry = $now->modify('+1 day'); // 給一個合理的過期時間

            foreach ($jtis as $jti) {
                $entries[] = new TokenBlacklistEntry(
                    jti: $jti,
                    tokenType: TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
                    expiresAt: $futureExpiry,
                    blacklistedAt: $now,
                    reason: $reason,
                    userId: $userId,
                );
            }

            return $this->batchAddToBlacklist($entries);
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * 將特定裝置的所有 token 加入黑名單.
     *
     * @param string $deviceId 裝置 ID
     * @param string $reason 黑名單原因
     * @return int 加入黑名單的 token 數量
     */
    public function blacklistAllDeviceTokens(string $deviceId, string $reason): int
    {
        try {
            // 查找該裝置所有的 refresh token
            $selectSql = '
                SELECT jti, user_id 
                FROM refresh_tokens 
                WHERE device_id = :device_id 
                AND revoked = 0 
                AND expires_at > NOW()
            ';

            $stmt = $this->pdo->prepare($selectSql);
            $stmt->execute(['device_id' => $deviceId]);

            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($tokens)) {
                return 0;
            }

            // 建立黑名單項目
            $entries = [];
            $now = new DateTimeImmutable();
            $futureExpiry = $now->modify('+1 day');

            foreach ($tokens as $token) {
                $entries[] = new TokenBlacklistEntry(
                    jti: $token['jti'],
                    tokenType: TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
                    expiresAt: $futureExpiry,
                    blacklistedAt: $now,
                    reason: $reason,
                    userId: (int) $token['user_id'],
                    deviceId: $deviceId,
                );
            }

            return $this->batchAddToBlacklist($entries);
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * 清理過期的黑名單項目.
     *
     * @param DateTime|null $beforeDate 清理此日期之前的記錄，null 時清理所有過期項目
     * @return int 清理的記錄數量
     */
    public function cleanup(?DateTime $beforeDate = null): int
    {
        try {
            if ($beforeDate === null) {
                $sql = 'DELETE FROM token_blacklist WHERE expires_at <= NOW()';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            } else {
                $sql = 'DELETE FROM token_blacklist WHERE expires_at <= :before_date';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['before_date' => $beforeDate->format('Y-m-d H:i:s')]);
            }

            return $stmt->rowCount();
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * 清理可清理的黑名單項目（根據 TokenBlacklistEntry 的 canBeCleanedUp 方法）.
     *
     * @return int 清理的記錄數量
     */
    public function cleanupExpiredEntries(): int
    {
        return $this->cleanup();
    }

    /**
     * 清理舊的黑名單項目（超過指定天數）.
     *
     * @param int $days 保留天數，預設 90 天
     * @return int 清理的記錄數量
     */
    public function cleanupOldEntries(int $days = 90): int
    {
        try {
            $sql = '
                DELETE FROM token_blacklist 
                WHERE blacklisted_at <= DATE_SUB(NOW(), INTERVAL :days DAY)
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['days' => $days]);

            return $stmt->rowCount();
        } catch (PDOException) {
            return 0;
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
            // 總項目數
            $totalSql = 'SELECT COUNT(*) FROM token_blacklist';
            $totalStmt = $this->pdo->prepare($totalSql);
            $totalStmt->execute();
            $total = (int) $totalStmt->fetchColumn();

            // 按 token 類型統計
            $typeSql = 'SELECT token_type, COUNT(*) as count FROM token_blacklist GROUP BY token_type';
            $typeStmt = $this->pdo->prepare($typeSql);
            $typeStmt->execute();
            $byTokenType = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // 按原因統計
            $reasonSql = 'SELECT reason, COUNT(*) as count FROM token_blacklist GROUP BY reason';
            $reasonStmt = $this->pdo->prepare($reasonSql);
            $reasonStmt->execute();
            $byReason = $reasonStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // 安全相關的項目
            $securityReasons = [
                TokenBlacklistEntry::REASON_SECURITY_BREACH,
                TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY,
                TokenBlacklistEntry::REASON_DEVICE_LOST,
                TokenBlacklistEntry::REASON_INVALID_SIGNATURE,
            ];
            $securitySql = 'SELECT COUNT(*) FROM token_blacklist WHERE reason IN ("' . implode('","', $securityReasons) . '")';
            $securityStmt = $this->pdo->prepare($securitySql);
            $securityStmt->execute();
            $securityRelated = (int) $securityStmt->fetchColumn();

            // 使用者主動的項目
            $userReasons = [
                TokenBlacklistEntry::REASON_LOGOUT,
                TokenBlacklistEntry::REASON_MANUAL_REVOCATION,
                TokenBlacklistEntry::REASON_DEVICE_LOST,
            ];
            $userSql = 'SELECT COUNT(*) FROM token_blacklist WHERE reason IN ("' . implode('","', $userReasons) . '")';
            $userStmt = $this->pdo->prepare($userSql);
            $userStmt->execute();
            $userInitiated = (int) $userStmt->fetchColumn();

            // 系統主動的項目
            $systemInitiated = $total - $userInitiated;

            return [
                'total' => $total,
                'by_token_type' => $byTokenType,
                'by_reason' => $byReason,
                'security_related' => $securityRelated,
                'user_initiated' => $userInitiated,
                'system_initiated' => $systemInitiated,
            ];
        } catch (PDOException) {
            return [
                'total' => 0,
                'by_token_type' => [],
                'by_reason' => [],
                'security_related' => 0,
                'user_initiated' => 0,
                'system_initiated' => 0,
            ];
        }
    }

    /**
     * 取得特定使用者的黑名單統計.
     *
     * @param int $userId 使用者 ID
     * @return array<string, mixed> 使用者統計資訊
     */
    public function getUserBlacklistStats(int $userId): array
    {
        try {
            $sql = '
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN token_type = "access" THEN 1 END) as access_tokens,
                    COUNT(CASE WHEN token_type = "refresh" THEN 1 END) as refresh_tokens,
                    COUNT(CASE WHEN reason IN ("security_breach", "suspicious_activity", "device_lost", "invalid_signature") THEN 1 END) as security_related,
                    MAX(blacklisted_at) as last_blacklisted
                FROM token_blacklist 
                WHERE user_id = :user_id
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total' => 0,
                'access_tokens' => 0,
                'refresh_tokens' => 0,
                'security_related' => 0,
                'last_blacklisted' => null,
            ];
        } catch (PDOException) {
            return [
                'total' => 0,
                'access_tokens' => 0,
                'refresh_tokens' => 0,
                'security_related' => 0,
                'last_blacklisted' => null,
            ];
        }
    }

    /**
     * 取得最近的黑名單項目.
     *
     * @param int $limit 限制數量，預設 100
     * @param DateTime|null $since 起始時間，null 時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getRecentBlacklistEntries(int $limit = 100, ?DateTime $since = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
            ';

            if ($since !== null) {
                $sql .= ' WHERE blacklisted_at >= :since';
            }

            $sql .= ' ORDER BY blacklisted_at DESC LIMIT :limit';

            $stmt = $this->pdo->prepare($sql);

            if ($since !== null) {
                $stmt->bindValue('since', $since->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            }
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 取得高優先級的黑名單項目.
     *
     * @param int $limit 限制數量，預設 50
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getHighPriorityEntries(int $limit = 50): array
    {
        try {
            // 優先取得安全相關的項目
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE reason IN ("security_breach", "suspicious_activity", "device_lost", "invalid_signature")
                ORDER BY blacklisted_at DESC 
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * 搜尋黑名單項目.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     * @param int|null $limit 限制數量，null 時不限制
     * @param int $offset 偏移量，預設 0
     * @return array<int, TokenBlacklistEntry> 搜尋結果
     */
    public function search(array $criteria, ?int $limit = null, int $offset = 0): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist 
                WHERE 1=1
            ';

            $params = [];

            if (!empty($criteria['user_id'])) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = (int) $criteria['user_id'];
            }

            if (!empty($criteria['device_id'])) {
                $sql .= ' AND device_id = :device_id';
                $params['device_id'] = $criteria['device_id'];
            }

            if (!empty($criteria['token_type'])) {
                $sql .= ' AND token_type = :token_type';
                $params['token_type'] = $criteria['token_type'];
            }

            if (!empty($criteria['reason'])) {
                $sql .= ' AND reason = :reason';
                $params['reason'] = $criteria['reason'];
            }

            if (!empty($criteria['date_from'])) {
                $sql .= ' AND blacklisted_at >= :date_from';
                $params['date_from'] = $criteria['date_from'];
            }

            if (!empty($criteria['date_to'])) {
                $sql .= ' AND blacklisted_at <= :date_to';
                $params['date_to'] = $criteria['date_to'];
            }

            $sql .= ' ORDER BY blacklisted_at DESC';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit OFFSET :offset';
                $params['limit'] = $limit;
                $params['offset'] = $offset;
            }

            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                if ($key === 'limit' || $key === 'offset' || $key === 'user_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException) {
            return [];
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

            if (!empty($criteria['user_id'])) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = (int) $criteria['user_id'];
            }

            if (!empty($criteria['device_id'])) {
                $sql .= ' AND device_id = :device_id';
                $params['device_id'] = $criteria['device_id'];
            }

            if (!empty($criteria['token_type'])) {
                $sql .= ' AND token_type = :token_type';
                $params['token_type'] = $criteria['token_type'];
            }

            if (!empty($criteria['reason'])) {
                $sql .= ' AND reason = :reason';
                $params['reason'] = $criteria['reason'];
            }

            if (!empty($criteria['date_from'])) {
                $sql .= ' AND blacklisted_at >= :date_from';
                $params['date_from'] = $criteria['date_from'];
            }

            if (!empty($criteria['date_to'])) {
                $sql .= ' AND blacklisted_at <= :date_to';
                $params['date_to'] = $criteria['date_to'];
            }

            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                if ($key === 'user_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }

            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    /**
     * 檢查黑名單大小是否超過限制.
     *
     * @param int $maxSize 最大大小，預設 100000
     * @return bool 超過限制時回傳 true
     */
    public function isSizeExceeded(int $maxSize = 100000): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM token_blacklist';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return (int) $stmt->fetchColumn() > $maxSize;
        } catch (PDOException) {
            return false;
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
            $sql = '
                SELECT 
                    COUNT(*) as total_entries,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_entries,
                    COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_entries
                FROM token_blacklist
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalEntries = (int) $result['total_entries'];
            $activeEntries = (int) $result['active_entries'];
            $expiredEntries = (int) $result['expired_entries'];

            // 粗略估算大小（每個項目平均約 200 bytes）
            $estimatedSizeMb = ($totalEntries * 200) / (1024 * 1024);

            return [
                'total_entries' => $totalEntries,
                'active_entries' => $activeEntries,
                'expired_entries' => $expiredEntries,
                'cleanable_entries' => $expiredEntries, // 過期的就是可清理的
                'estimated_size_mb' => round($estimatedSizeMb, 2),
            ];
        } catch (PDOException) {
            return [
                'total_entries' => 0,
                'active_entries' => 0,
                'expired_entries' => 0,
                'cleanable_entries' => 0,
                'estimated_size_mb' => 0.0,
            ];
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

            // 1. 清理過期項目
            $cleanedEntries = $this->cleanupExpiredEntries();

            // 2. 檢查項目總數，如果有太多項目，清理舊項目
            $totalCountSql = 'SELECT COUNT(*) FROM token_blacklist';
            $totalStmt = $this->pdo->prepare($totalCountSql);
            $totalStmt->execute();
            $totalEntries = (int) $totalStmt->fetchColumn();

            if ($totalEntries > 50000) {
                $cleanedEntries += $this->cleanupOldEntries(30); // 清理 30 天前的項目
            }

            // 3. 資料庫優化 (SQLite 的 VACUUM)
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $this->pdo->exec('VACUUM');
            }

            $this->pdo->commit();

            $executionTime = microtime(true) - $startTime;
            $newSizeInfo = $this->getSizeInfo();

            return [
                'cleaned_entries' => $cleanedEntries,
                'compacted_size' => $newSizeInfo['estimated_size_mb'],
                'execution_time' => round($executionTime, 2),
                'total_entries_after' => $newSizeInfo['total_entries'],
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            $executionTime = microtime(true) - $startTime;

            return [
                'cleaned_entries' => 0,
                'compacted_size' => 0,
                'execution_time' => round($executionTime, 2),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 從資料庫記錄建立 TokenBlacklistEntry.
     *
     * @param array<string, mixed> $row 資料庫記錄
     * @return TokenBlacklistEntry 黑名單項目
     */
    private function createEntryFromRow(array $row): TokenBlacklistEntry
    {
        $metadata = [];
        if (!empty($row['metadata'])) {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        return new TokenBlacklistEntry(
            jti: $row['jti'],
            tokenType: $row['token_type'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            blacklistedAt: new DateTimeImmutable($row['blacklisted_at']),
            reason: $row['reason'],
            userId: $row['user_id'] !== null ? (int) $row['user_id'] : null,
            deviceId: $row['device_id'],
            metadata: $metadata,
        );
    }

    /**
     * 檢查是否為重複鍵值錯誤.
     *
     * @param PDOException $e PDO 例外
     * @return bool 是重複鍵值錯誤時回傳 true
     */
    private function isDuplicateKeyError(PDOException $e): bool
    {
        // SQLite 的重複鍵值錯誤碼
        return $e->getCode() === '23000' && str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
