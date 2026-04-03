<?php

/**
 * 環境變數載入腳本
 *
 * 使用 vlucas/phpdotenv 載入 .env 檔案
 * 替代自訂解析器以解決注入漏洞和執行緒安全問題
 */

declare(strict_types=1);

// 決定環境
$environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'development';
$basePath = dirname(__DIR__);

// 使用 vlucas/phpdotenv 載入對應的環境檔案
$envFile = ".env.{$environment}";
$defaultEnv = '.env';

if (file_exists($basePath . '/' . $envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable($basePath, $envFile);
} else {
    $dotenv = Dotenv\Dotenv::createImmutable($basePath, $defaultEnv);
}

// 安全載入：不覆蓋已由外部設定的環境變數（如 CI 環境）
$dotenv->safeLoad();
