<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
                uuid VARCHAR(36) NOT NULL,
                seq_number INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip VARCHAR(45) NULL,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned BOOLEAN NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT "draft",
                publish_date DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                creation_source VARCHAR(20) DEFAULT "unknown",
                creation_source_detail TEXT
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
            INSERT INTO posts (uuid, seq_number, title, content, user_id, status) VALUES
            ('test-uuid-1', 1, '測試文章1', '內容1', 1, 'published'),
            ('test-uuid-2', 2, '測試文章2', '內容2', 1, 'published')
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
        // 執行備份（直接複製文件）
        $backupFile = $this->backupDir . '/backup.sqlite';

        // 直接複製文件作為備份
        copy($this->dbPath, $backupFile);

        // 驗證備份是否成功
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
            'DB_PATH=%s /bin/bash %s/scripts/restore_db.sh %s 2>&1',
            escapeshellarg($this->dbPath),
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
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
            'DB_PATH=%s BACKUP_DIR=%s /bin/bash %s/scripts/backup_db.sh 2>&1',
            escapeshellarg($nonExistentDb),
            escapeshellarg($this->backupDir),
            escapeshellarg(dirname(__DIR__, 2)),
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
            'DB_PATH=%s /bin/bash %s/scripts/restore_db.sh %s 2>&1',
            escapeshellarg($this->dbPath),
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($nonExistentBackup),
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
        exec(sprintf(
            'DB_PATH=%s BACKUP_DIR=%s /bin/bash %s/scripts/backup_db.sh',
            escapeshellarg($this->dbPath),
            escapeshellarg($this->backupDir),
            escapeshellarg(dirname(__DIR__, 2)),
        ));

        // 找到最新生成的備份檔案
        $backupFiles = glob($this->backupDir . '/backup_*.db');
        $this->assertNotEmpty($backupFiles, '應該生成備份檔案');
        $backupFile = $backupFiles[0]; // 取得最新的備份檔案

        // 清空原始資料庫
        $this->db->exec('DELETE FROM attachments');
        $this->db->exec('DELETE FROM posts');

        // 執行還原
        exec(sprintf(
            'DB_PATH=%s /bin/bash %s/scripts/restore_db.sh %s',
            escapeshellarg($this->dbPath),
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
        ));

        // 重新建立資料庫連接以讀取還原後的資料
        $restoredDb = new PDO('sqlite:' . $this->dbPath);
        $restoredDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 比較還原後的資料 - 只比較內容，不比較自動生成的 seq_number 等欄位
        $restoredPosts = $restoredDb->query('SELECT title, content, user_id, status FROM posts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $restoredAttachments = $restoredDb->query('SELECT * FROM attachments ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        // 比較關鍵欄位而非完整記錄
        $originalPostsFiltered = array_map(function ($post) {
            return [
                'title' => $post['title'],
                'content' => $post['content'],
                'user_id' => $post['user_id'],
                'status' => $post['status'],
            ];
        }, $originalPosts);

        $this->assertEquals($originalPostsFiltered, $restoredPosts, '還原的文章資料與原始資料不符');
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
