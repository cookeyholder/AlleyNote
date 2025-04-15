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
    public function createsSingletonPdoInstance(): void
    {
        $connection1 = DatabaseConnection::getInstance();
        $connection2 = DatabaseConnection::getInstance();

        $this->assertInstanceOf(PDO::class, $connection1);
        $this->assertSame($connection1, $connection2);
    }

    /** @test */
    public function executesQuerySuccessfully(): void
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
        $stmt = $pdo->prepare('INSERT INTO test_table (name) VALUES (?)');
        $stmt->execute(['test']);

        // 驗證資料
        $result = $pdo->query('SELECT * FROM test_table')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('test', $result['name']);
    }

    protected function tearDown(): void
    {
        DatabaseConnection::reset();
        parent::tearDown();
    }
}
