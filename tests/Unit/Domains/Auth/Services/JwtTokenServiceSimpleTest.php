<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Services\JwtTokenService;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Shared\Config\JwtConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * JwtTokenService 單元測試.
 *
 * 測試 JWT Token 服務的核心功能。
 */
final class JwtTokenServiceSimpleTest extends TestCase
{
    private JwtTokenService $service;

    private FirebaseJwtProvider $jwtProvider;

    /** @var MockObject&RefreshTokenRepositoryInterface */
    private MockObject $mockRefreshTokenRepository;

    /** @var MockObject&TokenBlacklistRepositoryInterface */
    private MockObject $mockBlacklistRepository;

    private JwtConfig $config;

    private DeviceInfo $deviceInfo;

    protected function setUp(): void
    {
        parent::setUp();

        // 設定測試用的環境變數
        $_ENV['JWT_PRIVATE_KEY'] = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCEd1LvGZVBEKkp\npJV2aGLBoTGvxSHhCQ3ZRGDwVUPv8w7Y0l/xBLhSbh2/iQGfX/bu7kA3kBvY2uH6\nHF1LPTbmF4EtWITExDkM/A3r6nuizYdVBNYM72yriDQPUveg6PAjataamKliexDF\naBAW8d+es9fFDgRtWj4qbO+WUs2vuffjI6SPuHXt1pdggu/NGBBMSv96W5Y6lmA+\ng4Qif4GAQn8nKS+5nvp/e80Rq6YKIr5mFyjgpICDu3RrAATmPKhKej6FgDZp69j3\nZiQWcywCWt3rwMC2Tz9DfdhKdwDzwKL4gnt1k55HZt9xAegWUroEtXzNnHL7tJ9I\nI1CyUDaTAgMBAAECggEAChYDzIzIHoIkPzV24+Mi0ddyLw31fGryEP7x2prDZ3u8\nP6oVAAb5+dzEixbldrsZ1Ctz3Ecut55C4oZSXC43BeH4RfmdclX2ehSfAr2B2G2J\nxmFt4uJABfeC7z/D9w6FakzyNic1jngMWNuJjhWwjybmYOymTaU3YoeU3n9DhgOo\n3zjNj573K5dLyFkAP+9YWQ8HaT/PHgJxDCpTVxEzQsyMxNQYVPZKrYFN/ZyY8oPL\nb9RfYDYeEel4/KOCgvOXPMJ32AcAdH2WwbyAzlt8Dhn6L+x0xIUTVdHlUng0DLp5\nsWqZmEzc81VAFijfo9aKFobsLoA5rJWSCcQ70ukuEQKBgQC4LLp993TOHi99BzJw\ncJNC2A5uq9kSwrXv/emOY2HXmJ6+J8SNksfr3BG94ukgZhJvUNA/n5LyJ3cpgNUm\nEKqK+PMk7S0CT6fbbBRXnnJijsQxyOqFAg7jYmMiYlTWyemPNXKpWCclMUgmSbNG\nJH/trXuLraDs7nnsgWM7k2CUVQKBgQC4IDag+yYQiuIiEAOdlPjv6J9z9dj9ri1w\n1jtiIPk41Xz9xZUA55pvnFXfOBEWxSrIlkLzR48HIA+cLB38XGOFpmW46l2k/o9X\nW7+pOyStdibrp16ppZY/NN6gwFjTnpPzpu7VJZvP+6M3Y2JyGBYCJ1xHPYMOX1oq\njPg6g1XHRwKBgEbQs/hhYKEsTBgn30YKkyTdjFcTbojfIzOfDuG35tQOE+OLyPCi\noopW+N9pUzgo5ye0DA6andbMQ+5KYiqbt+dtp5foNikwVZtx6DR0cQjiWh/GYB46\nV10o5HNBGdvokQyGgYsJoSuU0mgeaHcs65+I1/syDLFtVKYSbgRnO3htAoGBAKNw\nhM104h8BCSXvTSZOHILo3NGUQ187gz6MC/5ZAqC+cMra3h8FdwLnpRoVrKWnswiG\nyTsmJAHRJcodJyjh4b27LMRt1V4mUJrc6E6SH0aSgI3h7ZdtUuccSRosYyzFsNMx\nNQOi9KIz3nfGEpbwZmjXA4SBR5o0bdcjdxyJhFT1AoGAQb3Kw59mlGkDRf6aAmUE\nZ9JBfelirgrQ69ZKCKCVvZG/4mEDmU9E+6kHrf9Hbk1xOuGhY0+tSokLZQVY0+YS\nTcRRp/F1/kf6XHPlpHsaRn0phSKHSXxxXZ23w4Jqc9cDhTpfYZMsAQGacxKg/nNy\nmo0TtZZCNgLlXOCjt0o4Fpc=\n-----END PRIVATE KEY-----";
        $_ENV['JWT_PUBLIC_KEY'] = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhHdS7xmVQRCpKaSVdmhi\nwaExr8Uh4QkN2URg8FVD7/MO2NJf8QS4Um4dv4kBn1/27u5AN5Ab2Nrh+hxdSz02\n5heBLViExMQ5DPwN6+p7os2HVQTWDO9sq4g0D1L3oOjwI2rWmpipYnsQxWgQFvHf\nnrPXxQ4EbVo+KmzvllLNr7n34yOkj7h17daXYILvzRgQTEr/eluWOpZgPoOEIn+B\ngEJ/JykvuZ76f3vNEaumCiK+Zhco4KSAg7t0awAE5jyoSno+hYA2aevY92YkFnMs\nAlrd68DAtk8/Q33YSncA88Ci+IJ7dZOeR2bfcQHoFlK6BLV8zZxy+7SfSCNQslA2\nkwIDAQAB\n-----END PUBLIC KEY-----";
        $_ENV['JWT_ISSUER'] = 'alleynote-api';
        $_ENV['JWT_AUDIENCE'] = 'alleynote-client';
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '3600';
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '7200';

        $this->mockRefreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->mockBlacklistRepository = $this->createMock(TokenBlacklistRepositoryInterface::class);
        $this->config = new JwtConfig();
        $this->jwtProvider = new FirebaseJwtProvider($this->config);

        $this->service = new JwtTokenService(
            $this->jwtProvider,
            $this->mockRefreshTokenRepository,
            $this->mockBlacklistRepository,
            $this->config,
        );

        // 設定 mock repository 的預期行為
        $this->mockRefreshTokenRepository
            ->method('create')
            ->willReturn(true);

        $this->mockRefreshTokenRepository
            ->method('delete')
            ->willReturn(true);

        $this->mockRefreshTokenRepository
            ->method('revokeAllByUserId')
            ->willReturn(5); // 假設撤銷了 5 個 token

        $this->mockBlacklistRepository
            ->method('isBlacklisted')
            ->willReturn(false);

        $this->mockBlacklistRepository
            ->method('addToBlacklist')
            ->willReturn(true);

        $this->deviceInfo = new DeviceInfo(
            deviceId: 'test-device-123',
            deviceName: 'Test Device',
            ipAddress: '192.168.1.100',
            userAgent: 'Test User Agent',
            platform: 'Linux',
            browser: 'Chrome',
        );
    }

