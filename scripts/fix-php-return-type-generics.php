<?php

declare(strict_types=1);

echo "🔧 修復 PHP 函式返回型別中的泛型語法...\n";

$processedFiles = 0;
$fixedIssues = 0;

function processFile(string $filePath): int {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return 0;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return 0;
    }

    $fixCount = 0;
    $originalContent = $content;

    // 修復函式返回型別中的泛型語法
    $patterns = [
        // array<mixed> -> array<mixed>
        '/:\s*array<mixed>]*>/i' => ': array<mixed>',
        // 其他泛型類型
        '/:\s*(?:list|iterable)<[^>]*>/i' => ': array<mixed>',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixCount++;
        }
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
    }

    return $fixCount;
}

function scanDirectory(string $directory): void {
    global $processedFiles, $fixedIssues;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();

            // 跳過 vendor 目錄
            if (strpos($filePath, '/vendor/') !== false) {
                continue;
            }

            $processedFiles++;
            $fixes = processFile($filePath);
            $fixedIssues += $fixes;

            if ($fixes > 0) {
                echo "  修復了 {$fixes} 個問題: " . basename($filePath) . "\n";
            }
        }
    }
}

// 掃描主要目錄
$directories = [
    '/var/www/html/app',
    '/var/www/html/config',
    '/var/www/html/tests',
];

foreach ($directories as $directory) {
    if (is_dir($directory)) {
        scanDirectory($directory);
    }
}

echo "\n✅ PHP 返回型別泛型語法修復完成！\n";
echo "📊 處理了 {$processedFiles} 個檔案，修正了 {$fixedIssues} 個問題\n";
