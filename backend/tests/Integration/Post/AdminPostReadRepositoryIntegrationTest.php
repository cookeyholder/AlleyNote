<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use App\Domains\Post\Repositories\AdminPostReadRepository;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

/**
 * AdminPostReadRepository 整合測試.
 */
#[Group('integration')]
final class AdminPostReadRepositoryIntegrationTest extends IntegrationTestCase
{
    private AdminPostReadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AdminPostReadRepository($this->db);
    }

    /**
     * 建立指定作者的測試文章.
     *
     * @param array<string, mixed> $overrides
     */
    private function makePost(int $authorId, array $overrides = []): int
    {
        $check = $this->db->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $check->execute([$authorId]);
        if ((int) $check->fetchColumn() === 0) {
            $this->insertTestUser(['id' => $authorId, 'username' => 'author_' . $authorId]);
        }

        return $this->insertTestPost(array_merge(['user_id' => $authorId], $overrides));
    }

    public function test_paginate回傳文章列表與總數(): void
    {
        $this->makePost(1);
        $this->makePost(2);

        ['items' => $items, 'total' => $total] = $this->repository->paginate(1, 10);

        $this->assertSame(2, $total);
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertSame('published', $item['status']);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('content', $item);
            $this->assertArrayHasKey('created_at', $item);
            $this->assertArrayHasKey('updated_at', $item);
            /** @var array<string, mixed> $item */
            $this->assertIsString($item['author']);
            $this->assertStringStartsWith('author_', $item['author']);
        }
    }

    public function test_paginate支援標題關鍵字搜尋(): void
    {
        $this->makePost(1, ['title' => '特殊關鍵字公告']);
        $this->makePost(1, ['title' => '一般訊息']);

        ['items' => $items, 'total' => $total] = $this->repository->paginate(1, 10, '特殊關鍵字');

        $this->assertSame(1, $total);
        $this->assertSame('特殊關鍵字公告', $items[0]['title']);
    }

    public function test_paginate支援狀態篩選(): void
    {
        $this->makePost(1, ['status' => 'draft']);
        $this->makePost(1);

        ['total' => $total] = $this->repository->paginate(1, 10, '', 'draft');

        $this->assertSame(1, $total);
    }

    public function test_paginate預設排除未來發布的文章(): void
    {
        $this->makePost(1);
        $futureId = $this->makePost(1, [
            'publish_date' => gmdate('Y-m-d H:i:s', time() + 86400),
        ]);

        ['total' => $total] = $this->repository->paginate(1, 10);
        $this->assertSame(1, $total);

        ['total' => $totalWithFuture] = $this->repository->paginate(1, 10, '', '', true);
        $this->assertSame(2, $totalWithFuture);
    }

    public function test_paginate分頁偏移正確(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->makePost(1);
        }

        ['items' => $pageOne] = $this->repository->paginate(1, 2);
        ['items' => $pageTwo] = $this->repository->paginate(2, 2);

        $this->assertCount(2, $pageOne);
        $this->assertCount(1, $pageTwo);
        $this->assertNotSame($pageOne[0]['id'], $pageTwo[0]['id']);
    }

    public function test_findById回傳文章含作者與標籤(): void
    {
        $postId = $this->makePost(3, ['title' => '含標籤文章']);
        $this->db->exec("INSERT INTO tags (name, slug) VALUES ('標籤甲', 'tag-a')");
        $tagId = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        $stmt->execute([$postId, $tagId]);

        $post = $this->repository->findById($postId);

        $this->assertIsArray($post);
        /** @var array<string, mixed> $post */
        $idValue = $post['id'];
        $this->assertIsNumeric($idValue);
        $this->assertSame($postId, (int) $idValue);
        $this->assertSame('含標籤文章', $post['title']);
        $this->assertSame('author_3', $post['author']);
        $tags = $post['tags'];
        $this->assertIsArray($tags);
        $this->assertCount(1, $tags);
        /** @var array<string, mixed> $firstTag */
        $firstTag = $tags[0];
        $this->assertSame('標籤甲', $firstTag['name']);
    }

    public function test_findById找不到時回傳null(): void
    {
        $this->assertNull($this->repository->findById(99999));
    }

    public function test_findById預設排除草稿與未來文章(): void
    {
        $draftId = $this->makePost(1, ['status' => 'draft']);
        $futureId = $this->makePost(1, [
            'publish_date' => gmdate('Y-m-d H:i:s', time() + 86400),
        ]);

        $this->assertNull($this->repository->findById($draftId));
        $this->assertNull($this->repository->findById($futureId));
        $this->assertIsArray($this->repository->findById($draftId, true));
        $this->assertIsArray($this->repository->findById($futureId, true));
    }
}
