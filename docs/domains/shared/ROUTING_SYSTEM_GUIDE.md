# AlleyNote 路由系統使用指南

## 概述

AlleyNote 路由系統是一個高效能、模組化的 HTTP 路由解決方案，專為 AlleyNote 公布欄系統設計。系統基於 DDD (Domain-Driven Design) 架構，提供完整的路由註冊、解析、快取和中間件支援功能。

## 核心特性

### 🚀 高效能路由匹配
- 記憶體快取：72,930 匹配/秒
- 路由註冊：1000 條路由僅需 0.0043 秒
- 記憶體效率：4.1KB/路由

### 🛡️ 完整中間件系統
- PSR-15 相容中間件介面
- 路由層級中間件支援
- 中間件鏈式處理

### 📦 彈性路由配置
- 多檔案路由配置支援
- 路由群組和前綴
- 自動路由驗證

### ⚡ 智慧快取機制
- 檔案快取和記憶體快取
- 自動快取失效
- 快取統計和監控

## 快速開始

### 基本路由註冊

```php
use App\Infrastructure\Routing\Core\Router;

// 建立路由器實例
$router = new Router();

// 註冊基本路由
$router->get('/api/posts', 'PostController@index');
$router->post('/api/posts', 'PostController@store');
$router->get('/api/posts/{id}', 'PostController@show');
$router->put('/api/posts/{id}', 'PostController@update');
$router->delete('/api/posts/{id}', 'PostController@destroy');
```

### 路由配置檔案

路由配置檔案位於 `config/routes/` 目錄：

```
config/routes/
├── api.php          # API 路由
├── web.php          # Web 路由  
├── auth.php         # 認證路由
└── admin.php        # 管理員路由
```

#### API 路由示例 (config/routes/api.php)

```php
<?php

return [
    'prefix' => '/api/v1',
    'middleware' => ['cors', 'json-response'],
    'routes' => [
        // 文章管理
        [
            'method' => 'GET',
            'path' => '/posts',
            'handler' => 'App\Application\Controllers\Api\V1\PostController@index',
            'name' => 'api.posts.index'
        ],
        [
            'method' => 'POST', 
            'path' => '/posts',
            'handler' => 'App\Application\Controllers\Api\V1\PostController@store',
            'middleware' => ['auth', 'csrf'],
            'name' => 'api.posts.store'
        ],
        [
            'method' => 'GET',
            'path' => '/posts/{id}',
            'handler' => 'App\Application\Controllers\Api\V1\PostController@show',
            'name' => 'api.posts.show',
            'constraints' => ['id' => '\d+']
        ]
    ]
];
```

### 中間件使用

#### 建立自訂中間件

```php
<?php

namespace App\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        // 驗證邏輯
        $token = $request->getHeaderLine('Authorization');
        
        if (empty($token)) {
            return $this->unauthorizedResponse();
        }
        
        // 驗證通過，傳遞給下一個中間件
        return $next($request);
    }
    
    private function unauthorizedResponse(): ResponseInterface
    {
        // 返回 401 回應
    }
}
```

#### 註冊中間件

```php
use App\Infrastructure\Routing\Middleware\MiddlewareManager;

$middlewareManager = new MiddlewareManager();

// 註冊全域中間件
$middlewareManager->addGlobal('cors', new CorsMiddleware());

// 註冊路由中間件
$middlewareManager->add('auth', new AuthMiddleware());
$middlewareManager->add('csrf', new CsrfMiddleware());
```

### 路由參數處理

#### 基本參數

```php
// 路由定義
$router->get('/api/posts/{id}', 'PostController@show');

// 控制器中取得參數
public function show(ServerRequestInterface $request): ResponseInterface
{
    $id = $request->getAttribute('id');
    // 處理邏輯...
}
```

#### 參數約束

```php
// 在路由配置中設定約束
[
    'path' => '/posts/{id}',
    'handler' => 'PostController@show',
    'constraints' => ['id' => '\d+']  // 只允許數字
]
```

#### 可選參數

```php
// 可選參數使用 ? 標記
$router->get('/api/posts/{category?}', 'PostController@byCategory');
```

### 路由快取

#### 啟用快取

```php
use App\Infrastructure\Routing\Cache\RouteCacheFactory;

// 建立快取實例
$cache = RouteCacheFactory::create('file'); // 或 'memory'

// 設定快取給路由器
$router->setCache($cache);
```

#### 快取統計

```php
$stats = $cache->getStatistics();
echo "快取命中率: " . $stats['hit_rate'] . "%\n";
echo "總查詢數: " . $stats['total_queries'] . "\n";
echo "快取大小: " . $stats['cache_size'] . " 個項目\n";
```

#### 清除快取

```php
// 清除所有快取
$cache->clear();

// 清除特定路由快取
$cache->forget('/api/posts');
```

## 進階功能

### 路由群組

```php
// 群組路由與共用中間件
$router->group([
    'prefix' => '/admin',
    'middleware' => ['auth', 'admin'],
    'namespace' => 'App\Admin\Controllers'
], function ($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/users', 'UserController@index');
    $router->get('/settings', 'SettingsController@index');
});
```

