<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

/**
 * 使用者資料庫介面.
 *
 * 定義使用者相關的資料庫操作標準介面
 */
interface UserRepositoryInterface
{
    /**
     * 根據使用者名稱查找使用者.
     *
     * @param string $username 使用者名稱
     * @return array<string, mixed>|null 使用者資料陣列或 null
     */
    public function findByUsername(string $username): ?array;

    /**
     * 根據電子郵件查找使用者.
     *
     * @param string $email 電子郵件
     * @return array<string, mixed>|null 使用者資料陣列或 null
     */
    public function findByEmail(string $email): ?array;

    /**
     * 根據 UUID 查找使用者.
     *
     * @param string $uuid 使用者 UUID
     * @return array<string, mixed>|null 使用者資料陣列或 null
     */
    public function findByUuid(string $uuid): ?array;

    /**
     * 根據 ID 查找使用者.
     *
     * @param int $id 使用者 ID
     * @return array<string, mixed>|null 使用者資料陣列或 null
     */
    public function findById(int $id): ?array;

    /**
     * 根據 ID 查找使用者（包含角色資訊）.
     *
     * @param int $id 使用者 ID
     * @return array<string, mixed>|null 使用者資料陣列（包含 roles 欄位）或 null
     */
    public function findByIdWithRoles(int $id): ?array;

    /**
     * 驗證使用者登入憑證.
     *
     * @param string $username 使用者名稱或電子郵件
     * @param string $password 密碼
     * @return array<string, mixed>|null 驗證成功返回使用者資料，失敗返回 null
     */
    public function validateCredentials(string $username, string $password): ?array;

    /**
     * 更新使用者最後登入時間.
     *
     * @param int $userId 使用者 ID
     */
    public function updateLastLogin(int $userId): bool;

    /**
     * 檢查使用者名稱是否已存在.
     *
     * @param string $username 使用者名稱
     * @param int|null $excludeUserId 排除的使用者 ID（用於更新時檢查）
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool;

    /**
     * 檢查電子郵件是否已存在.
     *
     * @param string $email 電子郵件
     * @param int|null $excludeUserId 排除的使用者 ID（用於更新時檢查）
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool;

    /**
     * 建立新使用者.
     *
     * @param array<string, mixed> $data 使用者資料
     * @return array<string, mixed> 建立的使用者資料
     */
    public function create(array $data): array;

    /**
     * 更新使用者資料.
     *
     * @param int $id 使用者 ID
     * @param array<string, mixed> $data 更新的資料
     */
    public function update(int $id, array $data): bool;

    /**
     * 軟刪除使用者.
     *
     * @param int $id 使用者 ID
     */
    public function delete(int $id): bool;

    /**
     * 永久刪除使用者.
     *
     * @param int $id 使用者 ID
     */
    public function forceDelete(int $id): bool;

    /**
     * 復原已軟刪除的使用者.
     *
     * @param int $id 使用者 ID
     */
    public function restore(int $id): bool;

    /**
     * 取得使用者列表（分頁）.
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array<string, mixed> $filters 篩選條件
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * 取得已軟刪除的使用者列表.
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     */
    public function getTrashed(int $page = 1, int $perPage = 10): array;

    /**
     * 搜尋使用者.
     *
     * @param string $keyword 關鍵字
     * @param array<int, string>|array<string, mixed> $fields 搜尋欄位
     * @param int $limit 限制筆數
     * @return array<int, mixed>
     */
    public function search(string $keyword, array $fields = ['username', 'email'], int $limit = 10): array;

    /**
     * 統計使用者數量.
     *
     * @param array<string, mixed> $conditions 統計條件
     */
    public function getStats(array $conditions = []): array;
}
