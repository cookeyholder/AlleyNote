<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use App\Domains\Post\Models\Tag;
use App\Domains\Post\Repositories\TagRepository;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\Support\IntegrationTestCase;

/**
 * TagRepository 整合測試.
 */
#[Group('integration')]
final class TagRepositoryIntegrationTest extends IntegrationTestCase
{
    private TagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TagRepository($this->db);
    }

    /**
     * 直接寫入一筆標籤資料.
     *
     * @param array<string, mixed> $overrides
     */
    private function insertTag(array $overrides = []): int
    {
        $data = array_merge([
            'name'        => '標籤_' . uniqid(),
            'slug'        => 'slug-' . uniqid(),
            'description' => '測試用描述',
            'color'       => '#00aa55',
            'usage_count' => 0,
        ], $overrides);

        $stmt = $this->db->prepare(
            'INSERT INTO tags (name, slug, description, color, usage_count) VALUES (:name, :slug, :description, :color, :usage_count)',
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':slug'        => $data['slug'],
            ':description' => $data['description'],
            ':color'       => $data['color'],
            ':usage_count' => $data['usage_count'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function test_list回傳全部標籤與總數(): void
    {
        $this->insertTag(['name' => '甲', 'slug' => 'a']);
        $this->insertTag(['name' => '乙', 'slug' => 'b']);

        ['items' => $items, 'total' => $total] = $this->repository->list();

        $this->assertSame(2, $total);
        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(Tag::class, $items);
    }

    public function test_list支援關鍵字搜尋(): void
    {
        $this->insertTag(['name' => '技術分享', 'slug' => 'tech', 'description' => '程式相關']);
        $this->insertTag(['name' => '生活', 'slug' => 'life']);

        ['items' => $items, 'total' => $total] = $this->repository->list(1, 20, ['search' => 'tech']);

        $this->assertSame(1, $total);
        $this->assertSame('技術分享', $items[0]->getName());
    }

    public function test_list分頁與排序(): void
    {
        $lowId = $this->insertTag(['name' => '低使用量', 'usage_count' => 1]);
        $highId = $this->insertTag(['name' => '高使用量', 'usage_count' => 9]);

        ['items' => $items] = $this->repository->list(1, 1);

        // usage_count DESC 排序，第一筆應為高使用量
        $this->assertSame($highId, $items[0]->getId());
        $this->assertNotSame($lowId, $items[0]->getId());
    }

    public function test_findById存在與不存在(): void
    {
        $id = $this->insertTag(['name' => '查詢用']);

        $tag = $this->repository->findById($id);
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame($id, $tag->getId());
        $this->assertSame('查詢用', $tag->getName());

        $this->assertNull($this->repository->findById(999999));
    }

    public function test_findByName存在與不存在(): void
    {
        $id = $this->insertTag(['name' => '獨特名稱XYZ']);

        $tag = $this->repository->findByName('獨特名稱XYZ');
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame($id, $tag->getId());

        $this->assertNull($this->repository->findByName('不存在的名稱'));
    }

    public function test_findBySlug存在與不存在(): void
    {
        $id = $this->insertTag(['name' => 'Slug查詢', 'slug' => 'unique-slug-abc']);

        $tag = $this->repository->findBySlug('unique-slug-abc');
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame($id, $tag->getId());

        $this->assertNull($this->repository->findBySlug('no-such-slug'));
    }

    public function test_create建立標籤並回傳完整物件(): void
    {
        $tag = $this->repository->create([
            'name'        => '新建標籤',
            'slug'        => 'new-tag',
            'description' => '描述內容',
            'color'       => '#123456',
            'usage_count' => 3,
        ]);

        $this->assertSame('新建標籤', $tag->getName());
        $this->assertSame('new-tag', $tag->getSlug());
        $this->assertSame('描述內容', $tag->getDescription());
        $this->assertSame('#123456', $tag->getColor());
        $this->assertSame(3, $tag->getUsageCount());
        $this->assertGreaterThan(0, $tag->getId());
    }

    public function test_create允許省略選填欄位(): void
    {
        $tag = $this->repository->create(['name' => '只有名稱']);

        $this->assertSame('只有名稱', $tag->getName());
        $this->assertNull($tag->getSlug());
        $this->assertSame(0, $tag->getUsageCount());
    }

    public function test_update更新欄位(): void
    {
        $id = $this->insertTag(['name' => '舊名稱', 'slug' => 'old-slug']);

        $updated = $this->repository->update($id, [
            'name'        => '新名稱',
            'description' => '新描述',
            'color'       => '#654321',
            'usage_count' => 5,
        ]);

        $this->assertSame($id, $updated->getId());
        $this->assertSame('新名稱', $updated->getName());
        $this->assertSame('新描述', $updated->getDescription());
        $this->assertSame('#654321', $updated->getColor());
        $this->assertSame(5, $updated->getUsageCount());
    }

    public function test_update無變更時回傳原資料(): void
    {
        $id = $this->insertTag(['name' => '保持原狀']);

        $tag = $this->repository->update($id, []);

        $this->assertSame($id, $tag->getId());
        $this->assertSame('保持原狀', $tag->getName());
    }

    public function test_update不存在的標籤拋出例外(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('標籤不存在');

        $this->repository->update(999999, []);
    }

    public function test_delete刪除標籤(): void
    {
        $id = $this->insertTag();

        $this->assertTrue($this->repository->delete($id));
        $this->assertNull($this->repository->findById($id));
    }

    public function test_detachFromAllPosts解除文章關聯(): void
    {
        $userId = $this->insertTestUser();
        $postId = $this->insertTestPost(['user_id' => $userId]);
        $tagId = $this->insertTag();

        $stmt = $this->db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        $stmt->execute([$postId, $tagId]);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM post_tags WHERE tag_id = ?');
        $countStmt->execute([$tagId]);
        $this->assertSame(1, (int) $countStmt->fetchColumn());

        $this->repository->detachFromAllPosts($tagId);

        $countStmt->execute([$tagId]);
        $this->assertSame(0, (int) $countStmt->fetchColumn());
    }
}
