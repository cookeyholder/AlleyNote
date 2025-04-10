# AlleyNote 公布欄網站實作規劃

## 0. 測試驅動開發方法論

### 0.1 測試架構設計

在三層式架構中，每一層都有其對應的測試類型：

1. **資料存取層測試**
   ```php
   namespace Tests\Unit\Repositories;
   
   class PostRepositoryTest extends TestCase
   {
       private PDO $db;
       private PostRepository $repository;
       
       protected function setUp(): void
       {
           parent::setUp();
           $this->db = new PDO('sqlite::memory:');
           $this->repository = new PostRepository($this->db);
       }
       
       /** @test */
       public function shouldCreateNewPost(): void
       {
           // 安排
           $data = [
               'title' => '測試文章',
               'content' => '測試內容'
           ];
           
           // 執行
           $post = $this->repository->create($data);
           
           // 驗證
           $this->assertNotNull($post['id']);
           $this->assertEquals('測試文章', $post['title']);
       }
   }
   ```

2. **業務邏輯層測試**
   ```php
   namespace Tests\Unit\Services;
   
   class PostServiceTest extends TestCase
   {
       private PostRepository $repository;
       private PostService $service;
       
       protected function setUp(): void
       {
           parent::setUp();
           $this->repository = $this->createMock(PostRepository::class);
           $this->service = new PostService($this->repository);
       }
       
       /** @test */
       public function shouldValidatePostData(): void
       {
           // 安排
           $invalidData = [
               'title' => '', // 空標題應該被拒絕
               'content' => '內容'
           ];
           
           // 驗證
           $this->expectException(ValidationException::class);
           
           // 執行
           $this->service->createPost($invalidData);
       }
   }
   ```

3. **表現層測試**
   ```php
   namespace Tests\Integration;
   
   class PostControllerTest extends TestCase
   {
       private PostService $service;
       
       protected function setUp(): void
       {
           parent::setUp();
           $this->service = $this->app->make(PostService::class);
       }
       
       /** @test */
       public function shouldReturnCreatedPost(): void
       {
           // 安排
           $data = [
               'title' => '測試文章',
               'content' => '測試內容'
           ];
           
           // 執行
           $response = $this->postJson('/api/posts', $data);
           
           // 驗證
           $response->assertStatus(201)
                   ->assertJsonStructure([
                       'data' => ['id', 'title', 'content']
                   ]);
       }
   }
   ```

### 0.2 TDD 開發流程

在三層式架構中，每個功能的開發都遵循由內而外的測試驅動開發流程：

1. **資料存取層 TDD**
   - 撰寫 Repository 測試
   - 實作最小可行的資料存取功能
   - 重構並確保測試通過

2. **業務邏輯層 TDD**
   - 撰寫 Service 測試（使用 Repository Mock）
   - 實作業務規則和驗證邏輯
   - 重構並確保測試通過

3. **表現層 TDD**
   - 撰寫 Controller 測試
   - 實作路由和請求處理
   - 重構並確保測試通過

### 0.3 測試替身使用原則

1. **資料存取層**
   - 使用 SQLite 記憶體資料庫進行測試
   - 每個測試後清理資料
   - 使用交易確保測試資料隔離

2. **業務邏輯層**
   - 使用 Mock Repository 模擬資料存取
   - 使用 Stub 回傳預設資料
   - 使用 Spy 驗證方法呼叫

3. **表現層**
   - 使用整合測試驗證完整流程
   - 模擬 HTTP 請求和回應
   - 驗證 JSON 結構和狀態碼

### 0.4 測試資料管理

1. **測試資料工廠**
   ```php
   namespace Tests\Factories;
   
   class PostFactory
   {
       public static function make(array $overrides = []): array
       {
           return array_merge([
               'title' => '預設測試標題',
               'content' => '預設測試內容',
               'user_id' => 1
           ], $overrides);
       }
   }
   ```

2. **測試資料庫遷移**
   ```php
   namespace Tests\Database;
   
   class TestMigration
   {
       public function up(PDO $db): void
       {
           $db->exec("
               CREATE TABLE posts (
                   id INTEGER PRIMARY KEY AUTOINCREMENT,
                   title TEXT NOT NULL,
                   content TEXT NOT NULL,
                   user_id INTEGER NOT NULL,
                   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
               )
           ");
       }
   }
   ```

