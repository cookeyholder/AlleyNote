<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Contracts\CacheServiceInterface;
use DateTimeImmutable;
use Exception;

/**
 * 統計快取服務 - 多層次快取策略實作.
 *
 * 提供兩層快取架構：
 * - L1: 記憶體快取（高速，但不持久）
 * - L2: 持久快取（較慢，但持久化）
 *
 * 功能特性：
 * - 多層次快取策略
 * - 快取標籤管理
 * - 快取預熱機制
 * - 智能失效邏輯
 */
class StatisticsCacheService
{
    /**
     * @var array<string, mixed> L1 記憶體快取
     */
    private array $memoryCache = [];

    /**
     * @var array<string, array<string>> 快取標籤映射 [tag => [key1, key2, ...]]
     */
    private array $cacheTags = [];

    /**
     * 快取層級常數.
     */
    private const CACHE_LEVEL_MEMORY = 'memory';

    private const CACHE_LEVEL_PERSISTENT = 'persistent';

    /**
     * 快取標籤常數.
     */
    private const TAG_STATISTICS = 'statistics';

    private const TAG_DAILY = 'daily';

    private const TAG_WEEKLY = 'weekly';

    private const TAG_MONTHLY = 'monthly';

    private const TAG_YEARLY = 'yearly';

    private const TAG_USER_STATS = 'user_stats';

    private const TAG_POST_STATS = 'post_stats';

    private const TAG_SYSTEM_STATS = 'system_stats';

    public function __construct(
        private readonly CacheServiceInterface $persistentCache,
    ) {}

    /**
     * 取得快取的統計快照 - 多層次查找.
     *
     * 查找順序：L1 記憶體快取 → L2 持久快取
     */
    public function getCachedSnapshot(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        $cacheKey = $this->generateCacheKey($period);

        // L1: 檢查記憶體快取
        $memorySnapshot = $this->getFromMemoryCache($cacheKey);
        if ($memorySnapshot !== null) {
            return $memorySnapshot;
        }

        // L2: 檢查持久快取
        $persistentSnapshot = $this->getFromPersistentCache($cacheKey);
        if ($persistentSnapshot !== null) {
            // 將持久快取的資料回填到記憶體快取
            $this->setToMemoryCache($cacheKey, $persistentSnapshot, $period);

            return $persistentSnapshot;
        }

        return null;
    }

    /**
     * 快取統計快照 - 雙層寫入.
     */
    public function cacheSnapshot(
        StatisticsPeriod $period,
        StatisticsSnapshot $snapshot,
    ): void {
        $cacheKey = $this->generateCacheKey($period);
        $ttl = $period->type->getDefaultCacheTtl();

        // 取得快取標籤
        $tags = $this->generateCacheTags($period, $snapshot);

        // L1: 寫入記憶體快取
        $this->setToMemoryCache($cacheKey, $snapshot, $period);

        // L2: 寫入持久快取
        $this->setToPersistentCache($cacheKey, $snapshot, $ttl);

        // 註冊快取標籤
        $this->registerCacheTags($cacheKey, $tags);
    }

    /**
     * 使指定期間的快取失效 - 雙層清除.
     */
    public function invalidateCache(StatisticsPeriod $period): void
    {
        $cacheKey = $this->generateCacheKey($period);

        // L1: 清除記憶體快取
        unset($this->memoryCache[$cacheKey]);

        // L2: 清除持久快取
        $this->persistentCache->delete($cacheKey);

        // 清除標籤映射
        $this->removeCacheFromAllTags($cacheKey);
    }

    /**
     * 按標籤使快取失效.
     */
    public function invalidateCacheByTag(string $tag): int
    {
        $invalidatedCount = 0;

        if (!isset($this->cacheTags[$tag])) {
            return $invalidatedCount;
        }

        $cacheKeys = $this->cacheTags[$tag];

        foreach ($cacheKeys as $cacheKey) {
            // L1: 清除記憶體快取
            unset($this->memoryCache[$cacheKey]);

            // L2: 清除持久快取
            $this->persistentCache->delete($cacheKey);

            $invalidatedCount++;
        }

        // 清除整個標籤
        unset($this->cacheTags[$tag]);

        return $invalidatedCount;
    }

    /**
     * 使用者相關統計快取失效.
     */
    public function invalidateUserStatistics(): int
    {
        return $this->invalidateCacheByTag(self::TAG_USER_STATS);
    }

    /**
     * 文章相關統計快取失效.
     */
    public function invalidatePostStatistics(): int
    {
        return $this->invalidateCacheByTag(self::TAG_POST_STATS);
    }

