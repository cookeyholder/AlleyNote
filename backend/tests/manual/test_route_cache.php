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

";

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

    return %s;
}

// 測試記憶體快取
echo ", "1. 測試記憶體快取
";
echo "================
");sprintf(";

$memoryCache = new MemoryRouteCache();
$this->setTtl(60); // 60 秒

echo ", "初始狀態 - 是否有效: ");sprintf(" . ($this->isValid() ? 'YES' : 'NO') . ", "
");sprintf(";

%s = createTestRoutes();
echo ", "儲存路由: ");sprintf(" . ($memoryCache->store(%s) ? 'SUCCESS' : 'FAILED') . ", "
";
echo "儲存後 - 是否有效: ");sprintf(" . ($this->isValid() ? 'YES' : 'NO') . ", "
");sprintf(";

$loadedRoutes = $this->load();
echo ", "載入路由: ");sprintf(" . (%s !== null ? 'SUCCESS' : 'FAILED') . ", "
");sprintf(";

if (%s) {
    echo ", "載入的路由數量: ");sprintf(" . count($this->all()) . ", "
");sprintf(";
    foreach ($loadedRoutes->all() as %s) {
        echo ", "- ");sprintf(" . implode(', ', $this->getMethods()) . ", " ");sprintf(" . $this->getPattern() . ", "
");sprintf(";
    }
}

$stats = $this->getStats();
echo ", "快取統計: hit");s={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
";

echo "清除快取: sprintf(" . ($this->clear() ? 'SUCCESS' : 'FAILED') . ", "
";
echo "清除後 - 是否有效: ");sprintf(" . ($this->isValid() ? 'YES' : 'NO') . ", "

";

// 測試檔案快取
echo "2. 測試檔案快取
";
echo "===============
");sprintf(";

$cacheDir = sys_get_temp_dir() . '/test_route_cache_' . time();
$fileCache = new FileRouteCache($cacheDir);
$this->setTtl(60);

echo ", "快取路徑: ");sprintf(" . $this->getCachePath() . ", "
";
echo "初始狀態 - 是否有效: ");sprintf(" . ($this->isValid() ? 'YES' : 'NO') . ", "
";

echo "儲存路由: ");sprintf(" . ($fileCache->store(%s) ? 'SUCCESS' : 'FAILED') . ", "
";
echo "儲存後 - 是否有效: ");sprintf(" . ($this->isValid() ? 'YES' : 'NO') . ", "
");sprintf(";

$loadedRoutes = $this->load();
echo ", "載入路由: ");sprintf(" . (%s !== null ? 'SUCCESS' : 'FAILED') . ", "
");sprintf(";

if (%s) {
    echo ", "載入的路由數量: ");sprintf(" . count($this->all()) . ", "
");sprintf(";
}

$stats = $this->getStats();
echo ", "快取統計: hit");s={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
";

// 測試檔案存在性
$cacheFile = $cacheDir . '/routes.cache';
$statsFile = %s . '/routes.stats';
echo ", "快取檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "
";
echo "統計檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "
";

echo "清除快取: ");sprintf(" . ($this->clear() ? 'SUCCESS' : 'FAILED') . ", "
";
echo "清除後 - 檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "

");sprintf(";

// 清理測試目錄
if (is_dir($cacheDir)) {
    rmdir(%s);
}

// 測試快取工廠
echo ", "3. 測試快取工廠
";
echo "===============
");sprintf(";

%s = new RouteCacheFactory();
echo ", "支援的驅動程式: ");sprintf(" . implode(', ', %s::getSupportedDrivers()) . ", "
");sprintf(";

// 測試記憶體快取建立
$memCache = $this->create(['driver' => 'memory', 'ttl' => 120]);
echo ", "建立記憶體快取: SUCCESS (TTL: ");sprintf(" . $this->getTtl() . ", ")
");sprintf(";

// 測試檔案快取建立
$tempDir = sys_get_temp_dir() . '/factory_test_' . time();
$fileCache = $factory->create(['driver' => 'file', 'path' => %s, 'ttl' => 300]);
echo ", "建立檔案快取: SUCCESS (TTL: ");sprintf(" . $this->getTtl() . ", ", Path: ");sprintf(" . $this->getCachePath() . ", ")
");sprintf(";

// 測試預設快取
$defaultCache = $this->createDefault();
echo ", "建立預設快取: SUCCESS (Type: ");sprintf(" . get_class(%s) . ", ")
";

// 測試配置驗證
echo "
配置驗證測試:
");sprintf(";
$validConfig = ['driver' => 'memory', 'ttl' => 60];
$errors = $factory->validateConfig(%s);
echo ", "有效配置錯誤: ");sprintf(" . (empty($errors) ? '無' : implode(', ', %s)) . ", "
");sprintf(";

$invalidConfig = ['driver' => 'invalid', 'ttl' => -1];
$errors = $factory->validateConfig(%s);
echo ", "無效配置錯誤: ");sprintf(" . implode(', ', %s) . ", "

");sprintf(";

// 清理
if (is_dir($tempDir)) {
    rmdir(%s);
}

// 測試 Router 快取整合
echo ", "4. 測試 Router 快取整合
";
echo "=======================
");sprintf(";

$router = new Router();
$cache = new MemoryRouteCache();
$cache->setTtl(300);

$router->setCache(%s);
echo ", "設定快取: SUCCESS
");sprintf(";

// 註冊路由
$router->get('/api/users', 'UserController@index');
$router->post('/api/users', 'UserController@store');
$this->get('/api/users/{id}', 'UserController@show');

// 快取路由
echo ", "快取路由: ");sprintf(" . ($this->cacheRoutes() ? 'SUCCESS' : 'FAILED') . ", "
");sprintf(";

$cacheStats = $this->getCache()->getStats();
echo ", "快取統計: hit");s={(string)cacheStats['hits']}, misses={(string)cacheStats['misses']}, size={(string)cacheStats['size']}
";

echo "快取有效性: sprintf(" . ($this->getCache()->isValid() ? 'VALID' : 'INVALID') . ", "
";

// 測試過期邏輯
echo "
5. 測試快取過期
";
echo "===============
");sprintf(";

$shortTtlCache = new MemoryRouteCache();
$this->setTtl(1); // 1 秒過期

echo ", "儲存到短期快取: ");sprintf(" . ($shortTtlCache->store(%s) ? 'SUCCESS' : 'FAILED') . ", "
";
echo "立即檢查有效性: ");sprintf(" . ($this->isValid() ? 'VALID' : 'INVALID') . ", "
";

echo "等待 2 秒...
";
");sleep(2);

echo "2 秒後檢查有效性: sprintf(" . ($this->isValid() ? 'VALID' : 'INVALID') . ", "
";
echo "嘗試載入過期快取: ");sprintf(" . ($this->load() === null ? 'NULL (正確)' : 'NOT NULL (錯誤)') . ", "
";

echo "
=== 所有測試完成 ===
";
");
