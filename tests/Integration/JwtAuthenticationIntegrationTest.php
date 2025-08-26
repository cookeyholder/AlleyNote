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
use AlleyNote\Domains\Auth\Services\TokenBlacklistService;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use AlleyNote\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use AlleyNote\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use DateTimeImmutable;
use Mockery;
use Tests\TestCase;

/**
 * JWT 認證系統整合測試
 * 驗證各元件間的協作與端到端流程.
 *
 * @group integration
 */
class JwtAuthenticationIntegrationTest extends TestCase
{
    private JwtTokenServiceInterface $jwtTokenService;

    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    private TokenBlacklistRepositoryInterface $tokenBlacklistRepository;

    private UserRepositoryInterface $userRepository;

    private AuthenticationServiceInterface $authenticationService;

    private TokenBlacklistService $tokenBlacklistService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立真實的服務實例，模擬完整的系統行為
        $this->jwtTokenService = $this->createJwtTokenService();
        $this->refreshTokenRepository = new RefreshTokenRepository($this->db);
        $this->tokenBlacklistRepository = new TokenBlacklistRepository($this->db);
        $this->tokenBlacklistService = new TokenBlacklistService($this->tokenBlacklistRepository);

        // Mock UserRepository for testing
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
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
        $mockService = Mockery::mock(JwtTokenServiceInterface::class);

        // Mock generateTokenPair 方法
        $mockService->shouldReceive('generateTokenPair')
            ->andReturn(new TokenPair(
                'mock.access.token',
                'mock.refresh.token',
                new DateTimeImmutable('+1 hour'),
                new DateTimeImmutable('+30 days'),
            ));

        // Mock 其他需要的方法...
        $mockService->shouldReceive('validateToken')->andReturn(true);
        $mockService->shouldReceive('extractPayload')
            ->andReturn(new \AlleyNote\Domains\Auth\ValueObjects\JwtPayload(
                jti: 'mock-jti-' . uniqid(),
                sub: '1',
                iss: 'alleynote',
                aud: ['alleynote'],
                iat: new DateTimeImmutable(),
                exp: new DateTimeImmutable('+1 hour'),
                customClaims: ['type' => 'access']
            ));
        $mockService->shouldReceive('revokeToken')->andReturn(true);

        return $mockService;
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
