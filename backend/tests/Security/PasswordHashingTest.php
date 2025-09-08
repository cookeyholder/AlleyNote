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
        $userData = $this->getTestUserData('testuser', 'test@example.com', 'password123');
        $dto = new RegisterUserDTO($this->validator, $userData);

        $result = $this->authService->register($dto);
        $this->assertValidRegistrationResult($result);

        $userId = $this->extractUserIdFromResult($result);
        $hashedPassword = $this->getHashedPasswordFromDatabase($userId);

        $this->assertArgon2idHash($hashedPassword, (is_array($userData) && array_key_exists('password', $userData) ? $userData['password'] : null));
    }

    #[Test]
    public function shouldUseAppropriateHashingOptions(): void
    {
        $userData = $this->getTestUserData('testuser2', 'test2@example.com', 'securepassword456');
        $dto = new RegisterUserDTO($this->validator, $userData);

        $result = $this->authService->register($dto);
        $userId = $this->extractUserIdFromResult($result);
        $hashedPassword = $this->getHashedPasswordFromDatabase($userId);

        $this->assertAppropriateHashingOptions($hashedPassword);
    }

    #[Test]
    public function shouldRejectWeakPasswords(): void
    {
        $userData = $this->getTestUserData('testuser3', 'test3@example.com', '123'); // 太短的密碼
        $dto = new RegisterUserDTO($this->validator, $userData);

        $this->setupPasswordServiceToRejectWeakPassword('123');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('密碼長度必須至少為 8 個字元');

        $this->authService->register($dto);
    }

    #[Test]
    public function shouldPreventPasswordReuse(): void
    {
        $userData = $this->getTestUserData('testuser4', 'test4@example.com', 'password123');
        $dto = new RegisterUserDTO($this->validator, $userData);

        $result = $this->authService->register($dto);
        $userId = $this->extractUserIdFromResult($result);

        $this->assertValidUserId($userId);

        $this->expectPasswordReuseException();
        $this->userRepository->updatePassword($userId, (is_array($userData) && array_key_exists('password', $userData) ? $userData['password'] : null));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * 建立測試使用者資料.
     */
    private function getTestUserData(string $username, string $email, string $password): array
    {
        return [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $password,
            'user_ip' => '127.0.0.1',
        ];
    }

    /**
     * 驗證註冊結果有效.
     * @param mixed $result
     */
    private function assertValidRegistrationResult($result): void
    {
        $this->assertNotNull($result, '使用者註冊不應該返回 null');
        $this->assertIsArray($result, '註冊結果應該是陣列');
        $this->assertArrayHasKey('user', $result, '註冊結果應該包含 user 鍵');
    }

    /**
     * 從註冊結果中提取使用者 ID.
     * @param mixed $result
     */
    private function extractUserIdFromResult($result): int
    {
        $this->assertIsArray($result, '註冊結果應該是陣列');
        $user = (is_array($result) && array_key_exists('user', $result) ? $result['user'] : null);

        $userId = null;
        if (is_array($user) && isset((is_array($user) && array_key_exists('id', $user) ? $user['id'] : null))) {
            $userId = (is_array($user) && array_key_exists('id', $user) ? $user['id'] : null);
        } elseif (is_object($user) && method_exists($user, 'getId')) {
            $userId = $user->getId();
        } elseif (is_object($user) && isset($user->id)) {
            $userId = $user->id;
        }

        $this->assertNotNull($userId, '無法從註冊結果中取得使用者 ID');
        $this->assertIsInt($userId, '使用者 ID 應該是整數');

        return $userId;
    }

    /**
     * 從資料庫取得雜湊密碼
     */
    private function getHashedPasswordFromDatabase(int $userId): string
    {
        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hashedPassword = $stmt->fetchColumn();

        $this->assertNotFalse($hashedPassword, '無法從資料庫取得雜湊密碼');
        $this->assertIsString($hashedPassword, '雜湊密碼必須是字串型別');

        return $hashedPassword;
    }

    /**
     * 驗證 Argon2id 雜湊.
     */
    private function assertArgon2idHash(string $hashedPassword, string $originalPassword): void
    {
        $this->assertStringStartsWith('$argon2id$', $hashedPassword);
        $this->assertTrue(password_verify($originalPassword, $hashedPassword));
    }

    /**
     * 驗證適當的雜湊選項.
     */
    private function assertAppropriateHashingOptions(string $hashedPassword): void
    {
        $info = password_get_info($hashedPassword);

        $this->assertEquals(PASSWORD_ARGON2ID, (is_array($info) && array_key_exists('algo', $info) ? $info['algo'] : null));
        $this->assertGreaterThan(50, strlen($hashedPassword));
    }

    /**
     * 設定密碼服務拒絕弱密碼
     */
    private function setupPasswordServiceToRejectWeakPassword(string $weakPassword): void
    {
        $this->passwordService->shouldReceive('validatePassword')
            ->with($weakPassword)
            ->andThrow(new InvalidArgumentException('密碼長度必須至少為 8 個字元'));
    }

    /**
     * 驗證使用者 ID 有效.
     * @param mixed $userId
     */
    private function assertValidUserId($userId): void
    {
        $this->assertNotNull($userId, '無法從註冊結果中取得使用者 ID');
        $this->assertIsInt($userId, '使用者 ID 應該是整數');
    }

    /**
     * 期待密碼重複使用例外.
     */
    private function expectPasswordReuseException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('新密碼不能與目前的密碼相同');
    }
}
