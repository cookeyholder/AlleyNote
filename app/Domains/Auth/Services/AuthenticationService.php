<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface;
use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\DTOs\LoginRequestDTO;
use AlleyNote\Domains\Auth\DTOs\LoginResponseDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshResponseDTO;
use AlleyNote\Domains\Auth\Exceptions\AuthenticationException;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
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

            $userId = (int) $user['id'];
            $userEmail = $user['email'] ?? $request->email;

            // 3. 清理該使用者過期的 refresh token
            $this->refreshTokenRepository->cleanup();

            // 4. 檢查該使用者的活躍 token 數量限制
            $userTokens = $this->refreshTokenRepository->findByUserId($userId, false);
            if (count($userTokens) >= self::MAX_REFRESH_TOKENS_PER_USER) {
                // 撤銷最舊的活躍 token 來騰出空間
                $oldestToken = reset($userTokens);
                if ($oldestToken !== false) {
                    $this->refreshTokenRepository->revoke($oldestToken['jti'], 'max_tokens_exceeded');
                }
            }

            // 5. 產生 JWT token 對（包含儲存 refresh token）
            $tokenPair = $this->jwtTokenService->generateTokenPair($userId, $deviceInfo, [
                'email' => $userEmail,
                'scopes' => $request->scopes ?? [],
            ]);

            // 6. 更新使用者最後登入時間
            $this->userRepository->updateLastLogin($userId);

            // 7. 建立回應
            $payload = $this->jwtTokenService->extractPayload($tokenPair->getRefreshToken());

            return new LoginResponseDTO(
                tokens: $tokenPair,
                userId: $userId,
                userEmail: $userEmail,
                expiresAt: $payload->getExpiresAt()->getTimestamp(),
                sessionId: $payload->getJti(),
                permissions: $request->scopes,
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
            return $this->refreshTokenRepository->getUserTokenStats($userId);
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
}
