<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Core;

use App\Infrastructure\Routing\Contracts\RouteCollectionInterface;
use App\Infrastructure\Routing\Contracts\RouteInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由收集器.
 *
 * 管理和組織所有註冊的路由
 */
class RouteCollection implements RouteCollectionInterface
{
    /** @var RouteInterface[] */
    private array $routes = [];

    /** @var array<string, RouteInterface> */
    private array $namedRoutes = [];

    /** @var array<string, RouteInterface[]> */
    private array $routesByMethod = [];

    public function add(RouteInterface $route): void
    {
        $this->routes[] = $route;

        // 如果路由有名稱，加入命名路由索引
        if ($route->getName() !== null) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        // 按 HTTP 方法建立索引
        foreach ($route->getMethods() as $method) {
            $method = strtoupper($method);
            if (!isset($this->routesByMethod[$method])) {
                $this->routesByMethod[$method] = [];
            }
            $this->routesByMethod[$method][] = $route;
        }
    }

    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            if ($route instanceof RouteInterface) {
                $this->add($route);
            }
        }
    }

    public function getByName(string $name): ?RouteInterface
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function getByMethod(string $method): array
    {
        $method = strtoupper($method);

        return $this->routesByMethod[$method] ?? [];
    }

    public function match(ServerRequestInterface $request): ?RouteInterface
    {
        $method = $request->getMethod();
        $candidates = $this->getByMethod($method);

        foreach ($candidates as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        return null;
    }

    public function has(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    public function remove(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        $route = $this->namedRoutes[$name];
        unset($this->namedRoutes[$name]);

        // 從主要路由列表中移除
        $this->routes = array_filter($this->routes, static fn($r) => $r !== $route);

        // 從方法索引中移除
        foreach ($route->getMethods() as $method) {
            $method = strtoupper($method);
            if (isset($this->routesByMethod[$method])) {
                $this->routesByMethod[$method] = array_filter(
                    $this->routesByMethod[$method],
                    static fn($r) => $r !== $route,
                );
            }
        }

        return true;
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
        $this->routesByMethod = [];
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->routes as $route) {
            $data[] = [
                'methods' => $route->getMethods(),
                'pattern' => $route->getPattern(),
                'handler' => $this->serializeHandler($route->getHandler()),
                'name' => $route->getName(),
                'middleware' => $route->getMiddlewares(),
            ];
        }

        return $data;
    }

    public static function fromArray(array $data): RouteCollectionInterface
    {
        $collection = new self();

        foreach ($data as $routeData) {
            $route = new Route(
                $routeData['methods'],
                $routeData['pattern'],
                $routeData['handler'], // Note: 反序列化處理器可能需要額外邏輯
            );

            if (!empty($routeData['name'])) {
                $route->setName($routeData['name']);
            }

            if (!empty($routeData['middleware'])) {
                $route->middleware($routeData['middleware']);
            }

            $collection->add($route);
        }

        return $collection;
    }

    /**
     * 序列化路由處理器.
     *
     * 注意：這裡的實作是簡化版本，實際使用時可能需要更複雜的序列化邏輯
     *
     * @param callable|string|array $handler
     */
    private function serializeHandler($handler): string|array
    {
        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class) && is_string($method)) {
                return [$class, $method];
            }
        }

        // 對於其他類型的處理器，這裡暫時回傳字串表示
        // 實際實作時可能需要更複雜的序列化邏輯
        return 'callable';
    }
}
