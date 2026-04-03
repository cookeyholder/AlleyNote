<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

interface RepositoryInterface
{
    /**
     * 依 ID 查詢.
     */
    public function find(int $id): ?object;

    /**
     * 依 UUID 查詢.
     */
    public function findByUuid(string $uuid): ?object;

    /**
     * 建立新記錄.
     */
    public function create(array $data): object;

    /**
     * 更新記錄.
     */
    public function update(int $id, array $data): object;

    /**
     * 刪除記錄.
     */
    public function delete(int $id): bool;

    /**
     * 取得分頁資料.
     */
    public function paginate(int $page, int $perPage, array $conditions = []): array;
}
