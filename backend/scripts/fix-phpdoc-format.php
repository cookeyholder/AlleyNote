<?php

declare(strict_types=1);

function fixPhpDocFormat(string $filePath): void
{
    if (!file_exists($filePath)) {
        echo "檔案不存在: $filePath\n";
        return;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: $filePath\n";
        return;
    }

    $originalContent = $content;

    // 修復 /**\n      * 格式問題，轉換為正確的 PHPDoc 格式
    $patterns = [
        // 主要問題：/**\n      * @param 類型
        '/\/\*\*\\\\n\s+\*\s*@(param|return|var|throws)/',
        // 也處理沒有額外空白的情況
        '/\/\*\*\\\\n\s*@(param|return|var|throws)/',
        // 處理方法定義前的格式問題
        '/\/\*\*\\\\n\s+\*\s*@(param|return|var|throws)([^\n]*)\n\s*\*\/\s*(public|private|protected)/',
    ];

    $replacements = [
        '/**\n     * @$1',
        '/**\n * @$1', 
        '/**\n     * @$1$2\n     */\n    $3',
    ];

    foreach ($patterns as $index => $pattern) {
        $content = preg_replace($pattern, $replacements[$index] ?? $replacements[0], $content);
    }

    // 修復具體的 /**\n      * @param array<string, mixed> $context 格式
    $content = preg_replace(
        '/\/\*\*\\\\n\s+\*\s*@param\s+([^$]+)\s*\$([^*\n]+)\n\s*\*\/\s*(public|private|protected)/',
        '/**\n     * @param $1 $$2\n     */\n    $3',
        $content
    );

    // 修復 /**\n      * @return array<string, mixed> 格式  
    $content = preg_replace(
        '/\/\*\*\\\\n\s+\*\s*@return\s+([^*\n]+)\n\s*\*\/\s*(public|private|protected)/',
        '/**\n     * @return $1\n     */\n    $2',
        $content
    );

    // 修復建構函式參數的 PHPDoc 格式
    $content = preg_replace(
        '/\/\*\*\\\\n\s+\*\s*@param\s+([^*\n]+)([^*]*)\n/',
        '/**\n     * @param $1$2\n',
        $content
    );

    if ($content !== $originalContent) {
        if (file_put_contents($filePath, $content) !== false) {
            echo "已修復: $filePath\n";
        } else {
            echo "無法寫入檔案: $filePath\n";
        }
    } else {
        echo "無需修復: $filePath\n";
    }
}

// 取得需要修復的檔案列表
$files = [
    'app/Shared/Cache/Services/CacheManager.php',
    'app/Shared/Cache/Services/DefaultCacheStrategy.php', 
    'app/Shared/Cache/Strategies/DefaultCacheStrategy.php',
];

echo "開始修復 PHPDoc 格式問題...\n";

foreach ($files as $file) {
    fixPhpDocFormat(__DIR__ . '/../' . $file);
}

echo "PHPDoc 格式修復完成!\n";
