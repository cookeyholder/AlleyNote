<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use Exception;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * 系統監控服務.
 *
 * 提供系統資源監控、健康檢查和性能指標收集功能
 */
final class SystemMonitorService
{
    private PDO $database;

    private LoggerInterface $logger;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(PDO $database, LoggerInterface $logger, array $config = [])
    {
        $this->database = $database;
        $this->logger = $logger;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 取得系統健康狀態總覽.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return [
            'overall_status' => 'healthy',
            'memory' => $this->getMemoryUsage(),
            'cpu' => $this->getCpuUsage(),
            'disk' => $this->getDiskUsage(),
            'database' => $this->getDatabaseStatus(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得記憶體使用情況
     *
     * @return array<string, mixed>
     */
    public function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_usage_bytes' => $memoryUsage,
            'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_usage_bytes' => $memoryPeak,
            'peak_usage_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_bytes' => $memoryLimit,
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percentage' => $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0,
            'available_bytes' => max(0, $memoryLimit - $memoryUsage),
            'available_mb' => round(max(0, $memoryLimit - $memoryUsage) / 1024 / 1024, 2),
        ];
    }

    /**
     * 取得 CPU 使用率.
     *
     * @return array<string, mixed>
     */
    public function getCpuUsage(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return [
            'load_average_1min' => $loadAvg[0] ?? 0,
            'load_average_5min' => $loadAvg[1] ?? 0,
            'load_average_15min' => $loadAvg[2] ?? 0,
            'cpu_cores' => $this->getCpuCoreCount(),
        ];
    }

    /**
     * 取得磁碟使用情況
     *
     * @return array<string, mixed>
     */
    public function getDiskUsage(): array
    {
        $path = $this->config['disk_path'] ?? '/';

        if (!is_string($path) || !is_dir($path)) {
            return [
                'error' => 'Invalid disk path',
                'path' => $path,
            ];
        }

        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes && $freeBytes ? $totalBytes - $freeBytes : 0;

        return [
            'path' => $path,
            'total_bytes' => $totalBytes ?: 0,
            'total_gb' => $totalBytes ? round($totalBytes / 1024 / 1024 / 1024, 2) : 0,
            'free_bytes' => $freeBytes ?: 0,
            'free_gb' => $freeBytes ? round($freeBytes / 1024 / 1024 / 1024, 2) : 0,
            'used_bytes' => $usedBytes,
            'used_gb' => round($usedBytes / 1024 / 1024 / 1024, 2),
            'usage_percentage' => $totalBytes ? round(($usedBytes / $totalBytes) * 100, 2) : 0,
        ];
    }

    /**
     * 取得資料庫狀態.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStatus(): array
    {
        try {
            $startTime = microtime(true);
            $stmt = $this->database->prepare('SELECT 1 as test');
            $stmt->execute();
            $result = $stmt->fetch();
            $responseTime = microtime(true) - $startTime;

            return [
                'status' => 'connected',
                'response_time_ms' => round($responseTime * 1000, 2),
                'connection_info' => $this->getDatabaseConnectionInfo(),
            ];
        } catch (Exception $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }

    /**
     * 取得 CPU 核心數量.
     */
    private function getCpuCoreCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%') ?: 1;
        }

        $cores = shell_exec('nproc') ?: shell_exec('grep -c ^processor /proc/cpuinfo');

        return (int) $cores ?: 1;
    }

    /**
     * 解析記憶體限制.
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $memoryLimit = strtolower($memoryLimit);
        $value = (int) $memoryLimit;

        if (str_contains($memoryLimit, 'g')) {
            return $value * 1024 * 1024 * 1024;
        }

        if (str_contains($memoryLimit, 'm')) {
            return $value * 1024 * 1024;
        }

        if (str_contains($memoryLimit, 'k')) {
            return $value * 1024;
        }

        return $value;
    }

    /**
     * 取得資料庫連線資訊.
     *
     * @return array<string, mixed>
     */
    private function getDatabaseConnectionInfo(): array
    {
        try {
            return [
                'driver' => $this->database->getAttribute(PDO::ATTR_DRIVER_NAME),
                'version' => $this->database->getAttribute(PDO::ATTR_SERVER_VERSION),
            ];
        } catch (Exception $e) {
            return [
                'driver' => 'unknown',
                'version' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 取得預設設定.
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'disk_path' => '/',
            'memory_threshold' => 80,
            'cpu_threshold' => 80,
            'disk_threshold' => 90,
        ];
    }

    /**
     * 檢查系統是否健康.
     */
    public function isHealthy(): bool
    {
        $health = $this->getSystemHealth();

        // 檢查記憶體使用率
        if ($health['memory']['usage_percentage'] > $this->config['memory_threshold']) {
            return false;
        }

        // 檢查磁碟使用率
        if ($health['disk']['usage_percentage'] > $this->config['disk_threshold']) {
            return false;
        }

        // 檢查資料庫連線
        if ($health['database']['status'] !== 'connected') {
            return false;
        }

        return true;
    }

    /**
     * 取得效能指標.
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'memory' => $this->getMemoryUsage(),
            'cpu' => $this->getCpuUsage(),
            'disk' => $this->getDiskUsage(),
            'database_response' => $this->getDatabaseStatus()['response_time_ms'] ?? null,
        ];
    }
}