### 0.5 測試驗證規則

1. **Repository 測試規則**
   - 必須測試所有 CRUD 操作
   - 必須測試異常情況（如：記錄不存在）
   - 必須驗證資料完整性

2. **Service 測試規則**
   - 必須測試所有業務規則
   - 必須測試所有驗證規則
   - 必須測試異常處理
   - 必須驗證資料轉換邏輯

3. **Controller 測試規則**
   - 必須測試所有 HTTP 方法
   - 必須測試授權規則
   - 必須測試請求驗證
   - 必須測試回應格式

### 0.6 測試覆蓋率要求

1. **最低覆蓋率要求**
   - Repository 層：90%
   - Service 層：85%
   - Controller 層：80%

2. **關鍵路徑測試**
   - 使用者認證流程：100%
   - 資料驗證邏輯：100%
   - 權限檢查邏輯：100%

3. **效能測試基準**
   - 單元測試執行時間 < 100ms
   - 整合測試執行時間 < 500ms

## 1. 系統架構

### 1.1 三層式架構

系統採用簡單的三層式架構，確保邏輯和資料的分離：

1. **資料存取層 (Data Access Layer)**
   ```php
   namespace App\Repositories;
   
   class PostRepository
   {
       private PDO $db;
       
       public function __construct(PDO $db)
       {
           $this->db = $db;
       }
       
       // 純資料庫操作，不包含業務邏輯
       public function find(string $uuid): ?array
       {
           $stmt = $this->db->prepare('SELECT * FROM posts WHERE uuid = ?');
           $stmt->execute([$uuid]);
           return $stmt->fetch(PDO::FETCH_ASSOC);
       }
   }
   ```

2. **業務邏輯層 (Business Layer)**
   ```php
   namespace App\Services;
   
   class PostService
   {
       private PostRepository $repository;
       
       public function __construct(PostRepository $repository)
       {
           $this->repository = $repository;
       }
       
       // 包含業務規則和邏輯
       public function createPost(array $data): array
       {
           // 驗證資料
           $this->validatePost($data);
           
           // 處理業務邏輯
           $data['uuid'] = $this->generateUuid();
           $data['created_at'] = date('Y-m-d H:i:s');
           
           // 儲存資料
           return $this->repository->create($data);
       }
   }
   ```

3. **表現層 (Presentation Layer)**
   ```php
   namespace App\Controllers;
   
   class PostController
   {
       private PostService $service;
       
       public function __construct(PostService $service)
       {
           $this->service = $service;
       }
       
       // 處理 HTTP 請求和回應
       public function create(): Response
       {
           $data = $this->validateRequest();
           $post = $this->service->createPost($data);
           return new JsonResponse($post);
       }
   }
   ```

### 1.2 檔案儲存分離

1. **檔案儲存結構**
   ```
   /var/www/alleynote/
   ├── public/           # 網站公開檔案
   ├── storage/          # 資料儲存目錄
   │   ├── app/         # 應用程式檔案
   │   ├── files/       # 上傳檔案
   │   └── backups/     # 備份檔案
   └── database/        # 資料庫檔案
       └── alleynote.db # SQLite 資料庫
   ```

2. **檔案處理服務**
   ```php
   namespace App\Services;
   
   class FileService
   {
       private string $storagePath;
       
       public function __construct(string $storagePath)
       {
           $this->storagePath = $storagePath;
       }
       
       // 處理檔案上傳
       public function storeFile(UploadedFile $file): string
       {
           $filename = $this->generateUniqueFilename($file);
           $file->move($this->storagePath, $filename);
           return $filename;
       }
   }
   ```

### 1.3 備份機制

