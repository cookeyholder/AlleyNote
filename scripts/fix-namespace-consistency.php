#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 命名空間統一腳本
 * 
 * 將所有 AlleyNote\ 命名空間統一改為 App\ 命名空間
 */

echo "🔧 開始修復命名空間不一致問題...\n";

// 查找所有 PHP 檔案
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../app'),
    RecursiveIteratorIterator::SELF_FIRST
);

$processedFiles = 0;
$changedFiles = 0;

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 替換 namespace 宣告
        $content = preg_replace('/^namespace AlleyNote\\\\/m', 'namespace App\\', $content, -1, $namespaceCount);

        // 替換 use 語句
        $content = preg_replace('/^use AlleyNote\\\\/m', 'use App\\', $content, -1, $useCount);

        // 替換在程式碼中的完整類別名稱參考
        $content = preg_replace('/AlleyNote\\\\([A-Za-z0-9\\\\]+)::/', 'App\\\\$1::', $content, -1, $staticCount);

        // 替換字串中的命名空間參考（如 DI 配置）
        $content = preg_replace('/"AlleyNote\\\\([^"]+)"/', '"App\\\\$1"', $content, -1, $stringCount);
        $content = preg_replace("/'AlleyNote\\\\([^']+)'/", "'App\\\\$1'", $content, -1, $singleStringCount);

        $totalChanges = $namespaceCount + $useCount + $staticCount + $stringCount + $singleStringCount;

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $changedFiles++;
            echo "✅ 修復: " . str_replace(__DIR__ . '/../', '', $filePath) . " (變更: $totalChanges)\n";
        }

        $processedFiles++;
    }
}

echo "\n📊 統計結果:\n";
echo "- 處理檔案數: $processedFiles\n";
echo "- 修改檔案數: $changedFiles\n";

// 也檢查測試檔案
echo "\n🧪 檢查測試檔案...\n";

$testFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../tests'),
    RecursiveIteratorIterator::SELF_FIRST
);

$testProcessed = 0;
$testChanged = 0;

foreach ($testFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 替換 use 語句
        $content = preg_replace('/^use AlleyNote\\\\/m', 'use App\\', $content, -1, $useCount);

        // 替換在程式碼中的完整類別名稱參考
        $content = preg_replace('/AlleyNote\\\\([A-Za-z0-9\\\\]+)::/', 'App\\\\$1::', $content, -1, $staticCount);

        // 替換字串中的命名空間參考
        $content = preg_replace('/"AlleyNote\\\\([^"]+)"/', '"App\\\\$1"', $content, -1, $stringCount);
        $content = preg_replace("/'AlleyNote\\\\([^']+)'/", "'App\\\\$1'", $content, -1, $singleStringCount);

        $totalChanges = $useCount + $staticCount + $stringCount + $singleStringCount;

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $testChanged++;
            echo "✅ 修復測試: " . str_replace(__DIR__ . '/../', '', $filePath) . " (變更: $totalChanges)\n";
        }

        $testProcessed++;
    }
}

echo "\n📊 測試檔案統計:\n";
echo "- 處理測試檔案數: $testProcessed\n";
echo "- 修改測試檔案數: $testChanged\n";

echo "\n✅ 命名空間統一完成！\n";
echo "💡 建議接下來執行: composer dump-autoload\n";
