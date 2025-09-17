<?php

declare(strict_types=1);

namespace App\Domains\Post\Contracts;

use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Models\Post;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;

interface PostServiceInterface
{
    /**
     * 建立新文章.
     * @param CreatePostDTO $dto 文章資料
     * @throws ValidationException
     */
    public function createPost(CreatePostDTO $dto): Post;

    /**
     * 更新文章.
     * @param int $id 文章 ID
     * @throws ValidationException
     * @throws NotFoundException
     */
    public function updatePost(int $id, UpdatePostDTO $dto): Post;

    /**
     * 刪除文章.
     * @param int $id 文章 ID
     * @throws NotFoundException
     */
    public function deletePost(int $id): bool;

    /**
     * 根據 ID 查詢文章.
     * @param int $id 文章 ID
     * @throws NotFoundException
     */
    public function findById(int $id): Post;

    /**
     * 取得文章清單（分頁）.
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array<string, mixed> $filters 篩選條件
     * @return array{
     *     items: array<Post>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     last_page: int
     * }
     */
    public function listPosts(int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * 取得置頂文章.
     * @param int $limit 限制筆數
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array;

    /**
     * 設定文章置頂狀態.
     * @param int $id 文章 ID
     * @throws NotFoundException
     */
    public function setPinned(int $id, bool $isPinned): bool;

    /**
     * 為文章設定標籤。
     *
     * @param array<int, int> $tagIds
     */
    public function setTags(int $postId, array $tagIds): bool;

    /**
     * 記錄文章觀看.
     * @param int $id 文章 ID
     * @param int|null $userId 使用者 ID（若已登入）
     * @throws NotFoundException
     */
    public function recordView(int $id, string $userIp, ?int $userId = null): bool;
}
