<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Infrastructure\Database\DatabaseConnection;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class DatabaseConnectionTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DatabaseConnection::reset();
    }

    #[Test]
    public function createsSingletonPdoInstance(): void
    {
        $connection1 = DatabaseConnection::getInstance();
        $connection2 = DatabaseConnection::getInstance();

        $this->assertInstanceOf(PDO::class, $connection1);
        $this->assertSame($connection1, $connection2);
    }

    #[Test]
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

    #[Test]
    public function appliesSqlitePerformancePragmasCorrectly(): void
    {
        $pdo = DatabaseConnection::getInstance();

        // 驗證外鍵約束已開啟
        $fkStmt = $pdo->query('PRAGMA foreign_keys;');
        $this->assertNotFalse($fkStmt);
        $this->assertEquals(1, (int) $fkStmt->fetchColumn());

        // 驗證 busy_timeout 已設定為 5000ms
        $busyTimeoutStmt = $pdo->query('PRAGMA busy_timeout;');
        $this->assertNotFalse($busyTimeoutStmt);
        $this->assertEquals(5000, (int) $busyTimeoutStmt->fetchColumn());

        // 驗證 temp_store 為 MEMORY (2)
        $tempStoreStmt = $pdo->query('PRAGMA temp_store;');
        $this->assertNotFalse($tempStoreStmt);
        $this->assertEquals(2, (int) $tempStoreStmt->fetchColumn());

        // 驗證 cache_size 已調整 (負值代表以 KiB 為單位，-64000)
        $cacheSizeStmt = $pdo->query('PRAGMA cache_size;');
        $this->assertNotFalse($cacheSizeStmt);
        $this->assertEquals(-64000, (int) $cacheSizeStmt->fetchColumn());
    }

    #[Test]
    public function appliesFileDatabasePragmas(): void
    {
        $tempDb = sys_get_temp_dir() . '/alleynote_test_pragma_' . uniqid() . '.sqlite3';
        $pdo = new PDO('sqlite:' . $tempDb, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        DatabaseConnection::applySqlitePragmas($pdo, false);

        $journalModeStmt = $pdo->query('PRAGMA journal_mode;');
        $this->assertNotFalse($journalModeStmt);
        $journalMode = (string) $journalModeStmt->fetchColumn();
        $this->assertEquals('wal', strtolower($journalMode));

        $synchronousStmt = $pdo->query('PRAGMA synchronous;');
        $this->assertNotFalse($synchronousStmt);
        $synchronous = (int) $synchronousStmt->fetchColumn();
        $this->assertEquals(1, $synchronous); // 1 = NORMAL

        $mmapSizeStmt = $pdo->query('PRAGMA mmap_size;');
        $this->assertNotFalse($mmapSizeStmt);
        $mmapSize = (int) $mmapSizeStmt->fetchColumn();
        $this->assertGreaterThanOrEqual(268435456, $mmapSize);

        unset($pdo);
        if (file_exists($tempDb)) {
            unlink($tempDb);
        }
        if (file_exists($tempDb . '-wal')) {
            unlink($tempDb . '-wal');
        }
        if (file_exists($tempDb . '-shm')) {
            unlink($tempDb . '-shm');
        }
    }

    protected function tearDown(): void
    {
        DatabaseConnection::reset();
        parent::tearDown();
    }
}
