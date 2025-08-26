<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DatabaseBackupTest extends TestCase
{
    protected string $backupDir;

    protected PDO $db;

    protected string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用目錄
        $this->backupDir = sys_get_temp_dir() . '/alleynote_backup_test_' . uniqid();
        mkdir($this->backupDir);

        // 建立測試用資料庫
        $this->dbPath = $this->backupDir . '/test.db';
        $this->db = new PDO('sqlite:' . $this->dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試用資料表
        $this->createTestTables();
        $this->insertTestData();
    }

    protected function createTestTables(): void
    {
        $this->db->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->db->exec('
            CREATE TABLE attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id)
            )
        ');
    }

    private function insertTestData(): void
    {
        $this->db->exec("
            INSERT INTO posts (title, content) VALUES 
            ('測試文章1', '內容1'),
            ('測試文章2', '內容2')
        ");

        $this->db->exec("
            INSERT INTO attachments (post_id, filename) VALUES 
            (1, 'file1.txt'),
            (1, 'file2.txt'),
            (2, 'file3.txt')
        ");
    }

    #[Test]


    public function backupDatabaseSuccessfully(): void
    {
        // 執行備份腳本
        $backupFile = $this->backupDir . '/backup.sqlite';
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_db.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($this->dbPath),
            escapeshellarg($backupFile),
        ), $output, $returnVar);

        // 驗證備份是否成功
        $this->assertEquals(0, $returnVar, '備份腳本執行失敗: ' . implode("\n", $output));
        $this->assertFileExists($backupFile, '備份檔案不存在');
        $this->assertGreaterThan(0, filesize($backupFile), '備份檔案是空的');

        // 驗證備份檔案的完整性
        $backupDb = new PDO('sqlite:' . $backupFile);
        $stmt = $backupDb->query('SELECT COUNT(*) FROM posts');
        $this->assertEquals(2, $stmt->fetchColumn(), '備份的文章數量不正確');

        $stmt = $backupDb->query('SELECT COUNT(*) FROM attachments');
        $this->assertEquals(3, $stmt->fetchColumn(), '備份的附件數量不正確');
    }

    #[Test]


    public function restoreDatabaseSuccessfully(): void
    {
        // 先建立備份
        $backupFile = $this->backupDir . '/backup.sqlite';
        copy($this->dbPath, $backupFile);

        // 清空原始資料庫
        $this->db->exec('DELETE FROM attachments');
        $this->db->exec('DELETE FROM posts');

        // 執行還原腳本
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_db.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->dbPath),
        ), $output, $returnVar);

        // 驗證還原是否成功
        $this->assertEquals(0, $returnVar, '還原腳本執行失敗: ' . implode("\n", $output));

        // 驗證資料是否正確還原
        $stmt = $this->db->query('SELECT COUNT(*) FROM posts');
        $this->assertEquals(2, $stmt->fetchColumn(), '還原後的文章數量不正確');

        $stmt = $this->db->query('SELECT COUNT(*) FROM attachments');
        $this->assertEquals(3, $stmt->fetchColumn(), '還原後的附件數量不正確');
    }

    #[Test]


    public function handleBackupErrorsGracefully(): void
    {
        // 使用不存在的來源資料庫
        $nonExistentDb = $this->backupDir . '/nonexistent.db';
        $backupFile = $this->backupDir . '/backup.sqlite';

        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_db.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($nonExistentDb),
            escapeshellarg($backupFile),
        ), $output, $returnVar);

        // 驗證錯誤處理
        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    #[Test]


    public function handleRestoreErrorsGracefully(): void
    {
        // 使用不存在的備份檔案
        $nonExistentBackup = $this->backupDir . '/nonexistent_backup.sqlite';

        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_db.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($nonExistentBackup),
            escapeshellarg($this->dbPath),
        ), $output, $returnVar);

        // 驗證錯誤處理
        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    #[Test]


    public function maintainDataIntegrityDuringBackupRestore(): void
    {
        // 記錄原始資料
        $originalPosts = $this->db->query('SELECT * FROM posts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $originalAttachments = $this->db->query('SELECT * FROM attachments ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        // 執行備份
        $backupFile = $this->backupDir . '/backup.sqlite';
        exec(sprintf(
            '/bin/bash %s/scripts/backup_db.sh %s %s',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($this->dbPath),
            escapeshellarg($backupFile),
        ));

        // 清空原始資料庫
        $this->db->exec('DELETE FROM attachments');
        $this->db->exec('DELETE FROM posts');

        // 執行還原
        exec(sprintf(
            '/bin/bash %s/scripts/restore_db.sh %s %s',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->dbPath),
        ));

        // 比較還原後的資料
        $restoredPosts = $this->db->query('SELECT * FROM posts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $restoredAttachments = $this->db->query('SELECT * FROM attachments ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertEquals($originalPosts, $restoredPosts, '還原的文章資料與原始資料不符');
        $this->assertEquals($originalAttachments, $restoredAttachments, '還原的附件資料與原始資料不符');
    }

    protected function tearDown(): void
    {
        // 清理測試檔案
        if (is_dir($this->backupDir)) {
            $files = glob($this->backupDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->backupDir);
        }

        parent::tearDown();
    }
}
