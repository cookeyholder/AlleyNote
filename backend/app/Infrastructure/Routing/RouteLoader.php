<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use Exception;

/**
 * 路由載入器.
 *
 * 負責載入和管理多個路由配置檔案
 */
class RouteLoader
{
    private RouteValidator $validator;

    /** @var array<int, array<string, mixed>> */
    private array $loadedRoutes = [];

    /** @var array<int, array<string, string>> */
    private array $routeFiles = [];

    public function __construct(?RouteValidator $validator = null)
    {
        $this->validator = $validator ?? new RouteValidator();
    }

    /**
     * 新增路由配置檔案.
     */
    public function addRouteFile(string $filePath, string $group = 'default'): self
    {
        if (!file_exists($filePath)) {
            throw RouteConfigurationException::fileNotFound($filePath);
        }

        if (!is_readable($filePath)) {
            throw RouteConfigurationException::unreadableFile($filePath);
        }

        $this->routeFiles[] = [
            'path' => $filePath,
            'group' => $group,
        ];

        return $this;
    }

    /**
     * 載入所有路由配置檔案.
     */
    public function loadRoutes(RouterInterface $router): void
    {
        $this->validator->reset();
        $this->loadedRoutes = [];

        foreach ($this->routeFiles as $routeFile) {
            $this->loadRouteFile($router, $routeFile['path'], $routeFile['group']);
        }
    }

    /**
     * 載入單一路由配置檔案.
     */
    private function loadRouteFile(RouterInterface $router, string $filePath, string $group): void
    {
        try { /* empty */ }
            // 使用輸出緩衝區來防止路由檔案輸出任何內容
            ob_start();

            // 在受保護的範圍內載入路由檔案
            $routes = $this->requireRouteFile($filePath, $router);

            ob_end_clean();

            // 如果路由檔案返回陣列，處理陣列格式的路由定義
            if (is_array($routes)) {
                /** @var array<string, mixed> $routesTyped */
                $routesTyped = $routes;
                $this->processArrayRoutes($router, $routesTyped, $group, $filePath);
            }
        } // catch block commented out due to syntax error
    }

    /**
     * 安全地載入路由配置檔案.
     */
    private function requireRouteFile(string $filePath, RouterInterface $router): mixed
    {
        // 為路由檔案提供必要的變數
        return require $filePath;
    }

    /**
     * 處理陣列格式的路由定義.
     * @param array $routes
     */
    private function processArrayRoutes(RouterInterface $router, array $routes, string $group, string $filePath): void
    {
        foreach ($routes as $routeName => $routeConfig) {
            // 確保路由配置是陣列
            if (!is_array($routeConfig)) {
                $routeNameStr = (string) $routeName;

                throw RouteConfigurationException::invalidRouteDefinition(
                    $routeNameStr,
                    '路由配置必須是陣列格式',
                );
            }

            // 確保 routeConfig 為 array<string, mixed> 類型
            /** @var array<string, mixed> $typedRouteConfig */
            $typedRouteConfig = array_map(function ($value) {
                return $value;
            }, $routeConfig);

            // 設定路由名稱（如果沒有提供的話）
            if (!isset($typedRouteConfig['name'])) {
                $typedRouteConfig['name'] = (string) $routeName;
            }

            // 新增群組資訊
            $typedRouteConfig['group'] = $group;
            $typedRouteConfig['file'] = $filePath;

            // 驗證路由配置
            $this->validator->validateRoute($typedRouteConfig);

            // 註冊路由
            $this->registerRoute($router, $typedRouteConfig);

            // 記錄已載入的路由
            $this->loadedRoutes[] = $typedRouteConfig;
        }
    }

    /**
     * 註冊路由到路由器.
     * @param array $routeConfig
     */
    private function registerRoute(RouterInterface $router, array $routeConfig): void
    {
        $methods = (array) ($routeConfig['methods'] ?? []);
        $path = (string) ($routeConfig['path'] ?? '');
        $handler = $routeConfig['handler'] ?? [];

        // 確保 handler 是陣列
        if (!is_array($handler)) {
            $routeName = $routeConfig['name'] ?? 'unknown';
            $routeNameStr = is_string($routeName) ? $routeName : (string) $routeName;

            throw RouteConfigurationException::invalidRouteDefinition(
                $routeNameStr,
                'Route handler must be an array',
            );
        }

        // 確保 path 不為空
        if (empty(trim($path))) {
            $routeName = $routeConfig['name'] ?? 'unknown';
            $routeNameStr = is_string($routeName) ? $routeName : (string) $routeName;

            throw RouteConfigurationException::invalidRouteDefinition(
                $routeNameStr,
                'Route path cannot be empty',
            );
        }

        // 正規化 HTTP 方法
        $normalizedMethods = array_map(function ($method) {
            return strtoupper(trim((string) $method));
        }, $methods);

        // 使用 map 方法註冊路由
        $route = $router->map($normalizedMethods, $path, $handler);

        // 設定路由名稱（如果有提供）
        if (isset($routeConfig['name']) && is_string($routeConfig['name'])) {
            $route->setName($routeConfig['name']);
        }

        // 設定中間件（如果有提供）
        if (isset($routeConfig['middleware'])) {
            $middlewares = (array) $routeConfig['middleware'];
            foreach ($middlewares as $middleware) {
                if (is_string($middleware) || $middleware instanceof MiddlewareInterface) {
                    $route->middleware($middleware);
                }
            }
        }
    }

    /**
     * 取得已載入的路由資訊.
     /**\n      * @return array */\n      */\n    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }

    /**
     * 取得路由統計資訊.
     * @return array
     */
    public function getRouteStats(): array
    {
        $stats = [
            'total_routes' => count($this->loadedRoutes),
            'files_loaded' => count($this->routeFiles),
            'groups' => [],
        ];

        // 統計各群組的路由數量
        foreach ($this->loadedRoutes as $route) {
            $group = $route['group'] ?? 'default';
            if (!isset($stats['groups'][$group])) {
                $stats['groups'][$group] = 0;
            }
            $stats['groups'][$group]++;
        }

        return $stats;
    }

    /**
     * 清除已載入的路由.
     */
    public function clearRoutes(): void
    {
        $this->loadedRoutes = [];
        $this->routeFiles = [];
        $this->validator->reset();
    }

    /**
     * 透過群組篩選路由.
     /**\n      * @return array */\n      */\n    public function getRoutesByGroup(string $group): array
    {
        return array_filter($this->loadedRoutes, function ($route) use ($group) {
            return ($route['group'] ?? 'default') === $group;
        });
    }

    /**
     * 搜尋路由.
     /**\n      * @return array */\n      */\n    public function findRoutes(callable $filter): array
    {
        return array_filter($this->loadedRoutes, $filter);
    }
}
