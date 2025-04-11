<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Post;
use Mockery\MockInterface;

interface PostRepositoryInterface extends RepositoryInterface, MockInterface
{
    /**
     * 依流水號查詢文章
     * @param int $seqNumber
     * @return Post|null
     */
    public function findBySeqNumber(int $seqNumber): ?Post;

    /**
     * 取得置頂文章列表
     * @param int $limit
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array;

    /**
     * 依標籤 ID 取得文章列表
     * @param int $tagId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array;

    /**
     * 更新文章觀看次數
     * @param int $id
     * @param string $userIp
     * @param int|null $userId
     * @return bool
     */
    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool;

    /**
     * 設定文章置頂狀態
     * @param int $id
     * @param bool $isPinned
     * @return bool
     */
    public function setPinned(int $id, bool $isPinned): bool;

    /**
     * 設定文章標籤
     * @param int $id
     * @param array $tagIds
     * @return bool
     */
    public function setTags(int $id, array $tagIds): bool;
}