    /**
     * 系統相關統計快取失效.
     */
    public function invalidateSystemStatistics(): int
    {
        return $this->invalidateCacheByTag(self::TAG_SYSTEM_STATS);
    }

    /**
     * 清除所有快取 - 雙層清除.
     */
    public function clearAllCache(): void
    {
        // L1: 清除記憶體快取
        $this->memoryCache = [];

        // L2: 清除持久快取中的統計資料
        $this->persistentCache->deletePattern('stats:*');

        // 清除所有標籤映射
        $this->cacheTags = [];
    }

    /**
     * 取得快取統計資訊 - 增強版.
     */
    public function getCacheStats(): array
    {
        $memoryStats = $this->getMemoryCacheStats();
        $persistentStats = $this->persistentCache->getStats();

        return [
            'memory_cache' => $memoryStats,
            'persistent_cache' => $persistentStats,
            'total_tags' => count($this->cacheTags),
            'cache_levels' => [
                self::CACHE_LEVEL_MEMORY,
                self::CACHE_LEVEL_PERSISTENT,
            ],
        ];
    }

    /**
     * 預熱快取 - 策略性預載.
     */
    public function warmupCache(array $periods, ?callable $dataProvider = null): array
    {
        $warmedUpCount = 0;
        $errors = [];

        foreach ($periods as $period) {
            if (!$period instanceof StatisticsPeriod) {
                $errors[] = 'Invalid period type provided';
                continue;
            }

            try {
                // 檢查是否已有快取
                if ($this->hasCachedData($period)) {
                    continue; // 跳過已快取的資料
                }

                // 如果提供了資料供應器，使用它來取得資料
                if ($dataProvider !== null) {
                    $snapshot = $dataProvider($period);
                    if ($snapshot instanceof StatisticsSnapshot) {
                        $this->cacheSnapshot($period, $snapshot);
                        $warmedUpCount++;
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Failed to warm up cache for period {$period->type->value}: " . $e->getMessage();
            }
        }

        return [
            'warmed_up_count' => $warmedUpCount,
            'errors' => $errors,
            'total_requested' => count($periods),
        ];
    }

    /**
     * 智能快取預熱 - 根據使用模式預熱.
     */
    public function intelligentWarmup(?callable $dataProvider = null): array
    {
        $periodsToWarm = [];

        // 預熱常用的時間區間
        $periodsToWarm[] = StatisticsPeriod::today();
        $periodsToWarm[] = StatisticsPeriod::yesterday();
        $periodsToWarm[] = StatisticsPeriod::thisWeek();
        $periodsToWarm[] = StatisticsPeriod::lastWeek();
        $periodsToWarm[] = StatisticsPeriod::thisMonth();
        $periodsToWarm[] = StatisticsPeriod::lastMonth();

        return $this->warmupCache($periodsToWarm, $dataProvider);
    }

    /**
     * 清理過期的快取項目 - 雙層清理.
     */
    public function cleanupExpiredCache(): int
    {
        $removedCount = 0;

        // L1: 清理記憶體快取
        foreach ($this->memoryCache as $key => $cacheData) {
            if (!$this->isCacheValid($cacheData['expires_at'])) {
                unset($this->memoryCache[$key]);
                $this->removeCacheFromAllTags($key);
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * 取得快取使用狀況
     */
    public function getCacheUsage(): array
    {
        $memoryUsage = $this->getMemoryUsage();
        $tagsUsage = $this->getTagsUsage();

        return [
            'memory_cache' => $memoryUsage,
            'tags' => $tagsUsage,
            'layers' => [
                'L1_memory' => [
                    'entries' => count($this->memoryCache),
                    'memory_mb' => $memoryUsage['memory_usage_mb'],
                ],
                'L2_persistent' => [
                    'stats' => $this->persistentCache->getStats(),
                ],
            ],
        ];
    }

    /**
     * 檢查特定期間是否有快取.
     */
    public function hasCachedData(StatisticsPeriod $period): bool
    {
        return $this->getCachedSnapshot($period) !== null;
    }

    /**
     * 批量快取多個快照.
     *
     * @param array<array{period: StatisticsPeriod, snapshot: StatisticsSnapshot}> $items
     */
    public function batchCacheSnapshots(array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['period']) && isset($item['snapshot'])) {
                $this->cacheSnapshot($item['period'], $item['snapshot']);
            }
        }
    }

    /**
     * 取得指定快取鍵的詳細資訊.
     */
    public function getCacheDetails(string $cacheKey): ?array
    {
        // 先檢查記憶體快取
        if (isset($this->memoryCache[$cacheKey])) {
            $cacheData = $this->memoryCache[$cacheKey];

            return [
                'cache_key' => $cacheKey,
                'level' => self::CACHE_LEVEL_MEMORY,
                'created_at' => $cacheData['created_at']->format('Y-m-d H:i:s'),
                'expires_at' => $cacheData['expires_at']->format('Y-m-d H:i:s'),
                'is_valid' => $this->isCacheValid($cacheData['expires_at']),
                'snapshot_id' => $cacheData['snapshot']->getId()->toString(),
                'tags' => $this->getCacheKeyTags($cacheKey),
            ];
        }

        // 檢查持久快取
        if ($this->persistentCache->has($cacheKey)) {
            return [
                'cache_key' => $cacheKey,
                'level' => self::CACHE_LEVEL_PERSISTENT,
                'exists' => true,
                'tags' => $this->getCacheKeyTags($cacheKey),
            ];
        }

        return null;
    }

    // ===== 私有方法：多層快取實作 =====

    /**
     * 從記憶體快取取得資料.
     */
    private function getFromMemoryCache(string $cacheKey): ?StatisticsSnapshot
    {
        if (!isset($this->memoryCache[$cacheKey])) {
            return null;
        }

        $cachedData = $this->memoryCache[$cacheKey];

        // 檢查快取是否過期
        if ($this->isCacheValid($cachedData['expires_at'])) {
            /** @var StatisticsSnapshot $snapshot */
            $snapshot = $cachedData['snapshot'];
            return $snapshot;
        }

        // 移除過期的快取
        unset($this->memoryCache[$cacheKey]);
        $this->removeCacheFromAllTags($cacheKey);

        return null;
    }

    /**
     * 從持久快取取得資料.
     */
    private function getFromPersistentCache(string $cacheKey): ?StatisticsSnapshot
    {
        $cachedData = $this->persistentCache->get($cacheKey);

        if ($cachedData === null) {
            return null;
        }

        // 反序列化統計快照
        if (is_array($cachedData) && isset($cachedData['snapshot_data'])) {
            /** @var array<string, mixed> $snapshotData */
            $snapshotData = $cachedData['snapshot_data'];
            return $this->deserializeSnapshot($snapshotData);
        }

        return null;
    }

    /**
     * 寫入記憶體快取.
     */
    private function setToMemoryCache(
        string $cacheKey,
        StatisticsSnapshot $snapshot,
        StatisticsPeriod $period,
    ): void {
        $ttl = $period->type->getDefaultCacheTtl();
        $expiresAt = new DateTimeImmutable("+{$ttl} seconds");

        $this->memoryCache[$cacheKey] = [
            'snapshot' => $snapshot,
            'expires_at' => $expiresAt,
            'created_at' => new DateTimeImmutable(),
        ];
    }

    /**
     * 寫入持久快取.
     */
    private function setToPersistentCache(
        string $cacheKey,
        StatisticsSnapshot $snapshot,
        int $ttl,
    ): void {
        $cacheData = [
            'snapshot_data' => $this->serializeSnapshot($snapshot),
            'cached_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        $this->persistentCache->set($cacheKey, $cacheData, $ttl);
    }

    // ===== 私有方法：快取標籤管理 =====

    /**
     * 生成快取標籤.
     * 
     * @return array<string>
     */
    private function generateCacheTags(StatisticsPeriod $period, StatisticsSnapshot $snapshot): array
    {
        $tags = [self::TAG_STATISTICS];

        // 按週期類型加標籤
        match ($period->type) {
            PeriodType::DAILY => $tags[] = self::TAG_DAILY,
            PeriodType::WEEKLY => $tags[] = self::TAG_WEEKLY,
            PeriodType::MONTHLY => $tags[] = self::TAG_MONTHLY,
            PeriodType::YEARLY => $tags[] = self::TAG_YEARLY,
        };

        // 根據快照內容加上相關標籤
        $summary = $snapshot->getSummary();
        if (isset($summary['total_posts']) || isset($summary['total_views'])) {
            $tags[] = self::TAG_POST_STATS;
        }

        // 檢查是否有來源統計 (表示有使用者相關資料)
        if (!empty($snapshot->getSourceStats())) {
            $tags[] = self::TAG_USER_STATS;
        }

        // 檢查是否有額外指標 (可能包含系統統計)
        if (!empty($snapshot->getAdditionalMetrics())) {
            $tags[] = self::TAG_SYSTEM_STATS;
        }

        return $tags;
    }

    /**
     * 註冊快取標籤.
     * 
     * @param array<string> $tags
     */
    private function registerCacheTags(string $cacheKey, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->cacheTags[$tag])) {
                $this->cacheTags[$tag] = [];
            }

            if (!in_array($cacheKey, $this->cacheTags[$tag], true)) {
                $this->cacheTags[$tag][] = $cacheKey;
            }
        }
    }