1. **資料庫備份**
   ```bash
   #!/bin/bash
   # backup_db.sh
   
   BACKUP_DIR="/var/www/alleynote/storage/backups/database"
   DATE=$(date +%Y%m%d_%H%M%S)
   
   # 建立備份
   sqlite3 /var/www/alleynote/database/alleynote.db ".backup '$BACKUP_DIR/db_$DATE.sqlite'"
   
   # 壓縮備份
   gzip "$BACKUP_DIR/db_$DATE.sqlite"
   
   # 保留最近 30 天的備份
   find $BACKUP_DIR -name "db_*.sqlite.gz" -mtime +30 -delete
   ```

2. **檔案備份**
   ```bash
   #!/bin/bash
   # backup_files.sh
   
   BACKUP_DIR="/var/www/alleynote/storage/backups/files"
   DATE=$(date +%Y%m%d_%H%M%S)
   
   # 建立備份
   tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/alleynote/storage/files
   
   # 保留最近 30 天的備份
   find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete
   ```

### 1.4 定期工作排程

```crontab
# 資料庫每日備份
0 1 * * * /var/www/alleynote/scripts/backup_db.sh

# 檔案每週備份
0 2 * * 0 /var/www/alleynote/scripts/backup_files.sh
```

## 2. 詳細實作規範

### 2.1 資料存取規範

1. **Repository 模式**
   - 所有資料庫操作都必須通過 Repository 類別
   - Repository 只負責資料存取，不包含業務邏輯
   - 使用參數化查詢防止 SQL 注入

2. **資料驗證**
   - 在業務邏輯層進行資料驗證
   - 使用驗證器模式集中處理驗證規則
   - 回傳明確的錯誤訊息

### 2.2 業務邏輯規範

1. **Service 模式**
   - 業務邏輯集中在 Service 類別中
   - Service 通過建構子注入相依物件
   - 保持方法單一職責

2. **交易處理**
   - 在 Service 層處理資料庫交易
   - 確保資料一致性
   - 錯誤發生時自動回滾

### 2.3 檔案處理規範

1. **檔案上傳**
   - 驗證檔案類型和大小
   - 產生唯一檔名
   - 使用串流處理大型檔案

2. **檔案儲存**
   - 依照日期建立目錄結構
   - 使用相對路徑儲存檔案資訊
   - 定期清理暫存檔案

### 2.4 資料存取層詳細實作規範

#### 2.4.1 資料庫連線管理

##### 資料庫連線器設計
```php
namespace App\Database;

class DatabaseConnection
{
    private static ?PDO $instance = null;
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'sqlite:%s/database/alleynote.db',
                getenv('APP_ROOT')
            );
            self::$instance = new PDO($dsn, null, null, self::$options);
        }
        return self::$instance;
    }

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    public static function rollBack(): void
    {
        self::getInstance()->rollBack();
    }
}
```

##### 資料庫交易管理器
```php
namespace App\Database;

class TransactionManager
{
    public static function execute(callable $callback)
    {
        DatabaseConnection::beginTransaction();
        try {
            $result = $callback();
            DatabaseConnection::commit();
            return $result;
        } catch (\Exception $e) {
            DatabaseConnection::rollBack();
            throw $e;
        }
    }
}
```

#### 2.4.2 Repository 基礎類別

##### 抽象 Repository
```php
namespace App\Repositories;

abstract class AbstractRepository
{
    protected PDO $db;
    protected string $table;
    protected array $fillable = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    protected function create(array $data): array
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($fields));
        
        return $this->find($this->db->lastInsertId());
    }

    protected function find(string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    protected function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE $column = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    protected function findWhere(array $conditions): array
    {
        $where = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $where[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function update(string $id, array $data): bool
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $set = implode(', ', array_map(fn($field) => "$field = ?", array_keys($fields)));
        
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([...array_values($fields), $id]);
    }

    protected function delete(string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    protected function paginate(int $page, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        
        // 取得總記錄數
        $total = $this->db->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
        
        // 取得分頁資料
        $sql = "SELECT * FROM {$this->table} LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll();
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    protected function commit(): void
    {
        $this->db->commit();
    }

    protected function rollBack(): void
    {
        $this->db->rollBack();
    }
}
```

#### 2.4.3 具體 Repository 實作

