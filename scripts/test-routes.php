<?php

declare(strict_types=1);

/**
 * 路由配置測試腳本
 * 
 * 測試 JWT 認證相關路由是否正確配置
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Routing\Core\Router;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

echo "🔍 開始測試路由配置...\n\n";

// 建立路由器實例
$router = new Router();

// 載入路由配置
$routeConfig = require __DIR__ . '/../config/routes.php';
$routeConfig($router);

// 測試路由清單
$testRoutes = [
    // 公開路由
    ['GET', '/api/health', '健康檢查'],
    ['GET', '/docs', '文檔重導向'],
    ['GET', '/api/docs', 'API 文檔'],
    ['GET', '/api/docs/ui', 'API 文檔 UI'],

    // JWT 認證相關路由 (公開)
    ['POST', '/api/auth/register', '使用者註冊'],
    ['POST', '/api/auth/login', '使用者登入'],
    ['POST', '/api/auth/refresh', 'Token 刷新'],

    // JWT 認證路由
    ['POST', '/api/auth/logout', '使用者登出'],
    ['GET', '/api/auth/me', '取得使用者資訊'],

    // 貼文相關路由
    ['GET', '/api/posts', '瀏覽貼文清單'],
    ['GET', '/api/posts/123', '檢視特定貼文'],
    ['POST', '/api/posts', '建立新貼文'],
    ['PUT', '/api/posts/123', '更新貼文'],
    ['DELETE', '/api/posts/123', '刪除貼文'],
];

$successCount = 0;
$totalCount = count($testRoutes);

echo "📋 測試路由清單：\n";
echo "================\n";

foreach ($testRoutes as [$method, $path, $description]) {
    // 建立測試請求
    $request = new ServerRequest($method, new Uri($path));

    try {
        // 使用路由器的 dispatch 方法進行匹配
        $matchResult = $router->dispatch($request);

        if ($matchResult->isMatched()) {
            $route = $matchResult->getRoute();
            $routeName = $route->getName() ?? '未命名';
            $middlewareCount = count($route->getMiddlewares());

            echo "✅ {$method} {$path} - {$description}\n";
            echo "   路由名稱: {$routeName}\n";
            echo "   中介軟體數量: {$middlewareCount}\n";

            // 顯示路由參數（如果有）
            $parameters = $matchResult->getParameters();
            if (!empty($parameters)) {
                echo "   路由參數: " . json_encode($parameters) . "\n";
            }

            $successCount++;
        } else {
            echo "❌ {$method} {$path} - {$description} (路由未匹配: {$matchResult->getError()})\n";
        }
    } catch (Exception $e) {
        echo "❌ {$method} {$path} - {$description} (錯誤: {$e->getMessage()})\n";
    }

    echo "\n";
}

echo "📊 測試結果統計：\n";
echo "================\n";
echo "成功: {$successCount} / {$totalCount}\n";
echo "失敗: " . ($totalCount - $successCount) . " / {$totalCount}\n";
echo "成功率: " . round(($successCount / $totalCount) * 100, 2) . "%\n\n";

if ($successCount === $totalCount) {
    echo "🎉 所有路由配置測試通過！\n";
} else {
    echo "⚠️  部分路由配置需要修正\n";
}

// 測試路由集合統計
echo "\n📈 路由統計資訊：\n";
echo "================\n";
$allRoutes = $router->getRoutes()->all();
echo "總路由數量: " . count($allRoutes) . "\n";

// 按 HTTP 方法統計
$methodStats = [];
foreach ($allRoutes as $route) {
    foreach ($route->getMethods() as $method) {
        $methodStats[$method] = ($methodStats[$method] ?? 0) + 1;
    }
}

echo "HTTP 方法統計:\n";
foreach ($methodStats as $method => $count) {
    echo "  {$method}: {$count} 個路由\n";
}

// 按路由名稱前綴統計
$prefixStats = [];
foreach ($allRoutes as $route) {
    $name = $route->getName();
    if ($name) {
        $prefix = explode('.', $name)[0];
        $prefixStats[$prefix] = ($prefixStats[$prefix] ?? 0) + 1;
    }
}

if (!empty($prefixStats)) {
    echo "\n路由名稱前綴統計:\n";
    foreach ($prefixStats as $prefix => $count) {
        echo "  {$prefix}.*: {$count} 個路由\n";
    }
}

echo "\n✅ 路由配置測試完成！\n";
