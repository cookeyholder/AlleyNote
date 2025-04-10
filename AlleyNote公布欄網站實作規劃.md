# AlleyNote 公布欄網站實作規劃

## 0. 測試驅動開發 (TDD) 方法論

本專案採用測試驅動開發(TDD)方法論，遵循「紅燈-綠燈-重構」的開發循環：

### 0.1 TDD 開發循環

1. **紅燈階段**
   - 撰寫失敗的測試案例
   - 定義待實作功能的預期行為
   - 確認測試確實失敗

2. **綠燈階段**
   - 撰寫最小可行的實作程式碼
   - 讓測試案例通過
   - 不考慮程式碼品質，專注於功能正確性

3. **重構階段**
   - 改善程式碼品質
   - 消除重複程式碼
   - 確保測試持續通過

### 0.2 測試層級規劃

1. **單元測試 (Unit Tests)**
   - 類別方法測試
   - 服務函式測試
   - 資料模型測試
   - 工具函式測試

2. **整合測試 (Integration Tests)**
   - API 端點測試
   - 資料庫操作測試
   - 服務層整合測試
   - 外部服務整合測試

3. **端對端測試 (E2E Tests)**
   - 使用者流程測試
   - 系統功能測試
   - 效能測試

### 0.3 TDD 實作準則

1. **測試案例設計原則**
   - 一個測試只測試一個行為
   - 測試案例具有描述性的名稱
   - 遵循 Arrange-Act-Assert 模式
   - 避免測試間的相依性

2. **程式碼品質準則**
   - 依賴注入原則 (DI)
   - 介面隔離原則 (ISP)
   - 單一職責原則 (SRP)
   - 開放封閉原則 (OCP)

### 0.4 測試工具與框架設定

#### 1. PHPUnit 配置
```yaml
# phpunit.xml
<phpunit
    bootstrap="vendor/autoload.php"
    colors="true"
    testdox="true"
    stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/E2E</directory>
        </testsuites>
        <coverage>
            <include>
                <directory suffix=".php">app</directory>
            </include>
            <report>
                <html outputDirectory="tests/coverage"/>
                <clover outputFile="tests/coverage.xml"/>
            </report>
        </coverage>
</phpunit>
```

#### 2. 測試資料庫配置
```php
// config/database.php
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
],
```

#### 3. 測試輔助工具

1. **測試資料產生器**
   - Faker 資料產生器設定
   ```php
   use Faker\Factory as Faker;
   
   $faker = Faker::create('zh_TW');
   $faker->addProvider(new CustomDataProvider($faker));
   ```

2. **測試替身（Test Doubles）設定**
   ```php
   // 模擬外部服務
   $mock = $this->createMock(ExternalService::class);
   $mock->method('callApi')
        ->willReturn(['status' => 'success']);
   
   // 模擬資料庫
   $stub = $this->createStub(Repository::class);
   $stub->method('find')
        ->willReturn(new User(['name' => 'Test User']));
   ```

### 0.5 測試案例範本

#### 1. 單元測試範本
```php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PostService;
use App\Models\Post;

class PostServiceTest extends TestCase
{
    private PostService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostService();
    }
    
    /** @test */
    public function shouldCreateNewPost()
    {
        // 安排
        $input = [
            'title' => '測試文章',
            'content' => '這是測試內容'
        ];
        
        // 執行
        $post = $this->service->create($input);
        
        // 斷言
        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('測試文章', $post->title);
    }
}
```

#### 2. 整合測試範本
```php
namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }
    
    /** @test */
    public function shouldCreatePostThroughApi()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'title' => '測試文章',
                'content' => '這是測試內容'
            ]);
            
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content']
            ]);
    }
}
```

### 0.6 測試自動化與 CI/CD 整合

#### 1. GitHub Actions 工作流程
```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, sqlite3
        
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
        
    - name: Execute Tests
      run: |
        php artisan test --parallel
        php artisan test:coverage
        
    - name: Upload Coverage Report
      uses: codecov/codecov-action@v2
      with:
        file: ./tests/coverage.xml
```

### 0.7 測試效能指標

#### 1. 測試執行時間目標
- 單元測試：< 1秒/測試
- 整合測試：< 3秒/測試
- 端對端測試：< 10秒/測試

#### 2. 測試維護指標
1. **測試程式碼品質**
   - 循環複雜度 < 5
   - 函式長度 < 20行
   - 類別長度 < 200行

2. **測試可讀性**
   - 使用描述性的測試函式名稱
   - 遵循 AAA (Arrange-Act-Assert) 模式
   - 每個測試只測試一個行為

3. **測試隔離度**
   - 避免測試間的相依性
   - 每次測試後清理測試資料
   - 使用專用的測試資料庫

### 0.8 定期測試審查清單

1. **每週測試審查**
   - [ ] 檢查測試覆蓋率報告
   - [ ] 審查失敗的測試案例
   - [ ] 更新過時的測試資料
   - [ ] 檢查測試執行時間

2. **每月測試維護**
   - [ ] 重構重複的測試程式碼
   - [ ] 更新測試文件
   - [ ] 檢查測試相依套件更新
   - [ ] 最佳化緩慢的測試

## 1. 專案實作概述

本文件詳細說明 AlleyNote 公布欄網站的實作計畫，包含各階段工作項目、技術細節與時程規劃。

### 1.1 專案目標與範疇

依據規格書要求，本專案將實作一個現代化的公布欄系統，主要特點：
- 使用 PHP 8.4.5 建構後端系統
- 採用 SQLite3 作為資料庫系統
- 使用 Docker 進行容器化部署
- 在 Debian Linux 12 環境運行
- 使用 NGINX 作為網頁伺服器

