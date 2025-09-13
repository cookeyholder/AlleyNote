<?php

declare(strict_types=1);

/**
 * 測試路由配置檔案系統 (Task 2.2).
 */
require_once __DIR__ . '/././vendor/autoload.php';

use App\Infrastructure\Routing\RouteValidator;

echo '=== 路由配置檔案系統測試 ===

';

// 測試 1: 路由驗證器測試
echo '測試 1: 路由驗證器
';
$validator = new RouteValidator();

// 有效路由測試
try {
    $validRoute = [
        'methods' => ['GET'],
        'path' => '/api/test',
        'handler' => function () {
            return 'test';
        },
        'name' => 'test.route',
    ];

    $validator->validateRoute($validRoute);
    echo '✅ 有效路由驗證通過
';
} catch (Exception $e) {
    echo '錯誤: ' . $e->getMessage() . "\n";
}
$invalidRoute = [
    'methods' => ['INVALID_METHOD'],
    'path' => '/api/test',
    'handler' => 'invalid_handler',
    'name' => 'invalid.route',
];

$validator->validateRoute($invalidRoute);
echo '❌ 無效路由應該被拒絕但通過了驗證
';

class MockRoute
{
    private array $middlewares = [];

    private ?string $name = null;

    public function middleware($middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}

class MockRouteLoader
{
    private array $routes = [];

    public function addRoute(array $methods, string $path, string $handler): MockRoute
    {
        $route = new MockRoute();

        $this->routes[] = [
            'methods' => $methods,
            'path' => $path,
            'handler' => $handler,
            'route' => $route,
        ];

        return $route;
    }
}

$routeLoader = new MockRouteLoader();

try {
    // 測試程式碼
} catch (Exception $e) {
    echo '錯誤: ' . $e->getMessage();
}
