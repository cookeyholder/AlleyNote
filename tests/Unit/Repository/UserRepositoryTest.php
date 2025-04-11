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
}
