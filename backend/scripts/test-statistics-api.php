<?php

declare(strict_types=1);

/**
 * 測試統計 API
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Http\ServerRequest;
use GuzzleHttp\Psr7\Response;

// 載入環境變數
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// 建立應用程式
$app = require __DIR__ . '/../app/Application.php';
$app = new $app();

// 建立管理員使用者並取得 token
try {
    // 登入管理員
    $request = new ServerRequest(
        'POST',
        '/api/auth/login',
        [
            'Content-Type' => 'application/json',
        ],
        json_encode([
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
    );

    $response = $app->handle($request);
    $body = (string) $response->getBody();
    $loginData = json_decode($body, true);

    if (!isset($loginData['access_token'])) {
        echo "❌ 登入失敗: " . ($loginData['error'] ?? '未知錯誤') . "\n";
        exit(1);
    }

    $accessToken = $loginData['access_token'];
    echo "✅ 登入成功，取得 access token\n";

    // 測試統計 API
    echo "\n========== 測試 /api/statistics/overview ==========\n";
    $request = new ServerRequest(
        'GET',
        '/api/statistics/overview',
        [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ]
    );

    $response = $app->handle($request);
    $statusCode = $response->getStatusCode();
    $body = (string) $response->getBody();
    $data = json_decode($body, true);

    echo "Status Code: {$statusCode}\n";
    echo "Response Body:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    if ($statusCode === 200 && isset($data['success']) && $data['success']) {
        echo "\n✅ 統計 API 測試成功！\n";
    } else {
        echo "\n❌ 統計 API 測試失敗！\n";
        if (isset($data['error'])) {
            echo "錯誤訊息: " . json_encode($data['error'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} catch (Throwable $e) {
    echo "❌ 發生錯誤: " . $e->getMessage() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
