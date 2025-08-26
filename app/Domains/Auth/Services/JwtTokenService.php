<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\JwtProviderInterface;
use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenGenerationException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use App\Shared\Config\JwtConfig;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

/**
 * JWT Token 服務實作類別.
 *
 * 負責JWT token的核心業務邏輯，包括token生成、驗證、撤銷等功能。
 * 整合 FirebaseJwtProvider 提供安全的 RS256 JWT token 服務。
 */
final class JwtTokenService implements JwtTokenServiceInterface
{
    public function __construct(
        private readonly JwtProviderInterface $jwtProvider,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly TokenBlacklistRepositoryInterface $blacklistRepository,
        private readonly JwtConfig $config,
    ) {}

    public function generateTokenPair(int $userId, DeviceInfo $deviceInfo, array $customClaims = []): TokenPair
    {
        try {
            $now = new DateTimeImmutable();

            // 準備 access token 的 payload
            $accessTokenPayload = array_merge($customClaims, [
                'sub' => (string) $userId,
                'device_id' => $deviceInfo->getDeviceId(),
                'device_name' => $deviceInfo->getDeviceName(),
                'ip_address' => $deviceInfo->getIpAddress(),
                'user_agent' => $deviceInfo->getUserAgent(),
                'platform' => $deviceInfo->getPlatform(),
                'browser' => $deviceInfo->getBrowser(),
                'type' => 'access',
            ]);

            // 準備 refresh token 的 payload（較少資訊）
            $refreshTokenPayload = [
                'sub' => (string) $userId,
                'device_id' => $deviceInfo->getDeviceId(),
                'type' => 'refresh',
            ];

            // 產生 tokens
            $accessToken = $this->jwtProvider->generateAccessToken($accessTokenPayload);
            $refreshToken = $this->jwtProvider->generateRefreshToken($refreshTokenPayload);

            // 計算過期時間
            $accessTokenExpiresAt = $now->modify('+' . $this->config->getAccessTokenTtl() . ' seconds');
            $refreshTokenExpiresAt = $now->modify('+' . $this->config->getRefreshTokenTtl() . ' seconds');

            return new TokenPair(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                accessTokenExpiresAt: $accessTokenExpiresAt,
                refreshTokenExpiresAt: $refreshTokenExpiresAt,
            );
        } catch (Throwable $e) {
            throw new TokenGenerationException(
                TokenGenerationException::REASON_ENCODING_FAILED,
                TokenGenerationException::ACCESS_TOKEN,
                'Failed to generate token pair: ' . $e->getMessage(),
            );
        }
    }

