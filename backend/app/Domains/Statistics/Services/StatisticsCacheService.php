<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;

/**
 * 統計快取服務
 * 負責統計資料的快取管理.
 */
class StatisticsCacheService
{
    /**
     * @var array<string, mixed> 記憶體快取
     */
    private array $memoryCache = [];

    /**
     * 取得快取的統計快照.
     */
    public function getCachedSnapshot(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        $cacheKey = $this->generateCacheKey($period);

        if (isset($this->memoryCache[$cacheKey])) {
            $cachedData = $this->memoryCache[$cacheKey];

            // 檢查快取是否過期
            if ($this->isCacheValid($cachedData['expires_at'])) {
                return $cachedData['snapshot'];
            }

            // 移除過期的快取
            unset($this->memoryCache[$cacheKey]);
        }

        return null;
    }

    /**
     * 快取統計快照.
     */
    public function cacheSnapshot(
        StatisticsPeriod $period,
        StatisticsSnapshot $snapshot,
    ): void {
        $cacheKey = $this->generateCacheKey($period);
        $ttl = $period->type->getDefaultCacheTtl();
        $expiresAt = new DateTimeImmutable("+{$ttl} seconds");

        $this->memoryCache[$cacheKey] = [
            'snapshot' => $snapshot,
            'expires_at' => $expiresAt,
            'created_at' => new DateTimeImmutable(),
        ];
    }

    /**
     * 使指定期間的快取失效.
     */
    public function invalidateCache(StatisticsPeriod $period): void
    {
        $cacheKey = $this->generateCacheKey($period);
        unset($this->memoryCache[$cacheKey]);
    }

    /**
     * 清除所有快取.
     */
    public function clearAllCache(): void
    {
        $this->memoryCache = [];
    }

    /**
     * 取得快取統計資訊.
     */
    public function getCacheStats(): array
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
     * 清理過期的快取項目.
     */
    public function cleanupExpiredCache(): int
    {
        $removedCount = 0;

        foreach ($this->memoryCache as $key => $cacheData) {
            if (!$this->isCacheValid($cacheData['expires_at'])) {
                unset($this->memoryCache[$key]);
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * 預熱快取.
     */
    public function warmupCache(array $periods): void
    {
        // 此方法會預先載入常用期間的統計資料
        // 實際實作時需要搭配 Repository 來載入資料
        foreach ($periods as $period) {
            if (!$period instanceof StatisticsPeriod) {
                continue;
            }

            // 檢查是否已有快取
            if ($this->getCachedSnapshot($period) === null) {
                // 在實際實作中，這裡會呼叫 Repository 來載入資料
                // 目前先跳過，避免循環依賴
            }
        }
    }

    /**
     * 取得快取使用狀況.
     */
    public function getCacheUsage(): array
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
     * 檢查特定期間是否有快取.
     */
    public function hasCachedData(StatisticsPeriod $period): bool
    {
        return $this->getCachedSnapshot($period) !== null;
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
        if (!isset($this->memoryCache[$cacheKey])) {
            return null;
        }

        $cacheData = $this->memoryCache[$cacheKey];

        return [
            'cache_key' => $cacheKey,
            'created_at' => $cacheData['created_at']->format('Y-m-d H:i:s'),
            'expires_at' => $cacheData['expires_at']->format('Y-m-d H:i:s'),
            'is_valid' => $this->isCacheValid($cacheData['expires_at']),
            'snapshot_id' => $cacheData['snapshot']->getId()->toString(),
        ];
    }
}
