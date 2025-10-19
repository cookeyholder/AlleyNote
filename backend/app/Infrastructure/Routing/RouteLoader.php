<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use ParseError;
use Throwable;

/**
 * 路由載入器.
 *
 * 負責載入和管理多個路由配置檔案
 */
class RouteLoader
{
    private RouteValidator $validator;

    private array $loadedRoutes = [];

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
            if (!is_array($routeFile)) {
                continue;
            }

            $path = isset($routeFile['path']) && is_string($routeFile['path']) ? $routeFile['path'] : '';
            $group = isset($routeFile['group']) && is_string($routeFile['group']) ? $routeFile['group'] : 'default';

            if ($path !== '') {
                $this->loadRouteFile($router, $path, $group);
            }
        }
    }

    /**
     * 載入單一路由配置檔案.
     */
    private function loadRouteFile(RouterInterface $router, string $filePath, string $group): void
    {
        try {
            // 使用輸出緩衝區來防止路由檔案輸出任何內容
            ob_start();

            // 在受保護的範圍內載入路由檔案
            $routes = $this->requireRouteFile($filePath, $router);

            ob_end_clean();

            // 如果路由檔案返回陣列，處理陣列格式的路由定義
            if (is_array($routes)) {
                $this->processArrayRoutes($router, $routes, $group, $filePath);
            }
        } catch (ParseError $e) {
            throw RouteConfigurationException::syntaxError($filePath, $e->getMessage());
        } catch (Throwable $e) {
            if ($e instanceof RouteConfigurationException) {
                throw $e;
            }

            throw RouteConfigurationException::syntaxError(
                $filePath,
                '載入檔案時發生錯誤: ' . $e->getMessage(),
            );
        }
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
     */
    private function processArrayRoutes(RouterInterface $router, array $routes, string $group, string $filePath): void
    {
        foreach ($routes as $routeName => $routeConfig) {
            // 確保路由配置是陣列
            if (!is_array($routeConfig)) {
                throw RouteConfigurationException::invalidRouteDefinition(
                    $routeName,
                    '路由配置必須是陣列格式',
                );
            }

            // 設定路由名稱（如果沒有提供的話）
            if (!isset($routeConfig['name'])) {
                $routeConfig['name'] = is_string($routeName) ? $routeName : "route_{$routeName}";
            }

            // 新增群組資訊
            $routeConfig['group'] = $group;
            $routeConfig['file'] = $filePath;

            // 驗證路由配置
            $this->validator->validateRoute($routeConfig);

            // 註冊路由
            $this->registerRoute($router, $routeConfig);

            // 記錄已載入的路由
            $this->loadedRoutes[] = $routeConfig;
        }
    }

    /**
     * 註冊路由到路由器.
     */
    private function registerRoute(RouterInterface $router, array $routeConfig): void
    {
        $methods = isset($routeConfig['methods']) ? (array) $routeConfig['methods'] : ['GET'];
        $path = isset($routeConfig['path']) && is_string($routeConfig['path']) ? $routeConfig['path'] : '/';
        $handler = $routeConfig['handler'] ?? '';

        // 驗證 handler 型別
        if (!is_string($handler) && !is_array($handler) && !is_callable($handler)) {
            $handler = '';
        }

        // 正規化 HTTP 方法
        $normalizedMethods = array_map(function ($method) {
            if (!is_scalar($method)) {
                return 'GET';
            }

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
                if (is_string($middleware)) {
                    $route->middleware($middleware);
                }
            }
        }
    }

    /**
     * 取得已載入的路由資訊.
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }

    /**
     * 取得路由統計資訊.
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
            if (!is_array($route)) {
                continue;
            }

            $group = isset($route['group']) && is_string($route['group']) ? $route['group'] : 'default';
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
     */
    public function getRoutesByGroup(string $group): array
    {
        return array_filter($this->loadedRoutes, function ($route) use ($group) {
            if (!is_array($route)) {
                return false;
            }
            $routeGroup = isset($route['group']) && is_string($route['group']) ? $route['group'] : 'default';

            return $routeGroup === $group;
        });
    }

    /**
     * 搜尋路由.
     */
    public function findRoutes(callable $filter): array
    {
        return array_filter($this->loadedRoutes, $filter);
    }
}
