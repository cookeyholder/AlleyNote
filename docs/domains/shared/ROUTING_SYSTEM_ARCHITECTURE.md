# AlleyNote 路由系統架構設計文件

## 系統架構概覽

AlleyNote 路由系統採用分層式架構設計，嚴格遵循 DDD (Domain-Driven Design) 和 SOLID 原則。系統分為以下核心層級：

```
┌─────────────────────────────────────────────────────────┐
│                 應用層 (Application)                     │
├─────────────────────────────────────────────────────────┤
│              基礎設施層 (Infrastructure)                │  
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐   │
│  │   路由核心   │  │  中間件系統  │  │    快取系統      │   │
│  └─────────────┘  └─────────────┘  └─────────────────┘   │
├─────────────────────────────────────────────────────────┤
│               共享層 (Shared)                           │
└─────────────────────────────────────────────────────────┘
```

## 核心設計原則

### 1. 單一責任原則 (SRP)
- `Router`: 負責路由註冊和匹配
- `RouteCollection`: 負責路由集合管理
- `MiddlewareManager`: 負責中間件管理
- `RouteCache`: 負責快取操作

### 2. 開放/封閉原則 (OCP)
- 使用介面抽象化核心元件
- 支援新增快取實作而不修改現有程式碼
- 中間件系統支援擴展

### 3. 里氏替換原則 (LSP)
- 所有快取實作都可互換使用
- 中間件實作遵循相同介面契約

### 4. 介面隔離原則 (ISP)
- 細分介面：`RouteCacheInterface`、`MiddlewareInterface`
- 避免強制實作不需要的方法

### 5. 依賴反轉原則 (DIP)
- 依賴抽象而非具體實作
- 使用 DI 容器管理依賴注入

## 詳細架構設計

### 路由核心模組 (Core)

```
App\Infrastructure\Routing\Core\
├── Router.php                    # 路由器主類別
├── Route.php                     # 單一路由實體
├── RouteCollection.php           # 路由集合管理
└── RouteMatcher.php              # 路由匹配器
```

#### Router 類別設計

```php
class Router implements RouterInterface
{
    private RouteCollectionInterface $routes;
    private MiddlewareManagerInterface $middlewareManager;
    private ?RouteCacheInterface $cache;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->routes = new RouteCollection();
        $this->middlewareManager = new MiddlewareManager();
        
        if ($this->config['cache_enabled']) {
            $this->cache = RouteCacheFactory::create($this->config['cache_type']);
        }
    }
}
```

**職責分離**:
- 路由註冊和匹配的協調
- 中間件和快取系統的整合
- 配置管理和預設值設定

#### Route 實體設計

```php
class Route implements RouteInterface
{
    private string $method;
    private string $path;
    private mixed $handler;
    private array $middleware = [];
    private array $constraints = [];
    private array $defaults = [];
    private ?string $name = null;
    private ?string $domain = null;
    
    // 不可變性設計 - 所有修改都回傳新實例
    public function setName(string $name): Route
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }
}
```

**設計特點**:
- 值物件 (Value Object) 特性
- 不可變性 (Immutability)
- 流暢介面 (Fluent Interface)

### 合約層 (Contracts)

```
App\Infrastructure\Routing\Contracts\
├── RouterInterface.php           # 路由器介面
├── RouteInterface.php            # 路由實體介面
├── RouteCollectionInterface.php  # 路由集合介面
├── RouteCacheInterface.php       # 快取介面
├── MiddlewareInterface.php       # 中間件介面
├── MiddlewareManagerInterface.php # 中間件管理器介面
└── RouteMatchResult.php          # 匹配結果VO
```

#### 介面設計原則

**RouterInterface**:
```php
interface RouterInterface
{
    public function get(string $path, mixed $handler, array $options = []): RouteInterface;
    public function post(string $path, mixed $handler, array $options = []): RouteInterface;
    public function match(string $method, string $path): RouteMatchResult;
    public function group(array $attributes, callable $callback): void;
}
```

**特點**:
- 最小化介面：只包含必要方法
- 清晰的職責定義
- 支援方法鏈結

### 快取系統架構

```
App\Infrastructure\Routing\Cache\
├── RouteCacheFactory.php         # 快取工廠
├── FileRouteCache.php           # 檔案快取實作
├── MemoryRouteCache.php         # 記憶體快取實作
└── AbstractRouteCache.php       # 快取基礎類別
```

