<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * DI 容器工廠類.
 *
 * 負責建立和配置 DI 容器
 */
class ContainerFactory
{
    private static ?ContainerInterface $container = null;

    /**
     * 建立 DI 容器.
     */
    public static function create(): ContainerInterface
    {
        if (self::$container === null) {
            $builder = new ContainerBuilder();

            // 載入定義檔
            $builder->addDefinitions(__DIR__ . '/container.php');

            // 啟用編譯快取以提升效能（生產和開發環境）
            $cacheDir = __DIR__ . '/../../storage/di-cache';
            $proxiesDir = $cacheDir . '/proxies';

            // 確保快取目錄存在
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0o755, true);
            }
            if (!is_dir($proxiesDir)) {
                mkdir($proxiesDir, 0o755, true);
            }

            $builder->enableCompilation($cacheDir);
            $builder->writeProxiesToFile(true, $proxiesDir);

            // 設定快取效能優化選項（如果 APCu 可用）
            if (extension_loaded('apcu') && ini_get('apc.enabled')) {
                $builder->enableDefinitionCache();
            }

            // 在生產環境啟用更積極的快取
            if (getenv('APP_ENV') === 'production') {
                // 生產環境優化設定 - 使用基本快取選項
                // PHP-DI 7.x 的編譯快取已足夠
            }

            self::$container = $builder->build();
        }

        return self::$container;
    }

    /**
     * 重設容器（主要用於測試）.
     */
    public static function reset(): void
    {
        self::$container = null;
    }

    /**
     * 取得容器實例.
     */
    public static function getInstance(): ContainerInterface
    {
        return self::create();
    }

    /**
     * 檢查是否已初始化.
     */
    public static function isInitialized(): bool
    {
        return self::$container !== null;
    }
}
