<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    /**
     * 依 ID 查詢
     * @param int $id
     * @return object|null
     */
    public function find(int $id): ?object;

    /**
     * 依 UUID 查詢
     * @param string $uuid
     * @return object|null
     */
    public function findByUuid(string $uuid): ?object;

    /**
     * 建立新記錄
     * @param array $data
     * @return object
     */
    public function create(array $data): object;

    /**
     * 更新記錄
     * @param int $id
     * @param array $data
     * @return object
     */
    public function update(int $id, array $data): object;

    /**
     * 刪除記錄
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * 取得分頁資料
     * @param int $page
     * @param int $perPage
     * @param array $conditions
     * @return array
     */
    public function paginate(int $page, int $perPage, array $conditions = []): array;
}
