<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use Exception;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * 系統監控服務。
 *
 * 提供系統資源監控、健康檢查和性能指標收集功能
 */
class SystemMonitorService
{
    private PDO $database;

    private LoggerInterface $logger;

    private array $config;

    public function __construct(PDO $database, LoggerInterface $logger, array $config = [])
    {
        $this->database = $database;
        $this->logger = $logger;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 取得所有系統指標。
     *
     * @return array<string, mixed>
     */
    public function getAllMetrics(): array
    {
        return [
            'memory' => $this->getMemoryUsage(),
            'cpu' => $this->getCpuUsage(),
            'disk' => $this->getDiskUsage(),
            'database' => $this->getDatabaseStatus(),
            'timestamp' => time(),
            'environment' => $this->config['environment'] ?? 'production',
        ];
    }

    /**
     * 取得記憶體使用情況。
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
     * 取得 CPU 使用率。
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
     * 取得磁碟使用情況。
     *
     * @return array<string, mixed>
     */
    public function getDiskUsage(): array
    {
        $path = $this->config['disk_path'] ?? '/';

        if (!is_dir($path)) {
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
     * 取得資料庫狀態。
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStatus(): array
    {
        try {
            $startTime = microtime(true);

            // 測試資料庫連線
            $stmt = $this->database->query('SELECT 1 as test');
            $connected = $stmt !== false;

            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

            $status = [
                'connected' => $connected,
                'connection_time_ms' => $connectionTime,
                'driver' => $this->database->getAttribute(PDO::ATTR_DRIVER_NAME),
                'server_version' => $this->database->getAttribute(PDO::ATTR_SERVER_VERSION) ?? 'unknown',
            ];

            // SQLite 特定統計
            if ($status['driver'] === 'sqlite') {
                $status = array_merge($status, $this->getSqliteStats());
            }

            return $status;
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'connection_time_ms' => 0,
            ];
        }
    }

    /**
     * 取得應用程式健康狀態。
     *
     * @return array<string, mixed>
     */
    public function getHealthCheck(): array
    {
        $checks = [
            'database' => $this->checkDatabaseHealth(),
            'memory' => $this->checkMemoryHealth(),
            'disk' => $this->checkDiskHealth(),
            'environment' => $this->checkEnvironmentHealth(),
            'logs' => $this->checkLogHealth(),
        ];

        $overallHealth = true;
        $score = 0;
        $totalChecks = count($checks);

        foreach ($checks as $check) {
            if (is_array($check) && isset($check['status'])) {
                if ($check['status'] === 'healthy') {
                    $score++;
                } elseif ($check['status'] === 'critical') {
                    $overallHealth = false;
                }
            } else {
                $overallHealth = false;
            }
        }

        return [
            'overall_status' => $overallHealth ? 'healthy' : 'unhealthy',
            'health_score' => round(($score / $totalChecks) * 100, 1),
            'checks' => $checks,
            'timestamp' => time(),
            'environment' => $this->config['environment'] ?? 'unknown',
        ];
    }

    /**
     * 記錄系統指標到日誌。
     */
    public function logSystemMetrics(): void
    {
        $metrics = $this->getAllMetrics();

        $this->logger->info('System metrics collected', [
            'memory_usage_mb' => $metrics['memory']['current_usage_mb'] ?? 0,
            'memory_usage_percent' => $metrics['memory']['usage_percentage'] ?? 0,
            'disk_usage_percent' => $metrics['disk']['usage_percentage'] ?? 0,
            'load_average_1min' => $metrics['cpu']['load_average_1min'] ?? 0,
            'database_connected' => $metrics['database']['connected'] ?? false,
            'database_response_time_ms' => $metrics['database']['connection_time_ms'] ?? 0,
        ]);
    }

    /**
     * 檢查是否有任何關鍵問題。
     */
    public function hasAnyIssues(): bool
    {
        $healthCheck = $this->getHealthCheck();

        return $healthCheck['overall_status'] !== 'healthy';
    }

    /**
     * 取得系統資源使用警告。
     *
     * @return array<string>
     */
    public function getResourceWarnings(): array
    {
        $warnings = [];
        $memory = $this->getMemoryUsage();
        $disk = $this->getDiskUsage();

        if ($memory['usage_percentage'] > 90) {
            $warnings[] = sprintf('記憶體使用率過高: %.1f%%', $memory['usage_percentage']);
        }

        if ($disk['usage_percentage'] > 85) {
            $warnings[] = sprintf('磁碟使用率過高: %.1f%%', $disk['usage_percentage']);
        }

        $cpu = $this->getCpuUsage();
        $cores = $cpu['cpu_cores'] ?? 1;
        if ($cpu['load_average_5min'] > $cores * 0.8) {
            $warnings[] = sprintf('CPU 負載過高: %.2f (核心數: %d)', $cpu['load_average_5min'], $cores);
        }

        return $warnings;
    }

    /**
     * 取得預設配置。
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'environment' => 'production',
            'disk_path' => '/',
            'memory_warning_threshold' => 80,
            'disk_warning_threshold' => 85,
            'cpu_warning_threshold' => 0.8,
        ];
    }

    /**
     * 解析記憶體限制字串。
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return 0; // 無限制
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) $memoryLimit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * 取得 CPU 核心數。
     */
    private function getCpuCoreCount(): int
    {
        if (function_exists('shell_exec')) {
            $cores = shell_exec('nproc');
            if ($cores !== null) {
                return (int) trim($cores);
            }
        }

        return 1; // 預設值
    }

    /**
     * 取得 SQLite 統計資訊。
     *
     * @return array<string, mixed>
     */
    private function getSqliteStats(): array
    {
        try {
            $stats = [];

            // 取得資料庫檔案大小
            $dbPath = $this->config['database_path'] ?? null;
            if (is_string($dbPath) && is_file($dbPath)) {
                $fileSize = filesize($dbPath);
                if ($fileSize !== false) {
                    $stats['database_file_size_bytes'] = $fileSize;
                    $stats['database_file_size_mb'] = round($fileSize / 1024 / 1024, 2);
                }
            }

            // 取得表格數量
            $stmt = $this->database->query("SELECT COUNT(*) as table_count FROM sqlite_master WHERE type='table'");
            if ($stmt) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['table_count'] = $result['table_count'] ?? 0;
            }

            return $stats;
        } catch (Exception $e) {
            error_log('Failed to get SQLite stats: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * 檢查資料庫健康狀態。
     *
     * @return array<string, mixed>
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);
            $stmt = $this->database->query('SELECT 1');
            $connectionTime = (microtime(true) - $startTime) * 1000;

            if ($stmt && $connectionTime < 100) {
                return ['status' => 'healthy', 'message' => 'Database connection is good', 'response_time_ms' => round($connectionTime, 2)];
            } elseif ($stmt && $connectionTime < 1000) {
                return ['status' => 'warning', 'message' => 'Database connection is slow', 'response_time_ms' => round($connectionTime, 2)];
            } else {
                return ['status' => 'critical', 'message' => 'Database connection is very slow', 'response_time_ms' => round($connectionTime, 2)];
            }
        } catch (Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查記憶體健康狀態。
     *
     * @return array<string, mixed>
     */
    private function checkMemoryHealth(): array
    {
        $memory = $this->getMemoryUsage();
        $usage = $memory['usage_percentage'];

        if ($usage < 70) {
            return ['status' => 'healthy', 'message' => 'Memory usage is normal', 'usage_percent' => $usage];
        } elseif ($usage < 90) {
            return ['status' => 'warning', 'message' => 'Memory usage is high', 'usage_percent' => $usage];
        } else {
            return ['status' => 'critical', 'message' => 'Memory usage is critical', 'usage_percent' => $usage];
        }
    }

    /**
     * 檢查磁碟健康狀態。
     *
     * @return array<string, mixed>
     */
    private function checkDiskHealth(): array
    {
        $disk = $this->getDiskUsage();
        $usage = $disk['usage_percentage'];

        if ($usage < 70) {
            return ['status' => 'healthy', 'message' => 'Disk usage is normal', 'usage_percent' => $usage];
        } elseif ($usage < 85) {
            return ['status' => 'warning', 'message' => 'Disk usage is high', 'usage_percent' => $usage];
        } else {
            return ['status' => 'critical', 'message' => 'Disk usage is critical', 'usage_percent' => $usage];
        }
    }

    /**
     * 檢查環境配置健康狀態。
     *
     * @return array<string, mixed>
     */
    private function checkEnvironmentHealth(): array
    {
        try {
            $requiredExtensions = ['pdo', 'json', 'mbstring'];
            $missingExtensions = [];

            foreach ($requiredExtensions as $extension) {
                if (!extension_loaded($extension)) {
                    $missingExtensions[] = $extension;
                }
            }

            if (empty($missingExtensions)) {
                return ['status' => 'healthy', 'message' => 'All required PHP extensions are loaded'];
            } else {
                return ['status' => 'critical', 'message' => 'Missing PHP extensions', 'missing_extensions' => $missingExtensions];
            }
        } catch (Exception $e) {
            return ['status' => 'critical', 'message' => 'Failed to check environment: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查日誌健康狀態。
     *
     * @return array<string, mixed>
     */
    private function checkLogHealth(): array
    {
        $logDir = $this->config['log_directory'] ?? '/tmp';

        if (!is_dir($logDir)) {
            return ['status' => 'critical', 'message' => 'Log directory does not exist'];
        }

        if (!is_writable($logDir)) {
            return ['status' => 'critical', 'message' => 'Log directory is not writable'];
        }

        return ['status' => 'healthy', 'message' => 'Log directory is accessible'];
    }
}
