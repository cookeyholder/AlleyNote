<?php

declare(strict_types=1);

/**
 * 批量語法錯誤修復腳本 - 專門處理常見的語法錯誤模式.
 */
echo "=== 語法錯誤批量修復工具 ===\n\n";

$targetFiles = [
    'app/Shared/Cache/Drivers/FileCacheDriver.php',
    'app/Shared/Cache/Drivers/RedisCacheDriver.php',
    'app/Shared/Cache/Providers/CacheServiceProvider.php',
    'app/Shared/Cache/Services/CacheManager.php',
    'app/Shared/Cache/Strategies/DefaultCacheStrategy.php',
];

foreach ($targetFiles as $file) {
    echo "處理檔案：{$file}\n";

    if (!file_exists($file)) {
        echo "❌ 檔案不存在：{$file}\n";
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        echo "❌ 無法讀取檔案：{$file}\n";
        continue;
    }

    $originalContent = $content;

    // 修復常見的語法錯誤模式
    $fixes = [
        // 修復重複的等號
        '/\$[^=]+=\s*=\s*([^=])/' => '$1',

        // 修復多餘的箭頭符號
        '/=>\s*=>\s*/' => '=> ',

        // 修復重複的等號模式
        '/==\s*=\s*=\s*=/' => '===',

        // 修復重複的註解開始
        '/\/\*\*\s*\/\*\*/' => '/**',

        // 修復重複的註解結束
        '/\*\/\s*\*\//' => '*/',

        // 修復 try 後缺少 catch 的註解
        '/\/\/\s*catch block commented out due to syntax error/' => '',

        // 修復多餘的大括號
        '/{\s*{/' => '{',
        '/}\s*}/' => '}',
    ];

    $fixesApplied = 0;
    foreach ($fixes as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixesApplied++;
        }
    }

    // 特殊處理：修復重複的註解結構
    $content = preg_replace('/\/\*\*\s*\/\*\*\s*(.+?)\s*\*\/\s*\*\//', '/**\n$1\n*/', $content);

    // 特殊處理：修復建構子中的錯誤邏輯
    $content = preg_replace('/\$[a-zA-Z_]+\s*\?\s*true\s*:/', '$0 ?: ', $content);

    if ($content !== $originalContent) {
        if (file_put_contents($file, $content) !== false) {
            echo "✅ 已修復 {$fixesApplied} 個模式錯誤\n";
        } else {
            echo "❌ 無法寫入檔案：{$file}\n";
        }
    } else {
        echo "ℹ️  未發現需要修復的模式錯誤\n";
    }

    echo "\n";
}

echo "✅ 批量修復完成！\n";
