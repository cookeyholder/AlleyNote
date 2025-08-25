<?php

declare(strict_types=1);

/**
 * 測試路由配置檔案系統 (Task 2.2).
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;

echo "=== 路由配置檔案系統測試 ===\n\n";

// 測試 1: 路由驗證器測試
echo "測試 1: 路由驗證器\n";
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
    echo "✅ 有效路由驗證通過\n";
} catch (RouteConfigurationException $e) {
    echo '❌ 有效路由驗證失敗: ' . $e->getMessage() . "\n";
}

// 無效路由測試
try {
    $invalidRoute = [
        'methods' => ['INVALID_METHOD'],
        'path' => '/api/test',
        'handler' => 'invalid_handler',
        'name' => 'invalid.route',
    ];

    $validator->validateRoute($invalidRoute);
    echo "❌ 無效路由應該被拒絕但通過了驗證\n";
} catch (RouteConfigurationException $e) {
    echo '✅ 無效路由被正確拒絕: ' . $e->getMessage() . "\n";
}

echo "\n";

// 測試 2: 路由載入器測試
echo "測試 2: 路由載入器\n";

// 建立模擬路由器
$mockRouter = new class {
    public array $routes = [];

    public function map(array $methods, string $path, $handler)
    {
        $route = new class {
            private ?string $name = null;

            private array $middlewares = [];

            public function setName(string $name): self
            {
                $this->name = $name;

                return $this;
            }

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
        };

        $this->routes[] = [
            'methods' => $methods,
            'path' => $path,
            'handler' => $handler,
            'route' => $route,
        ];

        return $route;
    }
};

$routeLoader = new RouteLoader();

try {
    // 測試載入 API 路由
    $routeLoader->addRouteFile(__DIR__ . '/../../config/routes/api.php', 'api');
    $routeLoader->loadRoutes($mockRouter);

    $stats = $routeLoader->getRouteStats();
    echo "✅ 路由載入成功\n";
    echo "   - 總路由數: {$stats['total_routes']}\n";
    echo "   - 載入檔案數: {$stats['files_loaded']}\n";
    echo '   - 路由群組: ' . implode(', ', array_keys($stats['groups'])) . "\n";

    // 檢查載入的路由
    $loadedRoutes = $routeLoader->getLoadedRoutes();
    echo "   - 已載入路由:\n";
    foreach ($loadedRoutes as $route) {
        echo "     * {$route['name']}: {$route['methods'][0]} {$route['path']}\n";
    }
} catch (Exception $e) {
    echo '❌ 路由載入失敗: ' . $e->getMessage() . "\n";
}

echo "\n";

// 測試 3: 多個路由檔案載入測試
echo "測試 3: 多個路由檔案載入\n";

$multiRouteLoader = new RouteLoader();
$multiMockRouter = new class {
    public array $routes = [];

    public function map(array $methods, string $path, $handler)
    {
        $route = new class {
            private ?string $name = null;

            private array $middlewares = [];

            public function setName(string $name): self
            {
                $this->name = $name;

                return $this;
            }

            public function middleware($middleware): self
            {
                $this->middlewares[] = $middleware;

                return $this;
            }
        };

        $this->routes[] = [
            'methods' => $methods,
            'path' => $path,
            'handler' => $handler,
        ];

        return $route;
    }
};

try {
    $multiRouteLoader
        ->addRouteFile(__DIR__ . '/../../config/routes/api.php', 'api')
        ->addRouteFile(__DIR__ . '/../../config/routes/web.php', 'web')
        ->addRouteFile(__DIR__ . '/../../config/routes/auth.php', 'auth')
        ->addRouteFile(__DIR__ . '/../../config/routes/admin.php', 'admin');

    $multiRouteLoader->loadRoutes($multiMockRouter);

    $stats = $multiRouteLoader->getRouteStats();
    echo "✅ 多檔案路由載入成功\n";
    echo "   - 總路由數: {$stats['total_routes']}\n";
    echo "   - 路由群組統計:\n";
    foreach ($stats['groups'] as $group => $count) {
        echo "     * {$group}: {$count} 條路由\n";
    }
} catch (Exception $e) {
    echo '❌ 多檔案路由載入失敗: ' . $e->getMessage() . "\n";
}

echo "\n";

// 測試 4: 路由搜尋功能測試
echo "測試 4: 路由搜尋功能\n";

try {
    // 按群組搜尋路由
    $apiRoutes = $multiRouteLoader->getRoutesByGroup('api');
    echo '✅ API 路由搜尋: 找到 ' . count($apiRoutes) . " 條路由\n";

    $adminRoutes = $multiRouteLoader->getRoutesByGroup('admin');
    echo '✅ Admin 路由搜尋: 找到 ' . count($adminRoutes) . " 條路由\n";

    // 自訂篩選器搜尋
    $postRoutes = $multiRouteLoader->findRoutes(function ($route) {
        return strpos($route['path'], '/posts') !== false;
    });
    echo '✅ 貼文相關路由搜尋: 找到 ' . count($postRoutes) . " 條路由\n";
} catch (Exception $e) {
    echo '❌ 路由搜尋失敗: ' . $e->getMessage() . "\n";
}

echo "\n=== 測試完成 ===\n";
