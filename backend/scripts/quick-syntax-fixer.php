#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 修復語法錯誤的腳本
 *
 * 修復由自動化腳本造成的文檔註解語法錯誤
 */

class SyntaxErrorFixer
{
    private string $baseDir;
    private array $fixedFiles = [];
    private int $totalFixes = 0;

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 開始修復語法錯誤...\n\n";

        // 獲取有語法錯誤的檔案
        $errorFiles = $this->getSyntaxErrorFiles();

        foreach ($errorFiles as $file) {
            $this->fixFile($file);
        }

        $this->generateReport();
    }

    private function getSyntaxErrorFiles(): array
    {
        echo "🔍 掃描語法錯誤...\n";

        $command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
        $output = shell_exec($command);

        if (!$output) {
            echo "無法執行 PHPStan\n";
            return [];
        }

        $files = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('/^\s*Line\s+(.+\.php)/', $line, $matches)) {
                $file = trim($matches[1]);
                if (!in_array($file, $files)) {
                    $files[] = $file;
                }
            }
        }

        echo "發現 " . count($files) . " 個有語法錯誤的檔案\n\n";
        return $files;
    }

    private function fixFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            echo "⚠️  檔案不存在: $file\n";
            return;
        }

        echo "🔧 修復檔案: $file\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 修復常見的語法錯誤

        // 1. 修復重複的 @param 行
        $content = preg_replace('/(\s*\*\s*@param\s+[^*\n]+)\n\s*\*\s*@param\s+[^*\n]+(?=\n)/m', '$1', $content);

        // 2. 修復孤立的 @param 行（沒有正確的文檔塊結構）
        $content = preg_replace('/^(\s*)\*\s*@param\s+([^*\n]+)(?=\n\s*(?:public|private|protected|\}))/m', '$1/**\n$1 * @param $2\n$1 */', $content);

        // 3. 修復文檔塊後面緊跟參數註解的問題
        $content = preg_replace('/(\s*\*\/)\s*\n\s*\*\s*@param\s+([^*\n]+)(?=\n)/m', '$1', $content);

        // 4. 修復空的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n\s*\*\/\s*\n\s*\*\s*@param/m', '$1/**\n$1 * @param', $content);

        // 5. 修復缺少結束的文檔塊
        $content = preg_replace('/(\s*)\*\s*@param\s+([^*\n]+)\s*\n(\s*)(public|private|protected)/m', '$1/**\n$1 * @param $2\n$1 */\n$3$4', $content);

        // 6. 修復格式錯誤的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n\s*\*\s*\n\s*\*\s*@param/m', '$1/**\n$1 * @param', $content);

        // 7. 移除多餘的星號
        $content = preg_replace('/^(\s*)\*\s*\*\s*(@param|@return)/m', '$1 * $2', $content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $file;
            $this->totalFixes++;
            echo "  ✅ 已修復\n";
        } else {
            echo "  ℹ️  無需修復\n";
        }

        echo "\n";
    }

    private function generateReport(): void
    {
        echo "\n📋 修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復檔案數: {$this->totalFixes}\n\n";

        if (!empty($this->fixedFiles)) {
            echo "修復的檔案:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  • $file\n";
            }
        }

        echo "\n✅ 語法錯誤修復完成！\n";
        echo "💡 建議重新執行 PHPStan 確認\n";
    }
}

// 執行修復
$fixer = new SyntaxErrorFixer();
$fixer->run();