    /**
     * 從所有標籤中移除快取鍵.
     */
    private function removeCacheFromAllTags(string $cacheKey): void
    {
        foreach ($this->cacheTags as $tag => $keys) {
            $index = array_search($cacheKey, $keys, true);
            if ($index !== false) {
                unset($this->cacheTags[$tag][$index]);
                // 重新索引陣列
                $this->cacheTags[$tag] = array_values($this->cacheTags[$tag]);

                // 如果標籤下沒有快取鍵了，刪除標籤
                if (empty($this->cacheTags[$tag])) {
                    unset($this->cacheTags[$tag]);
                }
            }
        }
    }

    /**
     * 取得快取鍵的所有標籤.
     */
    private function getCacheKeyTags(string $cacheKey): array
    {
        $tags = [];
        foreach ($this->cacheTags as $tag => $keys) {
            if (in_array($cacheKey, $keys, true)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    // ===== 私有方法：統計與工具 =====

    /**
     * 取得記憶體快取統計.
     */
    private function getMemoryCacheStats(): array
    {
        $totalEntries = count($this->memoryCache);
        $validEntries = 0;
        $expiredEntries = 0;

        foreach ($this->memoryCache as $cacheData) {
            if ($this->isCacheValid($cacheData['expires_at'])) {
                $validEntries++;
            } else {
                $expiredEntries++;
            }
        }

        return [
            'total_entries' => $totalEntries,
            'valid_entries' => $validEntries,
            'expired_entries' => $expiredEntries,
            'hit_rate' => $totalEntries > 0 ? ($validEntries / $totalEntries) * 100 : 0,
        ];
    }

    /**
     * 取得記憶體使用狀況
     */
    private function getMemoryUsage(): array
    {
        $memoryUsage = 0;
        $oldestEntry = null;
        $newestEntry = null;

        foreach ($this->memoryCache as $cacheData) {
            // 估算記憶體使用量（簡化計算）
            $memoryUsage += strlen(serialize($cacheData));

            $createdAt = $cacheData['created_at'];
            if ($oldestEntry === null || $createdAt < $oldestEntry) {
                $oldestEntry = $createdAt;
            }
            if ($newestEntry === null || $createdAt > $newestEntry) {
                $newestEntry = $createdAt;
            }
        }

        return [
            'memory_usage_bytes' => $memoryUsage,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'oldest_entry' => $oldestEntry?->format('Y-m-d H:i:s'),
            'newest_entry' => $newestEntry?->format('Y-m-d H:i:s'),
            'cache_span_hours' => $oldestEntry && $newestEntry
                ? $newestEntry->diff($oldestEntry)->h
                : 0,
        ];
    }

    /**
     * 取得標籤使用狀況
     */
    private function getTagsUsage(): array
    {
        $tagsStats = [];

        foreach ($this->cacheTags as $tag => $keys) {
            $tagsStats[$tag] = [
                'cache_count' => count($keys),
                'keys' => $keys,
            ];
        }

        return [
            'total_tags' => count($this->cacheTags),
            'tags_details' => $tagsStats,
        ];
    }

    /**
     * 產生快取鍵.
     */
    private function generateCacheKey(StatisticsPeriod $period): string
    {
        return sprintf(
            'stats:%s:%s:%s',
            $period->type->value,
            $period->startDate->format('Y-m-d'),
            $period->endDate->format('Y-m-d'),
        );
    }

    /**
     * 檢查快取是否仍然有效.
     */
    private function isCacheValid(DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt > new DateTimeImmutable();
    }

    /**
     * 序列化統計快照 - 簡化版本.
     */
    private function serializeSnapshot(StatisticsSnapshot $snapshot): array
    {
        return [
            'id' => $snapshot->getId()->toString(),
            'summary' => $snapshot->getSummary(),
            'period_type' => $snapshot->getPeriod()->type->value,
            'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H:i:s'),
            'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
            'created_at' => $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
            'serialized_object' => serialize($snapshot), // 完整物件序列化作為備用
        ];
    }

    /**
     * 反序列化統計快照 - 簡化版本.
     */
    private function deserializeSnapshot(array $data): ?StatisticsSnapshot
    {
        try {
            // 首先嘗試反序列化完整物件
            if (isset($data['serialized_object']) && is_string($data['serialized_object'])) {
                $snapshot = unserialize($data['serialized_object']);
                if ($snapshot instanceof StatisticsSnapshot) {
                    return $snapshot;
                }
            }

            // 如果反序列化失敗，返回 null
            return null;
        } catch (Exception) {
            return null; // 反序列化失敗
        }
    }
}