##### 文章 Repository
```php
namespace App\Repositories;

class PostRepository extends AbstractRepository
{
    protected string $table = 'posts';
    protected array $fillable = [
        'uuid',
        'title',
        'content',
        'user_id',
        'user_ip',
        'status',
        'is_pinned',
        'publish_date'
    ];

    public function createPost(array $data): array
    {
        return TransactionManager::execute(function () use ($data) {
            // 產生流水號
            $seqNumber = $this->generateSequenceNumber();
            $data['seq_number'] = $seqNumber;
            
            // 建立文章
            $post = $this->create($data);
            
            // 處理標籤
            if (!empty($data['tags'])) {
                $this->attachTags($post['id'], $data['tags']);
            }
            
            return $post;
        });
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->findBy('uuid', $uuid);
    }

    public function findBySequenceNumber(string $seqNumber): ?array
    {
        return $this->findBy('seq_number', $seqNumber);
    }

    public function getPinnedPosts(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_pinned = 1 ORDER BY publish_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchPosts(array $criteria): array
    {
        $where = [];
        $params = [];
        
        if (!empty($criteria['title'])) {
            $where[] = "title LIKE ?";
            $params[] = "%{$criteria['title']}%";
        }
        
        if (!empty($criteria['tags'])) {
            $tagIds = implode(',', $criteria['tags']);
            $where[] = "id IN (SELECT post_id FROM post_tags WHERE tag_id IN ($tagIds))";
        }
        
        if (!empty($criteria['status'])) {
            $where[] = "status = ?";
            $params[] = $criteria['status'];
        }
        
        if (!empty($criteria['date_from'])) {
            $where[] = "publish_date >= ?";
            $params[] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $where[] = "publish_date <= ?";
            $params[] = $criteria['date_to'];
        }
        
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($criteria['sort'])) {
            $sql .= " ORDER BY {$criteria['sort']} " . 
                   ($criteria['order'] ?? 'DESC');
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function generateSequenceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT MAX(seq_number) FROM {$this->table} " .
               "WHERE strftime('%Y', created_at) = ? " .
               "AND strftime('%m', created_at) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year, $month]);
        $maxSeq = $stmt->fetchColumn() ?? 0;
        
        $nextSeq = $maxSeq + 1;
        return sprintf('%s%s%05d', $year, $month, $nextSeq);
    }

    private function attachTags(int $postId, array $tagIds): void
    {
        $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId]);
        }
    }
}
```

#### 2.4.4 Repository 測試案例

