<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

/**
 * Token 已過期例外.
 *
 * 當 JWT Token（Access Token 或 Refresh Token）已過期時拋出此例外。
 * 包含過期時間和剩餘時間等詳細資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenExpiredException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'token_expired';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4001;

    /**
     * Token 類型常數.
     */
    public const ACCESS_TOKEN = 'access_token';

    public const REFRESH_TOKEN = 'refresh_token';

    /**
     * 建立 Token 過期例外.
     *
     * @param string $tokenType Token 類型 (access_token 或 refresh_token)
     * @param int|null $expiredAt 過期時間戳
     * @param int|null $currentTime 目前時間戳
     * @param string $customMessage 自定義錯誤訊息
     */
    public function __construct(
        string $tokenType = self::ACCESS_TOKEN,
        ?int $expiredAt = null,
        ?int $currentTime = null,
        string $customMessage = '',
    ) {
        $currentTime ??= time();

        $message = $customMessage ?: $this->buildDefaultMessage($tokenType, $expiredAt, $currentTime);

        $context = [
            'token_type' => $tokenType,
            'expired_at' => $expiredAt,
            'current_time' => $currentTime,
        ];

        if ($expiredAt !== null) {
            $context['expired_duration'] = $currentTime - $expiredAt;
            $context['expired_at_human'] = date('Y-m-d H:i:s', $expiredAt);
            $context['current_time_human'] = date('Y-m-d H:i:s', $currentTime);
        }

        parent::__construct($message, self::ERROR_CODE, null, $context);
    }

    /**
     * 建構預設錯誤訊息.
     *
     * @param string $tokenType Token 類型
     * @param int|null $expiredAt 過期時間戳
     * @param int $currentTime 目前時間戳
     */
    private function buildDefaultMessage(string $tokenType, ?int $expiredAt, int $currentTime): string
    {
        $tokenName = $tokenType === self::ACCESS_TOKEN ? 'Access token' : 'Refresh token';

        if ($expiredAt === null) {
            return sprintf('%s has expired', $tokenName);
        }

        $expiredDuration = $currentTime - $expiredAt;
        $expiredAgo = $this->formatDuration($expiredDuration);

        return sprintf('%s expired %s ago', $tokenName, $expiredAgo);
    }

    /**
     * 格式化持續時間.
     *
     * @param int $seconds 秒數
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%d second%s', $seconds, $seconds === 1 ? '' : 's');
        }

        if ($seconds < 3600) {
            $minutes = intval(floor($seconds / 60));

            return sprintf('%d minute%s', $minutes, $minutes === 1 ? '' : 's');
        }

        if ($seconds < 86400) {
            $hours = intval(floor($seconds / 3600));

            return sprintf('%d hour%s', $hours, $hours === 1 ? '' : 's');
        }

        $days = intval(floor($seconds / 86400));

        return sprintf('%d day%s', $days, $days === 1 ? '' : 's');
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $tokenType = $this->context['token_type'] ?? self::ACCESS_TOKEN;

        if ($tokenType === self::ACCESS_TOKEN) {
            return '您的登入已過期，請重新登入或使用 Refresh Token 重新取得 Access Token。';
        }

        return '您的 Refresh Token 已過期，請重新登入。';
    }

    /**
     * 檢查是否為 Access Token 過期
     */
    public function isAccessTokenExpired(): bool
    {
        return ($this->context['token_type'] ?? '') === self::ACCESS_TOKEN;
    }

    /**
     * 檢查是否為 Refresh Token 過期
     */
    public function isRefreshTokenExpired(): bool
    {
        return ($this->context['token_type'] ?? '') === self::REFRESH_TOKEN;
    }

    /**
     * 取得 Token 類型.
     */
    public function getTokenType(): string
    {
        $tokenType = $this->context['token_type'] ?? self::ACCESS_TOKEN;
        return is_string($tokenType) ? $tokenType : self::ACCESS_TOKEN;
    }

    /**
     * 取得過期時間戳.
     */
    public function getExpiredAt(): ?int
    {
        $expiredAt = $this->context['expired_at'] ?? null;
        return is_int($expiredAt) ? $expiredAt : null;
    }

    /**
     * 取得已過期時間（秒）.
     */
    public function getExpiredDuration(): ?int
    {
        $expiredDuration = $this->context['expired_duration'] ?? null;
        return is_int($expiredDuration) ? $expiredDuration : null;
    }

    /**
     * 靜態工廠方法：建立 Access Token 過期例外.
     *
     * @param int|null $expiredAt 過期時間戳
     * @param int|null $currentTime 目前時間戳
     */
    public static function accessToken(?int $expiredAt = null, ?int $currentTime = null): self
    {
        return new self(self::ACCESS_TOKEN, $expiredAt, $currentTime);
    }

    /**
     * 靜態工廠方法：建立 Refresh Token 過期例外.
     *
     * @param int|null $expiredAt 過期時間戳
     * @param int|null $currentTime 目前時間戳
     */
    public static function refreshToken(?int $expiredAt = null, ?int $currentTime = null): self
    {
        return new self(self::REFRESH_TOKEN, $expiredAt, $currentTime);
    }
}
