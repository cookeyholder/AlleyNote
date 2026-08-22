<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Repositories\PostCrudRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Support\IntegrationTestCase;

/**
 * PostCrudRepository 整合測試.
 *
 * 注意：測試用 DatabaseTestTrait 的結構與正式 migration 有數處不一致
 * （post_views 缺少 uuid/user_ip/view_date；users 多了正式結構沒有的
 * status 欄位），因此於 setUp 中重建為正式結構。users.status 的多餘欄位
 * 會使 PostCrudRepository::getPinnedPosts 的未限定 status 查詢變得模糊，
 * 屬測試基礎架構待修正事項，已另行回報。
 */
#[Group('integration')]
final class PostCrudRepositoryIntegrationTest extends IntegrationTestCase
{
    private PostCrudRepository $repository;

    private static int $seq = 5000;

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

        // 將 post_views 與 users 調整為與正式 migration 一致的結構
        $this->alignProductionSchema();

        // IntegrationTestCase 已提供 $this->cache（記憶體模擬），
        // 這裡補上儲存庫會用到的 deletePattern 行為
        $this->cache->shouldReceive('deletePattern')->byDefault()->andReturn(true);

        $logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $logger->shouldReceive('logSecurityEvent')->byDefault()->andReturn(true);

        $cache = $this->cache;
        assert($cache instanceof CacheServiceInterface);

