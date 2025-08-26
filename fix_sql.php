<?php

// 修正 TokenBlacklistRepository 中的 SQL NOW() 函式為 SQLite 相容的語法
$file = '/var/www/html/app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php';
$content = file_get_contents($file);

// 替換 NOW() 為 datetime('now')，但避免已經在字串內的情況
$patterns = [
    // 基本 NOW() 替換
    "/NOW\(\)/i" => "datetime('now')",
    // 處理 DATE_SUB 的 MySQL 語法
    '/DATE_SUB\(NOW\(\), INTERVAL :days DAY\)/' => "datetime('now', '-' || :days || ' days')",
    '/DATE_SUB\(datetime\(\'now\'\), INTERVAL :days DAY\)/' => "datetime('now', '-' || :days || ' days')",
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// 寫回檔案
file_put_contents($file, $content);

echo "TokenBlacklistRepository SQL 語法已修正為 SQLite 相容\n";
