<?php

declare(strict_types=1);

/**
 * 修復 TokenBlacklistEntry.php 的語法錯誤
 */

function fixTokenBlacklistEntry(): bool
{
    $filePath = '/var/www/html/app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php';

    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Cannot read file: $filePath\n";
        return false;
    }

    // 1. 修復重複的 PHPDoc 註釋
    $content = preg_replace('/\/\*\*\s*\/\*\*\s*\n\s*\*\s*@param[^*]*\*\/\s*\*\//', '/**
     * @param array $data
     */', $content);

    // 2. 修復 unexpected '*' 錯誤 - 移除多餘的星號
    $content = preg_replace('/\s+\*\s*\*\s*\n/', "\n", $content);

    // 3. 修復 unexpected ':' 錯誤 - 修復數組語法
    $content = preg_replace('/\[\s*:\s*([A-Z_]+)/', '[$1', $content);
    $content = preg_replace('/,\s*:\s*([A-Z_]+)/', ', $1', $content);

    // 4. 修復 Cannot use empty array elements - 移除空數組元素
    $content = preg_replace('/,\s*,/', ',', $content);
    $content = preg_replace('/\[\s*,/', '[', $content);
    $content = preg_replace('/,\s*\]/', ']', $content);

    // 5. 修復 unexpected T_DOUBLE_ARROW 錯誤
    $content = preg_replace('/=>\s*=>\s*/', '=>', $content);
    $content = preg_replace('/=>\s*:\s*/', '=>', $content);

    // 6. 修復常數引用錯誤
    $patterns = [
        '/self\s*:\s*([A-Z_]+)/' => 'self::$1',
        '/self\s*=>\s*([A-Z_]+)/' => 'self::$1',
        '/self\s*=>\s*:([A-Z_]+)/' => 'self::$1',
        '/TokenBlacklistEntry\s*:\s*([A-Z_]+)/' => 'TokenBlacklistEntry::$1',
        '/TokenBlacklistEntry\s*=>\s*([A-Z_]+)/' => 'TokenBlacklistEntry::$1',
        '/TokenBlacklistEntry\s*=>\s*:([A-Z_]+)/' => 'TokenBlacklistEntry::$1',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    // 7. 修復特定的語法問題
    $fixes = [
        // 修復多餘的星號
        '/**
     * *
     */' => '/**
     */',

        // 修復數組中的冒號
        "[':" => "['",
        "',:" => "',",
        "=> :" => "=>",

        // 修復重複的箭頭
        "=> =>" => "=>",

        // 修復括號問題
        "))," => "),",
        "));" => ");",
        ")):" => "):",

        // 修復常數問題
        "self : " => "self::",
        "self =>" => "self::",
        ": REASON_" => "::REASON_",
        ": TOKEN_TYPE_" => "::TOKEN_TYPE_",
    ];

    foreach ($fixes as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    // 8. 清理多餘的空行和格式
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);

    // 寫回文件
    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        echo "Cannot write file: $filePath\n";
        return false;
    }

    echo "Fixed TokenBlacklistEntry.php\n";
    return true;
}

// 執行修復
echo "開始修復 TokenBlacklistEntry.php...\n\n";

if (fixTokenBlacklistEntry()) {
    // 檢查語法
    $output = [];
    $returnVar = 0;
    exec('php -l /var/www/html/app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php 2>&1', $output, $returnVar);

    if ($returnVar === 0) {
        echo "✅ Syntax OK: TokenBlacklistEntry.php\n";
    } else {
        echo "❌ Syntax Error: TokenBlacklistEntry.php\n";
        echo implode("\n", $output) . "\n";

        // 如果還有錯誤，顯示前幾個錯誤行
        echo "\n檢查第89行附近:\n";
        $lines = file('/var/www/html/app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php');
        for ($i = 85; $i < 95 && $i < count($lines); $i++) {
            echo sprintf("%3d: %s", $i + 1, $lines[$i]);
        }
    }
} else {
    echo "❌ Failed to fix TokenBlacklistEntry.php\n";
}

echo "\n修復完成！\n";
