<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

/**
 * 路由快取介面.
 *
 * 用於快取路由定義和匹配結果，提升效能
 */
interface RouteCacheInterface
{
    /**
     * 檢查快取是否存在且有效.
     */
    public function isValid(): bool;

    /**
     * 從快取中載入路由收集器.
     */
    public function load(): ?RouteCollectionInterface;

    /**
     * 將路由收集器儲存到快取.
     */
    public function store(RouteCollectionInterface $routes): bool;

    /**
     * 清除路由快取.
     */
    public function clear(): bool;

    /**
     * 取得快取路徑.
     */
    public function getCachePath(): string;

    /**
     * 設定快取過期時間 (秒).
     */
    public function setTtl(int $ttl): void;

    /**
     * 取得快取過期時間.
     */
    public function getTtl(): int;

    /**
     * 取得快取統計資訊.
     *
     * @return array<mixed>{hits: int, misses: int, size: int, created_at: int, last_used: int}
     */
    public function getStats(): array;
}
