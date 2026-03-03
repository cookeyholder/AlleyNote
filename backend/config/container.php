<?php

declare(strict_types=1);

/**
 * DI 容器配置檔案
 *
 * 定義應用程式所有服務的依賴注入配置
 */

use App\Domains\Auth\Providers\SimpleAuthServiceProvider;
use App\Domains\Auth\Repositories\RoleRepository;
use App\Domains\Auth\Repositories\PermissionRepository;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\Services\RoleManagementService;
use App\Application\Controllers\Api\V1\UserController;
use App\Application\Controllers\Api\V1\RoleController;
use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Contracts\TagRepositoryInterface;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Post\Repositories\TagRepository;
use App\Domains\Post\Services\PostService;
use App\Domains\Post\Services\TagManagementService;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Domains\Security\Providers\SecurityServiceProvider;
use App\Domains\Security\Services\Logging\LoggingSecurityService;
use App\Domains\Statistics\Providers\StatisticsServiceProvider;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\ServerRequestFactory;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Routing\Providers\RoutingServiceProvider;
use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Services\RateLimitService;
use App\Shared\Cache\Providers\CacheServiceProvider;
use App\Shared\Config\EnvironmentConfig;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use App\Shared\Monitoring\Contracts\PerformanceMonitorInterface;
use App\Shared\Monitoring\Contracts\SystemMonitorInterface;
use App\Shared\Monitoring\Providers\MonitoringServiceProvider;
use App\Shared\Validation\Factory\ValidatorFactory;
use App\Shared\Validation\Validator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

