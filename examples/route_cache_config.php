<?php

declare(strict_types=1);

/**
 * 路由快取配置範例
 * 
 * 展示如何在實際專案中配置和使用路由快取系統
 */

use App\Infrastructure\Routing\Cache\RouteCacheFactory;
use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Middleware\MiddlewareManager;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;

// 快取配置
$cacheConfigs = [
    'development' => [
        'driver' => 'memory',
        'ttl' => 60, // 1 分鐘（開發環境短期快取）
    ],
    'production' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../../storage/cache/routes',
        'ttl' => 3600, // 1 小時（生產環境長期快取）
    ],
    'testing' => [
        'driver' => 'memory',
        'ttl' => 0, // 不過期（測試環境）
    ],
];

/**
 * 根據環境建立路由器實例
 */
function createRouterWithCache(string $environment = 'development'): Router
{
    global $cacheConfigs;

    // 建立路由器
    $router = new Router();

    // 建立中間件管理器
    $middlewareDispatcher = new MiddlewareDispatcher();
    $middlewareManager = new MiddlewareManager($middlewareDispatcher);
    $router->setMiddlewareManager($middlewareManager);

    // 根據環境配置快取
    $cacheConfig = $cacheConfigs[$environment] ?? (is_array($cacheConfigs) ? $cacheConfigs['development'] : (is_object($cacheConfigs) ? $cacheConfigs->development : null));
    $cacheFactory = new RouteCacheFactory();

    try {
        $cache = $cacheFactory->create($cacheConfig);
        $router->setCache($cache);

        echo "✓ 已設定 {$environment} 環境快取: {(is_array($cacheConfig) ? $cacheConfig['driver'] : (is_object($cacheConfig) ? $cacheConfig->driver : null))} (TTL: {(is_array($cacheConfig) ? $cacheConfig['ttl'] : (is_object($cacheConfig) ? $cacheConfig->ttl : null))}s)\n";
    } catch (Exception $e) {
        echo "✗ 快取設定失敗: {$e->getMessage()}\n";
    }

    return $router;
}

/**
 * 註冊應用程式路由
 */
function registerRoutes(Router $router): void
{
    // API 路由群組
    $router->group(['prefix' => 'api/v1'], function (Router $router) {

        // 使用者相關路由
        $router->group(['prefix' => 'users'], function (Router $router) {
            $router->get('/', 'UserController@index');
            $router->post('/', 'UserController@store');
            $router->get('/{id}', 'UserController@show');
            $router->put('/{id}', 'UserController@update');
            $router->delete('/{id}', 'UserController@destroy');
        });

        // 文章相關路由
        $router->group(['prefix' => 'posts'], function (Router $router) {
            $router->get('/', 'PostController@index');
            $router->post('/', 'PostController@store');
            $router->get('/{id}', 'PostController@show');
            $router->put('/{id}', 'PostController@update');
            $router->delete('/{id}', 'PostController@destroy');

            // 文章評論路由
            $router->get('/{id}/comments', 'CommentController@index');
            $router->post('/{id}/comments', 'CommentController@store');
        });

        // 認證相關路由
        $router->group(['prefix' => 'auth'], function (Router $router) {
            $router->post('/login', 'AuthController@login');
            $router->post('/logout', 'AuthController@logout');
            $router->post('/register', 'AuthController@register');
            $router->post('/refresh', 'AuthController@refresh');
        });
    });

    // Web 路由
    $router->get('/', 'HomeController@index');
    $router->get('/about', 'HomeController@about');
    $router->get('/contact', 'HomeController@contact');
    $router->post('/contact', 'HomeController@contactSubmit');
}

/**
 * 快取統計資訊展示
 */
function displayCacheStats(Router $router): void
{
    $cache = $router->getCache();
    if (cache === null) {
        echo "沒有配置快取\n";
        return;
    }

    $stats = $cache->getStats();
    echo "\n=== 快取統計 ===\n";
    echo "快取命中: {(is_array($stats) ? $stats['hits'] : (is_object($stats) ? $stats->hits : null))}\n";
    echo "快取未命中: {(is_array($stats) ? $stats['misses'] : (is_object($stats) ? $stats->misses : null))}\n";
    echo "資料大小: {(is_array($stats) ? $stats['size'] : (is_object($stats) ? $stats->size : null))} bytes\n";

    if ((is_array($stats) ? $stats['hits'] : (is_object($stats) ? $stats->hits : null)) + (is_array($stats) ? $stats['misses'] : (is_object($stats) ? $stats->misses : null)) > 0) {
        $hitRatio = ((is_array($stats) ? $stats['hits'] : (is_object($stats) ? $stats->hits : null)) / ((is_array($stats) ? $stats['hits'] : (is_object($stats) ? $stats->hits : null)) + (is_array($stats) ? $stats['misses'] : (is_object($stats) ? $stats->misses : null)))) * 100;
        echo "命中率: " . number_format($hitRatio, 2) . "%\n";
    }

    if ((is_array($stats) ? $stats['created_at'] : (is_object($stats) ? $stats->created_at : null)) > 0) {
        echo "建立時間: " . date('Y-m-d H:i:s', (is_array($stats) ? $stats['created_at'] : (is_object($stats) ? $stats->created_at : null))) . "\n";
    }

    if ((is_array($stats) ? $stats['last_used'] : (is_object($stats) ? $stats->last_used : null)) > 0) {
        echo "最後使用: " . date('Y-m-d H:i:s', (is_array($stats) ? $stats['last_used'] : (is_object($stats) ? $stats->last_used : null))) . "\n";
    }

    echo "快取路徑: " . $cache->getCachePath() . "\n";
    echo "TTL: " . $cache->getTtl() . " 秒\n";
}

/**
 * 快取效能測試
 */
function performanceTest(Router $router): void
{
    echo "\n=== 效能測試 ===\n";

    // 測試路由註冊和快取
    $startTime = microtime(true);
    registerRoutes($router);
    $registerTime = microtime(true) - $startTime;

    // 快取路由
    $startTime = microtime(true);
    $router->cacheRoutes();
    $cacheTime = microtime(true) - $startTime;

    // 測試從快取載入
    $cache = $router->getCache();
    if ($cache && $cache->isValid()) {
        $startTime = microtime(true);
        $cache->load();
        $loadTime = microtime(true) - $startTime;

        echo "路由註冊時間: " . number_format($registerTime * 1000, 3) . " ms\n";
        echo "快取儲存時間: " . number_format($cacheTime * 1000, 3) . " ms\n";
        echo "快取載入時間: " . number_format($loadTime * 1000, 3) . " ms\n";

        $speedup = $registerTime / $loadTime;
        echo "速度提升: " . number_format($speedup, 2) . "x\n";
    }
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    echo "=== 路由快取配置範例 ===\n\n";

    // 測試不同環境的快取配置
    $environments = ['development', 'production', 'testing'];

    foreach ($environments as $env) {
        echo "\n--- {$env} 環境 ---\n";

        $router = createRouterWithCache($env);
        registerRoutes($router);

        // 快取路由
        $router->cacheRoutes();

        // 顯示統計資訊
        displayCacheStats($router);

        // 執行效能測試 (僅在 development 環境)
        if ($env === 'development') {
            $router2 = createRouterWithCache($env);
            performanceTest($router2);
        }

        // 清理快取
        if ($router->getCache()) {
            $router->getCache()->clear();
        }
    }

    echo "\n=== 測試完成 ===\n";
}
