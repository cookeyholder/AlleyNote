<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Shared\Contracts\ValidatorInterface;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    protected AuthService $authService;

    protected UserRepository $userRepository;

    protected PasswordSecurityServiceInterface|MockInterface $passwordService;

    protected ActivityLoggingServiceInterface|MockInterface $activityLogger;

    protected ValidatorInterface|MockInterface $validator;

    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化mock對象
        $this->passwordService = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        // 設定 ActivityLoggingService 預設行為
        $this->activityLogger->shouldReceive('logFailure')
            ->byDefault()
            ->andReturn(true);
        $this->activityLogger->shouldReceive('logSuccess')
            ->byDefault()
            ->andReturn(true);

        // 使用 SQLite 記憶體資料庫進行測試
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試資料表
        $this->createTestTables();

        $this->userRepository = new UserRepository($this->db);
        $this->authService = new AuthService($this->userRepository, $this->passwordService);

        // 設定活動記錄器的預設行為
        $this->activityLogger->shouldReceive('log')->byDefault()->andReturn(true);

        // 設定 validator 的預設行為
        $this->validator->shouldReceive('addRule')
            ->andReturnSelf()
            ->byDefault();

        $this->validator->shouldReceive('addMessage')
            ->andReturnSelf()
            ->byDefault();

        $this->validator->shouldReceive('validate')
            ->andReturnUsing(function ($data) {
                return $data; // 返回原始資料作為驗證過的資料
            })
            ->byDefault();

        $this->validator->shouldReceive('validateOrFail')
            ->andReturnUsing(function ($data) {
                return $data; // 返回原始資料作為驗證過的資料
            })
            ->byDefault();

        // 設定 passwordService 的預設行為
        $this->passwordService->shouldReceive('validatePassword')
            ->andReturnNull()
            ->byDefault();

        $this->passwordService->shouldReceive('hashPassword')
            ->andReturnUsing(function ($password) {
                return password_hash($password, PASSWORD_ARGON2ID);
            })
            ->byDefault();
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

    #[Test]
    public function shouldHashPasswordUsingArgon2id(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '127.0.0.1',
        ];

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 註冊使用者
        $user = $this->authService->register($dto);

        // 檢查用戶是否成功建立
        $this->assertNotNull($user, 'User registration should succeed');

        // 從資料庫中直接檢索最新的用戶
        $stmt = $this->db->prepare('SELECT id, password FROM users ORDER BY id DESC LIMIT 1');
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($userData, '應該能找到剛建立的用戶');
        $hashedPassword = (is_array($userData) ? $userData['password'] : (is_object($userData) ? $userData->password : null));

        // 確保密碼哈希不為空
        $this->assertNotFalse($hashedPassword, '密碼哈希不應該是 false');
        $this->assertNotEmpty($hashedPassword, '密碼哈希不應該是空的');

        // 驗證使用 Argon2id 演算法
        $this->assertStringStartsWith('$argon2id$', $hashedPassword);

        // 驗證原始密碼可以通過驗證
        // 使用原始的明文密碼，而不是 DTO 中的密碼（可能已被哈希）
        $this->assertTrue(password_verify('password123', $hashedPassword), '原始密碼應該能通過驗證');
    }

    #[Test]
    public function shouldUseAppropriateHashingOptions(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser2',
            'email' => 'test2@example.com',
            'password' => 'securepassword456',
            'confirm_password' => 'securepassword456',
            'user_ip' => '127.0.0.1',
        ];

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 註冊使用者
        $user = $this->authService->register($dto);

        // 檢查用戶是否成功建立
        $this->assertNotNull($user, 'User registration should succeed');

        // 從資料庫取得雜湊後的密碼
        $stmt = $this->db->prepare('SELECT id, password FROM users ORDER BY id DESC LIMIT 1');
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($userData, '應該能找到剛建立的用戶');
        $hashedPassword = (is_array($userData) ? $userData['password'] : (is_object($userData) ? $userData->password : null));

        // 確保密碼哈希不為空
        $this->assertNotFalse($hashedPassword, '密碼哈希不應該是 false');

        // 取得雜湊資訊
        $info = password_get_info($hashedPassword);

        // 驗證使用適當的雜湊選項
        $this->assertEquals(PASSWORD_ARGON2ID, (is_array($info) && isset((is_array($info) ? $info['algo'] : (is_object($info) ? $info->algo : null)))) ? (is_array($info) ? $info['algo'] : (is_object($info) ? $info->algo : null)) : null);

        // 驗證雜湊長度足夠
        $this->assertGreaterThan(50, strlen($hashedPassword));
    }

    #[Test]
    public function shouldRejectWeakPasswords(): void
    {
        // 準備測試資料（弱密碼）
        $userData = [
            'username' => 'testuser3',
            'email' => 'test3@example.com',
            'password' => '123', // 太短的密碼
            'confirm_password' => '123',
            'user_ip' => '127.0.0.1',
        ];

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 設定 passwordService 會拋出異常
        $this->passwordService->shouldReceive('validatePassword')
            ->with('123')
            ->andThrow(new InvalidArgumentException('密碼長度必須至少為 8 個字元'));

        // 預期會拋出例外
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('密碼長度必須至少為 8 個字元');

        // 執行測試
        $this->authService->register($dto);
    }

    #[Test]
    public function shouldPreventPasswordReuse(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser4',
            'email' => 'test4@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '127.0.0.1',
        ];

        // 建立 DTO
        $dto = new RegisterUserDTO($this->validator, $userData);

        // 註冊使用者
        $user = $this->authService->register($dto);

        // 檢查用戶是否成功建立
        $this->assertNotNull($user, 'User registration should succeed');

        // 從資料庫取得用戶 ID
        $stmt = $this->db->prepare('SELECT id FROM users ORDER BY id DESC LIMIT 1');
        $stmt->execute();
        $userId = $stmt->fetchColumn();

        $this->assertNotFalse($userId, '應該能找到剛建立的用戶 ID');

        // 模擬使用者嘗試更新密碼為相同的密碼
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('新密碼不能與目前的密碼相同');

        $this->userRepository->updatePassword((int) $userId, (is_array($userData) && isset((is_array($userData) ? $userData['password'] : (is_object($userData) ? $userData->password : null)))) ? (is_array($userData) ? $userData['password'] : (is_object($userData) ? $userData->password : null)) : null);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
