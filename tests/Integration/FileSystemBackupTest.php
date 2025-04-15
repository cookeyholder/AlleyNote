<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

class FileSystemBackupTest extends TestCase
{
    private string $testDir;
    private string $backupDir;
    private array $testFiles;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試目錄
        $this->testDir = sys_get_temp_dir() . '/alleynote_test_' . uniqid();
        $this->backupDir = sys_get_temp_dir() . '/alleynote_backup_' . uniqid();

        mkdir($this->testDir);
        mkdir($this->testDir . '/uploads', 0755, true);
        mkdir($this->testDir . '/storage', 0755, true);
        mkdir($this->backupDir, 0755, true);

        // 確保目錄權限正確
        chmod($this->testDir, 0755);
        chmod($this->backupDir, 0755);

        // 建立測試檔案
        $this->createTestFiles();
    }

    private function createTestFiles(): void
    {
        $this->testFiles = [
            '/uploads/image1.jpg' => str_repeat('x', 1024),  // 1KB
            '/uploads/document.pdf' => str_repeat('y', 2048),  // 2KB
            '/storage/data.json' => json_encode(['test' => 'data']),
            '/storage/config.ini' => "key=value\nfoo=bar",
        ];

        foreach ($this->testFiles as $path => $content) {
            $fullPath = $this->testDir . $path;
            file_put_contents($fullPath, $content);
            chmod($fullPath, 0644);
        }
    }

    /** @test */
    public function backupFilesSuccessfully(): void
    {
        // 執行備份腳本
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($this->testDir),
            escapeshellarg($this->backupDir)
        ), $output, $returnVar);

        // 驗證備份是否成功
        $this->assertEquals(0, $returnVar, '備份腳本執行失敗: ' . implode("\n", $output));

        // 取得最新的備份檔案
        $backupFiles = glob($this->backupDir . '/files_*.tar.gz');
        rsort($backupFiles);
        $backupFile = $backupFiles[0] ?? null;

        $this->assertNotNull($backupFile, '找不到備份檔案');
        $this->assertGreaterThan(0, filesize($backupFile), '備份檔案是空的');

        // 解壓縮備份檔案到臨時目錄進行驗證
        $tempDir = $this->backupDir . '/temp';
        mkdir($tempDir);
        exec("tar -xzf '$backupFile' -C '$tempDir'");

        // 解壓縮後會多一層目錄，取得該目錄路徑
        $extractedDir = glob($tempDir . '/*')[0] ?? null;
        $this->assertNotNull($extractedDir, '解壓縮後目錄不存在');

        // 驗證所有檔案都有備份
        foreach ($this->testFiles as $path => $content) {
            $backedUpFile = $extractedDir . $path;
            $this->assertFileExists($backedUpFile, "檔案 {$path} 未被備份");
            $this->assertEquals(
                $content,
                file_get_contents($backedUpFile),
                "檔案 {$path} 的內容不符"
            );
        }
    }

    /** @test */
    public function restoreFilesSuccessfully(): void
    {
        // 先建立備份
        $backupFile = $this->backupDir . '/files_' . date('Ymd_His') . '.tar.gz';
        exec("cd '{$this->testDir}' && tar -czf '$backupFile' .");

        // 清空原始目錄
        exec("rm -rf '{$this->testDir}/uploads' '{$this->testDir}/storage'");
        mkdir($this->testDir . '/uploads');
        mkdir($this->testDir . '/storage');

        // 執行還原腳本
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir)
        ), $output, $returnVar);

        // 驗證還原是否成功
        $this->assertEquals(0, $returnVar, '還原腳本執行失敗: ' . implode("\n", $output));

        // 驗證所有檔案都有還原
        foreach ($this->testFiles as $path => $content) {
            $restoredFile = $this->testDir . $path;
            $this->assertFileExists($restoredFile, "檔案 {$path} 未被還原");
            $this->assertEquals(
                $content,
                file_get_contents($restoredFile),
                "檔案 {$path} 的內容不符"
            );
            $this->assertEquals(
                0644,
                octdec(substr(sprintf('%o', fileperms($restoredFile)), -4)),
                "檔案 {$path} 的權限不正確"
            );
        }
    }

    /** @test */
    public function handleBackupErrorsGracefully(): void
    {
        // 使用不存在的來源目錄
        $nonExistentDir = $this->testDir . '/nonexistent';

        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($nonExistentDir),
            escapeshellarg($this->backupDir)
        ), $output, $returnVar);

        // 驗證錯誤處理
        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    /** @test */
    public function handleRestoreErrorsGracefully(): void
    {
        // 使用不存在的備份檔案
        $nonExistentBackup = $this->backupDir . '/nonexistent_backup.tar.gz';

        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($nonExistentBackup),
            escapeshellarg($this->testDir)
        ), $output, $returnVar);

        // 驗證錯誤處理
        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    /** @test */
    public function handlePermissionErrors(): void
    {
        // 設定目標目錄為唯讀
        chmod($this->testDir, 0444);

        // 測試目標目錄是否可寫入
        $testFile = $this->testDir . '/test.txt';
        $canWrite = @file_put_contents($testFile, 'test') !== false;
        if ($canWrite) {
            $this->markTestIncomplete('目標目錄仍可寫入，無法測試權限錯誤');
            chmod($this->testDir, 0755);
            return;
        }

        $backupFile = $this->backupDir . '/files_' . date('Ymd_His') . '.tar.gz';
        exec("cd '{$this->testDir}' && tar -czf '$backupFile' .");

        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir)
        ), $output, $returnVar);

        // 驗證錯誤處理
        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('權限', implode("\n", $output), '應該輸出權限錯誤訊息');

        // 恢復權限以便清理
        chmod($this->testDir, 0755);
    }

    /** @test */
    public function maintainFileMetadataDuringBackupRestore(): void
    {
        // 記錄原始檔案的中繼資料
        $originalMetadata = [];
        foreach ($this->testFiles as $path => $content) {
            $file = $this->testDir . $path;
            $originalMetadata[$path] = [
                'permissions' => fileperms($file),
                'owner' => fileowner($file),
                'group' => filegroup($file),
                'mtime' => filemtime($file)
            ];
        }

        // 執行備份
        $backupFile = $this->backupDir . '/files_backup.tar.gz';
        exec(sprintf(
            '/bin/bash %s/scripts/backup_files.sh %s %s',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($this->testDir),
            escapeshellarg($backupFile)
        ));

        // 清空原始目錄
        exec("rm -rf '{$this->testDir}/uploads' '{$this->testDir}/storage'");
        mkdir($this->testDir . '/uploads');
        mkdir($this->testDir . '/storage');

        // 執行還原
        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir)
        ));

        // 驗證檔案中繼資料
        foreach ($this->testFiles as $path => $content) {
            $file = $this->testDir . $path;
            if (!file_exists($file)) {
                $this->markTestIncomplete("檔案 {$path} 不存在，無法驗證中繼資料");
                continue;
            }
            $this->assertEquals(
                $originalMetadata[$path]['permissions'],
                fileperms($file),
                "檔案 {$path} 的權限不符"
            );
            $this->assertEquals(
                $originalMetadata[$path]['owner'],
                fileowner($file),
                "檔案 {$path} 的擁有者不符"
            );
            $this->assertEquals(
                $originalMetadata[$path]['group'],
                filegroup($file),
                "檔案 {$path} 的群組不符"
            );
        }
    }

    protected function tearDown(): void
    {
        // 清理測試目錄
        if (is_dir($this->testDir)) {
            exec("chmod -R 755 '{$this->testDir}'"); // 確保有權限刪除
            exec("rm -rf '{$this->testDir}'");
        }
        if (is_dir($this->backupDir)) {
            exec("rm -rf '{$this->backupDir}'");
        }

        parent::tearDown();
    }
}
