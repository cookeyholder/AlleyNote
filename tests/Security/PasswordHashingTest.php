<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\AuthService;
use InvalidArgumentException;
use Mockery;
use PDO;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    protected AuthService $authService;

    protected UserRepository $userRepository;

    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->authService = Mockery::mock(AuthService::class);
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->db = Mockery::mock(PDO::class);

        // 使用 SQLite 記憶體資料庫進行測試
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試資料表
        $this->createTestTables();

        $this->userRepository = new UserRepository($this->db);
        $this->authService = new AuthService($this->userRepository);
    }

    protected function createTestTables(): void
    {
        $this->db->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                status INTEGER DEFAULT 1,
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    /** @test */
    public function shouldHashPasswordUsingArgon2id(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 註冊使用者
        $user = $this->authService->register($userData);

        // 從資料庫取得雜湊後的密碼
        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hashedPassword = $stmt->fetchColumn();

        // 驗證使用 Argon2id 演算法
        $this->assertStringStartsWith('$argon2id$', $hashedPassword);

        // 驗證原始密碼可以通過驗證
        $this->assertTrue(password_verify($userData['password'], $hashedPassword));
    }

    /** @test */
    public function shouldUseAppropriateHashingOptions(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 註冊使用者
        $user = $this->authService->register($userData);

        // 從資料庫取得雜湊後的密碼
        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hashedPassword = $stmt->fetchColumn();

        // 取得雜湊資訊
        $info = password_get_info($hashedPassword);

        // 驗證使用適當的雜湊選項
        $this->assertEquals(PASSWORD_ARGON2ID, $info['algo']);

        // 驗證雜湊長度足夠
        $this->assertGreaterThan(50, strlen($hashedPassword));
    }

    /** @test */
    public function shouldRejectWeakPasswords(): void
    {
        // 準備測試資料（弱密碼）
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123', // 太短的密碼
        ];

        // 預期會拋出例外
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('密碼長度必須至少為 8 個字元');

        // 執行測試
        $this->authService->register($userData);
    }

    /** @test */
    public function shouldPreventPasswordReuse(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 註冊使用者
        $user = $this->authService->register($userData);

        // 模擬使用者嘗試更新密碼為相同的密碼
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('新密碼不能與目前的密碼相同');

        $this->userRepository->updatePassword($user['id'], $userData['password']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
