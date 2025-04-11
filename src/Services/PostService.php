<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Services\Contracts\PostServiceInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class PostService implements PostServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $repository
    ) {}

    public function createPost(array $data): Post
    {
        $this->validatePostData($data);
        return $this->repository->create($data);
    }

    public function updatePost(int $id, array $data): Post
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        $this->validatePostData($data);
        return $this->repository->update($id, $data);
    }

    public function deletePost(int $id): bool
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        return $this->repository->delete($id);
    }

    public function listPosts(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginate($page, $perPage, $filters);
    }

    public function getPinnedPosts(int $limit = 5): array
    {
        return $this->repository->getPinnedPosts($limit);
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        return $this->repository->setPinned($id, $isPinned);
    }

    public function setTags(int $id, array $tagIds): bool
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        // 確保所有標籤 ID 都是整數
        $tagIds = array_map('intval', array_unique($tagIds));

        return $this->repository->setTags($id, $tagIds);
    }

    public function recordView(int $id, string $userIp, ?int $userId = null): bool
    {
        if (!$this->repository->find($id)) {
            throw new NotFoundException('找不到指定的文章');
        }

        if (!is_valid_ip($userIp)) {
            throw new ValidationException('無效的 IP 位址');
        }

        return $this->repository->incrementViews($id, $userIp, $userId);
    }

    /**
     * 驗證文章資料
     * @param array $data 文章資料
     * @throws ValidationException
     */
    private function validatePostData(array $data): void
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = '標題不能為空';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors['title'] = '標題不能超過 255 個字元';
        }

        if (empty($data['content'])) {
            $errors['content'] = '內容不能為空';
        }

        if (isset($data['user_ip']) && !is_valid_ip($data['user_ip'])) {
            $errors['user_ip'] = '無效的 IP 位址';
        }

        if (!empty($errors)) {
            throw new ValidationException('文章資料驗證失敗', $errors);
        }
    }
}
