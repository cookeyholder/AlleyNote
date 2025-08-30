<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;

/**
 * 記憶體快取驅動。
 *
 * 使用 PHP 陣列作為快取存儲，提供最快的訪問速度但僅限於請求週期內
 */
class MemoryCacheDriver implements CacheDriverInterface
{
    /** @var array<string, array{value: mixed, expires_at: int}> 快取資料 */
    private array $cache = [];

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
            $this->stats['deletes']++;
            return true;
        }

        return false;
    }

    public function flush(): bool
    {
        $this->cache = [];
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
        $newValue = (int) $current + $value;
        $this->put($key, $newValue);
        return $newValue;
    }

    public function decrement(string $key, int $value = 1): int
    {
        $current = $this->get($key, 0);
        $newValue = (int) $current - $value;
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
}
