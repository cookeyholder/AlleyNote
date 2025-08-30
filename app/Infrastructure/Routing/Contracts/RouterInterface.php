<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由器介面.
 *
 * 核心路由器功能，負責路由註冊、解析和執行
 */
interface RouterInterface
{
    /**
     * 註冊 GET 路由.
     *
     * @param callable|string|array $handler
     */
    public function get(string $pattern, $handler): RouteInterface;

    /**
     * 註冊 POST 路由.
     *
     * @param callable|string|array $handler
     */
    public function post(string $pattern, $handler): RouteInterface;

    /**
     * 註冊 PUT 路由.
     *
     * @param callable|string|array $handler
     */
    public function put(string $pattern, $handler): RouteInterface;

    /**
     * 註冊 PATCH 路由.
     *
     * @param callable|string|array $handler
     */
    public function patch(string $pattern, $handler): RouteInterface;

    /**
     * 註冊 DELETE 路由.
     *
     * @param callable|string|array $handler
     */
    public function delete(string $pattern, $handler): RouteInterface;

    /**
     * 註冊 OPTIONS 路由.
     *
     * @param callable|string|array $handler
     */
    public function options(string $pattern, $handler): RouteInterface;

    /**
     * 註冊支援任何 HTTP 方法的路由.
     *
     * @param string[] $methods
     * @param callable|string|array $handler
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface;

    /**
     * 註冊支援所有 HTTP 方法的路由.
     *
     * @param callable|string|array $handler
     */
    public function any(string $pattern, $handler): RouteInterface;

    /**
     * 建立路由群組.
     *
     * @param array $attributes 群組屬性 (prefix, middleware, namespace, etc.)
     */
    public function group(array $attributes, callable $callback): void;

    /**
     * 解析請求並回傳匹配的路由.
     */
    public function dispatch(ServerRequestInterface $request): RouteMatchResult;

    /**
     * 取得路由收集器.
     */
    public function getRoutes(): RouteCollectionInterface;

    /**
     * 根據路由名稱產生 URL.
     */
    public function url(string $name, array $parameters = []): string;

    /**
     * 設定路由快取.
     */
    public function setCache(?RouteCacheInterface $cache): void;

    /**
     * 取得路由快取.
     */
    public function getCache(): ?RouteCacheInterface;

    /**
     * 設定中介軟體管理器.
     *
     * @param MiddlewareManagerInterface $middlewareManager 中介軟體管理器
     */
    public function setMiddlewareManager(MiddlewareManagerInterface $middlewareManager): void;

    /**
     * 取得中介軟體管理器.
     */
    public function getMiddlewareManager(): ?MiddlewareManagerInterface;

    /**
     * 新增全域中介軟體.
     *
     * @param MiddlewareInterface $middleware 中介軟體實例
     */
    public function addGlobalMiddleware(MiddlewareInterface $middleware): void;

    /**
     * 新增多個全域中介軟體.
     *
     * @param MiddlewareInterface[] $middlewares 中介軟體陣列
     */
    public function addGlobalMiddlewares(array $middlewares): void;
}
