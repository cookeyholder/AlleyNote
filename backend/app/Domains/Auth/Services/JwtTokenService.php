<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtProviderInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenGenerationException;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use App\Domains\Auth\ValueObjects\TokenPair;
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

    /**
     * 生成 Token 對.
     *
     * @param array<string, mixed> $customClaims
     */
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

            // 解析 refresh token 以獲取 JTI
            $refreshTokenData = $this->jwtProvider->parseTokenUnsafe($refreshToken);
            $jti = $refreshTokenData['jti'] ?? null;

            if (!$jti) {
                throw new TokenGenerationException(
                    TokenGenerationException::REASON_CLAIMS_INVALID,
                    TokenGenerationException::REFRESH_TOKEN,
                    'Refresh token missing JTI',
                );
            }

            // 將 refresh token 儲存到資料庫
            $refreshTokenExpiresAt = $now->modify('+' . $this->config->getRefreshTokenTtl() . ' seconds');
            $this->refreshTokenRepository->create(
                jti: (string) $jti,
                userId: $userId,
                tokenHash: hash('sha256', $refreshToken),
                deviceInfo: $deviceInfo,
                expiresAt: DateTime::createFromImmutable($refreshTokenExpiresAt),
            );

            // 計算過期時間
            $accessTokenExpiresAt = $now->modify('+' . $this->config->getAccessTokenTtl() . ' seconds');
            $refreshTokenExpiresAtFinal = $now->modify('+' . $this->config->getRefreshTokenTtl() . ' seconds');

            return new TokenPair(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                accessTokenExpiresAt: DateTime::createFromImmutable($accessTokenExpiresAt),
                refreshTokenExpiresAt: DateTime::createFromImmutable($refreshTokenExpiresAtFinal),
                tokenType: 'Bearer'
            );

        } catch (Throwable $e) {
            throw new TokenGenerationException(
                TokenGenerationException::REASON_GENERATION_FAILED,
                TokenGenerationException::ACCESS_TOKEN,
                'Failed to generate token pair: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * 驗證並解析 Token.
     */
    public function validateToken(string $token): JwtPayload
    {
        try {
            // 檢查 token 是否在黑名單中
            if ($this->isTokenBlacklisted($token)) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_BLACKLISTED,
                    'Token has been revoked'
                );
            }

            // 使用 JWT provider 驗證和解析 token
            $payload = $this->jwtProvider->parseToken($token);

            // 轉換為 JwtPayload 物件
            return $this->createJwtPayloadFromArray($payload);

        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_INVALID_FORMAT,
                'Token validation failed: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * 刷新 Token.
     */
    public function refreshToken(string $refreshToken, DeviceInfo $deviceInfo): TokenPair
    {
        try {
            // 驗證 refresh token
            $payload = $this->jwtProvider->parseToken($refreshToken);

            if (($payload['type'] ?? '') !== 'refresh') {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_INVALID_TYPE,
                    'Not a refresh token'
                );
            }

            $jti = $payload['jti'] ?? null;
            $userId = (int) ($payload['sub'] ?? 0);

            if (!$jti || !$userId) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_CLAIMS_INVALID,
                    'Invalid refresh token claims'
                );
            }

            // 檢查 refresh token 是否存在且有效
            $storedToken = $this->refreshTokenRepository->findByJti((string) $jti);
            if (!$storedToken) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_NOT_FOUND,
                    'Refresh token not found'
                );
            }

            // 驗證 token hash
            $tokenHash = hash('sha256', $refreshToken);
            if (!hash_equals($storedToken->getTokenHash(), $tokenHash)) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_HASH_MISMATCH,
                    'Refresh token hash mismatch'
                );
            }

            // 撤銷舊的 refresh token
            $this->refreshTokenRepository->revokeByJti((string) $jti);

            // 生成新的 token pair
            return $this->generateTokenPair($userId, $deviceInfo);

        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_REFRESH_FAILED,
                'Token refresh failed: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * 撤銷 Token.
     */
    public function revokeToken(string $token): void
    {
        try {
            $payload = $this->jwtProvider->parseTokenUnsafe($token);
            $jti = $payload['jti'] ?? null;
            $exp = $payload['exp'] ?? null;

            if (!$jti || !$exp) {
                throw new InvalidArgumentException('Token missing required claims');
            }

            $expiresAt = new DateTimeImmutable('@' . $exp);

            // 將 token 添加到黑名單
            $blacklistEntry = new TokenBlacklistEntry(
                jti: (string) $jti,
                expiresAt: DateTime::createFromImmutable($expiresAt)
            );

            $this->blacklistRepository->add($blacklistEntry);

            // 如果是 refresh token，也從資料庫中撤銷
            if (($payload['type'] ?? '') === 'refresh') {
                $this->refreshTokenRepository->revokeByJti((string) $jti);
            }

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to revoke token: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 撤銷使用者的所有 Token.
     */
    public function revokeAllUserTokens(int $userId): void
    {
        try {
            // 撤銷所有 refresh tokens
            $this->refreshTokenRepository->revokeAllByUserId($userId);

            // 注意：access tokens 無法直接從資料庫撤銷，
            // 因為它們是無狀態的。只能等待自然過期。
            // 在實際應用中，可能需要實現 token version 機制。

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to revoke user tokens: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 撤銷設備的所有 Token.
     */
    public function revokeDeviceTokens(int $userId, string $deviceId): void
    {
        try {
            $this->refreshTokenRepository->revokeByUserIdAndDeviceId($userId, $deviceId);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to revoke device tokens: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 檢查 Token 是否在黑名單中.
     */
    private function isTokenBlacklisted(string $token): bool
    {
        try {
            $payload = $this->jwtProvider->parseTokenUnsafe($token);
            $jti = $payload['jti'] ?? null;

            if (!$jti) {
                return false;
            }

            return $this->blacklistRepository->isBlacklisted((string) $jti);
        } catch (Throwable) {
            // 如果無法解析 token，認為它無效但不在黑名單中
            return false;
        }
    }

    /**
     * 從陣列創建 JwtPayload 物件.
     *
     * @param array<string, mixed> $payload
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

            // 安全地建立 DateTimeImmutable 物件
            $iat = DateTimeImmutable::createFromFormat('U', (string) $payload['iat']);
            if ($iat === false) {
                throw new InvalidArgumentException('Invalid iat timestamp: ' . (string) $payload['iat']);
            }

            $exp = DateTimeImmutable::createFromFormat('U', (string) $payload['exp']);
            if ($exp === false) {
                throw new InvalidArgumentException('Invalid exp timestamp: ' . (string) $payload['exp']);
            }

            $nbf = null;
            if (isset($payload['nbf'])) {
                $nbf = DateTimeImmutable::createFromFormat('U', (string) $payload['nbf']);
                if ($nbf === false) {
                    throw new InvalidArgumentException('Invalid nbf timestamp: ' . (string) $payload['nbf']);
                }
            }

            return new JwtPayload(
                jti: (string) $payload['jti'],
                sub: (string) $payload['sub'],
                iss: (string) $payload['iss'],
                aud: $this->normalizeAudience($payload['aud']),
                iat: $iat,
                exp: $exp,
                nbf: $nbf,
                customClaims: array_filter($payload, fn($key): bool => !in_array($key, [
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
            throw new InvalidArgumentException(
                'Failed to create JWT payload: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 正規化 audience 欄位.
     *
     * @param mixed $audience
     * @return array<string>
     */
    private function normalizeAudience($audience): array
    {
        if (is_string($audience)) {
            return [$audience];
        }

        if (is_array($audience)) {
            return array_map('strval', $audience);
        }

        return [];
    }
}
