<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Services\Contracts\PostServiceInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\Validators\PostValidator;
use App\Services\Enums\PostStatus;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StateTransitionException;
use DateTimeImmutable;

class PostService implements PostServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly PostValidator $validator
    ) {
    }

    public function createPost(array $data): Post
    {
        $this->validator->validate($data);

        // 如果沒有指定狀態，預設為草稿
        if (!isset($data['status'])) {
            $data['status'] = PostStatus::DRAFT->value;
        }

        // 設定建立時間
        $data['created_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::RFC3339);

        return $this->repository->create($data);
    }

    public function updatePost(int $id, array $data): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        $this->validator->validate($data);

        // 處理狀態轉換
        if (isset($data['status'])) {
            $currentStatus = PostStatus::from($post->getStatus());
            $targetStatus = PostStatus::from($data['status']);

            if (!$currentStatus->canTransitionTo($targetStatus)) {
                throw new StateTransitionException(
                    sprintf(
                        '無法將文章從「%s」狀態變更為「%s」',
                        $currentStatus->getLabel(),
                        $targetStatus->getLabel()
                    )
                );
            }
        }

        // 設定更新時間
        $data['updated_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::RFC3339);

        return $this->repository->update($id, $data);
    }

    public function deletePost(int $id): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 已發布的文章不能刪除，只能封存
        if (PostStatus::from($post->getStatus()) === PostStatus::PUBLISHED) {
            throw new StateTransitionException('已發布的文章不能刪除，請改為封存');
        }

        return $this->repository->delete($id);
    }

    public function getPost(int $id): Post
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        return $post;
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
     * 取得文章列表
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return array{items: Post[], total: int, page: int, perPage: int}
     */
    public function listPosts(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginate($page, $perPage, $filters);
    }

    /**
     * 取得置頂文章列表
     * @param int $limit 取得筆數
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array
    {
        // 只取得已發布的置頂文章
        $filters = ['status' => PostStatus::PUBLISHED->value, 'is_pinned' => true];
        return $this->repository->getPinnedPosts($limit, $filters);
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        $post = $this->repository->find($id);
        if (!$post) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 只有已發布的文章可以置頂
        if ($isPinned && PostStatus::from($post->getStatus()) !== PostStatus::PUBLISHED) {
            throw new StateTransitionException('只有已發布的文章可以置頂');
        }

        return $this->repository->setPinned($id, $isPinned);
    }

    /**
     * 設定文章標籤
     * @param int $id 文章 ID
     * @param array<int> $tagIds 標籤 ID 陣列
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
        $post = $this->getPost($id);

        // 檢查 IP 格式
        if (!filter_var($userIp, FILTER_VALIDATE_IP)) {
            throw new ValidationException('無效的 IP 位址');
        }

        // 只有已發布的文章才能計算瀏覽次數
        if ($post->getStatus() !== PostStatus::PUBLISHED->value) {
            return false;
        }

        return $this->repository->incrementViews($id, $userIp, $userId);
    }
}
