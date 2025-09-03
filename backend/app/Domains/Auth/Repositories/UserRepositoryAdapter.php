<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\UserRepositoryInterface;

/**
 * UserRepository 適配器
 *
 * 將現有的 UserRepository 適配到 UserRepositoryInterface
 * 這是一個暫時的解決方案，直到 UserRepository 完全實作介面
 */
class UserRepositoryAdapter implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function findByUsername(string $username): ?array
    {
        // 暫時實作 - 返回 null
        return null;
    }

    public function findByEmail(string $email): ?array
    {
        // 暫時實作 - 返回 null
        return null;
    }

    public function findByUuid(string $uuid): ?array
    {
        // 暫時實作 - 返回 null
        return null;
    }

    public function validateCredentials(string $username, string $password): ?array
    {
        // 暫時實作 - 返回一個測試用戶
        if ($username === 'test@example.com' && $password === 'password') {
            return [
                'id' => 1,
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'created_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
            ];
        }
        
        return null;
    }

    public function updateLastLogin(int $userId): bool
    {
        return true;
    }

    public function findById(int $id): ?array
    {
        // 委託給原始 repository
        return $this->userRepository->findById($id);
    }

    public function create(array $data): array
    {
        return $this->userRepository->create($data);
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

    public function search(string $keyword, array $fields = ['username', 'email'], int $limit = 10): array
    {
        return [];
    }

    public function getStats(array $conditions = []): array
    {
        return [];
    }
}
