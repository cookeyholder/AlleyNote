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
     * 依標籤 ID 取得文章列表.
     */
    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array;

    /**
     * 更新文章觀看次數.
     */
    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool;

    /**
     * 設定文章置頂狀態.
     */
    public function setPinned(int $id, bool $isPinned): bool;

    /**
     * 設定文章標籤.
     */
    public function setTags(int $id, array $tagIds): bool;

    /**
     * 依來源類型取得文章列表.
     *
     * @return Post[]
     */
    public function findByCreationSource(string $creationSource, int $limit = 10, int $offset = 0): array;

    /**
     * 取得來源分佈統計.
     *
     * @return array<string, int> 來源類型 => 文章數量的陣列
     */
    public function getSourceDistribution(): array;

    /**
     * 依來源類型和詳細資訊取得文章列表.
     *
     * @return Post[]
     */
    public function findByCreationSourceAndDetail(
        string $creationSource,
        ?string $creationSourceDetail = null,
        int $limit = 10,
        int $offset = 0,
    ): array;

    /**
     * 計算特定來源的文章總數.
     */
    public function countByCreationSource(string $creationSource): int;

    /**
     * 依來源類型取得分頁文章列表.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, lastPage: int}
     */
    public function paginateByCreationSource(
        string $creationSource,
        int $page = 1,
        int $perPage = 10,
    ): array;
}
