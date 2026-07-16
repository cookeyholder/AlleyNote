<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

interface CacheManagerInterface extends CacheInterface
{
    /**
     * 取得或設定標籤化快取。
     *
     * @param string|array<string> $tags 標籤
     */
    public function tags(string|array $tags): TaggedCacheInterface;

    /**
     * 設定快取前綴。
     */
    public function prefix(string $prefix): CacheManagerInterface;

    /**
     * 取得指定驅動的快取實例。
     */
    public function driver(?string $driver = null): CacheDriverInterface;

    /**
     * 取得所有驅動的健康狀態。
     */
    public function getHealthStatus(): array;

    /**
     * 預熱快取。
     *
     * @param array<string, callable> $warmupCallbacks 預熱回調
     */
    public function warmup(array $warmupCallbacks): array;

    /**
     * 清理所有驅動的過期項目。
     */
    public function cleanup(): array;

    /**
     * 取得指定的驅動程式。
     */
    public function getDriver(string $name): ?CacheDriverInterface;

    /**
     * 取得所有可用的驅動程式。
     */
    public function getDrivers(): array;
}
