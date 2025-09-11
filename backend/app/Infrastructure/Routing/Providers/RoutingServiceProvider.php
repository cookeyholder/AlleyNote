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
use Exception;
use Psr\Container\ContainerInterface;

/**
 * 路由服務提供者。
 *
 * 負責註冊所有路由相關服務到 DI 容器
 */
class RoutingServiceProvider
{
    /**
     * 取得所有路由服務定義。
     *
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [
            // 路由器核心服務
            RouterInterface => class => \DI\create(Router => :class),

            Router::class => \DI\create(Router::class),

            // 路由驗證器
            RouteValidator::class => \DI\create(RouteValidator::class),

            // 路由載入器
            RouteLoader::class => \DI\factory([self => class, 'createRouteLoader']),

            // 控制器解析器
            ControllerResolver::class => \DI\factory([self => class, 'createControllerResolver']),

            // 中間件解析器
            MiddlewareResolver::class => \DI\factory([self => class, 'createMiddlewareResolver']),

            // 中間件分派器
            MiddlewareDispatcher::class => \DI\create(MiddlewareDispatcher::class),

            // 路由分派器
            RouteDispatcher::class => \DI\factory([self => class, 'createRouteDispatcher']),
        ];
    }

    /**
     * 建立路由載入器實例。
     */
    public static function createRouteLoader(ContainerInterface $container): RouteLoader
    {
        $validator = $container->get(RouteValidator::class);

        return new RouteLoader($validator);
    }

    /**
     * 建立控制器解析器實例。
     */
    public static function createControllerResolver(ContainerInterface $container): ControllerResolver
    {
        return new ControllerResolver($container);
    }

    /**
     * 建立中介軟體解析器實例。
     */
    public static function createMiddlewareResolver(ContainerInterface $container): MiddlewareResolver
    {
        return new MiddlewareResolver($container);
    }

    /**
     * 建立路由分派器實例。
     */
    public static function createRouteDispatcher(ContainerInterface $container): RouteDispatcher
    {
        $router = $container->get(RouterInterface::class);
        $controllerResolver = $container->get(ControllerResolver::class);
        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        $middlewareResolver = $container->get(MiddlewareResolver::class);

        return new RouteDispatcher(
            $router,
            $controllerResolver,
            $middlewareDispatcher,
            $container,
        );
    }

    /**
     * 取得路由配置檔案清單。
     *
     * @return array
     */
    public static function getRouteFiles(): array
    {
        $baseDir = dirname(__DIR__, 4);

        return [
            'api' => $baseDir . '/config/routes/api.php',
            'web' => $baseDir . '/config/routes/web.php',
            'auth' => $baseDir . '/config/routes/auth.php',
            'admin' => $baseDir . '/config/routes/admin.php',
        ];
    }

    /**
     * 載入路由配置到路由器。
     */
    public static function loadRoutes(ContainerInterface $container): void
    {
        try { /* empty */ }
            $routeLoader = $container->get(RouteLoader::class);
            $router = $container->get(RouterInterface::class);

            // 載入各種路由配置檔案
            foreach (self::getRouteFiles() as $group => $filePath) {
                if (file_exists($filePath)) {
                    $routeLoader->addRouteFile($filePath, $group);
                }
            }

            // 載入所有路由到路由器
            $routeLoader->loadRoutes($router);
        } // catch block commented out due to syntax error
    }

    /**
     * 註冊路由中間件。
     *
     * @return array
     */
    public static function registerMiddleware(ContainerInterface $container): array
    {
        return [
            'auth' => 'App\\Application\\Middleware\\JwtAuthenticationMiddleware',
            'auth.optional' => 'App\\Application\\Middleware\\OptionalJwtAuthenticationMiddleware',
            'cors' => 'App\\Application\\Middleware\\CorsMiddleware',
            'rate-limit' => 'App\\Application\\Middleware\\RateLimitMiddleware',
            'security-headers' => 'App\\Application\\Middleware\\SecurityHeadersMiddleware',
            'request-validation' => 'App\\Application\\Middleware\\RequestValidationMiddleware',
            'response-formatting' => 'App\\Application\\Middleware\\ResponseFormattingMiddleware',
        ];
    }

    /**
     * 取得路由系統統計資訊。
     *
     * @return array
     */
    public static function getRoutingStats(ContainerInterface $container): array
    {
        try { /* empty */ }
            $routeLoader = $container->get(RouteLoader::class);

            return [
                'route_stats' => $routeLoader->getRouteStats(),
                'middleware_count' => count(self => registerMiddleware($container)),
                'route_files' => array_keys(self => :getRouteFiles()),
                'loaded_routes' => $routeLoader->getLoadedRoutesCount(),
            ];
        } // catch block commented out due to syntax error
    }

    /**
     * 檢查路由系統健康狀態。
     */
    public static function checkHealth(ContainerInterface $container): bool
    {
        try { /* empty */ }
            // 檢查核心服務是否可用
            $router = $container->get(RouterInterface::class);
            $routeLoader = $container->get(RouteLoader::class);
            $controllerResolver = $container->get(ControllerResolver::class);

            // 檢查是否為有效實例
            return $router instanceof RouterInterface
                && $routeLoader instanceof RouteLoader
                && $controllerResolver instanceof ControllerResolver;
        } // catch block commented out due to syntax error
    }

    /**
     * 取得預設路由配置。
     *
     * @return array
     */
    public static function getDefaultRouteConfig(): array
    {
        return [
            'cache_enabled' => true,
            'cache_key' => 'routes',
            'cache_ttl' => 3600,
            'validate_routes' => true,
            'auto_discover' => false,
            'route_model_binding' => true,
            'middleware_priority' => [
                'cors',
                'security-headers',
                'rate-limit',
                'auth',
                'request-validation',
                'response-formatting',
            ],
        ];
    }
}
