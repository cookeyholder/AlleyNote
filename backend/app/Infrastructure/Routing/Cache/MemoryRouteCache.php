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
    /**
     * 快取內容，鍵為名稱（如 'routes'），值為對應的 RouteCollectionInterface
     *
    * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * 每個快取項目的時間戳（UNIX 時間）
     *
     * @var array<string,int>
     */
    private array $timestamps = [];

    /**
     * 快取統計資訊
     *
     * @var array{hits:int, misses:int, size:int, created_at:int, last_used:int}
     */
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
            $elapsed = time() - (int) $this->timestamps['routes'];
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
            $this->stats['misses'] = (int) $this->stats['misses'] + 1;

            return null;
        }

        $data = $this->cache['routes'];
        if (!$data instanceof RouteCollectionInterface) {
            $this->stats['misses'] = (int) $this->stats['misses'] + 1;
            unset($this->cache['routes']);
            unset($this->timestamps['routes']);

            return null;
        }

        $this->stats['hits'] = (int) $this->stats['hits'] + 1;
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

    /**
     * 取得統計資訊
     *
     * @return array{hits:int, misses:int, size:int, created_at:int, last_used:int}
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * 取得所有快取項目.
     */
    /**
     * 取得所有快取項目.
     *
     * @return array<string,mixed>
     */
    public function getCache(): array
    {
        return $this->cache;
    }

    /**
     * 取得所有時間戳.
     */
    /**
     * 取得所有時間戳.
     *
     * @return array<string,int>
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

    $elapsed = time() - (int) $this->timestamps[$key];

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
                $cleaned = (int) $cleaned + 1;
            }
        }

        return $cleaned;
    }
}