#### 快取系統設計模式

**工廠模式 (Factory Pattern)**:
```php
class RouteCacheFactory
{
    public static function create(string $type, array $config = []): RouteCacheInterface
    {
        return match ($type) {
            'file' => new FileRouteCache($config['path'] ?? './storage/cache/routes'),
            'memory' => new MemoryRouteCache(),
            'redis' => new RedisRouteCache($config),
            default => throw new InvalidArgumentException("不支援的快取類型: {$type}")
        };
    }
}
```

**策略模式 (Strategy Pattern)**:
```php
abstract class AbstractRouteCache implements RouteCacheInterface
{
    protected array $statistics = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];
    
    final public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'hit_rate' => $this->calculateHitRate(),
            'total_queries' => $this->statistics['hits'] + $this->statistics['misses']
        ]);
    }
    
    abstract protected function doGet(string $key): ?array;
    abstract protected function doPut(string $key, array $data, int $ttl): bool;
}
```

#### 快取策略設計

**多層快取架構**:
```php
class LayeredRouteCache implements RouteCacheInterface
{
    private array $caches;
    
    public function __construct(RouteCacheInterface ...$caches)
    {
        $this->caches = $caches;
    }
    
    public function get(string $key): ?array
    {
        foreach ($this->caches as $cache) {
            $data = $cache->get($key);
            if ($data !== null) {
                // 將資料填充到更快的快取層
                $this->populateUpperLayers($key, $data);
                return $data;
            }
        }
        return null;
    }
}
```

### 中間件系統架構

```
App\Infrastructure\Routing\Middleware\
├── MiddlewareManager.php         # 中間件管理器
├── MiddlewareDispatcher.php      # 中間件分派器
├── AbstractMiddleware.php        # 中間件基礎類別
├── RouteParametersMiddleware.php # 路由參數中間件
└── RouteInfoMiddleware.php       # 路由資訊中間件
```

#### 中間件管道設計

**責任鏈模式 (Chain of Responsibility)**:
```php
class MiddlewareDispatcher implements MiddlewareDispatcherInterface
{
    public function dispatch(
        array $middlewares,
        ServerRequestInterface $request,
        RequestHandlerInterface $finalHandler
    ): ResponseInterface {
        $pipeline = array_reduce(
            array_reverse($middlewares),
            fn($next, $middleware) => new MiddlewareHandler($middleware, $next),
            $finalHandler
        );
        
        return $pipeline->handle($request);
    }
}

class MiddlewareHandler implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $next
    ) {}
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
    }
}
```

### 路由載入器架構

```
App\Infrastructure\Routing\
├── RouteLoader.php              # 路由配置載入器
├── RouteValidator.php           # 路由配置驗證器
└── Exceptions/
    ├── RouteConfigurationException.php
    └── InvalidRouteException.php
```

#### 配置載入設計

**建構者模式 (Builder Pattern)**:
```php
class RouteBuilder
{
    private array $routes = [];
    private array $currentGroup = [];
    
    public function group(array $attributes, callable $callback): self
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = array_merge($this->currentGroup, $attributes);
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
        return $this;
    }
    
    public function route(string $method, string $path, mixed $handler): Route
    {
        $route = new Route($method, $this->buildPath($path), $handler);
        
        if ($this->currentGroup) {
            $route = $this->applyGroupAttributes($route);
        }
        
        $this->routes[] = $route;
        return $route;
    }
}
```

## 效能最佳化設計

### 路由編譯系統

**編譯時最佳化**:
```php
class CompiledRoute
{
    private string $regex;
    private array $parameterNames;
    private array $staticPrefix;
    
    public function __construct(Route $route)
    {
        $this->compile($route);
    }
    
    private function compile(Route $route): void
    {
        $path = $route->getPath();
        
        // 提取靜態前綴以加速匹配
        $this->staticPrefix = $this->extractStaticPrefix($path);
        
        // 編譯正規表達式
        $this->regex = $this->compileRegex($path);
        
        // 提取參數名稱
        $this->parameterNames = $this->extractParameters($path);
    }
    
    public function matches(string $path): ?array
    {
        // 快速靜態前綴檢查
        if (!$this->matchesStaticPrefix($path)) {
            return null;
        }
        
        // 正規表達式匹配
        if (!preg_match($this->regex, $path, $matches)) {
            return null;
        }
        
        return $this->extractParameterValues($matches);
    }
}
```

