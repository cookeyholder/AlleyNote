<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use Exception;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordSecurityServiceInterface $passwordService,
        private ?JwtTokenServiceInterface $jwtTokenService = null,
        private bool $jwtEnabled = false,
    ) {}

    /**
     * 註冊新使用者.
     *
     * @return array<string, mixed>
     */
    public function register(RegisterUserDTO $dto, ?DeviceInfo $deviceInfo = null): array
    {
        // DTO 已經在建構時進行基本驗證，這裡進行密碼安全性檢查
        $this->passwordService->validatePassword($dto->password);

        // 轉換為陣列並雜湊密碼
        $data = $dto->toArray();
        $data['password'] = $this->passwordService->hashPassword((string) $data['password']);

        $user = $this->userRepository->create($data);

        // 如果啟用 JWT 且有提供 JWT 服務和裝置資訊，則產生 JWT token
        if ($this->jwtEnabled && $this->jwtTokenService !== null && $deviceInfo !== null) {
            try {
                $tokenPair = $this->jwtTokenService->generateTokenPair(
                    userId: (int) $user['id'],
                    deviceInfo: $deviceInfo,
                    customClaims: [
                        'type' => 'registration',
                        'username' => $user['username'],
                        'email' => $user['email'],
                    ],
                );

                return [
                    'success' => true,
                    'message' => '註冊成功',
                    'user' => $user,
                    'tokens' => [
                        'access_token' => $tokenPair->getAccessToken(),
                        'refresh_token' => $tokenPair->getRefreshToken(),
                        'token_type' => $tokenPair->getTokenType(),
                        'expires_in' => $tokenPair->getAccessTokenExpiresIn(),
                        'expires_at' => $tokenPair->getAccessTokenExpiresAt()->format('c'),
                    ],
                ];
            } catch (Exception $e) {
                // JWT 產生失敗時，記錄錯誤並回傳傳統格式
                error_log('JWT token 產生失敗: ' . $e->getMessage());
            }
        }

        // 傳統回傳格式（向後相容）
        return [
            'success' => true,
            'message' => '註冊成功',
            'user' => $user,
        ];
    }

    /**
     * 使用者登入.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials, ?DeviceInfo $deviceInfo = null): array
    {
        $user = $this->userRepository->findByEmail((string) $credentials['email']);

        if (!$user) {
            return [
                'success' => false,
                'message' => '無效的認證資訊',
            ];
        }

        if (!$this->isUserActive($user)) {
            return [
                'success' => false,
                'message' => '帳號已被停用',
            ];
        }

        if (!password_verify((string) $credentials['password'], (string) $user['password'])) {
            return [
                'success' => false,
                'message' => '無效的認證資訊',
            ];
        }

        $this->userRepository->updateLastLogin((string) $user['id']);

        unset($user['password']); // 移除敏感資訊

        // 如果啟用 JWT 且有提供 JWT 服務和裝置資訊，則產生 JWT token
        if ($this->jwtEnabled && $this->jwtTokenService !== null && $deviceInfo !== null) {
            try {
                $tokenPair = $this->jwtTokenService->generateTokenPair(
                    userId: (int) $user['id'],
                    deviceInfo: $deviceInfo,
                    customClaims: [
                        'type' => 'access',
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role'] ?? 'user',
                    ],
                );

                return [
                    'success' => true,
                    'message' => '登入成功',
                    'user' => $user,
                    'tokens' => [
                        'access_token' => $tokenPair->getAccessToken(),
                        'refresh_token' => $tokenPair->getRefreshToken(),
                        'token_type' => $tokenPair->getTokenType(),
                        'expires_in' => $tokenPair->getAccessTokenExpiresIn(),
                        'expires_at' => $tokenPair->getAccessTokenExpiresAt()->format('c'),
                    ],
                ];
            } catch (Exception $e) {
                // JWT 產生失敗時，記錄錯誤並回傳傳統格式
                error_log('JWT token 產生失敗: ' . $e->getMessage());
            }
        }

        // 傳統回傳格式（向後相容）
        return [
            'success' => true,
            'message' => '登入成功',
            'user' => $user,
        ];
    }

    /**
     * 使用者登出.
     *
     * @return array<string, mixed>
     */
    public function logout(?string $accessToken = null, ?DeviceInfo $deviceInfo = null): array
    {
        // 如果啟用 JWT 且有提供 JWT 服務和 access token
        if ($this->jwtEnabled && $this->jwtTokenService !== null && $accessToken !== null) {
            try {
                // 撤銷 access token（將其加入黑名單）
                $this->jwtTokenService->revokeToken($accessToken);

                return [
                    'success' => true,
                    'message' => '登出成功',
                ];
            } catch (Exception $e) {
                // JWT 撤銷失敗時，記錄錯誤
                error_log('JWT token 撤銷失敗: ' . $e->getMessage());
            }
        }

        // 傳統模式或 JWT 撤銷失敗時的回傳格式
        return [
            'success' => true,
            'message' => '登出成功',
        ];
    }

    /**
     * 檢查使用者是否為啟用狀態.
     *
     * @param array<string, mixed> $user
     */
    private function isUserActive(array $user): bool
    {
        return ($user['status'] ?? 'active') === 'active' || ($user['status'] ?? 1) === 1;
    }
}