    public function validateAccessToken(string $token, bool $checkBlacklist = true): JwtPayload
    {
        // 檢查黑名單
        if ($checkBlacklist && $this->isTokenRevoked($token)) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_BLACKLISTED,
                InvalidTokenException::ACCESS_TOKEN,
                'Token has been revoked',
            );
        }

        // 驗證 token 並確認是 access token
        $payload = $this->jwtProvider->validateToken($token, 'access');

        return $this->createJwtPayloadFromArray($payload);
    }

    public function validateRefreshToken(string $token, bool $checkBlacklist = true): JwtPayload
    {
        // 檢查黑名單
        if ($checkBlacklist && $this->isTokenRevoked($token)) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_BLACKLISTED,
                InvalidTokenException::REFRESH_TOKEN,
                'Token has been revoked',
            );
        }

        // 驗證 token 並確認是 refresh token
        $payload = $this->jwtProvider->validateToken($token, 'refresh');

        // 檢查 refresh token 是否在資料庫中存在且未被撤銷
        $jwtPayload = $this->createJwtPayloadFromArray($payload);
        $refreshTokenRecord = $this->refreshTokenRepository->findByJti($jwtPayload->getJti());

        if ($refreshTokenRecord === null) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_CLAIMS_INVALID,
                InvalidTokenException::REFRESH_TOKEN,
                'Refresh token not found in database',
            );
        }

        if ($this->refreshTokenRepository->isRevoked($jwtPayload->getJti())) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_BLACKLISTED,
                InvalidTokenException::REFRESH_TOKEN,
                'Refresh token has been revoked',
            );
        }

        return $jwtPayload;
    }

    public function extractPayload(string $token): JwtPayload
    {
        $payload = $this->jwtProvider->parseTokenUnsafe($token);

        return $this->createJwtPayloadFromArray($payload);
    }

    public function refreshTokens(string $refreshToken, DeviceInfo $deviceInfo): TokenPair
    {
        // 驗證 refresh token
        $payload = $this->validateRefreshToken($refreshToken);

        $userId = (int) $payload->getSubject();

        // 撤銷舊的 refresh token
        $this->refreshTokenRepository->delete($payload->getJti());

        // 產生新的 token pair
        return $this->generateTokenPair($userId, $deviceInfo);
    }

    public function revokeToken(string $token, string $reason = 'manual_revocation'): bool
    {
        try {
            $payload = $this->extractPayload($token);

            // 將 token 加入黑名單
            $blacklistEntry = new TokenBlacklistEntry(
                jti: $payload->getJti(),
                tokenType: $payload->getCustomClaim('type') ?? 'unknown',
                expiresAt: $payload->getExpiresAt(),
                blacklistedAt: new DateTimeImmutable(),
                reason: $reason,
                userId: (int) $payload->getSubject(),
            );

            $this->blacklistRepository->addToBlacklist($blacklistEntry);

            // 如果是 refresh token，也從資料庫中刪除
            if ($payload->getCustomClaim('type') === 'refresh') {
                $this->refreshTokenRepository->delete($payload->getJti());
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function revokeAllUserTokens(int $userId, string $reason = 'revoke_all_sessions'): int
    {
        return $this->refreshTokenRepository->revokeAllByUserId($userId, $reason);
    }

    public function isTokenRevoked(string $token): bool
    {
        try {
            $payload = $this->extractPayload($token);

            return $this->blacklistRepository->isBlacklisted($payload->getJti());
        } catch (Throwable) {
            return true; // 無法解析的 token 視為已撤銷
        }
    }

    public function getTokenRemainingTime(string $token): int
    {
        try {
            $payload = $this->extractPayload($token);
            $now = new DateTimeImmutable();
            $remaining = $payload->getExpiresAt()->getTimestamp() - $now->getTimestamp();

            return max(0, $remaining);
        } catch (Throwable) {
            return 0;
        }
    }

    public function isTokenNearExpiry(string $token, int $thresholdSeconds = 300): bool
    {
        $remainingTime = $this->getTokenRemainingTime($token);

        return $remainingTime > 0 && $remainingTime <= $thresholdSeconds;
    }

    public function isTokenOwnedBy(string $token, int $userId): bool
    {
        try {
            $payload = $this->extractPayload($token);

            return (int) $payload->getSubject() === $userId;
        } catch (Throwable) {
            return false;
        }
    }

    public function isTokenFromDevice(string $token, DeviceInfo $deviceInfo): bool
    {
        try {
            $payload = $this->extractPayload($token);
            $tokenDeviceId = $payload->getCustomClaim('device_id');

            return $tokenDeviceId === $deviceInfo->getDeviceId();
        } catch (Throwable) {
            return false;
        }
    }

    public function getAlgorithm(): string
    {
        return 'RS256';
    }

    public function getAccessTokenTtl(): int
    {
        return $this->config->getAccessTokenTtl();
    }

    public function getRefreshTokenTtl(): int
    {
        return $this->config->getRefreshTokenTtl();
    }

    /**
     * 將 refresh token 儲存到資料庫.
     *
     * @param string $refreshToken JWT refresh token
     * @param int $userId 使用者 ID
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @param DateTimeImmutable $expiresAt 過期時間
     *
     * @throws TokenGenerationException 當儲存失敗時
     */
    private function storeRefreshToken(
        string $refreshToken,
        int $userId,
        DeviceInfo $deviceInfo,
        DateTimeImmutable $expiresAt,
    ): void {
        try {
            $payload = $this->jwtProvider->parseTokenUnsafe($refreshToken);

            $this->refreshTokenRepository->create(
                jti: $payload['jti'],
                userId: $userId,
                tokenHash: hash('sha256', $refreshToken),
                expiresAt: new DateTime($expiresAt->format('Y-m-d H:i:s')),
                deviceInfo: $deviceInfo,
            );
        } catch (Throwable $e) {
            throw new TokenGenerationException(
                TokenGenerationException::REASON_RESOURCE_EXHAUSTED,
                TokenGenerationException::REFRESH_TOKEN,
                'Failed to store refresh token: ' . $e->getMessage(),
            );
        }
    }

    /**
     * 從陣列建立 JwtPayload 物件.
     *
     * @param array<string, mixed> $payload 原始 payload 資料
     *
     * @return JwtPayload JwtPayload 物件
     *
     * @throws InvalidTokenException 當 payload 資料無效時
     */
    private function createJwtPayloadFromArray(array $payload): JwtPayload
    {
        try {
            // 確保必要的鍵存在
            $requiredKeys = ['jti', 'sub', 'iss', 'aud', 'iat', 'exp'];
            foreach ($requiredKeys as $key) {
                if (!isset($payload[$key])) {
                    throw new InvalidArgumentException("Missing required payload key: {$key}");
                }
            }

            return new JwtPayload(
                jti: $payload['jti'],
                sub: $payload['sub'],
                iss: $payload['iss'],
                aud: [$payload['aud']],
                iat: DateTimeImmutable::createFromFormat('U', (string) $payload['iat']),
                exp: DateTimeImmutable::createFromFormat('U', (string) $payload['exp']),
                nbf: isset($payload['nbf']) ? DateTimeImmutable::createFromFormat('U', (string) $payload['nbf']) : null,
                customClaims: array_filter($payload, fn($key) => !in_array($key, [
                    'jti',
                    'sub',
                    'iss',
                    'aud',
                    'iat',
                    'exp',
                    'nbf',
                ], true), ARRAY_FILTER_USE_KEY),
            );
        } catch (Throwable $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_CLAIMS_INVALID,
                InvalidTokenException::ACCESS_TOKEN,
                'Invalid token payload: ' . $e->getMessage(),
            );
        }
    }
}
