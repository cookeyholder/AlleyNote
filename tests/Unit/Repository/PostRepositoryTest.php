<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Models\Post;
use App\Repositories\PostRepository;
use Tests\Factory\PostFactory;

class PostRepositoryTest extends TestCase
{
    private PDO $db;
    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立記憶體資料庫
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立資料表
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

        $this->db->exec("
            CREATE TABLE post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (post_id, tag_id)
            )
        ");

        $this->db->exec("
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL
            )
        ");

        $this->db->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            )
        ");

        $this->repository = new PostRepository($this->db);
    }

    public function testCanCreatePost(): void
    {
        // 準備測試資料
        $data = PostFactory::make([
            'title' => '測試文章',
            'content' => '這是一篇測試文章'
        ]);

        // 執行測試
        $post = $this->repository->create($data);

        // 驗證結果
        $this->assertInstanceOf(Post::class, $post);
        $this->assertNotEmpty($post->getUuid());
        $this->assertEquals('測試文章', $post->getTitle());
        $this->assertEquals('這是一篇測試文章', $post->getContent());
        $this->assertEquals(1, $post->getSeqNumber());
    }

    public function testCanFindPostById(): void
    {
        // 準備測試資料
        $data = PostFactory::make();
        $created = $this->repository->create($data);

        // 執行測試
        $found = $this->repository->find($created->getId());

        // 驗證結果
        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($created->getId(), $found->getId());
        $this->assertEquals($created->getTitle(), $found->getTitle());
    }

    public function testCanFindPostByUuid(): void
    {
        // 準備測試資料
        $data = PostFactory::make();
        $created = $this->repository->create($data);

        // 執行測試
        $found = $this->repository->findByUuid($created->getUuid());

        // 驗證結果
        $this->assertInstanceOf(Post::class, $found);
        $this->assertEquals($created->getUuid(), $found->getUuid());
        $this->assertEquals($created->getTitle(), $found->getTitle());
    }

    public function testCanUpdatePost(): void
    {
        // 準備測試資料
        $post = $this->repository->create(PostFactory::make());

        // 執行測試
        $updated = $this->repository->update($post->getId(), [
            'title' => '更新的標題',
            'content' => '更新的內容'
        ]);

        // 驗證結果
        $this->assertEquals('更新的標題', $updated->getTitle());
        $this->assertEquals('更新的內容', $updated->getContent());
        $this->assertNotEquals($post->getUpdatedAt(), $updated->getUpdatedAt());
    }

    public function testCanDeletePost(): void
    {
        // 準備測試資料
        $post = $this->repository->create(PostFactory::make());

        // 執行測試
        $result = $this->repository->delete($post->getId());
        $found = $this->repository->find($post->getId());

        // 驗證結果
        $this->assertTrue($result);
        $this->assertNull($found);
    }

    public function testCanPaginatePosts(): void
    {
        // 準備測試資料
        foreach (PostFactory::makeMany(15) as $data) {
            $this->repository->create($data);
        }

        // 執行測試
        $page1 = $this->repository->paginate(1, 5);
        $page2 = $this->repository->paginate(2, 5);

        // 驗證結果
        $this->assertCount(5, $page1['items']);
        $this->assertCount(5, $page2['items']);
        $this->assertEquals(15, $page1['total']);
        $this->assertEquals(3, $page1['last_page']);
    }

    public function testCanGetPinnedPosts(): void
    {
        // 準備測試資料
        $this->repository->create(PostFactory::make(['is_pinned' => true]));
        $this->repository->create(PostFactory::make(['is_pinned' => true]));
        $this->repository->create(PostFactory::make(['is_pinned' => false]));

        // 執行測試
        $pinnedPosts = $this->repository->getPinnedPosts();

        // 驗證結果
        $this->assertCount(2, $pinnedPosts);
        foreach ($pinnedPosts as $post) {
            $this->assertTrue($post->isPinned());
        }
    }

    public function testCanSetPinnedStatus(): void
    {
        // 準備測試資料
        $post = $this->repository->create(PostFactory::make(['is_pinned' => false]));

        // 執行測試
        $result = $this->repository->setPinned($post->getId(), true);
        $updated = $this->repository->find($post->getId());

        // 驗證結果
        $this->assertTrue($result);
        $this->assertTrue($updated->isPinned());
    }

    public function testCanIncrementViews(): void
    {
        // 準備測試資料
        $post = $this->repository->create(PostFactory::make());
        $initialViews = $post->getViews();

        // 執行測試
        $result = $this->repository->incrementViews($post->getId(), '127.0.0.1', 1);
        $updated = $this->repository->find($post->getId());

        // 驗證結果
        $this->assertTrue($result);
        $this->assertEquals($initialViews + 1, $updated->getViews());

        // 驗證觀看記錄
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM post_views WHERE post_id = ?');
        $stmt->execute([$post->getId()]);
        $viewCount = $stmt->fetchColumn();
        $this->assertEquals(1, $viewCount);
    }

    /** @test */
    public function testShouldRollbackOnTagAssignmentError(): void
    {
        // 準備測試資料
        $data = PostFactory::make([
            'title' => '交易測試文章',
            'content' => '這是交易測試內容'
        ]);

        $this->db->beginTransaction();
        $initialPostCount = $this->getPostCount();
        $this->db->commit();

        try {
            // 嘗試建立文章並指派不存在的標籤
            $this->repository->create($data, [999]);
            $this->fail('應該要拋出異常');
        } catch (\PDOException $e) {
            // 預期會拋出異常
        }

        // 驗證文章未被建立（交易已回溯）
        $this->assertEquals($initialPostCount, $this->getPostCount());
    }

    /** @test */
    public function testShouldCommitOnTagAssignmentSuccess(): void
    {
        // 建立測試標籤
        $this->db->exec("INSERT INTO tags (id, name) VALUES (1, '測試標籤')");

        // 準備測試資料
        $data = PostFactory::make([
            'title' => '交易測試文章',
            'content' => '這是交易測試內容'
        ]);

        $this->db->beginTransaction();
        $initialPostCount = $this->getPostCount();
        $initialTagCount = $this->getTagAssignmentCount();
        $this->db->commit();

        // 建立文章並指派標籤
        $post = $this->repository->create($data, [1]);

        // 驗證文章和標籤關聯都已成功建立
        $this->assertEquals($initialPostCount + 1, $this->getPostCount());
        $this->assertEquals($initialTagCount + 1, $this->getTagAssignmentCount());
        $this->assertNotNull($post);
        $this->assertEquals('交易測試文章', $post->getTitle());
    }

    private function getPostCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    private function getTagAssignmentCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM post_tags')->fetchColumn();
    }

    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }
}
