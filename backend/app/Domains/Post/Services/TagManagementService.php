<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\DTOs\CreateTagDTO;
use App\Domains\Post\DTOs\UpdateTagDTO;
use App\Domains\Post\Models\Tag;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Support\Str;

/**
 * 標籤管理服務.
 */
class TagManagementService
{
    /**
     * 取得標籤列表.
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function listTags(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $query = Tag::query();

        // 搜尋過濾
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 排序
        $query->orderBy('usage_count', 'desc')->orderBy('name', 'asc');

        // 分頁
        $total = $query->count();
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $tags = $query->skip($offset)->take($perPage)->get();

        return [
            'items' => $tags->map(fn($tag) => $tag->toArray())->toArray(),
            'total' => $total,
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
        $tag = Tag::find($id);

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
        if (!empty($dto->name) && Tag::where('name', $dto->name)->exists()) {
            $errors['name'] = ['標籤名稱已存在'];
        }

        // 產生 slug
        $slug = $dto->slug ?? Str::slug($dto->name);

        // 檢查 slug 是否重複
        if (Tag::where('slug', $slug)->exists()) {
            $errors['slug'] = ['標籤 slug 已存在'];
        }

        if (!empty($errors)) {
            throw new ValidationException('標籤資料驗證失敗', $errors);
        }

        // 建立標籤
        $tag = Tag::create([
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
        $tag = Tag::find($dto->id);

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
            } elseif (Tag::where('name', $dto->name)->where('id', '!=', $dto->id)->exists()) {
                $errors['name'] = ['標籤名稱已存在'];
            }
        }

        if ($dto->slug !== null) {
            if (Tag::where('slug', $dto->slug)->where('id', '!=', $dto->id)->exists()) {
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
                $updateData['slug'] = Str::slug($dto->name);
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

        if (!empty($updateData)) {
            $tag->update($updateData);
        }

        return $tag->fresh()->toArray();
    }

    /**
     * 刪除標籤.
     *
     * @throws NotFoundException
     */
    public function deleteTag(int $id): void
    {
        $tag = Tag::find($id);

        if (!$tag) {
            throw new NotFoundException("標籤不存在 (ID: {$id})");
        }

        // 解除與文章的關聯
        $tag->posts()->detach();

        // 刪除標籤
        $tag->delete();
    }
}
