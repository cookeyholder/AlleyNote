<?php

declare(strict_types=1);

/**
 * 修復 TokenBlacklistService.php 的語法錯誤
 */

function fixTokenBlacklistService(): bool
{
    $filePath = '/var/www/html/app/Domains/Auth/Services/TokenBlacklistService.php';

    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Cannot read file: $filePath\n";
        return false;
    }

    // 1. 修復常數引用錯誤：=> 變成 ::
    $content = preg_replace('/TokenBlacklistEntry\s*=>\s*([A-Z_]+)/', 'TokenBlacklistEntry::$1', $content);

    // 2. 修復常數引用錯誤：=> : 變成 ::
    $content = preg_replace('/TokenBlacklistEntry\s*=>\s*:([A-Z_]+)/', 'TokenBlacklistEntry::$1', $content);

    // 3. 修復 self => 變成 self::
    $content = preg_replace('/self\s*=>\s*([A-Z_]+)/', 'self::$1', $content);

    // 4. 修復 self => : 變成 self::
    $content = preg_replace('/self\s*=>\s*:([A-Z_]+)/', 'self::$1', $content);

    // 5. 修復多餘的右括號
    $content = preg_replace('/\]\),\s*\]\)/', '])', $content);

    // 6. 修復 array 尾隨逗號後的多餘括號
    $content = preg_replace('/,\s*\]\),/', ',', $content);

    // 7. 修復 try {
    $content = preg_replace('/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/', 'try {', $content);

    // 8. 修復未完成的 catch 塊
    $content = preg_replace('/\}\s*\/\/\s*catch\s+block\s+commented\s+out\s+due\s+to\s+syntax\s+error/', '} catch (Throwable $e) {
            error_log("Error in TokenBlacklistService: " . $e->getMessage());
            throw $e;
        }', $content);

    // 9. 確保所有開放的 try 都有對應的 catch
    $content = preg_replace('/(\s+)try\s*\{\s*\n(.*?)\n(\s+)return/', '$1try {
$2
$3return', $content);

    // 10. 修復特定的語法問題
    $fixes = [
        // 修復數組中的錯誤
        "'requested_count' => count(\$validJtis]]," => "'requested_count' => count(\$validJtis),",
        "=> new DateTimeImmutable()]]," => "=> new DateTimeImmutable(),",
        "\$this->repository->isSizeExceeded()]]," => "\$this->repository->isSizeExceeded(),",

        // 修復常數錯誤
        "TokenBlacklistEntry => TOKEN_TYPE_ACCESS," => "TokenBlacklistEntry::TOKEN_TYPE_ACCESS,",
        "TokenBlacklistEntry => :TOKEN_TYPE_REFRESH," => "TokenBlacklistEntry::TOKEN_TYPE_REFRESH,",
        "TokenBlacklistEntry => REASON_LOGOUT," => "TokenBlacklistEntry::REASON_LOGOUT,",
        "TokenBlacklistEntry => :REASON_REVOKED," => "TokenBlacklistEntry::REASON_REVOKED,",

        // 修復 self 常數錯誤
        "self => DEFAULT_MAX_BLACKLIST_SIZE," => "self::DEFAULT_MAX_BLACKLIST_SIZE,",
    ];

    foreach ($fixes as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    // 寫回文件
    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        echo "Cannot write file: $filePath\n";
        return false;
    }

    echo "Fixed TokenBlacklistService.php\n";
    return true;
}

// 執行修復
echo "開始修復 TokenBlacklistService.php...\n\n";

if (fixTokenBlacklistService()) {
    // 檢查語法
    $output = [];
    $returnVar = 0;
    exec('php -l /var/www/html/app/Domains/Auth/Services/TokenBlacklistService.php 2>&1', $output, $returnVar);

    if ($returnVar === 0) {
        echo "✅ Syntax OK: TokenBlacklistService.php\n";
    } else {
        echo "❌ Syntax Error: TokenBlacklistService.php\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "❌ Failed to fix TokenBlacklistService.php\n";
}

echo "\n修復完成！\n";
