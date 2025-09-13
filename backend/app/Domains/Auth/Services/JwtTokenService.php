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
                accessTokenExpiresAt: $accessTokenExpiresAt,
                refreshTokenExpiresAt: $refreshTokenExpiresAtFinal,
                tokenType: 'Bearer',
            );
        } catch (Throwable $e) {
            throw new TokenGenerationException(
                TokenGenerationException::REASON_GENERATION_FAILED,
                TokenGenerationException::TOKEN_PAIR,
                'Token pair generation failed: ' . $e->getMessage(),
                $e,
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
                    'Token has been revoked',
                );
            }

            // 使用 JWT provider 驗證和解析 token
            $payload = $this->jwtProvider->parseTokenUnsafe($token);

            // 轉換為 JwtPayload 物件
            // @phpstan-ignore-next-line
            return $this->createJwtPayloadFromArray((array) $payload);
        } catch (Throwable $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_MALFORMED,
                InvalidTokenException::ACCESS_TOKEN,
                'Token validation failed: ' . $e->getMessage(),
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
            $payload = $this->jwtProvider->parseTokenUnsafe($refreshToken);

            if (($payload['type'] ?? '') !== 'refresh') {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_MALFORMED,
                    'Not a refresh token',
                );
            }

            $jti = $payload['jti'] ?? null;
            $userId = (int) ($payload['sub'] ?? 0);

            if (!$jti || !$userId) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_CLAIMS_INVALID,
                    'Invalid refresh token claims',
                );
            }

            // 檢查 refresh token 是否存在且有效
            $storedToken = $this->refreshTokenRepository->findByJti((string) $jti);
            if (!$storedToken) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_MALFORMED,
                    'Refresh token not found',
                );
            }

            // 驗證 token hash
            $tokenHash = hash('sha256', $refreshToken);
            if (!hash_equals((string) ($storedToken['token_hash'] ?? ''), $tokenHash)) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_SIGNATURE_INVALID,
                    InvalidTokenException::REFRESH_TOKEN,
                    'Refresh token hash mismatch',
                );
            }

            // 撤銷舊的 refresh token
            $this->refreshTokenRepository->revoke((string) $jti);

            // 生成新的 token pair
            return $this->generateTokenPair($userId, $deviceInfo);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        } catch (Throwable $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_DECODE_FAILED,
                InvalidTokenException::REFRESH_TOKEN,
                'Token refresh failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * 撤銷 Token.
     */
    public function revokeToken(string $token, string $reason = 'manual_revocation'): bool
    {
        try {
            $payload = $this->jwtProvider->parseTokenUnsafe($token);
            $jti = $payload['jti'] ?? null;
            $exp = $payload['exp'] ?? null;

            if (!$jti || !$exp) {
                return false;
            }

            $expiresAt = new DateTimeImmutable('@' . (string) $exp);

            // 將 token 添加到黑名單
            $blacklistEntry = new TokenBlacklistEntry(
                jti: (string) $jti,
                tokenType: (string) ($payload['typ'] ?? 'access'),
                expiresAt: $expiresAt,
                blacklistedAt: new DateTimeImmutable(),
                reason: $reason,
                userId: isset($payload['sub']) ? (int) $payload['sub'] : null,
                deviceId: isset($payload['device_id']) ? (string) $payload['device_id'] : null,
            );

            $this->blacklistRepository->addToBlacklist($blacklistEntry);

            // 如果是 refresh token，也從資料庫中撤銷
            if (($payload['typ'] ?? '') === 'refresh') {
                $this->refreshTokenRepository->revoke((string) $jti);
            }

            return true;
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 撤銷使用者的所有 Token.
     */
    public function revokeAllUserTokens(int $userId, string $reason = 'user_revocation'): int
    {
        try {
            // 撤銷所有 refresh tokens
            $revokedCount = $this->refreshTokenRepository->revokeAllByUserId($userId);

            // 注意：access tokens 無法直接從資料庫撤銷，
            // 因為它們是無狀態的。只能等待自然過期。
            // 在實際應用中，可能需要實現 token version 機制。

            return $revokedCount;
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 撤銷設備的所有 Token.
     */
    public function revokeDeviceTokens(int $userId, string $deviceId): void
    {
        try {
            $this->refreshTokenRepository->revokeAllByDevice($userId, $deviceId);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
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
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 從陣列創建 JwtPayload 物件.
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
                // @phpstan-ignore-next-line
                aud: (array) (is_array($payload['aud'] ?? []) ? $payload['aud'] : [$payload['aud'] ?? '']),
                iat: $iat,
                exp: $exp,
                nbf: $nbf,
                customClaims: (array) array_filter($payload, fn($key): bool => !in_array($key, [
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
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 驗證 access token.
     */
    public function validateAccessToken(string $token, bool $checkBlacklist = true): JwtPayload
    {
        try {
            $payload = $this->jwtProvider->validateToken($token);

            // 檢查黑名單
            if ($checkBlacklist) {
                $jti = $payload['jti'] ?? null;
                if ($jti && $this->blacklistRepository->isBlacklisted((string) $jti)) {
                    throw new InvalidTokenException(
                        InvalidTokenException::REASON_BLACKLISTED,
                        'Token has been revoked',
                        'Access denied',
                    );
                }
            }

            // @phpstan-ignore-next-line
            return $this->createJwtPayloadFromArray((array) $payload);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 驗證 refresh token.
     */
    public function validateRefreshToken(string $token, bool $checkBlacklist = true): JwtPayload
    {
        try {
            $payload = $this->jwtProvider->validateToken($token);

            // 檢查 token 類型
            if (($payload['typ'] ?? '') !== 'refresh') {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_MALFORMED,
                    'Invalid token type for refresh token',
                    'Token validation failed',
                );
            }

            // 檢查黑名單
            if ($checkBlacklist) {
                $jti = $payload['jti'] ?? null;
                if ($jti && $this->blacklistRepository->isBlacklisted((string) $jti)) {
                    throw new InvalidTokenException(
                        InvalidTokenException::REASON_BLACKLISTED,
                        'Token has been revoked',
                        'Access denied',
                    );
                }
            }

            // @phpstan-ignore-next-line
            return $this->createJwtPayloadFromArray((array) $payload);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 提取 token payload.
     */
    public function extractPayload(string $token): JwtPayload
    {
        try {
            $payload = $this->jwtProvider->parseTokenUnsafe($token);

            // @phpstan-ignore-next-line
            return $this->createJwtPayloadFromArray((array) $payload);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 重新整理 tokens.
     */
    public function refreshTokens(string $refreshToken, DeviceInfo $deviceInfo): TokenPair
    {
        try {
            // 驗證 refresh token
            $payload = $this->validateRefreshToken($refreshToken);
            $userId = (int) $payload->getSubject();

            // 檢查資料庫中的 refresh token
            $jti = $payload->toArray()['jti'];
            $storedToken = $this->refreshTokenRepository->findByJti((string) $jti);

            if (!$storedToken) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_MALFORMED,
                    'Refresh token not found',
                    'Token not found',
                );
            }

            // 驗證 token hash
            if (!hash_equals((string) ($storedToken['token_hash'] ?? ''), hash('sha256', $refreshToken))) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_SIGNATURE_INVALID,
                    InvalidTokenException::REFRESH_TOKEN,
                    'Token hash mismatch',
                );
            }

            // 撤銷舊的 refresh token
            $this->refreshTokenRepository->revoke((string) $jti);

            // 產生新的 token pair
            return $this->generateTokenPair($userId, $deviceInfo);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 檢查token是否已被撤銷（是否在黑名單中）.
     */
    public function isTokenRevoked(string $token): bool
    {
        try {
            $payload = $this->extractPayload($token);
            $jti = $payload->toArray()['jti'] ?? null;

            return $jti && $this->blacklistRepository->isBlacklisted((string) $jti);
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 取得token的剩餘有效時間（秒）.
     */
    public function getTokenRemainingTime(string $token): int
    {
        try {
            $payload = $this->extractPayload($token);
            $expiresAt = $payload->getExpiresAt();
            $now = new DateTimeImmutable();

            if ($expiresAt <= $now) {
                return 0;
            }

            return $expiresAt->getTimestamp() - $now->getTimestamp();
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 檢查token是否即將過期
     */
    public function isTokenNearExpiry(string $token, int $thresholdSeconds = 300): bool
    {
        $remainingTime = $this->getTokenRemainingTime($token);

        return $remainingTime > 0 && $remainingTime <= $thresholdSeconds;
    }

    /**
     * 驗證token是否屬於特定使用者.
     */
    public function isTokenOwnedBy(string $token, int $userId): bool
    {
        try {
            $payload = $this->extractPayload($token);

            return $payload->getSubject() === (string) $userId;
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 驗證token是否來自特定裝置.
     */
    public function isTokenFromDevice(string $token, DeviceInfo $deviceInfo): bool
    {
        try {
            $payload = $this->extractPayload($token);
            $claims = $payload->toArray();

            return ($claims['device_id'] ?? '') === $deviceInfo->getDeviceId();
        } catch (Throwable $e) {
            error_log('Error in JwtTokenService.php: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * 取得JWT演算法名稱.
     */
    public function getAlgorithm(): string
    {
        return 'RS256';
    }

    /**
     * 取得access token的TTL（存活時間）.
     */
    public function getAccessTokenTtl(): int
    {
        return $this->config->getAccessTokenTtl();
    }

    /**
     * 取得refresh token的TTL（存活時間）.
     */
    public function getRefreshTokenTtl(): int
    {
        return $this->config->getRefreshTokenTtl();
    }
}
