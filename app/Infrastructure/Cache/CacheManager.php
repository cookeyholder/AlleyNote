<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

class CacheManager
{
    private array $cache = [];

    private array $expiry = [];

    private int $defaultTtl = 3600; // 1 hour

    public function __construct(int $defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * 取得快取值
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->cache[$key];
    }

    /**
     * 設定快取值
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl ??= $this->defaultTtl;

        $this->cache[$key] = $value;
        $this->expiry[$key] = time() + $ttl;

        return true;
    }

    /**
     * 檢查快取是否存在且未過期
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        if (isset($this->expiry[$key]) && time() > $this->expiry[$key]) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    /**
     * 刪除快取.
     */
    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expiry[$key]);

        return true;
    }

    /**
     * 清空所有快取.
     */
    public function clear(): bool
    {
        $this->cache = [];
        $this->expiry = [];

        return true;
    }

    /**
     * 記憶化取得（如果不存在則執行回調並快取結果）.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * 永久記憶化取得（直到手動刪除）.
     */
    public function rememberForever(string $key, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->cache[$key] = $value;
        unset($this->expiry[$key]); // 永不過期

        return $value;
    }

    /**
     * 取得或設定多個快取值
     */
    public function many(array $keys): mixed
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * 設定多個快取值
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * 根據模式刪除快取.
     */
    public function deletePattern(string $pattern): int
    {
        // 先 quote 特殊字符，但保留 * 不被 quote
        $escapedPattern = str_replace('\\*', '*', preg_quote($pattern, '/'));
        // 然後將 * 替換為正則表達式的 .*
        $regexPattern = str_replace('*', '.*', $escapedPattern);
        $deleted = 0;

        foreach (array_keys($this->cache) as $key) {
            if (preg_match("/^{$regexPattern}$/", $key)) {
                $this->delete($key);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * 增加數值快取.
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);

        return $new;
    }

    /**
     * 減少數值快取.
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    /**
     * 取得快取統計資訊.
     */
    public function getStats(): mixed
    {
        $now = time();
        $expired = 0;
        $active = 0;

        foreach ($this->expiry as $key => $expireTime) {
            if ($now > $expireTime) {
                $expired++;
            } else {
                $active++;
            }
        }

        return [
            'total_keys' => count($this->cache),
            'active_keys' => $active,
            'expired_keys' => $expired,
            'memory_usage' => $this->getMemoryUsage(),
        ];
    }

    /**
     * 估算記憶體使用量.
     */
    private function getMemoryUsage(): string
    {
        $size = strlen(serialize($this->cache)) + strlen(serialize($this->expiry));

        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }

    /**
     * 清理過期的快取.
     */
    public function cleanup(): int
    {
        $now = time();
        $cleaned = 0;

        foreach ($this->expiry as $key => $expireTime) {
            if ($now > $expireTime) {
                $this->delete($key);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * 檢查快取鍵是否有效.
     */
    public function isValidKey(string $key): bool
    {
        return CacheKeys::isValidKey($key);
    }
}
