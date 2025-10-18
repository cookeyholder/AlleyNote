#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 統計資料回填指令執行腳本.
 *
 * 此腳本提供統計資料回填功能的 CLI 介面，支援按統計類型和日期範圍進行回填。
 *
 * 使用方式：
 *   php scripts/statistics-recalculation.php [type] [start_date] [end_date] [options]
 *
 * 範例：
 *   php scripts/statistics-recalculation.php overview 2023-01-01 2023-01-31 --force
 *   php scripts/statistics-recalculation.php posts 2023-01-01 2023-01-31 --batch-size=7
 *   php scripts/statistics-recalculation.php --dry-run
 */

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 設定記憶體和時間限制
ini_set('memory_limit', '512M');
set_time_limit(0);

// 載入 Composer 自動載入器
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../backend/vendor/autoload.php',
];

$autoloaderFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    fwrite(STDERR, "錯誤: 無法找到 Composer 自動載入器\n");
    fwrite(STDERR, "請確保已執行 'composer install' 並且 vendor 目錄存在\n");
    exit(1);
}

// 載入環境變數
$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../backend/.env',
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        break;
    }
}

use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Infrastructure\Statistics\Commands\StatisticsRecalculationCommand;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

try {
    // 建立 DI 容器（這裡需要根據實際的 DI 容器實作來調整）
    $container = createContainer();

    // 從容器取得必要的服務
    $aggregationService = $container->get(StatisticsAggregationService::class);
    $applicationService = $container->get(StatisticsApplicationService::class);
    $logger = $container->get(LoggerInterface::class);

    // 建立指令
    $command = new StatisticsRecalculationCommand(
        $aggregationService,
        $applicationService,
        $logger,
    );

    // 建立 Console 應用程式
    $app = new Application('統計回填工具', '1.0.0');
    $app->add($command);
    $app->setDefaultCommand('statistics:recalculate', true);

    // 執行指令
    $exitCode = $app->run();
    exit($exitCode);

} catch (Throwable $e) {
    fwrite(STDERR, "致命錯誤: " . $e->getMessage() . "\n");
    fwrite(STDERR, "檔案: " . $e->getFile() . ":" . $e->getLine() . "\n");
    if (isset($logger)) {
        $logger->critical('統計回填腳本執行失敗', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
    exit(1);
}

/**
 * 建立 DI 容器.
 *
 * 這個函式需要根據專案的實際 DI 容器實作來調整。
 * 目前提供一個基本的實作框架。
 */
function createContainer(): ContainerInterface
{
    // 這裡需要根據專案的實際 DI 容器實作來建立容器
    // 例如：
    // - 如果使用 PHP-DI，則使用 ContainerBuilder
    // - 如果使用 Symfony DI，則載入 Symfony 的容器
    // - 如果使用自訂容器，則建立並配置容器

    throw new RuntimeException(
        '請實作 createContainer() 函式來建立 DI 容器。' .
        '此函式需要根據專案的 DI 容器實作來調整。'
    );

    // 範例實作（需要根據實際情況調整）:
    /*
    $containerBuilder = new DI\ContainerBuilder();
    $containerBuilder->addDefinitions([
        StatisticsAggregationService::class => DI\autowire(),
        StatisticsApplicationService::class => DI\autowire(),
        LoggerInterface::class => DI\factory(function () {
            return new \Monolog\Logger('statistics-recalculation');
        }),
        // 添加其他必要的服務定義...
    ]);
    return $containerBuilder->build();
    */
}

/**
 * 顯示使用說明.
 */
function showUsage(): void
{
    echo "統計資料回填指令\n";
    echo "================\n\n";

    echo "使用方式：\n";
    echo "  php scripts/statistics-recalculation.php [type] [start_date] [end_date] [options]\n\n";

    echo "參數：\n";
    echo "  type        統計類型 (overview, posts, users, popular)\n";
    echo "  start_date  開始日期 (YYYY-MM-DD)\n";
    echo "  end_date    結束日期 (YYYY-MM-DD)\n\n";

    echo "選項：\n";
    echo "  --force, -f          強制覆蓋已存在的快照\n";
    echo "  --batch-size=N, -b=N 批次處理天數（預設 30 天）\n";
    echo "  --dry-run, -d        試執行模式，只顯示將要處理的項目\n";
    echo "  --help, -h           顯示此說明\n\n";

    echo "範例：\n";
    echo "  php scripts/statistics-recalculation.php overview 2023-01-01 2023-01-31 --force\n";
    echo "  php scripts/statistics-recalculation.php posts 2023-01-01 2023-01-31 --batch-size=7\n";
    echo "  php scripts/statistics-recalculation.php --dry-run\n";
    echo "  php scripts/statistics-recalculation.php --help\n\n";

    echo "支援的統計類型：\n";
    echo "  overview  總覽統計\n";
    echo "  posts     文章統計\n";
    echo "  users     使用者統計\n";
    echo "  popular   熱門內容統計\n\n";
}

// 檢查是否請求說明
if (in_array('--help', $argv) || in_array('-h', $argv)) {
    showUsage();
    exit(0);
}
