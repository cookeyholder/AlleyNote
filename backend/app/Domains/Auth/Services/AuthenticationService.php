<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\LoginResponseDTO;
use App\Domains\Auth\DTOs\LogoutRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\DTOs\RefreshResponseDTO;
use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use Throwable;

/**
 * 認證服務實作.
 *
 * 實作完整的 JWT 認證服務功能，整合各個認證元件。
 * 提供使用者登入、登出、權杖管理等核心認證功能。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final class AuthenticationService implements AuthenticationServiceInterface
{
    private const MAX_REFRESH_TOKENS_PER_USER = 50;

    public function __construct(
        private readonly JwtTokenServiceInterface $jwtTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function login(LoginRequestDTO $request, DeviceInfo $deviceInfo): LoginResponseDTO
    {
        try {
            // 1. 驗證使用者憑證
            $user = $this->userRepository->validateCredentials($request->email, $request->password);
            if ($user === null) {
                throw new AuthenticationException(
                    AuthenticationException::REASON_INVALID_CREDENTIALS,
                    'Invalid credentials provided',
                );
            }

            // 2. 檢查使用者狀態（如果有軟刪除或停用欄位）
            if (isset($user['deleted_at']) && !empty($user['deleted_at'])) {
                throw new AuthenticationException(
                    AuthenticationException::REASON_ACCOUNT_DISABLED,
                    'User account has been deactivated',
                );
            }

            $userIdRaw = $user['id'] ?? null;
            if (!is_int($userIdRaw) && !is_numeric($userIdRaw)) {
                throw new AuthenticationException(
                    AuthenticationException::REASON_INVALID_CREDENTIALS,
                    'Invalid user ID',
                );
            }
            $userId = is_int($userIdRaw) ? $userIdRaw : (int) $userIdRaw;
            $userEmail = $user['email'] ?? $request->email;
            $userName = $user['username'] ?? null;

            // 3. 清理該使用者過期的 refresh token
            $this->refreshTokenRepository->cleanup();

            // 4. 檢查該使用者的活躍 token 數量限制
            $userTokens = $this->refreshTokenRepository->findByUserId($userId, false);
            if (count($userTokens) >= self::MAX_REFRESH_TOKENS_PER_USER) {
                // 撤銷最舊的活躍 token 來騰出空間
                $oldestToken = reset($userTokens);
                if ($oldestToken !== false && is_array($oldestToken)) {
                    $jti = $oldestToken['jti'] ?? null;
                    if (is_string($jti)) {
                        $this->refreshTokenRepository->revoke($jti, 'max_tokens_exceeded');
                    }
                }
            }

            // 5. 取得使用者角色資訊
            $userWithRoles = $this->userRepository->findByIdWithRoles($userId);
            $roles = $userWithRoles['roles'] ?? [];
            $userRole = null;
            if (is_array($roles) && !empty($roles) && isset($roles[0]) && is_array($roles[0])) {
                $userRoleRaw = $roles[0]['name'] ?? null;
                $userRole = is_string($userRoleRaw) ? $userRoleRaw : null;
            }

            // 根據角色自動賦予權限
            $permissions = $this->getPermissionsForRole($userRole);

            // 6. 產生 JWT token 對（包含儲存 refresh token 和角色資訊）
            $tokenPair = $this->jwtTokenService->generateTokenPair($userId, $deviceInfo, [
                'email' => $userEmail,
                'username' => $userName,
                'role' => $userRole,
                'permissions' => $permissions,  // 使用 permissions 而不是 scopes
                'scopes' => $request->scopes ?? [],
            ]);

            // 7. 更新使用者最後登入時間
            $this->userRepository->updateLastLogin($userId);

            // 8. 建立回應
            $payload = $this->jwtTokenService->extractPayload($tokenPair->getRefreshToken());

            $emailForResponse = is_string($userEmail) ? $userEmail : '';

            return new LoginResponseDTO(
                tokens: $tokenPair,
                userId: $userId,
                userEmail: $emailForResponse,
                expiresAt: $payload->getExpiresAt()->getTimestamp(),
                userName: $userName,
                sessionId: $payload->getJti(),
                permissions: $request->scopes,
                roles: $roles,
            );
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new AuthenticationException(
                AuthenticationException::REASON_INVALID_CREDENTIALS,
                'Login failed: ' . $e->getMessage(),
            );
        }
    }

    public function refresh(RefreshRequestDTO $request, DeviceInfo $deviceInfo): RefreshResponseDTO
    {
        try {
            // 1. 驗證並取得新的 token pair（這個過程會自動撤銷舊 token 並創建新 token）
            $newTokenPair = $this->jwtTokenService->refreshTokens($request->refreshToken, $deviceInfo);

            // 2. 建立回應
            $newPayload = $this->jwtTokenService->extractPayload($newTokenPair->getRefreshToken());
            $oldPayload = $this->jwtTokenService->extractPayload($request->refreshToken);

            return new RefreshResponseDTO(
                tokens: $newTokenPair,
                userId: $oldPayload->getUserId(),
                expiresAt: $newPayload->getExpiresAt()->getTimestamp(),
                sessionId: $newPayload->getJti(),
                permissions: $request->scopes,
            );
        } catch (InvalidTokenException|TokenExpiredException $e) {
            throw new AuthenticationException(
                AuthenticationException::REASON_INVALID_REFRESH_TOKEN,
                'Invalid refresh token: ' . $e->getMessage(),
            );
        } catch (Throwable $e) {
            throw new AuthenticationException(
                AuthenticationException::REASON_TOKEN_REFRESH_FAILED,
                'Token refresh failed: ' . $e->getMessage(),
            );
        }
    }

    public function logout(LogoutRequestDTO $request): bool
    {
        try {
            if ($request->refreshToken !== null) {
                $payload = $this->jwtTokenService->extractPayload($request->refreshToken);

                if ($request->revokeAllTokens) {
                    // 撤銷該使用者的所有 token
                    $this->refreshTokenRepository->revokeAllByUserId($payload->getUserId(), 'logout_all');
                } else {
                    // 只撤銷當前 refresh token
                    $this->refreshTokenRepository->revoke($payload->getJti(), 'user_logout');
                }
            }

            // 撤銷 access token（加入黑名單）
            if ($request->accessToken !== '') {
                $this->jwtTokenService->revokeToken($request->accessToken, 'user_logout');
            }

            return true;
        } catch (Throwable $e) {
            throw new AuthenticationException('Invalid credentials provided', 'Logout failed: ' . $e->getMessage());
        }
    }

    public function validateAccessToken(string $accessToken): bool
    {
        try {
            $this->jwtTokenService->validateAccessToken($accessToken);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function validateRefreshToken(string $refreshToken): bool
    {
        try {
            $payload = $this->jwtTokenService->validateRefreshToken($refreshToken);

            return $this->refreshTokenRepository->isValid($payload->getJti());
        } catch (Throwable) {
            return false;
        }
    }

    public function revokeRefreshToken(string $refreshToken, string $reason = 'manual_revocation'): bool
    {
        try {
            $payload = $this->jwtTokenService->extractPayload($refreshToken);

            return $this->refreshTokenRepository->revoke($payload->getJti(), $reason);
        } catch (Throwable) {
            return false;
        }
    }

    public function revokeAllUserTokens(int $userId, ?string $excludeJti = null, string $reason = 'logout_all'): int
    {
        try {
            return $this->refreshTokenRepository->revokeAllByUserId($userId, $reason, $excludeJti);
        } catch (Throwable) {
            return 0;
        }
    }

    public function revokeDeviceTokens(int $userId, string $deviceId, string $reason = 'device_logout'): int
    {
        try {
            return $this->refreshTokenRepository->revokeAllByDevice($userId, $deviceId, $reason);
        } catch (Throwable) {
            return 0;
        }
    }

    public function getUserTokenStats(int $userId): array
    {
        try {
            $stats = $this->refreshTokenRepository->getUserTokenStats($userId);

            // 確保所有值都是 int
            return [
                'total' => is_int($stats['total'] ?? null) ? $stats['total'] : 0,
                'active' => is_int($stats['active'] ?? null) ? $stats['active'] : 0,
                'expired' => is_int($stats['expired'] ?? null) ? $stats['expired'] : 0,
                'revoked' => is_int($stats['revoked'] ?? null) ? $stats['revoked'] : 0,
            ];
        } catch (Throwable) {
            return [
                'total' => 0,
                'active' => 0,
                'expired' => 0,
                'revoked' => 0,
            ];
        }
    }

    public function cleanupExpiredTokens(?DateTime $beforeDate = null): int
    {
        try {
            return $this->refreshTokenRepository->cleanup($beforeDate);
        } catch (Throwable) {
            return 0;
        }
    }

    public function cleanupRevokedTokens(int $days = 30): int
    {
        try {
            return $this->refreshTokenRepository->cleanupRevoked($days);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * 從 access token 取得使用者資訊.
     */
    public function getUserFromToken(string $accessToken): ?array
    {
        try {
            // 驗證 token
            if (!$this->validateAccessToken($accessToken)) {
                return null;
            }

            // 提取 payload
            $payload = $this->jwtTokenService->extractPayload($accessToken);

            // 從使用者 ID 查找使用者 (使用 UUID 查詢)
            $userId = $payload->getUserId();
            $user = $this->userRepository->findByUuid((string) $userId);

            if (!$user) {
                return null;
            }

            return [
                'user' => $user,
                'token_info' => [
                    'user_id' => $payload->getUserId(),
                    'subject' => $payload->getSubject(),
                    'issued_at' => $payload->getIssuedAt()->getTimestamp(),
                    'expires_at' => $payload->getExpiresAt()->getTimestamp(),
                    'token_id' => $payload->getJti(),
                    'custom_claims' => $payload->getCustomClaims(),
                ],
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * 根據角色取得預設權限.
     *
     * @return string[]
     */
    private function getPermissionsForRole(?string $role): array
    {
        // 如果是 super_admin 或 admin,賦予所有統計權限
        if ($role === 'super_admin' || $role === 'admin') {
            return ['*'];  // 全權限
        }

        // 一般角色沒有權限
        return [];
    }
}