### 1.2 技術堆疊清單

#### 後端技術
- 程式語言：PHP 8.4.5
- 資料庫：SQLite3
- Web 伺服器：NGINX
- 容器化：Docker & Docker Compose
- 作業系統：Debian Linux 12

#### 前端技術
- HTML5 & CSS3
- JavaScript/TypeScript
- Tailwind CSS
- CKEditor（最新版）

#### 開發工具
- Git 版本控制
- Docker 容器化工具
- VSCode/PHPStorm IDE
- Postman API 測試工具
- phpMyAdmin 資料庫管理工具

## 2. 實作階段規劃

### 2.1 第一階段：環境建置與基礎架構（2週）

#### 2.1.1 測試環境建置（2天）
1. **測試框架設定**
   ```
   /tests
   ├── Unit/
   │   ├── Models/
   │   ├── Services/
   │   └── Utils/
   ├── Integration/
   │   ├── API/
   │   ├── Database/
   │   └── Services/
   └── E2E/
       ├── Features/
       └── Scenarios/
   ```

2. **測試工具建置**
   - PHPUnit 設定
   - Mockery 模擬物件框架
   - Faker 測試資料產生器
   - PHPUnit-Watcher 自動測試工具

3. **持續整合設定**
   - GitHub Actions 工作流程
   - 測試覆蓋率報告
   - 程式碼品質檢查

#### 2.1.2 基礎架構測試（3天）
1. **資料庫連接測試**
   ```php
   class DatabaseConnectionTest extends TestCase
   {
       public function testShouldConnectToDatabase()
       {
           $connection = new DatabaseConnection();
           $this->assertTrue($connection->isConnected());
       }

       public function testShouldHandleDatabaseError()
       {
           $connection = new DatabaseConnection('invalid_path');
           $this->assertFalse($connection->isConnected());
       }
   }
   ```

2. **路由系統測試**
   ```php
   class RouterTest extends TestCase
   {
       public function testShouldRouteToCorrectController()
       {
           $router = new Router();
           $route = $router->resolve('/api/v1/posts');
           $this->assertEquals(PostsController::class, $route->getController());
       }
   }
   ```

#### 2.1.3 開發環境建置（3天）
1. **Docker 環境配置**
   - 建立 Dockerfile 
   - 設定 docker-compose.yml
   - 配置 PHP-FPM 
   - 設定 NGINX 伺服器

2. **資料庫環境設定**
   - 建立 SQLite3 資料庫
   - 設定資料庫連接
   - 建立資料庫備份機制

3. **版本控制設定**
   - 初始化 Git 儲存庫
   - 設定 .gitignore
   - 建立開發分支策略

#### 2.1.4 基礎架構實作（4天）
1. **專案目錄結構建置**
   ```
   /
   ├── app/
   │   ├── Controllers/
   │   ├── Models/
   │   ├── Services/
   │   └── Repositories/
   ├── config/
   ├── database/
   │   └── migrations/
   ├── public/
   ├── resources/
   │   ├── views/
   │   ├── js/
   │   └── css/
   └── tests/
   ```

2. **基礎元件實作**
   - 路由系統
   - 資料庫連接器
   - 快取機制
   - 日誌系統
   - 錯誤處理機制

#### 2.1.5 安全框架建置（3天）
- 實作 JWT 驗證機制
- 設定 CORS 政策
- 實作 XSS 防護
- 設定 CSRF 保護
- 建立 IP 過濾機制

### 2.2 第二階段：核心功能實作（4週）

#### 2.2.1 使用者管理系統（1週）

1. **使用者模型測試**
   ```php
   class UserTest extends TestCase
   {
       public function testShouldCreateUser()
       {
           $userData = [
               'username' => 'testuser',
               'email' => 'test@example.com',
               'password' => 'password123'
           ];
           
           $user = new User($userData);
           
           $this->assertEquals('testuser', $user->username);
           $this->assertTrue($user->verifyPassword('password123'));
       }

       public function testShouldValidateEmail()
       {
           $this->expectException(ValidationException::class);
           
           $userData = [
               'username' => 'testuser',
               'email' => 'invalid-email',
               'password' => 'password123'
           ];
           
           new User($userData);
       }
   }
   ```

2. **認證服務測試**
   ```php
   class AuthServiceTest extends TestCase
   {
       public function testShouldAuthenticateValidUser()
       {
           $authService = new AuthService();
           $token = $authService->authenticate('testuser', 'password123');
           
           $this->assertNotNull($token);
           $this->assertTrue($authService->verifyToken($token));
       }

       public function testShouldRejectInvalidCredentials()
       {
           $this->expectException(AuthenticationException::class);
           
           $authService = new AuthService();
           $authService->authenticate('testuser', 'wrongpassword');
       }
   }
   ```

#### 2.2.2 文章管理系統（2週）
1. **資料模型設計**
   - Posts 資料表
   - Tags 資料表
   - Attachments 資料表
   - Post_Views 資料表

2. **功能實作**
   - 文章 CRUD 操作
   - 文章編輯器整合（CKEditor）
   - 文章置頂功能
   - 草稿系統
   - 排程發布功能
   - 搜尋功能實作

#### 2.2.3 附件管理系統（1週）
1. **功能實作**
   - 檔案上傳機制
   - 檔案格式驗證
   - 檔案大小限制
   - 預覽功能
   - 安全性檢查

