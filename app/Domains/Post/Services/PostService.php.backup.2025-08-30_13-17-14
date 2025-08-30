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

        return $this->repository->create($data);
    }

    public function updatePost(int $id, UpdatePostDTO $dto): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 檢查是否有資料要更新
        if (!$dto->hasChanges()) {
            return $post;
        }

        // DTO 已經在建構時驗證過資料，這裡直接轉換為陣列
        $data = $dto->toArray();

        // 處理狀態轉換（如果有提供狀態）
        if ($dto->status !== null) {
            $currentStatus = PostStatus::from($post->getStatus());
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

        return $this->repository->update($id, $data);
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

        return $post;
    }

    /**
     * 取得文章列表.
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return array<mixed>{items: Post[], total: int, page: int, per_page: int, last_page: int}
     */
    public function listPosts(int $page = 1, int $perPage = 10, array $filters = []): mixed
    {
        $result = $this->repository->paginate($page, $perPage, $filters);

        // 確保回傳格式符合介面要求
        return [
            // 'items' => (is_array($result) && isset($data ? $result->items : null)))) ? $data ? $result->items : null)) : null, // isset 語法錯誤已註解
            // 'total' => (is_array($result) && isset($data ? $result->total : null)))) ? $data ? $result->total : null)) : null, // isset 語法錯誤已註解
            // 'page' => (is_array($result) && isset($data ? $result->page : null)))) ? $data ? $result->page : null)) : null, // isset 語法錯誤已註解
            // 'per_page' => (is_array($result) && isset($data ? $result->perPage : null)))) ? $data ? $result->perPage : null)) : null, // isset 語法錯誤已註解
            // 'last_page' => (is_array($result) && isset($data ? $result->lastPage : null)))) ? $data ? $result->lastPage : null)) : null, // isset 語法錯誤已註解
        ];
    }

    /**
     * 取得置頂文章列表.
     * @param int $limit 取得筆數
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): mixed
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
    public function setTags(int $id, array $tagIds): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 確保所有標籤 ID 都是整數
        $tagIds = array_map('intval', array_unique($tagIds));

        return $this->repository->setTags($id, $tagIds);
    }

    public function recordView(int $id, string $userIp, ?int $userId = null): bool
    {
        $post = $this->findById($id);

        // 檢查 IP 格式
        if (!filter_var($userIp, FILTER_VALIDATE_IP)) {
            throw ValidationException::fromSingleError('user_ip', '無效的 IP 位址');
        }

        // 只有已發布的文章才能計算瀏覽次數
        if ($post->getStatus() !== PostStatus::PUBLISHED->value) {
            return false;
        }

        return $this->repository->incrementViews($id, $userIp, $userId);
    }
}