    public function test_generateTokenPair_should_return_token_pair_when_valid_input(): void
    {
        // Arrange
        $userId = 123;
        $customClaims = ['role' => 'user'];

        $this->mockRefreshTokenRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(true);

        // Act
        $result = $this->service->generateTokenPair($userId, $this->deviceInfo, $customClaims);

        // Assert
        $this->assertInstanceOf(TokenPair::class, $result);
        $this->assertIsString($result->getAccessToken());
        $this->assertIsString($result->getRefreshToken());

        // 驗證 token 可以被解析
        $accessPayload = $this->service->extractPayload($result->getAccessToken());
        $this->assertSame((string) $userId, $accessPayload->getSubject());
        $this->assertSame('user', $accessPayload->getCustomClaim('role'));
        $this->assertSame('test-device-123', $accessPayload->getCustomClaim('device_id'));
    }

    public function test_revokeAllUserTokens_should_delete_all_user_refresh_tokens(): void
    {
        // Arrange
        $userId = 123;

        // Act - 因為我們在 setUp 中已經設定 mock，直接測試回傳值即可
        $result = $this->service->revokeAllUserTokens($userId);

        // Assert
        $this->assertSame(5, $result);
    }

    public function test_getAlgorithm_should_return_RS256(): void
    {
        // Act
        $result = $this->service->getAlgorithm();

        // Assert
        $this->assertSame('RS256', $result);
    }

