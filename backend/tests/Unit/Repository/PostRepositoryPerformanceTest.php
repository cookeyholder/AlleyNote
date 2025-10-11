<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Infrastructure\Services\CacheService;
use DateTimeImmutable;
use DateTimeInterface;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Tests\Factory\PostFactory;
use Tests\TestCase;

class PostRepositoryPerformanceTest extends TestCase
{
    private PostRepository $repository;

    protected PDO $db;

    protected CacheService|MockInterface $cache;

    private LoggingSecurityServiceInterface|MockInterface $loggingSecurityService;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用 SQLite 記憶體資料庫進行測試
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試用資料表
        $this->createTestTables();

        // 模擬快取服務
        $this->cache = Mockery::mock(CacheService::class);
        $this->cache->shouldReceive('remember')
            ->byDefault()
            ->andReturnUsing(function ($key, $callback) {
                return $callback();
            });
        $this->cache->shouldReceive('delete')->byDefault();

        // LoggingSecurityServiceInterface mock
        $this->loggingSecurityService = Mockery::mock(LoggingSecurityServiceInterface::class);
        $this->loggingSecurityService->shouldReceive('logSecurityEvent')->byDefault();

        $this->repository = new PostRepository($this->db, $this->cache, $this->loggingSecurityService);
    }

    protected function createTestTables(): void
    {
        // 建立 users 資料表（用於 JOIN）
        $this->db->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ');

        // 插入測試用戶
        $now = date('Y-m-d H:i:s');
        $this->db->exec("
            INSERT INTO users (id, username, email, password, created_at, updated_at) VALUES
            (1, 'testuser', 'test@example.com', 'hashed_password', '$now', '$now')
        ");

        // 建立文章資料表
        $this->db->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                seq_number INTEGER,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip VARCHAR(45),
                is_pinned BOOLEAN DEFAULT 0,
                status VARCHAR(20) DEFAULT "draft",
                views INTEGER DEFAULT 0,
                publish_date DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME NULL,
                creation_source VARCHAR(20) DEFAULT "unknown",
                creation_source_detail TEXT NULL
            )
        ');

        // 建立索引
        $this->db->exec('CREATE INDEX idx_posts_status ON posts(status)');
        $this->db->exec('CREATE INDEX idx_posts_publish_date ON posts(publish_date)');
        $this->db->exec('CREATE INDEX idx_posts_is_pinned ON posts(is_pinned)');

        // 建立標籤資料表
        $this->db->exec('
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 建立文章標籤關聯表
        $this->db->exec('
            CREATE TABLE post_tags (
                post_id INTEGER,
                tag_id INTEGER,
                created_at DATETIME,
                PRIMARY KEY (post_id, tag_id)
            )
        ');

        // 建立文章瀏覽記錄表
        $this->db->exec('
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip VARCHAR(45) NOT NULL,
                view_date DATETIME NOT NULL
            )
        ');

        // 建立索引
        $this->db->exec('CREATE INDEX idx_post_views_post_id ON post_views(post_id)');
        $this->db->exec('CREATE INDEX idx_post_views_view_date ON post_views(view_date)');
    }

    public function testBulkInsertPerformance(): void
    {
        $startTime = microtime(true);
        $count = 100;

        for ($i = 0; $i < $count; $i++) {
            $data = PostFactory::make([
                'title' => "文章 {$i}",
                'content' => "內容 {$i}",
                'user_id' => 1,
            ]);
            $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $this->repository->create($data);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 轉換為毫秒

        $this->assertLessThan(100, $duration / $count, '每筆新增時間應小於 100ms');
    }

    public function testPaginationPerformance(): void
    {
        // 建立 1000 筆測試資料
        for ($i = 0; $i < 1000; $i++) {
            $data = PostFactory::make([
                'title' => "文章 {$i}",
                'content' => "內容 {$i}",
                'user_id' => 1,
            ]);
            $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $this->repository->create($data);
        }

        $startTime = microtime(true);
        $result = $this->repository->paginate(50, 10); // 取得第 50 頁，每頁 10 筆
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $duration, '分頁查詢時間應小於 50ms');
        $this->assertCount(10, $result['items']);
    }

    public function testSearchPerformance(): void
    {
        // 建立 1000 筆測試資料
        for ($i = 0; $i < 1000; $i++) {
            $data = PostFactory::make([
                'title' => "文章 {$i}",
                'content' => "內容 {$i}",
                'user_id' => 1,
                'status' => 'published',
            ]);
            $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
            $this->repository->create($data);
        }

        $startTime = microtime(true);
        $result = $this->repository->paginate(1, 10, ['status' => 'published']);
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $duration, '搜尋時間應小於 100ms');
    }

    public function testMultipleTagAssignmentPerformance(): void
    {
        $this->markTestSkipped('效能測試暫時跳過，等待事務管理問題解決');
    }

    public function testConcurrentViewsIncrementPerformance(): void
    {
        $this->markTestSkipped('效能測試暫時跳過，等待事務管理問題解決');
    }
}
