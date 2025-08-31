<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 快取策略介面。
 *
 * 定義快取策略的行為，如 LRU、FIFO、TTL 等
 */
interface CacheStrategyInterface
{
    /**
     * 判斷是否應該快取該項目。
     */
    public function shouldCache(string $key, mixed $value, int $ttl): bool;

    /**
     * 選擇快取驅動。
     *
     * @param array<string, CacheDriverInterface> $drivers 可用驅動
     */
    public function selectDriver(array $drivers, string $key, mixed $value): ?CacheDriverInterface;

    /**
     * 決定 TTL 值。
     */
    public function decideTtl(string $key, mixed $value, int $requestedTtl): int;

    /**
     * 處理快取未命中情況。
     */
    public function handleMiss(string $key, callable $callback): mixed;

    /**
     * 處理快取驅動故障。
     *
     * @param array<string, CacheDriverInterface> $availableDrivers 可用驅動
     */
    public function handleDriverFailure(
        CacheDriverInterface $failedDriver,
        array $availableDrivers,
        string $operation,
        array $params,
    ): mixed;

    /**
     * 獲取策略統計資訊。
     */
    public function getStats(): array;

    /**
     * 重設策略統計。
     */
    public function resetStats(): void;
}
