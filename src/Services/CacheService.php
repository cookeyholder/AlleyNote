<?php

declare(strict_types=1);

namespace App\Services;

class CacheService
{
    private ?\Redis $redis = null;
    private const TTL = 3600; // 預設快取時間 1 小時
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 100000; // 100ms

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        if ($this->redis !== null) {
            return;
        }

        $retries = 0;
        while ($retries < self::MAX_RETRIES) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect(
                    getenv('REDIS_HOST') ?: 'redis',
                    (int) (getenv('REDIS_PORT') ?: 6379),
                    2.0 // 連線逾時時間
                );
                return;
            } catch (\RedisException $e) {
                $retries++;
                if ($retries === self::MAX_RETRIES) {
                    throw new \RuntimeException('無法連線至 Redis 伺服器: ' . $e->getMessage());
                }
                usleep(self::RETRY_DELAY * $retries);
            }
        }
    }

    public function get(string $key): mixed
    {
        try {
            $value = $this->redis?->get($key);
            if (!$value) {
                return null;
            }

            $data = json_decode($value, true);
            if (!is_array($data)) {
                return $data;
            }

            if (isset($data['__type']) && isset($data['__data'])) {
                $className = $data['__type'];
                if (class_exists($className) && method_exists($className, 'fromArray')) {
                    return $className::fromArray($data['__data']);
                }
            }

            return $data;
        } catch (\RedisException $e) {
            error_log('Redis 讀取錯誤: ' . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $data = $value;
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data = [
                    '__type' => get_class($value),
                    '__data' => $value->toArray()
                ];
            }

            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                error_log('JSON 編碼錯誤: ' . json_last_error_msg());
                return false;
            }

            return $this->redis?->setex(
                $key,
                $ttl ?: self::TTL,
                $encoded
            ) ?? false;
        } catch (\RedisException $e) {
            error_log('Redis 寫入錯誤: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return (bool) ($this->redis?->del($key) ?? 0);
        } catch (\RedisException $e) {
            error_log('Redis 刪除錯誤: ' . $e->getMessage());
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            return $this->redis?->flushDB() ?? false;
        } catch (\RedisException $e) {
            error_log('Redis 清除錯誤: ' . $e->getMessage());
            return false;
        }
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            $value = $this->get($key);

            if ($value === null) {
                $value = $callback();
                if ($value !== null) {
                    $this->set($key, $value, $ttl);
                }
            }

            return $value;
        } catch (\Throwable $e) {
            error_log('Redis 快取操作錯誤: ' . $e->getMessage());
            return $callback();
        }
    }

    public function tags(array $tags): self
    {
        return $this;
    }
}
