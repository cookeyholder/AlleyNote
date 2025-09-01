<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;

/**
 * 有前綴的快取管理器。
 *
 * 為所有操作自動新增前綴
 */
class PrefixedCacheManager implements CacheManagerInterface
{
    public function __construct(
        private CacheManagerInterface $manager,
        private string $prefix,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->manager->get($this->prefixKey($key), $default);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->manager->set($this->prefixKey($key), $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->manager->has($this->prefixKey($key));
    }

    public function delete(string $key): bool
    {
        return $this->manager->delete($this->prefixKey($key));
    }

    public function clear(): bool
    {
        return $this->manager->clear();
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->manager->remember($this->prefixKey($key), $callback, $ttl);
    }

    public function tags(string|array $tags): TaggedCacheInterface
    {
        return $this->manager->tags($tags);
    }

    public function prefix(string $prefix): CacheManagerInterface
    {
        return new self($this->manager, $this->prefix . $prefix);
    }

    public function driver(?string $driver = null): CacheDriverInterface
    {
        return $this->manager->driver($driver);
    }

    public function getDriver(string $name): ?CacheDriverInterface
    {
        return $this->manager->getDriver($name);
    }

    public function getDrivers(): array
    {
        return $this->manager->getDrivers();
    }

    public function getStats(): array
    {
        return $this->manager->getStats();
    }

    public function getHealthStatus(): array
    {
        return $this->manager->getHealthStatus();
    }

    public function warmup(array $warmupCallbacks): array
    {
        $prefixedCallbacks = [];

        foreach ($warmupCallbacks as $key => $callback) {
            $prefixedCallbacks[$this->prefixKey($key)] = $callback;
        }

        return $this->manager->warmup($prefixedCallbacks);
    }

    public function cleanup(): array
    {
        return $this->manager->cleanup();
    }

    /**
     * 為鍵新增前綴。
     */
    private function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 取得前綴。
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
