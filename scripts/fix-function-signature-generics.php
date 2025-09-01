<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

/**
 * 修復函數簽名中錯誤的泛型類型標註
 * PHP 不支援在函數簽名中使用 array<mixed> 這種語法
 * 這些只能在 docblock 中使用
 */

$baseDir = __DIR__ . '/..';
$directories = ['app', 'tests'];

echo "開始修復函數簽名中的泛型類型標註錯誤...\n";

$totalFiles = 0;
$totalFixes = 0;

foreach ($directories as $dir) {
    $fullDir = $baseDir . '/' . $dir;
    if (!is_dir($fullDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        if ($content === false) {
            continue;
        }

        $originalContent = $content;
        $fixesInFile = 0;

        // 修復各種函數簽名中的泛型類型標註
        $patterns = [
            // array<mixed> -> array<mixed>
            '/\): array<mixed>/' => '): array<mixed>',
            '/\): array<mixed>/' => '): array<mixed>',
            '/\): array<mixed>/' => '): array<mixed>',
            '/\): array<mixed>/' => '): array<mixed>',
            // 同時處理參數類型
            '/\(([^)]*array<mixed>)<string, mixed>/' => '($1',
            '/\(([^)]*array<mixed>)<int, mixed>/' => '($1',
            '/\(([^)]*array<mixed>)<string, string>/' => '($1',
            '/\(([^)]*array<mixed>)<int, string>/' => '($1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $matches = preg_match_all($pattern, $content);
                if ($matches > 0) {
                    $fixesInFile += $matches;
                }
                $content = $newContent;
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $totalFiles++;
            $totalFixes += $fixesInFile;

            $relativePath = str_replace($baseDir . '/', '', $filePath);
            echo "處理檔案: {$relativePath}\n";
            echo "  修復了 {$fixesInFile} 個函數簽名泛型錯誤\n";
        }
    }
}

echo "\n修復完成！\n";
echo "總修復次數: {$totalFixes}\n";
echo "修復的檔案數: {$totalFiles}\n";
