<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Token Pair Value Object 單元測試.
 */
final class TokenPairTest extends TestCase
{
    private DateTimeImmutable $now;

    private DateTimeImmutable $accessExpiry;

    private DateTimeImmutable $refreshExpiry;

    private string $validAccessToken;

    private string $validRefreshToken;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable();
        $this->accessExpiry = $this->now->add(new DateInterval('PT1H')); // 1小時後
        $this->refreshExpiry = $this->now->add(new DateInterval('P7D')); // 7天後

        // 有效的 JWT 格式 token (header.payload.signature)
        $this->validAccessToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.signature-part';
        $this->validRefreshToken = 'refresh_token_1234567890abcdef';
    }

    public function testConstructorWithValidData(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertSame($this->validAccessToken, $tokenPair->getAccessToken());
        $this->assertSame($this->validRefreshToken, $tokenPair->getRefreshToken());
        $this->assertEquals($this->accessExpiry, $tokenPair->getAccessTokenExpiresAt());
        $this->assertEquals($this->refreshExpiry, $tokenPair->getRefreshTokenExpiresAt());
        $this->assertSame('Bearer', $tokenPair->getTokenType());
    }

    public function testConstructorWithCustomTokenType(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
            tokenType: 'Basic',
        );

        $this->assertSame('Basic', $tokenPair->getTokenType());
    }

    public function testGetAccessTokenExpiresIn(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        // 1小時 = 3600秒
        $this->assertSame(3600, $tokenPair->getAccessTokenExpiresIn($this->now));

        // 30分鐘後
        $laterTime = $this->now->modify('+30 minutes');
        $this->assertSame(1800, $tokenPair->getAccessTokenExpiresIn($laterTime));

        // 已過期
        $expiredTime = $this->accessExpiry->modify('+1 hour');
        $this->assertSame(0, $tokenPair->getAccessTokenExpiresIn($expiredTime));
    }

    public function testGetRefreshTokenExpiresIn(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        // 7天 = 604800秒
        $this->assertSame(604800, $tokenPair->getRefreshTokenExpiresIn($this->now));

        // 1天後
        $laterTime = $this->now->modify('+1 day');
        $this->assertSame(518400, $tokenPair->getRefreshTokenExpiresIn($laterTime));

        // 已過期
        $expiredTime = $this->refreshExpiry->modify('+1 day');
        $this->assertSame(0, $tokenPair->getRefreshTokenExpiresIn($expiredTime));
    }

    public function testIsAccessTokenExpired(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertFalse($tokenPair->isAccessTokenExpired($this->now));
        $this->assertFalse($tokenPair->isAccessTokenExpired($this->accessExpiry->modify('-1 second')));
        $this->assertTrue($tokenPair->isAccessTokenExpired($this->accessExpiry));
        $this->assertTrue($tokenPair->isAccessTokenExpired($this->accessExpiry->modify('+1 hour')));
    }

    public function testIsRefreshTokenExpired(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertFalse($tokenPair->isRefreshTokenExpired($this->now));
        $this->assertFalse($tokenPair->isRefreshTokenExpired($this->refreshExpiry->modify('-1 second')));
        $this->assertTrue($tokenPair->isRefreshTokenExpired($this->refreshExpiry));
        $this->assertTrue($tokenPair->isRefreshTokenExpired($this->refreshExpiry->modify('+1 day')));
    }

    public function testIsFullyExpired(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertFalse($tokenPair->isFullyExpired($this->now));
        $this->assertFalse($tokenPair->isFullyExpired($this->accessExpiry)); // access expired, refresh still valid
        $this->assertTrue($tokenPair->isFullyExpired($this->refreshExpiry)); // both expired
    }

    public function testCanRefresh(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertFalse($tokenPair->canRefresh($this->now)); // access token still valid
        $this->assertTrue($tokenPair->canRefresh($this->accessExpiry)); // access expired, refresh valid
        $this->assertFalse($tokenPair->canRefresh($this->refreshExpiry)); // both expired
    }

    public function testIsAccessTokenNearExpiry(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        // 10分鐘前 (50分鐘剩餘，大於預設300秒閾值)
        $earlyTime = $this->now->modify('+10 minutes');
        $this->assertFalse($tokenPair->isAccessTokenNearExpiry(300, $earlyTime));

        // 55分鐘後 (5分鐘剩餘，等於預設300秒閾值)
        $nearExpiryTime = $this->now->modify('+55 minutes');
        $this->assertTrue($tokenPair->isAccessTokenNearExpiry(300, $nearExpiryTime));

        // 57分鐘後 (3分鐘剩餘，小於預設300秒閾值)
        $veryNearExpiryTime = $this->now->modify('+57 minutes');
        $this->assertTrue($tokenPair->isAccessTokenNearExpiry(300, $veryNearExpiryTime));

        // 已過期
        $expiredTime = $this->accessExpiry->modify('+1 hour');
        $this->assertFalse($tokenPair->isAccessTokenNearExpiry(300, $expiredTime));
    }

    public function testIsAccessTokenNearExpiryWithCustomThreshold(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        // 50分鐘後 (10分鐘剩餘，小於15分鐘閾值)
        $nearExpiryTime = $this->now->modify('+50 minutes');
        $this->assertTrue($tokenPair->isAccessTokenNearExpiry(900, $nearExpiryTime)); // 15分鐘 = 900秒
        $this->assertFalse($tokenPair->isAccessTokenNearExpiry(300, $nearExpiryTime)); // 5分鐘 = 300秒
    }

    public function testGetAuthorizationHeader(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $expected = 'Bearer ' . $this->validAccessToken;
        $this->assertSame($expected, $tokenPair->getAuthorizationHeader());
    }

    public function testGetAuthorizationHeaderWithCustomTokenType(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
            tokenType: 'Basic',
        );

        $expected = 'Basic ' . $this->validAccessToken;
        $this->assertSame($expected, $tokenPair->getAuthorizationHeader());
    }

    public function testToArray(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $array = $tokenPair->toArray();

        $this->assertSame($this->validAccessToken, $array['access_token']);
        $this->assertSame($this->validRefreshToken, $array['refresh_token']);
        $this->assertSame('Bearer', $array['token_type']);
        $this->assertIsInt($array['expires_in']);
        $this->assertSame($this->accessExpiry->format(DateTimeImmutable::ATOM), $array['access_token_expires_at']);
        $this->assertSame($this->refreshExpiry->format(DateTimeImmutable::ATOM), $array['refresh_token_expires_at']);
    }

    public function testToApiResponse(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $response = $tokenPair->toApiResponse();

        $this->assertSame($this->validAccessToken, $response['access_token']);
        $this->assertSame($this->validRefreshToken, $response['refresh_token']);
        $this->assertSame('Bearer', $response['token_type']);
        $this->assertIsInt($response['expires_in']);
        $this->assertArrayNotHasKey('access_token_expires_at', $response);
        $this->assertArrayNotHasKey('refresh_token_expires_at', $response);
    }

    public function testToApiResponseWithoutRefreshToken(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $response = $tokenPair->toApiResponse(false);

        $this->assertSame($this->validAccessToken, $response['access_token']);
        $this->assertSame('Bearer', $response['token_type']);
        $this->assertIsInt($response['expires_in']);
        $this->assertArrayNotHasKey('refresh_token', $response);
    }

    public function testJsonSerialize(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertEquals($tokenPair->toArray(), $tokenPair->jsonSerialize());
    }

    public function testEquals(): void
    {
        $tokenPair1 = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $tokenPair2 = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $tokenPair3 = new TokenPair(
            accessToken: 'different.access.token',
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $this->assertTrue($tokenPair1->equals($tokenPair2));
        $this->assertFalse($tokenPair1->equals($tokenPair3));
    }

    public function testToString(): void
    {
        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $string = $tokenPair->toString();

        $this->assertStringContainsString('TokenPair(', $string);
        $this->assertStringContainsString('accessToken=eyJhbGciOiJSUzI1NiIs...', $string);
        $this->assertStringContainsString('refreshToken=refresh_token_123456...', $string);
        $this->assertStringContainsString('tokenType=Bearer', $string);

        // 檢查時間格式而不是具體時間
        $this->assertMatchesRegularExpression('/accessExpiresAt=\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $string);
        $this->assertMatchesRegularExpression('/refreshExpiresAt=\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $string);

        $this->assertSame($string, (string) $tokenPair);
    }

    public function testConstructorWithEmptyAccessToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token cannot be empty');

        new TokenPair(
            accessToken: '',
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithInvalidAccessTokenFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token must be a valid JWT format');

        new TokenPair(
            accessToken: 'invalid-jwt-format',
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithInvalidAccessTokenParts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token contains invalid Base64URL encoded parts');

        new TokenPair(
            accessToken: 'invalid..parts',
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithEmptyRefreshToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token cannot be empty');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: '',
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithTooShortRefreshToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token must be at least 16 characters long');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: 'short',
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithTooLongRefreshToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token cannot exceed 255 characters');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: str_repeat('a', 256),
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithEmptyTokenType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token type cannot be empty');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
            tokenType: '',
        );
    }

    public function testConstructorWithInvalidTokenType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token type must be one of: Bearer, Basic, Digest');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
            tokenType: 'InvalidType',
        );
    }

    public function testConstructorWithAccessTokenExpirationInPast(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token expiration time must be in the future');

        $pastTime = $this->now->modify('-1 hour');

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $pastTime,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );
    }

    public function testConstructorWithRefreshTokenExpirationInPast(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token expiration time must be in the future');

        $pastTime = new DateTimeImmutable('-1 hour'); // 1小時前
        $futureTime = new DateTimeImmutable('+1 hour'); // 1小時後

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $futureTime, // 確保 Access Token 時間有效
            refreshTokenExpiresAt: $pastTime,    // Refresh Token 時間無效
        );
    }

    public function testConstructorWithRefreshTokenExpirationBeforeAccessToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token expiration time must be after access token expiration time');

        $accessTime = new DateTimeImmutable('+2 hours'); // 2小時後
        $refreshTime = new DateTimeImmutable('+1 hour'); // 1小時後（比 Access Token 早）

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $accessTime,
            refreshTokenExpiresAt: $refreshTime,
        );
    }

    public function testConstructorWithExcessiveTimeInterval(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time interval between tokens cannot exceed 1 year');

        $accessTime = new DateTimeImmutable('+1 hour');
        $tooLateRefreshTime = new DateTimeImmutable('+2 years'); // 超過 1 年間隔

        new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $accessTime,
            refreshTokenExpiresAt: $tooLateRefreshTime,
        );
    }

    public function testIsAccessTokenNearExpiryWithNegativeThreshold(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Threshold seconds must be non-negative');

        $tokenPair = new TokenPair(
            accessToken: $this->validAccessToken,
            refreshToken: $this->validRefreshToken,
            accessTokenExpiresAt: $this->accessExpiry,
            refreshTokenExpiresAt: $this->refreshExpiry,
        );

        $tokenPair->isAccessTokenNearExpiry(-100);
    }
}
