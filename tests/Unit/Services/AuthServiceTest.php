<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;

class AuthServiceTest extends TestCase
{
    private UserRepository|MockInterface $userRepository;
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->service = new AuthService($this->userRepository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /** @test */
    public function it_should_register_new_user_successfully(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 設定 mock 預期行為
        $this->userRepository->expects()
            ->create($userData)
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1
            ]);

        // 執行測試
        $result = $this->service->register($userData);

        // 驗證結果
        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals(1, $result['status']);
    }

    /** @test */
    public function it_should_validate_registration_data(): void
    {
        // 準備無效的測試資料
        $invalidData = [
            'username' => '', // 空的使用者名稱
            'email' => 'invalid-email', // 無效的電子郵件
            'password' => '123' // 太短的密碼
        ];

        // 執行測試並預期會拋出例外
        $this->expectException(\InvalidArgumentException::class);
        $this->service->register($invalidData);
    }

    /** @test */
    public function it_should_login_user_successfully(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 模擬資料庫中的使用者資料（已雜湊的密碼）
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->expects()
            ->findByEmail('test@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'test@example.com',
                'password' => $hashedPassword,
                'status' => 1
            ]);

        $this->userRepository->expects()
            ->updateLastLogin('1')
            ->andReturn(true);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertTrue($result['success']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    /** @test */
    public function it_should_fail_login_with_invalid_credentials(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // 模擬資料庫中的使用者資料
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->expects()
            ->findByEmail('test@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'test@example.com',
                'password' => $hashedPassword,
                'status' => 1
            ]);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertFalse($result['success']);
        $this->assertEquals('無效的認證資訊', $result['message']);
    }

    /** @test */
    public function it_should_not_login_inactive_user(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'inactive@example.com',
            'password' => 'password123'
        ];

        // 模擬停用的使用者資料
        $hashedPassword = password_hash('password123', PASSWORD_ARGON2ID);
        $this->userRepository->expects()
            ->findByEmail('inactive@example.com')
            ->andReturn([
                'id' => '1',
                'uuid' => 'test-uuid',
                'email' => 'inactive@example.com',
                'password' => $hashedPassword,
                'status' => 0 // 停用狀態
            ]);

        // 執行測試
        $result = $this->service->login($credentials);

        // 驗證結果
        $this->assertFalse($result['success']);
        $this->assertEquals('帳號已被停用', $result['message']);
    }
}
