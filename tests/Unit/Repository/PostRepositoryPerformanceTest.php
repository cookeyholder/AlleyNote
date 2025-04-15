<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use PDO;
use App\Services\CacheService;
use App\Repositories\PostRepository;
use Tests\Factory\PostFactory;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PostRepositoryPerformanceTest extends MockeryTestCase
{
    private PostRepository $repository;
    private PDO $db;
    private CacheService|MockInterface $cache;

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

        $this->repository = new PostRepository($this->db, $this->cache);
    }

    private function createTestTables(): void
    {
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
                updated_at DATETIME
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
                name VARCHAR(50) NOT NULL
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
                'user_id' => 1
            ]);
            $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
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
                'user_id' => 1
            ]);
            $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
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
                'status' => 'published'
            ]);
            $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
            $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
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
        // 建立測試用標籤
        for ($i = 1; $i <= 10; $i++) {
            $this->db->exec("INSERT INTO tags (id, name) VALUES ({$i}, '標籤 {$i}')");
        }

        $data = PostFactory::make(['user_id' => 1]);
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);
        $tagIds = range(1, 10);

        $startTime = microtime(true);
        $result = $this->repository->setTags($post->getId(), $tagIds);
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertTrue($result);
        $this->assertLessThan(100, $duration, '標籤指派時間應小於 100ms');
    }

    public function testConcurrentViewsIncrementPerformance(): void
    {
        $data = PostFactory::make(['user_id' => 1]);
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);
        $concurrentCount = 10;
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentCount; $i++) {
            $this->repository->incrementViews(
                $post->getId(),
                "192.168.1.{$i}",
                $i + 1
            );
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        $averageDuration = $duration / $concurrentCount;

        $this->assertLessThan(50, $averageDuration, '平均每次瀏覽次數更新時間應小於 50ms');

        $updatedPost = $this->repository->find($post->getId());
        $this->assertEquals($concurrentCount, $updatedPost->getViewCount());
    }
}
