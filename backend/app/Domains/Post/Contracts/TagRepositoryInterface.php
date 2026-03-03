<?php

declare(strict_types=1);

namespace App\Domains\Post\Contracts;

use App\Domains\Post\Models\Tag;

/**
 * 標籤資料存取介面.
 */
interface TagRepositoryInterface
{
    /**
     * 取得標籤列表.
     *
     * @param array<string, mixed> $filters
     * @return array{items: array<int, Tag>, total: int}
     */
    public function list(int $page = 1, int $perPage = 20, array $filters = []): array;

    /**
     * 根據 ID 查找標籤.
     */
    public function findById(int $id): ?Tag;

    /**
     * 根據名稱查找標籤.
     */
    public function findByName(string $name): ?Tag;

    /**
     * 根據 slug 查找標籤.
     */
    public function findBySlug(string $slug): ?Tag;

    /**
     * 建立標籤.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Tag;

    /**
     * 更新標籤.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Tag;

    /**
     * 刪除標籤.
     */
    public function delete(int $id): bool;

    /**
     * 解除標籤與所有文章的關聯.
     */
    public function detachFromAllPosts(int $tagId): void;
}
