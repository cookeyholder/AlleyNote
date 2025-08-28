<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use PHPUnit\Framework\TestCase;

/**
 * Token 過期例外單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenExpiredExceptionTest extends TestCase
{
    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $exception = new TokenExpiredException();

        $this->assertSame(TokenExpiredException::ERROR_CODE, $exception->getCode());
        $this->assertSame('token_expired', $exception->getErrorType());
        $this->assertStringContainsString('Access token has expired', $exception->getMessage());
    }

    /**
     * 測試 Access Token 建構.
     */
    public function testAccessTokenConstruction(): void
    {
        $expiredAt = 1640995200; // 2022-01-01 00:00:00
        $currentTime = 1640995800; // 2022-01-01 00:10:00

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertSame('Access token expired 10 minutes ago', $exception->getMessage());
        $this->assertSame(TokenExpiredException::ACCESS_TOKEN, $exception->getTokenType());
        $this->assertSame($expiredAt, $exception->getExpiredAt());
        $this->assertSame(600, $exception->getExpiredDuration()); // 10 minutes in seconds
        $this->assertTrue($exception->isAccessTokenExpired());
        $this->assertFalse($exception->isRefreshTokenExpired());
    }

    /**
     * 測試 Refresh Token 建構.
     */
    public function testRefreshTokenConstruction(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995800;

        $exception = new TokenExpiredException(
            TokenExpiredException::REFRESH_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('Refresh token expired', $exception->getMessage());
        $this->assertSame(TokenExpiredException::REFRESH_TOKEN, $exception->getTokenType());
        $this->assertFalse($exception->isAccessTokenExpired());
        $this->assertTrue($exception->isRefreshTokenExpired());
    }

    /**
     * 測試沒有過期時間的情況
     */
    public function testWithoutExpiredTime(): void
    {
        $exception = new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN, null);

        $this->assertSame('Access token has expired', $exception->getMessage());
        $this->assertNull($exception->getExpiredAt());
        $this->assertNull($exception->getExpiredDuration());
    }

    /**
     * 測試自定義錯誤訊息.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom expiration message';
        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            null,
            null,
            $customMessage,
        );

        $this->assertSame($customMessage, $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 秒.
     */
    public function testDurationFormatSeconds(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995230; // 30 seconds later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('30 seconds ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 1秒（單數）.
     */
    public function testDurationFormatSingleSecond(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995201; // 1 second later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('1 second ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 分鐘.
     */
    public function testDurationFormatMinutes(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640996400; // 20 minutes later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('20 minutes ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 1分鐘（單數）.
     */
    public function testDurationFormatSingleMinute(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995260; // 1 minute later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('1 minute ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 小時
     */
    public function testDurationFormatHours(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1641002400; // 2 hours later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('2 hours ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 1小時（單數）.
     */
    public function testDurationFormatSingleHour(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640998800; // 1 hour later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('1 hour ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 天數.
     */
    public function testDurationFormatDays(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1641254400; // 3 days later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('3 days ago', $exception->getMessage());
    }

    /**
     * 測試持續時間格式化 - 1天（單數）.
     */
    public function testDurationFormatSingleDay(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1641081600; // 1 day later

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertStringContainsString('1 day ago', $exception->getMessage());
    }

    /**
     * 測試用戶友好訊息 - Access Token.
     */
    public function testUserFriendlyMessageAccessToken(): void
    {
        $exception = new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN);

        $message = $exception->getUserFriendlyMessage();
        $this->assertStringContainsString('登入已過期', $message);
        $this->assertStringContainsString('重新登入', $message);
        $this->assertStringContainsString('Refresh Token', $message);
    }

    /**
     * 測試用戶友好訊息 - Refresh Token.
     */
    public function testUserFriendlyMessageRefreshToken(): void
    {
        $exception = new TokenExpiredException(TokenExpiredException::REFRESH_TOKEN);

        $message = $exception->getUserFriendlyMessage();
        $this->assertStringContainsString('Refresh Token 已過期', $message);
        $this->assertStringContainsString('重新登入', $message);
    }

    /**
     * 測試上下文資訊.
     */
    public function testContextInformation(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995800;

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $context = $exception->getContext();

        $this->assertSame(TokenExpiredException::ACCESS_TOKEN, $context['token_type']);
        $this->assertSame($expiredAt, $context['expired_at']);
        $this->assertSame($currentTime, $context['current_time']);
        $this->assertSame(600, $context['expired_duration']);
        $this->assertSame(date('Y-m-d H:i:s', $expiredAt), $context['expired_at_human']);
        $this->assertSame(date('Y-m-d H:i:s', $currentTime), $context['current_time_human']);
    }

    /**
     * 測試使用目前時間.
     */
    public function testUsingCurrentTime(): void
    {
        $expiredAt = time() - 300; // 5 minutes ago

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
        );

        $context = $exception->getContext();
        $this->assertEqualsWithDelta(time(), $context['current_time'], 2);
        $this->assertEqualsWithDelta(300, $context['expired_duration'], 5);
    }

    /**
     * 測試靜態工廠方法 - accessToken.
     */
    public function testAccessTokenFactoryMethod(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995800;

        $exception = TokenExpiredException::accessToken($expiredAt, $currentTime);

        $this->assertInstanceOf(TokenExpiredException::class, $exception);
        $this->assertTrue($exception->isAccessTokenExpired());
        $this->assertSame($expiredAt, $exception->getExpiredAt());
        $this->assertSame(600, $exception->getExpiredDuration());
    }

    /**
     * 測試靜態工廠方法 - refreshToken.
     */
    public function testRefreshTokenFactoryMethod(): void
    {
        $expiredAt = 1640995200;
        $currentTime = 1640995800;

        $exception = TokenExpiredException::refreshToken($expiredAt, $currentTime);

        $this->assertInstanceOf(TokenExpiredException::class, $exception);
        $this->assertTrue($exception->isRefreshTokenExpired());
        $this->assertSame($expiredAt, $exception->getExpiredAt());
    }

    /**
     * 測試靜態工廠方法使用預設參數.
     */
    public function testFactoryMethodsWithDefaults(): void
    {
        $accessException = TokenExpiredException::accessToken();
        $refreshException = TokenExpiredException::refreshToken();

        $this->assertTrue($accessException->isAccessTokenExpired());
        $this->assertTrue($refreshException->isRefreshTokenExpired());

        // 檢查使用目前時間
        $accessContext = $accessException->getContext();
        $refreshContext = $refreshException->getContext();

        $this->assertEqualsWithDelta(time(), $accessContext['current_time'], 2);
        $this->assertEqualsWithDelta(time(), $refreshContext['current_time'], 2);
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testErrorDetails(): void
    {
        $exception = new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN);

        $details = $exception->getErrorDetails();

        $this->assertSame('token_expired', $details['error_type']);
        $this->assertSame(TokenExpiredException::ERROR_CODE, $details['code']);
        $this->assertArrayHasKey('context', $details);
        $this->assertSame(TokenExpiredException::ACCESS_TOKEN, $details['context']['token_type']);
    }

    /**
     * 測試邊界情況 - 零秒持續時間.
     */
    public function testZeroDurationEdgeCase(): void
    {
        $timestamp = 1640995200;

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $timestamp,
            $timestamp,
        );

        $this->assertSame(0, $exception->getExpiredDuration());
        $this->assertStringContainsString('0 seconds ago', $exception->getMessage());
    }

    /**
     * 測試負數持續時間（未來時間）.
     */
    public function testNegativeDuration(): void
    {
        $expiredAt = time() + 300; // 5 minutes in future
        $currentTime = time();

        $exception = new TokenExpiredException(
            TokenExpiredException::ACCESS_TOKEN,
            $expiredAt,
            $currentTime,
        );

        $this->assertLessThan(0, $exception->getExpiredDuration());
    }
}
