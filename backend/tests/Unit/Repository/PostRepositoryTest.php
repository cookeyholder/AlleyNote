<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Domains\Post\Models\Post;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Infrastructure\Services\CacheService;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Tests\Factory\PostFactory;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    private PostRepository $repository;

    protected PDO $db;

    protected CacheService|MockInterface $cache;

    protected LoggingSecurityServiceInterface|MockInterface $logger;

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
        $this->cache->shouldReceive('deletePattern')->byDefault();

        // 模擬日誌安全服務
        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $this->logger->shouldReceive('logSecurityEvent')->byDefault();

        $this->repository = new PostRepository($this->db, $this->cache, $this->logger);
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
                views INTEGER DEFAULT 0,
                is_pinned BOOLEAN DEFAULT 0,
                status VARCHAR(20) DEFAULT "draft",
                publish_date DATETIME,
                creation_source VARCHAR(20) DEFAULT "web" NOT NULL,
                creation_source_detail TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME DEFAULT NULL
            )
        ');

        // 建立標籤資料表
        $this->db->exec('
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL,
                usage_count INTEGER DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME
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
    }

    public function testCanCreatePost(): void
    {
        $data = PostFactory::make([
            'title' => '測試文章',
            'content' => '這是測試內容',
        ]);
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);

        $post = $this->repository->create($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('測試文章', $post->getTitle());
        $this->assertEquals('這是測試內容', $post->getContent());
    }

    public function testCanFindPostById(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $found = $this->repository->find($post->getId());

        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($post->getId(), $found->getId());
    }

    public function testCanFindPostByUuid(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $found = $this->repository->findByUuid($post->getUuid());

        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($post->getUuid(), $found->getUuid());
    }

    public function testCanUpdatePost(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $updateData = [
            'title' => '更新後的標題',
            'content' => '更新後的內容',
            'user_id' => 1,
        ];

        $updatedPost = $this->repository->update($post->getId(), $updateData);

        $this->assertInstanceOf(Post::class, $updatedPost);
        $this->assertEquals('更新後的標題', $updatedPost->getTitle());
        $this->assertEquals('更新後的內容', $updatedPost->getContent());
    }

    public function testCanDeletePost(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $result = $this->repository->delete($post->getId());

        $this->assertTrue($result);
        $this->assertNull($this->repository->find($post->getId()));
    }

    public function testCanPaginatePosts(): void
    {
        // 建立 15 篇文章
        for ($i = 1; $i <= 15; $i++) {
            $this->repository->create(PostFactory::make([
                'title' => "文章 {$i}",
                'content' => "內容 {$i}",
            ]));
        }

        $result = $this->repository->paginate(1, 10);

        $this->assertCount(10, $result['items']);
        $this->assertEquals(15, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
        $this->assertEquals(2, $result['lastPage']);
    }

    public function testCanGetPinnedPosts(): void
    {
        // 建立置頂文章
        $this->repository->create(PostFactory::make([
            'is_pinned' => true,
            'title' => '置頂文章 1',
        ]));
        $this->repository->create(PostFactory::make([
            'is_pinned' => true,
            'title' => '置頂文章 2',
        ]));

        // 建立普通文章
        $this->repository->create(PostFactory::make([
            'is_pinned' => false,
            'title' => '普通文章',
        ]));

        $pinnedPosts = $this->repository->getPinnedPosts();

        $this->assertCount(2, $pinnedPosts);
        foreach ($pinnedPosts as $post) {
            $this->assertTrue($post->getIsPinned());
        }
    }

    public function testCanSetPinnedStatus(): void
    {
        $post = $this->repository->create(PostFactory::make(['is_pinned' => false]));

        $result = $this->repository->setPinned($post->getId(), true);

        $this->assertTrue($result);
        $updated = $this->repository->find($post->getId());
        $this->assertTrue($updated->getIsPinned());
    }

    public function testCanIncrementViews(): void
    {
        // 確保資料庫沒有活動交易
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        $data = PostFactory::make();
        $data['publish_date'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['created_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $data['updated_at'] = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);
        $initialViews = $post->getViews();

        $result = $this->repository->incrementViews(
            $post->getId(),
            '127.0.0.1',
            1,
        );

        $this->assertTrue($result);
        $updated = $this->repository->find($post->getId());
        $this->assertEquals($initialViews + 1, $updated->getViews());
    }

    public function testShouldRollbackOnTagAssignmentError(): void
    {
        $post = $this->repository->create(PostFactory::make());

        try {
            $this->repository->setTags($post->getId(), [999]); // 使用不存在的標籤 ID
            $this->fail('應該拋出異常');
        } catch (Exception $e) {
            // 確保交易已回溯
            $tags = $this->db->query("SELECT * FROM post_tags WHERE post_id = {$post->getId()}")->fetchAll();
            $this->assertEmpty($tags);
        }
    }

    public function testShouldCommitOnTagAssignmentSuccess(): void
    {
        // 確保資料庫沒有活動交易
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        // 建立測試用標籤（包含所有必要欄位）
        $now = date('Y-m-d H:i:s');
        $this->db->exec("INSERT INTO tags (id, name, usage_count, created_at, updated_at) VALUES (1, '測試標籤', 0, '{$now}', '{$now}')");

        $post = $this->repository->create(PostFactory::make());
        $result = $this->repository->setTags($post->getId(), [1]);

        $this->assertTrue($result);
        $tags = $this->db->query("SELECT * FROM post_tags WHERE post_id = {$post->getId()}")->fetchAll();
        $this->assertCount(1, $tags);
    }

    public function testFindByCreationSource(): void
    {
        // 建立不同來源的文章
        $webPost = $this->repository->create(array_merge(PostFactory::make(), [
            'title' => 'Web Post',
        ]));

        $apiPost = $this->repository->create(array_merge(PostFactory::make(), [
            'title' => 'API Post',
        ]));

        // 手動更新來源資訊（因為觸發器會設定為 'web'）
        $this->db->exec("UPDATE posts SET creation_source = 'web' WHERE id = {$webPost->getId()}");
        $this->db->exec("UPDATE posts SET creation_source = 'api' WHERE id = {$apiPost->getId()}");

        // 測試按來源查詢
        $webPosts = $this->repository->findByCreationSource('web');
        $this->assertCount(1, $webPosts);
        $this->assertEquals('Web Post', $webPosts[0]->getTitle());

        $apiPosts = $this->repository->findByCreationSource('api');
        $this->assertCount(1, $apiPosts);
        $this->assertEquals('API Post', $apiPosts[0]->getTitle());

        // 測試不存在的來源
        $unknownPosts = $this->repository->findByCreationSource('unknown');
        $this->assertEmpty($unknownPosts);
    }

    public function testGetSourceDistribution(): void
    {
        // 建立不同來源的文章
        $post1 = $this->repository->create(PostFactory::make());
        $post2 = $this->repository->create(PostFactory::make());
        $post3 = $this->repository->create(PostFactory::make());

        // 手動更新來源資訊
        $this->db->exec("UPDATE posts SET creation_source = 'web' WHERE id IN ({$post1->getId()}, {$post2->getId()})");
        $this->db->exec("UPDATE posts SET creation_source = 'api' WHERE id = {$post3->getId()}");

        $distribution = $this->repository->getSourceDistribution();

        $this->assertIsArray($distribution);
        $this->assertEquals(2, $distribution['web'] ?? 0);
        $this->assertEquals(1, $distribution['api'] ?? 0);
    }

    public function testFindByCreationSourceAndDetail(): void
    {
        // 建立帶詳細資訊的文章
        $post1 = $this->repository->create(PostFactory::make());
        $post2 = $this->repository->create(PostFactory::make());
        $post3 = $this->repository->create(PostFactory::make());

        // 手動更新來源和詳細資訊
        $this->db->exec("UPDATE posts SET creation_source = 'api', creation_source_detail = 'mobile_app' WHERE id = {$post1->getId()}");
        $this->db->exec("UPDATE posts SET creation_source = 'api', creation_source_detail = 'web_app' WHERE id = {$post2->getId()}");
        $this->db->exec("UPDATE posts SET creation_source = 'api', creation_source_detail = NULL WHERE id = {$post3->getId()}");

        // 測試按來源和詳細資訊查詢
        $mobileAppPosts = $this->repository->findByCreationSourceAndDetail('api', 'mobile_app');
        $this->assertCount(1, $mobileAppPosts);
        $this->assertEquals($post1->getId(), $mobileAppPosts[0]->getId());

        $webAppPosts = $this->repository->findByCreationSourceAndDetail('api', 'web_app');
        $this->assertCount(1, $webAppPosts);
        $this->assertEquals($post2->getId(), $webAppPosts[0]->getId());

        // 測試 NULL 詳細資訊
        $nullDetailPosts = $this->repository->findByCreationSourceAndDetail('api', null);
        $this->assertCount(1, $nullDetailPosts);
        $this->assertEquals($post3->getId(), $nullDetailPosts[0]->getId());
    }

    public function testCountByCreationSource(): void
    {
        // 建立不同來源的文章
        $post1 = $this->repository->create(PostFactory::make());
        $post2 = $this->repository->create(PostFactory::make());
        $post3 = $this->repository->create(PostFactory::make());

        // 手動更新來源資訊
        $this->db->exec("UPDATE posts SET creation_source = 'web' WHERE id IN ({$post1->getId()}, {$post2->getId()})");
        $this->db->exec("UPDATE posts SET creation_source = 'api' WHERE id = {$post3->getId()}");

        $webCount = $this->repository->countByCreationSource('web');
        $this->assertEquals(2, $webCount);

        $apiCount = $this->repository->countByCreationSource('api');
        $this->assertEquals(1, $apiCount);

        $unknownCount = $this->repository->countByCreationSource('unknown');
        $this->assertEquals(0, $unknownCount);
    }

    public function testPaginateByCreationSource(): void
    {
        // 建立多個相同來源的文章
        $posts = [];
        for ($i = 0; $i < 5; $i++) {
            $posts[] = $this->repository->create(array_merge(PostFactory::make(), [
                'title' => "Test Post {$i}",
            ]));
        }

        // 手動更新來源資訊
        $postIds = implode(',', array_map(fn($p) => $p->getId(), $posts));
        $this->db->exec("UPDATE posts SET creation_source = 'web' WHERE id IN ({$postIds})");

        // 測試分頁
        $result = $this->repository->paginateByCreationSource('web', 1, 3);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('lastPage', $result);

        $this->assertCount(3, $result['items']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['perPage']);
        $this->assertEquals(2, $result['lastPage']);

        // 測試第二頁
        $result2 = $this->repository->paginateByCreationSource('web', 2, 3);
        $this->assertCount(2, $result2['items']);
    }
}
