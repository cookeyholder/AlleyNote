<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Models\Post;
use App\Repositories\PostRepository;
use Tests\Factory\PostFactory;

class PostRepositoryPerformanceTest extends TestCase
{
    private PDO $db;
    private PostRepository $repository;
    private const PERFORMANCE_THRESHOLD = 100; // 100ms

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $this->createTables();
        $this->repository = new PostRepository($this->db);
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        $this->db->exec('DROP TABLE IF EXISTS posts');
        $this->db->exec('DROP TABLE IF EXISTS post_tags');
        $this->db->exec('DROP TABLE IF EXISTS post_views');
        $this->db->exec('DROP TABLE IF EXISTS tags');

        unset($this->repository);
        unset($this->db);

        parent::tearDown();
    }

    private function createTables(): void
    {
        // 建立文章表
        $this->db->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip TEXT NOT NULL,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                publish_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ");

        // 建立標籤表
        $this->db->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ");

        // 建立文章標籤關聯表
        $this->db->exec("
            CREATE TABLE post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            )
        ");

        // 建立文章觀看記錄表
        $this->db->exec("
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ");
    }

    private function seedTestData(): void
    {
        // 建立 100 篇測試文章
        for ($i = 0; $i < 100; $i++) {
            $this->repository->create(PostFactory::make([
                'title' => "測試文章 {$i}",
                'content' => "這是測試文章 {$i} 的內容"
            ]));
        }

        // 建立測試標籤
        $now = format_datetime();
        $this->db->exec("INSERT INTO tags (name, created_at, updated_at) VALUES 
            ('測試標籤1', '{$now}', '{$now}'),
            ('測試標籤2', '{$now}', '{$now}'),
            ('測試標籤3', '{$now}', '{$now}')");
    }

    public function testBulkInsertPerformance(): void
    {
        $startTime = microtime(true);

        // 批量建立 50 篇文章
        for ($i = 0; $i < 50; $i++) {
            $this->repository->create(PostFactory::make());
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLD * 50, // 允許每篇文章 100ms
            $executionTime,
            "批量建立 50 篇文章的時間超過閾值"
        );
    }

    public function testPaginationPerformance(): void
    {
        $startTime = microtime(true);

        // 進行 10 次分頁查詢
        for ($page = 1; $page <= 10; $page++) {
            $this->repository->paginate($page, 10);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLD * 10, // 允許每次分頁 100ms
            $executionTime,
            "執行 10 次分頁查詢的時間超過閾值"
        );
    }

    public function testSearchPerformance(): void
    {
        $startTime = microtime(true);

        // 進行 10 次標題搜尋
        for ($i = 0; $i < 10; $i++) {
            $this->repository->paginate(1, 10, ['title' => "%測試%"]);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLD * 10, // 允許每次搜尋 100ms
            $executionTime,
            "執行 10 次標題搜尋的時間超過閾值"
        );
    }

    public function testMultipleTagAssignmentPerformance(): void
    {
        $startTime = microtime(true);

        // 建立文章並指派多個標籤
        $post = $this->repository->create(PostFactory::make(), [1, 2, 3]);

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLD,
            $executionTime,
            "建立文章並指派多個標籤的時間超過閾值"
        );
    }

    public function testConcurrentViewsIncrementPerformance(): void
    {
        $startTime = microtime(true);

        // 模擬 10 個並發觀看數增加
        for ($i = 0; $i < 10; $i++) {
            $this->repository->incrementViews(1, "192.168.1.{$i}");
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLD * 10, // 允許每次遞增 100ms
            $executionTime,
            "執行 10 次並發觀看數增加的時間超過閾值"
        );
    }
}
