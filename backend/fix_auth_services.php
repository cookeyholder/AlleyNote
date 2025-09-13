<?php

declare(strict_types=1);

/**
 * 修復 Auth Services 中的 try-catch 語法錯誤
 */

function fixTryCatchSyntax(string $filePath): bool
{
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Cannot read file: $filePath\n";
        return false;
    }

    // 修復模式 1: try { ... } // catch block commented out due to syntax error
    $pattern1 = '/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}(.*?)\}\s*\/\/\s*catch\s+block\s+commented\s+out\s+due\s+to\s+syntax\s+error/s';
    $replacement1 = 'try {$1} catch (Throwable $e) {
            error_log("Error in ' . basename($filePath) . ': " . $e->getMessage());
            throw $e;
        }';

    $content = preg_replace($pattern1, $replacement1, $content);

    // 修復模式 2: } // catch block commented out due to syntax error catch (Throwable $e) {
    $pattern2 = '/\}\s*\/\/\s*catch\s+block\s+commented\s+out\s+due\s+to\s+syntax\s+error\s+catch\s*\(\s*Throwable\s+\$e\s*\)\s*\{/';
    $replacement2 = '} catch (Throwable $e) {';

    $content = preg_replace($pattern2, $replacement2, $content);

    // 修復模式 3: 移除重複的陣列關閉括號
    $pattern3 = '/\]\)\],$/m';
    $replacement3 = ']);';

    $content = preg_replace($pattern3, $replacement3, $content);

    // 修復模式 4: == == 雙等號錯誤
    $pattern4 = '/\s+==\s+==/';
    $replacement4 = ' ===';

    $content = preg_replace($pattern4, $replacement4, $content);

    // 修復模式 5: 移除空的 try 塊
    $pattern5 = '/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/';
    $replacement5 = 'try {';

    $content = preg_replace($pattern5, $replacement5, $content);

    // 寫回文件
    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        echo "Cannot write file: $filePath\n";
        return false;
    }

    echo "Fixed: $filePath\n";
    return true;
}

// 要修復的文件列表
$authFiles = [
    '/var/www/html/app/Domains/Auth/Services/JwtTokenService.php',
    '/var/www/html/app/Domains/Auth/Services/PasswordSecurityService.php',
    '/var/www/html/app/Domains/Auth/Services/RefreshTokenService.php',
    '/var/www/html/app/Domains/Auth/Services/TokenBlacklistService.php',
];

echo "開始修復 Auth Services 語法錯誤...\n\n";

foreach ($authFiles as $file) {
    if (fixTryCatchSyntax($file)) {
        // 檢查語法
        $output = [];
        $returnVar = 0;
        exec("php -l $file 2>&1", $output, $returnVar);

        if ($returnVar === 0) {
            echo "✅ Syntax OK: " . basename($file) . "\n";
        } else {
            echo "❌ Syntax Error: " . basename($file) . "\n";
            echo implode("\n", $output) . "\n";
        }
    }
    echo "\n";
}

echo "Auth Services 修復完成！\n";
