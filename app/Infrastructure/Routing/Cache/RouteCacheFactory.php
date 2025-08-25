<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Cache;

use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use InvalidArgumentException;

/**
 * 路由快取工廠.
 *
 * 根據配置建立適當的快取實作
 */
class RouteCacheFactory
{
    /**
     * 支援的快取驅動程式.
     */
    private const SUPPORTED_DRIVERS = [
        'file' => FileRouteCache::class,
        'memory' => MemoryRouteCache::class,
        // 'redis' => RedisRouteCache::class, // 如果 Redis 可用時啟用
    ];

    /**
     * 建立路由快取實例.
     *
     * @param array{driver: string, path?: string, ttl?: int, redis?: array} $config 快取配置
     */
    public function create(array $config): RouteCacheInterface
    {
        $driver = $config['driver'];

        if (!isset(self::SUPPORTED_DRIVERS[$driver])) {
            throw new InvalidArgumentException(
                "Unsupported cache driver: {$driver}. Supported drivers: "
                    . implode(', ', array_keys(self::SUPPORTED_DRIVERS)),
            );
        }

        return match ($driver) {
            'file' => $this->createFileCache($config),
            'memory' => $this->createMemoryCache($config),
            // 'redis' => $this->createRedisCache($config),
            default => throw new InvalidArgumentException("Unknown cache driver: {$driver}")
        };
    }

    /**
     * 建立檔案快取實例.
     *
     * @param array{path?: string, ttl?: int} $config
     */
    private function createFileCache(array $config): FileRouteCache
    {
        $cachePath = $config['path'] ?? sys_get_temp_dir() . '/route_cache';
        $cache = new FileRouteCache($cachePath);

        if (isset($config['ttl'])) {
            $cache->setTtl((int) $config['ttl']);
        }

        return $cache;
    }

    /**
     * 建立記憶體快取實例.
     *
     * @param array{ttl?: int} $config
     */
    private function createMemoryCache(array $config): MemoryRouteCache
    {
        $cache = new MemoryRouteCache();

        if (isset($config['ttl'])) {
            $cache->setTtl((int) $config['ttl']);
        }

        return $cache;
    }

    /**
     * 取得支援的快取驅動程式列表.
     *
     * @return string[]
     */
    public static function getSupportedDrivers(): array
    {
        return array_keys(self::SUPPORTED_DRIVERS);
    }

    /**
     * 檢查指定驅動程式是否支援.
     */
    public static function isDriverSupported(string $driver): bool
    {
        return isset(self::SUPPORTED_DRIVERS[$driver]);
    }

    /**
     * 取得快取驅動程式的類別名稱.
     */
    public static function getDriverClass(string $driver): ?string
    {
        return self::SUPPORTED_DRIVERS[$driver] ?? null;
    }

    /**
     * 建立預設快取實例（記憶體快取）.
     */
    public function createDefault(): RouteCacheInterface
    {
        return $this->create(['driver' => 'memory']);
    }

    /**
     * 驗證快取配置.
     *
     * @param array{driver: string, path?: string, ttl?: int} $config
     * @return string[] 驗證錯誤訊息
     */
    public function validateConfig(array $config): array
    {
        $errors = [];

        if (!array_key_exists('driver', $config)) {
            $errors[] = 'Cache driver is required';
        } elseif (!self::isDriverSupported($config['driver'])) {
            $errors[] = sprintf(
                'Unsupported cache driver: %s. Supported drivers: %s',
                $config['driver'],
                implode(', ', self::getSupportedDrivers()),
            );
        }

        if (array_key_exists('ttl', $config) && (!is_int($config['ttl']) || $config['ttl'] < 0)) {
            $errors[] = 'Cache TTL must be a non-negative integer';
        }

        if ($config['driver'] === 'file') {
            if (array_key_exists('path', $config)) {
                $path = $config['path'];
                if (!is_string($path) || empty($path)) {
                    $errors[] = 'Cache path must be a non-empty string';
                } elseif (!is_writable(dirname($path))) {
                    $errors[] = sprintf('Cache directory is not writable: %s', dirname($path));
                }
            }
        }

        return $errors;
    }
}
