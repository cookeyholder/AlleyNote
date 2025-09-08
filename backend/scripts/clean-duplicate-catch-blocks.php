<?php

declare(strict_types=1);

/**
 * 清理重複 catch 塊的修復腳本
 *
 * 這個腳本專門用來清理程式碼中的重複 catch 塊，
 * 特別是由於之前修復過程中產生的多重重複結構。
 */

echo "🔧 清理重複 catch 塊...\n";

$projectRoot = __DIR__ . '/..';
$totalFiles = 0;
$totalFixes = 0;

/**
 * 遞迴掃描目錄獲取所有 PHP 檔案
 */
function scanPhpFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * 清理檔案中的重複 catch 塊
 */
function cleanDuplicateCatchBlocks(string $filePath): int
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return 0;
    }

    $originalContent = $content;
    $fixes = 0;

    // 模式1: 清理連續的重複 catch 塊 (最常見的模式)
    $pattern1 = '/(\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*)+/';
    $content = preg_replace($pattern1, '', $content, -1, $count1);
    $fixes += $count1;

    // 模式2: 清理單獨的重複 catch 塊
    $pattern2 = '/\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*}/';
    $content = preg_replace($pattern2, '', $content, -1, $count2);
    $fixes += $count2;

    // 模式3: 清理不完整的 try-catch 結構末尾
    $pattern3 = '/\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*}\s*}/';
    $content = preg_replace($pattern3, '}', $content, -1, $count3);
    $fixes += $count3;

    // 模式4: 清理錯誤的 catch 塊位置 (在其他語句後面)
    $pattern4 = '/(\s*return\s+[^;]+;\s*)\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*}/';
    $content = preg_replace($pattern4, '$1', $content, -1, $count4);
    $fixes += $count4;

    // 模式5: 清理多層嵌套的重複 catch
    $pattern5 = '/(\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*}\s*)+/';
    $content = preg_replace($pattern5, '', $content, -1, $count5);
    $fixes += $count5;

    // 如果有修改，寫回檔案
    if ($content !== $originalContent && $fixes > 0) {
        file_put_contents($filePath, $content);
        $fileName = basename($filePath);
        echo "  修復 {$fixes} 個重複 catch 塊在 {$fileName}\n";
        echo "修復檔案: {$filePath}\n";
    }

    return $fixes;
}

// 掃描所有 PHP 檔案
$directories = [
    $projectRoot . '/app',
    $projectRoot . '/tests',
];

$allFiles = [];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $allFiles = array_merge($allFiles, scanPhpFiles($dir));
    }
}

$totalFiles = count($allFiles);

// 處理每個檔案
foreach ($allFiles as $file) {
    $fixes = cleanDuplicateCatchBlocks($file);
    if ($fixes > 0) {
        $totalFixes += $fixes;
    }
}

echo "\n📊 重複 catch 塊清理摘要:\n";
echo "==================================================\n";
echo "掃描的檔案數: {$totalFiles}\n";
echo "總修復數量: {$totalFixes}\n";

if ($totalFixes > 0) {
    echo "\n💡 修復完成後建議:\n";
    echo "  1. 執行 PHPStan 檢查修復效果\n";
    echo "  2. 運行測試確保功能正常\n";
    echo "  3. 檢查是否還有其他語法錯誤\n";
    echo "  4. 提交修復變更\n";

    echo "\n📈 預期改善:\n";
    echo "  - 消除 'unexpected T_CATCH' 錯誤\n";
    echo "  - 恢復正常的 try-catch 結構\n";
    echo "  - 改善程式碼可讀性和維護性\n";
    echo "  - 減少語法錯誤總數\n";
}

echo "\n✅ 重複 catch 塊清理完成！\n";
