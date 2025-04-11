<?php

namespace App\Services;

use App\Repositories\UserRepository;
use InvalidArgumentException;

class AuthService
{
    public function __construct(private UserRepository $userRepository) {}

    public function register(array $data): array
    {
        $this->validateRegistrationData($data);
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

    private function validateRegistrationData(array $data): void
    {
        if (empty($data['username'])) {
            throw new InvalidArgumentException('使用者名稱不能為空');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('無效的電子郵件格式');
        }

        if (strlen($data['password']) < 8) {
            throw new InvalidArgumentException('密碼長度必須至少為 8 個字元');
        }
    }
}
