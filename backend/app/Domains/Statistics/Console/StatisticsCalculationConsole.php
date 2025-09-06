<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Console;

use App\Domains\Statistics\Commands\StatisticsCalculationCommand;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * 統計計算控制台介面。
 *
 * 提供命令行介面來執行統計計算任務
 */
readonly class StatisticsCalculationConsole
{
    public function __construct(
        private StatisticsCalculationCommand $calculationCommand,
        private LoggerInterface $logger,
    ) {}

    /**
     * 主要執行入口。
     *
     * @param string[] $arguments 命令行參數
     */
    public function run(array $arguments): int
    {
        try {
            $options = $this->parseArguments($arguments);

            $this->logger->info('統計計算控制台啟動', [
                'options' => $options,
                'arguments' => $arguments,
            ]);

            return match ($options['command']) {
                'calculate' => $this->handleCalculateCommand($options),
                'status' => $this->handleStatusCommand(),
                'cleanup' => $this->handleCleanupCommand(),
                'help' => $this->handleHelpCommand(),
                default => $this->handleInvalidCommand($options['command']),
            };
        } catch (Exception $e) {
            $this->logger->error('統計計算控制台執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->printError("執行失敗: {$e->getMessage()}");

            return 1;
        }
    }

    /**
     * 處理計算指令。
     */
    private function handleCalculateCommand(array $options): int
    {
        $periods = $options['periods'] ?? ['daily', 'weekly', 'monthly'];
        $force = $options['force'] ?? false;
        $skipCache = $options['skip-cache'] ?? false;

        $this->printInfo('開始統計計算任務...');
        $this->printInfo('週期: ' . implode(', ', $periods));

        if ($force) {
            $this->printWarning('強制模式：將忽略現有鎖定');
        }

        if ($skipCache) {
            $this->printWarning('跳過快取：將重新計算所有統計');
        }

        $result = $this->calculationCommand->execute($periods, $force, $skipCache);

        $this->printCalculationResults($result);

        return $result['failure_count'] > 0 ? 1 : 0;
    }

    /**
     * 處理狀態查詢指令。
     */
    private function handleStatusCommand(): int
    {
        $this->printInfo('查詢統計計算任務狀態...');

        $status = $this->calculationCommand->getStatus();

        $this->printStatusResults($status);

        return 0;
    }

    /**
     * 處理清理指令。
     */
    private function handleCleanupCommand(): int
    {
        $this->printInfo('清理過期的鎖定檔案...');

        $cleanedCount = $this->calculationCommand->cleanupExpiredLocks();

        if ($cleanedCount > 0) {
            $this->printSuccess("已清理 {$cleanedCount} 個過期鎖定檔案");
        } else {
            $this->printInfo('沒有發現過期的鎖定檔案');
        }

        return 0;
    }

    /**
     * 處理說明指令。
     */
    private function handleHelpCommand(): int
    {
        $this->printHelp();

        return 0;
    }

    /**
     * 處理無效指令。
     */
    private function handleInvalidCommand(string $command): int
    {
        $this->printError("無效的指令: {$command}");
        $this->printHelp();

        return 1;
    }

    /**
     * 解析命令行參數。
     */
    private function parseArguments(array $arguments): array
    {
        $options = [
            'command' => 'help',
            'periods' => [],
            'force' => false,
            'skip-cache' => false,
        ];

        $i = 0;
        while ($i < count($arguments)) {
            $arg = $arguments[$i];

            switch ($arg) {
                case 'calculate':
                case 'status':
                case 'cleanup':
                case 'help':
                    $options['command'] = $arg;
                    break;
                case '--periods':
                    if (!isset($arguments[$i + 1])) {
                        throw new InvalidArgumentException('--periods 需要參數值');
                    }
                    $options['periods'] = explode(',', $arguments[$i + 1]);
                    $i++; // 跳過下一個參數
                    break;
                case '--force':
                    $options['force'] = true;
                    break;
                case '--skip-cache':
                    $options['skip-cache'] = true;
                    break;
                default:
                    // 如果不是已知選項，且不是以 -- 開頭，可能是週期類型的簡寫
                    if (!str_starts_with($arg, '--')) {
                        if (in_array($arg, ['daily', 'weekly', 'monthly', 'yearly'])) {
                            $options['periods'][] = $arg;
                        }
                    }
                    break;
            }

            $i++;
        }

        // 如果沒有指定週期，使用預設值
        if (empty($options['periods']) && $options['command'] === 'calculate') {
            $options['periods'] = ['daily', 'weekly', 'monthly'];
        }

        return $options;
    }

    /**
     * 輸出計算結果。
     */
    private function printCalculationResults(array $result): void
    {
        $this->printInfo("\n=== 統計計算結果 ===");
        $this->printInfo('總執行時間: ' . number_format($result['total_duration'], 2) . ' 秒');
        $this->printInfo("總週期數: {$result['total_periods']}");
        $this->printSuccess("成功: {$result['success_count']}");

        if ($result['failure_count'] > 0) {
            $this->printError("失敗: {$result['failure_count']}");
        }

        $this->printInfo("\n=== 詳細結果 ===");
        foreach ($result['results'] as $period => $periodResult) {
            $status = $periodResult['success'] ? '✓' : '✗';
            $duration = number_format($periodResult['duration'] ?? 0, 2);

            if ($periodResult['success']) {
                $extra = '';
                if ($periodResult['cached']) {
                    $extra = ' (快取)';
                } elseif (isset($periodResult['snapshot_id'])) {
                    $extra = " (快照: {$periodResult['snapshot_id']})";
                }
                $this->printSuccess("{$status} {$period}: {$duration}s{$extra}");
            } else {
                $error = $periodResult['error'] ?? 'Unknown error';
                if ($periodResult['skipped']) {
                    $this->printWarning("{$status} {$period}: 已跳過 - {$error}");
                } else {
                    $this->printError("{$status} {$period}: {$error}");
                }
            }
        }
    }

    /**
     * 輸出狀態結果。
     */
    private function printStatusResults(array $status): void
    {
        $this->printInfo("\n=== 統計計算任務狀態 ===");
        $this->printInfo("鎖定超時時間: {$status['lock_timeout']} 秒");
        $this->printInfo("最大重試次數: {$status['max_retries']}");
        $this->printInfo("重試間隔: {$status['retry_delay']} 秒");

        $this->printInfo("\n=== 週期狀態 ===");
        foreach ($status['periods'] as $period => $periodStatus) {
            $lockedStatus = $periodStatus['locked'] ? '🔒 已鎖定' : '🔓 可用';
            $this->printInfo("{$period}: {$lockedStatus}");

            if ($periodStatus['locked'] && $periodStatus['lock_time']) {
                $lockAge = $periodStatus['lock_age_seconds'];
                $this->printInfo('  鎖定時間: ' . date('Y-m-d H:i:s', $periodStatus['lock_time']));
                $this->printInfo("  鎖定時長: {$lockAge} 秒");

                if ($lockAge > $status['lock_timeout']) {
                    $this->printWarning('  ⚠️ 鎖定時間過長，可能需要清理');
                }
            }
        }
    }

    /**
     * 輸出說明資訊。
     */
    private function printHelp(): void
    {
        $this->printInfo('統計計算控制台');
        $this->printInfo('================');
        $this->printInfo('');
        $this->printInfo('使用方式:');
        $this->printInfo('  php statistics-console.php <command> [options]');
        $this->printInfo('');
        $this->printInfo('指令:');
        $this->printInfo('  calculate    執行統計計算任務');
        $this->printInfo('  status       查詢任務狀態');
        $this->printInfo('  cleanup      清理過期鎖定檔案');
        $this->printInfo('  help         顯示此說明');
        $this->printInfo('');
        $this->printInfo('選項:');
        $this->printInfo('  --periods <list>   指定要計算的週期 (daily,weekly,monthly,yearly)');
        $this->printInfo('  --force            強制執行，忽略現有鎖定');
        $this->printInfo('  --skip-cache       跳過快取檢查，重新計算');
        $this->printInfo('');
        $this->printInfo('範例:');
        $this->printInfo('  php statistics-console.php calculate');
        $this->printInfo('  php statistics-console.php calculate --periods daily,weekly');
        $this->printInfo('  php statistics-console.php calculate --force --skip-cache');
        $this->printInfo('  php statistics-console.php status');
        $this->printInfo('  php statistics-console.php cleanup');
    }

    /**
     * 輸出成功訊息。
     */
    private function printSuccess(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    /**
     * 輸出警告訊息。
     */
    private function printWarning(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    /**
     * 輸出錯誤訊息。
     */
    private function printError(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    /**
     * 輸出一般資訊。
     */
    private function printInfo(string $message): void
    {
        echo "{$message}\n";
    }
}
