<?php

/**
 * 環境變數載入腳本
 * 
 * 在應用程式啟動前載入 .env 檔案
 */

declare(strict_types=1);

// 決定環境
$environment = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
$envFile = dirname(__DIR__) . "/.env.{$environment}";

if (!file_exists($envFile)) {
    $envFile = dirname(__DIR__) . '/.env';
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // 跳過註解和空行
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // 解析 KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            
            // 設定環境變數（覆寫模式以確保載入）
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
