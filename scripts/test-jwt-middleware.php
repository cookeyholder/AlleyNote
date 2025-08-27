<?php

declare(strict_types=1);

/**
 * JWT 中介軟體功能測試腳本
 * 
 * 測試 JWT 認證和授權中介軟體是否正確運作
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

echo "🔐 開始測試 JWT 中介軟體功能...\n\n";

// 建立 HTTP 客戶端（針對 Docker 內部網路）
$httpClient = new Client([
    'base_uri' => 'http://nginx',
    'timeout' => 30,
    'http_errors' => false, // 不要在 4xx/5xx 錯誤時拋出例外
    'allow_redirects' => false, // 不自動跟隨重導向，避免 HTTPS 問題
    'verify' => false, // 關閉 SSL 驗證（測試用）
]);

// 測試案例定義
$testCases = [
    [
        'name' => '健康檢查 (公開路由)',
        'method' => 'GET',
        'uri' => '/api/health',
        'headers' => [],
        'expectedStatus' => [200, 404], // 允許 404 如果路由還沒實作 handler
        'description' => '測試公開路由是否不需要認證'
    ],
    [
        'name' => '文檔路由 (公開)',
        'method' => 'GET',
        'uri' => '/api/docs',
        'headers' => [],
        'expectedStatus' => [200, 404],
        'description' => '測試文檔路由是否公開可存取'
    ],
    [
        'name' => '未認證存取受保護路由',
        'method' => 'GET',
        'uri' => '/api/auth/me',
        'headers' => [],
        'expectedStatus' => [401],
        'description' => '測試未提供 token 時是否回傳 401'
    ],
    [
        'name' => '無效 token 存取受保護路由',
        'method' => 'GET',
        'uri' => '/api/auth/me',
        'headers' => [
            'Authorization' => 'Bearer invalid_token_here'
        ],
        'expectedStatus' => [401],
        'description' => '測試無效 token 時是否回傳 401'
    ],
    [
        'name' => '嘗試存取需要權限的路由',
        'method' => 'POST',
        'uri' => '/api/posts',
        'headers' => [],
        'expectedStatus' => [401],
        'description' => '測試建立貼文時是否需要認證'
    ],
    [
        'name' => '嘗試刪除貼文（需要權限）',
        'method' => 'DELETE',
        'uri' => '/api/posts/1',
        'headers' => [],
        'expectedStatus' => [401],
        'description' => '測試刪除貼文時是否需要認證和授權'
    ]
];

$successCount = 0;
$totalCount = count($testCases);

echo "📋 執行 JWT 中介軟體測試：\n";
echo "==========================\n";

foreach ($testCases as $testCase) {
    echo "\n🔍 測試案例: {$testCase['name']}\n";
    echo "   描述: {$testCase['description']}\n";
    echo "   請求: {$testCase['method']} {$testCase['uri']}\n";

    try {
        $options = [
            'headers' => array_merge(
                ['Host' => 'localhost'], // 確保每個請求都有正確的 Host header
                $testCase['headers']
            )
        ];

        $response = $httpClient->request(
            $testCase['method'],
            $testCase['uri'],
            $options
        );

        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody()->getContents();

        echo "   回應狀態: {$statusCode}\n";

        // 檢查狀態碼是否符合預期
        if (in_array($statusCode, $testCase['expectedStatus'], true)) {
            echo "   ✅ 測試通過\n";
            $successCount++;

            // 顯示回應內容的前 200 個字元
            if (!empty($responseBody)) {
                $preview = strlen($responseBody) > 200
                    ? substr($responseBody, 0, 200) . '...'
                    : $responseBody;
                echo "   回應預覽: " . json_encode($preview) . "\n";
            }
        } else {
            echo "   ❌ 測試失敗 - 預期狀態碼: " . implode(' 或 ', $testCase['expectedStatus']) . "，實際: {$statusCode}\n";
            if (!empty($responseBody)) {
                echo "   錯誤回應: {$responseBody}\n";
            }
        }
    } catch (GuzzleException $e) {
        echo "   ❌ 測試失敗 - HTTP 請求錯誤: {$e->getMessage()}\n";
    } catch (Exception $e) {
        echo "   ❌ 測試失敗 - 一般錯誤: {$e->getMessage()}\n";
    }
}

echo "\n📊 測試結果統計：\n";
echo "================\n";
echo "成功: {$successCount} / {$totalCount}\n";
echo "失敗: " . ($totalCount - $successCount) . " / {$totalCount}\n";
echo "成功率: " . round(($successCount / $totalCount) * 100, 2) . "%\n\n";

if ($successCount === $totalCount) {
    echo "🎉 所有 JWT 中介軟體測試通過！\n";
    echo "✅ 路由配置和中介軟體運作正常\n";
} else {
    echo "⚠️  部分測試失敗，請檢查：\n";
    echo "   - Docker 容器是否正在運行\n";
    echo "   - Web 服務是否啟動完成\n";
    echo "   - 路由配置是否正確載入\n";
    echo "   - 中介軟體是否正確註冊\n";
}

echo "\n✅ JWT 中介軟體功能測試完成！\n";
