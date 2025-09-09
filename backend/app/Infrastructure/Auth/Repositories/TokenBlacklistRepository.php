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
    ) {
    }

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
            if ($this->isDuplicateKeyError($e)) {
                return true; // 已經在黑名單中，視為成功
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
            throw new Exception('檢查黑名單失敗: ' . $e->getMessage(), 0, $e);
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
            throw new Exception('檢查 token hash 黑名單失敗: ' . $e->getMessage(), 0, $e);
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
            throw new Exception('移除黑名單項目失敗: ' . $e->getMessage(), 0, $e);
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
        } catch (PDOException $e) {
            throw new Exception('查找黑名單項目失敗: ' . $e->getMessage(), 0, $e);
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
        } catch (PDOException $e) {
            throw new Exception('查找使用者黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得特定裝置的黑名單項目.
     *
     * @param string $deviceId 裝置 ID
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
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
        } catch (PDOException $e) {
            throw new Exception('查找裝置黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據 token 類型查找項目.
     *
     * @param string $tokenType token 類型
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
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
        } catch (PDOException $e) {
            throw new Exception('查找 token 類型黑名單項目失敗: ' . $e->getMessage(), 0, $e);
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
        } catch (PDOException $e) {
            throw new Exception('查找黑名單原因項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得在特定時間範圍內的黑名單項目.
     *
     * @param DateTimeImmutable $startDate 開始日期
     * @param DateTimeImmutable $endDate 結束日期
     * @param int|null $limit 限制數量
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate, ?int $limit = null): array
    {
        try {
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist
                WHERE blacklisted_at BETWEEN :start_date AND :end_date
                ORDER BY blacklisted_at DESC
            ';

            if ($limit !== null) {
                $sql .= ' LIMIT :limit';
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $startDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue('end_date', $endDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);

            if ($limit !== null) {
                $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('查找日期範圍黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得所有黑名單項目（分頁）.
     *
     * @param int $page 頁碼（從 1 開始）
     * @param int $perPage 每頁數量
     * @return array<string, mixed> 包含項目和分頁資訊的陣列
     */
    public function findAll(int $page = 1, int $perPage = 50): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // 取得總數
            $countSql = 'SELECT COUNT(*) FROM token_blacklist';
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute();
            $totalCount = (int) $countStmt->fetchColumn();

            // 取得分頁資料
            $sql = '
                SELECT jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                FROM token_blacklist
                ORDER BY blacklisted_at DESC
                LIMIT :limit OFFSET :offset
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entries[] = $this->createEntryFromRow($row);
            }

            return [
                'items' => $entries,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_count' => $totalCount,
                    'total_pages' => (int) ceil($totalCount / $perPage),
                    'has_more' => ($page * $perPage) < $totalCount,
                ],
            ];
        } catch (PDOException $e) {
            throw new Exception('查找所有黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理過期的黑名單項目.
     *
     * @param DateTimeImmutable|null $before 清理此時間之前的項目，預設為當前時間
     * @return int 清理的項目數量
     */
    public function cleanupExpiredEntries(?DateTimeImmutable $before = null): int
    {
        if ($before === null) {
            $before = new DateTimeImmutable();
        }

        try {
            $sql = 'DELETE FROM token_blacklist WHERE expires_at <= :before';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['before' => $before->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('清理過期黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理舊的黑名單項目.
     *
     * @param int $daysOld 清理多少天前的項目
     * @return int 清理的項目數量
     */
    public function cleanupOldEntries(int $daysOld = 30): int
    {
        try {
            $cutoffDate = new DateTimeImmutable("-{$daysOld} days");
            $sql = 'DELETE FROM token_blacklist WHERE blacklisted_at <= :cutoff_date';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('清理舊黑名單項目失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 撤銷使用者的所有 token.
     *
     * @param int $userId 使用者 ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的 token 數量
     */
    public function revokeAllUserTokens(int $userId, string $reason = 'user_revoked'): int
    {
        try {
            $currentTime = new DateTimeImmutable();
            $sql = '
                UPDATE token_blacklist
                SET reason = :reason, blacklisted_at = :blacklisted_at
                WHERE user_id = :user_id AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'reason' => $reason,
                'blacklisted_at' => $currentTime->format('Y-m-d H:i:s'),
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('撤銷使用者所有 token 失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 撤銷裝置的所有 token.
     *
     * @param string $deviceId 裝置 ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的 token 數量
     */
    public function revokeAllDeviceTokens(string $deviceId, string $reason = 'device_revoked'): int
    {
        try {
            $currentTime = new DateTimeImmutable();
            $sql = '
                UPDATE token_blacklist
                SET reason = :reason, blacklisted_at = :blacklisted_at
                WHERE device_id = :device_id AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'device_id' => $deviceId,
                'reason' => $reason,
                'blacklisted_at' => $currentTime->format('Y-m-d H:i:s'),
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('撤銷裝置所有 token 失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得黑名單統計資訊.
     *
     * @return array<string, mixed> 統計資訊
     */
    public function getStatistics(): array
    {
        try {
            $currentTime = new DateTimeImmutable();

            // 總項目數
            $totalSql = 'SELECT COUNT(*) FROM token_blacklist';
            $totalStmt = $this->pdo->prepare($totalSql);
            $totalStmt->execute();
            $totalEntries = (int) $totalStmt->fetchColumn();

            // 有效項目數
            $activeSql = 'SELECT COUNT(*) FROM token_blacklist WHERE expires_at > :current_time';
            $activeStmt = $this->pdo->prepare($activeSql);
            $activeStmt->execute(['current_time' => $currentTime->format('Y-m-d H:i:s')]);
            $activeEntries = (int) $activeStmt->fetchColumn();

            // 過期項目數
            $expiredEntries = $totalEntries - $activeEntries;

            // 按原因分組統計
            $reasonSql = 'SELECT reason, COUNT(*) as count FROM token_blacklist GROUP BY reason ORDER BY count DESC';
            $reasonStmt = $this->pdo->prepare($reasonSql);
            $reasonStmt->execute();
            $reasonStats = $reasonStmt->fetchAll(PDO::FETCH_ASSOC);

            // 按 token 類型分組統計
            $typeSql = 'SELECT token_type, COUNT(*) as count FROM token_blacklist GROUP BY token_type ORDER BY count DESC';
            $typeStmt = $this->pdo->prepare($typeSql);
            $typeStmt->execute();
            $typeStats = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_entries' => $totalEntries,
                'active_entries' => $activeEntries,
                'expired_entries' => $expiredEntries,
                'reason_statistics' => $reasonStats,
                'type_statistics' => $typeStats,
            ];
        } catch (PDOException $e) {
            throw new Exception('取得黑名單統計資訊失敗: ' . $e->getMessage(), 0, $e);
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
            $currentTime = new DateTimeImmutable();

            // 總項目數
            $totalSql = 'SELECT COUNT(*) FROM token_blacklist';
            $totalStmt = $this->pdo->prepare($totalSql);
            $totalStmt->execute();
            $totalEntries = (int) $totalStmt->fetchColumn();

            // 有效項目數
            $activeSql = 'SELECT COUNT(*) FROM token_blacklist WHERE expires_at > :current_time';
            $activeStmt = $this->pdo->prepare($activeSql);
            $activeStmt->execute(['current_time' => $currentTime->format('Y-m-d H:i:s')]);
            $activeEntries = (int) $activeStmt->fetchColumn();

            $expiredEntries = $totalEntries - $activeEntries;

            // 估算大小（每個項目約 1KB）
            $estimatedSizeMb = $totalEntries / 1024;

            return [
                'total_entries' => $totalEntries,
                'active_entries' => $activeEntries,
                'expired_entries' => $expiredEntries,
                'cleanable_entries' => $expiredEntries,
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
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception('最佳化黑名單失敗: ' . $e->getMessage(), 0, $e);
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
            $stringMetadata = is_string($row['metadata']) ? $row['metadata'] : (string) $row['metadata'];
            $decoded = json_decode($stringMetadata, true);
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
