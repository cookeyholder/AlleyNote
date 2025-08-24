<?php

/**
 * DDD 命名空間更新工具
 *
 * 這個腳本用於自動更新檔案中的命名空間和 use 語句，以符合新的 DDD 架構
 *
 * 使用方式：
 * php scripts/ddd-namespace-updater.php --mode=validate
 * php scripts/ddd-namespace-updater.php --mode=execute
 * php scripts/ddd-namespace-updater.php --mode=rollback
 */

class DDDNamespaceUpdater
{
    private array $namespaceMappings;
    private array $config;
    private array $processedFiles = [];
    private array $errors = [];
    private array $warnings = [];
    private string $logFile;
    private string $backupDir;

    public function __construct()
    {
        $this->config = require __DIR__ . '/namespace-mapping.php';
        $this->namespaceMappings = $this->config['namespace_mappings'];
        $this->logFile = $this->config['logging']['log_file'];
        $this->backupDir = $this->config['backup_settings']['backup_directory'];

        // 確保目錄存在
        $this->ensureDirectoriesExist();
    }

    public function run($mode = 'validate')
    {
        $this->log("開始執行 DDD 命名空間更新，模式: $mode");

        switch ($mode) {
            case 'validate':
                return $this->validateMode();
            case 'execute':
                return $this->executeMode();
            case 'rollback':
                return $this->rollbackMode();
            default:
                $this->error("未知的模式: $mode");
                return false;
        }
    }

    /**
     * 驗證模式 - 檢查所有檔案是否可以安全更新
     */
    private function validateMode(): bool
    {
        $this->log("執行驗證模式...");

        $phpFiles = $this->findAllPhpFiles();
        $errors = 0;

        foreach ($phpFiles as $file) {
            if ($this->isExcludedFile($file)) {
                continue;
            }

            try {
                $this->validateFile($file);
            } catch (Exception $e) {
                $this->error("驗證檔案 $file 時發生錯誤: " . $e->getMessage());
                $errors++;
            }
        }

        $this->log("驗證完成。發現 $errors 個錯誤。");
        $this->printSummary();

        return $errors === 0;
    }

    /**
     * 執行模式 - 實際更新所有檔案
     */
    private function executeMode(): bool
    {
        $this->log("執行更新模式...");

        // 先執行驗證
        if (!$this->validateMode()) {
            $this->error("驗證失敗，無法執行更新");
            return false;
        }

        // 創建備份
        $this->createBackup();

        $phpFiles = $this->findAllPhpFiles();
        $updated = 0;

        foreach ($phpFiles as $file) {
            if ($this->isExcludedFile($file)) {
                continue;
            }

            try {
                if ($this->updateFile($file)) {
                    $updated++;
                }
            } catch (Exception $e) {
                $this->error("更新檔案 $file 時發生錯誤: " . $e->getMessage());
            }
        }

        $this->log("更新完成。共更新 $updated 個檔案。");
        $this->printSummary();

        return true;
    }

    /**
     * 回滾模式 - 恢復備份
     */
    private function rollbackMode(): bool
    {
        $this->log("執行回滾模式...");

        $backupDirs = glob($this->backupDir . '/backup_*', GLOB_ONLYDIR);
        if (empty($backupDirs)) {
            $this->error("沒有找到可用的備份");
            return false;
        }

        // 使用最新的備份
        $latestBackup = array_pop($backupDirs);
        $this->log("使用備份: $latestBackup");

        // 恢復檔案
        $this->restoreFromBackup($latestBackup);

        $this->log("回滾完成");
        return true;
    }