return array_merge(
    // 基本 HTTP 服務
    [
        // PSR-7 HTTP 消息介面
        ResponseInterface::class => \DI\factory(function () {
            return new Response();
        }),

        ServerRequestInterface::class => \DI\factory(function () {
            return ServerRequestFactory::fromGlobals();
        }),

        StreamInterface::class => \DI\factory(function () {
            return new Stream();
        }),

        // HTTP 工廠
        ServerRequestFactory::class => \DI\create(ServerRequestFactory::class),

        // 環境配置
        EnvironmentConfig::class => \DI\factory(function () {
            return new EnvironmentConfig();
        }),

        // 資料庫連線
        \PDO::class => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
            $dbPath = $c->get('db.path');
            return new \PDO('sqlite:' . $dbPath);
        }),
    ],

    // 路由系統服務
    RoutingServiceProvider::getDefinitions(),
    SecurityServiceProvider::getDefinitions(),

    // JWT 認證系統服務
    SimpleAuthServiceProvider::getDefinitions(),

    // 快取系統服務
    CacheServiceProvider::getDefinitions(),

    // 統計系統服務
    StatisticsServiceProvider::getDefinitions(),

    // 自訂中介軟體
    [
        // PostView 速率限制中介軟體
        \App\Application\Middleware\PostViewRateLimitMiddleware::class => \DI\autowire(\App\Application\Middleware\PostViewRateLimitMiddleware::class),
        'post_view_rate_limit' => \DI\get(\App\Application\Middleware\PostViewRateLimitMiddleware::class),

        // 其他控制器
        \App\Application\Controllers\Api\V1\PostViewController::class => \DI\autowire(\App\Application\Controllers\Api\V1\PostViewController::class),
    ],

    // 基本應用程式服務
    [
        // 環境配置
        'app.debug' => \DI\env('APP_DEBUG', false),
        'app.name' => \DI\env('APP_NAME', 'AlleyNote'),
        'app.version' => '1.0.0',

        // 資料庫配置（為將來準備）
        'db.path' => \DI\env('DB_PATH', __DIR__ . '/../database/alleynote.sqlite3'),
        'db.driver' => \DI\env('DB_DRIVER', 'sqlite'),

        // 日誌配置
        'log.path' => \DI\env('LOG_PATH', __DIR__ . '/../storage/logs/app.log'),
        'log.level' => \DI\env('LOG_LEVEL', 'info'),

        // 快取配置
        'cache.default_driver' => \DI\env('CACHE_DEFAULT_DRIVER', 'memory'),
        'cache.path' => \DI\env('CACHE_PATH', __DIR__ . '/../storage/cache'),

        // 快取驅動設定
        'cache.drivers.memory' => [
            'enabled' => true,
            'priority' => 90,
            'max_size' => 1000,
            'ttl' => 3600,
        ],
        'cache.drivers.file' => [
            'enabled' => true,
            'priority' => 50,
            'ttl' => 3600,
        ],
        'cache.drivers.redis' => [
            'enabled' => \DI\env('REDIS_ENABLED', false),
            'priority' => 70,
            'host' => \DI\env('REDIS_HOST', '127.0.0.1'),
            'port' => \DI\env('REDIS_PORT', 6379),
            'database' => \DI\env('REDIS_DATABASE', 0),
            'timeout' => 2.0,
            'prefix' => 'alleynote:cache:',
        ],

        // 快取策略設定
        'cache.strategy' => [
            'min_ttl' => 60,
            'max_ttl' => 86400,
            'max_value_size' => 1024 * 1024,
            'exclude_patterns' => ['temp:*', 'debug:*'],
        ],

        // 快取管理器設定
        'cache.manager' => [
            'enable_sync' => false,
            'sync_ttl' => 3600,
            'max_retry_attempts' => 3,
            'retry_delay' => 100,
        ],

        // API 配置
        'api.base_url' => \DI\env('API_BASE_URL', 'http://localhost'),
        'api.version' => \DI\env('API_VERSION', 'v1'),

        // 安全配置
        'security.jwt_secret' => \DI\env('JWT_SECRET', 'your-secret-key'),
        'security.session_lifetime' => \DI\env('SESSION_LIFETIME', 3600),
    ],

    // 第三方服務配置
    [
        // Monolog Logger
        LoggerInterface::class => \DI\factory(function (\Psr\Container\ContainerInterface $c) {
            $logger = new Logger($c->get('app.name'));
            $handler = new StreamHandler($c->get('log.path'), Logger::DEBUG);
            $logger->pushHandler($handler);
            return $logger;
        }),

        // Logger 別名
        Logger::class => \DI\get(LoggerInterface::class),

        // PDO 連線（如果需要）
        // PDO::class => \DI\factory(function (ContainerInterface $c) {
        //     return new PDO('sqlite:' . $c->get('db.path'));
        // }),

        // Cache Monitor Controller
        \App\Application\Controllers\Admin\CacheMonitorController::class => \DI\factory(function (\Psr\Container\ContainerInterface $container) {
            return new \App\Application\Controllers\Admin\CacheMonitorController(
                $container->get(\App\Shared\Monitoring\Contracts\CacheMonitorInterface::class),
                $container->get(\App\Shared\Cache\Contracts\CacheManagerInterface::class)
            );
        }),

        // Tag Management Controller
        \App\Application\Controllers\Admin\TagManagementController::class => \DI\factory(function (\Psr\Container\ContainerInterface $container) {
            $cacheManager = $container->get(\App\Shared\Cache\Contracts\CacheManagerInterface::class);

            $tagRepository = null;
            try {
                $tagRepository = $container->get(\App\Shared\Cache\Contracts\TagRepositoryInterface::class);
            } catch (\Exception) {
                // 標籤倉庫不可用
            }

            $groupManager = null;
            try {
                $groupManager = $container->get(\App\Shared\Cache\Services\CacheGroupManager::class);
            } catch (\Exception) {
                // 分組管理器不可用
            }

            $logger = null;
            try {
                $logger = $container->get(\Psr\Log\LoggerInterface::class);
            } catch (\Exception) {
                // 記錄器不可用
            }

            return new \App\Application\Controllers\Admin\TagManagementController(
                $cacheManager,
                $tagRepository,
                $groupManager,
                $logger
            );
        }),
    ],

    // 快取服務
    CacheServiceProvider::getDefinitions(),

    // 核心領域與共用服務
    [
        CacheService::class => \DI\autowire(CacheService::class),
        CacheServiceInterface::class => \DI\get(CacheService::class),

        LoggingSecurityServiceInterface::class => \DI\autowire(LoggingSecurityService::class),

        PostRepositoryInterface::class => \DI\autowire(PostRepository::class)
            ->constructorParameter('db', \DI\get(\PDO::class))
            ->constructorParameter('cache', \DI\get(CacheServiceInterface::class))
            ->constructorParameter('logger', \DI\get(LoggingSecurityServiceInterface::class)),

        PostServiceInterface::class => \DI\autowire(PostService::class)
            ->constructorParameter('repository', \DI\get(PostRepositoryInterface::class)),

        TagRepositoryInterface::class => \DI\autowire(TagRepository::class)
            ->constructorParameter('db', \DI\get(\PDO::class)),

        TagManagementService::class => \DI\autowire(TagManagementService::class)
            ->constructorParameter('tagRepository', \DI\get(TagRepositoryInterface::class)),

        RateLimitService::class => \DI\autowire(RateLimitService::class)
            ->constructorParameter('cache', \DI\get(CacheService::class)),

        ValidatorFactory::class => \DI\autowire(ValidatorFactory::class),
        ValidatorInterface::class => \DI\factory(static fn (ValidatorFactory $factory) => $factory->createForDTO()),
        Validator::class => \DI\autowire(Validator::class),

        // ========================================
        // 使用者管理模組
        // ========================================
        
        // Repositories
        RoleRepository::class => \DI\autowire(RoleRepository::class)
            ->constructorParameter('db', \DI\get(\PDO::class)),

        PermissionRepository::class => \DI\autowire(PermissionRepository::class)
            ->constructorParameter('db', \DI\get(\PDO::class)),

        UserRepository::class => \DI\autowire(UserRepository::class)
            ->constructorParameter('db', \DI\get(\PDO::class)),

        // Services
        UserManagementService::class => \DI\autowire(UserManagementService::class)
            ->constructorParameter('userRepository', \DI\get(UserRepository::class)),

        RoleManagementService::class => \DI\autowire(RoleManagementService::class)
            ->constructorParameter('roleRepository', \DI\get(RoleRepository::class))
            ->constructorParameter('permissionRepository', \DI\get(PermissionRepository::class)),

        // Controllers
        UserController::class => \DI\autowire(UserController::class)
            ->constructorParameter('userManagementService', \DI\get(UserManagementService::class)),

        RoleController::class => \DI\autowire(RoleController::class)
            ->constructorParameter('roleManagementService', \DI\get(RoleManagementService::class)),
    ],

    // 監控服務
    MonitoringServiceProvider::getDefinitions()
);
