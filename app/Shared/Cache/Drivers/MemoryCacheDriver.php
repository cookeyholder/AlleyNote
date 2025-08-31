<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;

/**
 * 記憶體快取驅動。
 *
 * 使用 PHP 陣列作為快取存儲，提供最快的訪問速度但僅限於請求週期內
 * 提供標籤支援功能
 */
class MemoryCacheDriver implements CacheDriverInterface, TaggedCacheInterface
{
    /** @var array<string, array{value: mixed, expires_at: int}> 快取資料 */
    private array $cache = [];

    /** @var array<string, array<string>> 標籤索引 */
    private array $tagIndex = [];

    /** @var array<string> 當前標籤 */
    private array $tags = [];

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'clears' => 0,
    ];

    /** @var int 最大快取項目數量 */
    private int $maxItems;

    public function __construct(int $maxItems = 1000)
    {
        $this->maxItems = $maxItems;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->cache[$key])) {
            $this->stats['misses']++;
            return $default;
        }

        $item = $this->cache[$key];

        // 檢查過期
        if ($item['expires_at'] !== 0 && time() > $item['expires_at']) {
            unset($this->cache[$key]);
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $item['value'];
    }

    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        // 檢查是否達到最大項目數
        if (count($this->cache) >= $this->maxItems && !isset($this->cache[$key])) {
            $this->evictOldest();
        }

        $expiresAt = $ttl > 0 ? time() + $ttl : 0;

        $this->cache[$key] = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];

        // 如果有標籤，添加到標籤索引
        if (!empty($this->tags)) {
            $this->addKeyToTags($key, $this->tags);
        }

        $this->stats['sets']++;
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $item = $this->cache[$key];

        // 檢查過期
        if ($item['expires_at'] !== 0 && time() > $item['expires_at']) {
            unset($this->cache[$key]);
            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->removeKeyFromAllTags($key);
            $this->stats['deletes']++;
            return true;
        }

        return false;
    }

    public function flush(): bool
    {
        $this->cache = [];
        $this->tagIndex = [];
        $this->stats['clears']++;
        return true;
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function putMany(array $values, int $ttl = 3600): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function forgetMany(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->forget($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function forgetPattern(string $pattern): int
    {
        $deleted = 0;
        $keys = array_keys($this->cache);

        foreach ($keys as $key) {
            if ($this->matchesPattern($key, $pattern)) {
                if ($this->forget($key)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $newValue = (is_int($current) || is_numeric($current)) ? (int) $current + $value : $value;
        $this->put($key, $newValue);
        return $newValue;
    }

    public function decrement(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $newValue = (is_int($current) || is_numeric($current)) ? (int) $current - $value : -$value;
        $this->put($key, $newValue);
        return $newValue;
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        if ($value !== null) {
            $this->put($key, $value, $ttl);
        }

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, 0);
    }

    public function getStats(): array
    {
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $totalRequests > 0 ? ($this->stats['hits'] / $totalRequests) * 100 : 0;

        return array_merge($this->stats, [
            'total_items' => count($this->cache),
            'max_items' => $this->maxItems,
            'hit_rate' => round($hitRate, 2),
            'memory_usage' => $this->getMemoryUsage(),
            'expired_items' => $this->getExpiredItemsCount(),
        ]);
    }

    public function getConnection(): mixed
    {
        return $this->cache;
    }

    public function isAvailable(): bool
    {
        return true; // 記憶體快取總是可用的
    }

    public function cleanup(): int
    {
        $cleaned = 0;
        $currentTime = time();

        foreach ($this->cache as $key => $item) {
            if ($item['expires_at'] !== 0 && $currentTime > $item['expires_at']) {
                unset($this->cache[$key]);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * 計算記憶體使用量。
     */
    private function getMemoryUsage(): int
    {
        return strlen(serialize($this->cache));
    }

    /**
     * 取得過期項目數量。
     */
    private function getExpiredItemsCount(): int
    {
        $expired = 0;
        $currentTime = time();

        foreach ($this->cache as $item) {
            if ($item['expires_at'] !== 0 && $currentTime > $item['expires_at']) {
                $expired++;
            }
        }

        return $expired;
    }

    /**
     * 淘汰最舊的項目。
     */
    private function evictOldest(): void
    {
        if (empty($this->cache)) {
            return;
        }

        $oldestKey = array_key_first($this->cache);
        unset($this->cache[$oldestKey]);
        $this->stats['deletes']++;
    }

    /**
     * 檢查鍵是否符合模式。
     */
    private function matchesPattern(string $key, string $pattern): bool
    {
        // 簡單的萬用字元匹配
        $pattern = str_replace(['*', '?'], ['.*', '.'], $pattern);
        return preg_match('/^' . $pattern . '$/', $key) === 1;
    }

    /**
     * 設定最大項目數量。
     */
    public function setMaxItems(int $maxItems): void
    {
        $this->maxItems = $maxItems;

        // 如果當前項目數超過新限制，淘汰多餘項目
        while (count($this->cache) > $maxItems) {
            $this->evictOldest();
        }
    }

    /**
     * 取得最大項目數量。
     */
    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    /**
     * 重設統計資料。
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'clears' => 0,
        ];
    }

    /**
     * 增加新標籤到快取管理器
     *
     * @param string|array<string> $tags 標籤
     */
    public function addTags(string|array $tags): TaggedCacheInterface
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $this->tags = array_unique(array_merge($this->tags, $tagsArray));
        return $this;
    }

    /**
     * 取得當前標籤化快取的所有鍵
     *
     * @return array<string> 快取鍵陣列
     */
    public function getTaggedKeys(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return $this->getKeysByTags($this->tags);
    }

    /**
     * 使用指定標籤存放快取項目
     *
     * @param string $key 快取鍵
     * @param mixed $value 快取值
     * @param array<string> $tags 標籤陣列
     * @param int $ttl 存活時間（秒）
     * @return bool 是否成功
     */
    public function putWithTags(string $key, mixed $value, array $tags, int $ttl = 3600): bool
    {
        $oldTags = $this->tags;
        $this->tags = $tags;
        $result = $this->put($key, $value, $ttl);
        $this->tags = $oldTags;
        return $result;
    }

    /**
     * 取得快取項目的所有標籤
     *
     * @param string $key 快取鍵
     * @return array<string> 標籤陣列
     */
    public function getTagsByKey(string $key): array
    {
        $tags = [];
        foreach ($this->tagIndex as $tag => $keys) {
            if (in_array($key, $keys, true)) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }

    /**
     * 為現有快取項目添加標籤
     *
     * @param string $key 快取鍵
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return bool 是否成功
     */
    public function addTagsToKey(string $key, string|array $tags): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $tagsArray = is_array($tags) ? $tags : [$tags];
        $this->addKeyToTags($key, $tagsArray);
        return true;
    }

    /**
     * 從快取項目移除標籤
     *
     * @param string $key 快取鍵
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return bool 是否成功
     */
    public function removeTagsFromKey(string $key, string|array $tags): bool
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $removed = false;

        foreach ($tagsArray as $tag) {
            if (!isset($this->tagIndex[$tag])) {
                continue;
            }

            $index = array_search($key, $this->tagIndex[$tag], true);
            if ($index !== false) {
                unset($this->tagIndex[$tag][$index]);
                $this->tagIndex[$tag] = array_values($this->tagIndex[$tag]);
                $removed = true;

                // 如果標籤下沒有鍵了，移除標籤
                if (empty($this->tagIndex[$tag])) {
                    unset($this->tagIndex[$tag]);
                }
            }
        }

        return $removed;
    }

    /**
     * 檢查快取項目是否包含指定標籤
     *
     * @param string $key 快取鍵
     * @param string $tag 標籤
     * @return bool 是否包含
     */
    public function hasTag(string $key, string $tag): bool
    {
        return isset($this->tagIndex[$tag]) && in_array($key, $this->tagIndex[$tag], true);
    }

    /**
     * 清除未使用的標籤
     *
     * @return int 清除的標籤數量
     */
    public function cleanupUnusedTags(): int
    {
        $cleaned = 0;
        foreach ($this->tagIndex as $tag => $keys) {
            $validKeys = [];
            foreach ($keys as $key) {
                if ($this->has($key)) {
                    $validKeys[] = $key;
                }
            }

            if (empty($validKeys)) {
                unset($this->tagIndex[$tag]);
                $cleaned++;
            } else {
                $this->tagIndex[$tag] = $validKeys;
            }
        }

        return $cleaned;
    }

    /**
     * 取得當前標籤
     *
     * @return array<string> 標籤陣列
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 根據標籤清空快取
     *
     * @param array<string>|string $tags 要清空的標籤
     * @return int 清空的項目數量
     */
    public function flushByTags(array|string $tags): int
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $deletedCount = 0;

        foreach ($tagsArray as $tag) {
            if (!isset($this->tagIndex[$tag])) {
                continue;
            }

            $keys = $this->tagIndex[$tag];
            foreach ($keys as $key) {
                if (isset($this->cache[$key])) {
                    unset($this->cache[$key]);
                    $deletedCount++;
                }
            }

            // 清空標籤索引
            unset($this->tagIndex[$tag]);
        }

        $this->stats['deletes'] += $deletedCount;
        return $deletedCount;
    }

    /**
     * 根據標籤取得快取鍵
     *
     * @param string $tag 標籤名稱
     * @return array<string> 快取鍵陣列
     */
    public function getKeysByTag(string $tag): array
    {
        if (!isset($this->tagIndex[$tag])) {
            return [];
        }

        // 過濾出存在且未過期的鍵
        $validKeys = [];
        foreach ($this->tagIndex[$tag] as $key) {
            if ($this->has($key)) {
                $validKeys[] = $key;
            }
        }

        return $validKeys;
    }

    /**
     * 根據多個標籤取得共同的快取鍵
     *
     * @param array<string> $tags 標籤陣列
     * @return array<string> 共同的快取鍵陣列
     */
    public function getKeysByTags(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $commonKeys = null;

        foreach ($tags as $tag) {
            $tagKeys = $this->getKeysByTag($tag);

            if ($commonKeys === null) {
                $commonKeys = $tagKeys;
            } else {
                $commonKeys = array_intersect($commonKeys, $tagKeys);
            }

            // 如果已經沒有共同鍵，提早結束
            if (empty($commonKeys)) {
                break;
            }
        }

        return $commonKeys;
    }

    /**
     * 檢查標籤是否存在
     *
     * @param string $tag 標籤名稱
     * @return bool 是否存在
     */
    public function tagExists(string $tag): bool
    {
        return isset($this->tagIndex[$tag]) && !empty($this->tagIndex[$tag]);
    }

    /**
     * 取得所有標籤
     *
     * @return array<string> 所有標籤陣列
     */
    public function getAllTags(): array
    {
        return array_keys($this->tagIndex);
    }

    /**
     * 取得標籤統計資訊
     *
     * @return array<string, mixed> 標籤統計資訊
     */
    public function getTagStatistics(): array
    {
        $statistics = [
            'total_tags' => count($this->tagIndex),
            'tags' => [],
        ];

        foreach ($this->tagIndex as $tag => $keys) {
            $validKeys = $this->getKeysByTag($tag);
            $statistics['tags'][$tag] = [
                'key_count' => count($validKeys),
                'sample_keys' => array_slice($validKeys, 0, 5), // 顯示前 5 個鍵作為範例
            ];
        }

        return $statistics;
    }

    /**
     * 將快取鍵添加到標籤索引
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 標籤陣列
     */
    private function addKeyToTags(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tagIndex[$tag])) {
                $this->tagIndex[$tag] = [];
            }

            if (!in_array($key, $this->tagIndex[$tag], true)) {
                $this->tagIndex[$tag][] = $key;
            }
        }
    }

    /**
     * 從所有標籤索引中移除快取鍵
     *
     * @param string $key 快取鍵
     */
    private function removeKeyFromAllTags(string $key): void
    {
        foreach ($this->tagIndex as $tag => &$keys) {
            $index = array_search($key, $keys, true);
            if ($index !== false) {
                unset($keys[$index]);
                $keys = array_values($keys); // 重新索引陣列
            }

            // 如果標籤下沒有鍵了，移除標籤
            if (empty($keys)) {
                unset($this->tagIndex[$tag]);
            }
        }
        unset($keys);
    }

    // ===== 標籤化快取介面實作 =====

    /**
     * 設定快取標籤
     *
     * @param array<string>|string $tags 標籤陣列或單一標籤
     * @return TaggedCacheInterface 標籤化快取實例
     */
    public function tags(array|string $tags): TaggedCacheInterface
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];

        // 建立新的驅動實例以避免標籤污染
        $driver = clone $this;
        $driver->tags = $tagsArray;

        return $driver;
    }
}
