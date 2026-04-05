<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;

class UserManagementService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * 取得使用者列表.
     */
    public function listUsers(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->userRepository->paginate($page, $perPage, $filters);
    }

    /**
     * 取得單一使用者.
     */
    public function getUser(int $id): array
    {
        $user = $this->userRepository->findByIdWithRoles($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        // 移除敏感資訊
        unset($user['password_hash']);

        return $user;
    }

    /**
     * 建立使用者.
     */
    public function createUser(CreateUserDTO $dto): array
    {
        // 驗證使用者名稱是否已存在
        if ($this->userRepository->findByUsername($dto->username)) {
            throw ValidationException::fromSingleError('username', '使用者名稱已被使用');
        }
        // 驗證 email 是否已存在
        if ($this->userRepository->findByEmail($dto->email)) {
            throw ValidationException::fromSingleError('email', 'Email 已被使用');
        }
        // 驗證並雜湊密碼
        try {
            $hashedPassword = \App\Domains\Auth\ValueObjects\Password::fromPlainText($dto->password)->getHash();
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::fromSingleError('password', $e->getMessage());
        }
        // 建立使用者
        $user = $this->userRepository->create([
            'username' => $dto->username,
            'email' => $dto->email,
            'password' => $hashedPassword,
        ]);
        // 分配角色
        if (!empty($dto->roleIds)) {
            $this->userRepository->setUserRoles($user['id'], $dto->roleIds);
        }

        // 重新取得完整資訊
        return $this->getUser($user['id']);
    }

    /**
     * 更新使用者.
     */
    public function updateUser(int $id, UpdateUserDTO $dto): array
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        $updateData = [];
        // 檢查使用者名稱是否已被其他人使用
        if ($dto->username !== null) {
            $existing = $this->userRepository->findByUsername($dto->username);
            if ($existing && $existing['id'] != $id) {
                throw ValidationException::fromSingleError('username', '使用者名稱已被使用');
            }
            $updateData['username'] = $dto->username;
        }
        // 檢查 email 是否已被其他人使用
        if ($dto->email !== null) {
            $existing = $this->userRepository->findByEmail($dto->email);
            if ($existing && $existing['id'] != $id) {
                throw ValidationException::fromSingleError('email', 'Email 已被使用');
            }
            $updateData['email'] = $dto->email;
        }
        // 更新密碼
        if ($dto->password !== null) {
            try {
                $updateData['password'] = \App\Domains\Auth\ValueObjects\Password::fromPlainText($dto->password)->getHash();
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::fromSingleError('password', $e->getMessage());
            }
        }
        // 更新基本資料
        if (!empty($updateData)) {
            $this->userRepository->update((string) $id, $updateData);
        }
        // 更新角色
        if ($dto->roleIds !== null) {
            $this->userRepository->setUserRoles($id, $dto->roleIds);
        }

        // 重新取得完整資訊
        return $this->getUser($id);
    }

    /**
     * 刪除使用者.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }

        return $this->userRepository->delete((string) $id);
    }

    /**
     * 分配角色給使用者.
     *
     * @param int[] $roleIds
     */
    public function assignRoles(int $userId, array $roleIds): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }

        return $this->userRepository->setUserRoles($userId, $roleIds);
    }

    /**
     * 取得使用者的角色.
     *
     * @return int[]
     */
    public function getUserRoles(int $userId): array
    {
        return $this->userRepository->getUserRoleIds($userId);
    }

    /**
     * 啟用使用者.
     */
    public function activateUser(int $id): array
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        $this->userRepository->update((string) $id, ['is_active' => true]);

        return $this->getUser($id);
    }

    /**
     * 停用使用者.
     */
    public function deactivateUser(int $id): array
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        $this->userRepository->update((string) $id, ['is_active' => false]);

        return $this->getUser($id);
    }

    /**
     * 重設使用者密碼
     */
    public function resetPassword(int $id, string $newPassword): bool
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        // 驗證並雜湊密碼
        try {
            $hashedPassword = \App\Domains\Auth\ValueObjects\Password::fromPlainText($newPassword)->getHash();
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::fromSingleError('password', $e->getMessage());
        }
        $this->userRepository->update((string) $id, ['password' => $hashedPassword]);

        return true;
    }

    /**
     * 變更使用者密碼（需驗證舊密碼）.
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new NotFoundException('使用者不存在');
        }
        // 驗證當前密碼
        $passwordHash = $user['password'] ?? $user['password_hash'] ?? '';
        if (!password_verify($currentPassword, $passwordHash)) {
            throw ValidationException::fromSingleError('current_password', '當前密碼不正確');
        }
        // 驗證並雜湊新密碼
        try {
            $hashedPassword = \App\Domains\Auth\ValueObjects\Password::fromPlainText($newPassword)->getHash();
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::fromSingleError('new_password', $e->getMessage());
        }
        $this->userRepository->update((string) $id, ['password' => $hashedPassword]);

        return true;
    }
}
