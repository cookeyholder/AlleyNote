<?php

/**
 * 保守的 PHPStan 錯誤修復腳本
 */

error_reporting(E_ALL & ~E_WARNING);

// 還原到安全狀態
$filePath = './app/Shared/Helpers/functions.php';
$content = file_get_contents($filePath);

// 修復錯誤的陣列存取替換
$content = preg_replace(
    '/\(is_array\((\$\w+)\) \? \$\w+\[[\'"](\w+)[\'"]\] : \(is_object\(\$\w+\) \? \$\w+->\w+ : null\)\)\s*=/',
    '$1[\'$2\'] =',
    $content
);

file_put_contents($filePath, $content);

echo "修復了 functions.php 中的語法錯誤\n";

// 現在進行更保守的修復
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.'),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fixedFiles = 0;
$totalFixes = 0;

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();

    // 跳過不需要處理的目錄
    if (strpos($filePath, './vendor/') === 0 ||
        strpos($filePath, './node_modules/') === 0 ||
        strpos($filePath, './storage/') === 0) {
        continue;
    }

    $content = file_get_contents($filePath);
    if ($content === false) continue;

    $originalContent = $content;

    // 只修復最安全的類型註解

    // 1. 修復方法參數中的 array 類型（不是函式簽名）
    $content = preg_replace(
        '/(@param\s+)array(\s+\$\w+)/',
        '$1array<mixed>$2',
        $content
    );

    // 2. 修復方法返回類型註解
    $content = preg_replace(
        '/(@return\s+)array(\s|$)/',
        '$1array<mixed>$2',
        $content
    );

    // 3. 修復類屬性註解
    $content = preg_replace(
        '/(@var\s+)array(\s|$)/',
        '$1array<mixed>$2',
        $content
    );

    // 4. 修復無效的 PHPDoc
    $content = preg_replace(
        '/@return\s+array>\s*/',
        '@return array<mixed> ',
        $content
    );

    $content = preg_replace(
        '/@param\s+array>\s*/',
        '@param array<mixed> ',
        $content
    );

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $fixedFiles++;
        $totalFixes++;
    }
}

echo "保守修復完成！\n";
echo "修復的檔案數：$fixedFiles\n";
