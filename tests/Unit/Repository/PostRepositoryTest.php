<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use PDO;
use App\Services\CacheService;
use App\Repositories\PostRepository;
use App\Models\Post;
use Tests\Factory\PostFactory;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PostRepositoryTest extends MockeryTestCase
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

    protected function createTestTables(): void
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
                views INTEGER DEFAULT 0,
                is_pinned BOOLEAN DEFAULT 0,
                status VARCHAR(20) DEFAULT "draft",
                publish_date DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

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
    }

    public function testCanCreatePost(): void
    {
        $data = PostFactory::make([
            'title' => '測試文章',
            'content' => '這是測試內容'
        ]);
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);

        $post = $this->repository->create($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('測試文章', $post->getTitle());
        $this->assertEquals('這是測試內容', $post->getContent());
    }

    public function testCanFindPostById(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $found = $this->repository->find($post->getId());

        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($post->getId(), $found->getId());
    }

    public function testCanFindPostByUuid(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $found = $this->repository->findByUuid($post->getUuid());

        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($post->getUuid(), $found->getUuid());
    }

    public function testCanUpdatePost(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);

        $updateData = [
            'title' => '更新後的標題',
            'content' => '更新後的內容',
            'user_id' => 1
        ];

        $updatedPost = $this->repository->update($post->getId(), $updateData);

        $this->assertInstanceOf(Post::class, $updatedPost);
        $this->assertEquals('更新後的標題', $updatedPost->getTitle());
        $this->assertEquals('更新後的內容', $updatedPost->getContent());
    }

    public function testCanDeletePost(): void
    {
        $data = PostFactory::make();
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
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
                'content' => "內容 {$i}"
            ]));
        }

        $result = $this->repository->paginate(1, 10);

        $this->assertCount(10, $result['items']);
        $this->assertEquals(15, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(2, $result['last_page']);
    }

    public function testCanGetPinnedPosts(): void
    {
        // 建立置頂文章
        $this->repository->create(PostFactory::make([
            'is_pinned' => true,
            'title' => '置頂文章 1'
        ]));
        $this->repository->create(PostFactory::make([
            'is_pinned' => true,
            'title' => '置頂文章 2'
        ]));

        // 建立普通文章
        $this->repository->create(PostFactory::make([
            'is_pinned' => false,
            'title' => '普通文章'
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
        $data = PostFactory::make();
        $data['publish_date'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['created_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $data['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339);
        $post = $this->repository->create($data);
        $initialViews = $post->getViews();

        $result = $this->repository->incrementViews(
            $post->getId(),
            '127.0.0.1',
            1
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
        } catch (\Exception $e) {
            // 確保交易已回溯
            $tags = $this->db->query("SELECT * FROM post_tags WHERE post_id = {$post->getId()}")->fetchAll();
            $this->assertEmpty($tags);
        }
    }

    public function testShouldCommitOnTagAssignmentSuccess(): void
    {
        // 建立測試用標籤
        $this->db->exec("INSERT INTO tags (id, name) VALUES (1, '測試標籤')");

        $post = $this->repository->create(PostFactory::make());
        $result = $this->repository->setTags($post->getId(), [1]);

        $this->assertTrue($result);
        $tags = $this->db->query("SELECT * FROM post_tags WHERE post_id = {$post->getId()}")->fetchAll();
        $this->assertCount(1, $tags);
    }
}
