<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Cache;

use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;

/**
 * 記憶體快取實作.
 *
 * 使用 PHP 記憶體存儲路由快取資料，適用於開發和測試環境
 */
class MemoryRouteCache implements RouteCacheInterface
{
    private int $ttl = 3600; // 預設 1 小時

    private array $cache = [];

    private array $timestamps = [];

    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'size' => 0,
        'created_at' => 0,
        'last_used' => 0,
    ];

    public function __construct()
    {
        $this->stats['created_at'] = time();
    }

    public function isValid(): bool
    {
        if (!isset($this->cache['routes'])) {
            return false;
        }

        // 檢查是否過期
        if ($this->ttl > 0 && isset($this->timestamps['routes'])) {
            $elapsed = time() - $this->timestamps['routes'];
            if ($elapsed > $this->ttl) {
                unset($this->cache['routes']);
                unset($this->timestamps['routes']);

                return false;
            }
        }

        return true;
    }

    public function load(): ?RouteCollectionInterface
    {
        if (!$this->isValid()) {
            $this->stats['misses']++;

            return null;
        }

        $data = $this->cache['routes'];
        if (!$data instanceof RouteCollectionInterface) {
            $this->stats['misses']++;
            unset($this->cache['routes']);
            unset($this->timestamps['routes']);

            return null;
        }

        $this->stats['hits']++;
        $this->stats['last_used'] = time();

        return $data;
    }

    public function store(RouteCollectionInterface $routes): bool
    {
        $this->cache['routes'] = $routes;
        $this->timestamps['routes'] = time();

        // 計算大小（序列化後的大小）
        $serialized = serialize($routes);
        $this->stats['size'] = strlen($serialized);
        $this->stats['created_at'] = time();

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        $this->timestamps = [];

        // 重置統計
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'size' => 0,
            'created_at' => time(),
            'last_used' => 0,
        ];

        return true;
    }

    public function getCachePath(): string
    {
        return 'memory://routes';
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * 取得所有快取項目.
     */
    public function getCache(): array
    {
        return $this->cache;
    }

    /**
     * 取得所有時間戳.
     */
    public function getTimestamps(): array
    {
        return $this->timestamps;
    }

    /**
     * 檢查指定項目是否已過期
     */
    public function isItemExpired(string $key): bool
    {
        if (!isset($this->timestamps[$key])) {
            return true;
        }

        if ($this->ttl <= 0) {
            return false;
        }

        $elapsed = time() - $this->timestamps[$key];

        return $elapsed > $this->ttl;
    }

    /**
     * 清理已過期的項目.
     */
    public function cleanupExpired(): int
    {
        $cleaned = 0;

        foreach ($this->timestamps as $key => $timestamp) {
            if ($this->isItemExpired($key)) {
                unset($this->cache[$key]);
                unset($this->timestamps[$key]);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