##### 基本 CRUD 測試
```php
namespace Tests\Unit\Repositories;

class PostRepositoryTest extends TestCase
{
    private PDO $db;
    private PostRepository $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->setupTestDatabase();
        $this->repository = new PostRepository($this->db);
    }
    
    /** @test */
    public function shouldCreatePostWithTags(): void
    {
        // 安排
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'status' => 1,
            'tags' => [1, 2, 3]
        ];
        
        // 執行
        $post = $this->repository->createPost($data);
        
        // 驗證文章基本資料
        $this->assertNotNull($post['id']);
        $this->assertEquals('測試文章', $post['title']);
        
        // 驗證流水號格式
        $this->assertMatchesRegularExpression(
            '/^\d{6}\d{5}$/',
            $post['seq_number']
        );
        
        // 驗證標籤關聯
        $sql = "SELECT COUNT(*) FROM post_tags WHERE post_id = ?";
        $tagCount = $this->db->prepare($sql)->execute([$post['id']])
            ->fetchColumn();
        $this->assertEquals(3, $tagCount);
    }
    
    /** @test */
    public function shouldFindPostByUuid(): void
    {
        // 安排
        $uuid = 'test-uuid';
        $this->insertTestPost(['uuid' => $uuid]);
        
        // 執行
        $post = $this->repository->findByUuid($uuid);
        
        // 驗證
        $this->assertNotNull($post);
        $this->assertEquals($uuid, $post['uuid']);
    }
    
    /** @test */
    public function shouldReturnNullForNonexistentPost(): void
    {
        // 執行
        $post = $this->repository->findByUuid('non-existent');
        
        // 驗證
        $this->assertNull($post);
    }
    
    /** @test */
    public function shouldSearchPostsByMultipleCriteria(): void
    {
        // 安排
        $this->insertTestPosts();
        
        // 執行
        $criteria = [
            'title' => '測試',
            'tags' => [1],
            'date_from' => '2025-01-01',
            'sort' => 'publish_date',
            'order' => 'DESC'
        ];
        $posts = $this->repository->searchPosts($criteria);
        
        // 驗證
        $this->assertNotEmpty($posts);
        foreach ($posts as $post) {
            $this->assertStringContains('測試', $post['title']);
            $this->assertGreaterThanOrEqual('2025-01-01', $post['publish_date']);
        }
    }
    
    /** @test */
    public function shouldRollbackTransactionOnError(): void
    {
        // 安排
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'tags' => [999] // 不存在的標籤 ID
        ];
        
        // 執行與驗證
        $this->expectException(\PDOException::class);
        $this->repository->createPost($data);
        
        // 驗證文章未被建立
        $sql = "SELECT COUNT(*) FROM posts";
        $count = $this->db->query($sql)->fetchColumn();
        $this->assertEquals(0, $count);
    }
    
    private function setupTestDatabase(): void
    {
        $this->db->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                seq_number TEXT NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                publish_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )
        ");
        
        $this->db->exec("
            CREATE TABLE post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts (id),
                FOREIGN KEY (tag_id) REFERENCES tags (id)
            )
        ");
    }
    
    private function insertTestPost(array $data): void
    {
        $sql = "INSERT INTO posts (uuid, seq_number, title, content, user_id) 
                VALUES (:uuid, '202504001', :title, :content, :user_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid' => $data['uuid'] ?? 'test-uuid',
            'title' => $data['title'] ?? '測試文章',
            'content' => $data['content'] ?? '測試內容',
            'user_id' => $data['user_id'] ?? 1
        ]);
    }
    
    private function insertTestPosts(): void
    {
        // 插入測試標籤
        $this->db->exec("INSERT INTO tags (id, name) VALUES (1, '測試標籤')");
        
        // 插入多筆測試文章
        for ($i = 1; $i <= 5; $i++) {
            $this->insertTestPost([
                'uuid' => "test-uuid-$i",
                'title' => "測試文章 $i",
                'content' => "測試內容 $i"
            ]);
            
            // 關聯標籤
            $postId = $this->db->lastInsertId();
            $this->db->exec("INSERT INTO post_tags (post_id, tag_id) 
                            VALUES ($postId, 1)");
        }
    }
}
```

#### 2.4.5 資料驗證與例外處理

##### 驗證器介面
```php
namespace App\Validation;

interface ValidatorInterface
{
    public function validate(array $data): array;
    public function getErrors(): array;
    public function isValid(): bool;
}
```

##### 文章驗證器實作
```php
namespace App\Validation;

class PostValidator implements ValidatorInterface
{
    private array $errors = [];
    private array $rules = [
        'title' => [
            'required' => true,
            'min_length' => 3,
            'max_length' => 255
        ],
        'content' => [
            'required' => true,
            'min_length' => 10
        ],
        'user_id' => [
            'required' => true,
            'numeric' => true
        ],
        'tags' => [
            'array' => true,
            'min_count' => 1,
            'max_count' => 5
        ]
    ];

    public function validate(array $data): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            foreach ($rules as $rule => $parameter) {
                $method = "validate" . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $data[$field] ?? null, $parameter);
                }
            }
        }

        return $this->errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    private function validateRequired(string $field, $value, bool $required): void
    {
        if ($required && empty($value)) {
            $this->errors[$field][] = "$field 不能為空";
        }
    }

    private function validateMinLength(string $field, $value, int $min): void
    {
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field][] = "$field 長度不能小於 $min 個字元";
        }
    }

    private function validateMaxLength(string $field, $value, int $max): void
    {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field][] = "$field 長度不能大於 $max 個字元";
        }
    }

    private function validateNumeric(string $field, $value, bool $numeric): void
    {
        if ($numeric && !is_numeric($value)) {
            $this->errors[$field][] = "$field 必須是數字";
        }
    }

    private function validateArray(string $field, $value, bool $isArray): void
    {
        if ($isArray && !is_array($value)) {
            $this->errors[$field][] = "$field 必須是陣列";
        }
    }

    private function validateMinCount(string $field, $value, int $min): void
    {
        if (is_array($value) && count($value) < $min) {
            $this->errors[$field][] = "$field 至少要有 $min 個項目";
        }
    }

    private function validateMaxCount(string $field, $value, int $max): void
    {
        if (is_array($value) && count($value) > $max) {
            $this->errors[$field][] = "$field 最多只能有 $max 個項目";
        }
    }
}
```

