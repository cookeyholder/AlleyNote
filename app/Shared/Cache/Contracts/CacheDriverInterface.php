<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 快取驅動介面。
 * 
 * 定義所有快取驅動必須實作的基本操作
 */
interface CacheDriverInterface
{
    /**
     * 從快取中取得資料。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 將資料存入快取。
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取是否存在。
     */
    public function has(string $key): bool;

    /**
     * 從快取中刪除資料。
     */
    public function forget(string $key): bool;

    /**
     * 清空所有快取。
     */
    public function flush(): bool;

    /**
     * 批次取得多個快取。
     * 
     * @param array<string> $keys 快取鍵陣列
     * @return array<string, mixed> 快取資料
     */
    public function many(array $keys): array;

    /**
     * 批次設定多個快取。
     * 
     * @param array<string, mixed> $values 快取資料
     */
    public function putMany(array $values, int $ttl = 3600): bool;

    /**
     * 批次刪除多個快取。
     * 
     * @param array<string> $keys 快取鍵陣列
     */
    public function forgetMany(array $keys): bool;

    /**
     * 依照模式刪除快取。
     */
    public function forgetPattern(string $pattern): int;

    /**
     * 增加數值快取。
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * 減少數值快取。
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * 記憶化取得 - 若快取不存在則執行回調。
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * 永久記憶化取得。
     */
    public function rememberForever(string $key, callable $callback): mixed;

    /**
     * 取得快取統計資訊。
     */
    public function getStats(): array;

    /**
     * 取得快取連線資訊。
     */
    public function getConnection(): mixed;

    /**
     * 檢查驅動是否可用。
     */
    public function isAvailable(): bool;

    /**
     * 清理過期的快取項目。
     */
    public function cleanup(): int;
}