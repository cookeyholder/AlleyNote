<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Exceptions\TokenGenerationException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordSecurityServiceInterface $passwordService,
        private ?JwtTokenServiceInterface $jwtTokenService = null,
        private bool $jwtEnabled = false,
    ) {}

    public function register(RegisterUserDTO $dto, ?DeviceInfo $deviceInfo = null): array
    {
        // DTO 已經在建構時進行基本驗證，這裡進行密碼安全性檢查
        $this->passwordService->validatePassword($dto->password);

        // 轉換為陣列並雜湊密碼
        $data = $dto->toArray();
        $data['password'] = $this->passwordService->hashPassword($data['password']);

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
            } catch (TokenGenerationException $e) {
                // 如果 JWT 產生失敗，回傳傳統格式但記錄錯誤
                error_log('JWT token generation failed during registration: ' . $e->getMessage());
            }
        }

        // 傳統回傳格式（向後相容）
        return [
            'success' => true,
            'message' => '註冊成功',
            'user' => $user,
        ];
    }

    public function login(array $credentials, ?DeviceInfo $deviceInfo = null): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user) {
            return [
                'success' => false,
                'message' => '無效的認證資訊',
            ];
        }

        if ($user['status'] === 0) {
            return [
                'success' => false,
                'message' => '帳號已被停用',
            ];
        }

        if (!password_verify($credentials['password'], $user['password'])) {
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
            } catch (TokenGenerationException $e) {
                // 如果 JWT 產生失敗，回傳傳統格式但記錄錯誤
                error_log('JWT token generation failed during login: ' . $e->getMessage());
            }
        }

        // 傳統回傳格式（向後相容）
        return [
            'success' => true,
            'message' => '登入成功',
            'user' => $user,
        ];
    }

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
            } catch (\Exception $e) {
                // 如果撤銷失敗，記錄錯誤但仍然回傳成功（使用者體驗優先）
                error_log('JWT token revocation failed during logout: ' . $e->getMessage());
            }
        }

        // 傳統模式或 JWT 撤銷失敗時的回傳格式
        return [
            'success' => true,
            'message' => '登出成功',
        ];
    }
}
