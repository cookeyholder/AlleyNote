#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 最終語法錯誤清理腳本
 * 
 * 處理剩餘的文檔註解語法問題
 */

class FinalSyntaxCleaner
{
    private string $baseDir;
    private array $fixedFiles = [];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🧹 執行最終語法清理...\n\n";

        // 處理所有 PHP 檔案
        $files = $this->getAllPHPFiles();
        
        foreach ($files as $file) {
            $this->cleanFile($file);
        }

        echo "\n✅ 最終語法清理完成！\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
    }

    private function getAllPHPFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir . '/app', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        // 也包含測試檔案
        $testIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir . '/tests', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($testIterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function cleanFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復重複的文檔塊開始標記
        $content = preg_replace('/(\s*)\/\*\*\s*\n(\s*)\/\*\*\s*\n/m', '$1/**\n', $content);
        
        // 修復重複的文檔塊結束標記
        $content = preg_replace('/(\s*)\*\/\s*\n(\s*)\*\/\s*\n/m', '$1 */\n', $content);
        
        // 修復孤立的 */ 在註解中間
        $content = preg_replace('/(\s*)\*\/\s*\n(\s*)\*\s*@(param|return)/m', '$2 * @$3', $content);
        
        // 修復空的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n(\s*)\*\/\s*\n/m', '', $content);
        
        // 修復格式錯誤的單行註解
        $content = preg_replace('/(\s*)\/\*\*\s*@(param|return)\s+([^\n]+)\s*\*\//m', '$1/**\n$1 * @$2 $3\n$1 */', $content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $relativePath = str_replace($this->baseDir . '/', '', $filePath);
            $this->fixedFiles[] = $relativePath;
            echo "✅ 清理: $relativePath\n";
        }
    }
}

// 執行清理
$cleaner = new FinalSyntaxCleaner();
$cleaner->run();