##### 例外處理基礎類別
```php
namespace App\Exceptions;

abstract class BaseException extends \Exception
{
    protected array $errors = [];

    public function __construct(string $message = "", array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

class ValidationException extends BaseException {}
class ResourceNotFoundException extends BaseException {}
class DatabaseException extends BaseException {}
```

#### 2.4.6 效能最佳化策略

##### 查詢快取管理器
```php
namespace App\Cache;

class QueryCache
{
    private static array $cache = [];
    private static array $ttl = [];

    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        if (self::has($key) && !self::isExpired($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    public static function put(string $key, $value, int $ttl): void
    {
        self::$cache[$key] = $value;
        self::$ttl[$key] = time() + $ttl;
    }

    public static function get(string $key)
    {
        return self::$cache[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }

    public static function forget(string $key): void
    {
        unset(self::$cache[$key], self::$ttl[$key]);
    }

    public static function isExpired(string $key): bool
    {
        return !isset(self::$ttl[$key]) || time() > self::$ttl[$key];
    }

    public static function flush(): void
    {
        self::$cache = [];
        self::$ttl = [];
    }
}
```

##### 使用快取範例
```php
public function getPinnedPosts(): array
{
    $cacheKey = 'pinned_posts';
    
    return QueryCache::remember($cacheKey, function () {
        $sql = "SELECT * FROM {$this->table} WHERE is_pinned = 1 
                ORDER BY publish_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }, 300); // 5分鐘快取
}
```

##### 批次處理範例
```php
public function batchInsertTags(array $tags, int $batchSize = 100): void
{
    $chunks = array_chunk($tags, $batchSize);
    
    foreach ($chunks as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '(?)'));
        $sql = "INSERT INTO tags (name) VALUES $placeholders";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_column($chunk, 'name'));
    }
}
```

##### 延遲載入關聯
```php
public function getPostWithTags(int $postId): array
{
    $post = $this->find($postId);
    if (!$post) {
        return [];
    }
    
    // 只有真正需要標籤資料時才載入
    $post['tags'] = function () use ($postId) {
        $sql = "SELECT t.* FROM tags t
                JOIN post_tags pt ON pt.tag_id = t.id
                WHERE pt.post_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    };
    
    return $post;
}
```

### 2.5 業務邏輯層詳細實作規範

#### 2.5.1 文章服務實作
```php
namespace App\Services;

class PostService
{
    private PostRepository $repository;
    private PostValidator $validator;
    
    public function __construct(PostRepository $repository, PostValidator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }
    
    public function createPost(array $data): array
    {
        // 驗證輸入資料
        $this->validator->validate($data);
        
        // 產生 UUID
        $data['uuid'] = Uuid::uuid4()->toString();
        
        // 設定發布狀態
        $data['status'] = $data['publish_immediately'] ?? false 
            ? PostStatus::PUBLISHED 
            : PostStatus::DRAFT;
            
        // 設定 IP 位址
        $data['user_ip'] = request()->ip();
        
        // 建立文章
        return $this->repository->createPost($data);
    }
    
    public function updatePost(string $uuid, array $data): array
    {
        // 取得現有文章
        $post = $this->repository->findByUuid($uuid);
        if (!$post) {
            throw new PostNotFoundException();
        }
        
        // 驗證更新資料
        $this->validator->validateUpdate($data);
        
        // 更新文章
        $this->repository->update($post['id'], $data);
        
        return $this->repository->findByUuid($uuid);
    }
    
    public function searchPosts(array $criteria): array
    {
        // 處理搜尋條件
        $criteria = $this->processSearchCriteria($criteria);
        
        // 執行搜尋
        return $this->repository->searchPosts($criteria);
    }
    
    private function processSearchCriteria(array $criteria): array
    {
        // 處理日期範圍
        if (!empty($criteria['date_range'])) {
            [$from, $to] = explode(',', $criteria['date_range']);
            $criteria['date_from'] = $from;
            $criteria['date_to'] = $to;
            unset($criteria['date_range']);
        }
        
        // 處理排序
        if (!empty($criteria['sort'])) {
            $criteria['order'] = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';
            $criteria['sort'] = ltrim($criteria['sort'], '-');
        }
        
        return $criteria;
    }
}
```

