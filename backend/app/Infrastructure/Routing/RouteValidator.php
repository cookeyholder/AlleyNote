<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;

/**
 * 路由驗證器.
 *
 * 負責驗證路由配置的正確性
 */
class RouteValidator
{
    /**
     * 有效的 HTTP 方法.
     */
    private const VALID_HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD',
    ];

    /**
     * 已註冊的路由，用於檢查重複.
     */
    private array $registeredRoutes = [];

    /**
     * 驗證路由配置.
     */
    public function validateRoute(array $routeConfig): void
    {
        $this->validateRouteStructure($routeConfig);
        $this->validateHttpMethods($routeConfig);
        $this->validatePath($routeConfig);
        $this->validateHandler($routeConfig);
        $this->checkDuplicateRoute($routeConfig);
    }

    /**
     * 驗證路由基本結構.
     */
    private function validateRouteStructure(array $routeConfig): void
    {
        $required = ['methods', 'path', 'handler'];

        foreach ($required as $field) {
            if (!array_key_exists($field, $routeConfig)) {
                $routeName = isset($routeConfig['name']) && is_string($routeConfig['name'])
                    ? $routeConfig['name']
                    : '未命名路由';
                throw RouteConfigurationException::invalidRouteDefinition(
                    $routeName,
                    "缺少必要欄位: {$field}",
                );
            }
        }
    }

    /**
     * 驗證 HTTP 方法.
     */
    private function validateHttpMethods(array $routeConfig): void
    {
        $methods = (array) $routeConfig['methods'];
        $routeName = isset($routeConfig['name']) && is_string($routeConfig['name'])
            ? $routeConfig['name']
            : '未命名路由';

        if (empty($methods)) {
            throw RouteConfigurationException::invalidRouteDefinition(
                $routeName,
                'HTTP 方法不能為空',
            );
        }

        foreach ($methods as $method) {
            if (!is_string($method)) {
                throw RouteConfigurationException::invalidRouteDefinition(
                    $routeName,
                    'HTTP 方法必須是字串',
                );
            }

            $method = strtoupper(trim($method));
            if (!in_array($method, self::VALID_HTTP_METHODS, true)) {
                throw RouteConfigurationException::invalidRouteDefinition(
                    $routeName,
                    "無效的 HTTP 方法: {$method}",
                );
            }
        }
    }

    /**
     * 驗證路由路径.
     */
    private function validatePath(array $routeConfig): void
    {
        $path = $routeConfig['path'];
        $routeName = isset($routeConfig['name']) && is_string($routeConfig['name'])
            ? $routeConfig['name']
            : '未命名路由';

        if (!is_string($path)) {
            throw RouteConfigurationException::invalidRouteDefinition(
                $routeName,
                '路由路径必須是字串',
            );
        }

        if (empty($path) || $path[0] !== '/') {
            throw RouteConfigurationException::invalidRouteDefinition(
                $routeName,
                '路由路径必須以 "/" 開始',
            );
        }

        // 檢查路径參數語法
        if (preg_match_all('/\{([^}]+)\}/', $path, $matches)) {
            foreach ($matches[1] as $param) {
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\?)?$/', $param)) {
                    throw RouteConfigurationException::invalidRouteDefinition(
                        $routeName,
                        "無效的路由參數格式: {{$param}}",
                    );
                }
            }
        }
    }

    /**
     * 驗證處理器.
     */
    private function validateHandler(array $routeConfig): void
    {
        $handler = $routeConfig['handler'];
        $routeName = isset($routeConfig['name']) && is_string($routeConfig['name'])
            ? $routeConfig['name']
            : '未命名路由';

        // 允許的處理器格式：
        // 1. 閉包 (Closure)
        // 2. 字串格式 'ControllerClass@method'
        // 3. 陣列格式 [ControllerClass::class, 'method']
        // 4. 可呼叫的物件或函式

        if (is_callable($handler)) {
            return; // 閉包或可呼叫物件
        }

        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                $parts = explode('@', $handler, 2);
                if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
                    throw RouteConfigurationException::invalidHandler($routeName, $handler);
                }

                return;
            }
        }

        if (is_array($handler)) {
            if (
                count($handler) === 2
                && is_string($handler[0])
                && is_string($handler[1])
                && !empty($handler[0])
                && !empty($handler[1])
            ) {
                return;
            }
        }

        throw RouteConfigurationException::invalidHandler($routeName, $handler);
    }

    /**
     * 檢查重複路由.
     */
    private function checkDuplicateRoute(array $routeConfig): void
    {
        $methods = (array) $routeConfig['methods'];
        $path = $routeConfig['path'];

        if (!is_string($path)) {
            return;
        }

        foreach ($methods as $method) {
            if (!is_string($method)) {
                continue;
            }

            $methodStr = strtoupper(trim($method));
            $key = "{$methodStr}:{$path}";

            if (isset($this->registeredRoutes[$key])) {
                throw RouteConfigurationException::duplicateRoute($methodStr, $path);
            }

            $this->registeredRoutes[$key] = true;
        }
    }

    /**
     * 重設驗證狀態（用於測試或重新驗證）.
     */
    public function reset(): void
    {
        $this->registeredRoutes = [];
    }

    /**
     * 取得已註冊的路由清單.
     */
    public function getRegisteredRoutes(): array
    {
        return array_keys($this->registeredRoutes);
    }
}
