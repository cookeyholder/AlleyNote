<?php

namespace Tests\Unit\Repository;

use App\Database\DatabaseConnection;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

class UserRepositoryTest extends TestCase
{
    private PDO $db;
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        DatabaseConnection::reset();
        $this->db = DatabaseConnection::getInstance();
        $this->setupTestDatabase();
        $this->repository = new UserRepository($this->db);
    }

    private function setupTestDatabase(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS users");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                status INTEGER DEFAULT 1,
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /** @test */
    public function it_should_successfully_create_user(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->repository->create($userData);

        $this->assertNotNull($result['id']);
        $this->assertNotNull($result['uuid']);
        $this->assertEquals($userData['username'], $result['username']);
        $this->assertEquals($userData['email'], $result['email']);
        $this->assertNotEquals($userData['password'], $result['password']);
        $this->assertEquals(1, $result['status']);
    }

    /** @test */
    public function it_should_update_user_successfully(): void
    {
        // 先建立使用者
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // 更新使用者資料
        $updateData = [
            'email' => 'updated@example.com',
            'status' => 0
        ];

        $updated = $this->repository->update($user['id'], $updateData);

        $this->assertEquals($updateData['email'], $updated['email']);
        $this->assertEquals($updateData['status'], $updated['status']);
        $this->assertEquals($user['username'], $updated['username']);
    }

    /** @test */
    public function it_should_delete_user_successfully(): void
    {
        // 先建立使用者
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $result = $this->repository->delete($user['id']);
        $this->assertTrue($result);

        $found = $this->repository->findById($user['id']);
        $this->assertNull($found);
    }

    /** @test */
    public function it_should_find_user_by_uuid(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByUuid($created['uuid']);

        $this->assertEquals($created['uuid'], $found['uuid']);
        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    /** @test */
    public function it_should_find_user_by_username(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByUsername($userData['username']);

        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    /** @test */
    public function it_should_find_user_by_email(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findByEmail($userData['email']);

        $this->assertEquals($created['email'], $found['email']);
        $this->assertEquals($created['username'], $found['username']);
    }

    /** @test */
    public function it_should_prevent_duplicate_username(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test1@example.com',
            'password' => 'password123'
        ];

        $this->repository->create($userData);

        $this->expectException(PDOException::class);

        $userData['email'] = 'test2@example.com';
        $this->repository->create($userData);
    }

    /** @test */
    public function it_should_prevent_duplicate_email(): void
    {
        $userData = [
            'username' => 'testuser1',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $this->repository->create($userData);

        $this->expectException(PDOException::class);

        $userData['username'] = 'testuser2';
        $this->repository->create($userData);
    }

    /** @test */
    public function it_should_find_user_by_id(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $created = $this->repository->create($userData);
        $found = $this->repository->findById($created['id']);

        $this->assertEquals($created['id'], $found['id']);
        $this->assertEquals($created['username'], $found['username']);
        $this->assertEquals($created['email'], $found['email']);
    }

    /** @test */
    public function it_should_return_null_when_user_not_found(): void
    {
        $result = $this->repository->findById('999');
        $this->assertNull($result);
    }

    /** @test */
    public function it_should_update_last_login_time(): void
    {
        $user = $this->repository->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $before = new \DateTime();
        sleep(1); // 等待 1 秒確保時間差

        $this->repository->updateLastLogin($user['id']);

        $updated = $this->repository->findById($user['id']);
        $lastLogin = new \DateTime($updated['last_login']);

        $this->assertTrue($lastLogin > $before);
    }
}
