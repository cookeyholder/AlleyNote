<?php

declare(strict_types=1);

/**
 * PHPStan 修復腳本：處理 json_encode 回傳型別問題
 * 
 * json_encode() 可能回傳 false，需要處理這種情況
 */

$filesToFix = [
    '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/ActivityLogController.php',
];

echo "🔧 開始修復 json_encode 回傳型別問題...\n";

foreach ($filesToFix as $filepath) {
    if (!file_exists($filepath)) {
        echo "⚠️  檔案不存在: {$filepath}\n";
        continue;
    }

    echo "📝 處理檔案: " . basename($filepath) . "\n";

    $content = file_get_contents($filepath);
    $originalContent = $content;

    // 修復模式 1：$response->getBody()->write($json ?: '{}');
    $pattern1 = '/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*json_encode\([^)]+\))([^;]*);[\s]*(\$[a-zA-Z_][a-zA-Z0-9_]*->getBody\(\)->write\(\$[a-zA-Z_][a-zA-Z0-9_]*\s*\?\:\s*[\'"][^\'",]*[\'"][^)]*\))/';
    $replacement1 = '$1$2;$3';
    $content = preg_replace($pattern1, $replacement1, $content);

    // 修復模式 2: 直接在 write 中使用 json_encode
    $pattern2 = '/\$[a-zA-Z_][a-zA-Z0-9_]*->getBody\(\)->write\((json_encode\([^)]+\))\s*\?\:\s*([\'"][^\'",]*[\'"])\)/';
    $replacement2 = '$response->getBody()->write($1 ?: $2)';
    $content = preg_replace($pattern2, $replacement2, $content);

    // 更具體的修復：查找 json_encode 並確保錯誤處理
    $lines = explode("\n", $content);
    $modifiedLines = [];
    $inMethod = false;

    foreach ($lines as $lineNumber => $line) {
        // 檢查是否包含 json_encode 並且有 ?: 模式
        if (strpos($line, 'json_encode') !== false && strpos($line, '?:') !== false) {
            // 確保有適當的錯誤處理
            if (strpos($line, "?: '{\"error\"") === false && strpos($line, "?: '{}'") === false) {
                // 添加適當的錯誤處理
                $line = str_replace(" ?: ''", " ?: '{\"error\": \"JSON encoding failed\"}'", $line);
                $line = str_replace(" ?: \"\"", " ?: '{\"error\": \"JSON encoding failed\"}'", $line);
            }
        }

        $modifiedLines[] = $line;
    }

    $content = implode("\n", $modifiedLines);

    if ($content !== $originalContent) {
        // 備份原檔案
        $backupFile = $filepath . '.backup.' . date('Y-m-d_H-i-s');
        copy($filepath, $backupFile);
        echo "💾 備份檔案: " . basename($backupFile) . "\n";

        // 寫入修復後的內容
        file_put_contents($filepath, $content);
        echo "✅ 修復完成\n";
    } else {
        echo "ℹ️  無需修復\n";
    }

    echo "\n";
}

echo "🎉 修復腳本執行完成！\n";

// 檢查語法
echo "\n🔍 檢查語法...\n";
foreach ($filesToFix as $filepath) {
    if (file_exists($filepath)) {
        $output = [];
        $returnVar = 0;
        exec("php -l {$filepath} 2>&1", $output, $returnVar);

        if ($returnVar === 0) {
            echo "✅ " . basename($filepath) . " 語法正確\n";
        } else {
            echo "❌ " . basename($filepath) . " 語法錯誤:\n";
            echo implode("\n", $output) . "\n";
        }
    }
}
