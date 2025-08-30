<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Exceptions\TokenGenerationException;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\TokenPair;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\AuthService;
use App\Shared\Contracts\ValidatorInterface;
use DateTimeImmutable;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * AuthService 單元測試.
 *
 * 測試更新後的 AuthService，包含 JWT 整合和向後相容性功能。
 * 驗證傳統認證模式和 JWT 認證模式都能正確運作。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class AuthServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private UserRepository|MockInterface $userRepository;

    private PasswordSecurityServiceInterface|MockInterface $passwordService;

    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private ValidatorInterface|MockInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->passwordService = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * 測試傳統模式的使用者註冊（無 JWT）.
     */
    public function test_register_traditional_mode_without_jwt(): void
    {
        // 建立傳統模式的 AuthService（不使用 JWT）
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: null,
            jwtEnabled: false,
        );

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '192.168.1.1',
        ];

        // 設定驗證器 mock
        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')->once()->andReturn($userData);

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 設定 mock expectations
        $this->passwordService->shouldReceive('validatePassword')
            ->once()
            ->with('password123');

        $this->passwordService->shouldReceive('hashPassword')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');

        $expectedUserData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'user_ip' => '192.168.1.1',
        ];

        $this->userRepository->shouldReceive('create')
            ->once()
            ->with($expectedUserData)
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'email' => 'test@example.com',
                'created_at' => '2025-01-25 10:00:00',
            ]);

        // 執行測試
        $result = $service->register($dto);

        // 驗證結果 - 傳統格式，不包含 tokens
        $this->assertTrue($result['success']);
        $this->assertEquals('註冊成功', $result['message']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertArrayNotHasKey('tokens', $result);
    }

    /**
     * 測試 JWT 模式的使用者註冊.
     */
    public function test_register_jwt_mode_with_tokens(): void
    {
        // 建立 JWT 模式的 AuthService
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: $this->jwtTokenService,
            jwtEnabled: true,
        );

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '192.168.1.1',
        ];

        // 設定驗證器 mock
        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')->once()->andReturn($userData);

        $dto = new RegisterUserDTO($this->validator, $userData);

        // 建立 DeviceInfo
        $deviceInfo = DeviceInfo::fromUserAgent(
            userAgent: 'Mozilla/5.0 Test Browser',
            ipAddress: '192.168.1.100',
            deviceName: 'Test Device',
        );

        // 設定 mock expectations
        $this->passwordService->shouldReceive('validatePassword')->once()->with('password123');
        $this->passwordService->shouldReceive('hashPassword')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');

        $createdUser = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'created_at' => '2025-01-25 10:00:00',
        ];

        $this->userRepository->shouldReceive('create')->once()->andReturn($createdUser);

        // 建立 JWT token pair（使用正確格式的 JWT token）
        $tokenPair = new TokenPair(
            accessToken: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwiaWF0IjoxNTE2MjM5MDIyfQ.signature',
            refreshToken: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwiaWF0IjoxNTE2MjM5MDIyfQ.refresh_signature',
            accessTokenExpiresAt: new DateTimeImmutable('+1 hour'),
            refreshTokenExpiresAt: new DateTimeImmutable('+30 days'),
            tokenType: 'Bearer',
        );

        $this->jwtTokenService->shouldReceive('generateTokenPair')
            ->once()
            ->withArgs(function ($userId, $deviceInfo, $customClaims) {
                return $userId === 1
                    && $deviceInfo instanceof DeviceInfo
                    && $deviceInfo->getIpAddress() === '192.168.1.100'
                    && is_array($customClaims)
                    && $customClaims['type'] === 'registration'
                    && $customClaims['username'] === 'testuser'
                    && $customClaims['email'] === 'test@example.com';
            })
            ->andReturn($tokenPair);

        // 執行測試
        $result = $service->register($dto, $deviceInfo);

        // 驗證結果 - 包含 JWT tokens
        $this->assertTrue($result['success']);
        $this->assertEquals('註冊成功', $result['message']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertEquals('Bearer', $result['tokens']['token_type']);
        $this->assertArrayHasKey('access_token', $result['tokens']);
        $this->assertArrayHasKey('refresh_token', $result['tokens']);
        $this->assertArrayHasKey('expires_in', $result['tokens']);
        $this->assertArrayHasKey('expires_at', $result['tokens']);
    }

    /**
     * 測試 JWT 模式下 token 產生失敗的情況.
     */
    public function test_register_jwt_mode_token_generation_failure(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: $this->jwtTokenService,
            jwtEnabled: true,
        );

        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '192.168.1.1',
        ];

        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')->once()->andReturn($userData);

        $dto = new RegisterUserDTO($this->validator, $userData);
        $deviceInfo = DeviceInfo::fromUserAgent('Test Browser', '192.168.1.1');

        // 設定正常的註冊流程
        $this->passwordService->shouldReceive('validatePassword')->once();
        $this->passwordService->shouldReceive('hashPassword')->once()->andReturn('hashed_password');
        $this->userRepository->shouldReceive('create')->once()->andReturn([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        // 設定 JWT token 產生失敗
        $this->jwtTokenService->shouldReceive('generateTokenPair')
            ->once()
            ->andThrow(new TokenGenerationException('Token generation failed'));

        $result = $service->register($dto, $deviceInfo);

        // 驗證回退到傳統格式
        $this->assertTrue($result['success']);
        $this->assertEquals('註冊成功', $result['message']);
        $this->assertArrayNotHasKey('tokens', $result);
    }

    /**
     * 測試傳統模式的使用者登入.
     */
    public function test_login_traditional_mode_success(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: null,
            jwtEnabled: false,
        );

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $user = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'status' => 1,
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn($user);

        $this->userRepository->shouldReceive('updateLastLogin')
            ->once()
            ->with('1');

        $result = $service->login($credentials);

        $this->assertTrue($result['success']);
        $this->assertEquals('登入成功', $result['message']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertArrayNotHasKey('tokens', $result);
        $this->assertArrayNotHasKey('password', $result['user']);
    }

    /**
     * 測試 JWT 模式的使用者登入.
     */
    public function test_login_jwt_mode_success(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: $this->jwtTokenService,
            jwtEnabled: true,
        );

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $deviceInfo = DeviceInfo::fromUserAgent('Test Browser', '192.168.1.1');

        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $user = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'status' => 1,
            'role' => 'user',
        ];

        $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);
        $this->userRepository->shouldReceive('updateLastLogin')->once()->with('1');

        $tokenPair = new TokenPair(
            accessToken: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwiaWF0IjoxNTE2MjM5MDIyfQ.signature',
            refreshToken: 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIiwiaWF0IjoxNTE2MjM5MDIyfQ.refresh_signature',
            accessTokenExpiresAt: new DateTimeImmutable('+1 hour'),
            refreshTokenExpiresAt: new DateTimeImmutable('+30 days'),
        );

        $this->jwtTokenService->shouldReceive('generateTokenPair')
            ->once()
            ->andReturn($tokenPair);

        $result = $service->login($credentials, $deviceInfo);

        $this->assertTrue($result['success']);
        $this->assertEquals('登入成功', $result['message']);
        $this->assertEquals('testuser', $result['user']['username']);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertArrayHasKey('access_token', $result['tokens']);
    }

    /**
     * 測試使用者不存在的情況.
     */
    public function test_login_user_not_found(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: null,
            jwtEnabled: false,
        );

        $credentials = [
            'email' => 'notfound@example.com',
            'password' => 'password123',
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('notfound@example.com')
            ->andReturn(null);

        $result = $service->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertEquals('無效的認證資訊', $result['message']);
    }

    /**
     * 測試帳號被停用的情況.
     */
    public function test_login_user_disabled(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
        );

        $credentials = [
            'email' => 'disabled@example.com',
            'password' => 'password123',
        ];

        $user = [
            'id' => 1,
            'email' => 'disabled@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'status' => 0, // 停用狀態
        ];

        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $result = $service->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertEquals('帳號已被停用', $result['message']);
    }

    /**
     * 測試密碼錯誤的情況.
     */
    public function test_login_invalid_password(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
        );

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $user = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('correctpassword', PASSWORD_ARGON2ID),
            'status' => 1,
        ];

        $this->userRepository->shouldReceive('findByEmail')->once()->andReturn($user);

        $result = $service->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertEquals('無效的認證資訊', $result['message']);
    }

    /**
     * 測試傳統模式的登出.
     */
    public function test_logout_traditional_mode(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: null,
            jwtEnabled: false,
        );

        $result = $service->logout();

        $this->assertTrue($result['success']);
        $this->assertEquals('登出成功', $result['message']);
    }

    /**
     * 測試 JWT 模式的登出.
     */
    public function test_logout_jwt_mode_success(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: $this->jwtTokenService,
            jwtEnabled: true,
        );

        $accessToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test.signature';
        $deviceInfo = DeviceInfo::fromUserAgent('Test Browser', '192.168.1.1');

        $this->jwtTokenService->shouldReceive('revokeToken')
            ->once()
            ->with($accessToken)
            ->andReturn(true);

        $result = $service->logout($accessToken, $deviceInfo);

        $this->assertTrue($result['success']);
        $this->assertEquals('登出成功', $result['message']);
    }

    /**
     * 測試 JWT 模式登出時撤銷失敗的情況.
     */
    public function test_logout_jwt_mode_revocation_failure(): void
    {
        $service = new AuthService(
            userRepository: $this->userRepository,
            passwordService: $this->passwordService,
            jwtTokenService: $this->jwtTokenService,
            jwtEnabled: true,
        );

        $accessToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test.signature';
        $deviceInfo = DeviceInfo::fromUserAgent('Test Browser', '192.168.1.1');

        // 模擬撤銷失敗
        $this->jwtTokenService->shouldReceive('revokeToken')
            ->once()
            ->with($accessToken)
            ->andThrow(new Exception('Revocation failed'));

        $result = $service->logout($accessToken, $deviceInfo);

        // 即使撤銷失敗，仍然回傳成功（使用者體驗優先）
        $this->assertTrue($result['success']);
        $this->assertEquals('登出成功', $result['message']);
    }
}
