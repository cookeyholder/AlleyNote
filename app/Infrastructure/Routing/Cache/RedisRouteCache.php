<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Cache;

use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;
use Redis;
use RedisException;

/**
 * Redis 快取實作.
 *
 * 使用 Redis 存儲路由快取資料，提供更好的效能和分散式支援
 */
class RedisRouteCache implements RouteCacheInterface
{
    private const CACHE_KEY_PREFIX = 'route_cache:';

    private const STATS_KEY = 'route_cache:stats';

    private int $ttl = 3600; // 預設 1 小時

    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
        'created_at' => 0,
        'last_used' => 0,
    ];

    public function __construct(
        private readonly Redis $redis,
        private readonly string $keyPrefix = 'routes',
    ) {
        $this->loadStats();
    }

    public function isValid(): bool
    {
        try {
            $cacheKey = $this->getCacheKey();
            $exists = $this->redis->exists($cacheKey);

            return is_int($exists) && $exists > 0;
        } catch (RedisException) {
            return false;
        }
    }

    public function load(): ?RouteCollectionInterface
    {
        try {
            $cacheKey = $this->getCacheKey();
            $content = $this->redis->get($cacheKey);

            if ($content === false) {
                $this->stats['misses']++;
                $this->saveStats();

                return null;
            }

            $data = unserialize($content);
            if (!$data instanceof RouteCollectionInterface) {
                $this->stats['misses']++;
                $this->saveStats();

                return null;
            }

            $this->stats['hits']++;
            $this->stats['last_used'] = time();
            $this->saveStats();

            return $data;
        } catch (RedisException) {
            $this->stats['misses']++;
            $this->saveStats();

            return null;
        }
    }

    public function store(RouteCollectionInterface $routes): bool
    {
        try {
            $cacheKey = $this->getCacheKey();
            $content = serialize($routes);

            $result = $this->ttl > 0
                ? $this->redis->setex($cacheKey, $this->ttl, $content)
                : $this->redis->set($cacheKey, $content);

            if ($result) {
                $this->stats['size'] = strlen($content);
                $this->stats['created_at'] = time();
                $this->saveStats();

                return true;
            }

            return false;
        } catch (RedisException) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $cacheKey = $this->getCacheKey();
            $statsKey = self::STATS_KEY;

            // 使用 pipeline 提升效能
            $pipe = $this->redis->multi();
            $pipe->del($cacheKey);
            $pipe->del($statsKey);
            $results = $pipe->exec();

            // 重置統計
            $this->stats = [
                'hits' => 0,
                'misses' => 0,
                'size' => 0,
                'created_at' => 0,
                'last_used' => 0,
            ];

            return is_array($results) && count($results) === 2;
        } catch (RedisException) {
            return false;
        }
    }

    public function getCachePath(): string
    {
        return "redis://{$this->keyPrefix}";
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getStats(): mixed
    {
        return $this->stats;
    }

    /**
     * 取得 Redis 連線物件.
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * 檢查 Redis 連線狀態.
     */
    public function isConnected(): bool
    {
        try {
            return $this->redis->ping() === '+PONG';
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * 取得快取鍵名.
     */
    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . $this->keyPrefix;
    }

    /**
     * 載入統計資料.
     */
    private function loadStats(): void
    {
        try {
            $content = $this->redis->get(self::STATS_KEY);
            if ($content !== false) {
                $stats = json_decode($content, true);
                if (is_array($stats) && !empty($stats)) {
                    $this->stats = array_merge($this->stats, $stats);
                }
            }
        } catch (RedisException) {
            // 忽略載入錯誤，使用預設值
        }
    }

    /**
     * 儲存統計資料.
     */
    private function saveStats(): void
    {
        try {
            $content = (json_encode($this->stats) ?? '') ?: '';
            $this->redis->set(self::STATS_KEY, $content);
        } catch (RedisException) {
            // 忽略儲存錯誤
        }
    }
}
