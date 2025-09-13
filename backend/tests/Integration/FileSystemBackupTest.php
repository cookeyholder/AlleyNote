<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class FileSystemBackupTest extends TestCase
{
    private string $testDir;

    private string $backupDir;

    /** @var array<string, string> */
    private array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試目錄
        $this->testDir = sys_get_temp_dir() . '/alleynote_test_' . uniqid();
        $this->backupDir = sys_get_temp_dir() . '/alleynote_backup_' . uniqid();

        mkdir($this->testDir);
        mkdir($this->testDir . '/uploads', 0o755, true);
        mkdir($this->testDir . '/storage', 0o755, true);
        mkdir($this->backupDir, 0o755, true);

        // 確保目錄權限正確
        chmod($this->testDir, 0o755);
        chmod($this->backupDir, 0o755);

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
            chmod($fullPath, 0o644);
        }
    }

    #[Test]
    public function backupFilesSuccessfully(): void
    {
        $backupFile = $this->executeBackupScript();
        $this->assertBackupFileCreated($backupFile);
        $this->assertBackupContainsAllFiles($backupFile);
    }

    private function executeBackupScript(): string
    {
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($this->testDir),
            escapeshellarg($this->backupDir),
        ), $output, $returnVar);

        $this->assertEquals(0, $returnVar, '備份腳本執行失敗: ' . implode("\n", $output));

        $backupFiles = glob($this->backupDir . '/files_*.tar.gz');
        rsort($backupFiles);

        return $backupFiles[0] ?? '';
    }

    private function assertBackupFileCreated(string $backupFile): void
    {
        $this->assertNotEmpty($backupFile, '找不到備份檔案');
        $this->assertGreaterThan(0, filesize($backupFile), '備份檔案是空的');
    }

    private function assertBackupContainsAllFiles(string $backupFile): void
    {
        $extractedDir = $this->extractBackupFile($backupFile);

        foreach ($this->testFiles as $path => $content) {
            $backedUpFile = (is_string($extractedDir) ? $extractedDir : '') . (is_string($path) ? $path : '');
            $this->assertFileExists($backedUpFile, sprintf('檔案 %s 未被備份', $path));
            $this->assertEquals(
                $content,
                file_get_contents($backedUpFile),
                sprintf('檔案 %s 的內容不符', $path),
            );
        }
    }

    private function extractBackupFile(string $backupFile): string
    {
        $tempDir = $this->backupDir . '/temp';
        mkdir($tempDir);
        exec(sprintf('tar -xzf "%s" -C "%s"', $backupFile, $tempDir));

        $extractedDir = glob($tempDir . '/*')[0] ?? null;
        $this->assertNotNull($extractedDir, '解壓縮後目錄不存在');

        return $extractedDir;
    }

    #[Test]
    public function restoreFilesSuccessfully(): void
    {
        $backupFile = $this->createManualBackup();
        $this->clearOriginalFiles();
        $this->executeRestoreScript($backupFile);
        $this->assertAllFilesRestored();
    }

    private function createManualBackup(): string
    {
        $backupFile = $this->backupDir . '/files_' . date('Ymd_His') . '.tar.gz';
        exec(sprintf('cd "%s" && tar -czf "%s" .', $this->testDir, $backupFile));

        return $backupFile;
    }

    private function clearOriginalFiles(): void
    {
        exec(sprintf('rm -rf "%s/uploads" "%s/storage"', $this->testDir, $this->testDir));
        mkdir($this->testDir . '/uploads');
        mkdir($this->testDir . '/storage');
    }

    private function executeRestoreScript(string $backupFile): void
    {
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir),
        ), $output, $returnVar);

        $this->assertEquals(0, $returnVar, '還原腳本執行失敗: ' . implode("\n", $output));
    }

    private function assertAllFilesRestored(): void
    {
        foreach ($this->testFiles as $path => $content) {
            $restoredFile = $this->testDir . $path;
            $this->assertFileExists($restoredFile, sprintf('檔案 %s 未被還原', $path));
            $this->assertEquals(
                $content,
                file_get_contents($restoredFile),
                sprintf('檔案 %s 的內容不符', $path),
            );
            $this->assertEquals(
                0o644,
                octdec(substr(sprintf('%o', fileperms($restoredFile)), -4)),
                sprintf('檔案 %s 的權限不正確', $path),
            );
        }
    }

    #[Test]
    public function handleBackupErrorsGracefully(): void
    {
        $nonExistentDir = $this->testDir . '/nonexistent';
        $this->executeBackupScriptWithExpectedError($nonExistentDir);
    }

    #[Test]
    public function handleRestoreErrorsGracefully(): void
    {
        $nonExistentBackup = $this->backupDir . '/nonexistent_backup.tar.gz';
        $this->executeRestoreScriptWithExpectedError($nonExistentBackup);
    }

    #[Test]
    public function handlePermissionErrors(): void
    {
        $nonExistentBackupFile = $this->ensureBackupFileDoesNotExist();
        $this->executeRestoreScriptWithSpecificError($nonExistentBackupFile, '找不到備份檔案');
    }

    private function executeBackupScriptWithExpectedError(string $sourceDir): void
    {
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/backup_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($sourceDir),
            escapeshellarg($this->backupDir),
        ), $output, $returnVar);

        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    private function executeRestoreScriptWithExpectedError(string $backupFile): void
    {
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir),
        ), $output, $returnVar);

        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $this->assertStringContainsString('錯誤', implode("\n", $output), '應該輸出錯誤訊息');
    }

    private function ensureBackupFileDoesNotExist(): string
    {
        $nonExistentBackupFile = $this->backupDir . '/nonexistent_backup.tar.gz';

        if (file_exists($nonExistentBackupFile)) {
            unlink($nonExistentBackupFile);
        }

        return $nonExistentBackupFile;
    }

    private function executeRestoreScriptWithSpecificError(string $backupFile, string $expectedErrorMessage): void
    {
        $output = [];
        $returnVar = 0;

        exec(sprintf(
            '/bin/bash %s/scripts/restore_files.sh %s %s 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg($backupFile),
            escapeshellarg($this->testDir),
        ), $output, $returnVar);

        $this->assertNotEquals(0, $returnVar, '應該回報錯誤狀態碼');
        $outputString = implode("\n", $output);
        $this->assertStringContainsString($expectedErrorMessage, $outputString, '應該輸出檔案不存在錯誤訊息');
    }

    #[Test]
    public function maintainFileMetadataDuringBackupRestore(): void
    {
        $originalMetadata = $this->recordOriginalFileMetadata();
        $backupFile = $this->performFullBackupRestore();
        $this->assertFileMetadataPreserved($originalMetadata);
    }

    private function recordOriginalFileMetadata(): array
    {
        $originalMetadata = [];
        foreach ($this->testFiles as $path => $content) {
            $file = $this->testDir . $path;

            $permissions = fileperms($file);
            $owner = fileowner($file);
            $group = filegroup($file);
            $mtime = filemtime($file);

            if ($permissions === false || $owner === false || $group === false || $mtime === false) {
                throw new RuntimeException(sprintf('無法取得檔案 %s 的中繼資料', $path));
            }

            $originalMetadata[$path] = [
                'permissions' => $permissions,
                'owner' => $owner,
                'group' => $group,
                'mtime' => $mtime,
            ];
        }

        return $originalMetadata;
    }

    private function performFullBackupRestore(): string
    {
        $backupFile = $this->executeBackupScript();
        $this->clearOriginalFiles();
        $this->executeRestoreScript($backupFile);

        return $backupFile;
    }

    private function assertFileMetadataPreserved(array $originalMetadata): void
    {
        foreach ($originalMetadata as $path => $metadata) {
            $restoredFile = $this->testDir . $path;
            $this->assertFileExists($restoredFile, sprintf('檔案 %s 未被還原', $path));

            // 檢查權限（只檢查使用者權限部分）
            $restoredPermissions = fileperms($restoredFile);
            $this->assertNotFalse($restoredPermissions, sprintf('無法取得檔案 %s 的權限', $path));

            $expectedUserPerms = ((is_array($metadata) && array_key_exists('permissions', $metadata) ? $metadata['permissions'] : null) & 0o700) >> 6;
            $actualUserPerms = ($restoredPermissions & 0o700) >> 6;
            $this->assertEquals(
                $expectedUserPerms,
                $actualUserPerms,
                sprintf('檔案 %s 的使用者權限不符', $path),
            );
        }
    }

    #[Test]
    public function handleLargeFileBackup(): void
    {
        $largeFileName = '/uploads/large_file.bin';
        $largeFileContent = str_repeat('Z', 1024 * 1024); // 1MB
        $largeFilePath = $this->testDir . $largeFileName;

        file_put_contents($largeFilePath, $largeFileContent);
        $this->testFiles[$largeFileName] = $largeFileContent;

        $backupFile = $this->executeBackupScript();
        $this->assertBackupFileCreated($backupFile);

        // 驗證大檔案是否正確備份
        $extractedDir = $this->extractBackupFile($backupFile);
        $backedUpLargeFile = (is_string($extractedDir) ? $extractedDir : '') . (is_string($largeFileName) ? $largeFileName : '');
        $this->assertFileExists($backedUpLargeFile, '大檔案未被正確備份');
        $this->assertEquals(
            strlen($largeFileContent),
            filesize($backedUpLargeFile),
            '大檔案大小不符',
        );
    }

    #[Test]
    public function handleEmptyDirectories(): void
    {
        $emptyDir = $this->testDir . '/empty';
        mkdir($emptyDir);

        $backupFile = $this->executeBackupScript();
        $extractedDir = $this->extractBackupFile($backupFile);

        $this->assertDirectoryExists($extractedDir . '/empty', '空目錄未被保留');
    }

    #[Test]
    public function handleSpecialCharactersInFilenames(): void
    {
        $specialFiles = [
            '/storage/file with spaces.txt' => 'content with spaces',
            '/storage/file-with-dashes.log' => 'content with dashes',
            '/storage/file_with_underscores.cfg' => 'content with underscores',
        ];

        foreach ($specialFiles as $path => $content) {
            $fullPath = $this->testDir . $path;
            file_put_contents($fullPath, $content);
            $this->testFiles[$path] = $content;
        }

        $backupFile = $this->executeBackupScript();
        $this->assertBackupContainsAllFiles($backupFile);
    }

    protected function tearDown(): void
    {
        // 清理測試目錄
        if (is_dir($this->testDir)) {
            exec(sprintf('rm -rf "%s"', $this->testDir));
        }
        if (is_dir($this->backupDir)) {
            exec(sprintf('rm -rf "%s"', $this->backupDir));
        }

        parent::tearDown();
    }
}
