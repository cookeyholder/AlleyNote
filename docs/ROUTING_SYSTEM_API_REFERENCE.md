# AlleyNote 路由系統 API 參考文件

## 核心類別

### Router

路由器核心類別，負責路由註冊、匹配和分派。

#### 建構函式

```php
public function __construct(array $config = [])
```

**參數**:
- `$config` - 路由器配置陣列

**設定選項**:
```php
[
    'cache_enabled' => true,           // 是否啟用快取
    'cache_type' => 'file',           // 快取類型 ('file' 或 'memory')
    'cache_ttl' => 3600,              // 快取生存時間 (秒)
    'debug_mode' => false,            // 是否啟用除錯模式
    'strict_mode' => true,            // 是否啟用嚴格模式
    'case_sensitive' => false         // 是否區分大小寫
]
```

#### 方法

##### 路由註冊方法

```php
public function get(string $path, mixed $handler, array $options = []): Route
public function post(string $path, mixed $handler, array $options = []): Route
public function put(string $path, mixed $handler, array $options = []): Route
public function patch(string $path, mixed $handler, array $options = []): Route
public function delete(string $path, mixed $handler, array $options = []): Route
public function options(string $path, mixed $handler, array $options = []): Route
```

**參數**:
- `$path` - 路由路徑 (支援參數佔位符如 `{id}`)
- `$handler` - 路由處理器 (控制器方法、可調用物件或閉包)
- `$options` - 路由選項

**路由選項**:
```php
[
    'name' => 'route.name',                    // 路由名稱
    'middleware' => ['auth', 'csrf'],          // 中間件陣列
    'constraints' => ['id' => '\d+'],          // 參數約束
    'defaults' => ['category' => 'general'],   // 預設參數值
    'domain' => 'api.example.com',            // 子域名約束
    'https' => true                           // 強制 HTTPS
]
```

**回傳值**: `Route` 實例

**範例**:
```php
$router->get('/api/posts/{id}', 'PostController@show', [
    'name' => 'posts.show',
    'middleware' => ['auth'],
    'constraints' => ['id' => '\d+']
]);
```

##### 路由匹配方法

```php
public function match(string $method, string $path): RouteMatchResult
```

**參數**:
- `$method` - HTTP 方法
- `$path` - 請求路徑

**回傳值**: `RouteMatchResult` 實例

```php
$result = $router->match('GET', '/api/posts/123');
if ($result->isFound()) {
    $handler = $result->getHandler();
    $params = $result->getParameters();
}
```

##### 路由群組方法

```php
public function group(array $attributes, callable $callback): void
```

**參數**:
- `$attributes` - 群組屬性
- `$callback` - 群組路由定義回調

**群組屬性**:
```php
[
    'prefix' => '/api/v1',                     // 路由前綴
    'middleware' => ['cors', 'auth'],          // 群組中間件
    'namespace' => 'App\Api\Controllers',      // 控制器命名空間
    'name' => 'api.',                         // 路由名稱前綴
    'domain' => 'api.example.com'             // 子域名
]
```

**範例**:
```php
$router->group([
    'prefix' => '/admin',
    'middleware' => ['auth', 'admin'],
    'name' => 'admin.'
], function ($router) {
    $router->get('/dashboard', 'DashboardController@index')
           ->name('dashboard');  // 完整名稱: admin.dashboard
});
```

##### 快取控制方法

```php
public function setCache(RouteCacheInterface $cache): void
public function getCache(): ?RouteCacheInterface
public function clearCache(): void
```

##### 中間件管理方法

```php
public function setMiddlewareManager(MiddlewareManagerInterface $manager): void
public function getMiddlewareManager(): MiddlewareManagerInterface
public function middleware(string $name, MiddlewareInterface $middleware): void
```

##### 其他方法

```php
public function getRoutes(): array                    // 取得所有路由
public function getRoute(string $name): ?Route       // 根據名稱取得路由
public function hasRoute(string $name): bool         // 檢查路由是否存在
public function url(string $name, array $params = []): string  // 產生路由 URL
public function getStatistics(): array               // 取得路由統計資訊
```

---

### Route

單一路由實體，包含路由定義的所有資訊。

#### 屬性

```php
private string $method;              // HTTP 方法
private string $path;                // 路由路徑
private mixed $handler;              // 路由處理器
private array $middleware;           // 中間件陣列
private array $constraints;          // 參數約束
private array $defaults;             // 預設參數
private ?string $name;               // 路由名稱
private ?string $domain;             // 域名約束
```

#### 方法

