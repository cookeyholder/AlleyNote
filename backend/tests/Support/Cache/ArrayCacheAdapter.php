<?php

declare(strict_types=1);

namespace Tests\Support\Cache;

/**
 * 陣列快取適配器.
 *
 * 用於測試環境的簡單快取實作，使用 PHP 陣列儲存資料。
 * 支援 TTL、標籤管理等基本快取功能。
 */
class ArrayCacheAdapter
{
    private array $cache = [];

    private array $tags = [];

    private array $expiry = [];

    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    /**
     * 獲取快取值.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->cleanExpired();

        if ($this->has($key)) {
            $currentHits = $this->stats['hits'] ?? 0;
            $hits = is_numeric($currentHits) ? (int) $currentHits : 0;
            $this->stats['hits'] = $hits + 1;

            return $this->cache[$key];
        }

        $currentMisses = $this->stats['misses'] ?? 0;
        $misses = is_numeric($currentMisses) ? (int) $currentMisses : 0;
        $this->stats['misses'] = $misses + 1;

        return $default;
    }

    /**
     * 設置快取值.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->cache[$key] = $value;
        $currentSets = $this->stats['sets'] ?? 0;
        $sets = is_numeric($currentSets) ? (int) $currentSets : 0;
        $this->stats['sets'] = $sets + 1;

        if ($ttl !== null) {
            $this->expiry[$key] = time() + $ttl;
        }

        return true;
    }

    /**
     * 檢查快取鍵是否存在.
     */
    public function has(string $key): bool
    {
        $this->cleanExpired();

        return array_key_exists($key, $this->cache);
    }

    /**
     * 刪除快取.
     */
    public function forget(string $key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            unset($this->cache[$key]);
            unset($this->expiry[$key]);
            $this->removeKeyFromAllTags($key);
            $currentDeletes = $this->stats['deletes'] ?? 0;
            $deletes = is_numeric($currentDeletes) ? (int) $currentDeletes : 0;
            $this->stats['deletes'] = $deletes + 1;

            return true;
        }

        return false;
    }

    /**
     * 清空所有快取.
     */
    public function flush(): bool
    {
        $this->cache = [];
        $this->tags = [];
        $this->expiry = [];

        return true;
    }

    /**
     * 根據標籤清空快取.
     */
    public function flushByTags(array $tags): bool
    {
        foreach ($tags as $tag) {
            if (isset($this->tags[$tag]) && is_array($this->tags[$tag])) {
                foreach ($this->tags[$tag] as $key) {
                    unset($this->cache[$key]);
                    unset($this->expiry[$key]);
                }
                unset($this->tags[$tag]);
            }
        }

        return true;
    }

    /**
     * 為快取鍵添加標籤.
     */
    public function tagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            if (is_array($this->tags[$tag])) {
                $this->tags[$tag][] = $key;
            }
        }
    }

    /**
     * 獲取快取統計資訊.
     */
    public function getStats(): array
    {
        $currentHits = $this->stats['hits'] ?? 0;
        $currentMisses = $this->stats['misses'] ?? 0;
        $hits = is_numeric($currentHits) ? (int) $currentHits : 0;
        $misses = is_numeric($currentMisses) ? (int) $currentMisses : 0;
        $total = $hits + $misses;
        $hitRate = $total > 0 ? $hits / $total : 0.0;

        return array_merge($this->stats, [
            'total_requests' => $total,
            'hit_rate' => $hitRate,
            'cache_size' => count($this->cache),
        ]);
    }

    /**
     * 清空統計資訊.
     */
    public function clearStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * 獲取或設置快取值（回調模式）.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null, array $tags = []): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        if (!empty($tags)) {
            $this->tagKey($key, $tags);
        }

        return $value;
    }

    /**
     * 清理過期的快取項目.
     */
    private function cleanExpired(): void
    {
        $now = time();
        foreach ($this->expiry as $key => $expiryTime) {
            if ($now > $expiryTime) {
                $this->forget($key);
            }
        }
    }

    /**
     * 從所有標籤中移除指定的鍵.
     */
    private function removeKeyFromAllTags(string $key): void
    {
        foreach ($this->tags as $tag => $keys) {
            if (is_array($keys)) {
                $index = array_search($key, $keys, true);
                if ($index !== false) {
                    // 從陣列中移除元素
                    $updatedKeys = $keys;
                    unset($updatedKeys[$index]);
                    $updatedKeys = array_values($updatedKeys); // 重新索引

                    if (empty($updatedKeys)) {
                        // 如果標籤下沒有鍵了，刪除該標籤
                        unset($this->tags[$tag]);
                    } else {
                        // 更新標籤的鍵清單
                        $this->tags[$tag] = $updatedKeys;
                    }
                }
            }
        }
    }
}
