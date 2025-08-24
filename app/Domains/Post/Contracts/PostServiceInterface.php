<?php

declare(strict_types=1);

namespace App\Domains\Post\Contracts;

use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Models\Post;

interface PostServiceInterface
{
    /**
     * 建立新文章.
     * @param CreatePostDTO $dto 文章資料
     * @return Post
     * @throws ValidationException
     */
    public function createPost(CreatePostDTO $dto): Post;

    /**
     * 更新文章.
     * @param int $id 文章 ID
     * @param UpdatePostDTO $dto 更新資料
     * @return Post
     * @throws ValidationException
     * @throws NotFoundException
     */
    public function updatePost(int $id, UpdatePostDTO $dto): Post;

    /**
     * 刪除文章.
     * @param int $id 文章 ID
     * @return bool
     * @throws NotFoundException
     */
    public function deletePost(int $id): bool;

    /**
     * 根據 ID 查詢文章.
     * @param int $id 文章 ID
     * @return Post
     * @throws NotFoundException
     */
    public function findById(int $id): Post;

    /**
     * 取得文章列表（含分頁）.
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return array{
     *     items: Post[],
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
     * @param bool $isPinned 是否置頂
     * @return bool
     * @throws NotFoundException
     */
    public function setPinned(int $id, bool $isPinned): bool;

    /**
     * 設定文章標籤.
     * @param int $id 文章 ID
     * @param array $tagIds 標籤 ID 陣列
     * @return bool
     * @throws NotFoundException
     */
    public function setTags(int $id, array $tagIds): bool;

    /**
     * 記錄文章觀看.
     * @param int $id 文章 ID
     * @param string $userIp 使用者 IP
     * @param int|null $userId 使用者 ID（若已登入）
     * @return bool
     * @throws NotFoundException
     */
    public function recordView(int $id, string $userIp, ?int $userId = null): bool;
}
