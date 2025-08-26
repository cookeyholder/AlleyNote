<?php

declare(strict_types=1);

namespace Tests\Integration;

use AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface;
use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\DTOs\LoginRequestDTO;
use AlleyNote\Domains\Auth\DTOs\LoginResponseDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshResponseDTO;
use AlleyNote\Domains\Auth\Exceptions\AuthenticationException;
use AlleyNote\Domains\Auth\Services\AuthenticationService;
use AlleyNote\Domains\Auth\Services\JwtTokenService;
use AlleyNote\Domains\Auth\Services\TokenBlacklistService;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use AlleyNote\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use AlleyNote\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Shared\Config\JwtConfig;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * JWT 認證系統整合測試
 * 驗證各元件間的協作與端到端流程.
 *
 * @group integration
 */
class JwtAuthenticationIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private JwtTokenServiceInterface $jwtTokenService;

    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    private TokenBlacklistRepositoryInterface $tokenBlacklistRepository;

    private UserRepositoryInterface|MockInterface $userRepository;

    private AuthenticationServiceInterface $authenticationService;

    private TokenBlacklistService $tokenBlacklistService;

    protected function setUp(): void
    {
        parent::setUp();

        // 先建立 Repository 實例
        $this->refreshTokenRepository = new RefreshTokenRepository($this->db);
        $this->tokenBlacklistRepository = new TokenBlacklistRepository($this->db);
        $this->tokenBlacklistService = new TokenBlacklistService($this->tokenBlacklistRepository);

        // 然後建立真實的服務實例，模擬完整的系統行為
        $this->jwtTokenService = $this->createJwtTokenService();

        // Mock UserRepository for testing
        /** @var UserRepositoryInterface|MockInterface $userRepository */
        $userRepository = Mockery::mock(UserRepositoryInterface::class)->shouldIgnoreMissing();
        $this->userRepository = $userRepository;
        $this->setupUserRepositoryMock();

        $this->authenticationService = new AuthenticationService(
            $this->jwtTokenService,
            $this->refreshTokenRepository,
            $this->userRepository,
        );

        // 建立測試使用者
        $this->createTestUser();
    }

    /**
     * 清理測試資料.
     */
    protected function tearDown(): void
    {
        // 清理 refresh_tokens 表
        $this->db->exec('DELETE FROM refresh_tokens');
        // 清理 token_blacklist 表
        $this->db->exec('DELETE FROM token_blacklist');

        parent::tearDown();
    }

    /**
     * 測試完整的登入流程.
     *
     * @test
     */
    public function canPerformCompleteLoginFlow(): void
    {
        // 準備登入請求
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
            rememberMe: true,
        );

        $deviceInfo = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Test Device',
        );

        // 執行登入
        $loginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

        // 驗證回應結構
        $this->assertInstanceOf(LoginResponseDTO::class, $loginResponse);
        $this->assertInstanceOf(TokenPair::class, $loginResponse->tokens);
        $this->assertEquals(1, $loginResponse->userId);
        $this->assertEquals('test@example.com', $loginResponse->userEmail);

        // 驗證 Access Token 可以被正確解析
        $accessToken = $loginResponse->tokens->getAccessToken();
        $this->assertNotEmpty($accessToken);
        $this->assertIsString($accessToken);

        // 驗證 Refresh Token 已存入資料庫
        $refreshTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertNotEmpty($refreshTokens);
    }

    /**
     * 測試 Token 刷新流程.
     *
     * @test
     */
    public function canRefreshTokensSuccessfully(): void
    {
        // 先進行登入獲取 Token
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $deviceInfo = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Test Device',
        );

        $originalLoginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

        // 等待一秒確保新 Token 有不同的時間戳
        sleep(1);

        // 執行 Token 刷新
        $refreshRequest = new RefreshRequestDTO(
            refreshToken: $originalLoginResponse->tokens->getRefreshToken(),
        );

        $refreshResponse = $this->authenticationService->refresh($refreshRequest, $deviceInfo);

        // 驗證新 Token 與原 Token 不同
        $this->assertInstanceOf(RefreshResponseDTO::class, $refreshResponse);
        $this->assertNotEquals(
            $originalLoginResponse->tokens->getAccessToken(),
            $refreshResponse->tokens->getAccessToken(),
        );
        $this->assertNotEquals(
            $originalLoginResponse->tokens->getRefreshToken(),
            $refreshResponse->tokens->getRefreshToken(),
        );

        // 驗證新 Token 可以正常使用
        $newAccessToken = $refreshResponse->tokens->getAccessToken();
        $this->assertNotEmpty($newAccessToken);
    }

    /**
     * 測試登出流程與 Token 黑名單.
     *
     * @test
     */
    public function canLogoutAndBlacklistTokens(): void
    {
        // 進行登入
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $deviceInfo = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Test Device',
        );

        $loginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

        // 執行登出
        $logoutRequest = new LogoutRequestDTO(
            accessToken: $loginResponse->tokens->getAccessToken(),
            refreshToken: $loginResponse->tokens->getRefreshToken(),
        );

        $this->authenticationService->logout($logoutRequest);

        // 驗證 Refresh Token 已被移除
        $refreshTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertEmpty($refreshTokens);

        // 驗證黑名單功能（檢查是否有黑名單項目）
        $stats = $this->tokenBlacklistRepository->getUserBlacklistStats(1);
        $this->assertArrayHasKey('total_blacklisted', $stats);
        $this->assertGreaterThan(0, $stats['total_blacklisted']);
    }

    /**
     * 測試多設備登入管理.
     *
     * @test
     */
    public function canManageMultipleDeviceLogins(): void
    {
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        // 設備1登入
        $device1Info = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Device 1',
        );
        $device1Response = $this->authenticationService->login($loginRequest, $device1Info);

        // 設備2登入
        $device2Info = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            '192.168.1.101',
            'Device 2',
        );
        $device2Response = $this->authenticationService->login($loginRequest, $device2Info);

        // 驗證兩個設備都有有效的 Token
        $this->assertInstanceOf(TokenPair::class, $device1Response->tokens);
        $this->assertInstanceOf(TokenPair::class, $device2Response->tokens);

        $this->assertEquals(1, $device1Response->userId);
        $this->assertEquals(1, $device2Response->userId);

        // 驗證資料庫中有兩個 Refresh Token 記錄
        $refreshTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertCount(2, $refreshTokens);

        // 從設備1登出
        $logoutRequest = new LogoutRequestDTO(
            accessToken: $device1Response->tokens->getAccessToken(),
            refreshToken: $device1Response->tokens->getRefreshToken(),
        );
        $this->authenticationService->logout($logoutRequest);

        // 驗證設備1的 Refresh Token 已被移除，設備2的仍存在
        $remainingTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertCount(1, $remainingTokens);
    }

    /**
     * 測試無效憑證登入.
     *
     * @test
     */
    public function canHandleInvalidCredentials(): void
    {
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'wrongpassword',
        );

        $deviceInfo = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Test Device',
        );

        // 設定 UserRepository 回傳 null 表示認證失敗
        $mockUserRepository = Mockery::mock(UserRepositoryInterface::class);
        $mockUserRepository->shouldReceive('validateCredentials')
            ->with('test@example.com', 'wrongpassword')
            ->andReturn(null);

        // 暫時替換 userRepository
        $originalUserRepository = $this->userRepository;
        $this->userRepository = $mockUserRepository;

        // 重新建立 AuthenticationService
        $authService = new AuthenticationService(
            $this->jwtTokenService,
            $this->refreshTokenRepository,
            $this->userRepository,
        );

        $this->expectException(AuthenticationException::class);
        $authService->login($loginRequest, $deviceInfo);

        // 恢復原始 userRepository
        $this->userRepository = $originalUserRepository;
    }

    /**
     * 測試黑名單自動清理功能.
     *
     * @test
     */
    public function canCleanupExpiredBlacklistEntries(): void
    {
        // 建立已過期的黑名單條目
        $expiredEntry = new TokenBlacklistEntry(
            jti: 'expired-token-jti',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('-1 hour'),
            blacklistedAt: new DateTimeImmutable('-2 hours'),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $this->tokenBlacklistRepository->addToBlacklist($expiredEntry);

        // 建立未過期的黑名單條目
        $activeEntry = new TokenBlacklistEntry(
            jti: 'active-token-jti',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $this->tokenBlacklistRepository->addToBlacklist($activeEntry);

        // 執行清理
        $cleanupResult = $this->tokenBlacklistService->autoCleanup();
        $cleanedCount = $cleanupResult['expired_cleaned'] ?? 0;

        // 驗證過期條目被清理，活躍條目保留
        $this->assertEquals(1, $cleanedCount);
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('expired-token-jti'));
        $this->assertTrue($this->tokenBlacklistRepository->isBlacklisted('active-token-jti'));
    }

    /**
     * 測試系統健康檢查.
     *
     * @test
     */
    public function canPerformHealthCheck(): void
    {
        // 建立一些測試資料
        $loginRequest = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $deviceInfo = DeviceInfo::fromUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            '192.168.1.100',
            'Health Check Device',
        );

        $loginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

        $logoutRequest = new LogoutRequestDTO(
            accessToken: $loginResponse->tokens->getAccessToken(),
            refreshToken: $loginResponse->tokens->getRefreshToken(),
        );
        $this->authenticationService->logout($logoutRequest);

        // 執行健康檢查
        $healthStatus = $this->tokenBlacklistService->getHealthStatus();

        // 驗證健康狀態包含預期資訊
        $this->assertArrayHasKey('totalBlacklisted', $healthStatus);
        $this->assertArrayHasKey('expiredCount', $healthStatus);
        $this->assertArrayHasKey('activeCount', $healthStatus);
        $this->assertArrayHasKey('oldestEntry', $healthStatus);
        $this->assertArrayHasKey('newestEntry', $healthStatus);

        $this->assertGreaterThanOrEqual(1, $healthStatus['totalBlacklisted']);
    }

    /**
     * 建立 JWT Token 服務 Mock.
     */
    private function createJwtTokenService(): JwtTokenServiceInterface
    {
        // 設定測試用的環境變數 (如果還沒設定)
        if (!isset($_ENV['JWT_PRIVATE_KEY'])) {
            $_ENV['JWT_PRIVATE_KEY'] = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCEd1LvGZVBEKkp\npJV2aGLBoTGvxSHhCQ3ZRGDwVUPv8w7Y0l/xBLhSbh2/iQGfX/bu7kA3kBvY2uH6\nHF1LPTbmF4EtWITExDkM/A3r6nuizYdVBNYM72yriDQPUveg6PAjataamKliexDF\naBAW8d+es9fFDgRtWj4qbO+WUs2vuffjI6SPuHXt1pdggu/NGBBMSv96W5Y6lmA+\ng4Qif4GAQn8nKS+5nvp/e80Rq6YKIr5mFyjgpICDu3RrAATmPKhKej6FgDZp69j3\nZiQWcywCWt3rwMC2Tz9DfdhKdwDzwKL4gnt1k55HZt9xAegWUroEtXzNnHL7tJ9I\nI1CyUDaTAgMBAAECggEAChYDzIzIHoIkPzV24+Mi0ddyLw31fGryEP7x2prDZ3u8\nP6oVAAb5+dzEixbldrsZ1Ctz3Ecut55C4oZSXC43BeH4RfmdclX2ehSfAr2B2G2J\nxmFt4uJABfeC7z/D9w6FakzyNic1jngMWNuJjhWwjybmYOymTaU3YoeU3n9DhgOo\n3zjNj573K5dLyFkAP+9YWQ8HaT/PHgJxDCpTVxEzQsyMxNQYVPZKrYFN/ZyY8oPL\nb9RfYDYeEel4/KOCgvOXPMJ32AcAdH2WwbyAzlt8Dhn6L+x0xIUTVdHlUng0DLp5\nsWqZmEzc81VAFijfo9aKFobsLoA5rJWSCcQ70ukuEQKBgQC4LLp993TOHi99BzJw\ncJNC2A5uq9kSwrXv/emOY2HXmJ6+J8SNksfr3BG94ukgZhJvUNA/n5LyJ3cpgNUm\nEKqK+PMk7S0CT6fbbBRXnnJijsQxyOqFAg7jYmMiYlTWyemPNXKpWCclMUgmSbNG\nJH/trXuLraDs7nnsgWM7k2CUVQKBgQC4IDag+yYQiuIiEAOdlPjv6J9z9dj9ri1w\n1jtiIPk41Xz9xZUA55pvnFXfOBEWxSrIlkLzR48HIA+cLB38XGOFpmW46l2k/o9X\nW7+pOyStdibrp16ppZY/NN6gwFjTnpPzpu7VJZvP+6M3Y2JyGBYCJ1xHPYMOX1oq\njPg6g1XHRwKBgEbQs/hhYKEsTBgn30YKkyTdjFcTbojfIzOfDuG35tQOE+OLyPCi\noopW+N9pUzgo5ye0DA6andbMQ+5KYiqbt+dtp5foNikwVZtx6DR0cQjiWh/GYB46\nV10o5HNBGdvokQyGgYsJoSuU0mgeaHcs65+I1/syDLFtVKYSbgRnO3htAoGBAKNw\nhM104h8BCSXvTSZOHILo3NGUQ187gz6MC/5ZAqC+cMra3h8FdwLnpRoVrKWnswiG\nyTsmJAHRJcodJyjh4b27LMRt1V4mUJrc6E6SH0aSgI3h7ZdtUuccSRosYyzFsNMx\nNQOi9KIz3nfGEpbwZmjXA4SBR5o0bdcjdxyJhFT1AoGAQb3Kw59mlGkDRf6aAmUE\nZ9JBfelirgrQ69ZKCKCVvZG/4mEDmU9E+6kHrf9Hbk1xOuGhY0+tSokLZQVY0+YS\nTcRRp/F1/kf6XHPlpHsaRn0phSKHSXxxXZ23w4Jqc9cDhTpfYZMsAQGacxKg/nNy\nmo0TtZZCNgLlXOCjt0o4Fpc=\n-----END PRIVATE KEY-----";
            $_ENV['JWT_PUBLIC_KEY'] = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhHdS7xmVQRCpKaSVdmhi\nwaExr8Uh4QkN2URg8FVD7/MO2NJf8QS4Um4dv4kBn1/27u5AN5Ab2Nrh+hxdSz02\n5heBLViExMQ5DPwN6+p7os2HVQTWDO9sq4g0D1L3oOjwI2rWmpipYnsQxWgQFvHf\nnrPXxQ4EbVo+KmzvllLNr7n34yOkj7h17daXYILvzRgQTEr/eluWOpZgPoOEIn+B\ngEJ/JykvuZ76f3vNEaumCiK+Zhco4KSAg7t0awAE5jyoSno+hYA2aevY92YkFnMs\nAlrd68DAtk8/Q33YSncA88Ci+IJ7dZOeR2bfcQHoFlK6BLV8zZxy+7SfSCNQslA2\nkwIDAQAB\n-----END PUBLIC KEY-----";
            $_ENV['JWT_ISSUER'] = 'alleynote-api';
            $_ENV['JWT_AUDIENCE'] = 'alleynote-client';
            $_ENV['JWT_ACCESS_TOKEN_TTL'] = '3600';
            $_ENV['JWT_REFRESH_TOKEN_TTL'] = '7200';
        }

        // 使用真實的 JwtTokenService
        $config = new JwtConfig();
        $jwtProvider = new FirebaseJwtProvider($config);

        return new JwtTokenService(
            $jwtProvider,
            $this->refreshTokenRepository, // 使用真實的 Repository
            $this->tokenBlacklistRepository, // 使用真實的 Repository
            $config,
        );
    }

    /**
     * 設定 UserRepository Mock.
     */
    private function setupUserRepositoryMock(): void
    {
        // 設定成功的憑證驗證
        $this->userRepository->shouldReceive('validateCredentials')
            ->andReturnUsing(function ($email, $password) {
                if ($email === 'test@example.com' && $password === 'password123') {
                    return [
                        'id' => 1,
                        'email' => 'test@example.com',
                        'username' => 'testuser',
                        'status' => 1,
                    ];
                }

                return null;
            });

        // 設定更新最後登入時間
        $this->userRepository->shouldReceive('updateLastLogin')
            ->with(1)
            ->andReturn(true);

        // 其他可能需要的方法
        $this->userRepository->shouldReceive('findById')
            ->andReturnUsing(function ($id) {
                if ($id === 1) {
                    return [
                        'id' => 1,
                        'email' => 'test@example.com',
                        'username' => 'testuser',
                        'status' => 1,
                    ];
                }

                return null;
            });

        // 支援 findByUuid 方法
        $this->userRepository->shouldReceive('findByUuid')
            ->andReturnUsing(function ($uuid) {
                // 模擬根據 UUID 找到使用者
                return [
                    'id' => 1,
                    'email' => 'test@example.com',
                    'username' => 'testuser',
                    'status' => 1,
                ];
            });
    }

    /**
     * 建立測試使用者.
     */
    private function createTestUser(): void
    {
        $this->db->exec("
            INSERT INTO users (id, username, email, password, status, created_at, updated_at)
            VALUES (1, 'testuser', 'test@example.com', 'password123', 1, datetime('now'), datetime('now'))
        ");
    }

    /**
     * 產生測試用私鑰.
     */
    private function generateTestPrivateKey(): string
    {
        return '-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA4f5wg5l2hKsTeNem/V41fGnJm6gOdrj8ym3rFkEjWT2btYEt
YUDzWmNfflgKkZwSNgUVFm1JgqGnJkF7xT8w7ZQe2nrjT5e7xzp6UOpG6U3XdMnm
CJc3g8g5x9x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
-----END RSA PRIVATE KEY-----';
    }

    /**
     * 產生測試用公鑰.
     */
    private function generateTestPublicKey(): string
    {
        return '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4f5wg5l2hKsTeNem/V41
fGnJm6gOdrj8ym3rFkEjWT2btYEtYUDzWmNfflgKkZwSNgUVFm1JgqGnJkF7xT8w
7ZQe2nrjT5e7xzp6UOpG6U3XdMnmCJc3g8g5x9x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7x7
QIDAQAB
-----END PUBLIC KEY-----';
    }
}
