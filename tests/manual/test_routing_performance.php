<?php

declare(strict_types=1);

/**
 * 路由系統效能基準測試.
 *
 * 測試項目：
 * 1. 路由註冊效能
 * 2. 路由匹配效能
 * 3. 快取效能
 * 4. 記憶體使用量
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Routing\Cache\FileRouteCache;
use App\Infrastructure\Routing\Cache\MemoryRouteCache;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;
use GuzzleHttp\Psr7\ServerRequest;

echo "=== 路由系統效能基準測試 ===\n\n";

// 測試配置
$routeCount = 1000;
$matchTests = 10000;

// 1. 路由註冊效能測試
echo "測試 1: 路由註冊效能 ({$routeCount} 條路由)\n";
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

$router = new Router();
for ($i = 1; $i <= $routeCount; $i++) {
    $route = new Route(
        ['GET'],
        "/test/route/{$i}",
        'TestController@index', // 使用字串格式避免序列化問題
    );
    $route->setName("test_route_{$i}");
    $router->getRoutes()->add($route);
}

$registrationTime = microtime(true) - $startTime;
$registrationMemory = memory_get_usage(true) - $startMemory;

echo sprintf(
    "✅ 註冊 %d 條路由耗時: %.4f 秒 (平均 %.6f 秒/路由)\n",
    $routeCount,
    $registrationTime,
    $registrationTime / $routeCount,
);
echo sprintf(
    "✅ 記憶體使用: %.2f MB (平均 %.2f KB/路由)\n",
    $registrationMemory / 1024 / 1024,
    ($registrationMemory / 1024) / $routeCount,
);
echo "\n";

// 2. 路由匹配效能測試
echo "測試 2: 路由匹配效能 ({$matchTests} 次匹配)\n";

$testPaths = [];
for ($i = 0; $i < 100; $i++) {
    $testPaths[] = '/test/route/' . rand(1, $routeCount);
}

$startTime = microtime(true);
for ($i = 0; $i < $matchTests; $i++) {
    $path = $testPaths[$i % count($testPaths)];
    $request = new ServerRequest('GET', $path);
    $matchResult = $router->dispatch($request);
}
$matchingTime = microtime(true) - $startTime;

echo sprintf(
    "✅ %d 次路由匹配耗時: %.4f 秒 (平均 %.6f 秒/匹配)\n",
    $matchTests,
    $matchingTime,
    $matchingTime / $matchTests,
);
echo sprintf("✅ 匹配速度: %.0f 匹配/秒\n", $matchTests / $matchingTime);
echo "\n";

// 3. 快取效能測試
echo "測試 3: 快取效能測試\n";

// 記憶體快取測試
try {
    $memoryCache = new MemoryRouteCache();

    // 建立測試路由集合
    $cacheTestCollection = new RouteCollection();
    for ($i = 1; $i <= 100; $i++) {
        $route = new Route(
            ['GET'],
            "/cache/test/{$i}",
            'TestController@cacheTest', // 使用字串格式避免序列化問題
            "cache_route_{$i}",
        );
        $cacheTestCollection->add($route);
    }

    $startTime = microtime(true);
    $memoryCache->store($cacheTestCollection);
    $cached = $memoryCache->load();
    $memoryCacheTime = microtime(true) - $startTime;

    echo sprintf("✅ 記憶體快取 (100 條路由): %.6f 秒\n", $memoryCacheTime);
} catch (Exception $e) {
    echo '⚠️ 記憶體快取測試失敗: ' . $e->getMessage() . "\n";
    $memoryCacheTime = 1.0; // 預設值避免除零錯誤
}

// 檔案快取測試
$tempDir = sys_get_temp_dir() . '/alleynote_cache_test';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0o755, true);
}

try {
    $fileCache = new FileRouteCache($tempDir);
    $startTime = microtime(true);
    $fileCache->store($cacheTestCollection);
    $cached = $fileCache->load();
    $fileCacheTime = microtime(true) - $startTime;

    echo sprintf("✅ 檔案快取 (100 條路由): %.6f 秒\n", $fileCacheTime);
    echo sprintf("✅ 記憶體快取比檔案快取快 %.1f 倍\n", $fileCacheTime / $memoryCacheTime);
} catch (Exception $e) {
    echo '⚠️ 檔案快取測試失敗: ' . $e->getMessage() . "\n";
}

echo "\n";

// 4. 記憶體使用量分析
echo "測試 4: 記憶體使用量分析\n";
$finalMemory = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

echo sprintf("✅ 目前記憶體使用: %.2f MB\n", $finalMemory / 1024 / 1024);
echo sprintf("✅ 峰值記憶體使用: %.2f MB\n", $peakMemory / 1024 / 1024);
echo sprintf("✅ 每條路由平均記憶體: %.2f KB\n", ($finalMemory / 1024) / $routeCount);
echo "\n";

// 5. 整體效能摘要
echo "測試 5: 效能摘要\n";
echo '✅ 路由註冊速度: ' . ($registrationTime < 0.1 ? '優秀' : ($registrationTime < 0.5 ? '良好' : '需優化')) . "\n";
echo '✅ 路由匹配速度: ' . (($matchingTime / $matchTests) < 0.001 ? '優秀' : (($matchingTime / $matchTests) < 0.005 ? '良好' : '需優化')) . "\n";
echo '✅ 記憶體效率: ' . (($finalMemory / 1024 / 1024) < 10 ? '優秀' : (($finalMemory / 1024 / 1024) < 50 ? '良好' : '需優化')) . "\n";
echo '✅ 快取效能: ' . ($memoryCacheTime < 0.01 ? '優秀' : ($memoryCacheTime < 0.1 ? '良好' : '需優化')) . "\n";

// 6. 路由統計資訊
echo "\n測試 6: 路由統計資訊\n";
$routes = $router->getRoutes();
echo sprintf("✅ 總路由數量: %d\n", $routes->count());
echo sprintf("✅ GET 方法路由: %d\n", count($routes->getByMethod('GET')));
echo sprintf("✅ 命名路由數量: %d\n", $routeCount);
echo "\n";

// 清理測試檔案
if (isset($tempDir) && is_dir($tempDir)) {
    $files = glob("$tempDir/*");
    if ($files) {
        array_map('unlink', $files);
    }
    rmdir($tempDir);
}

echo "=== 效能測試完成 ===\n";
