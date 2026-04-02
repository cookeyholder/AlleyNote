<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\StateTransitionException;
use App\Domains\Post\Models\Post;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Throwable;

/**
 * 貼文領域服務.
 */
class PostService implements PostServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
    ) {}

    /**
     * 建立新貼文.
     */
    public function createPost(array $data): Post
    {
        $post = new Post($data);
        $this->repository->store($post);

        return $post;
    }

    /**
     * 更新貼文.
     */
    public function updatePost(int $id, array $data): Post
    {
        $post = $this->findById($id);
        $post->update($data);
        $this->repository->update($post);

        return $post;
    }

    /**
     * 刪除貼文.
     */
    public function deletePost(int $id): void
    {
        $this->repository->delete($id);
    }

    /**
     * 取得單一貼文.
     */
    public function findById(int $id): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new PostNotFoundException($id);
        }

        return $post;
    }

    /**
     * 取得分頁貼文列表.
     */
    public function listPosts(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->paginate($filters, $page, $perPage);
    }

    /**
     * 取得置頂貼文.
     */
    public function getPinnedPosts(int $limit = 5): array
    {
        return $this->repository->getPinned($limit);
    }

    /**
     * 設定貼文置頂狀態.
     */
    public function setPinned(int $id, bool $isPinned): void
    {
        $post = $this->findById($id);
        $this->repository->setPinned($id, $isPinned);
    }

    /**
     * 取得貼文標籤.
     */
    public function getPostTags(int $id): array
    {
        return $this->repository->getTags($id);
    }

    /**
     * 設定貼文標籤.
     */
    public function setPostTags(int $id, array $tagIds): void
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 確保所有標籤 ID 都是整數
        $tagIds = array_map('intval', array_unique($tagIds));

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

        // 將字串狀態轉換為 PostStatus 枚舉
        try {
            $targetStatus = PostStatus::from($status);
        } catch (Throwable $e) {
            throw ValidationException::fromSingleError(
                'status',
                sprintf('無效的狀態值: "%s"。可接受的值有: %s', $status, implode(', ', array_map(fn($s) => $s->value, PostStatus::cases()))),
            );
        }

        // 使用狀態機驗證狀態轉換
        /** @var PostStatus $currentStatus */
        $currentStatus = $post->getStatus();
        if (!$currentStatus->canTransitionTo($targetStatus)) {
            throw new StateTransitionException(
                sprintf(
                    '狀態轉換失敗：無法從目前的「%s」狀態變更為目標「%s」狀態。',
                    $currentStatus->getLabel(),
                    $targetStatus->getLabel(),
                ),
            );
        }

        $post->updateStatus($targetStatus);
        $this->repository->update($post);

        return $post;
    }

    /**
     * 置頂貼文.
     */
    public function pinPost(int $id): Post
    {
        $this->setPinned($id, true);

        return $this->findById($id);
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