```php
public function getMethod(): string
public function getPath(): string
public function getHandler(): mixed
public function getMiddleware(): array
public function getConstraints(): array
public function getDefaults(): array
public function getName(): ?string
public function getDomain(): ?string

public function setName(string $name): Route
public function setMiddleware(array $middleware): Route
public function addMiddleware(string $middleware): Route
public function setConstraints(array $constraints): Route
public function setDefaults(array $defaults): Route
public function setDomain(string $domain): Route

public function matches(string $method, string $path): bool
public function compile(): CompiledRoute
```

**範例**:
```php
$route = new Route('GET', '/posts/{id}', 'PostController@show');
$route->setName('posts.show')
      ->addMiddleware('auth')
      ->setConstraints(['id' => '\d+']);
```

---

### RouteMatchResult

路由匹配結果包含匹配狀態、處理器和參數資訊。

#### 方法

```php
public function isFound(): bool                    // 是否找到匹配路由
public function getHandler(): mixed                // 取得路由處理器
public function getParameters(): array             // 取得路由參數
public function getRoute(): ?Route                 // 取得匹配的路由實例
public function getMiddleware(): array             // 取得路由中間件
public function getStatusCode(): int               // 取得狀態碼 (200, 404, 405)
public function getAllowedMethods(): array         // 取得允許的 HTTP 方法 (405 錯誤時)
```

**範例**:
```php
$result = $router->match('GET', '/posts/123');

if ($result->isFound()) {
    $handler = $result->getHandler();        // 'PostController@show'
    $params = $result->getParameters();      // ['id' => '123']
    $middleware = $result->getMiddleware();  // ['auth', 'throttle']
} else {
    $statusCode = $result->getStatusCode();  // 404 或 405
    if ($statusCode === 405) {
        $allowed = $result->getAllowedMethods();  // ['POST', 'PUT']
    }
}
```

---

### RouteCollection

路由集合管理類別，負責儲存和組織路由。

#### 方法

```php
public function add(Route $route): void               // 新增路由
public function addGroup(array $routes): void        // 新增路由群組
public function get(string $name): ?Route            // 根據名稱取得路由
public function has(string $name): bool              // 檢查路由是否存在
public function all(): array                         // 取得所有路由
public function count(): int                         // 取得路由數量
public function clear(): void                        // 清除所有路由
public function getByMethod(string $method): array   // 根據方法取得路由
public function getByPath(string $path): array       // 根據路徑取得路由
public function toArray(): array                     // 轉換為陣列
```

---

## 快取系統

### RouteCacheInterface

路由快取介面定義。

```php
interface RouteCacheInterface
{
    public function get(string $key): ?array;
    public function put(string $key, array $data, int $ttl = 0): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
    public function has(string $key): bool;
    public function getStatistics(): array;
}
```

### FileRouteCache

檔案系統快取實作。

#### 建構函式

```php
public function __construct(string $cachePath = './storage/cache/routes')
```

#### 方法

```php
public function get(string $key): ?array
public function put(string $key, array $data, int $ttl = 3600): bool
public function forget(string $key): bool
public function flush(): bool
public function has(string $key): bool
public function getStatistics(): array
```

**範例**:
```php
$cache = new FileRouteCache('./storage/cache/routes');
$router->setCache($cache);

// 快取統計
$stats = $cache->getStatistics();
// [
//     'total_files' => 15,
//     'cache_size' => 45120,
//     'hit_rate' => 87.5,
//     'last_cleanup' => '2025-08-25 10:30:15'
// ]
```

### MemoryRouteCache

記憶體快取實作。

#### 方法

```php
public function get(string $key): ?array
public function put(string $key, array $data, int $ttl = 0): bool  // TTL 在記憶體快取中忽略
public function forget(string $key): bool
public function flush(): bool
public function has(string $key): bool
public function getStatistics(): array
```

---

## 中間件系統

### MiddlewareInterface

PSR-15 相容中間件介面。

```php
interface MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

### MiddlewareManager

中間件管理器。

#### 方法

```php
public function add(string $name, MiddlewareInterface $middleware): void
public function addGlobal(string $name, MiddlewareInterface $middleware): void
public function get(string $name): ?MiddlewareInterface
public function has(string $name): bool
public function resolve(array $names): array
public function getGlobal(): array
public function getRegistered(): array
```

**範例**:
```php
$manager = new MiddlewareManager();

// 註冊中間件
$manager->add('auth', new AuthMiddleware());
$manager->add('csrf', new CsrfMiddleware());

// 註冊全域中間件
$manager->addGlobal('cors', new CorsMiddleware());

