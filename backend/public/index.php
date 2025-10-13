<?php

declare(strict_types=1);

// 設置 API 安全標頭
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 移除伺服器資訊洩漏
header_remove('X-Powered-By');

// 載入 Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// 載入環境變數
require __DIR__ . '/../bootstrap/load_env.php';

use App\Application;
use App\Infrastructure\Http\ServerRequestFactory;

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 設定時區
date_default_timezone_set('Asia/Taipei');

// 取得請求資訊
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 移除查詢參數
$path = parse_url($requestUri, PHP_URL_PATH);

// 使用新的路由系統處理所有請求
try {
    $application = new Application();
    $request = ServerRequestFactory::fromGlobals();
    $response = $application->run($request);

    // 輸出回應
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    echo $response->getBody();
    exit;
} catch (Exception $e) {
    // 錯誤處理
    error_log("路由系統錯誤: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'timestamp' => (new DateTime())->format('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
