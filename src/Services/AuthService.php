<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\DTOs\Auth\RegisterUserDTO;
use App\Services\Security\Contracts\PasswordSecurityServiceInterface;
use InvalidArgumentException;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordSecurityServiceInterface $passwordService
    ) {}

    public function register(RegisterUserDTO $dto): array
    {
        // DTO 已經在建構時進行基本驗證，這裡進行密碼安全性檢查
        $this->passwordService->validatePassword($dto->password);

        // 轉換為陣列並雜湊密碼
        $data = $dto->toArray();
        $data['password'] = $this->passwordService->hashPassword($data['password']);

        return $this->userRepository->create($data);
    }

    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user) {
            return [
                'success' => false,
                'message' => '無效的認證資訊'
            ];
        }

        if ($user['status'] === 0) {
            return [
                'success' => false,
                'message' => '帳號已被停用'
            ];
        }

        if (!password_verify($credentials['password'], $user['password'])) {
            return [
                'success' => false,
                'message' => '無效的認證資訊'
            ];
        }

        $this->userRepository->updateLastLogin($user['id']);

        unset($user['password']); // 移除敏感資訊

        return [
            'success' => true,
            'message' => '登入成功',
            'user' => $user
        ];
    }
}
