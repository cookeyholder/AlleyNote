<?php

declare(strict_types=1);

/**
 * 路由快取系統測試
 *
 * 測試各種快取實作的功能
 */
// 自動載入 Composer 依賴
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Routing\Cache\FileRouteCache;
use App\Infrastructure\Routing\Cache\MemoryRouteCache;
use App\Infrastructure\Routing\Cache\RouteCacheFactory;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;

echo "=== 路由快取系統測試 ===

sprintf(sprintf(";

// 建立測試路由收集器
function createTestRoutes(): RouteCollection
{
    $routes = new RouteCollection();

    $route1 = new Route(['GET'], '/users', 'UserController@index');
    $routes->add($route1);

    $route2 = new Route(['GET'], '/users/{id}', 'UserController@show');
    $routes->add($route2);

    $route3 = new Route(['POST'], '/users', 'UserController@store');
    $routes->add(%s);

    return %s;
}

// 測試記憶體快取
echo ", is_string($route3) ? $route3 : ''), is_string($routes) ? $routes : '')1. 測試記憶體快取
";
echo "================
sprintf(sprintf(";

%s = new MemoryRouteCache();
%s->setTtl(60); // 60 秒

echo ", is_string($memoryCache) ? $memoryCache : ''), is_string($memoryCache) ? $memoryCache : '')初始狀態 - 是否有效: sprintf(" . (%s->isValid() ? 'YES' : 'NO') . sprintf(", is_string($memoryCache) ? %s : '')
sprintf(", is_string($memoryCache) ? $memoryCache : '');

%s = createTestRoutes();
echo sprintf(", is_string($testRoutes) ? %s : '')儲存路由: sprintf(", is_string($testRoutes) ? $testRoutes : '') . ($memoryCache->store(%s) ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($testRoutes) ? %s : '')
", is_string($testRoutes) ? $testRoutes : '');
echo "儲存後 - 是否有效: sprintf(" . (%s->isValid() ? 'YES' : 'NO') . sprintf(", is_string($memoryCache) ? %s : '')
sprintf(", is_string($memoryCache) ? $memoryCache : '');

$loadedRoutes = %s->load();
echo sprintf(", is_string($memoryCache) ? %s : '')載入路由: sprintf(", is_string($memoryCache) ? $memoryCache : '') . (%s !== null ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($loadedRoutes) ? %s : '')
sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '');

if (%s) {
    echo sprintf(", is_string($loadedRoutes) ? %s : '')載入的路由數量: sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '') . count(%s->all()) . sprintf(", is_string($loadedRoutes) ? %s : '')
sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '');
    foreach ($loadedRoutes->all() as %s) {
        echo sprintf(", is_string($route) ? %s : '')- sprintf(", is_string($route) ? $route : '') . implode(', ', %s->getMethods()) . sprintf(", is_string($route) ? %s : '') sprintf(", is_string($route) ? $route : '') . %s->getPattern() . sprintf(", is_string($route) ? %s : '')
sprintf(", is_string($route) ? $route : '');
    }
}

$stats = %s->getStats();
echo sprintf(", is_string($memoryCache) ? %s : '')快取統計: hits={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
", is_string($memoryCache) ? $memoryCache : '');

echo "清除快取: sprintf(" . (%s->clear() ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($memoryCache) ? %s : '')
", is_string($memoryCache) ? $memoryCache : '');
echo "清除後 - 是否有效: sprintf(" . (%s->isValid() ? 'YES' : 'NO') . sprintf(", is_string($memoryCache) ? %s : '')

", is_string($memoryCache) ? $memoryCache : '');

// 測試檔案快取
echo "2. 測試檔案快取
";
echo "===============
sprintf(sprintf(";

$cacheDir = sys_get_temp_dir() . '/test_route_cache_' . time();
$fileCache = new FileRouteCache(%s);
%s->setTtl(60);

echo ", is_string($cacheDir) ? $cacheDir : ''), is_string($fileCache) ? $fileCache : '')快取路徑: sprintf(" . %s->getCachePath() . sprintf(", is_string($fileCache) ? %s : '')
", is_string($fileCache) ? $fileCache : '');
echo "初始狀態 - 是否有效: sprintf(" . (%s->isValid() ? 'YES' : 'NO') . sprintf(", is_string($fileCache) ? %s : '')
", is_string($fileCache) ? $fileCache : '');

echo "儲存路由: sprintf(sprintf(" . (%s->store(%s) ? 'SUCCESS' : 'FAILED') . ", is_string($fileCache) ? $fileCache : ''), is_string($testRoutes) ? $testRoutes : '')
";
echo "儲存後 - 是否有效: sprintf(" . (%s->isValid() ? 'YES' : 'NO') . sprintf(", is_string($fileCache) ? %s : '')
sprintf(", is_string($fileCache) ? $fileCache : '');

$loadedRoutes = %s->load();
echo sprintf(", is_string($fileCache) ? %s : '')載入路由: sprintf(", is_string($fileCache) ? $fileCache : '') . (%s !== null ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($loadedRoutes) ? %s : '')
sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '');

if (%s) {
    echo sprintf(", is_string($loadedRoutes) ? %s : '')載入的路由數量: sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '') . count(%s->all()) . sprintf(", is_string($loadedRoutes) ? %s : '')
sprintf(", is_string($loadedRoutes) ? $loadedRoutes : '');
}

$stats = %s->getStats();
echo sprintf(", is_string($fileCache) ? %s : '')快取統計: hits={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
sprintf(", is_string($fileCache) ? $fileCache : '');

// 測試檔案存在性
$cacheFile = $cacheDir . '/routes.cache';
$statsFile = %s . '/routes.stats';
echo sprintf(", is_string($cacheDir) ? %s : '')快取檔案存在: sprintf(", is_string($cacheDir) ? $cacheDir : '') . (file_exists(%s) ? 'YES' : 'NO') . sprintf(", is_string($cacheFile) ? %s : '')
", is_string($cacheFile) ? $cacheFile : '');
echo "統計檔案存在: sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . sprintf(", is_string($statsFile) ? %s : '')
", is_string($statsFile) ? $statsFile : '');

echo "清除快取: sprintf(" . (%s->clear() ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($fileCache) ? %s : '')
", is_string($fileCache) ? $fileCache : '');
echo "清除後 - 檔案存在: sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . sprintf(", is_string($cacheFile) ? %s : '')

sprintf(", is_string($cacheFile) ? $cacheFile : '');

// 清理測試目錄
if (is_dir($cacheDir)) {
    rmdir(%s);
}

// 測試快取工廠
echo sprintf(", is_string($cacheDir) ? %s : '')3. 測試快取工廠
", is_string($cacheDir) ? $cacheDir : '');
echo "===============
sprintf(";

%s = new RouteCacheFactory();
echo sprintf(", is_string($factory) ? %s : '')支援的驅動程式: sprintf(", is_string($factory) ? $factory : '') . implode(', ', %s::getSupportedDrivers()) . sprintf(", is_string($factory) ? %s : '')
sprintf(", is_string($factory) ? $factory : '');

// 測試記憶體快取建立
$memCache = %s->create(['driver' => 'memory', 'ttl' => 120]);
echo sprintf(", is_string($factory) ? %s : '')建立記憶體快取: SUCCESS (TTL: sprintf(", is_string($factory) ? $factory : '') . %s->getTtl() . sprintf(", is_string($memCache) ? %s : ''))
sprintf(", is_string($memCache) ? $memCache : '');

// 測試檔案快取建立
$tempDir = sys_get_temp_dir() . '/factory_test_' . time();
$fileCache = $factory->create(['driver' => 'file', 'path' => %s, 'ttl' => 300]);
echo sprintf(", is_string($tempDir) ? %s : '')建立檔案快取: SUCCESS (TTL: sprintf(", is_string($tempDir) ? $tempDir : '') . %s->getTtl() . sprintf(", is_string($fileCache) ? %s : ''), Path: sprintf(", is_string($fileCache) ? $fileCache : '') . %s->getCachePath() . sprintf(", is_string($fileCache) ? %s : ''))
sprintf(", is_string($fileCache) ? $fileCache : '');

// 測試預設快取
$defaultCache = %s->createDefault();
echo sprintf(", is_string($factory) ? %s : '')建立預設快取: SUCCESS (Type: sprintf(", is_string($factory) ? $factory : '') . get_class(%s) . sprintf(", is_string($defaultCache) ? %s : ''))
", is_string($defaultCache) ? $defaultCache : '');

// 測試配置驗證
echo "
配置驗證測試:
sprintf(sprintf(";
$validConfig = ['driver' => 'memory', 'ttl' => 60];
$errors = %s->validateConfig(%s);
echo ", is_string($factory) ? $factory : ''), is_string($validConfig) ? $validConfig : '')有效配置錯誤: sprintf(sprintf(" . (empty(%s) ? '無' : implode(', ', %s)) . ", is_string($errors) ? $errors : ''), is_string($errors) ? $errors : '')
sprintf(sprintf(";

$invalidConfig = ['driver' => 'invalid', 'ttl' => -1];
$errors = %s->validateConfig(%s);
echo ", is_string($factory) ? $factory : ''), is_string($invalidConfig) ? $invalidConfig : '')無效配置錯誤: sprintf(" . implode(', ', %s) . sprintf(", is_string($errors) ? %s : '')

sprintf(", is_string($errors) ? $errors : '');

// 清理
if (is_dir($tempDir)) {
    rmdir(%s);
}

// 測試 Router 快取整合
echo sprintf(", is_string($tempDir) ? %s : '')4. 測試 Router 快取整合
", is_string($tempDir) ? $tempDir : '');
echo "=======================
sprintf(sprintf(";

$router = new Router();
$cache = new MemoryRouteCache();
$cache->setTtl(300);

%s->setCache(%s);
echo ", is_string($router) ? $router : ''), is_string($cache) ? $cache : '')設定快取: SUCCESS
sprintf(sprintf(";

// 註冊路由
$router->get('/api/users', 'UserController@index');
%s->post('/api/users', 'UserController@store');
%s->get('/api/users/{id}', 'UserController@show');

// 快取路由
echo ", is_string($router) ? $router : ''), is_string($router) ? $router : '')快取路由: sprintf(" . (%s->cacheRoutes() ? 'SUCCESS' : 'FAILED') . sprintf(", is_string($router) ? %s : '')
sprintf(", is_string($router) ? $router : '');

$cacheStats = %s->getCache()->getStats();
echo sprintf(", is_string($router) ? %s : '')快取統計: hits={(string)cacheStats['hits']}, misses={(string)cacheStats['misses']}, size={(string)cacheStats['size']}
", is_string($router) ? $router : '');

echo "快取有效性: sprintf(" . (%s->getCache()->isValid() ? 'VALID' : 'INVALID') . sprintf(", is_string($router) ? %s : '')
", is_string($router) ? $router : '');

// 測試過期邏輯
echo "
5. 測試快取過期
";
echo "===============
sprintf(sprintf(";

%s = new MemoryRouteCache();
%s->setTtl(1); // 1 秒過期

echo ", is_string($shortTtlCache) ? $shortTtlCache : ''), is_string($shortTtlCache) ? $shortTtlCache : '')儲存到短期快取: sprintf(sprintf(" . (%s->store(%s) ? 'SUCCESS' : 'FAILED') . ", is_string($shortTtlCache) ? $shortTtlCache : ''), is_string($testRoutes) ? $testRoutes : '')
";
echo "立即檢查有效性: sprintf(" . (%s->isValid() ? 'VALID' : 'INVALID') . sprintf(", is_string($shortTtlCache) ? %s : '')
", is_string($shortTtlCache) ? $shortTtlCache : '');

echo "等待 2 秒...
";
sleep(2);

echo "2 秒後檢查有效性: sprintf(" . (%s->isValid() ? 'VALID' : 'INVALID') . sprintf(", is_string($shortTtlCache) ? %s : '')
", is_string($shortTtlCache) ? $shortTtlCache : '');
echo "嘗試載入過期快取: sprintf(" . (%s->load() === null ? 'NULL (正確)' : 'NOT NULL (錯誤)') . sprintf(", is_string($shortTtlCache) ? %s : '')
", is_string($shortTtlCache) ? $shortTtlCache : '');

echo "
=== 所有測試完成 ===
";
