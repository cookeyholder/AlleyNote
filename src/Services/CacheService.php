<?php

declare(strict_types=1);

namespace App\Services;

class CacheService
{
    private \Redis $redis;
    private const TTL = 3600; // 預設快取時間 1 小時

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(
            getenv('REDIS_HOST') ?: 'localhost',
            (int) (getenv('REDIS_PORT') ?: 6379)
        );
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value ? json_decode($value, true) : null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->redis->setex(
            $key,
            $ttl ?: self::TTL,
            json_encode($value)
        );
    }

    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }

    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    public function tags(array $tags): self
    {
        // 在未來可以擴充標籤功能
        return $this;
    }
}
