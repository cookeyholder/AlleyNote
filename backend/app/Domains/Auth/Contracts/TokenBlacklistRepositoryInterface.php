<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;

/**
 * Token 黑名單 Repository 介面.
 *
 * 定義token黑名單的資料存取操作，包含新增、查詢、刪除等功能。
 * 負責管理被撤銷或無效token的黑名單記錄，確保token安全性。
 */
interface TokenBlacklistRepositoryInterface
{
    /**
     * 將token加入黑名單.
     *
     * @param TokenBlacklistEntry $entry 黑名單項目
     * @return bool 加入成功時回傳true
     */
    public function addToBlacklist(TokenBlacklistEntry $entry): bool;

    /**
     * 檢查token是否在黑名單中.
     *
     * @param string $jti JWT ID
     * @return bool 在黑名單中時回傳true
     */
    public function isBlacklisted(string $jti): bool;

    /**
     * 檢查token是否在黑名單中（根據token hash）.
     *
     * @param string $tokenHash token的雜湊值
     * @return bool 在黑名單中時回傳true
     */
    public function isTokenHashBlacklisted(string $tokenHash): bool;

    /**
     * 從黑名單中移除token.
     *
     * @param string $jti JWT ID
     * @return bool 移除成功時回傳true
     */
    public function removeFromBlacklist(string $jti): bool;

    /**
     * 根據JTI查找黑名單項目.
     *
     * @param string $jti JWT ID
     * @return TokenBlacklistEntry|null 黑名單項目，找不到時回傳null
     */
    public function findByJti(string $jti): ?TokenBlacklistEntry;

    /**
     * 取得使用者的所有黑名單項目.
     *
     * @param int $userId 使用者ID
     * @param int|null $limit 限制數量，null時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByUserId(int $userId, ?int $limit = null): array;

    /**
     * 取得特定裝置的黑名單項目.
     *
     * @param string $deviceId 裝置ID
     * @param int|null $limit 限制數量，null時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByDeviceId(string $deviceId, ?int $limit = null): array;

    /**
     * 根據黑名單原因查找項目.
     *
     * @param string $reason 黑名單原因
     * @param int|null $limit 限制數量，null時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByReason(string $reason, ?int $limit = null): array;

    /**
     * 批次將token加入黑名單.
     *
     * @param array<int, TokenBlacklistEntry> $entries 黑名單項目陣列
     * @return int 成功加入的數量
     */
    public function batchAddToBlacklist(array $entries): int;

    /**
     * 批次檢查token是否在黑名單中.
     *
     * @param array<int, string> $jtis JTI陣列
     * @return array<string, bool> JTI為key，是否在黑名單為值的陣列
     */
    public function batchIsBlacklisted(array $jtis): array;

    /**
     * 批次從黑名單移除token.
     *
     * @param array<int, string> $jtis JTI陣列
     * @return int 成功移除的數量
     */
    public function batchRemoveFromBlacklist(array $jtis): int;

    /**
     * 將使用者的所有token加入黑名單.
     *
     * @param int $userId 使用者ID
     * @param string $reason 黑名單原因
     * @param string|null $excludeJti 排除的JTI
     * @return int 加入黑名單的token數量
     */
    public function blacklistAllUserTokens(int $userId, string $reason, ?string $excludeJti = null): int;

    /**
     * 將特定裝置的所有token加入黑名單.
     *
     * @param string $deviceId 裝置ID
     * @param string $reason 黑名單原因
     * @return int 加入黑名單的token數量
     */
    public function blacklistAllDeviceTokens(string $deviceId, string $reason): int;

    /**
     * 清理過期的黑名單項目.
     *
     * @param DateTime|null $beforeDate 清理此日期之前的記錄，null時清理所有過期項目
     * @return int 清理的記錄數量
     */
    public function cleanup(?DateTime $beforeDate = null): int;

    /**
     * 清理可清理的黑名單項目（根據TokenBlacklistEntry的canBeCleanedUp方法）.
     *
     * @return int 清理的記錄數量
     */
    public function cleanupExpiredEntries(): int;

    /**
     * 清理舊的黑名單項目（超過指定天數）.
     *
     * @param int $days 保留天數，預設90天
     * @return int 清理的記錄數量
     */
    public function cleanupOldEntries(int $days = 90): int;

    /**
     * 取得黑名單統計資訊.
     *
     * @return array<string, mixed> 統計資訊
     *                              - total: 總項目數
     *                              - by_token_type: 按token類型分組的統計
     *                              - by_reason: 按原因分組的統計
     *                              - security_related: 與安全相關的項目數
     *                              - user_initiated: 使用者主動的項目數
     *                              - system_initiated: 系統主動的項目數
     */
    public function getBlacklistStats(): array;

    /**
     * 取得特定使用者的黑名單統計.
     *
     * @param int $userId 使用者ID
     * @return array<string, mixed> 使用者統計資訊
     */
    public function getUserBlacklistStats(int $userId): array;

    /**
     * 取得最近的黑名單項目.
     *
     * @param int $limit 限制數量，預設100
     * @param DateTime|null $since 起始時間，null時不限制
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getRecentBlacklistEntries(int $limit = 100, ?DateTime $since = null): array;

    /**
     * 取得高優先級的黑名單項目.
     *
     * @param int $limit 限制數量，預設50
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getHighPriorityEntries(int $limit = 50): array;

    /**
     * 搜尋黑名單項目.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     *                                       - user_id: 使用者ID
     *                                       - device_id: 裝置ID
     *                                       - token_type: token類型
     *                                       - reason: 黑名單原因
     *                                       - date_from: 起始日期
     *                                       - date_to: 結束日期
     * @param int|null $limit 限制數量，null時不限制
     * @param int $offset 偏移量，預設0
     * @return array<int, TokenBlacklistEntry> 搜尋結果
     */
    public function search(array $criteria, ?int $limit = null, int $offset = 0): array;

    /**
     * 計算搜尋結果總數.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     * @return int 總數
     */
    public function countSearch(array $criteria): int;

    /**
     * 檢查黑名單大小是否超過限制.
     *
     * @param int $maxSize 最大大小，預設100000
     * @return bool 超過限制時回傳true
     */
    public function isSizeExceeded(int $maxSize = 100000): bool;

    /**
     * 取得黑名單大小資訊.
     *
     * @return array<string, mixed> 大小資訊
     *                              - total_entries: 總項目數
     *                              - active_entries: 活動項目數
     *                              - expired_entries: 過期項目數
     *                              - cleanable_entries: 可清理項目數
     *                              - estimated_size_mb: 預估大小（MB）
     */
    public function getSizeInfo(): array;

    /**
     * 最佳化黑名單儲存.
     *
     * @return array<string, mixed> 最佳化結果
     *                              - cleaned_entries: 清理的項目數
     *                              - compacted_size: 壓縮後大小
     *                              - execution_time: 執行時間（秒）
     */
    public function optimize(): array;
}
