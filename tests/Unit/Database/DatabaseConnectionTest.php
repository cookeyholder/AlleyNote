<?php

namespace Tests\Unit\Database;

use App\Database\DatabaseConnection;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DatabaseConnection::reset();
    }

    /** @test */
    public function it_should_create_singleton_pdo_instance(): void
    {
        $connection1 = DatabaseConnection::getInstance();
        $connection2 = DatabaseConnection::getInstance();

        $this->assertInstanceOf(PDO::class, $connection1);
        $this->assertSame($connection1, $connection2);
    }

    /** @test */
    public function it_should_successfully_execute_query(): void
    {
        $pdo = DatabaseConnection::getInstance();

        // 建立測試資料表
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS test_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )
        ');

        // 插入測試資料
        $pdo->exec("INSERT INTO test_table (name) VALUES ('測試資料')");

        // 查詢資料
        $stmt = $pdo->query('SELECT * FROM test_table');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('測試資料', $result['name']);
    }

    protected function tearDown(): void
    {
        DatabaseConnection::reset();
        parent::tearDown();
    }
}
