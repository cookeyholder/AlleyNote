<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\Contracts\TagRepositoryInterface;
use App\Domains\Post\DTOs\CreateTagDTO;
use App\Domains\Post\DTOs\UpdateTagDTO;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 標籤管理服務.
 */
class TagManagementService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * 取得標籤列表.
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function listTags(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $result = $this->tagRepository->list($page, $perPage, $filters);

        $lastPage = (int) ceil($result['total'] / $perPage);

        return [
            'items' => array_map(fn($tag) => $tag->toArray(), $result['items']),
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * 取得單一標籤.
     *
     * @return array<string, mixed>
     * @throws NotFoundException
     */
    public function getTag(int $id): array
    {
        $tag = $this->tagRepository->findById($id);

        if (!$tag) {
            throw new NotFoundException("標籤不存在 (ID: {$id})");
        }

        return $tag->toArray();
    }

    /**
     * 建立標籤.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function createTag(CreateTagDTO $dto): array
    {
        $this->logger->debug('TagManagementService::createTag received payload', [
            'payload' => $dto->toArray(),
        ]);

        $errors = [];

        if ($dto->name === '') {
            $errors['name'] = ['標籤名稱為必填'];
        } elseif (mb_strlen($dto->name) > 50) {
            $errors['name'] = ['標籤名稱不可超過 50 字元'];
        }

        if ($dto->name !== '' && $this->tagRepository->findByName($dto->name)) {
            $errors['name'] = ['標籤名稱已存在'];
        }

        $slug = $dto->slug ?? $this->generateSlug($dto->name);

        if ($this->tagRepository->findBySlug($slug)) {
            $errors['slug'] = ['標籤 slug 已存在'];
        }

        if (!empty($errors)) {
            $this->logger->notice('TagManagementService::createTag validation failed', [
                'payload' => $dto->toArray(),
                'errors' => $errors,
            ]);

            throw ValidationException::fromMultipleErrors($errors, '標籤資料驗證失敗');
        }

        $tag = $this->tagRepository->create([
            'name' => $dto->name,
            'slug' => $slug,
            'description' => $dto->description,
            'color' => $dto->color,
            'usage_count' => 0,
        ]);

        $created = $tag->toArray();
        $this->logger->info('TagManagementService::createTag succeeded', [
            'tag' => $created,
        ]);

        return $created;
    }

    /**
     * 更新標籤.
     *
     * @return array<string, mixed>
     * @throws NotFoundException|ValidationException
     */
    public function updateTag(UpdateTagDTO $dto): array
    {
        $this->logger->debug('TagManagementService::updateTag received payload', [
            'payload' => $dto->toArray(),
        ]);

        $tag = $this->tagRepository->findById($dto->id);

        if (!$tag) {
            $this->logger->warning('TagManagementService::updateTag target not found', [
                'tagId' => $dto->id,
            ]);

            throw new NotFoundException("標籤不存在 (ID: {$dto->id})");
        }

        $errors = [];

        if ($dto->name !== null) {
            if ($dto->name === '') {
                $errors['name'] = ['標籤名稱為必填'];
            } elseif (mb_strlen($dto->name) > 50) {
                $errors['name'] = ['標籤名稱不可超過 50 字元'];
            } else {
                $existingTag = $this->tagRepository->findByName($dto->name);
                if ($existingTag && $existingTag->getId() !== $dto->id) {
                    $errors['name'] = ['標籤名稱已存在'];
                }
            }
        }

        if ($dto->slug !== null) {
            $existingTag = $this->tagRepository->findBySlug($dto->slug);
            if ($existingTag && $existingTag->getId() !== $dto->id) {
                $errors['slug'] = ['標籤 slug 已存在'];
            }
        }

        if (!empty($errors)) {
            $this->logger->notice('TagManagementService::updateTag validation failed', [
                'payload' => $dto->toArray(),
                'errors' => $errors,
            ]);

            throw ValidationException::fromMultipleErrors($errors, '標籤資料驗證失敗');
        }

        $updateData = [];
        if ($dto->name !== null) {
            $updateData['name'] = $dto->name;
            if ($dto->slug === null) {
                $updateData['slug'] = $this->generateSlug($dto->name);
            }
        }
        if ($dto->slug !== null) {
            $updateData['slug'] = $dto->slug;
        }
        if ($dto->description !== null) {
            $updateData['description'] = $dto->description;
        }
        if ($dto->color !== null) {
            $updateData['color'] = $dto->color;
        }

        $updatedTag = $this->tagRepository->update($dto->id, $updateData);

        $updated = $updatedTag->toArray();
        $this->logger->info('TagManagementService::updateTag succeeded', [
            'tag' => $updated,
        ]);

        return $updated;
    }

    /**
     * 刪除標籤.
     *
     * @throws NotFoundException
     */
    public function deleteTag(int $id): void
    {
        $tag = $this->tagRepository->findById($id);

        if (!$tag) {
            throw new NotFoundException("標籤不存在 (ID: {$id})");
        }

        $this->tagRepository->detachFromAllPosts($id);
        $this->tagRepository->delete($id);
    }

    /**
     * 生成 URL slug.
     */
    private function generateSlug(string $text): string
    {
        $slug = mb_strtolower($text, 'UTF-8');
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'tag-' . uniqid();
    }
}
