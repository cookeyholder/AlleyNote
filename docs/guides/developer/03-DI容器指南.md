# AlleyNote DI 容器使用指南

**版本**: v4.0
**更新日期**: 2025-09-03
**適用範圍**: AlleyNote 專案開發者
**架構**: 前後端分離 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

---

## 📋 目錄

1. [概述](#概述)
2. [DI 容器架構](#di-容器架構)
3. [基本使用方法](#基本使用方法)
4. [服務註冊](#服務註冊)
5. [服務解析](#服務解析)
6. [快取機制](#快取機制)
7. [PHP 8.4.12 新特性](#php-8412-新特性)
8. [最佳實踐](#最佳實踐)
9. [故障排除](#故障排除)
10. [進階用法](#進階用法)

---

## 概述

AlleyNote 使用 PHP-DI 容器來管理依賴注入，在前後端分離架構中提供了自動化的服務管理、生命週期控制和效能優化。本指南將幫助開發者正確使用 DI 容器。

### 主要特色

- ✅ **自動依賴解析**: 自動解析建構函式依賴
- ✅ **編譯快取**: 生產環境編譯快取提升效能
- ✅ **代理快取**: 延遲載入代理類別
- ✅ **PHP 8.4.12 屬性支援**: 支援最新 PHP 屬性註解
- ✅ **單例管理**: 自動管理服務生命週期
- ✅ **介面綁定**: 介面與實作類別綁定
- ✅ **API 優先設計**: 專為 API 服務優化
- ✅ **非同步屬性**: 支援 PHP 8.4 非同步屬性

---

## DI 容器架構

### 核心組件 (前後端分離)

```
ContainerFactory
├── API Container Builder    (後端 API 服務)
├── Definition Source
├── Cache Configuration
├── Proxy Configuration
├── JWT Service Definitions  (API 認證)
└── CORS Service Definitions (前後端通訊)
```

### 檔案結構

```
src/Infrastructure/
├── ContainerFactory.php          # 容器工廠
├── ServiceDefinitions.php        # 服務定義
└── Container/
    ├── CacheConfig.php           # 快取設定
    ├── ProxyConfig.php           # 代理設定
    └── Definitions/              # 定義檔案
        ├── RepositoryDefinitions.php
        ├── ServiceDefinitions.php
        ├── ControllerDefinitions.php
        └── ValidatorDefinitions.php
```

---

## 基本使用方法

### 1. 容器初始化

```php
<?php
// 在應用程式入口點 (public/index.php)
use AlleyNote\Infrastructure\ContainerFactory;

$container = ContainerFactory::create();
```

### 2. 服務獲取

```php
<?php
// 方式一：直接獲取
$postService = $container->get(PostService::class);

// 方式二：使用介面
$validator = $container->get(ValidatorInterface::class);

// 方式三：透過類型提示自動注入
class PostController
{
    public function __construct(
        private PostService $postService,
        private ValidatorInterface $validator
    ) {}
}
```

### 3. 服務使用範例

```php
<?php
namespace AlleyNote\Controller;

use AlleyNote\Service\PostService;
use AlleyNote\Validation\ValidatorInterface;
use Psr\Container\ContainerInterface;

class PostController
{
    public function __construct(
        private PostService $postService,
        private ValidatorInterface $validator,
        private ContainerInterface $container
    ) {}

    public function create(): void
    {
        // 服務已自動注入，直接使用
        $dto = new CreatePostDTO($_POST, $this->validator);
        $result = $this->postService->createPost($dto);

        // 或動態獲取其他服務
        $cacheService = $this->container->get(CacheService::class);
    }
}
```

---

## 服務註冊

### 1. 自動註冊

DI 容器會自動註冊所有具有類型提示的服務：

```php
<?php
// 自動註冊 - 無需手動設定
class PostService
{
    public function __construct(
        private PostRepository $repository,
        private CacheService $cache
    ) {}
}
```

### 2. 介面綁定

```php
<?php
// src/Infrastructure/Container/Definitions/ServiceDefinitions.php

return [
    // 介面綁定到實作類別
    ValidatorInterface::class => DI\autowire(Validator::class),
    CacheInterface::class => DI\autowire(FileCache::class),

    // 單例服務
    PostService::class => DI\autowire()->scope(Scope::SINGLETON),

    // 工廠模式
    'logger' => DI\factory(function (ContainerInterface $c) {
        return new Logger('alleynote');
    }),

    // 參數注入
    DatabaseConfig::class => DI\create()->constructor(
        DI\env('DB_HOST', 'localhost'),
        DI\env('DB_PORT', 3306)
    ),
];
```

### 3. 條件式註冊

```php
<?php
// 依據環境註冊不同實作
return [
    CacheInterface::class => DI\factory(function () {
        if (extension_loaded('apcu') && function_exists('apcu_store')) {
            return new ApcuCache();
        }
        return new FileCache();
    }),

    ValidatorInterface::class => DI\factory(function () {
        $validator = new Validator();

        // 設定繁體中文錯誤訊息
        $validator->setErrorMessages([
            'required' => '此欄位為必填',
            'email' => '請輸入有效的電子郵件地址',
            'min_length' => '最少需要 {min} 個字元',
        ]);

        return $validator;
    }),
];
```

---

## 服務解析

### 1. 建構函式注入

```php
<?php
class PostController
{
    public function __construct(
        private PostService $postService,        // 自動注入
        private ValidatorInterface $validator,   // 介面注入
        private CacheService $cache              // 自動注入
    ) {}
}
```

### 2. 方法注入

```php
<?php
class PostController
{
    #[Inject]
    private LoggerInterface $logger;

    #[Inject]
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }
}
```

### 3. 屬性注入

```php
<?php
class PostController
{
    #[Inject]
    private PostService $postService;

    #[Inject('app.debug')]
    private bool $debug;
}
```

---

## 快取機制

### 1. 編譯快取

```php
<?php
// ContainerFactory.php 中的快取設定
if (getenv('APP_ENV') === 'production') {
    $builder->enableCompilation('/app/storage/di-cache');
    $builder->writeProxiesToFile(true, '/app/storage/di-cache/proxies');
}
```

### 2. 快取預熱

```bash
# 使用快取預熱腳本
php scripts/warm-cache.php

# 輸出範例：
# ✅ PostService 預熱成功
# ✅ ValidatorInterface 預熱成功
# ✅ PostRepository 預熱成功
# 🎉 快取預熱完成！共預熱 14 個服務，耗時 37.37ms
```

### 3. 快取清理

```bash
# 清理 DI 快取
php scripts/cache-monitor.php clear di

# 清理所有快取
php scripts/cache-monitor.php clear all
```

---

## 最佳實踐

### 1. 服務設計原則

```php
<?php
// ✅ 好的設計 - 依賴介面
class PostService
{
    public function __construct(
        private PostRepositoryInterface $repository,
        private ValidatorInterface $validator,
        private CacheInterface $cache
    ) {}
}

// ❌ 不好的設計 - 依賴具體類別
class PostService
{
    public function __construct(
        private PostRepository $repository,    // 具體類別
        private Validator $validator          // 具體類別
    ) {}
}
```

### 2. 生命週期管理

```php
<?php
return [
    // 單例服務 - 整個請求週期共享
    PostService::class => DI\autowire()->scope(Scope::SINGLETON),
    CacheService::class => DI\autowire()->scope(Scope::SINGLETON),

    // 原型服務 - 每次獲取都建立新實例
    CreatePostDTO::class => DI\autowire()->scope(Scope::PROTOTYPE),

    // 請求範圍 - 每個 HTTP 請求共享
    DatabaseConnection::class => DI\autowire()->scope('request'),
];
```

### 3. 懶載入

```php
<?php
// 使用代理進行懶載入
return [
    ExpensiveService::class => DI\autowire()
        ->lazy()  // 延遲初始化
        ->scope(Scope::SINGLETON),
];
```

### 4. 條件式服務

```php
<?php
return [
    LoggerInterface::class => DI\factory(function () {
        $level = getenv('LOG_LEVEL') ?: 'info';

        if (getenv('APP_ENV') === 'testing') {
            return new NullLogger();
        }

        return new FileLogger($level);
    }),
];
```

---

## 故障排除

### 1. 常見錯誤

#### 循環依賴

```php
<?php
// ❌ 問題：A 依賴 B，B 依賴 A
class ServiceA
{
    public function __construct(ServiceB $b) {}
}

class ServiceB
{
    public function __construct(ServiceA $a) {}
}

// ✅ 解決：引入介面打破循環
interface ServiceAInterface {}

class ServiceA implements ServiceAInterface
{
    public function __construct(ServiceB $b) {}
}

class ServiceB
{
    public function __construct(ServiceAInterface $a) {}
}
```

#### 未註冊的服務

```bash
# 錯誤訊息
No entry or class found for 'SomeService'

# 解決方法：
# 1. 檢查類別是否存在
# 2. 檢查命名空間是否正確
# 3. 檢查是否需要手動註冊
```

#### 快取問題

```bash
# 清理快取並重新生成
rm -rf storage/di-cache/*
php scripts/warm-cache.php
```

### 2. 除錯技巧

```php
<?php
// 開啟除錯模式
$builder->enableCompilation('/app/storage/di-cache', CompiledContainer::class, true);

// 檢查服務是否已註冊
if ($container->has(SomeService::class)) {
    echo "服務已註冊";
}

// 獲取服務定義
$definition = $container->getKnownEntryNames();
var_dump($definition);
```

---

## 進階用法

### 1. 自定義定義載入器

```php
<?php
class CustomDefinitionLoader
{
    public function load(): array
    {
        return [
            'custom.service' => DI\factory(function () {
                return new CustomService();
            }),
        ];
    }
}

// 在 ContainerFactory 中使用
$builder->addDefinitions(new CustomDefinitionLoader());
```

### 2. 中間件整合

```php
<?php
class DIMiddleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // 將容器加入請求屬性
        $request = $request->withAttribute('container', $this->container);

        return $handler->handle($request);
    }
}
```

### 3. 服務裝飾器

```php
<?php
return [
    LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
        $logger = new FileLogger();

        // 在生產環境加入裝飾器
        if (getenv('APP_ENV') === 'production') {
            $logger = new CachedLogger($logger);
            $logger = new AsyncLogger($logger);
        }

        return $logger;
    }),
];
```

### 4. 動態服務註冊

```php
<?php
class ServiceRegistrar
{
    public static function register(ContainerBuilder $builder): void
    {
        // 掃描特定目錄自動註冊服務
        $services = glob(__DIR__ . '/Services/*.php');

        foreach ($services as $service) {
            $className = basename($service, '.php');
            $fullClassName = "AlleyNote\\Service\\{$className}";

            if (class_exists($fullClassName)) {
                $builder->addDefinitions([
                    $fullClassName => DI\autowire(),
                ]);
            }
        }
    }
}
```

---

## 效能優化技巧

### 1. 預編譯容器

```php
<?php
// 生產環境使用預編譯容器
if (getenv('APP_ENV') === 'production') {
    $builder->enableCompilation(
        '/app/storage/di-cache',
        'CompiledContainer'
    );

    // 啟用定義快取
    $builder->enableDefinitionCache();
}
```

### 2. 代理類別

```php
<?php
// 對於昂貴的服務使用代理
return [
    ExpensiveService::class => DI\autowire()
        ->lazy()
        ->scope(Scope::SINGLETON),
];
```

### 3. 效能監控

```php
<?php
// 監控容器建立時間
$start = microtime(true);
$container = ContainerFactory::create();
$time = (microtime(true) - $start) * 1000;

if ($time > 50) {  // 超過 50ms 警告
    error_log("DI Container creation took {$time}ms");
}
```

---

## 測試支援

### 1. 測試容器

```php
<?php
class TestContainerFactory
{
    public static function create(): ContainerInterface
    {
        $builder = new ContainerBuilder();

        // 測試專用服務定義
        $builder->addDefinitions([
            DatabaseInterface::class => DI\create(InMemoryDatabase::class),
            CacheInterface::class => DI\create(NullCache::class),
            LoggerInterface::class => DI\create(NullLogger::class),
        ]);

        return $builder->build();
    }
}
```

### 2. Mock 服務

```php
<?php
class PostServiceTest extends TestCase
{
    public function testCreatePost(): void
    {
        $container = $this->createMockContainer([
            PostRepositoryInterface::class => $this->createMock(PostRepositoryInterface::class),
        ]);

        $service = $container->get(PostService::class);
        // 測試邏輯...
    }
}
```

---

## 參考資源

### 官方文件
- [PHP-DI 官方文件](https://php-di.org/)
- [PSR-11 容器介面](https://www.php-fig.org/psr/psr-11/)

### 專案文件
- [ARCHITECTURE_IMPROVEMENT_COMPLETION.md](ARCHITECTURE_IMPROVEMENT_COMPLETION.md)
- [VALIDATOR_GUIDE.md](VALIDATOR_GUIDE.md)
- [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)

### 相關腳本
- `scripts/warm-cache.php` - 快取預熱
- `scripts/cache-monitor.php` - 快取監控
- `scripts/container-debug.php` - 容器除錯

---

## 常見問題 FAQ

**Q: 如何檢查服務是否已正確註冊？**
```bash
php scripts/container-debug.php list-services
```

**Q: 循環依賴如何解決？**
A: 引入介面或使用 Setter 注入打破循環依賴。

**Q: 快取失效如何處理？**
```bash
php scripts/cache-monitor.php clear di
php scripts/warm-cache.php
```

**Q: 如何在測試中 Mock 服務？**
A: 使用測試專用容器工廠，註冊 Mock 物件。

---

*文件版本: v2.0*
*維護者: AlleyNote 開發團隊*
