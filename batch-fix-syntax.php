<?php
/**
 * 批次修復所有 PHP 檔案中的語法錯誤
 */

// 掃描所有 PHP 檔案
$directories = [
    '/var/www/html/app',
];

$allFiles = [];

foreach ($directories as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $allFiles[] = $file->getPathname();
        }
    }
}

$fixedFiles = 0;
$totalIssues = 0;

echo "開始批次修復語法錯誤...\n";
echo "總計檔案數: " . count($allFiles) . "\n\n";

foreach ($allFiles as $filePath) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileIssues = 0;

    // 修復模式 1: 註解掉的複雜 isset 語句
    $pattern = '/\/\/ if \(!?isset\([^)]*\? [^)]*: [^)]*\)\) \{ \/\/ isset[^\n]*\n/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        $fileIssues++;
    }

    // 修復模式 2: 孤立的 catch 語句 - 移除簡單的 exception throw
    $pattern = '/\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/[^\}]*\s*throw\s+\$e;\s*\}/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        $fileIssues++;
    }

    // 修復模式 3: 修復破損的 if 語句結構
    $lines = explode("\n", $content);
    $newLines = [];
    $skipUntil = -1;

    for ($i = 0; $i < count($lines); $i++) {
        if ($i <= $skipUntil) {
            continue; // 跳過已標記的行
        }

        $line = trim($lines[$i]);

        // 檢查註解掉的 if 語句
        if (preg_match('/^\/\/ if \(!?isset/', $line)) {
            // 尋找對應的結束大括號
            $braceCount = 0;
            $found = false;

            for ($j = $i + 1; $j < count($lines); $j++) {
                $testLine = trim($lines[$j]);

                if (preg_match('/^\}$/', $testLine) && $braceCount == 0) {
                    $skipUntil = $j;
                    $found = true;
                    $fileIssues++;
                    break;
                }

                $braceCount += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
            }

            if ($found) {
                continue;
            }
        }

        // 修復複雜的 isset 表達式
        if (preg_match('/isset\([^)]*\? [^)]*: [^)]*\)/', $lines[$i])) {
            $originalLine = $lines[$i];
            $lines[$i] = preg_replace('/isset\(\(is_array\([^)]+\)\s*\?\s*[^:]+:\s*[^)]+\)\)/', 'true', $lines[$i]);
            if ($lines[$i] !== $originalLine) {
                $fileIssues++;
            }
        }

        $newLines[] = $lines[$i];
    }

    $content = implode("\n", $newLines);

    // 儲存修復後的檔案
    if ($content !== $originalContent && $fileIssues > 0) {
        file_put_contents($filePath, $content);
        $fixedFiles++;
        $totalIssues += $fileIssues;
        echo "修復: " . str_replace('/var/www/html/', '', $filePath) . " ($fileIssues 個問題)\n";
    }
}

echo "\n批次修復完成！\n";
echo "修復的檔案: $fixedFiles\n";
echo "修復的問題: $totalIssues\n";
