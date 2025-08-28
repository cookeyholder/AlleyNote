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

echo "=== 路由快取系統測試 ===\n\n";

// 建立測試路由收集器
function createTestRoutes(): RouteCollection
{
    $routes = new RouteCollection();

    $route1 = new Route(['GET'], '/users', 'UserController@index');
    $routes->add($route1);

    $route2 = new Route(['GET'], '/users/{id}', 'UserController@show');
    $routes->add($route2);

    $route3 = new Route(['POST'], '/users', 'UserController@store');
    $routes->add($route3);

    return $routes;
}

// 測試記憶體快取
echo "1. 測試記憶體快取\n";
echo "================\n";

$memoryCache = new MemoryRouteCache();
$memoryCache->setTtl(60); // 60 秒

echo "初始狀態 - 是否有效: " . ($memoryCache->isValid() ? 'YES' : 'NO') . "\n";

$testRoutes = createTestRoutes();
echo "儲存路由: " . ($memoryCache->store($testRoutes) ? 'SUCCESS' : 'FAILED') . "\n";
echo "儲存後 - 是否有效: " . ($memoryCache->isValid() ? 'YES' : 'NO') . "\n";

$loadedRoutes = $memoryCache->load();
echo "載入路由: " . ($loadedRoutes !== null ? 'SUCCESS' : 'FAILED') . "\n";

if ($loadedRoutes) {
    echo "載入的路由數量: " . count($loadedRoutes->all()) . "\n";
    foreach ($loadedRoutes->all() as $route) {
        echo "- " . implode(', ', $route->getMethods()) . " " . $route->getPattern() . "\n";
    }
}

$stats = $memoryCache->getStats();
echo "快取統計: hits={$stats['hits']}, misses={$stats['misses']}, size={$stats['size']}\n";

echo "清除快取: " . ($memoryCache->clear() ? 'SUCCESS' : 'FAILED') . "\n";
echo "清除後 - 是否有效: " . ($memoryCache->isValid() ? 'YES' : 'NO') . "\n\n";

// 測試檔案快取
echo "2. 測試檔案快取\n";
echo "===============\n";

$cacheDir = sys_get_temp_dir() . '/test_route_cache_' . time();
$fileCache = new FileRouteCache($cacheDir);
$fileCache->setTtl(60);

echo "快取路徑: " . $fileCache->getCachePath() . "\n";
echo "初始狀態 - 是否有效: " . ($fileCache->isValid() ? 'YES' : 'NO') . "\n";

echo "儲存路由: " . ($fileCache->store($testRoutes) ? 'SUCCESS' : 'FAILED') . "\n";
echo "儲存後 - 是否有效: " . ($fileCache->isValid() ? 'YES' : 'NO') . "\n";

$loadedRoutes = $fileCache->load();
echo "載入路由: " . ($loadedRoutes !== null ? 'SUCCESS' : 'FAILED') . "\n";

if ($loadedRoutes) {
    echo "載入的路由數量: " . count($loadedRoutes->all()) . "\n";
}

$stats = $fileCache->getStats();
echo "快取統計: hits={$stats['hits']}, misses={$stats['misses']}, size={$stats['size']}\n";

// 測試檔案存在性
$cacheFile = $cacheDir . '/routes.cache';
$statsFile = $cacheDir . '/routes.stats';
echo "快取檔案存在: " . (file_exists($cacheFile) ? 'YES' : 'NO') . "\n";
echo "統計檔案存在: " . (file_exists($statsFile) ? 'YES' : 'NO') . "\n";

echo "清除快取: " . ($fileCache->clear() ? 'SUCCESS' : 'FAILED') . "\n";
echo "清除後 - 檔案存在: " . (file_exists($cacheFile) ? 'YES' : 'NO') . "\n\n";

// 清理測試目錄
if (is_dir($cacheDir)) {
    rmdir($cacheDir);
}

// 測試快取工廠
echo "3. 測試快取工廠\n";
echo "===============\n";

$factory = new RouteCacheFactory();
echo "支援的驅動程式: " . implode(', ', $factory::getSupportedDrivers()) . "\n";

// 測試記憶體快取建立
$memCache = $factory->create(['driver' => 'memory', 'ttl' => 120]);
echo "建立記憶體快取: SUCCESS (TTL: " . $memCache->getTtl() . ")\n";

// 測試檔案快取建立
$tempDir = sys_get_temp_dir() . '/factory_test_' . time();
$fileCache = $factory->create(['driver' => 'file', 'path' => $tempDir, 'ttl' => 300]);
echo "建立檔案快取: SUCCESS (TTL: " . $fileCache->getTtl() . ", Path: " . $fileCache->getCachePath() . ")\n";

// 測試預設快取
$defaultCache = $factory->createDefault();
echo "建立預設快取: SUCCESS (Type: " . get_class($defaultCache) . ")\n";

// 測試配置驗證
echo "\n配置驗證測試:\n";
$validConfig = ['driver' => 'memory', 'ttl' => 60];
$errors = $factory->validateConfig($validConfig);
echo "有效配置錯誤: " . (empty($errors) ? '無' : implode(', ', $errors)) . "\n";

$invalidConfig = ['driver' => 'invalid', 'ttl' => -1];
$errors = $factory->validateConfig($invalidConfig);
echo "無效配置錯誤: " . implode(', ', $errors) . "\n\n";

// 清理
if (is_dir($tempDir)) {
    rmdir($tempDir);
}

// 測試 Router 快取整合
echo "4. 測試 Router 快取整合\n";
echo "=======================\n";

$router = new Router();
$cache = new MemoryRouteCache();
$cache->setTtl(300);

$router->setCache($cache);
echo "設定快取: SUCCESS\n";

// 註冊路由
$router->get('/api/users', 'UserController@index');
$router->post('/api/users', 'UserController@store');
$router->get('/api/users/{id}', 'UserController@show');

// 快取路由
echo "快取路由: " . ($router->cacheRoutes() ? 'SUCCESS' : 'FAILED') . "\n";

$cacheStats = $router->getCache()->getStats();
echo "快取統計: hits={$cacheStats['hits']}, misses={$cacheStats['misses']}, size={$cacheStats['size']}\n";

echo "快取有效性: " . ($router->getCache()->isValid() ? 'VALID' : 'INVALID') . "\n";

// 測試過期邏輯
echo "\n5. 測試快取過期\n";
echo "===============\n";

$shortTtlCache = new MemoryRouteCache();
$shortTtlCache->setTtl(1); // 1 秒過期

echo "儲存到短期快取: " . ($shortTtlCache->store($testRoutes) ? 'SUCCESS' : 'FAILED') . "\n";
echo "立即檢查有效性: " . ($shortTtlCache->isValid() ? 'VALID' : 'INVALID') . "\n";

echo "等待 2 秒...\n";
sleep(2);

echo "2 秒後檢查有效性: " . ($shortTtlCache->isValid() ? 'VALID' : 'INVALID') . "\n";
echo "嘗試載入過期快取: " . ($shortTtlCache->load() === null ? 'NULL (正確)' : 'NOT NULL (錯誤)') . "\n";

echo "\n=== 所有測試完成 ===\n";
