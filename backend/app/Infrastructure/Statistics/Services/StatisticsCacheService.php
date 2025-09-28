<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 統計快取服務實作.
 *
 * 為統計功能提供專業化的快取操作，支援標籤管理、批量失效、
 * 快取預熱等高級功能。使用檔案系統模擬標籤功能。
 */
final class StatisticsCacheService implements StatisticsCacheServiceInterface
{
    /** 快取鍵前綴 */
    private const CACHE_PREFIX = 'statistics';

    /** 標籤索引前綴 */
    private const TAG_PREFIX = 'tags';

    /** 預設 TTL */
    private const DEFAULT_TTL = 3600;

    /** 支援的統計類型標籤 */
    private const SUPPORTED_TAGS = [
        'statistics',
        'overview',
        'posts',
        'users',
        'popular',
        'trends',
        'sources',
        'prewarmed',
    ];

    /** @var array<string, int> 快取統計資訊 */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'puts' => 0,
        'deletes' => 0,
        'flushes' => 0,
        'tag_operations' => 0,
    ];

    public function __construct(
        private readonly CacheServiceInterface $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $cacheKey = $this->buildCacheKey($key);

        // 嘗試從快取取得
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->stats['hits']++;
            $this->logger->debug('統計快取命中', ['key' => $key]);

            return $cached;
        }

        $this->stats['misses']++;

        try {
            // 執行回調函式
            $value = $callback();

            // 快取結果
            $this->cache->set($cacheKey, $value, $ttl);
            $this->stats['puts']++;

            $this->logger->debug('統計快取記憶化', [
                'key' => $key,
                'ttl' => $ttl,
                'has_value' => $value !== null,
            ]);

            return $value;
        } catch (Throwable $e) {
            $this->logger->error('統計快取記憶化失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function flushByTags(array $tags): void
    {
        $validTags = array_intersect($tags, self::SUPPORTED_TAGS);
        if (empty($validTags)) {
            return;
        }

        try {
            foreach ($validTags as $tag) {
                $this->flushTag($tag);
            }

            $this->stats['tag_operations'] += count($validTags);
            $this->logger->info('按標籤清除統計快取', [
                'tags' => $validTags,
                'count' => count($validTags),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('按標籤清除快取失敗', [
                'tags' => $validTags,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function forget(array|string $keys): void
    {
        $keyArray = is_string($keys) ? [$keys] : $keys;

        try {
            foreach ($keyArray as $key) {
                $cacheKey = $this->buildCacheKey($key);
                $this->cache->delete($cacheKey);

                // 同時清理標籤索引
                $this->removeFromAllTagIndexes($key);
            }

            $this->stats['deletes'] += count($keyArray);
            $this->logger->debug('刪除統計快取', [
                'keys' => $keyArray,
                'count' => count($keyArray),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('刪除快取失敗', [
                'keys' => $keyArray,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function get(string $key): mixed
    {
        $cacheKey = $this->buildCacheKey($key);
        $value = $this->cache->get($cacheKey);

        if ($value !== null) {
            $this->stats['hits']++;
            $this->logger->debug('統計快取讀取命中', ['key' => $key]);
        } else {
            $this->stats['misses']++;
            $this->logger->debug('統計快取讀取未命中', ['key' => $key]);
        }

        return $value;
    }

    public function put(string $key, mixed $value, int $ttl = self::DEFAULT_TTL, array $tags = []): bool
    {
        try {
            $cacheKey = $this->buildCacheKey($key);

            // 存入主快取
            $result = $this->cache->set($cacheKey, $value, $ttl);

            if ($result) {
                // 更新標籤索引
                $this->updateTagIndexes($key, $tags);

                $this->stats['puts']++;
                $this->logger->debug('統計快取寫入', [
                    'key' => $key,
                    'ttl' => $ttl,
                    'tags' => $tags,
                    'has_value' => $value !== null,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('統計快取寫入失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function has(string $key): bool
    {
        $cacheKey = $this->buildCacheKey($key);

        return $this->cache->has($cacheKey);
    }

    public function flush(): bool
    {
        try {
            // 使用模式匹配刪除所有統計相關快取
            $deletedCount = $this->cache->deletePattern(self::CACHE_PREFIX . ':*');

            // 清理標籤索引
            $this->cache->deletePattern(self::TAG_PREFIX . ':*');

            $this->stats['flushes']++;
            $this->logger->info('清空所有統計快取', [
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount >= 0;
        } catch (Throwable $e) {
            $this->logger->error('清空統計快取失敗', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getStats(): array
    {
        $cacheStats = $this->cache->getStats();
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $totalRequests > 0 ? ($this->stats['hits'] / $totalRequests) * 100 : 0;

        return [
            'statistics_cache' => [
                'hits' => $this->stats['hits'],
                'misses' => $this->stats['misses'],
                'puts' => $this->stats['puts'],
                'deletes' => $this->stats['deletes'],
                'flushes' => $this->stats['flushes'],
                'tag_operations' => $this->stats['tag_operations'],
                'hit_rate' => round($hitRate, 2),
                'total_requests' => $totalRequests,
            ],
            'underlying_cache' => $cacheStats,
            'supported_tags' => self::SUPPORTED_TAGS,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 預熱統計快取.
     *
     * 批次預載入常用的統計資料到快取中。
     *
     * @param array<string, callable> $warmupCallbacks 預熱回調陣列
     * @param int $warmupTtl 預熱資料的 TTL
     * @return array<string, array{success: bool, duration?: float, error?: string}> 預熱結果
     */
    public function warmup(array $warmupCallbacks, int $warmupTtl = 7200): array
    {
        $results = [];

        foreach ($warmupCallbacks as $key => $callback) {
            $startTime = microtime(true);

            try {
                $value = $callback();
                $this->put($key, $value, $warmupTtl, ['statistics', 'prewarmed']);

                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $results[$key] = [
                    'success' => true,
                    'duration' => $duration,
                ];

                $this->logger->debug('統計快取預熱成功', [
                    'key' => $key,
                    'duration' => $duration,
                ]);
            } catch (Throwable $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('統計快取預熱失敗', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('統計快取預熱完成', [
            'total' => count($warmupCallbacks),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
        ]);

        return $results;
    }

    /**
     * 清理過期的統計快取.
     *
     * @return int 清理的快取項目數量
     */
    public function cleanup(): int
    {
        try {
            // 這裡可以實作更複雜的清理邏輯
            // 目前使用簡單的模式匹配清理
            $deletedCount = 0;

            // 清理過期的標籤索引
            $tagIndexPattern = self::TAG_PREFIX . ':*';
            $deletedCount += $this->cache->deletePattern($tagIndexPattern);

            $this->logger->info('統計快取清理完成', [
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount;
        } catch (Throwable $e) {
            $this->logger->error('統計快取清理失敗', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 建立快取鍵.
     */
    private function buildCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . ':' . $key;
    }

    /**
     * 建立標籤索引鍵.
     */
    private function buildTagIndexKey(string $tag): string
    {
        return self::TAG_PREFIX . ':' . $tag;
    }

    /**
     * 更新標籤索引.
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 標籤陣列
     */
    private function updateTagIndexes(string $key, array $tags): void
    {
        $validTags = array_intersect($tags, self::SUPPORTED_TAGS);

        foreach ($validTags as $tag) {
            $indexKey = $this->buildTagIndexKey($tag);
            $currentIndex = $this->cache->get($indexKey) ?? [];

            if (!is_array($currentIndex)) {
                $currentIndex = [];
            }

            // 加入新的快取鍵到標籤索引中
            if (!in_array($key, $currentIndex, true)) {
                $currentIndex[] = $key;
                $this->cache->set($indexKey, $currentIndex, self::DEFAULT_TTL * 2);
            }
        }
    }

    /**
     * 根據標籤清除快取.
     */
    private function flushTag(string $tag): void
    {
        $indexKey = $this->buildTagIndexKey($tag);
        $taggedKeys = $this->cache->get($indexKey);

        if (!is_array($taggedKeys)) {
            $this->logger->debug('清除標籤快取', [
                'tag' => $tag,
                'deleted_keys' => 0,
            ]);

            return;
        }

        // 刪除所有標籤下的快取項目
        /** @var array<string> $taggedKeys */
        foreach ($taggedKeys as $taggedKey) {
            $cacheKey = $this->buildCacheKey($taggedKey);
            $this->cache->delete($cacheKey);
        }

        // 清除標籤索引
        $this->cache->delete($indexKey);

        $this->logger->debug('清除標籤快取', [
            'tag' => $tag,
            'deleted_keys' => count($taggedKeys),
        ]);
    }

    /**
     * 從所有標籤索引中移除指定鍵.
     */
    private function removeFromAllTagIndexes(string $key): void
    {
        foreach (self::SUPPORTED_TAGS as $tag) {
            $indexKey = $this->buildTagIndexKey($tag);
            $currentIndex = $this->cache->get($indexKey);

            if (!is_array($currentIndex)) {
                continue;
            }

            /** @var array<string> $currentIndex */
            $updatedIndex = array_filter($currentIndex, fn(string $k) => $k !== $key);

            if (count($updatedIndex) !== count($currentIndex)) {
                $this->cache->set($indexKey, array_values($updatedIndex), self::DEFAULT_TTL * 2);
            }
        }
    }
}