### 命名路由

```php
// 註冊命名路由
$router->get('/api/posts', 'PostController@index')->name('posts.index');

// 產生 URL
$url = $router->route('posts.index'); // /api/posts
```

### 路由模型綁定

```php
// 自動注入模型
$router->get('/api/posts/{post}', 'PostController@show')
       ->bind('post', Post::class);
```

### 子域名路由

```php
// 子域名支援
$router->domain('api.alleynote.com')->group(function ($router) {
    $router->get('/posts', 'ApiController@posts');
});
```

## 效能最佳化

### 快取策略

1. **開發環境**: 使用記憶體快取以加快開發速度
2. **生產環境**: 使用檔案快取以節省記憶體

```php
$cacheType = $_ENV['APP_ENV'] === 'production' ? 'file' : 'memory';
$cache = RouteCacheFactory::create($cacheType);
```

### 路由預編譯

```php
// 產生路由快取檔案
php scripts/route-cache.php
```

### 中間件最佳化

```php
// 條件式中間件載入
if ($request->getMethod() === 'POST') {
    $middlewareManager->add('csrf', new CsrfMiddleware());
}
```

## 錯誤處理

### 自訂錯誤處理器

```php
$router->setErrorHandler(function ($exception) {
    if ($exception instanceof RouteNotFoundException) {
        return new JsonResponse(['error' => 'Route not found'], 404);
    }
    
    return new JsonResponse(['error' => 'Server error'], 500);
});
```

### 路由配置驗證

```php
use App\Infrastructure\Routing\RouteValidator;

$validator = new RouteValidator();
$result = $validator->validateConfiguration($routeConfig);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "錯誤: {$error}\n";
    }
}
```

## 測試

### 路由測試

```php
use PHPUnit\Framework\TestCase;
use App\Infrastructure\Routing\Core\Router;

class RouteTest extends TestCase
{
    public function testRouteMatching()
    {
        $router = new Router();
        $router->get('/test/{id}', 'TestController@show');
        
        $result = $router->match('GET', '/test/123');
        
        $this->assertTrue($result->isFound());
        $this->assertEquals('TestController@show', $result->getHandler());
        $this->assertEquals(['id' => '123'], $result->getParameters());
    }
}
```

### 中間件測試

```php
public function testMiddlewareExecution()
{
    $middleware = new AuthMiddleware();
    $request = $this->createRequest();
    
    $response = $middleware->process($request, function ($req) {
        return new Response(200);
    });
    
    $this->assertEquals(200, $response->getStatusCode());
}
```

## 故障排除

### 常見問題

#### 1. 路由無法匹配

**問題**: 路由定義正確但無法匹配請求

**解決方案**:
```php
// 檢查路由快取
$cache->clear();

// 確認路由格式
$router->get('/api/test', function() { return 'test'; });

// 檢查路由註冊
$routes = $router->getRoutes();
var_dump($routes);
```

#### 2. 中間件未執行

**問題**: 中間件沒有被調用

**解決方案**:
```php
// 確認中間件註冊
$middlewares = $middlewareManager->getRegistered();

// 檢查中間件順序
$stack = $router->getMiddlewareStack($route);
```

#### 3. 快取問題

**問題**: 路由更改後沒有生效

**解決方案**:
```php
// 清除路由快取
$cache->clear();

// 重新載入路由配置
$routeLoader->reload();
```

### 除錯工具

```php
// 啟用路由除錯模式
$router->setDebugMode(true);

// 取得路由統計
$stats = $router->getStatistics();
print_r($stats);

// 檢查路由定義
$router->dumpRoutes();
```

## 配置參考

### 路由器設定

```php
$config = [
    'cache_enabled' => true,
    'cache_type' => 'file',
    'cache_ttl' => 3600,
    'debug_mode' => false,
    'strict_mode' => true,
    'case_sensitive' => false
];

$router = new Router($config);
```

### 中間件設定

```php
$middlewareConfig = [
    'global' => ['cors', 'security-headers'],
    'api' => ['auth', 'rate-limit'],
    'admin' => ['auth', 'admin-check', 'csrf']
];
```

## 安全考量

### CSRF 保護

```php
$router->post('/api/posts', 'PostController@store')
       ->middleware('csrf');
```

### 速率限制

```php
$router->group(['middleware' => 'rate-limit:100,60'], function ($router) {
    $router->post('/api/posts', 'PostController@store');
});
```

### 輸入驗證

```php
$router->post('/api/posts', 'PostController@store')
       ->middleware('validate:CreatePostRequest');
```

## 版本資訊

- **目前版本**: v1.0.0
- **PHP 需求**: >= 8.4
- **依賴套件**: PSR-7, PSR-15, PHP-DI

## 相關文件

- [API 參考文件](./ROUTING_SYSTEM_API_REFERENCE.md)
- [架構設計文件](./ROUTING_SYSTEM_ARCHITECTURE.md) 
- [效能指南](./ROUTING_SYSTEM_PERFORMANCE_GUIDE.md)
- [開發者指南](./DEVELOPER_GUIDE.md)