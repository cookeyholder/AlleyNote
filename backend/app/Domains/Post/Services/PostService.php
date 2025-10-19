<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class PostService implements PostServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
    ) {}

    public function createPost(CreatePostDTO $dto): Post
    {
        // DTO 已經在建構時驗證過資料，這裡直接轉換為陣列
        $data = $dto->toArray();

        // 設定建立時間
        // // $data ? $data->created_at : null)) = new DateTimeImmutable()->format(DateTimeImmutable::RFC3339); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

        $post = $this->repository->create($data);

        if (!$post instanceof Post) {
            throw new RuntimeException('Failed to create post');
        }

        return $post;
    }

    public function updatePost(int $id, UpdatePostDTO $dto): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        if (!$post instanceof Post) {
            throw new RuntimeException('Invalid post object from repository');
        }

        // 檢查是否有資料要更新
        if (!$dto->hasChanges()) {
            return $post;
        }

        // DTO 已經在建構時驗證過資料，這裡直接轉換為陣列
        $data = $dto->toArray();

        // 處理狀態轉換（如果有提供狀態）
        if ($dto->status !== null) {
            /** @var PostStatus $currentStatus */
            $currentStatus = $post->getStatus();
            $targetStatus = $dto->status;

            if (!$currentStatus->canTransitionTo($targetStatus)) {
                throw new StateTransitionException(
                    sprintf(
                        '無法將文章從「%s」狀態變更為「%s」',
                        $currentStatus->getLabel(),
                        $targetStatus->getLabel(),
                    ),
                );
            }
        }

        // 設定更新時間
        // // $data ? $data->updated_at : null)) = new DateTimeImmutable()->format(DateTimeImmutable::RFC3339); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

        $updatedPost = $this->repository->update($id, $data);

        if (!$updatedPost instanceof Post) {
            throw new RuntimeException('Failed to update post');
        }

        return $updatedPost;
    }

    public function deletePost(int $id): bool
    {
        try {
            return $this->repository->safeDelete($id);
        } catch (InvalidArgumentException $e) {
            throw new StateTransitionException($e->getMessage());
        } catch (Exception $e) {
            error_log("刪除文章失敗 (ID: $id): " . $e->getMessage());

            throw new RuntimeException('刪除文章時發生錯誤');
        }
    }

    public function findById(int $id): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        if (!$post instanceof Post) {
            throw new RuntimeException('Invalid post object');
        }

        return $post;
    }

    /**
     * 取得文章列表.
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array<string, mixed> $filters 篩選條件
     * @return array{items: Post[], total: int, page: int, per_page: int, last_page: int}
     */
    public function listPosts(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $result = $this->repository->paginate($page, $perPage, $filters);

        // 確保回傳格式符合介面要求
        /** @var array<int|string, Post> $items */
        $items = is_array($result['items'] ?? null) ? $result['items'] : [];
        $total = is_int($result['total'] ?? null) ? $result['total'] : 0;
        $pageNum = is_int($result['page'] ?? null) ? $result['page'] : $page;
        $perPageNum = is_int($result['perPage'] ?? null) ? $result['perPage'] : $perPage;
        $lastPageNum = is_int($result['lastPage'] ?? null) ? $result['lastPage'] : 1;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $pageNum,
            'per_page' => $perPageNum,
            'last_page' => $lastPageNum,
        ];
    }

    /**
     * 取得置頂文章列表.
     * @param int $limit 取得筆數
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array
    {
        return $this->repository->getPinnedPosts($limit);
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        try {
            return $this->repository->safeSetPinned($id, $isPinned);
        } catch (InvalidArgumentException $e) {
            throw new StateTransitionException($e->getMessage());
        } catch (Exception $e) {
            error_log("設定置頂狀態失敗 (ID: $id): " . $e->getMessage());

            throw new RuntimeException('設定置頂狀態時發生錯誤');
        }
    }

    /**
     * 設定文章標籤.
     * @param int $id 文章 ID
     * @param array $tagIds 標籤 ID 陣列
     */
    public function setTags(int $id, array $tagIds): void
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 確保所有標籤 ID 都是整數
        /** @var array<int> */
        $tagIds = array_map(
            /** @param mixed $id */
            fn($id): int => is_numeric($id) ? (int) $id : 0,
            array_unique($tagIds)
        );

        $this->repository->setTags($id, $tagIds);
    }

    public function recordView(int $id, string $userIp, ?int $userId = null): bool
    {
        $post = $this->findById($id);

        // 檢查 IP 格式
        if (!filter_var($userIp, FILTER_VALIDATE_IP)) {
            throw ValidationException::fromSingleError('user_ip', '無效的 IP 位址');
        }

        // 只有已發布的文章才能計算瀏覽次數
        if (!$post->hasStatus(PostStatus::PUBLISHED)) {
            return false;
        }

        return $this->repository->incrementViews($id, $userIp, $userId);
    }

    /**
     * 更新貼文狀態.
     */
    public function updatePostStatus(int $id, string $status): Post
    {
        $post = $this->findById($id);

        // 驗證狀態值
        $validStatuses = ['draft', 'published', 'archived'];
        if (!in_array($status, $validStatuses)) {
            throw ValidationException::fromSingleError('status', '無效的狀態值');
        }

        // 更新狀態
        $updated = $this->repository->update($id, ['status' => $status]);

        if (!$updated instanceof Post) {
            throw new RuntimeException('Failed to update post status');
        }

        return $updated;
    }

    /**
     * 取消置頂貼文.
     */
    public function unpinPost(int $id): Post
    {
        $this->setPinned($id, false);

        return $this->findById($id);
    }
}
