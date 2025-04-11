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
    private const VALID_STATUSES = ['draft', 'published', 'archived'];

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
        if (empty($data['title'])) {
            throw new ValidationException('文章標題不可為空');
        }
        if (strlen($data['title']) > 255) {
            throw new ValidationException('文章標題不可超過 255 字元');
        }
        if (empty($data['content'])) {
            throw new ValidationException('文章內容不可為空');
        }
        if (isset($data['user_ip']) && !filter_var($data['user_ip'], FILTER_VALIDATE_IP)) {
            throw new ValidationException('無效的 IP 位址格式');
        }
        if (isset($data['publish_date']) && !strtotime($data['publish_date'])) {
            throw new ValidationException('無效的發布日期格式');
        }
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            throw new ValidationException('無效的文章狀態');
        }
    }
}
