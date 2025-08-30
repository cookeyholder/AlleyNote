<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Core;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\MiddlewareManagerInterface;
use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;
use App\Infrastructure\Routing\Contracts\RouteInterface;
use App\Infrastructure\Routing\Contracts\RouteMatchResult;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由器核心實作.
 *
 * 提供路由註冊、解析和執行的完整功能
 */
class Router implements RouterInterface
{
    private RouteCollectionInterface $routes;

    private ?RouteCacheInterface $cache = null;

    private ?MiddlewareManagerInterface $middlewareManager = null;

    /** @var array<string, mixed> 當前群組屬性 */
    private array $currentGroupAttributes = [];

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function get(string $pattern, $handler): RouteInterface
    {
        return $this->map(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, $handler): RouteInterface
    {
        return $this->map(['POST'], $pattern, $handler);
    }

    public function put(string $pattern, $handler): RouteInterface
    {
        return $this->map(['PUT'], $pattern, $handler);
    }

    public function patch(string $pattern, $handler): RouteInterface
    {
        return $this->map(['PATCH'], $pattern, $handler);
    }

    public function delete(string $pattern, $handler): RouteInterface
    {
        return $this->map(['DELETE'], $pattern, $handler);
    }

    public function options(string $pattern, $handler): RouteInterface
    {
        return $this->map(['OPTIONS'], $pattern, $handler);
    }

    public function map(array $methods, string $pattern, $handler): RouteInterface
    {
        // 套用群組前綴
        if (!empty($this->currentGroupAttributes['prefix'])) {
            $pattern = $this->applyPrefix($this->currentGroupAttributes['prefix'], $pattern);
        }

        // 套用命名空間到處理器
        if (!empty($this->currentGroupAttributes['namespace']) && is_array($handler)) {
            $handler[0] = $this->currentGroupAttributes['namespace'] . '\\' . $handler[0];
        }

        $route = new Route($methods, $pattern, $handler);

        // 套用群組中間件
        if (!empty($this->currentGroupAttributes['middleware'])) {
            $route->middleware($this->currentGroupAttributes['middleware']);
        }

        $this->routes->add($route);

        return $route;
    }

    public function any(string $pattern, $handler): RouteInterface
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $handler);
    }

    public function group(array $attributes, callable $callback): void
    {
        $previousAttributes = $this->currentGroupAttributes;

        // 合併群組屬性
        $this->currentGroupAttributes = $this->mergeGroupAttributes($previousAttributes, $attributes);

        // 執行群組回呼
        $callback($this);

        // 恢復之前的群組屬性
        $this->currentGroupAttributes = $previousAttributes;
    }

    public function dispatch(ServerRequestInterface $request): RouteMatchResult
    {
        // 嘗試從快取載入路由
        if ($this->cache !== null && $this->cache->isValid()) {
            $cachedRoutes = $this->cache->load();
            if ($cachedRoutes !== null) {
                $this->routes = $cachedRoutes;
            }
        }

        // 尋找匹配的路由
        $matchedRoute = $this->routes->match($request);

        if ($matchedRoute === null) {
            return RouteMatchResult::failed('No route matched the request');
        }

        // 擷取路由參數
        $parameters = $matchedRoute->extractParameters($request->getUri()->getPath());

        return RouteMatchResult::success($matchedRoute, $parameters);
    }

    public function getRoutes(): RouteCollectionInterface
    {
        return $this->routes;
    }

    public function url(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);

        if ($route === null) {
            throw new InvalidArgumentException("Route named '{$name}' not found");
        }

        $url = $route->getPattern();

        // 替換路徑參數
        foreach ($parameters as $key => $value) {
            $url = str_replace('{' . $key . '}', (string) $value, $url);
        }

        // 檢查是否還有未替換的參數
        if (preg_match('/\{[^}]+\}/', $url)) {
            throw new InvalidArgumentException("Missing required parameters for route '{$name}'");
        }

        return $url;
    }

    public function setCache(?RouteCacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getCache(): ?RouteCacheInterface
    {
        return $this->cache;
    }

    /**
     * 快取當前路由定義.
     */
    public function cacheRoutes(): bool
    {
        if ($this->cache === null) {
            return false;
        }

        return $this->cache->store($this->routes);
    }

    public function setMiddlewareManager(MiddlewareManagerInterface $middlewareManager): void
    {
        $this->middlewareManager = $middlewareManager;
    }

    public function getMiddlewareManager(): ?MiddlewareManagerInterface
    {
        return $this->middlewareManager;
    }

    public function addGlobalMiddleware(MiddlewareInterface $middleware): void
    {
        if ($this->middlewareManager !== null) {
            $this->middlewareManager->add($middleware);
        }
    }

    public function addGlobalMiddlewares(array $middlewares): void
    {
        if ($this->middlewareManager !== null) {
            $this->middlewareManager->addMultiple($middlewares);
        }
    }

    /**
     * 套用群組前綴到路由模式.
     */
    private function applyPrefix(string $prefix, string $pattern): string
    {
        $prefix = trim($prefix, '/');
        $pattern = trim($pattern, '/');

        if (empty($prefix)) {
            return '/' . $pattern;
        }

        if (empty($pattern)) {
            return '/' . $prefix;
        }

        return '/' . $prefix . '/' . $pattern;
    }

    /**
     * 合併群組屬性.
     *
     * @return array<string, mixed>
     */
    private function mergeGroupAttributes(array $previous, array $new): array
    {
        $merged = $previous;

        // 合併前綴
        if (!empty($new['prefix'])) {
            $existingPrefix = $merged['prefix'] ?? '';
            $merged['prefix'] = $this->applyPrefix($existingPrefix, $new['prefix']);
        }

        // 合併中間件
        if (!empty($new['middleware'])) {
            $existingMiddleware = $merged['middleware'] ?? [];
            $newMiddleware = is_array($new['middleware']) ? $new['middleware'] : [$new['middleware']];
            $merged['middleware'] = array_merge($existingMiddleware, $newMiddleware);
        }

        // 合併命名空間
        if (!empty($new['namespace'])) {
            $existingNamespace = $merged['namespace'] ?? '';
            $merged['namespace'] = empty($existingNamespace)
                ? $new['namespace']
                : $existingNamespace . '\\' . $new['namespace'];
        }

        // 其他屬性直接覆蓋
        foreach ($new as $key => $value) {
            if (!in_array($key, ['prefix', 'middleware', 'namespace'], true)) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
