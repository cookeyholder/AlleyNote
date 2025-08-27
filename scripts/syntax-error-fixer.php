<?php

declare(strict_types=1);

/**
 * 語法錯誤修復工具
 * 修復 targeted-error-fixer 產生的 \n 字面文字問題
 */

$projectRoot = __DIR__ . '/..';

$testFiles = [
    $projectRoot . '/tests',
];

$fixedFiles = 0;
$totalFixes = 0;

foreach ($testFiles as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);

            if (!$content) continue;

            $originalContent = $content;

            // 修復 \n 字面文字問題
            $content = str_replace('/** @phpstan-ignore-next-line method.unused */\\n', '/** @phpstan-ignore-next-line method.unused */', $content);
            $content = str_replace('*/\\n', '*/', $content);
            $content = preg_replace('/\*\/\\n\s*\n/', "*/\n", $content);

            // 移除多餘的空行
            $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

            if ($content !== $originalContent) {
                file_put_contents($filePath, $content);
                $fixedFiles++;
                $fixes = substr_count($originalContent, '\\n') - substr_count($content, '\\n');
                $totalFixes += $fixes;
                echo "✅ 修復 " . basename($filePath) . " ($fixes 個語法錯誤)\n";
            }
        }
    }
}

echo "\n🎉 語法修復完成！\n";
echo "📁 修復了 $fixedFiles 個檔案\n";
echo "🔧 總共修復 $totalFixes 個語法錯誤\n";
