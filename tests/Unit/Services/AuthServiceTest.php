<?php

namespace Tests\Unit\Services;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\AuthService;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private UserRepository|MockInterface $userRepository;

    private PasswordSecurityServiceInterface|MockInterface $passwordService;

    private ValidatorInterface|MockInterface $validator;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->passwordService = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        $this->service = new AuthService($this->userRepository, $this->passwordService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testIt_should_register_new_user_successfully(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '192.168.1.1',
        ];

        // 設定驗證器 mock
        $this->validator->shouldReceive('addRule')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('addMessage')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($userData);

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 設定密碼服務 mock
        $this->passwordService->shouldReceive('validatePassword')
            ->once()
            ->with('password123');

        $this->passwordService->shouldReceive('hashPassword')
            ->once()
            ->with('password123')
            ->andReturn('hashed_password');

        // 設定用戶倉庫 mock
        $expectedData = $dto->toArray();
        $expectedData['password'] = 'hashed_password';

        $this->userRepository->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1,
            ]);

        // 執行測試
        $result = $this->service->register($dto);

        // 驗證結果
        $this->assertTrue($result['success']);
        $this->assertEquals('註冊成功', $result['message']);
        $this->assertArrayHasKey('user', $result);

        $user = $result['user'];
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals(1, $user['status']);
    }

    public function testIt_should_validate_registration_data(): void
    {
        // 準備無效的測試資料
        $invalidData = [
            'username' => '', // 空的使用者名稱
            'email' => 'invalid-email', // 無效的電子郵件
            'password' => '123', // 太短的密碼
            'confirm_password' => '456', // 不匹配的確認密碼
            'user_ip' => '192.168.1.1',
        ];

        // 設定驗證器 mock 拋出驗證異常
        $this->validator->shouldReceive('addRule')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('addMessage')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andThrow(new ValidationException(
                new ValidationResult(false, ['username' => ['使用者名稱不能為空']], [], []),
            ));

        // 執行測試並預期會拋出例外
        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $invalidData);
    }

    public function testIt_should_login_user_successfully(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 模擬資料庫中的使用者資料（已雜湊的密碼）
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'test@example.com',
                'password' => $hashedPassword,
                'status' => 1,
            ]);

        $this->userRepository->shouldReceive('updateLastLogin')
            ->once()
            ->with('1')
            ->andReturn(true);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertTrue($result['success']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    public function testIt_should_fail_login_with_invalid_credentials(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        // 模擬資料庫中的使用者資料
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'test@example.com',
                'password' => $hashedPassword,
                'status' => 1,
            ]);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertFalse($result['success']);
        $this->assertEquals('無效的認證資訊', $result['message']);
    }

    public function testIt_should_not_login_inactive_user(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ];

        // 模擬停用的使用者資料
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->shouldReceive('findByEmail')
            ->once()
            ->with('inactive@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'inactive@example.com',
                'password' => $hashedPassword,
                'status' => 0, // 停用狀態
            ]);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertFalse($result['success']);
        $this->assertEquals('帳號已被停用', $result['message']);
    }
}
