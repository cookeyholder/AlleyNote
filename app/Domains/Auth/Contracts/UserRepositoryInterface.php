<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

use App\Domains\User\Models\User;
use App\Shared\Contracts\RepositoryInterface;

/**
 * 使用者資料庫介面
 *
 * 定義使用者相關的資料庫操作標準介面
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * 根據使用者名稱查找使用者
     *
     * @param string $username 使用者名稱
     * @return User|null
     */
    public function findByUsername(string $username): ?User;

    /**
     * 根據電子郵件查找使用者
     *
     * @param string $email 電子郵件
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * 根據 UUID 查找使用者
     *
     * @param string $uuid 使用者 UUID
     * @return User|null
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * 驗證使用者登入憑證
     *
     * @param string $username 使用者名稱或電子郵件
     * @param string $password 密碼
     * @return User|null 驗證成功返回使用者，失敗返回 null
     */
    public function validateCredentials(string $username, string $password): ?User;

    /**
     * 更新使用者最後登入時間
     *
     * @param int $userId 使用者 ID
     * @return bool
     */
    public function updateLastLogin(int $userId): bool;

    /**
     * 檢查使用者名稱是否已存在
     *
     * @param string $username 使用者名稱
     * @param int|null $excludeUserId 排除的使用者 ID（用於更新時檢查）
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool;

    /**
     * 檢查電子郵件是否已存在
     *
     * @param string $email 電子郵件
     * @param int|null $excludeUserId 排除的使用者 ID（用於更新時檢查）
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool;

    /**
     * 建立新使用者
     *
     * @param array $data 使用者資料
     * @return User
     */
    public function create(array $data): User;

    /**
     * 更新使用者資料
     *
     * @param int $id 使用者 ID
     * @param array $data 更新的資料
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * 軟刪除使用者
     *
     * @param int $id 使用者 ID
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * 永久刪除使用者
     *
     * @param int $id 使用者 ID
     * @return bool
     */
    public function forceDelete(int $id): bool;

    /**
     * 復原已軟刪除的使用者
     *
     * @param int $id 使用者 ID
     * @return bool
     */
    public function restore(int $id): bool;

    /**
     * 取得使用者列表（分頁）
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @param array $filters 篩選條件
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array;

    /**
     * 取得已軟刪除的使用者列表
     *
     * @param int $page 頁碼
     * @param int $perPage 每頁筆數
     * @return array
     */
    public function getTrashed(int $page = 1, int $perPage = 10): array;

    /**
     * 搜尋使用者
     *
     * @param string $keyword 關鍵字
     * @param array $fields 搜尋欄位
     * @param int $limit 限制筆數
     * @return array
     */
    public function search(string $keyword, array $fields = ['username', 'email'], int $limit = 10): array;

    /**
     * 統計使用者數量
     *
     * @param array $conditions 統計條件
     * @return array
     */
    public function getStats(array $conditions = []): array;
}
