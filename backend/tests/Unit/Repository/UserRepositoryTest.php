<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Domains\Auth\Repositories\UserRepository;
use DateTime;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private PDO $db;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 SQLite 記憶體資料庫連接
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 設置測試資料庫結構
        $this->setupTestDatabase();

        // 初始化 UserRepository
        $this->repository = new UserRepository($this->db);
    }

    private function setupTestDatabase(): void
    {
        $this->db->exec('DROP TABLE IF EXISTS users');
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                status INTEGER DEFAULT 1,
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    #[Test]
    public function createUserSuccessfully(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = $this->repository->create($userData);

        $this->assertNotNull($result['id']);
        $this->assertNotNull($result['uuid']);
        $this->assertEquals($userData['username'], $result['username']);
        $this->assertEquals($userData['email'], $result['email']);
        $this->assertEquals($userData['password'], $result['password_hash']); // Repository 使用 password_hash 欄位
        $this->assertEquals(1, $result['status']);
    }

    #[Test]
    public function updateUserSuccessfully(): void
    {
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $updateData = [
            'email' => 'updated@example.com',
            'status' => 0,
        ];

        $userId = $user['id'];
        $this->assertIsInt($userId);
        $updated = $this->repository->update((string) $userId, $updateData);

        $this->assertEquals($updateData['email'], $updated['email']);
        $this->assertEquals($updateData['status'], $updated['status']);
        $this->assertEquals($user['username'], $updated['username']);
    }

    #[Test]
    public function deleteUserSuccessfully(): void
    {
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $userId = $user['id'];
        $this->assertIsInt($userId);
        $result = $this->repository->delete((string) $userId);
        $this->assertTrue($result);

        $found = $this->repository->findById($user['id']);
        $this->assertNull($found);
    }

    #[Test]
    public function findUserByUuid(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByUuid($created['uuid']);

        $this->assertEquals($created['uuid'], $found['uuid']);
        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    #[Test]
    public function findUserByUsername(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByUsername($userData['username']);

        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    #[Test]
    public function findUserByEmail(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByEmail($userData['email']);

        $this->assertEquals($created['email'], $found['email']);
        $this->assertEquals($created['username'], $found['username']);
    }

    #[Test]
    public function preventDuplicateUsername(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test1@example.com',
            'password' => 'password123',
        ];

        $this->repository->create($userData);

        $this->expectException(PDOException::class);

        $userData['email'] = 'test2@example.com';
        $this->repository->create($userData);
    }

    #[Test]
    public function preventDuplicateEmail(): void
    {
        $userData = [
            'username' => 'testuser1',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $this->repository->create($userData);

        $this->expectException(PDOException::class);

        $userData['username'] = 'testuser2';
        $this->repository->create($userData);
    }

    #[Test]
    public function findUserById(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findById($created['id']);

        $this->assertEquals($created['id'], $found['id']);
        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    #[Test]
    public function returnNullWhenUserNotFound(): void
    {
        $result = $this->repository->findById(999);
        $this->assertNull($result);
    }

    #[Test]
    public function updateLastLoginTime(): void
    {
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $before = new DateTime();
        sleep(1); // 等待 1 秒確保時間差

        $userId = $user['id'];
        $this->assertIsInt($userId);
        $this->repository->updateLastLogin((string) $userId);

        $updated = $this->repository->findById($user['id']);
        $lastLogin = new DateTime($updated['last_login']);

        $this->assertTrue($lastLogin > $before);
    }
}
