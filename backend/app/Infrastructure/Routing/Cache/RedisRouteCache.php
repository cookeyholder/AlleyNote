<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Cache;

use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;
use Exception;
use Redis;

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

    /**
     * @var array{hits: int, misses: int, size: int, created_at: int, last_used: int}
     */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
        'created_at' => 0,
        'last_used' => 0,
    ];

    /**
     * @param Redis $redis Redis 連線實例
     * @param string $keyPrefix 快取鍵前綴
     */
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
        } catch (Exception $e) {
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

            if (!is_string($content)) {
                $this->stats['misses']++;
                $this->saveStats();

                return null;
            }

            $data = unserialize($content);
            if (!($data instanceof RouteCollectionInterface)) {
                $this->stats['misses']++;
                $this->saveStats();

                return null;
            }

            $this->stats['hits']++;
            $this->stats['last_used'] = time();
            $this->saveStats();

            return $data;
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return false;
        }
    }

    public function getCachePath(): string
    {
        return "redis://{$this->keyPrefix}";
    }

    /**
     * 設定快取存活時間.
     *
     * @param int $ttl 快取存活時間（秒）
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * 取得快取存活時間.
     *
     * @return int 快取存活時間（秒）
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * 取得快取統計資料.
     *
     * @return array{hits: int, misses: int, size: int, created_at: int, last_used: int}
     */
    public function getStats(): array
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
        } catch (Exception $e) {
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
            if ($content !== false && is_string($content)) {
                $stats = json_decode($content, true);
                if (is_array($stats)) {
                    /** @var array{hits?: int, misses?: int, size?: int, created_at?: int, last_used?: int} $stats */
                    $filteredStats = array_intersect_key($stats, $this->stats);
                    $this->stats = array_merge($this->stats, $filteredStats);
                }
            }
        } catch (Exception $e) {
            // 忽略錯誤，使用預設值
        }
    }

    /**
     * 儲存統計資料.
     */
    private function saveStats(): void
    {
        try {
            $content = json_encode($this->stats, JSON_THROW_ON_ERROR);
            $this->redis->set(self::STATS_KEY, $content);
        } catch (Exception $e) {
            // 忽略錯誤
        }
    }
}
