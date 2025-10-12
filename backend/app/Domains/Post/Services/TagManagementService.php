<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\Contracts\TagRepositoryInterface;
use App\Domains\Post\DTOs\CreateTagDTO;
use App\Domains\Post\DTOs\UpdateTagDTO;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;

/**
 * 標籤管理服務.
 */
class TagManagementService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

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
        // 驗證
        $errors = [];

        if (empty($dto->name)) {
            $errors['name'] = ['標籤名稱為必填'];
        } elseif (mb_strlen($dto->name) > 50) {
            $errors['name'] = ['標籤名稱不可超過 50 字元'];
        }

        // 檢查名稱是否重複
        if (!empty($dto->name) && $this->tagRepository->findByName($dto->name)) {
            $errors['name'] = ['標籤名稱已存在'];
        }

        // 產生 slug
        $slug = $dto->slug ?? $this->generateSlug($dto->name);

        // 檢查 slug 是否重複
        if ($this->tagRepository->findBySlug($slug)) {
            $errors['slug'] = ['標籤 slug 已存在'];
        }

        if (!empty($errors)) {
            throw new ValidationException('標籤資料驗證失敗', $errors);
        }

        // 建立標籤
        $tag = $this->tagRepository->create([
            'name' => $dto->name,
            'slug' => $slug,
            'description' => $dto->description,
            'color' => $dto->color,
            'usage_count' => 0,
        ]);

        return $tag->toArray();
    }

    /**
     * 更新標籤.
     *
     * @return array<string, mixed>
     * @throws NotFoundException|ValidationException
     */
    public function updateTag(UpdateTagDTO $dto): array
    {
        $tag = $this->tagRepository->findById($dto->id);

        if (!$tag) {
            throw new NotFoundException("標籤不存在 (ID: {$dto->id})");
        }

        // 驗證
        $errors = [];

        if ($dto->name !== null) {
            if (empty($dto->name)) {
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
            throw new ValidationException('標籤資料驗證失敗', $errors);
        }

        // 更新標籤
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

        return $updatedTag->toArray();
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

        // 解除與文章的關聯
        $this->tagRepository->detachFromAllPosts($id);

        // 刪除標籤
        $this->tagRepository->delete($id);
    }

    /**
     * 生成 URL slug.
     */
    private function generateSlug(string $text): string
    {
        // 轉小寫
        $slug = mb_strtolower($text, 'UTF-8');

        // 替換空白為連字號
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;

        // 移除特殊字元（保留中文、英文、數字、連字號）
        $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug) ?? $slug;

        // 移除多餘的連字號
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;

        // 移除頭尾的連字號
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'tag-' . uniqid();
    }
}