// 解析中間件
$middlewares = $manager->resolve(['auth', 'csrf']);
```

### MiddlewareDispatcher

中間件分派器，負責執行中間件鏈。

#### 方法

```php
public function dispatch(
    array $middlewares,
    ServerRequestInterface $request,
    RequestHandlerInterface $finalHandler
): ResponseInterface
```

---

## 路由載入器

### RouteLoader

路由配置載入器。

#### 方法

```php
public function load(array $files): array
public function loadFile(string $file): array
public function validate(array $config): ValidationResult
public function getLoadedRoutes(): array
public function reload(): void
```

**範例**:
```php
$loader = new RouteLoader();
$routes = $loader->load([
    'config/routes/api.php',
    'config/routes/web.php'
]);

// 驗證配置
$result = $loader->validate($routes);
if (!$result->isValid()) {
    throw new InvalidRouteConfigException($result->getErrors());
}
```

---

## 例外處理

### 路由相關例外

```php
class RouteNotFoundException extends Exception {}
class RouteConfigurationException extends Exception {}
class InvalidRouteException extends Exception {}
class MethodNotAllowedException extends Exception {}
class RouteCompilationException extends Exception {}
```

### 使用範例

```php
try {
    $result = $router->match('GET', '/api/posts/abc');
    if (!$result->isFound()) {
        if ($result->getStatusCode() === 404) {
            throw new RouteNotFoundException("Route not found: GET /api/posts/abc");
        } elseif ($result->getStatusCode() === 405) {
            throw new MethodNotAllowedException(
                "Method not allowed",
                $result->getAllowedMethods()
            );
        }
    }
} catch (RouteNotFoundException $e) {
    // 處理 404 錯誤
} catch (MethodNotAllowedException $e) {
    // 處理 405 錯誤
}
```

---

## 配置檔案格式

### 路由配置結構

```php
return [
    'prefix' => '/api/v1',                          // 可選：路由前綴
    'middleware' => ['cors', 'api'],                // 可選：群組中間件
    'namespace' => 'App\Api\Controllers',           // 可選：控制器命名空間
    'domain' => 'api.example.com',                  // 可選：域名約束
    'routes' => [                                   // 必需：路由定義陣列
        [
            'method' => 'GET',                      // 必需：HTTP 方法
            'path' => '/posts',                     // 必需：路由路徑
            'handler' => 'PostController@index',    // 必需：處理器
            'name' => 'posts.index',               // 可選：路由名稱
            'middleware' => ['throttle:60,1'],      // 可選：路由專用中間件
            'constraints' => [],                    // 可選：參數約束
            'defaults' => []                        // 可選：預設參數
        ]
    ]
];
```

### 完整配置範例

```php
return [
    'prefix' => '/api/v1',
    'middleware' => ['cors', 'json-response'],
    'routes' => [
        // 基本路由
        [
            'method' => 'GET',
            'path' => '/status',
            'handler' => function() {
                return new JsonResponse(['status' => 'ok']);
            },
            'name' => 'api.status'
        ],
        
        // 帶參數的路由
        [
            'method' => 'GET',
            'path' => '/posts/{id}',
            'handler' => 'PostController@show',
            'name' => 'api.posts.show',
            'constraints' => ['id' => '\d+']
        ],
        
        // 帶中間件的路由
        [
            'method' => 'POST',
            'path' => '/posts',
            'handler' => 'PostController@store',
            'name' => 'api.posts.store',
            'middleware' => ['auth', 'csrf', 'throttle:10,1']
        ],
        
        // 可選參數路由
        [
            'method' => 'GET',
            'path' => '/posts/{category?}',
            'handler' => 'PostController@byCategory',
            'name' => 'api.posts.category',
            'defaults' => ['category' => 'all']
        ]
    ]
];
```

---

## 效能監控

### 統計資訊

```php
// 路由器統計
$stats = $router->getStatistics();
/*
[
    'total_routes' => 45,
    'total_matches' => 1250,
    'cache_hits' => 1180,
    'cache_misses' => 70,
    'average_match_time' => 0.0015,
    'memory_usage' => 2048576
]
*/

// 快取統計
$cacheStats = $cache->getStatistics();
/*
[
    'hit_rate' => 94.4,
    'total_queries' => 1250,
    'cache_size' => 45,
    'memory_usage' => 180224
]
*/
```

### 效能基準測試

```php
// 路由匹配效能測試
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

for ($i = 0; $i < 1000; $i++) {
    $router->match('GET', '/api/posts/123');
}

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

$executionTime = ($endTime - $startTime) * 1000; // 毫秒
$memoryUsed = $endMemory - $startMemory;         // 位元組

echo "1000 次路由匹配:\n";
echo "執行時間: {$executionTime}ms\n";
echo "記憶體使用: {$memoryUsed} bytes\n";
echo "平均每次: " . ($executionTime / 1000) . "ms\n";
```

## 版本資訊

- **API 版本**: v1.0.0
- **相容性**: PHP 8.4+
- **依賴**: PSR-7, PSR-15, PSR-11
- **最後更新**: 2025-08-25