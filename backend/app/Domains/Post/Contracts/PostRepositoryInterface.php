<?php

declare(strict_types=1);

namespace App\Domains\Post\Contracts;

use App\Domains\Post\Models\Post;
use App\Shared\Contracts\RepositoryInterface;

interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * 依流水號查詢文章.
     */
    public function findBySeqNumber(int $seqNumber): ?Post;

    /**
     * 使用悲觀鎖查找文章（用於防止競爭條件）.
     */
    public function findWithLock(int $id): ?Post;

    /**
     * 安全刪除文章（使用悲觀鎖防止競爭條件）.
     */
    public function safeDelete(int $id): bool;

    /**
     * 安全設定置頂狀態（使用悲觀鎖防止競爭條件）.
     */
    public function safeSetPinned(int $id, bool $isPinned): bool;

    /**
     * 取得置頂文章列表.
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array;

    /**
     * 根據標籤取得文章。
     *
     * @return array<int, Post>
     */
    public function getPostsByTag(string $tag, int $limit = 10, int $offset = 0): array;

    /**
     * 更新文章觀看次數.
     */
    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool;

    /**
     * 設定文章置頂狀態.
     */
    public function setPinned(int $id, bool $isPinned): bool;

    /**
     * 為文章設定標籤。
     *
     * @param array<int, int> $tagIds
     */
    public function setTags(int $postId, array $tagIds): bool;
}
