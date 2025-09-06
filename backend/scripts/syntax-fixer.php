#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 語法錯誤修復器
 *
 * 修復自動化工具引入的語法錯誤
 */

class SyntaxErrorFixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private int $totalFixes = 0;

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 啟動語法錯誤修復器...\n\n";

        try {
            // 1. 獲取所有 PHP 檔案
            $files = $this->getAllPHPFiles();
            echo "📁 掃描 " . count($files) . " 個 PHP 檔案\n\n";

            // 2. 修復每個檔案的語法錯誤
            foreach ($files as $file) {
                $this->fixFile($file);
            }

            // 3. 生成報告
            $this->generateReport();

        } catch (Exception $e) {
            echo "❌ 修復過程中發生錯誤: {$e->getMessage()}\n";
        }
    }

    private function getAllPHPFiles(): array
    {
        $files = [];

        // 掃描主要目錄
        $directories = [
            $this->baseDir . '/app',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir, $files);
            }
        }

        return $files;
    }

    private function scanDirectory(string $dir, array &$files): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    private function fixFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $relativePath = str_replace($this->baseDir . '/', '', $filePath);

        // 跳過 vendor 目錄
        if (strpos($relativePath, 'vendor/') === 0) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 應用語法修復
        $content = $this->fixSyntaxErrors($content, $fixes);

        if ($fixes > 0) {
            file_put_contents($filePath, $content);
            $this->appliedFixes[$relativePath] = $fixes;
            $this->totalFixes += $fixes;
            echo "✅ 修復: $relativePath ($fixes 個修復)\n";
        }
    }

    private function fixSyntaxErrors(string $content, int &$fixes): string
    {
        // 修復常見的語法錯誤
        $patterns = [
            // 修復錯誤的變數替換
            '/is_numeric\(\$queryParam\) \? \(int\) \$queryParam : 0s\[\'(\w+)\'\]/' => 'is_numeric($queryParams[\'\1\']) ? (int) $queryParams[\'\1\'] : 0',

            // 修復陣列存取語法錯誤
            '/\$queryParam\s*:\s*0s\[\'(\w+)\'\]/' => '$queryParams[\'\1\']',

            // 修復複雜的型別檢查表達式
            '/is_numeric\(\$(\w+)\[\'(\w+)\'\]\) \? is_numeric\(\$(\w+)\) \? \(int\) \$(\w+) : 0s\[\'(\w+)\'\] : (\d+)/' => 'is_numeric($\1[\'\2\']) ? (int) $\1[\'\2\'] : \6',

            // 修復語法錯誤的條件表達式
            '/\? \(int\) \$(\w+) : 0s\[\'(\w+)\'\]/' => '? (int) $\1[\'\2\'] : 0',

            // 修復不完整的陣列語法
            '/0s\[\'(\w+)\'\]/' => '0',

            // 修復錯誤的變數引用
            '/\$queryParam\b/' => '$queryParams',

            // 修復錯誤的三元運算子
            '/\?\s*:\s*(\w+)/' => '? null : \1',

            // 修復空陣列元素
            '/,\s*,/' => ',',
            '/\[\s*,/' => '[',
            '/,\s*\]/' => ']',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        return $content;
    }

    private function generateReport(): void
    {
        echo "\n📋 語法錯誤修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";
        echo "修復檔案數: " . count($this->appliedFixes) . "\n\n";

        if (!empty($this->appliedFixes)) {
            echo "修復詳情:\n";
            foreach ($this->appliedFixes as $file => $fixes) {
                echo "  • $file: $fixes 個修復\n";
            }
        }

        echo "\n✅ 語法錯誤修復完成！\n";
        echo "💡 建議執行 PHP 語法檢查確認修復效果\n";
    }
}

// 執行語法修復
$fixer = new SyntaxErrorFixer();
$fixer->run();
