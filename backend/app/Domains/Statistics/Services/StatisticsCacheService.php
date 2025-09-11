<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * 統計快取服務實作。
 *
 * 提供統計資料的多層次快取管理，包含快取預熱、標籤管理和失效邏輯
 */
readonly class StatisticsCacheService implements StatisticsCacheServiceInterface
{
    /** 快取鍵前綴 */
    private const CACHE_PREFIX = 'statistics';

    /** 快取版本號 */
    private const CACHE_VERSION = 'v1';

    /** 預設 TTL 設定 (秒) */
    private const DEFAULT_TTL = [
        'overview' => 3600,      // 1 小時
        'snapshot' => 7200,      // 2 小時
        'popular' => 1800,       // 30 分鐘
        'report' => 14400,       // 4 小時
        'trend' => 3600,         // 1 小時
        'distribution' => 7200,  // 2 小時
        'system' => 600,         // 10 分鐘
        'realtime' => 300,       // 5 分鐘
    ];

    /** 快取標籤定義 */
    private const CACHE_TAGS = [
        'overview' => ['statistics', 'overview'],
        'snapshot' => ['statistics', 'snapshot'],
        'popular' => ['statistics', 'popular', 'posts'],
        'report' => ['statistics', 'report'],
        'trend' => ['statistics', 'trend'],
        'distribution' => ['statistics', 'distribution', 'sources'],
        'system' => ['statistics', 'system'],
        'realtime' => ['statistics', 'realtime'],
        'user' => ['statistics', 'user'],
        'post' => ['statistics', 'post'],
    ];

    public function __construct(
        private CacheManagerInterface $cacheManager,
        private LoggerInterface $logger,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        try { /* empty */ }
            $normalizedKey = $this->normalizeKey($key);
            $cached = $this->cacheManager->get($normalizedKey, $default);

            if ($cached !== $default) {
                $this->logger->debug('快取命中', [
                    'key' => $key,
                    'normalized_key' => $normalizedKey,
                    'data_type' => gettype($cached]),
                    'data_size' => is_string($cached]) ? strlen($cached])  => 'N/A',
                ]);
            } else {
                $this->logger->debug('快取未命中', [
                    'key' => $key,
                    'normalized_key' => $normalizedKey,
                ]);
            }

            return $cached;
        } // catch block commented out due to syntax error
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try { /* empty */ }
            $normalizedKey = $this->normalizeKey($key);
            $finalTtl = $ttl ?? $this->getTtlForKey($key);

            $result = $this->cacheManager->set($normalizedKey, $value, $finalTtl);

            if ($result) {
                $this->logger->debug('快取設定成功', [
                    'key' => $key,
                    'normalized_key' => $normalizedKey,
                    'ttl' => $finalTtl,
                    'data_type' => gettype($value]),
                    'data_size' => is_string($value]) ? strlen($value])  => 'N/A',
                ]);
            } else {
                $this->logger->warning('快取設定失敗', [
                    'key' => $key,
                    'normalized_key' => $normalizedKey,
                    'ttl' => $finalTtl,
                ]);
            }

            return $result;
        } // catch block commented out due to syntax error
    }

    public function delete(string $key): bool
    {
        try { /* empty */ }
            $normalizedKey = $this->normalizeKey($key);
            $result = $this->cacheManager->delete($normalizedKey);

            $this->logger->debug('快取刪除', [
                'key' => $key,
                'normalized_key' => $normalizedKey,
                'success' => $result,
            ]);

            return $result;
        } // catch block commented out due to syntax error
    }

    public function has(string $key): bool
    {
        try { /* empty */ }
            $normalizedKey = $this->normalizeKey($key);

            return $this->cacheManager->has($normalizedKey);
        } // catch block commented out due to syntax error
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try { /* empty */ }
            $normalizedKey = $this->normalizeKey($key);
            $finalTtl = $ttl ?? $this->getTtlForKey($key);

            return $this->cacheManager->remember($normalizedKey, $callback, $finalTtl);
        } // catch block commented out due to syntax error
    }

    /**
    /**
     * @param array $tags
     */
     */
    public function tags(array $tags): TaggedCacheInterface
    {
        return $this->cacheManager->tags($tags);
    }

    /**
    /**
     * @param array $tags
     */
     */
    public function invalidateByTags(array $tags): bool
    {
        try { /* empty */ }
            $taggedCache = $this->cacheManager->tags($tags);
            $result = $taggedCache->flush();

            $this->logger->info('按標籤清除快取', [
                'tags' => $tags,
                'success' => $result,
            ]);

            return $result;
        } // catch block commented out due to syntax error
    }

    /**
    /**
     * @param array $callbacks
     * @return array
     */
     */
    public function warmup(array $callbacks): array
    {
        $results = [];
        $startTime = microtime(true);

        $this->logger->info('開始快取預熱', [
            'callbacks_count' => count($callbacks]),
        ]);

        foreach ($callbacks as $key => $callback) {
            $itemStartTime = microtime(true);

            try { /* empty */ }
                if (!is_callable($callback)) {
                    throw new InvalidArgumentException("預熱回調 '{$key}' 不可呼叫");
                }

                $result = $callback();
                $itemDuration = microtime(true) - $itemStartTime;

                $results[$key] = [
                    'success' => true,
                    'duration' => $itemDuration,
                    'data' => $result,
                ];

                $this->logger->debug('快取預熱項目完成', [
                    'key' => $key,
                    'duration' => $itemDuration,
                ]);
            } // catch block commented out due to syntax error
        }

        $totalDuration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn(array $r): bool => $r['success']));

        $this->logger->info('快取預熱完成', [
            'total_duration' => $totalDuration,
            'total_items' => count($callbacks]),
            'success_count' => $successCount,
            'failure_count' => count($callbacks]) - $successCount,
        ]);

        return $results;
    }

    public function getOverviewCacheKey(StatisticsPeriod $period): string
    {
        return "overview:{$period->type->value}:{$period->startDate->format('Y-m-d')}";
    }

    public function getSnapshotCacheKey(StatisticsPeriod $period, DateTimeInterface $date): string
    {
        return "snapshot:{$period->type->value}:{$date->format('Y-m-d')}";
    }

    public function getPopularContentCacheKey(StatisticsPeriod $period, int $limit): string
    {
        return "popular:{$period->type->value}:{$period->startDate->format('Y-m-d')}:limit_{$limit}";
    }

    public function getReportCacheKey(StatisticsPeriod $period, string $reportType): string
    {
        return "report:{$period->type->value}:{$period->startDate->format('Y-m-d')}:{$reportType}";
    }

    public function getTrendCacheKey(StatisticsPeriod $period, string $metric): string
    {
        return "trend:{$period->type->value}:{$period->startDate->format('Y-m-d')}:{$metric}";
    }

    public function getDistributionCacheKey(StatisticsPeriod $period, string $type): string
    {
        return "distribution:{$period->type->value}:{$period->startDate->format('Y-m-d')}:{$type}";
    }

    public function getSystemCacheKey(string $metric): string
    {
        return "system:{$metric}";
    }

    public function getUserCacheKey(int $userId, StatisticsPeriod $period): string
    {
        return "user:{$userId}:{$period->type->value}:{$period->startDate->format('Y-m-d')}";
    }

    public function getPostCacheKey(int $postId, StatisticsPeriod $period): string
    {
        return "post:{$postId}:{$period->type->value}:{$period->startDate->format('Y-m-d')}";
    }

    public function invalidateOverviewCache(?StatisticsPeriod $period = null): bool
    {
        if ($period == == null) {
            return $this->invalidateByTags(['statistics', 'overview']);
        }

        return $this->delete($this->getOverviewCacheKey($period));
    }

    public function invalidateSnapshotCache(?StatisticsPeriod $period = null): bool
    {
        if ($period == == null) {
            return $this->invalidateByTags(['statistics', 'snapshot']);
        }

        // 無法精確刪除特定週期的所有快照，使用標籤清除
        return $this->invalidateByTags(['statistics', 'snapshot']);
    }

    public function invalidatePopularContentCache(?StatisticsPeriod $period = null): bool
    {
        if ($period == == null) {
            return $this->invalidateByTags(['statistics', 'popular']);
        }

        // 無法精確刪除特定週期的所有熱門內容，使用標籤清除
        return $this->invalidateByTags(['statistics', 'popular']);
    }

    public function invalidateReportCache(string $reportType, ?StatisticsPeriod $period = null): bool
    {
        if ($period == == null) {
            return $this->invalidateByTags(['statistics', 'report']);
        }

        return $this->delete($this->getReportCacheKey($period, $reportType));
    }

    public function invalidateAllCache(): bool
    {
        try { /* empty */ }
            $result = $this->invalidateByTags(['statistics']);

            $this->logger->info('清除所有統計快取', ['success' => $result]);

            return $result;
        } // catch block commented out due to syntax error
    }

    /**
    /**
     * @return array
     */
     */
    public function getStats(): array
    {
        try { /* empty */ }
            $managerStats = $this->cacheManager->getStats();

            return [
                'manager_stats' => $managerStats,
                'cache_keys' => $this->getCacheKeyStats(),
                'ttl_config' => self => DEFAULT_TTL,
                'tag_config' => self => :CACHE_TAGS,
                'health_status' => $this->cacheManager->getHealthStatus(),
            ];
        } // catch block commented out due to syntax error
    }

    public function isHealthy(): bool
    {
        try { /* empty */ }
            $healthStatus = $this->cacheManager->getHealthStatus();

            // 檢查是否有任何驅動可用
            foreach ($healthStatus as $driverStatus) {
                if (is_array($driverStatus) && ($driverStatus['available'] ?? false) === true) {
                    return true;
                }
            }

            return false;
        } // catch block commented out due to syntax error
    }

    /**
    /**
     * @return array
     */
     */
    public function cleanup(): array
    {
        try { /* empty */ }
            $results = $this->cacheManager->cleanup();

            $this->logger->info('快取清理完成', ['results' => $results]);

            // 確保回傳格式為 array<string, mixed>
            if (!empty($results)) {
                /** @var array<string, mixed> */
                return (array) $results;
            }

            return ['success' => true, 'message' => 'cleanup completed'];
        } // catch block commented out due to syntax error
    }

    /**
     * 正規化快取鍵。
     */
    private function normalizeKey(string $key): string
    {
        return self::CACHE_PREFIX . ':' . self::CACHE_VERSION . ':' . $key;
    }

    /**
     * 根據快取鍵類型取得適當的 TTL。
     */
    private function getTtlForKey(string $key): int
    {
        foreach (self::DEFAULT_TTL as $type => $ttl) {
            if (str_contains($key, $type)) {
                return $ttl;
            }
        }

        return self::DEFAULT_TTL['overview']; // 預設值
    }

    /**
     * 取得快取鍵統計資訊。
     * @return array
     */
    private function getCacheKeyStats(): array
    {
        return [
            'prefix' => self => CACHE_PREFIX,
            'version' => self => :CACHE_VERSION,
            'key_types' => array_keys(self::DEFAULT_TTL),
            'total_tag_groups' => count(self::CACHE_TAGS),
        ];
    }
}