    /**
     * 驗證單個檔案
     */
    private function validateFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("無法讀取檔案: $file");
        }

        // 檢查語法
        if (!$this->isValidPhpSyntax($content)) {
            throw new Exception("PHP 語法錯誤");
        }

        // 分析當前命名空間和 use 語句
        $analysis = $this->analyzeFile($content);

        // 檢查是否需要更新
        $updates = $this->calculateUpdates($analysis);

        if (!empty($updates)) {
            $this->log("檔案 $file 需要更新: " . implode(', ', array_keys($updates)));
        }
    }

    /**
     * 更新單個檔案
     */
    private function updateFile(string $file): bool
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("無法讀取檔案: $file");
        }

        $originalContent = $content;

        // 分析檔案
        $analysis = $this->analyzeFile($content);
        $updates = $this->calculateUpdates($analysis);

        if (empty($updates)) {
            return false; // 沒有需要更新的內容
        }

        // 應用更新
        $content = $this->applyUpdates($content, $updates);

        // 驗證更新後的內容
        if (!$this->isValidPhpSyntax($content)) {
            throw new Exception("更新後的 PHP 語法無效");
        }

        // 寫入檔案
        if (file_put_contents($file, $content) === false) {
            throw new Exception("無法寫入檔案: $file");
        }

        $this->processedFiles[] = [
            'file' => $file,
            'updates' => $updates,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->log("已更新檔案: $file");
        return true;
    }

    /**
     * 分析 PHP 檔案的命名空間和 use 語句
     */
    private function analyzeFile(string $content): array
    {
        $analysis = [
            'namespace' => null,
            'use_statements' => [],
            'class_names' => [],
            'interface_names' => [],
            'trait_names' => []
        ];

        // 提取命名空間
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
            $analysis['namespace'] = trim($matches[1]);
        }

        // 提取 use 語句
        preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
        foreach ($matches[1] as $use) {
            $use = trim($use);
            if (strpos($use, ' as ') !== false) {
                list($class, $alias) = explode(' as ', $use, 2);
                $analysis['use_statements'][trim($alias)] = trim($class);
            } else {
                $parts = explode('\\', $use);
                $className = end($parts);
                $analysis['use_statements'][$className] = $use;
            }
        }

        // 提取類別名稱
        preg_match_all('/^(?:abstract\s+)?class\s+(\w+)/m', $content, $matches);
        $analysis['class_names'] = $matches[1];

        // 提取介面名稱
        preg_match_all('/^interface\s+(\w+)/m', $content, $matches);
        $analysis['interface_names'] = $matches[1];

        // 提取 trait 名稱
        preg_match_all('/^trait\s+(\w+)/m', $content, $matches);
        $analysis['trait_names'] = $matches[1];

        return $analysis;
    }

    /**
     * 計算需要進行的更新
     */
    private function calculateUpdates(array $analysis): array
    {
        $updates = [];

        // 檢查命名空間是否需要更新
        if ($analysis['namespace']) {
            $oldNamespace = $analysis['namespace'];
            $newNamespace = $this->findNewNamespace($oldNamespace);

            if ($newNamespace && $newNamespace !== $oldNamespace) {
                $updates['namespace'] = [
                    'old' => $oldNamespace,
                    'new' => $newNamespace
                ];
            }
        }

        // 檢查 use 語句是否需要更新
        $useUpdates = [];
        foreach ($analysis['use_statements'] as $alias => $fullClass) {
            $newClass = $this->findNewNamespace($fullClass);
            if ($newClass && $newClass !== $fullClass) {
                $useUpdates[$alias] = [
                    'old' => $fullClass,
                    'new' => $newClass
                ];
            }
        }

        if (!empty($useUpdates)) {
            $updates['use_statements'] = $useUpdates;
        }

        return $updates;
    }

    /**
     * 應用更新到檔案內容
     */
    private function applyUpdates(string $content, array $updates): string
    {
        // 更新命名空間聲明
        if (isset($updates['namespace'])) {
            $old = $updates['namespace']['old'];
            $new = $updates['namespace']['new'];
            $content = preg_replace(
                '/^namespace\s+' . preg_quote($old, '/') . ';/m',
                "namespace $new;",
                $content
            );
        }

        // 更新 use 語句
        if (isset($updates['use_statements'])) {
            foreach ($updates['use_statements'] as $alias => $change) {
                $old = preg_quote($change['old'], '/');
                $new = $change['new'];

                // 處理有別名的 use 語句
                if ($alias !== basename(str_replace('\\', '/', $change['old']))) {
                    $content = preg_replace(
                        '/^use\s+' . $old . '\s+as\s+' . preg_quote($alias, '/') . ';/m',
                        "use $new as $alias;",
                        $content
                    );
                } else {
                    $content = preg_replace(
                        '/^use\s+' . $old . ';/m',
                        "use $new;",
                        $content
                    );
                }
            }
        }

        return $content;
    }

    /**
     * 尋找新的命名空間
     */
    private function findNewNamespace(string $oldNamespace): ?string
    {
        // 直接映射
        if (isset($this->namespaceMappings[$oldNamespace])) {
            return $this->namespaceMappings[$oldNamespace];
        }

        // 模糊匹配（處理部分匹配的情況）
        foreach ($this->namespaceMappings as $old => $new) {
            if (strpos($oldNamespace, $old) === 0) {
                return str_replace($old, $new, $oldNamespace);
            }
        }

        return null;
    }

    /**
     * 檢查 PHP 語法是否有效
     */
    private function isValidPhpSyntax(string $content): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
        file_put_contents($tempFile, $content);

        $output = [];
        $returnCode = 0;
        exec("php -l $tempFile 2>&1", $output, $returnCode);

        unlink($tempFile);

        return $returnCode === 0;
    }

    /**
     * 尋找所有 PHP 檔案
     */
    private function findAllPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('app', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    /**
     * 檢查檔案是否被排除
     */
    private function isExcludedFile(string $file): bool
    {
        $normalizedFile = str_replace('\\', '/', $file);
        foreach ($this->config['excluded_files'] as $excluded) {
            if (strpos($normalizedFile, $excluded) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 創建備份
     */
    private function createBackup(): void
    {
        $timestamp = date($this->config['backup_settings']['timestamp_format']);
        $backupPath = $this->backupDir . "/backup_$timestamp";

        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // 備份 app 目錄
        $this->copyDirectory('app', $backupPath . '/app');

        $this->log("已創建備份: $backupPath");
    }

    /**
     * 從備份恢復
     */
    private function restoreFromBackup(string $backupPath): void
    {
        if (!is_dir($backupPath . '/app')) {
            throw new Exception("備份目錄無效: $backupPath");
        }

        // 刪除當前 app 目錄
        $this->removeDirectory('app');

        // 恢復備份
        $this->copyDirectory($backupPath . '/app', 'app');

        $this->log("已從備份恢復: $backupPath");
    }

    /**
     * 複製目錄
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    /**
     * 刪除目錄
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * 確保必要的目錄存在
     */
    private function ensureDirectoriesExist(): void
    {
        $dirs = [
            dirname($this->logFile),
            $this->backupDir
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * 記錄日誌
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] INFO: $message" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }

    /**
     * 記錄錯誤
     */
    private function error(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] ERROR: $message" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;

        $this->errors[] = $message;
    }

    /**
     * 記錄警告
     */
    private function warning(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] WARNING: $message" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;

        $this->warnings[] = $message;
    }

    /**
     * 打印摘要
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "執行摘要\n";
        echo str_repeat('=', 50) . "\n";
        echo "處理的檔案數: " . count($this->processedFiles) . "\n";
        echo "錯誤數: " . count($this->errors) . "\n";
        echo "警告數: " . count($this->warnings) . "\n";

        if (!empty($this->errors)) {
            echo "\n錯誤:\n";
            foreach ($this->errors as $error) {
                echo "- $error\n";
            }
        }

        if (!empty($this->warnings)) {
            echo "\n警告:\n";
            foreach ($this->warnings as $warning) {
                echo "- $warning\n";
            }
        }

        echo "\n詳細日誌請查看: " . $this->logFile . "\n";
    }
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['mode:']);
    $mode = $options['mode'] ?? 'validate';

    $updater = new DDDNamespaceUpdater();
    $success = $updater->run($mode);

    exit($success ? 0 : 1);
}
