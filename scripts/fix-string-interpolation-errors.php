<?php

declare(strict_types=1);

echo "🔧 修復字串插值語法錯誤...\n";

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

    // 修復字串插值中的複雜表達式
    // 將 "{(complex_expression) ? value : null}" 轉換為簡化語法
    $patterns = [
        '/\{\(is_array\(\$([a-zA-Z_][a-zA-Z0-9_]*)\) && isset\(\$\1\[\'([^\']+)\'\]\)\) \? \$\1\[\'([^\']+)\'\] : null\}/' => '" . ($1[$2] ?? "Unknown") . "',
        '/\{isset\(\$([a-zA-Z_][a-zA-Z0-9_]*)\[\'([^\']+)\'\]\) \? \$\1\[\'([^\']+)\'\] : ([^}]+)\}/' => '" . ($1[$2] ?? $4) . "',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content && $newContent !== null) {
            $content = $newContent;
            $fixCount++;
        }
    }

    // 簡化的修復：手動處理常見模式
    if (strpos($content, '{(is_array(') !== false) {
        // 替換剩餘的複雜字串插值模式
        $content = preg_replace_callback(
            '/\{[^}]+\}/',
            function ($matches) {
                $match = $matches[0];
                // 如果包含複雜的 is_array 檢查，簡化它
                if (strpos($match, 'is_array(') !== false && strpos($match, '?') !== false) {
                    return '" . "Unknown" . "';
                }
                return $match;
            },
            $content
        );
        $fixCount++;
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
    '/var/www/html/tests/manual',
];

foreach ($directories as $directory) {
    if (is_dir($directory)) {
        scanDirectory($directory);
    }
}

echo "\n✅ 字串插值語法錯誤修復完成！\n";
echo "📊 處理了 {$processedFiles} 個檔案，修正了 {$fixedIssues} 個問題\n";
