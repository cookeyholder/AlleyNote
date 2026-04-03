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

$appEnv = getenv('APP_ENV') ?: 'production';
$displayErrors = filter_var($appEnv !== 'production', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $displayErrors ? '1' : '0');

// 設定時區
date_default_timezone_set('Asia/Taipei');

// 取得請求資訊
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 依環境與設定決定 CORS（供前端靜態伺服器與 E2E 使用）
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$corsAllowedOriginsEnv = getenv('CORS_ALLOWED_ORIGINS') ?: '';

if ($corsAllowedOriginsEnv !== '') {
    $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $corsAllowedOriginsEnv))));
} elseif ($appEnv !== 'production') {
    $allowedOrigins = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ];
} else {
    $allowedOrigins = [];
}

if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

    if ($requestMethod === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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
} catch (Throwable $e) {
    // 錯誤處理
    app_log('critical', '路由系統錯誤', ['exception' => $e->getMessage()]);
    header('Content-Type: application/json');
    http_response_code(500);
    $appEnv = getenv('APP_ENV') ?: 'production';
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $appEnv !== 'production' ? $e->getMessage() : '伺服器內部錯誤，請稍後再試',
        'timestamp' => (new DateTime())->format('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