### 2.3 第三階段：進階功能與優化（3週）

#### 2.3.1 IP 管理系統（1週）
1. **功能實作**
   - IP 黑白名單管理
   - IP 範圍設定（CIDR）
   - 存取記錄系統
   - 異常存取偵測

#### 2.3.2 效能優化（1週）
1. **快取機制實作**
   - 頁面快取
   - 查詢快取
   - 靜態資源快取

2. **效能監控**
   - 系統監控設定
   - 效能指標收集
   - 日誌分析系統

#### 2.3.3 外部整合功能（1週）
1. **API 開發**
   - RESTful API 設計
   - API 文件產生
   - API 認證機制
   - 跨域支援設定

### 2.4 第四階段：測試與部署（3週）

#### 2.4.1 測試（2週）
1. **單元測試**
   - Controllers 測試
   - Models 測試
   - Services 測試
   - Repositories 測試

2. **整合測試**
   - API 端點測試
   - 使用者流程測試
   - 權限系統測試

3. **效能測試**
   - 負載測試
   - 壓力測試
   - 併發測試

#### 2.4.2 部署準備（1週）
1. **部署腳本準備**
   - Docker 部署腳本
   - 資料庫遷移腳本
   - 環境設定腳本

2. **文件準備**
   - 系統文件
   - API 文件
   - 使用者手冊
   - 維運手冊

## 3. 詳細技術實作規劃

### 3.1 資料庫實作細節

#### 3.1.1 資料表結構定義

```sql
-- Users 資料表
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    status INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Units 資料表
CREATE TABLE units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Posts 資料表
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT NOT NULL UNIQUE,
    seq_number INTEGER NOT NULL UNIQUE,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    user_ip TEXT NOT NULL,
    views INTEGER DEFAULT 0,
    is_pinned INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    publish_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 8. 詳細技術實作細節

### 8.1 資料庫設計與效能優化

#### 8.1.1 索引策略
```sql
-- Users 資料表索引
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_uuid ON users(uuid);

-- Posts 資料表索引
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_publish_date ON posts(publish_date);
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_is_pinned ON posts(is_pinned);
CREATE UNIQUE INDEX idx_posts_seq_number ON posts(seq_number);

-- Post_Views 資料表索引
CREATE INDEX idx_post_views_post_id ON post_views(post_id);
CREATE INDEX idx_post_views_user_id ON post_views(user_id);
CREATE INDEX idx_post_views_view_date ON post_views(view_date);
```

#### 8.1.2 資料表分區策略
```sql
-- Posts 資料表依照時間分區
CREATE TABLE posts_2024 (
    CHECK (publish_date >= '2024-01-01' AND publish_date < '2025-01-01')
) INHERITS (posts);

CREATE TABLE posts_2025 (
    CHECK (publish_date >= '2025-01-01' AND publish_date < '2026-01-01')
) INHERITS (posts);

-- 自動分區函式
CREATE OR REPLACE FUNCTION posts_insert_trigger()
RETURNS TRIGGER AS $$
BEGIN
    IF (NEW.publish_date >= '2024-01-01' AND NEW.publish_date < '2025-01-01') THEN
        INSERT INTO posts_2024 VALUES (NEW.*);
    ELSIF (NEW.publish_date >= '2025-01-01' AND NEW.publish_date < '2026-01-01') THEN
        INSERT INTO posts_2025 VALUES (NEW.*);
    ELSE
        RAISE EXCEPTION 'Date out of range';
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;
```

### 8.2 快取策略實作

#### 8.2.1 快取介面與抽象工廠
```php
interface CacheInterface
{
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function get(string $key): mixed;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}

enum CacheType
{
    case Redis;
    case File;
    case Memory;
}

readonly class CacheConfig
{
    public function __construct(
        public CacheType $type,
        public array $options = []
    ) {}
}

class CacheFactory
{
    public static function create(CacheConfig $config): CacheInterface
    {
        return match($config->type) {
            CacheType::Redis => new RedisCache($config->options),
            CacheType::File => new FileCache($config->options),
            CacheType::Memory => new MemoryCache($config->options),
        };
    }
}
```

#### 8.2.2 快取實作（檔案系統為預設）
```php
class FileCache implements CacheInterface
{
    private const DEFAULT_TTL = 3600;
    private string $cachePath;
    private readonly LoggerInterface $logger;

    public function __construct(
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->cachePath = $options['path'] ?? '/var/cache/alleynote';
        $this->logger = $logger ?? new NullLogger();
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0750, true);
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $this->validateKey($key);
            $path = $this->getPath($key);
            $data = [
                'value' => $value,
                'expires' => time() + ($ttl ?? self::DEFAULT_TTL)
            ];
            
            $tempFile = tempnam($this->cachePath, 'cache_');
            file_put_contents($tempFile, serialize($data));
            chmod($tempFile, 0640);
            
