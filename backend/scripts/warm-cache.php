<?php

declare(strict_types=1);

/**
 * DI 容器快取預熱腳本
 *
 * 用於在部署後預先建立 DI 容器編譯快取，提升應用程式啟動效能
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\ContainerFactory;
use App\Database\DatabaseConnection;

echo "🚀 開始 DI 容器快取預熱...\n";

$startTime = microtime(true);

try {
    // 設定環境變數
    putenv('APP_ENV=production');

    echo "📦 建立 DI 容器...\n";

    // 建立容器實例 - 這會觸發編譯快取生成
    $container = ContainerFactory::create();

    echo "✅ DI 容器建立成功\n";

    // 預熱常用服務（讓容器解析這些服務以產生代理類）
    echo "🔥 預熱核心服務...\n";

    $services = [
        'App\Domains\Post\Contracts\PostServiceInterface',
        'App\Shared\Contracts\CacheServiceInterface',
        'App\Domains\Post\Contracts\PostRepositoryInterface',
        'App\Domains\Auth\Contracts\UserRepositoryInterface',
        'App\Domains\Attachment\Contracts\AttachmentRepositoryInterface',
        'App\Domains\Attachment\Services\AttachmentService',
        'App\Domains\Security\Contracts\XssProtectionServiceInterface',
        'App\Domains\Security\Contracts\CsrfProtectionServiceInterface',
        'App\Domains\Security\Contracts\LoggingSecurityServiceInterface',
        'App\Shared\Contracts\ValidatorInterface',
        'App\Application\Controllers\PostController',
        'App\Application\Controllers\Api\V1\AttachmentController',
        'App\Application\Controllers\Api\V1\AuthController',
        'App\Application\Controllers\Api\V1\IpController',
    ];

    $warmedServices = 0;

    foreach ($services as $service) {
        try {
            if ($container->has($service)) {
                $instance = $container->get($service);
                echo "  ✓ {$service}\n";
                $warmedServices++;
            } else {
                echo "  ⚠ {$service} (服務未註冊)\n";
            }
        } catch (Exception $e) {
            echo "  ✗ {$service} (錯誤: {$e->getMessage()})\n";
        }
    }

    // 測試資料庫連線預熱
    echo "🗄️ 預熱資料庫連線...\n";
    try {
        $db = DatabaseConnection::getInstance();
        $db->query('SELECT 1')->fetchColumn();
        echo "  ✓ 資料庫連線正常\n";
    } catch (Exception $e) {
        echo "  ⚠ 資料庫連線預熱失敗: {$e->getMessage()}\n";
    }

    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    echo "\n🎉 快取預熱完成！\n";
    echo "📊 統計資訊:\n";
    echo "  - 預熱服務數量: {$warmedServices}/" . count($services) . "\n";
    echo "  - 執行時間: {$duration}ms\n";

    // 檢查快取檔案
    $cacheDir = __DIR__ . '/../storage/di-cache';
    $proxiesDir = $cacheDir . '/proxies';

    if (is_dir($cacheDir)) {
        $cacheFiles = glob($cacheDir . '/*.php');
        $proxyFiles = is_dir($proxiesDir) ? glob($proxiesDir . '/*.php') : [];

        echo "  - 快取檔案: " . count($cacheFiles) . " 個\n";
        echo "  - 代理檔案: " . count($proxyFiles) . " 個\n";
        echo "  - 快取目錄: {$cacheDir}\n";
    }

    echo "\n✨ 應用程式已準備就緒，享受更快的啟動速度！\n";
} catch (Exception $e) {
    echo "\n❌ 快取預熱失敗: {$e->getMessage()}\n";
    echo "📍 錯誤位置: {$e->getFile()}:{$e->getLine()}\n";

    if ($e->getPrevious()) {
        echo "🔗 原始錯誤: {$e->getPrevious()->getMessage()}\n";
    }

    exit(1);
}

echo "\n";