### 快取最佳化策略

**預載入策略**:
```php
class PreloadingRouteCache implements RouteCacheInterface
{
    private array $preloadedRoutes = [];
    
    public function __construct(array $commonRoutes = [])
    {
        $this->preloadCommonRoutes($commonRoutes);
    }
    
    private function preloadCommonRoutes(array $routes): void
    {
        foreach ($routes as $key => $route) {
            $this->preloadedRoutes[$key] = $this->compileRoute($route);
        }
    }
    
    public function get(string $key): ?array
    {
        // 首先檢查預載入的路由
        if (isset($this->preloadedRoutes[$key])) {
            $this->statistics['hits']++;
            return $this->preloadedRoutes[$key];
        }
        
        // 其他邏輯...
    }
}
```

## 錯誤處理架構

### 例外階層設計

```
App\Infrastructure\Routing\Exceptions\
├── RoutingException.php              # 基礎例外
├── RouteNotFoundException.php        # 路由未找到
├── MethodNotAllowedException.php     # 方法不允許
├── RouteConfigurationException.php   # 配置錯誤
├── RouteCompilationException.php     # 編譯錯誤
└── InvalidRouteParameterException.php # 參數錯誤
```

**錯誤處理策略**:
```php
abstract class RoutingException extends Exception
{
    protected array $context = [];
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
}

class RouteNotFoundException extends RoutingException
{
    public static function forPath(string $method, string $path): self
    {
        $exception = new self("路由未找到: {$method} {$path}");
        $exception->setContext([
            'method' => $method,
            'path' => $path,
            'suggested_routes' => [] // 可以提供建議路由
        ]);
        return $exception;
    }
}
```

## 測試架構設計

### 測試策略分層

```
tests/
├── Unit/
│   ├── Core/
│   ├── Cache/
│   ├── Middleware/
│   └── Validation/
├── Integration/
│   ├── Routing/
│   ├── Controllers/
│   └── Middleware/
└── Performance/
    ├── RoutingBenchmark.php
    └── CacheBenchmark.php
```

**測試工廠設計**:
```php
class RouteTestFactory
{
    public static function createBasicRoute(): Route
    {
        return new Route('GET', '/test', 'TestController@index');
    }
    
    public static function createParameterizedRoute(): Route
    {
        return new Route('GET', '/test/{id}', 'TestController@show')
            ->setConstraints(['id' => '\d+']);
    }
    
    public static function createRouter(array $config = []): Router
    {
        $router = new Router($config);
        $router->setCache(new MemoryRouteCache());
        return $router;
    }
}
```

## 設計模式應用

### 已採用的設計模式

1. **工廠模式**: `RouteCacheFactory`, `MiddlewareFactory`
2. **策略模式**: 快取策略, 路由匹配策略
3. **責任鏈模式**: 中間件管道
4. **建構者模式**: 路由建構器
5. **命令模式**: 路由處理器
6. **觀察者模式**: 路由事件系統
7. **裝飾器模式**: 中間件裝飾
8. **單例模式**: 路由快取管理器

### 架構優勢

1. **可擴展性**: 易於新增新的快取實作和中間件
2. **可測試性**: 介面導向設計便於單元測試
3. **效能**: 多層快取和路由編譯最佳化
4. **可維護性**: 清晰的職責分離和程式碼組織
5. **可配置性**: 豐富的配置選項和彈性設定

## 未來擴展方向

### 計劃中的功能

1. **GraphQL 路由支援**
2. **WebSocket 路由處理**
3. **API 版本管理**
4. **路由快取預熱**
5. **分散式路由快取**
6. **路由分析和監控**

### 架構演進計劃

1. **微服務支援**: 路由代理和服務發現
2. **事件驅動**: 路由變更事件和響應式更新
3. **AI 最佳化**: 基於使用模式的路由最佳化
4. **雲原生**: Kubernetes 整合和服務網格支援

---

**文件版本**: v1.0.0  
**建立日期**: 2025-08-25  
**最後更新**: 2025-08-25  
**維護者**: GitHub Copilot