### 2.6 表現層詳細實作規範

#### 2.6.1 API 控制器實作
```php
namespace App\Controllers;

class PostController
{
    private PostService $service;
    
    public function __construct(PostService $service)
    {
        $this->service = $service;
    }
    
    public function index(Request $request): Response
    {
        try {
            $posts = $this->service->searchPosts($request->all());
            return new JsonResponse(['data' => $posts]);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'error' => '無效的搜尋條件',
                'details' => $e->getErrors()
            ], 400);
        }
    }
    
    public function store(Request $request): Response
    {
        try {
            $post = $this->service->createPost($request->all());
            return new JsonResponse(['data' => $post], 201);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'error' => '無效的文章資料',
                'details' => $e->getErrors()
            ], 400);
        }
    }
    
    public function update(string $uuid, Request $request): Response
    {
        try {
            $post = $this->service->updatePost($uuid, $request->all());
            return new JsonResponse(['data' => $post]);
        } catch (PostNotFoundException $e) {
            return new JsonResponse([
                'error' => '找不到指定的文章'
            ], 404);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'error' => '無效的文章資料',
                'details' => $e->getErrors()
            ], 400);
        }
    }
}
```

### 2.7 系統監控與效能最佳化

#### 2.7.1 效能監控實作
```php
namespace App\Monitoring;

class PerformanceMonitor
{
    private static array $queries = [];
    private static array $timings = [];
    
    public static function logQuery(string $sql, float $time): void
    {
        self::$queries[] = [
            'sql' => $sql,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
    }
    
    public static function startTiming(string $key): void
    {
        self::$timings[$key] = [
            'start' => microtime(true)
        ];
    }
    
    public static function endTiming(string $key): void
    {
        if (isset(self::$timings[$key])) {
            self::$timings[$key]['end'] = microtime(true);
            self::$timings[$key]['duration'] = 
                self::$timings[$key]['end'] - self::$timings[$key]['start'];
        }
    }
    
    public static function getMetrics(): array
    {
        return [
            'queries' => self::$queries,
            'timings' => self::$timings,
            'memory' => [
                'current' => memory_get_usage(),
                'peak' => memory_get_peak_usage()
            ]
        ];
    }
}
```

#### 2.7.2 快取策略實作
```php
namespace App\Cache;

class PostCache
{
    private Cache $cache;
    private int $ttl;
    
    public function __construct(Cache $cache, int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
    public function remember(string $key, callable $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }
        
        $value = $callback();
        $this->cache->put($key, $value, $this->ttl);
        
        return $value;
    }
    
    public function invalidate(string $key): void
    {
        $this->cache->forget($key);
    }
    
    public function invalidatePattern(string $pattern): void
    {
        $keys = $this->cache->getKeys($pattern);
        foreach ($keys as $key) {
            $this->invalidate($key);
        }
    }
}
```

#### 2.7.3 系統監控配置
```php
return [
    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'response_time' => true,
            'memory_usage' => true,
            'sql_queries' => true,
            'cache_hits' => true
        ],
        'thresholds' => [
            'slow_query' => 1000, // 毫秒
            'high_memory' => 64 * 1024 * 1024, // 64MB
            'max_execution_time' => 5000 // 毫秒
        ],
        'alerting' => [
            'enabled' => true,
            'channels' => ['slack', 'email'],
            'threshold_exceeded' => true,
            'error_occurred' => true
        ]
    ]
];
```