<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 快取管理器介面。
 *
 * 提供統一的快取管理功能，支援多層快取和策略選擇
 */
interface CacheManagerInterface
{
    /**
     * 取得快取資料。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 設定快取資料。
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取是否存在。
     */
    public function has(string $key): bool;

    /**
     * 刪除快取。
     */
    public function delete(string $key): bool;

    /**
     * 清空所有快取。
     */
    public function clear(): bool;

    /**
     * 記憶化取得。
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

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
     * 取得快取統計資訊。
     */
    public function getStats(): array;

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
