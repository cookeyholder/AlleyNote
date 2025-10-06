<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\UserRepositoryInterface;

/**
 * UserRepository 適配器.
 *
 * 將現有的 UserRepository 適配到 UserRepositoryInterface
 * 這是一個暫時的解決方案，直到 UserRepository 完全實作介面
 */
class UserRepositoryAdapter implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        // 委託給原始 repository 的相應方法
        $result = $this->userRepository->findByUsername($username);

        /** @var array<string, mixed>|null */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        // 委託給原始 repository
        $result = $this->userRepository->findByEmail($email);

        /** @var array<string, mixed>|null */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $uuid): ?array
    {
        // 委託給原始 repository
        $result = $this->userRepository->findByUuid($uuid);

        /** @var array<string, mixed>|null */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validateCredentials(string $username, string $password): ?array
    {
        // 嘗試通過 email 查找使用者
        $user = $this->userRepository->findByEmail($username);
        if (!is_array($user)) {
            // 如果通過 email 找不到，嘗試通過 username 查找
            $user = $this->userRepository->findByUsername($username);
        }

        if (!is_array($user)) {
            return null;
        }

        // 驗證密碼
        // 支援 password 和 password_hash 兩種欄位名稱
        $passwordHash = $user['password_hash'] ?? $user['password'] ?? null;
        
        if ($passwordHash === null) {
            return null;
        }

        // 使用 password_verify 驗證密碼
        if (!password_verify($password, $passwordHash)) {
            return null;
        }

        /** @var array<string, mixed> */
        return $user;
    }

    public function updateLastLogin(int $userId): bool
    {
        // 委託給原始 repository，但需要轉換參數類型
        return $this->userRepository->updateLastLogin((string) $userId);
    }

    public function findById(int $id): ?array
    {
        // 委託給原始 repository
        return $this->userRepository->findById($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->userRepository->create($data);

        return $result;
    }

    public function update(int $id, array $data): bool
    {
        // 暫時實作 - 返回 true
        return true;
    }

    public function delete(int $id): bool
    {
        // 暫時實作 - 返回 true
        return true;
    }

    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        return false;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        return false;
    }

    public function forceDelete(int $id): bool
    {
        return true;
    }

    public function restore(int $id): bool
    {
        return true;
    }

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return [];
    }

    public function getTrashed(int $page = 1, int $perPage = 10): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function search(string $keyword, array $fields = [], int $limit = 10): array
    {
        return [];
    }

    public function getStats(array $conditions = []): array
    {
        return [];
    }
}