    public function test_getAccessTokenTtl_should_return_config_value(): void
    {
        // Act
        $result = $this->service->getAccessTokenTtl();

        // Assert
        $this->assertSame($this->config->getAccessTokenTtl(), $result);
    }

    public function test_getRefreshTokenTtl_should_return_config_value(): void
    {
        // Act
        $result = $this->service->getRefreshTokenTtl();

        // Assert
        $this->assertSame($this->config->getRefreshTokenTtl(), $result);
    }

    public function test_extractPayload_should_return_payload_without_validation(): void
    {
        // 建立一個真實的 token 來測試
        $userId = 456;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act
        $result = $this->service->extractPayload($accessToken);

        // Assert
        $this->assertSame((string) $userId, $result->getSubject());
        $this->assertSame('test-device-123', $result->getCustomClaim('device_id'));
        $this->assertSame('access', $result->getCustomClaim('type'));
    }

    public function test_isTokenOwnedBy_should_return_true_when_owned_by_user(): void
    {
        // Arrange
        $userId = 789;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act
        $result = $this->service->isTokenOwnedBy($accessToken, $userId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_isTokenOwnedBy_should_return_false_when_not_owned_by_user(): void
    {
        // Arrange
        $userId = 789;
        $otherUserId = 999;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act
        $result = $this->service->isTokenOwnedBy($accessToken, $otherUserId);

        // Assert
        $this->assertFalse($result);
    }

    public function test_isTokenFromDevice_should_return_true_when_from_device(): void
    {
        // Arrange
        $userId = 111;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act
        $result = $this->service->isTokenFromDevice($accessToken, $this->deviceInfo);

        // Assert
        $this->assertTrue($result);
    }

    public function test_isTokenFromDevice_should_return_false_when_from_different_device(): void
    {
        // Arrange
        $userId = 111;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        $differentDevice = new DeviceInfo(
            deviceId: 'different-device-456',
            deviceName: 'Different Device',
            ipAddress: '192.168.1.200',
            userAgent: 'Different User Agent',
            platform: 'Linux',
            browser: 'Firefox',
        );

        // Act
        $result = $this->service->isTokenFromDevice($accessToken, $differentDevice);

        // Assert
        $this->assertFalse($result);
    }

    public function test_getTokenRemainingTime_should_return_remaining_seconds(): void
    {
        // Arrange
        $userId = 222;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act
        $result = $this->service->getTokenRemainingTime($accessToken);

        // Assert
        $this->assertGreaterThan(3500, $result); // Should be close to 3600 seconds
        $this->assertLessThan(3601, $result);
    }

    public function test_getTokenRemainingTime_should_return_zero_when_token_invalid(): void
    {
        // Arrange
        $invalidToken = 'invalid-token-format';

        // Act
        $result = $this->service->getTokenRemainingTime($invalidToken);

        // Assert
        $this->assertSame(0, $result);
    }

    public function test_isTokenNearExpiry_should_return_true_when_near_expiry(): void
    {
        // 這個測試比較難實現，因為我們無法輕易建立即將過期的 token
        // 我們測試預設情況
        $userId = 333;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act - 用很長的閾值來測試
        $result = $this->service->isTokenNearExpiry($accessToken, 7200); // 2 hours

        // Assert
        $this->assertTrue($result); // 新產生的 token 剩餘時間應該小於 2 小時
    }

    public function test_isTokenNearExpiry_should_return_false_when_not_near_expiry(): void
    {
        $userId = 444;
        $this->mockRefreshTokenRepository->method('create')->willReturn(true);

        $tokenPair = $this->service->generateTokenPair($userId, $this->deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        // Act - 用很短的閾值來測試
        $result = $this->service->isTokenNearExpiry($accessToken, 60); // 1 minute

        // Assert
        $this->assertFalse($result); // 新產生的 token 應該還有超過 1 分鐘
    }

    public function test_isTokenRevoked_should_return_true_when_token_invalid(): void
    {
        // Arrange
        $invalidToken = 'invalid-token';

        // Act
        $result = $this->service->isTokenRevoked($invalidToken);

        // Assert
        $this->assertTrue($result);
    }
}
