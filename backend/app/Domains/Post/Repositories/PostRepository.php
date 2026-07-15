<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use PDO;

class PostRepository implements PostRepositoryInterface
{
    private PostCrudRepository $crud;

    private PostSearchRepository $search;

    private PostAnalyticsRepository $analytics;

    public function __construct(
        PDO $db,
        CacheServiceInterface $cache,
        LoggingSecurityServiceInterface $logger,
    ) {
        $this->crud = new PostCrudRepository($db, $cache, $logger);
        $this->search = new PostSearchRepository($db, $cache);
        $this->analytics = new PostAnalyticsRepository($db, $cache);
    }

    public function find(int $id): ?Post
    {
        return $this->crud->find($id);
    }

    public function findWithLock(int $id): ?Post
    {
        return $this->crud->findWithLock($id);
    }

    public function findByUuid(string $uuid): ?Post
    {
        return $this->crud->findByUuid($uuid);
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        return $this->crud->findBySeqNumber($seqNumber);
    }

    public function safeDelete(int $id): bool
    {
        return $this->crud->safeDelete($id);
    }

    public function safeSetPinned(int $id, bool $isPinned): bool
    {
        return $this->crud->safeSetPinned($id, $isPinned);
    }

    public function create(array $data, array $tagIds = []): Post
    {
        return $this->crud->create($data, $tagIds);
    }

    public function update(int $id, array $data): Post
    {
        return $this->crud->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->crud->delete($id);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        return $this->crud->paginate($page, $perPage, $conditions);
    }

    /**
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array
    {
        return $this->crud->getPinnedPosts($limit);
    }

    /**
     * @return array{items: Post[], total: int, page: int, perPage: int, lastPage: int}
     */
    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array
    {
        return $this->crud->getPostsByTag($tagId, $page, $perPage);
    }

    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool
    {
        return $this->crud->incrementViews($id, $userIp, $userId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPostTags(int $id): array
    {
        return $this->crud->getPostTags($id);
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        return $this->crud->setPinned($id, $isPinned);
    }

    public function setTags(int $id, array $tagIds): void
    {
        $this->crud->setTags($id, $tagIds);
    }

    /**
     * @return Post[]
     */
    public function searchByTitle(string $title): array
    {
        return $this->search->searchByTitle($title);
    }

    public function findLatestByUserId(int $userId): ?Post
    {
        return $this->search->findLatestByUserId($userId);
    }

    /**
     * @return Post[]
     */
    public function search(string $keyword): array
    {
        return $this->search->search($keyword);
    }

    /**
     * @return Post[]
     */
    public function findByCreationSource(string $creationSource, int $limit = 10, int $offset = 0): array
    {
        return $this->analytics->findByCreationSource($creationSource, $limit, $offset);
    }

    /**
     * @return array<string, int>
     */
    public function getSourceDistribution(): array
    {
        return $this->analytics->getSourceDistribution();
    }

    /**
     * @return Post[]
     */
    public function findByCreationSourceAndDetail(
        string $creationSource,
        ?string $creationSourceDetail = null,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->analytics->findByCreationSourceAndDetail($creationSource, $creationSourceDetail, $limit, $offset);
    }

    public function countByCreationSource(string $creationSource): int
    {
        return $this->analytics->countByCreationSource($creationSource);
    }

    /**
     * @return array{items: Post[], total: int, page: int, perPage: int, lastPage: int}
     */
    public function paginateByCreationSource(
        string $creationSource,
        int $page = 1,
        int $perPage = 10,
    ): array {
        return $this->analytics->paginateByCreationSource($creationSource, $page, $perPage);
    }
}
