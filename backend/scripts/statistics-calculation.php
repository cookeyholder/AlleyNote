#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 統計計算定時任務執行腳本.
 *
 * 用法:
 *   php statistics-calculation.php [options]
 *
 * 選項:
 *   --periods=daily,weekly,monthly  指定要計算的週期（預設: daily）
 *   --max-retries=3                 最大重試次數（預設: 3）
 *   --force                         強制重新計算現有快照
 *   --help                          顯示此說明訊息
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Statistics\Commands\StatisticsCalculationCommand;
use Psr\Container\ContainerInterface;

// 解析命令列參數
function parseArguments(array $argv): array
{
    $options = [
        'periods' => ['daily'],
        'maxRetries' => 3,
        'force' => false,
        'help' => false,
    ];

    foreach ($argv as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }

        if ($arg === '--force') {
            $options['force'] = true;
            continue;
        }

        if (strpos($arg, '--periods=') === 0) {
            $periods = substr($arg, 10);
            $options['periods'] = array_filter(array_map('trim', explode(',', $periods)));
            continue;
        }

        if (strpos($arg, '--max-retries=') === 0) {
            $maxRetries = (int) substr($arg, 14);
            if ($maxRetries >= 0) {
                $options['maxRetries'] = $maxRetries;
            }
            continue;
        }
    }

    return $options;
}

// 顯示說明訊息
function showHelp(): void
{
    echo <<<'HELP'
統計計算定時任務執行腳本

用法:
  php statistics-calculation.php [選項]

選項:
  --periods=daily,weekly,monthly  指定要計算的週期（預設: daily）
                                  可選值: daily, weekly, monthly
  --max-retries=3                 最大重試次數（預設: 3）
  --force                         強制重新計算現有快照
  --help, -h                      顯示此說明訊息

範例:
  php statistics-calculation.php
  php statistics-calculation.php --periods=daily,weekly
  php statistics-calculation.php --periods=monthly --max-retries=5 --force

HELP;
}

// 驗證週期參數
function validatePeriods(array $periods): void
{
    $validPeriods = ['daily', 'weekly', 'monthly'];
    $invalidPeriods = array_diff($periods, $validPeriods);

    if (!empty($invalidPeriods)) {
        echo "錯誤: 無效的統計週期: " . implode(', ', $invalidPeriods) . PHP_EOL;
        echo "有效的週期類型: " . implode(', ', $validPeriods) . PHP_EOL;
        exit(1);
    }

    if (empty($periods)) {
        echo "錯誤: 至少需要指定一個統計週期" . PHP_EOL;
        exit(1);
    }
}

// 格式化執行時間
function formatDuration(float $milliseconds): string
{
    if ($milliseconds < 1000) {
        return round($milliseconds, 2) . 'ms';
    }

    $seconds = $milliseconds / 1000;
    if ($seconds < 60) {
        return round($seconds, 2) . 's';
    }

    $minutes = $seconds / 60;
    if ($minutes < 60) {
        return floor($minutes) . 'm' . round($seconds % 60, 1) . 's';
    }

    $hours = $minutes / 60;

    return floor($hours) . 'h' . round($minutes % 60, 1) . 'm';
}

// 主執行函數
function main(array $argv): int
{
    try {
        // 解析參數
        $options = parseArguments($argv);

        // 顯示說明
        if ($options['help']) {
            showHelp();

            return 0;
        }

        // 驗證參數
        validatePeriods($options['periods']);

        echo "=== 統計計算定時任務開始 ===" . PHP_EOL;
        echo "週期: " . implode(', ', $options['periods']) . PHP_EOL;
        echo "最大重試次數: " . $options['maxRetries'] . PHP_EOL;
        echo "強制重新計算: " . ($options['force'] ? '是' : '否') . PHP_EOL;
        echo "開始時間: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL . PHP_EOL;

        // 載入相依性容器
        $containerPath = __DIR__ . '/../app/Infrastructure/Config/container.php';
        if (!file_exists($containerPath)) {
            throw new RuntimeException('找不到相依性容器設定檔案');
        }

        /** @var ContainerInterface $container */
        $container = require $containerPath;

        // 獲取統計計算命令
        $command = $container->get(StatisticsCalculationCommand::class);

        // 執行統計計算
        $result = $command->execute(
            periods: $options['periods'],
            maxRetries: $options['maxRetries'],
            force: $options['force'],
        );

        // 顯示執行結果
        echo "=== 執行結果 ===" . PHP_EOL;
        echo "總快照數量: " . $result['total_snapshots'] . PHP_EOL;
        echo "成功快照數量: " . $result['successful_snapshots'] . PHP_EOL;
        echo "失敗快照數量: " . $result['failed_snapshots'] . PHP_EOL;
        echo "重試次數: " . $result['retries'] . PHP_EOL;
        echo "執行時間: " . formatDuration($result['duration_ms']) . PHP_EOL;

        if ($result['failed_snapshots'] > 0) {
            echo PHP_EOL . "=== 失敗詳情 ===" . PHP_EOL;
            foreach ($result['errors'] as $error) {
                echo "- {$error['snapshot_type']} ({$error['period_type']}): {$error['error']}" . PHP_EOL;
                if ($error['retries'] > 0) {
                    echo "  重試次數: {$error['retries']}" . PHP_EOL;
                }
            }
        }

        echo PHP_EOL . "結束時間: " . date('Y-m-d H:i:s') . PHP_EOL;
        echo "=== 統計計算任務完成 ===" . PHP_EOL;

        // 返回適當的退出碼
        return $result['failed_snapshots'] > 0 ? 1 : 0;
    } catch (Throwable $e) {
        echo "錯誤: " . $e->getMessage() . PHP_EOL;
        echo "追蹤: " . $e->getTraceAsString() . PHP_EOL;

        return 1;
    }
}

// 執行腳本
exit(main($argv));
