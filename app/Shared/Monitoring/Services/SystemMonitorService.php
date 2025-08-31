<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use App\Shared\Config\EnvironmentConfig;
use App\Shared\Monitoring\Contracts\SystemMonitorInterface;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * 系統監控服務實作。
 *
 * 提供全面的系統監控功能，包含記憶體、磁碟、資料庫等系統指標監控
 */
class SystemMonitorService implements SystemMonitorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private PDO $database,
        private EnvironmentConfig $config
    ) {
    }

    /**
     * 取得系統基本資訊。
     */
    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'system' => PHP_OS_FAMILY,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'environment' => $this->config->getEnvironment(),
            'timezone' => date_default_timezone_get(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => $this->getLoadedExtensions(),
            'timestamp' => time(),
        ];
    }

    /**
     * 取得記憶體使用統計。
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
     * 取得 CPU 使用率（簡化版本）。
     */
    public function getCpuUsage(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return [
            'load_average_1min' => $loadAvg[0] ?? 0,
            'load_average_5min' => $loadAvg[1] ?? 0,
            'load_average_15min' => $loadAvg[2] ?? 0,
            'cpu_count' => $this->getCpuCount(),
            'process_id' => getmypid(),
            'process_uid' => function_exists('posix_getuid') ? posix_getuid() : null,
            'process_gid' => function_exists('posix_getgid') ? posix_getgid() : null,
        ];
    }

    /**
     * 取得磁碟使用統計。
     */
    public function getDiskUsage(string $path = '/'): array
    {
        if (!is_dir($path)) {
            $path = dirname(__DIR__, 4);
        }

        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes ? $totalBytes - $freeBytes : 0;

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
     * 取得資料庫連線狀態和統計。
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
        } catch (\Exception $e) {
            $this->logger->error('Database status check failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'connection_time_ms' => null,
            ];
        }
    }

    /**
     * 取得應用程式健康狀態。
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
            if ($check['status'] === 'healthy') {
                $score++;
            } elseif ($check['status'] === 'critical') {
                $overallHealth = false;
            }
        }

        return [
            'overall_status' => $overallHealth ? 'healthy' : 'unhealthy',
            'health_score' => round(($score / $totalChecks) * 100, 1),
            'checks' => $checks,
            'timestamp' => time(),
            'environment' => $this->config->getEnvironment(),
        ];
    }

    /**
     * 記錄系統指標到日誌。
     */
    public function logSystemMetrics(): void
    {
        $metrics = $this->getAllMetrics();

        // 安全地存取陣列元素
        $memoryUsageMb = is_array($metrics['memory'] ?? null) && is_numeric($metrics['memory']['current_usage_mb'] ?? 0)
            ? (float) $metrics['memory']['current_usage_mb'] : 0.0;
        $memoryUsagePercent = is_array($metrics['memory'] ?? null) && is_numeric($metrics['memory']['usage_percentage'] ?? 0)
            ? (float) $metrics['memory']['usage_percentage'] : 0.0;
        $diskUsagePercent = is_array($metrics['disk'] ?? null) && is_numeric($metrics['disk']['usage_percentage'] ?? 0)
            ? (float) $metrics['disk']['usage_percentage'] : 0.0;
        $dbConnected = is_array($metrics['database'] ?? null) && is_bool($metrics['database']['connected'] ?? false)
            ? $metrics['database']['connected'] : false;
        $healthScore = is_array($metrics['health'] ?? null) && is_numeric($metrics['health']['health_score'] ?? 0)
            ? (float) $metrics['health']['health_score'] : 0.0;

        $this->logger->info('System metrics collected', [
            'memory_usage_mb' => $memoryUsageMb,
            'memory_usage_percent' => $memoryUsagePercent,
            'disk_usage_percent' => $diskUsagePercent,
            'database_connected' => $dbConnected,
            'health_score' => $healthScore,
        ]);

        // 記錄警告
        if ($memoryUsagePercent > 80) {
            $memoryLimitMb = is_array($metrics['memory'] ?? null) && is_numeric($metrics['memory']['limit_mb'] ?? 0)
                ? (float) $metrics['memory']['limit_mb'] : 0.0;
            
            $this->logger->warning('High memory usage detected', [
                'usage_percent' => $memoryUsagePercent,
                'used_mb' => $memoryUsageMb,
                'limit_mb' => $memoryLimitMb,
            ]);
        }

        if ($metrics['disk']['usage_percentage'] > 85) {
            $this->logger->warning('High disk usage detected', [
                'usage_percent' => $metrics['disk']['usage_percentage'],
                'used_gb' => $metrics['disk']['used_gb'],
                'total_gb' => $metrics['disk']['total_gb'],
            ]);
        }
    }

    /**
     * 檢查系統是否正常運作。
     */
    public function isSystemHealthy(): bool
    {
        $health = $this->getHealthCheck();
        return $health['overall_status'] === 'healthy' && $health['health_score'] >= 80;
    }

    /**
     * 取得所有系統指標。
     */
    public function getAllMetrics(): array
    {
        return [
            'system' => $this->getSystemInfo(),
            'memory' => $this->getMemoryUsage(),
            'cpu' => $this->getCpuUsage(),
            'disk' => $this->getDiskUsage(),
            'database' => $this->getDatabaseStatus(),
            'health' => $this->getHealthCheck(),
        ];
    }

    // ===== 私有方法 =====

    /**
     * 取得載入的 PHP 擴充功能。
     */
    private function getLoadedExtensions(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions);
        return $extensions;
    }

    /**
     * 解析記憶體限制字串。
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);

        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }

    /**
     * 取得 CPU 核心數量。
     */
    private function getCpuCount(): int
    {
        if (function_exists('posix_times')) {
            return (int) shell_exec('nproc') ?: 1;
        }

        // 備選方案
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            return substr_count($cpuinfo ?: '', 'processor');
        }

        return 1;
    }

    /**
     * 取得 SQLite 統計資訊。
     */
    private function getSqliteStats(): array
    {
        try {
            $stats = [];

            // 取得資料庫檔案大小
            $dbPath = $this->config->get('DB_DATABASE');
            if (is_file($dbPath)) {
                $fileSize = filesize($dbPath);
                $stats['database_file_size_bytes'] = $fileSize;
                $stats['database_file_size_mb'] = round($fileSize / 1024 / 1024, 2);
            }

            // 取得表格數量
            $stmt = $this->database->query("SELECT COUNT(*) as table_count FROM sqlite_master WHERE type='table'");
            $result = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($result) {
                $stats['table_count'] = (int) $result['table_count'];
            }

            return $stats;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 檢查資料庫健康狀態。
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
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查記憶體健康狀態。
     */
    private function checkMemoryHealth(): array
    {
        $memory = $this->getMemoryUsage();
        $usage = $memory['usage_percentage'];

        if ($usage < 70) {
            return ['status' => 'healthy', 'message' => 'Memory usage is normal', 'usage_percent' => $usage];
        } elseif ($usage < 85) {
            return ['status' => 'warning', 'message' => 'Memory usage is elevated', 'usage_percent' => $usage];
        } else {
            return ['status' => 'critical', 'message' => 'Memory usage is critical', 'usage_percent' => $usage];
        }
    }

    /**
     * 檢查磁碟健康狀態。
     */
    private function checkDiskHealth(): array
    {
        $disk = $this->getDiskUsage();
        $usage = $disk['usage_percentage'];

        if ($usage < 80) {
            return ['status' => 'healthy', 'message' => 'Disk usage is normal', 'usage_percent' => $usage];
        } elseif ($usage < 90) {
            return ['status' => 'warning', 'message' => 'Disk usage is elevated', 'usage_percent' => $usage];
        } else {
            return ['status' => 'critical', 'message' => 'Disk usage is critical', 'usage_percent' => $usage];
        }
    }

    /**
     * 檢查環境配置健康狀態。
     */
    private function checkEnvironmentHealth(): array
    {
        try {
            $errors = $this->config->validate();

            if (empty($errors)) {
                return ['status' => 'healthy', 'message' => 'Environment configuration is valid'];
            } else {
                return ['status' => 'warning', 'message' => 'Environment configuration has issues', 'errors' => $errors];
            }
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Environment configuration check failed: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查日誌健康狀態。
     */
    private function checkLogHealth(): array
    {
        $logPaths = [
            'app' => '/var/www/html/storage/logs/app.log',
            'error' => '/var/www/html/storage/logs/error.log',
        ];

        $issues = [];

        foreach ($logPaths as $type => $path) {
            if (file_exists($path)) {
                if (!is_writable($path)) {
                    $issues[] = "Log file {$type} is not writable: {$path}";
                }

                $size = filesize($path);
                if ($size > 100 * 1024 * 1024) { // 100MB
                    $issues[] = "Log file {$type} is too large: " . round($size / 1024 / 1024, 1) . 'MB';
                }
            } else {
                $logDir = dirname($path);
                if (!is_dir($logDir) || !is_writable($logDir)) {
                    $issues[] = "Log directory is not writable: {$logDir}";
                }
            }
        }

        if (empty($issues)) {
            return ['status' => 'healthy', 'message' => 'Log system is functioning normally'];
        } else {
            return ['status' => 'warning', 'message' => 'Log system has issues', 'issues' => $issues];
        }
    }
}
