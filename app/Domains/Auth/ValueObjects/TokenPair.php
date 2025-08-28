<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Token Pair Value Object.
 *
 * 表示 Access Token 和 Refresh Token 的配對，用於 JWT 認證流程。
 * 此類別是不可變的，確保 token 配對的完整性和安全性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final readonly class TokenPair implements JsonSerializable
{
    /**
     * 建構 Token Pair.
     *
     * @param string $accessToken JWT Access Token
     * @param string $refreshToken Refresh Token
     * @param DateTimeImmutable $accessTokenExpiresAt Access Token 過期時間
     * @param DateTimeImmutable $refreshTokenExpiresAt Refresh Token 過期時間
     * @param string $tokenType Token 類型，預設為 "Bearer"
     *
     * @throws InvalidArgumentException 當參數無效時
     */
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private DateTimeImmutable $accessTokenExpiresAt,
        private DateTimeImmutable $refreshTokenExpiresAt,
        private string $tokenType = 'Bearer',
    ) {
        $this->validateAccessToken($accessToken);
        $this->validateRefreshToken($refreshToken);
        $this->validateTokenType($tokenType);
        $this->validateExpirationTimes($accessTokenExpiresAt, $refreshTokenExpiresAt);
    }

    /**
     * 取得 Access Token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * 取得 Refresh Token.
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * 取得 Access Token 過期時間.
     */
    public function getAccessTokenExpiresAt(): DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    /**
     * 取得 Refresh Token 過期時間.
     */
    public function getRefreshTokenExpiresAt(): DateTimeImmutable
    {
        return $this->refreshTokenExpiresAt;
    }

    /**
     * 取得 Token 類型.
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * 取得 Access Token 剩餘有效秒數.
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     * @return int 剩餘秒數，如已過期則返回 0
     */
    public function getAccessTokenExpiresIn(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $diff = $this->accessTokenExpiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    /**
     * 取得 Refresh Token 剩餘有效秒數.
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     * @return int 剩餘秒數，如已過期則返回 0
     */
    public function getRefreshTokenExpiresIn(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $diff = $this->refreshTokenExpiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    /**
     * 檢查 Access Token 是否已過期
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function isAccessTokenExpired(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return $this->accessTokenExpiresAt <= $now;
    }

    /**
     * 檢查 Refresh Token 是否已過期
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function isRefreshTokenExpired(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return $this->refreshTokenExpiresAt <= $now;
    }

    /**
     * 檢查是否兩個 token 都已過期
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function isFullyExpired(?DateTimeImmutable $now = null): bool
    {
        return $this->isAccessTokenExpired($now) && $this->isRefreshTokenExpired($now);
    }

    /**
     * 檢查是否可以進行 token 刷新（Access Token 過期但 Refresh Token 有效）.
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function canRefresh(?DateTimeImmutable $now = null): bool
    {
        return $this->isAccessTokenExpired($now) && !$this->isRefreshTokenExpired($now);
    }

    /**
     * 檢查 Access Token 是否即將過期
     *
     * @param int $thresholdSeconds 閾值秒數，預設為 300 秒 (5分鐘)
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function isAccessTokenNearExpiry(int $thresholdSeconds = 300, ?DateTimeImmutable $now = null): bool
    {
        if ($thresholdSeconds < 0) {
            throw new InvalidArgumentException('Threshold seconds must be non-negative');
        }

        $now ??= new DateTimeImmutable();
        $expiresIn = $this->getAccessTokenExpiresIn($now);

        return $expiresIn > 0 && $expiresIn <= $thresholdSeconds;
    }

    /**
     * 建立帶有完整認證標頭的 Access Token.
     */
    public function getAuthorizationHeader(): string
    {
        return $this->tokenType . ' ' . $this->accessToken;
    }

    /**
     * 轉換為陣列格式.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->getAccessTokenExpiresIn(),
            'access_token_expires_at' => $this->accessTokenExpiresAt->format(DateTimeImmutable::ATOM),
            'refresh_token_expires_at' => $this->refreshTokenExpiresAt->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * 轉換為 API 回應格式（隱藏敏感資訊）.
     *
     * @param bool $includeRefreshToken 是否包含 Refresh Token
     * @return array<string, mixed>
     */
    public function toApiResponse(bool $includeRefreshToken = true): array
    {
        $response = [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->getAccessTokenExpiresIn(),
        ];

        if ($includeRefreshToken) {
            $response['refresh_token'] = $this->refreshToken;
        }

        return $response;
    }

    /**
     * JsonSerializable 實作.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查與另一個 TokenPair 是否相等.
     *
     * @param TokenPair $other 另一個 TokenPair
     */
    public function equals(TokenPair $other): bool
    {
        return $this->accessToken === $other->accessToken
            && $this->refreshToken === $other->refreshToken
            && $this->tokenType === $other->tokenType
            && $this->accessTokenExpiresAt->getTimestamp() === $other->accessTokenExpiresAt->getTimestamp()
            && $this->refreshTokenExpiresAt->getTimestamp() === $other->refreshTokenExpiresAt->getTimestamp();
    }

    /**
     * 轉換為字串表示.
     */
    public function toString(): string
    {
        $accessTokenPreview = substr($this->accessToken, 0, 20) . '...';
        $refreshTokenPreview = substr($this->refreshToken, 0, 20) . '...';

        return sprintf(
            'TokenPair(accessToken=%s, refreshToken=%s, tokenType=%s, accessExpiresAt=%s, refreshExpiresAt=%s)',
            $accessTokenPreview,
            $refreshTokenPreview,
            $this->tokenType,
            $this->accessTokenExpiresAt->format('Y-m-d H:i:s'),
            $this->refreshTokenExpiresAt->format('Y-m-d H:i:s'),
        );
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * 驗證 Access Token.
     *
     * @param string $accessToken Access Token
     * @throws InvalidArgumentException 當 Access Token 無效時
     */
    private function validateAccessToken(string $accessToken): void
    {
        if (empty($accessToken)) {
            throw new InvalidArgumentException('Access token cannot be empty');
        }

        // 基本的 JWT 格式檢查（三個部分用點分隔）
        $parts = explode('.', $accessToken);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Access token must be a valid JWT format (header.payload.signature)');
        }

        // 檢查每個部分是否為有效的 Base64URL 編碼
        foreach ($parts as $part) {
            if (empty($part) || !preg_match('/^[A-Za-z0-9_-]+$/', $part)) {
                throw new InvalidArgumentException('Access token contains invalid Base64URL encoded parts');
            }
        }
    }

    /**
     * 驗證 Refresh Token.
     *
     * @param string $refreshToken Refresh Token
     * @throws InvalidArgumentException 當 Refresh Token 無效時
     */
    private function validateRefreshToken(string $refreshToken): void
    {
        if (empty($refreshToken)) {
            throw new InvalidArgumentException('Refresh token cannot be empty');
        }

        // 檢查 Refresh Token 長度（JWT refresh token 通常會比較長）
        if (mb_strlen($refreshToken) < 16) {
            throw new InvalidArgumentException('Refresh token must be at least 16 characters long');
        }

        if (mb_strlen($refreshToken) > 2000) {
            throw new InvalidArgumentException('Refresh token cannot exceed 2000 characters');
        }
    }

    /**
     * 驗證 Token 類型.
     *
     * @param string $tokenType Token 類型
     * @throws InvalidArgumentException 當 Token 類型無效時
     */
    private function validateTokenType(string $tokenType): void
    {
        if (empty($tokenType)) {
            throw new InvalidArgumentException('Token type cannot be empty');
        }

        $validTypes = ['Bearer', 'Basic', 'Digest'];
        if (!in_array($tokenType, $validTypes, true)) {
            throw new InvalidArgumentException(
                'Token type must be one of: ' . implode(', ', $validTypes),
            );
        }
    }

    /**
     * 驗證過期時間.
     *
     * @param DateTimeImmutable $accessTokenExpiresAt Access Token 過期時間
     * @param DateTimeImmutable $refreshTokenExpiresAt Refresh Token 過期時間
     * @throws InvalidArgumentException 當過期時間設定無效時
     */
    private function validateExpirationTimes(
        DateTimeImmutable $accessTokenExpiresAt,
        DateTimeImmutable $refreshTokenExpiresAt,
    ): void {
        $now = new DateTimeImmutable();

        // Access Token 過期時間不能是過去的時間
        if ($accessTokenExpiresAt <= $now) {
            throw new InvalidArgumentException('Access token expiration time must be in the future');
        }

        // Refresh Token 過期時間不能是過去的時間
        if ($refreshTokenExpiresAt <= $now) {
            throw new InvalidArgumentException('Refresh token expiration time must be in the future');
        }

        // Refresh Token 的過期時間應該比 Access Token 晚
        if ($refreshTokenExpiresAt <= $accessTokenExpiresAt) {
            throw new InvalidArgumentException('Refresh token expiration time must be after access token expiration time');
        }

        // 檢查過期時間間隔是否合理（Access Token 不應該比 Refresh Token 晚太多）
        $maxInterval = 365 * 24 * 60 * 60; // 1年的秒數
        if (($refreshTokenExpiresAt->getTimestamp() - $accessTokenExpiresAt->getTimestamp()) > $maxInterval) {
            throw new InvalidArgumentException('Time interval between tokens cannot exceed 1 year');
        }
    }
}
