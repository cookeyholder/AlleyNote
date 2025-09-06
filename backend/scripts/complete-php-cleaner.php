#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 完整 PHP 檔案清理器
 *
 * 清理所有可能的語法問題和特殊字符
 */

class CompletePHPCleaner
{
    private string $baseDir;

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🧹 執行完整 PHP 檔案清理...\n\n";

        $files = $this->getAllPHPFiles();
        $cleaned = 0;

        foreach ($files as $file) {
            if ($this->cleanFile($file)) {
                $cleaned++;
                echo "✅ 清理: " . str_replace($this->baseDir . '/', '', $file) . "\n";
            }
        }

        echo "\n完成！清理了 $cleaned 個檔案\n";
    }

    private function getAllPHPFiles(): array
    {
        $files = [];

        // app 目錄
        $this->scanDirectory($this->baseDir . '/app', $files);
        // tests 目錄
        $this->scanDirectory($this->baseDir . '/tests', $files);

        return $files;
    }

    private function scanDirectory(string $dir, array &$files): void
    {
        if (!is_dir($dir)) return;

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

    private function cleanFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 清理各種問題
        $content = $this->cleanEscapeSequences($content);
        $content = $this->cleanWhitespace($content);
        $content = $this->cleanDocBlocks($content);
        $content = $this->validateSyntax($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            return true;
        }

        return false;
    }

    private function cleanEscapeSequences(string $content): string
    {
        // 移除所有錯誤的轉義序列
        $content = preg_replace('/\\\\n(\s*)([\*\/])/', "\n$1$2", $content);
        $content = str_replace('\n', "\n", $content);

        return $content;
    }

    private function cleanWhitespace(string $content): string
    {
        // 標準化換行符
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 移除行尾空格
        $content = preg_replace('/[ \t]+$/m', '', $content);

        // 確保檔案以換行符結尾
        if (!empty($content) && substr($content, -1) !== "\n") {
            $content .= "\n";
        }

        return $content;
    }

    private function cleanDocBlocks(string $content): string
    {
        // 修復重複的文檔塊標記
        $content = preg_replace('/\/\*\*\s*\n\s*\/\*\*/', '/**', $content);
        $content = preg_replace('/\*\/\s*\n\s*\*\//', '*/', $content);

        // 修復格式錯誤的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n(\s*)\*\s*@/', '$1/**\n$2 * @', $content);

        return $content;
    }

    private function validateSyntax(string $content): string
    {
        // 基本語法驗證和修復
        $lines = explode("\n", $content);
        $fixedLines = [];
        $inDocBlock = false;

        foreach ($lines as $line) {
            // 跟蹤文檔塊狀態
            if (strpos($line, '/**') !== false) {
                $inDocBlock = true;
            } elseif (strpos($line, '*/') !== false) {
                $inDocBlock = false;
            }

            // 修復文檔塊中的問題
            if ($inDocBlock && preg_match('/^\s*\*\s*@(param|return)\s+/', $line)) {
                // 確保格式正確
                $line = preg_replace('/^\s*\*\s*@/', '     * @', $line);
            }

            $fixedLines[] = $line;
        }

        return implode("\n", $fixedLines);
    }
}

// 執行清理
$cleaner = new CompletePHPCleaner();
$cleaner->run();