        $this->repository = new PostCrudRepository($this->db, $cache, $logger);
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
     * 依正式結構寫入使用者.
     */
    private function insertProductionUser(int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT OR IGNORE INTO users (id, username, email, password_hash) VALUES (?, ?, ?, ?)',
        );
        $stmt->execute([
            $userId,
            'author_' . $userId . '_' . uniqid(),
            'user_' . $userId . '_' . uniqid() . '@example.com',
            password_hash('secret', PASSWORD_BCRYPT),
        ]);
    }

    /**
     * 直接寫入一篇文章並回傳 ID.
     *
     * @param array<string, mixed> $overrides
     */
    private function insertPost(array $overrides = []): int
    {
        $userId = isset($overrides['user_id']) && is_int($overrides['user_id']) ? $overrides['user_id'] : 1;
        $this->insertProductionUser($userId);

        $data = array_merge([
            'uuid'         => 'uuid-' . uniqid(),
            'seq_number'   => static::nextSeq(),
            'title'        => '直接寫入文章',
            'content'      => '<p>內容</p>',
            'user_id'      => $userId,
            'user_ip'      => '127.0.0.1',
            'views'        => 0,
            'is_pinned'    => 0,
            'status'       => PostStatus::DRAFT->value,
            'publish_date' => gmdate('Y-m-d H:i:s', time() - 3600),
            'created_at'   => gmdate('Y-m-d H:i:s', time() - 3600),
            'updated_at'   => gmdate('Y-m-d H:i:s', time() - 3600),
        ], $overrides);

        $stmt = $this->db->prepare(
            'INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, views, is_pinned, status, publish_date, created_at, updated_at)
             VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :views, :is_pinned, :status, :publish_date, :created_at, :updated_at)',
        );
        $stmt->execute([
            ':uuid'         => $data['uuid'],
            ':seq_number'   => $data['seq_number'],
            ':title'        => $data['title'],
            ':content'      => $data['content'],
            ':user_id'      => $data['user_id'],
            ':user_ip'      => $data['user_ip'],
            ':views'        => $data['views'],
            ':is_pinned'    => $data['is_pinned'],
            ':status'       => $data['status'],
            ':publish_date' => $data['publish_date'],
            ':created_at'   => $data['created_at'],
            ':updated_at'   => $data['updated_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function insertTag(string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO tags (name) VALUES (?)');
        $stmt->execute([$name]);

        return (int) $this->db->lastInsertId();
    }

    public function test_create建立文章含標籤(): void
    {
        $tagId = $this->insertTag('建立測試標籤');

        $post = $this->repository->create([
            'title'   => '新文章',
            'content' => '<p>新內容</p>',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            // 測試用結構的 publish_date 為 NOT NULL（正式結構允許 null），需明確給值
            'publish_date' => gmdate('Y-m-d H:i:s', time() - 3600),
        ], [$tagId]);

        $this->assertGreaterThan(0, $post->getId());
        $this->assertSame(PostStatus::DRAFT, $post->getStatus());

        $tags = $this->repository->getPostTags($post->getId());
        $this->assertCount(1, $tags);
        $this->assertSame('建立測試標籤', $tags[0]['name']);

        $countStmt = $this->db->prepare('SELECT usage_count FROM tags WHERE id = ?');
        $countStmt->execute([$tagId]);
        $this->assertSame(1, (int) $countStmt->fetchColumn());
    }

    public function test_find與findByUuid與findBySeqNumber(): void
    {
        $postId = $this->insertPost(['uuid' => 'fixed-uuid-1']);
        $seqStmt = $this->db->prepare('SELECT seq_number FROM posts WHERE id = ?');
        $seqStmt->execute([$postId]);
        $rawSeq = $seqStmt->fetchColumn();
        $this->assertIsNumeric($rawSeq);
        $seqNumber = (int) $rawSeq;

        $found = $this->repository->find($postId);
        $this->assertNotNull($found);
        $this->assertSame($postId, $found->getId());
        $this->assertSame('fixed-uuid-1', $found->getUuid());

        $byUuid = $this->repository->findByUuid('fixed-uuid-1');
        $this->assertNotNull($byUuid);
        $this->assertSame($postId, $byUuid->getId());

        $bySeq = $this->repository->findBySeqNumber($seqNumber);
        $this->assertNotNull($bySeq);
        $this->assertSame($postId, $bySeq->getId());

        $locked = $this->repository->findWithLock($postId);
        $this->assertNotNull($locked);

        $this->assertNull($this->repository->find(999999));
        $this->assertNull($this->repository->findByUuid('no-such-uuid'));
        $this->assertNull($this->repository->findBySeqNumber(999999999));
        $this->assertNull($this->repository->findWithLock(999999));
    }

    public function test_update允許欄位與保護欄位處理(): void
    {
        $postId = $this->insertPost();

        $updated = $this->repository->update($postId, [
            'title'           => '更新後標題',
            'id'              => 777,
            'uuid'            => 'hacked',
            'malicious_field' => 'x',
        ]);

        $this->assertSame('更新後標題', $updated->getTitle());
        // 保護欄位不可被更新
        $this->assertNotSame(777, $updated->getId());
        $this->assertNotSame('hacked', $updated->getUuid());
        // 未提及的內容維持原值；非白名單欄位會被記錄並忽略
        $this->assertSame('<p>內容</p>', $updated->getContent());
    }

    public function test_update無有效欄位時原樣回傳(): void
    {
        $postId = $this->insertPost(['title' => '原始標題']);

        $post = $this->repository->update($postId, []);

        $this->assertSame('原始標題', $post->getTitle());
    }

    public function test_update不存在的文章拋出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->repository->update(999999, ['title' => '任何']);
    }

    public function test_safeDelete規則(): void
    {
        $draftId = $this->insertPost(['status' => PostStatus::DRAFT->value]);
        $publishedId = $this->insertPost(['status' => PostStatus::PUBLISHED->value]);

        $this->assertTrue($this->repository->safeDelete($draftId));
        $this->assertNull($this->repository->find($draftId));

        try {
            $this->repository->safeDelete($publishedId);
            $this->fail('已發布文章應不可刪除');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('已發布的文章不能刪除', $e->getMessage());
        }

        $this->assertFalse($this->repository->safeDelete(999999));
    }

    public function test_safeSetPinned規則(): void
    {
        $draftId = $this->insertPost(['status' => PostStatus::DRAFT->value]);
        $publishedId = $this->insertPost(['status' => PostStatus::PUBLISHED->value]);

        try {
            $this->repository->safeSetPinned($draftId, true);
            $this->fail('草稿文章應不可置頂');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('只有已發布的文章可以置頂', $e->getMessage());
        }

        $this->assertTrue($this->repository->safeSetPinned($publishedId, true));
        $this->assertTrue($this->repository->find($publishedId)?->isPinned());

        // 取消置頂不需要已發布狀態
        $this->assertTrue($this->repository->safeSetPinned($draftId, false));
        $this->assertFalse($this->repository->safeSetPinned(999999, true));
    }

    public function test_paginate支援搜尋與條件(): void
    {
        $this->insertPost(['title' => '可搜尋的獨特標題QQ']);
        $this->insertPost(['title' => '其他文章']);

        $result = $this->repository->paginate(1, 10);
        $this->assertSame(2, $result['total']);

        $searched = $this->repository->paginate(1, 10, ['search' => '獨特標題QQ']);
        $this->assertSame(1, $searched['total']);
        $this->assertCount(1, $searched['items']);

        $byTitle = $this->repository->paginate(1, 10, ['title' => '其他文章']);
        $this->assertSame(1, $byTitle['total']);

        $emptyPage = $this->repository->paginate(5, 10);
        $this->assertSame([], $emptyPage['items']);
    }

    public function test_getPinnedPosts只回傳置頂文章(): void
    {
        $pinnedId = $this->insertPost(['is_pinned' => 1, 'status' => PostStatus::PUBLISHED->value]);
        $this->insertPost(['is_pinned' => 0]);

        $pinned = $this->repository->getPinnedPosts();

        $this->assertCount(1, $pinned);
        $this->assertSame($pinnedId, $pinned[0]->getId());
    }

    public function test_getPostsByTag依標籤查詢文章(): void
    {
        $tagId = $this->insertTag('關聯標籤');
        $postId = $this->insertPost();
        $stmt = $this->db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        $stmt->execute([$postId, $tagId]);

        $result = $this->repository->getPostsByTag($tagId);

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['items']);
        $this->assertSame($postId, $result['items'][0]->getId());
    }

    public function test_incrementViews記錄瀏覽次數(): void
    {
        $postId = $this->insertPost();

        $this->assertTrue($this->repository->incrementViews($postId, '10.1.2.3', 1));

        $statsStmt = $this->db->prepare('SELECT views FROM posts WHERE id = ?');
        $statsStmt->execute([$postId]);
        $this->assertSame(1, (int) $statsStmt->fetchColumn());

        $viewCountStmt = $this->db->prepare('SELECT COUNT(*) FROM post_views WHERE post_id = ?');
        $viewCountStmt->execute([$postId]);
        $this->assertSame(1, (int) $viewCountStmt->fetchColumn());
    }

    public function test_incrementViews參數驗證(): void
    {
        $postId = $this->insertPost();

        try {
            $this->repository->incrementViews($postId, '10.1.2.3', 0);
            $this->fail('使用者 ID 必須是正整數');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('正整數', $e->getMessage());
        }

        try {
            $this->repository->incrementViews(999999, '10.1.2.3');
            $this->fail('不存在的文章應擲出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('找不到指定的文章', $e->getMessage());
        }
    }

    public function test_setTags替換標籤並更新使用量(): void
    {
        $tagA = $this->insertTag('標籤A');
        $tagB = $this->insertTag('標籤B');
        $postId = $this->insertPost();

        $this->repository->setTags($postId, [$tagA, 'not-a-number', $tagB]);

        $tags = $this->repository->getPostTags($postId);
        $names = array_column($tags, 'name');
        sort($names);
        $this->assertSame(['標籤A', '標籤B'], $names);

        // 替換為空陣列即清空
        $this->repository->setTags($postId, []);
        $this->assertSame([], $this->repository->getPostTags($postId));

        $usageStmt = $this->db->prepare('SELECT usage_count FROM tags WHERE id = ?');
        $usageStmt->execute([$tagA]);
        $this->assertSame(0, (int) $usageStmt->fetchColumn());
    }

    public function test_setTags使用量統計更新(): void
    {
        $tagId = $this->insertTag('計數標籤');
        $postOne = $this->insertPost();
        $postTwo = $this->insertPost();

        $this->repository->setTags($postOne, [$tagId]);
        $this->repository->setTags($postTwo, [(string) $tagId]);

        $usageStmt = $this->db->prepare('SELECT usage_count FROM tags WHERE id = ?');
        $usageStmt->execute([$tagId]);
        $this->assertSame(2, (int) $usageStmt->fetchColumn());
    }

    public function test_setTags含不存在的標籤時擲出例外(): void
    {
        $postId = $this->insertPost();

        try {
            $this->repository->setTags($postId, [999999]);
            $this->fail('不存在的標籤應擲出例外');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('無法設定文章標籤', $e->getMessage());
        }

        // 交易應已回滾，關聯保持為空
        $this->assertSame([], $this->repository->getPostTags($postId));
    }

    public function test_delete移除文章(): void
    {
        $postId = $this->insertPost();

        $this->assertTrue($this->repository->delete($postId));
        $this->assertNull($this->repository->find($postId));
    }
}