            return rename($tempFile, $path);
        } catch (Throwable $e) {
            $this->logger->error('快取寫入失敗', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function get(string $key): mixed
    {
        try {
            $this->validateKey($key);
            $path = $this->getPath($key);
            
            if (!file_exists($path)) {
                return null;
            }

            $data = unserialize(file_get_contents($path));
            if (time() > $data['expires']) {
                unlink($path);
                return null;
            }

            return $data['value'];
        } catch (Throwable $e) {
            $this->logger->error('快取讀取失敗', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function validateKey(string $key): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
            throw new InvalidArgumentException('無效的快取鍵名');
        }
    }

    private function getPath(string $key): string
    {
        return $this->cachePath . '/' . hash('sha256', $key);
    }
}
```

#### 8.2.3 Redis 快取實作（可選）
```php
class RedisCache implements CacheInterface
{
    private ?Redis $redis = null;
    private readonly LoggerInterface $logger;

    public function __construct(
        array $options = [],
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        
        try {
            $this->redis = new Redis();
            $this->redis->connect(
                $options['host'] ?? '127.0.0.1',
                $options['port'] ?? 6379,
                $options['timeout'] ?? 2.0
            );
            
            if (isset($options['password'])) {
                $this->redis->auth($options['password']);
            }
            
            if (isset($options['database'])) {
                $this->redis->select($options['database']);
            }
        } catch (Throwable $e) {
            $this->logger->error('Redis 連接失敗，將改用檔案快取', [
                'error' => $e->getMessage()
            ]);
            $this->redis = null;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->redis) {
            return (new FileCache())->set($key, $value, $ttl);
        }

        try {
            return $this->redis->set(
                $this->prefixKey($key),
                serialize($value),
                $ttl ? ['EX' => $ttl] : []
            );
        } catch (Throwable $e) {
            $this->logger->error('Redis 寫入失敗', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function prefixKey(string $key): string
    {
        return 'alleynote:' . $key;
    }
}
```

#### 8.2.4 快取使用範例
```php
class PostService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly PostRepository $repository
    ) {}

    public function getPost(string $uuid): ?Post
    {
        $cacheKey = "post:$uuid";
        
        // 首先嘗試從快取取得
        $cachedPost = $this->cache->get($cacheKey);
        if ($cachedPost !== null) {
            return $cachedPost;
        }
        
        // 從資料庫取得
        $post = $this->repository->find($uuid);
        if ($post) {
            // 存入快取（1小時）
            $this->cache->set($cacheKey, $post, 3600);
        }
        
        return $post;
    }
}

// 使用範例
$config = new CacheConfig(
    // 如果有 Redis 就用 Redis，否則自動降級到檔案快取
    type: extension_loaded('redis') ? CacheType::Redis : CacheType::File,
    options: [
        'host' => 'localhost',
        'port' => 6379,
        'timeout' => 2.0,
        'database' => 0
    ]
);

$cache = CacheFactory::create($config);
$postService = new PostService($cache, new PostRepository());
```

### 8.3 安全性實作細節

#### 8.3.1 密碼雜湊與驗證
```php
readonly class PasswordConfig
{
    public function __construct(
        public int $memoryCost = 65536,    // 64MB
        public int $timeCost = 4,          // 4 次迭代
        public int $threads = 3,           // 3 個執行緒
        public int $outputLength = 32      // 256 位元
    ) {}
}

class PasswordService
{
    private const HASH_ALGO = PASSWORD_ARGON2ID;

    public function __construct(
        private readonly PasswordConfig $config = new PasswordConfig()
    ) {}

    public function hash(#[\SensitiveParameter] string $password): string
    {
        return password_hash(
            $password,
            self::HASH_ALGO,
            [
                'memory_cost' => $this->config->memoryCost,
                'time_cost' => $this->config->timeCost,
                'threads' => $this->config->threads
            ]
        );
    }

    public function verify(
        #[\SensitiveParameter] string $password,
        #[\SensitiveParameter] string $hash
    ): bool {
        return password_verify($password, $hash);
    }

    public function needsRehash(#[\SensitiveParameter] string $hash): bool
    {
        return password_needs_rehash(
            $hash,
            self::HASH_ALGO,
            [
                'memory_cost' => $this->config->memoryCost,
                'time_cost' => $this->config->timeCost,
                'threads' => $this->config->threads
            ]
        );
    }
}
```

### 8.4 檔案上傳服務實作

#### 8.4.1 檔案處理服務
```php
class FileUploadService
{
    private const UPLOAD_DIR = '/var/www/uploads';
    private const MAX_FILE_SIZE = 52428800; // 50MB
    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public function upload(UploadedFile $file): string
    {
        $this->validateFile($file);
        $filename = $this->generateUniqueFilename($file);
        $path = $this->getUploadPath($filename);
        
        move_uploaded_file($file->getPathname(), $path);
        
        return $filename;
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new UploadException('檔案太大');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_TYPES)) {
            throw new UploadException('不支援的檔案類型');
        }

        // 病毒掃描
        $this->scanFile($file);
    }

    private function scanFile(UploadedFile $file): void
    {
        $scanner = new ClamAV();
        $result = $scanner->scan($file->getPathname());
        
        if ($result->isInfected()) {
            throw new UploadException('檔案可能包含病毒');
        }
    }
}
```

### 8.5 效能監控實作

#### 8.5.1 效能追蹤器
```php
class PerformanceTracker
{
    private $measurements = [];
    private $startTimes = [];

    public function startMeasurement(string $name): void
    {
        $this->startTimes[$name] = microtime(true);
    }

    public function endMeasurement(string $name): float
    {
        if (!isset($this->startTimes[$name])) {
            throw new RuntimeException("未找到測量開始時間");
        }

        $duration = microtime(true) - $this->startTimes[$name];
        $this->measurements[$name][] = $duration;

        return $duration;
    }

    public function getAverageTime(string $name): float
    {
        if (!isset($this->measurements[$name])) {
            return 0;
        }

        return array_sum($this->measurements[$name]) / count($this->measurements[$name]);
    }

    public function exportMetrics(): array
    {
        $metrics = [];
        foreach ($this->measurements as $name => $durations) {
            $metrics[$name] = [
                'avg' => $this->getAverageTime($name),
                'min' => min($durations),
                'max' => max($durations),
                'count' => count($durations)
            ];
        }
        return $metrics;
    }
}
```

### 8.6 資料庫交易管理

#### 8.6.1 交易管理器
```php
class TransactionManager
{
    private $pdo;
    private $transactionLevel = 0;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function begin(): void
    {
        if ($this->transactionLevel === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
        }
        $this->transactionLevel++;
    }

    public function commit(): void
    {
        $this->transactionLevel--;
        if ($this->transactionLevel === 0) {
            $this->pdo->commit();
        }
    }

    public function rollback(?int $toLevel = null): void
    {
        if ($toLevel === null) {
            $this->pdo->rollBack();
            $this->transactionLevel = 0;
            return;
        }

        if ($toLevel < $this->transactionLevel) {
            $this->pdo->exec("ROLLBACK TO SAVEPOINT LEVEL{$toLevel}");
            $this->transactionLevel = $toLevel;
        }
    }
}
```

### 8.7 搜尋引擎整合

#### 8.7.1 全文搜尋服務
```php
class SearchService
{
    private PDO $pdo;
    private $elastic;
    private const INDEX = 'posts';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->elastic = ClientBuilder::create()
            ->setHosts(['localhost:9200'])
            ->build();
    }

    public function indexPost(string $uuid): void
    {
        $stmt = $this->pdo->prepare('
            SELECT p.*, u.username as author_name, u.name as unit_name
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN units un ON p.unit_id = un.id
            WHERE p.uuid = :uuid
        ');
        
        $stmt->execute(['uuid' => $uuid]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            throw new RuntimeException('找不到指定文章');
        }

        // 取得文章標籤
        $tagStmt = $this->pdo->prepare('
            SELECT t.name
            FROM post_tags pt
            JOIN tags t ON pt.tag_id = t.id
            WHERE pt.post_id = :post_id
        ');
        $tagStmt->execute(['post_id' => $post['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // 建立搜尋索引
        $this->elastic->index([
            'index' => self::INDEX,
            'id' => $post['uuid'],
            'body' => [
                'title' => $post['title'],
                'content' => $post['content'],
                'author' => $post['author_name'],
                'tags' => $tags,
                'publish_date' => $post['publish_date'],
                'unit' => $post['unit_name']
            ]
        ]);
    }

    public function search(string $query, array $filters = []): array
    {
        // 搜尋 Elasticsearch
        $searchParams = [
            'index' => self::INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['title^2', 'content', 'tags^1.5']
                            ]
                        ],
                        'filter' => []
                    ]
                ],
                'highlight' => [
                    'fields' => [
                        'title' => new \stdClass(),
                        'content' => new \stdClass()
                    ]
                ]
            ]
        ];

        foreach ($filters as $field => $value) {
            $searchParams['body']['query']['bool']['filter'][] = [
                'term' => [$field => $value]
            ];
        }

        $results = $this->elastic->search($searchParams);

        // 從資料庫取得完整資料
        $uuids = array_column($results['hits']['hits'], '_id');
        if (empty($uuids)) {
            return ['total' => 0, 'hits' => []];
        }

        $placeholders = str_repeat('?,', count($uuids) - 1) . '?';
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username as author_name, un.name as unit_name
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN units un ON p.unit_id = un.id
            WHERE p.uuid IN ($placeholders)
        ");
        
        $stmt->execute($uuids);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 合併搜尋結果與資料庫資料
        $finalResults = [];
        foreach ($results['hits']['hits'] as $hit) {
            foreach ($posts as $post) {
                if ($post['uuid'] === $hit['_id']) {
                    $finalResults[] = array_merge(
                        $post,
                        ['highlights' => $hit['highlight'] ?? []]
                    );
                    break;
                }
            }
        }

        return [
            'total' => $results['hits']['total']['value'],
            'hits' => $finalResults
        ];
    }
}
```

### 8.8 IP 過濾與管理

#### 8.8.1 IP 過濾器
```php
class IPFilter
{
    private $redis;
    private const BLACKLIST_KEY = 'ip:blacklist';
    private const WHITELIST_KEY = 'ip:whitelist';
    private const RATE_LIMIT_PREFIX = 'ip:rate:';
    private const RATE_LIMIT = 100; // 每分鐘請求數
    private const RATE_WINDOW = 60; // 時間窗口（秒）

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function isAllowed(string $ip): bool
    {
        // 檢查白名單
        if ($this->redis->sIsMember(self::WHITELIST_KEY, $ip)) {
            return true;
        }

        // 檢查黑名單
        if ($this->redis->sIsMember(self::BLACKLIST_KEY, $ip)) {
            return false;
        }

        // 檢查頻率限制
        return $this->checkRateLimit($ip);
    }

    private function checkRateLimit(string $ip): bool
    {
        $key = self::RATE_LIMIT_PREFIX . $ip;
        $currentCount = $this->redis->incr($key);
        
        if ($currentCount === 1) {
            $this->redis->expire($key, self::RATE_WINDOW);
        }

        return $currentCount <= self::RATE_LIMIT;
    }

    public function blockIP(string $ip): void
    {
        $this->redis->sAdd(self::BLACKLIST_KEY, $ip);
        $this->redis->sRem(self::WHITELIST_KEY, $ip);
    }

    public function allowIP(string $ip): void
    {
        $this->redis->sAdd(self::WHITELIST_KEY, $ip);
        $this->redis->sRem(self::BLACKLIST_KEY, $ip);
    }
}
```

### 8.9 背景工作佇列

#### 8.9.1 佇列工作器
```php
class QueueWorker
{
    private Redis $redis;
    private PDO $pdo;
    private const QUEUE_KEY = 'tasks:queue';
    private const PROCESSING_KEY = 'tasks:processing';
    private const MAX_RETRY = 3;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public function enqueue(Task $task): void
    {
        // 開始資料庫交易
        $this->pdo->beginTransaction();
        
        try {
            // 記錄任務到資料庫
            $stmt = $this->pdo->prepare('
                INSERT INTO tasks (uuid, type, data, status, created_at)
                VALUES (:uuid, :type, :data, :status, :created_at)
            ');
            
            $stmt->execute([
                'uuid' => $task->getUuid(),
                'type' => $task->getType(),
                'data' => json_encode($task->getData()),
                'status' => 'queued',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 加入 Redis 佇列
            $this->redis->lPush(self::QUEUE_KEY, $task->getUuid());
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function processNextTask(): void
    {
        // 從 Redis 佇列取出任務
        $taskUuid = $this->redis->rPopLPush(
            self::QUEUE_KEY,
            self::PROCESSING_KEY
        );

        if (!$taskUuid) {
            return;
        }

        // 開始資料庫交易
        $this->pdo->beginTransaction();
        
        try {
            // 從資料庫讀取任務詳情
            $stmt = $this->pdo->prepare('
                SELECT * FROM tasks 
                WHERE uuid = :uuid AND status = :status
                FOR UPDATE
            ');
            
            $stmt->execute([
                'uuid' => $taskUuid,
                'status' => 'queued'
            ]);
            
            $taskData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$taskData) {
                $this->pdo->rollBack();
                return;
            }

            // 更新任務狀態為處理中
            $stmt = $this->pdo->prepare('
                UPDATE tasks 
                SET status = :status,
                    started_at = :started_at
                WHERE uuid = :uuid
            ');
            
            $stmt->execute([
                'status' => 'processing',
                'started_at' => date('Y-m-d H:i:s'),
                'uuid' => $taskUuid
            ]);

            // 建立任務實例
            $task = Task::createFromArray($taskData);
            
            // 執行任務
            $task->execute();
            
            // 更新任務狀態為完成
            $stmt = $this->pdo->prepare('
                UPDATE tasks 
                SET status = :status,
                    completed_at = :completed_at
                WHERE uuid = :uuid
            ');
            
            $stmt->execute([
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'uuid' => $taskUuid
            ]);
            
            // 從處理中佇列移除
            $this->redis->lRem(self::PROCESSING_KEY, $taskUuid, 1);
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            
            // 更新重試次數和錯誤資訊
            $stmt = $this->pdo->prepare('
                UPDATE tasks 
                SET retry_count = retry_count + 1,
                    last_error = :error,
                    status = CASE 
                        WHEN retry_count >= :max_retry THEN :failed_status
                        ELSE :queued_status
                    END
                WHERE uuid = :uuid
            ');
            
            $stmt->execute([
                'error' => $e->getMessage(),
                'max_retry' => self::MAX_RETRY,
                'failed_status' => 'failed',
                'queued_status' => 'queued',
                'uuid' => $taskUuid
            ]);

            // 如果未達到最大重試次數，重新加入佇列
            $stmt = $this->pdo->prepare('
                SELECT retry_count FROM tasks WHERE uuid = :uuid
            ');
            $stmt->execute(['uuid' => $taskUuid]);
            $retryCount = (int)$stmt->fetchColumn();
            
            if ($retryCount < self::MAX_RETRY) {
                $this->redis->lPush(self::QUEUE_KEY, $taskUuid);
            } else {
                $this->handleFailedTask($taskUuid, $e);
            }
            
            $this->redis->lRem(self::PROCESSING_KEY, $taskUuid, 1);
        }
    }

    private function handleFailedTask(string $taskUuid, Exception $e): void
    {
        // 記錄失敗資訊到專門的失敗任務資料表
        $stmt = $this->pdo->prepare('
            INSERT INTO failed_tasks (
                task_uuid,
                error_message,
                stack_trace,
                failed_at
            ) VALUES (
                :task_uuid,
                :error_message,
                :stack_trace,
                :failed_at
            )
        ');
        
        $stmt->execute([
            'task_uuid' => $taskUuid,
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
            'failed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

### 8.10 日誌記錄系統

#### 8.10.1 日誌記錄器
```php
class Logger
{
    private const LOG_PATH = '/var/log/alleynote';
    private const MAX_LOG_SIZE = 104857600; // 100MB
    private const MAX_LOG_FILES = 10;

    private function rotate(): void
    {
        $logFile = self::LOG_PATH . '/app.log';
        if (file_exists($logFile) && filesize($logFile) > self::MAX_LOG_SIZE) {
            for ($i = self::MAX_LOG_FILES - 1; $i > 0; $i--) {
                $oldFile = "{$logFile}.{$i}";
                $newFile = "{$logFile}." . ($i + 1);
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }
            rename($logFile, "{$logFile}.1");
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->rotate();
        
        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];

        file_put_contents(
            self::LOG_PATH . '/app.log',
            json_encode($entry) . "\n",
            FILE_APPEND
        );
    }
}
```

### 8.11 資料庫互動實作

#### 8.11.1 資料庫連線與查詢
```php
readonly class DatabaseConfig
{
    public function __construct(
        public string $path,
        public int $timeout = 2000,
        public bool $readOnly = false,
        public string $encoding = 'UTF-8'
    ) {}
}

class Database
{
    private PDO $pdo;
    private readonly LoggerInterface $logger;

    public function __construct(
        DatabaseConfig $config,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => $config->timeout,
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::SQLITE_ATTR_OPEN_FLAGS => $config->readOnly 
                ? PDO::SQLITE_OPEN_READONLY 
                : PDO::SQLITE_OPEN_READWRITE | PDO::SQLITE_OPEN_CREATE
        ];

        $dsn = "sqlite:{$config->path}";
        
        try {
            $this->pdo = new PDO($dsn, null, null, $options);
            $this->pdo->exec("PRAGMA encoding = '{$config->encoding}'");
        } catch (PDOException $e) {
            $this->logger->error('資料庫連線失敗', [
                'error' => $e->getMessage(),
                'dsn' => $dsn
            ]);
            throw $e;
        }
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * 使用 PHP 8.4 新的 Iterator 綁定功能執行批次查詢
     */
    public function batchInsert(string $sql, Iterator $records): int
    {
        $stmt = $this->pdo->prepare($sql);
        $count = 0;
        
        foreach ($records as $record) {
            $stmt->execute((array) $record);
            $count++;
        }
        
        return $count;
    }
}
```

#### 8.11.2 資料庫儲存庫基底類別
```php
abstract readonly class BaseRepository
{
    public function __construct(
        protected Database $db,
        protected LoggerInterface $logger
    ) {}

    /**
     * 使用 PHP 8.4 的型別標註功能和回傳型別
     */
    abstract public function create(object $data): int|string;
    abstract public function update(int|string $id, object $data): bool;
    abstract public function delete(int|string $id): bool;
    abstract public function find(int|string $id): ?object;
}

/**
 * 附件儲存庫實作
 */
final class AttachmentRepository extends BaseRepository
{
    public function create(object $data): int|string
    {
        // 檢查附件數量限制
        $currentCount = $this->getAttachmentCount($data->post_id);
        $maxAttachments = $this->getMaxAttachments($data->post_id);
        
        if ($maxAttachments === 0) {
            throw new AttachmentException('此文章不允許附件上傳');
        }
        
        if ($currentCount >= $maxAttachments) {
            throw new AttachmentException('已達附件數量上限');
        }
        
        // 新增附件
        $sql = 'INSERT INTO attachments (uuid, post_id, filename, filepath, filesize, filetype) 
                VALUES (:uuid, :post_id, :filename, :filepath, :filesize, :filetype)';
                
        return $this->db->query($sql, (array) $data)->lastInsertId();
    }

    private function getMaxAttachments(int $postId): int
    {
        // 取得文章所屬單位的附件數量設定
        $sql = 'SELECT u.max_attachments FROM posts p 
                JOIN units u ON p.unit_id = u.id 
                WHERE p.id = ?';
                
        $result = $this->db->query($sql, [$postId])->fetch();
        return $result['max_attachments'] ?? 0;
    }

    private function getAttachmentCount(int $postId): int
    {
        $sql = 'SELECT COUNT(*) as count FROM attachments WHERE post_id = ?';
        $result = $this->db->query($sql, [$postId])->fetch();
        return (int) $result['count'];
    }
}
```

#### 8.11.3 單位附件設定服務
```php
final readonly class UnitAttachmentConfig
{
    public function __construct(
        public int $maxAttachments = 0,
        public int $maxFileSize = 0,
        public array $allowedTypes = []
    ) {}
}

final class UnitService
{
    public function __construct(
        private readonly Database $db,
        private readonly LoggerInterface $logger
    ) {}

    public function updateAttachmentConfig(
        int $unitId, 
        UnitAttachmentConfig $config
    ): bool {
        $sql = 'UPDATE units 
                SET max_attachments = :max_attachments,
                    max_file_size = :max_file_size,
                    allowed_types = :allowed_types
                WHERE id = :id';
                
        try {
            $this->db->query($sql, [
                'max_attachments' => $config->maxAttachments,
                'max_file_size' => $config->maxFileSize,
                'allowed_types' => json_encode($config->allowedTypes),
                'id' => $unitId
            ]);
            
            $this->logger->info('更新單位附件設定', [
                'unit_id' => $unitId,
                'config' => $config
            ]);
            
            return true;
        } catch (PDOException $e) {
            $this->logger->error('更新單位附件設定失敗', [
                'unit_id' => $unitId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
```

## 9. 系統備份與還原規劃

### 9.1 備份策略

#### 9.1.1 資料庫備份腳本
```bash
#!/bin/bash

# 備份目錄
BACKUP_DIR="/backup/alleynote/db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# 建立備份
sqlite3 /var/www/alleynote/database.sqlite ".backup '$BACKUP_DIR/db_$TIMESTAMP.sqlite'"

# 壓縮備份
gzip "$BACKUP_DIR/db_$TIMESTAMP.sqlite"

# 刪除超過30天的備份
find $BACKUP_DIR -name "db_*.sqlite.gz" -mtime +30 -delete
```

#### 9.1.2 檔案系統備份設定
```yaml
backup:
  frequency: daily
  retention: 30
  paths:
    - /var/www/alleynote/uploads
    - /var/www/alleynote/storage
    - /etc/alleynote
  exclude:
    - "*.tmp"
    - "*.log"
  compression: gzip
  encryption: AES-256
```

## 10. 部署自動化腳本

### 10.1 部署腳本
```bash
#!/bin/bash

# 環境變數
ENV_FILE=".env"
DEPLOY_DIR="/var/www/alleynote"

# 更新程式碼
git pull origin main

# 安裝相依套件
composer install --no-dev

# 執行資料庫遷移
php artisan migrate --force

# 重建快取
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 更新檔案權限
chown -R www-data:www-data $DEPLOY_DIR
chmod -R 755 $DEPLOY_DIR/storage

# 重啟服務
systemctl restart php8.4-fpm
systemctl restart nginx
```

## 11. 除錯與監控工具

### 11.1 除錯工具設定
```php
class Debugger
{
    private const MAX_TRACE_DEPTH = 10;
    private const SENSITIVE_FIELDS = ['password', 'token', 'secret'];

    public static function dump($var, bool $sanitize = true): string
    {
        if ($sanitize) {
            $var = self::sanitizeData($var);
        }

        ob_start();
        var_dump($var);
        return ob_get_clean();
    }

    private static function sanitizeData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, self::SENSITIVE_FIELDS)) {
                    $data[$key] = '******';
                } else if (is_array($value)) {
                    $data[$key] = self::sanitizeData($value);
                }
            }
        }
        return $data;
    }
}
```

## 12. DevContainer 開發環境配置

### 12.1 基礎配置檔案結構

專案根目錄需包含以下 DevContainer 相關檔案：

```plaintext
.devcontainer/
├── devcontainer.json
├── docker-compose.yml
├── Dockerfile
└── config/
    ├── php.ini
    ├── xdebug.ini
    └── nginx.conf
```

### 12.2 DevContainer 配置檔案

#### 12.2.1 devcontainer.json
```json
{
  "name": "AlleyNote Development",
  "dockerComposeFile": "docker-compose.yml",
  "service": "app",
  "workspaceFolder": "/workspace",
  
  "customizations": {
    "vscode": {
      "extensions": [
        "xdebug.php-debug",
        "bmewburn.vscode-intelephense-client",
        "editorconfig.editorconfig",
        "esbenp.prettier-vscode",
        "mikestead.dotenv",
        "calebporzio.better-phpunit",
        "ms-azuretools.vscode-docker"
      ],
      "settings": {
        "php.validate.executablePath": "/usr/local/bin/php",
        "php.suggest.basic": false,
        "editor.formatOnSave": true,
        "editor.defaultFormatter": "esbenp.prettier-vscode"
      }
    }
  },
  
  "forwardPorts": [80, 9003],
  
  "postCreateCommand": "composer install && php artisan key:generate"
}
```

#### 12.2.2 docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build: 
      context: .
      dockerfile: Dockerfile
    volumes:
      - ..:/workspace:cached
      - ./config/php.ini:/usr/local/etc/php/php.ini
      - ./config/xdebug.ini:/usr/local/etc/php/conf.d/xdebug.ini
    command: sleep infinity
    
  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    volumes:
      - ..:/workspace:cached
      - ./config/nginx.conf:/etc/nginx/conf.d/default.conf
```

#### 12.2.3 Dockerfile
```dockerfile
FROM php:8.4.5-fpm

# 安裝必要套件
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# 安裝並配置 Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /workspace

# 設定使用者權限
RUN useradd -m vscode && \
    chown -R vscode:vscode /workspace
USER vscode
```

### 12.3 開發工具配置檔案

#### 12.3.1 PHP 配置 (php.ini)
```ini
[PHP]
memory_limit = 512M
post_max_size = 50M
upload_max_filesize = 50M
max_execution_time = 300
error_reporting = E_ALL
display_errors = On
display_startup_errors = On
log_errors = On
error_log = /dev/stderr
date.timezone = "Asia/Taipei"

[opcache]
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

#### 12.3.2 Xdebug 配置 (xdebug.ini)
```ini
[xdebug]
xdebug.mode = debug,develop
xdebug.start_with_request = yes
xdebug.client_host = host.docker.internal
xdebug.client_port = 9003
xdebug.idekey = VSCODE
xdebug.max_nesting_level = 500
xdebug.log = /tmp/xdebug.log
```

#### 12.3.3 Nginx 配置 (nginx.conf)
```nginx
server {
    listen 80;
    server_name localhost;
    root /workspace/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 12.4 開發環境使用說明

1. **環境建置步驟**
   - 安裝 Visual Studio Code
   - 安裝 Remote - Containers 擴充功能
   - 使用 VS Code 開啟專案資料夾
   - 選擇 "Reopen in Container" 啟動開發環境

2. **開發工作流程**
   - 程式碼在本機編輯，自動同步至容器
   - 使用整合式終端機執行指令
   - Xdebug 除錯功能已預先配置完成
   - 資料庫操作可使用 VS Code SQLite 擴充功能

3. **程式碼品質工具**
   ```bash
   # 執行程式碼風格檢查
   ./vendor/bin/phpcs

   # 執行靜態程式碼分析
   ./vendor/bin/phpstan analyse

   # 執行單元測試
   ./vendor/bin/phpunit
   ```

4. **常用指令**
   ```bash
   # 檢視環境資訊
   php -v
   composer -V

   # 安裝相依套件
   composer install

   # 建立新的 migration
   php artisan make:migration

   # 執行資料庫遷移
   php artisan migrate
   ```

### 12.5 開發環境維護

1. **容器映像檔更新**
   - 定期更新基礎映像檔
   - 依需求增加新的系統套件
   - 更新 PHP 擴充功能

2. **開發工具更新**
   - VS Code 擴充功能更新
   - Composer 相依套件更新
   - 開發工具配置優化

3. **效能最佳化**
   - 容器資源使用監控
   - 開發環境效能調校
   - 快取機制最佳化