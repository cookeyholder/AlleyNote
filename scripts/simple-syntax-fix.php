<?php

declare(strict_types=1);

/**
 * 簡單的語法錯誤修復工具
 * 專門修復重複的 { 符號問題
 */

$projectRoot = __DIR__ . '/..';
$testDir = $projectRoot . '/tests';

// 遞迴搜尋所有 PHP 檔案
function findAllPhpFiles($dir)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

$files = findAllPhpFiles($testDir);
$fixedFiles = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;

    // 修復模式: class 定義後的重複 {
    $pattern = '/^(class\s+\w+(?:Test|TestCase)\s+extends\s+.*TestCase.*\n\{\n\s*use MockeryPHPUnitIntegration;\s*\n)\n\{/m';
    $content = preg_replace($pattern, '$1', $content);

    // 如果內容有改變，寫回檔案
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedFiles[] = basename($file);
        echo "✅ 修復: " . basename($file) . "\n";
    }
}

if (empty($fixedFiles)) {
    echo "🎉 沒有發現需要修復的檔案\n";
} else {
    echo "\n🔧 修復完成！總計修復 " . count($fixedFiles) . " 個檔案\n";
    echo "修復的檔案: " . implode(', ', $fixedFiles) . "\n";
}
