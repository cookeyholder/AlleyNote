<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Post\Repositories\PostSearchRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

/**
 * PostRepository 外觀層委派方法與 PostSearchRepository 整合測試.
 */
#[Group('integration')]
final class PostRepositoryFacadeTest extends IntegrationTestCase
{
    private PostRepository $repository;

    private static int $seq = 7000;

    /**
     * 產生不重複的流水號.
     */
    private static function nextSeq(): int
    {
        return ++self::$seq;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->alignProductionSchema();

        $this->cache->shouldReceive('deletePattern')->byDefault()->andReturn(true);
        $this->cache->shouldReceive('remember')->byDefault()->andReturnUsing(
            fn(string $key, callable $callback, ?int $ttl = null): mixed => $callback(),
        );

        $logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $logger->shouldReceive('logSecurityEvent')->byDefault()->andReturn(true);

        $cache = $this->cache;
        assert($cache instanceof CacheServiceInterface);

        $this->repository = new PostRepository($this->db, $cache, $logger);
    }

    /**
     * 將 post_views 與 users 資料表調整為正式 migration 的結構.
     */
    private function alignProductionSchema(): void
    {
        $this->db->exec('PRAGMA foreign_keys = OFF');
        $this->db->exec('DROP TABLE IF EXISTS post_views');
        $this->db->exec('
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ');
        // 正式結構的 users 沒有 status 欄位（測試用 trait 多加了）
        $this->db->exec('DROP TABLE IF EXISTS users');
        $this->db->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT
            )
        ');
        $this->db->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * 建立測試文章並回傳 ID.
     *
     * @param array<string, mixed> $overrides
     * @param list<int> $tagIds
     */
    private function createPost(array $overrides = [], array $tagIds = []): int
    {
        $data = array_merge([
            'uuid'         => 'facade-uuid-' . uniqid(),
            'seq_number'   => self::nextSeq(),
            'title'        => '外觀層測試文章',
            'content'      => '外觀層測試內容',
            'user_id'      => 1,
            'user_ip'      => '127.0.0.1',
            'is_pinned'    => 0,
            'status'       => PostStatus::DRAFT->value,
            'publish_date' => date('Y-m-d H:i:s'),
        ], $overrides);

        $post = $this->repository->create($data, $tagIds);

        return $post->getId();
    }

    public function test_findWithLock可取得已存在文章(): void
    {
        $id = $this->createPost(['title' => '鎖定查詢文章']);

        $post = $this->repository->findWithLock($id);

        $this->assertNotNull($post);
        $this->assertSame($id, $post->getId());
    }

    public function test_findWithLock對不存在文章回傳null(): void
    {
        $this->assertNull($this->repository->findWithLock(9999999));
    }

    public function test_findBySeqNumber依流水號查詢(): void
    {
        // create() 會忽略外部傳入的 seq_number，改以 DB 自身產生的值查詢
        $id = $this->createPost();
        $post = $this->repository->find($id);
        $this->assertNotNull($post);
        $seqNumber = (int) $post->getSeqNumber();

        $found = $this->repository->findBySeqNumber($seqNumber);

        $this->assertNotNull($found);
        $this->assertSame($id, $found->getId());
    }

    public function test_safeDelete草稿可刪除且已發布拋出例外(): void
    {
        $draftId = $this->createPost();
        $publishedId = $this->createPost(['status' => PostStatus::PUBLISHED->value]);

        $this->assertTrue($this->repository->safeDelete($draftId));
        $this->assertNull($this->repository->find($draftId));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('已發布的文章不能刪除，請改為封存');

        $this->repository->safeDelete($publishedId);
    }

    public function test_safeSetPinned規則(): void
    {
        $draftId = $this->createPost();
        $publishedId = $this->createPost(['status' => PostStatus::PUBLISHED->value]);

        try {
            // 草稿不能置頂
            $this->repository->safeSetPinned($draftId, true);
            $this->fail('草稿置頂應拋出例外');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        // 已發布文章可置頂與取消置頂；不存在文章回傳 false
        $this->assertTrue($this->repository->safeSetPinned($publishedId, true));
        $this->assertTrue($this->repository->find($publishedId)?->isPinned());
        $this->assertTrue($this->repository->safeSetPinned($publishedId, false));
        $this->assertFalse($this->repository->find($publishedId)?->isPinned());
        $this->assertFalse($this->repository->safeSetPinned(9999999, true));
    }

    public function test_getPostsByTag與getPostTags(): void
    {
        // 建立標籤（測試用 tags 表無 uuid 欄位）
        $stmt = $this->db->prepare('INSERT INTO tags (name) VALUES (?)');
        $stmt->execute(['整合標籤']);
        $tagId = (int) $this->db->lastInsertId();

        $postId = $this->createPost(['title' => '帶標籤的文章'], [$tagId]);

        $result = $this->repository->getPostsByTag($tagId);
        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
        $this->assertSame($postId, $result['items'][0]->getId());

        $tags = $this->repository->getPostTags($postId);
        $this->assertSame([[
            'id'   => $tagId,
            'name' => '整合標籤',
        ]], $tags);
    }

    public function test_searchByTitle以關鍵字搜尋標題(): void
    {
        $this->createPost(['title' => '獨特關鍵詞搜尋目標']);
        $this->createPost(['title' => '完全不同的標題']);

        $results = $this->repository->searchByTitle('獨特關鍵詞');

        $this->assertCount(1, $results);
        $this->assertSame('獨特關鍵詞搜尋目標', $results[0]->getTitle());
    }

    public function test_findLatestByUserId回傳最新一篇文章(): void
    {
        $userId = 42;
        // created_at 由 prepareNewPostData 產生（秒級精度），改以 UPDATE 確保先後順序明確
        $firstId = $this->createPost(['user_id' => $userId, 'title' => '第一篇']);
        $latestId = $this->createPost(['user_id' => $userId, 'title' => '第二篇']);
        $this->createPost(['user_id' => 99, 'title' => '其他使用者']);

        $stmt = $this->db->prepare('UPDATE posts SET created_at = ? WHERE id = ?');
        $stmt->execute(['2026-01-01 10:00:00', $firstId]);
        $stmt->execute(['2026-06-01 10:00:00', $latestId]);

        $latest = $this->repository->findLatestByUserId($userId);

        $this->assertNotNull($latest);
        $this->assertSame($latestId, $latest->getId());
    }

    public function test_findLatestByUserId無文章時回傳null(): void
    {
        $cache = $this->cache;
        assert($cache instanceof CacheServiceInterface);
        $search = new PostSearchRepository($this->db, $cache);

        $this->assertNull($search->findLatestByUserId(987654));
    }

    public function test_PostSearchRepository直接呼叫搜尋與最新查詢(): void
    {
        $cache = $this->cache;
        assert($cache instanceof CacheServiceInterface);
        $search = new PostSearchRepository($this->db, $cache);

        $this->createPost(['title' => '直接搜尋範例文章']);

        $results = $search->searchByTitle('直接搜尋範例');
        $this->assertCount(1, $results);
        $this->assertSame('直接搜尋範例文章', $results[0]->getTitle());

        $latest = $search->findLatestByUserId(1);
        $this->assertNotNull($latest);
    }
}
