<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Providers;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\Middleware\MiddlewareResolver;
use App\Infrastructure\Routing\RouteDispatcher;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * 路由服務提供者.
 *
 * 負責註冊所有路由相關服務到 DI 容器
 */
class RoutingServiceProvider
{
    /**
     * 取得所有路由服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // 路由器核心服務
            RouterInterface::class => \DI\create(Router::class),

            Router::class => \DI\create(Router::class),

            // 路由驗證器
            RouteValidator::class => \DI\create(RouteValidator::class),

            // 路由載入器
            RouteLoader::class => \DI\factory([self::class, 'createRouteLoader']),

            // 控制器解析器
            ControllerResolver::class => \DI\factory([self::class, 'createControllerResolver']),

            // 中間件解析器
            MiddlewareResolver::class => \DI\factory([self::class, 'createMiddlewareResolver']),

            // 中間件分派器
            MiddlewareDispatcher::class => \DI\create(MiddlewareDispatcher::class),

            // 路由分派器
            RouteDispatcher::class => \DI\factory([self::class, 'createRouteDispatcher']),
        ];
    }

    /**
     * 建立路由載入器實例.
     */
    public static function createRouteLoader(ContainerInterface $container): RouteLoader
    {
        $validator = $container->get(RouteValidator::class);
        if (!($validator instanceof RouteValidator)) {
            $validator = null;
        }

        return new RouteLoader($validator);
    }

    /**
     * 建立控制器解析器實例.
     */
    public static function createControllerResolver(ContainerInterface $container): ControllerResolver
    {
        return new ControllerResolver($container);
    }

    /**
     * 建立中介軟體解析器實例.
     */
    public static function createMiddlewareResolver(ContainerInterface $container): MiddlewareResolver
    {
        return new MiddlewareResolver($container);
    }

    /**
     * 建立路由分派器實例.
     */
    public static function createRouteDispatcher(ContainerInterface $container): RouteDispatcher
    {
        $router = $container->get(RouterInterface::class);
        $controllerResolver = $container->get(ControllerResolver::class);
        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        $middlewareResolver = $container->get(MiddlewareResolver::class);

        if (!($router instanceof RouterInterface)) {
            throw new \RuntimeException('RouterInterface not found in container');
        }
        if (!($controllerResolver instanceof ControllerResolver)) {
            throw new \RuntimeException('ControllerResolver not found in container');
        }
        if (!($middlewareDispatcher instanceof MiddlewareDispatcher)) {
            throw new \RuntimeException('MiddlewareDispatcher not found in container');
        }

        return new RouteDispatcher(
            $router,
            $controllerResolver,
            $middlewareDispatcher,
            $container,
        );
    }

    /**
     * 取得路由配置檔案清單.
     */
    public static function getRouteFiles(): array
    {
        return [
            'api' => __DIR__ . '/../../../../config/routes/api.php',
            'web' => __DIR__ . '/../../../../config/routes/web.php',
            'auth' => __DIR__ . '/../../../../config/routes/auth.php',
            'admin' => __DIR__ . '/../../../../config/routes/admin.php',
            'statistics' => __DIR__ . '/../../../../config/routes/statistics.php',
            'activity-logs' => __DIR__ . '/../../../../config/routes/activity-logs.php',
        ];
    }

    /**
     * 載入路由配置到路由器.
     */
    public static function loadRoutes(ContainerInterface $container): void
    {
        $routeLoader = $container->get(RouteLoader::class);
        $router = $container->get(RouterInterface::class);

        if (!($routeLoader instanceof RouteLoader) || !($router instanceof RouterInterface)) {
            error_log('路由元件無效');
            return;
        }

        try {
            // 載入各種路由配置檔案
            foreach (self::getRouteFiles() as $group => $filePath) {
                if (is_string($filePath) && file_exists($filePath)) {
                    $routeLoader->addRouteFile($filePath, $group);
                }
            }

            // 載入所有路由到路由器
            $routeLoader->loadRoutes($router);
        } catch (Throwable $e) {
            // 記錄路由載入錯誤並回退到基本配置
            error_log('路由載入失敗: ' . $e->getMessage());

            // 嘗試載入舊版路由檔案作為回退
            $legacyRoutesFile = __DIR__ . '/../../../../config/routes.php';
            if (file_exists($legacyRoutesFile)) {
                $routeDefinitions = require $legacyRoutesFile;
                if (is_callable($routeDefinitions)) {
                    $routeDefinitions($router);
                }
            }
        }
    }

    /**
     * 取得路由系統統計資訊.
     */
    public static function getRoutingStats(ContainerInterface $container): array
    {
        try {
            $routeLoader = $container->get(RouteLoader::class);
            if (!($routeLoader instanceof RouteLoader)) {
                return [
                    'error' => 'RouteLoader not found',
                    'total_routes' => 0,
                    'files_loaded' => 0,
                    'groups' => [],
                ];
            }

            return $routeLoader->getRouteStats();
        } catch (Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'total_routes' => 0,
                'files_loaded' => 0,
                'groups' => [],
            ];
        }
    }
}
