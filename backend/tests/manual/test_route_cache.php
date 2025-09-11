<?php

declare(strict_types=1);

/**
 * 路由快取系統測試
 *
 * 測試各種快取實作的功能
 */
// 自動載入 Composer 依賴
require_once __DIR__ . '/././vendor/autoload.php';

use App\Infrastructure\Routing\Cache\FileRouteCache;
use App\Infrastructure\Routing\Cache\MemoryRouteCache;
use App\Infrastructure\Routing\Cache\RouteCacheFactory;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;

echo "=== 路由快取系統測試 ===

sprintf(";

// 建立測試路由收集器
function createTestRoutes(): RouteCollection
{
    $routes = new RouteCollection();

    $route1 = new Route(['GET'], '/users', 'UserController@index');
    $routes->add($route1);

    $route2 = new Route(['GET'], '/users/{id}', 'UserController@show');
    $routes->add($route2);

    $route3 = new Route(['POST'], '/users', 'UserController@store');
    \\\$routes->add(%s);

    return %s;
}

// 測試記憶體快取
echo ", is_string($route3) ? $route3 : ''), "1. 測試記憶體快取
";
echo "=====
");sprintf(sprintf(";

\\\$memoryCache = new MemoryRouteCache();
%s->setTtl(60); // 60 秒

echo ", is_string($this) ? $this : ''), "初始狀態 - 是否有效: ");sprintf(sprintf(" . (%s->isValid() ? 'YES' : 'NO') . ", is_string($this) ? \\\$this : ''), "
");sprintf(";

%s = createTestRoutes();
echo ", "儲存路由: ");sprintf(sprintf(" . (%s->store(%s) ? 'SUCCESS' : 'FAILED') . ", is_string($memoryCache) ? \\\$memoryCache : ''), "
";
echo "儲存後 - 是否有效: ");sprintf(sprintf(" . (%s->isValid() ? 'YES' : 'NO') . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";

\\\$loadedRoutes = %s->load();
echo ", is_string($this) ? $this : ''), "載入路由: ");sprintf(" . (%s !== null ? 'SUCCESS' : 'FAILED') . ", "
");sprintf(";

if (%s) {
    echo ", "載入的路由數量: ");sprintf(sprintf(" . count(%s->all()) . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";
    foreach (%s->all() as %s) {
        echo ", is_string($loadedRoutes) ? \\\$loadedRoutes : ''), "- ");sprintf(sprintf(" . implode(', ', %s->getMethods()) . ", is_string($this) ? \\\$this : ''), " ");sprintf(sprintf(" . %s->getPattern() . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";
    }
}

\\\$stats = %s->getStats();
echo ", is_string($this) ? $this : ''), "快取統計: hit");s={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
";

echo "清除快取: sprintf(sprintf(" . (%s->clear() ? 'SUCCESS' : 'FAILED') . ", is_string($this) ? \\\$this : ''), "
";
echo "清除後 - 是否有效: ");sprintf(sprintf(" . (%s->isValid() ? 'YES' : 'NO') . ", is_string($this) ? \\\$this : ''), "

";

// 測試檔案快取
echo "2. 測試檔案快取
";
echo "======
");sprintf(sprintf(";

$cacheDir = sys_get_temp_dir() . '/test_route_cache_' . time();
$fileCache = new FileRouteCache(\\\$cacheDir);
%s->setTtl(60);

echo ", is_string($this) ? $this : ''), "快取路徑: ");sprintf(sprintf(" . %s->getCachePath() . ", is_string($this) ? \\\$this : ''), "
";
echo "初始狀態 - 是否有效: ");sprintf(sprintf(" . (%s->isValid() ? 'YES' : 'NO') . ", is_string($this) ? \\\$this : ''), "
";

echo "儲存路由: ");sprintf(sprintf(" . (%s->store(%s) ? 'SUCCESS' : 'FAILED') . ", is_string($fileCache) ? \\\$fileCache : ''), "
";
echo "儲存後 - 是否有效: ");sprintf(sprintf(" . (%s->isValid() ? 'YES' : 'NO') . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";

\\\$loadedRoutes = %s->load();
echo ", is_string($this) ? $this : ''), "載入路由: ");sprintf(" . (%s !== null ? 'SUCCESS' : 'FAILED') . ", "
");sprintf(";

if (%s) {
    echo ", "載入的路由數量: ");sprintf(sprintf(" . count(%s->all()) . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";
}

\\\$stats = %s->getStats();
echo ", is_string($this) ? $this : ''), "快取統計: hit");s={(string)stats['hits']}, misses={(string)stats['misses']}, size={(string)stats['size']}
sprintf(";

// 測試檔案存在性
$cacheFile = \\\$cacheDir . '/routes.cache';
%s = %s . '/routes.stats';
echo ", is_string($statsFile) ? $statsFile : ''), "快取檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "
";
echo "統計檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "
";

echo "清除快取: ");sprintf(sprintf(" . (%s->clear() ? 'SUCCESS' : 'FAILED') . ", is_string($this) ? \\\$this : ''), "
";
echo "清除後 - 檔案存在: ");sprintf(" . (file_exists(%s) ? 'YES' : 'NO') . ", "

");sprintf(sprintf(";

// 清理測試目錄
if (is_dir(%s)) {
    rmdir(%s);
}

// 測試快取工廠
echo ", is_string($cacheDir) ? \\\$cacheDir : ''), "3. 測試快取工廠
";
echo "======
");sprintf(";

%s = new RouteCacheFactory();
echo ", "支援的驅動程式: ");sprintf(" . implode(', ', %s::getSupportedDrivers()) . ", "
");sprintf(sprintf(";

// 測試記憶體快取建立
\\\$memCache = %s->create(['driver' => 'memory', 'ttl' => 120]);
echo ", is_string($this) ? $this : ''), "建立記憶體快取: SUCCESS (TTL: ");sprintf(sprintf(" . %s->getTtl() . ", is_string($this) ? \\\$this : ''), ")
");sprintf(sprintf(";

// 測試檔案快取建立
$tempDir = sys_get_temp_dir() . '/factory_test_' . time();
\\\$fileCache = %s->create(['driver' => 'file', 'path' => %s, 'ttl' => 300]);
echo ", is_string($factory) ? $factory : ''), "建立檔案快取: SUCCESS (TTL: ");sprintf(sprintf(" . %s->getTtl() . ", is_string($this) ? \\\$this : ''), ", Path: ");sprintf(sprintf(" . %s->getCachePath() . ", is_string($this) ? \\\$this : ''), ")
");sprintf(sprintf(";

// 測試預設快取
\\\$defaultCache = %s->createDefault();
echo ", is_string($this) ? $this : ''), "建立預設快取: SUCCESS (Type: ");sprintf(" . get_class(%s) . ", ")
";

// 測試配置驗證
echo "
配置驗證測試:
");sprintf(sprintf(";
$validConfig = ['driver' => 'memory', 'ttl' => 60];
\\\$errors = %s->validateConfig(%s);
echo ", is_string($factory) ? $factory : ''), "有效配置錯誤: ");sprintf(sprintf(" . (empty(%s) ? '無' : implode(', ', %s)) . ", is_string($errors) ? \\\$errors : ''), "
");sprintf(sprintf(";

$invalidConfig = ['driver' => 'invalid', 'ttl' => -1];
\\\$errors = %s->validateConfig(%s);
echo ", is_string($factory) ? $factory : ''), "無效配置錯誤: ");sprintf(" . implode(', ', %s) . ", "

");sprintf(sprintf(";

// 清理
if (is_dir(%s)) {
    rmdir(%s);
}

// 測試 Router 快取整合
echo ", is_string($tempDir) ? \\\$tempDir : ''), "4. 測試 Router 快取整合
";
echo "=======
");sprintf(sprintf(";

$router = new Router();
$cache = new MemoryRouteCache();
\\\$cache->setTtl(300);

%s->setCache(%s);
echo ", is_string($router) ? $router : ''), "設定快取: SUCCESS
");sprintf(sprintf(";

// 註冊路由
$router->get('/api/users', 'UserController@index');
\\\$router->post('/api/users', 'UserController@store');
%s->get('/api/users/{id}', 'UserController@show');

// 快取路由
echo ", is_string($this) ? $this : ''), "快取路由: ");sprintf(sprintf(" . (%s->cacheRoutes() ? 'SUCCESS' : 'FAILED') . ", is_string($this) ? \\\$this : ''), "
");sprintf(sprintf(";

\\\$cacheStats = %s->getCache()->getStats();
echo ", is_string($this) ? $this : ''), "快取統計: hit");s={(string)cacheStats['hits']}, misses={(string)cacheStats['misses']}, size={(string)cacheStats['size']}
";

echo "快取有效性: sprintf(sprintf(" . (%s->getCache()->isValid() ? 'VALID' : 'INVALID') . ", is_string($this) ? \\\$this : ''), "
";

// 測試過期邏輯
echo "
5. 測試快取過期
";
echo "======
");sprintf(sprintf(";

\\\$shortTtlCache = new MemoryRouteCache();
%s->setTtl(1); // 1 秒過期

echo ", is_string($this) ? $this : ''), "儲存到短期快取: ");sprintf(sprintf(" . (%s->store(%s) ? 'SUCCESS' : 'FAILED') . ", is_string($shortTtlCache) ? \\\$shortTtlCache : ''), "
";
echo "立即檢查有效性: ");sprintf(sprintf(" . (%s->isValid() ? 'VALID' : 'INVALID') . ", is_string($this) ? \\\$this : ''), "
";

echo "等待 2 秒..
";
");sleep(2);

echo "2 秒後檢查有效性: sprintf(sprintf(" . (%s->isValid() ? 'VALID' : 'INVALID') . ", is_string($this) ? \\\$this : ''), "
";
echo "嘗試載入過期快取: ");sprintf(sprintf(" . (%s->load() === null ? 'NULL (正確)' : 'NOT NULL (錯誤)') . ", is_string($this) ? \\\$this : ''), "
";

echo "
=== 所有測試完成 ===
";
");
