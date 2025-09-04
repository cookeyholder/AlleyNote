<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由收集器介面.
 *
 * 管理多個路由的收集和查詢
 */
interface RouteCollectionInterface
{
    /**
     * 新增路由到收集器.
     */
    public function add(RouteInterface $route): void;

    /**
     * 新增多個路由.
     *
     * @param RouteInterface[] $routes
     */
    public function addRoutes(array $routes): void;

    /**
     * 根據名稱取得路由.
     */
    public function getByName(string $name): ?RouteInterface;

    /**
     * 取得所有路由.
     *
     * @return RouteInterface[]
     */
    public function all(): array;

    /**
     * 根據 HTTP 方法取得路由.
     *
     * @return RouteInterface[]
     */
    public function getByMethod(string $method): array;

    /**
     * 查找匹配的路由.
     */
    public function match(ServerRequestInterface $request): ?RouteInterface;

    /**
     * 檢查是否有指定名稱的路由.
     */
    public function has(string $name): bool;

    /**
     * 移除指定名稱的路由.
     */
    public function remove(string $name): bool;

    /**
     * 清空所有路由.
     */
    public function clear(): void;

    /**
     * 取得路由總數.
     */
    public function count(): int;

    /**
     * 轉換為陣列格式 (用於快取).
     */
    public function toArray(): array;

    /**
     * 從陣列格式建立 (用於快取恢復).
     */
    public static function fromArray(array $data): self;
}
