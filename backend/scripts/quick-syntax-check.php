<?php

declare(strict_types=1);

/**
 * 快速 PHP 語法檢查器
 *
 * 用於驗證所有 PHP 檔案的語法是否正確
 */

class QuickSyntaxChecker
{
    private int $totalFiles = 0;
    private int $syntaxErrors = 0;
    private array $errorFiles = [];

    public function checkSyntax(): void
    {
        echo "🔍 開始快速語法檢查...\n\n";

        $phpFiles = $this->findPhpFiles();

        foreach ($phpFiles as $file) {
            $this->checkFile($file);
        }

        $this->printSummary();
    }

    private function findPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../app')
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    private function checkFile(string $filePath): void
    {
        $this->totalFiles++;

        // 使用 php -l 進行語法檢查
        $command = "php -l " . escapeshellarg($filePath) . " 2>&1";
        $output = shell_exec($command);

        if (strpos($output, 'No syntax errors detected') === false) {
            $this->syntaxErrors++;
            $this->errorFiles[] = [
                'file' => $filePath,
                'error' => trim($output)
            ];

            echo "❌ 語法錯誤: " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
            echo "   " . trim($output) . "\n\n";
        } else {
            echo "✅ " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
        }
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📋 語法檢查報告\n";
        echo str_repeat("=", 50) . "\n";
        echo "總檔案數: {$this->totalFiles}\n";
        echo "語法錯誤: {$this->syntaxErrors}\n";
        echo "成功率: " . round((($this->totalFiles - $this->syntaxErrors) / $this->totalFiles) * 100, 2) . "%\n";

        if ($this->syntaxErrors > 0) {
            echo "\n❌ 發現語法錯誤的檔案:\n";
            foreach ($this->errorFiles as $error) {
                echo "  • " . str_replace(__DIR__ . '/../', '', $error['file']) . "\n";
                echo "    " . $error['error'] . "\n\n";
            }
        } else {
            echo "\n🎉 所有檔案語法檢查通過！\n";
        }
    }
}

// 執行語法檢查
$checker = new QuickSyntaxChecker();
$checker->checkSyntax();
