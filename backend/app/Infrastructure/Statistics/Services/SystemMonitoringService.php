<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\SystemMonitoringServiceInterface;
use DateTimeImmutable;
use PDO;
use Predis\ClientInterface as RedisClientInterface;
use Throwable;

/**
 * 主機與系統狀態監控服務實作.
 *
 * 收集主機 CPU、記憶體、磁碟、PHP Runtime、SQLite 資料庫及 Redis 快取 health 狀態.
 */
class SystemMonitoringService implements SystemMonitoringServiceInterface
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?RedisClientInterface $redis = null,
    ) {}

    public function getSystemHealthStatus(): array
    {
        $cpu = $this->getCpuMetrics();
        $memory = $this->getMemoryMetrics();
        $disk = $this->getDiskMetrics();
        $database = $this->getDatabaseMetrics();
        $cache = $this->getCacheMetrics();
        $phpRuntime = $this->getPhpRuntimeMetrics();
        $system = $this->getSystemMetrics();
        $activitySummary = $this->getActivitySummaryMetrics();

        $container = $this->getContainerMetrics();

        return [
            'cpu'              => $cpu,
            'memory'           => $memory,
            'disk'             => $disk,
            'database'         => $database,
            'cache'            => $cache,
            'php_runtime'      => $phpRuntime,
            'system'           => $system,
            'activity_summary' => $activitySummary,
            'container'        => $container,
            'timestamp'        => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得 CPU 負載與核心數資訊.
     *
     * @return array<string, mixed>
     */
    private function getCpuMetrics(): array
    {
        $loadAvg = [0.0, 0.0, 0.0];
        if (function_exists('sys_getloadavg')) {
            $sysLoad = sys_getloadavg();
            if (is_array($sysLoad) && count($sysLoad) >= 3) {
                $loadAvg = [
                    round((float) $sysLoad[0], 2),
                    round((float) $sysLoad[1], 2),
                    round((float) $sysLoad[2], 2),
                ];
            }
        } elseif (is_readable('/proc/loadavg')) {
            $content = file_get_contents('/proc/loadavg');
            if ($content !== false) {
                $parts = explode(' ', trim($content));
                if (count($parts) >= 3) {
                    $loadAvg = [
                        round((float) $parts[0], 2),
                        round((float) $parts[1], 2),
                        round((float) $parts[2], 2),
                    ];
                }
            }
        }

        $cpuCores = 1;
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $cpuCores = max(1, substr_count($cpuinfo, 'processor'));
            }
        }

        // 計算估算 CPU 使用率百分比
        $usagePercent = min(100.0, round(($loadAvg[0] / $cpuCores) * 100, 1));

        return [
            'load_average'  => $loadAvg,
            'cores'         => $cpuCores,
            'usage_percent' => $usagePercent,
            'status'        => $usagePercent > 85 ? 'danger' : ($usagePercent > 70 ? 'warning' : 'healthy'),
        ];
    }

    /**
     * 取得記憶體使用數據.
     *
     * @return array<string, mixed>
     */
    private function getMemoryMetrics(): array
    {
        $totalMemory = 0;
        $freeMemory = 0;
        $buffers = 0;
        $cached = 0;
        $hasAvailable = false;

        if (is_readable('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo !== false) {
                if (preg_match('/MemTotal:\s+(\d+)\s+kB/i', $meminfo, $matches)) {
                    $totalMemory = (int) $matches[1] * 1024;
                }
                if (preg_match('/MemAvailable:\s+(\d+)\s+kB/i', $meminfo, $matches)) {
                    $freeMemory = (int) $matches[1] * 1024;
                    $hasAvailable = true;
                } elseif (preg_match('/MemFree:\s+(\d+)\s+kB/i', $meminfo, $matches)) {
                    $freeMemory = (int) $matches[1] * 1024;
                }
                if (preg_match('/Buffers:\s+(\d+)\s+kB/i', $meminfo, $matches)) {
                    $buffers = (int) $matches[1] * 1024;
                }
                if (preg_match('/^Cached:\s+(\d+)\s+kB/im', $meminfo, $matches)) {
                    $cached = (int) $matches[1] * 1024;
                }
            }
        }

        if ($totalMemory > 0) {
            if ($hasAvailable) {
                $usedMemory = max(0, $totalMemory - $freeMemory);
            } else {
                $realFree = $freeMemory + $buffers + $cached;
                $usedMemory = max(0, $totalMemory - $realFree);
            }
        } else {
            // 回退使用 PHP 記憶體限制與記憶體使用量估算
            $phpLimitStr = ini_get('memory_limit');
            $phpLimit = is_string($phpLimitStr) ? $this->parseSize($phpLimitStr) : 0;
            $totalMemory = $phpLimit > 0 ? $phpLimit : 512 * 1024 * 1024;
            $usedMemory = memory_get_usage(true);
        }

        $usagePercent = round(($usedMemory / $totalMemory) * 100, 1);

        return [
            'total_bytes'      => $totalMemory,
            'used_bytes'       => $usedMemory,
            'free_bytes'       => max(0, $totalMemory - $usedMemory),
            'usage_percent'    => $usagePercent,
            'php_used_bytes'   => memory_get_usage(true),
            'php_peak_bytes'   => memory_get_peak_usage(true),
            'php_memory_limit' => (string) ini_get('memory_limit'),
            'status'           => $usagePercent > 90 ? 'danger' : ($usagePercent > 75 ? 'warning' : 'healthy'),
        ];
    }

    /**
     * 取得磁碟空間使用數據.
     *
     * @return array<string, mixed>
     */
    private function getDiskMetrics(): array
    {
        $path = '/var/www/html';
        if (!is_dir($path)) {
            $path = dirname(__DIR__, 4);
        }

        $totalSpace = @disk_total_space($path);
        $freeSpace = @disk_free_space($path);

        if ($totalSpace === false || $totalSpace <= 0.0) {
            $totalSpace = 100.0 * 1024 * 1024 * 1024; // 100 GB fallback
            $freeSpace = 70.0 * 1024 * 1024 * 1024;
        }

        $freeSpaceVal = is_float($freeSpace) ? $freeSpace : 0.0;
        $usedSpace = max(0, (int) $totalSpace - (int) $freeSpaceVal);
        $usagePercent = round(($usedSpace / (float) $totalSpace) * 100, 1);

        return [
            'total_bytes'   => (int) $totalSpace,
            'used_bytes'    => $usedSpace,
            'free_bytes'    => (int) $freeSpaceVal,
            'usage_percent' => $usagePercent,
            'path'          => $path,
            'status'        => $usagePercent > 90 ? 'danger' : ($usagePercent > 80 ? 'warning' : 'healthy'),
        ];
    }

    /**
     * 取得 SQLite 資料庫健康度與統計.
     *
     * @return array<string, mixed>
     */
    private function getDatabaseMetrics(): array
    {
        $dbPath = dirname(__DIR__, 4) . '/database/alleynote.sqlite3';
        $fileSize = is_file($dbPath) ? (int) filesize($dbPath) : 0;

        $tableCount = 0;
        $journalMode = 'unknown';
        $integrityStatus = 'unknown';
        $totalRecords = 0;

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
            if ($stmt !== false) {
                $tableCount = (int) $stmt->fetchColumn();
            }

            $stmtMode = $this->db->query('PRAGMA journal_mode;');
            if ($stmtMode !== false) {
                $journalMode = (string) $stmtMode->fetchColumn();
            }

            $stmtCheck = $this->db->query('PRAGMA integrity_check;');
            if ($stmtCheck !== false) {
                $integrityStatus = (string) $stmtCheck->fetchColumn();
            }

            // 統計關鍵資料表總筆數
            $tables = ['users', 'posts', 'user_activity_logs', 'post_views', 'comments', 'tags'];
            foreach ($tables as $table) {
                try {
                    $cntStmt = $this->db->query("SELECT COUNT(*) FROM {$table}");
                    if ($cntStmt !== false) {
                        $totalRecords += (int) $cntStmt->fetchColumn();
                    }
                } catch (Throwable) {
                    // Ignore missing table during count
                }
            }
        } catch (Throwable) {
            // Handled gracefully
        }

        return [
            'driver'           => 'sqlite',
            'database_path'    => 'database/alleynote.sqlite3',
            'file_size_bytes'  => $fileSize,
            'table_count'      => $tableCount,
            'total_records'    => $totalRecords,
            'journal_mode'     => strtoupper($journalMode),
            'integrity_status' => $integrityStatus,
            'status'           => strtolower($integrityStatus) === 'ok' ? 'healthy' : 'warning',
        ];
    }

    /**
     * 取得 Redis 快取與 Session 狀態.
     *
     * @return array<string, mixed>
     */
    private function getCacheMetrics(): array
    {
        $redisConnected = false;
        $usedMemory = 0;
        $uptimeDays = 0.0;
        $activeSessions = 0;

        if ($this->redis !== null) {
            try {
                /** @var mixed $info */
                $info = $this->redis->info();
                $redisConnected = true;
                if (is_array($info)) {
                    /** @var mixed $memSection */
                    $memSection = $info['Memory'] ?? $info;
                    if (is_array($memSection) && isset($memSection['used_memory']) && is_numeric($memSection['used_memory'])) {
                        $usedMemory = (int) $memSection['used_memory'];
                    }

                    /** @var mixed $serverSection */
                    $serverSection = $info['Server'] ?? $info;
                    if (is_array($serverSection) && isset($serverSection['uptime_in_seconds']) && is_numeric($serverSection['uptime_in_seconds'])) {
                        $uptimeSeconds = (int) $serverSection['uptime_in_seconds'];
                        $uptimeDays = round($uptimeSeconds / 86400, 1);
                    }
                }
            } catch (Throwable) {
                $redisConnected = false;
            }
        }

        // 查詢活躍 Session (refresh_tokens)
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM refresh_tokens WHERE expires_at > DATETIME('now')");
            if ($stmt !== false) {
                $activeSessions = (int) $stmt->fetchColumn();
            }
        } catch (Throwable) {
            $activeSessions = 0;
        }

        return [
            'redis_connected'   => $redisConnected,
            'redis_used_memory' => $usedMemory,
            'redis_uptime_days' => $uptimeDays,
            'active_sessions'   => $activeSessions,
            'status'            => $redisConnected ? 'healthy' : 'warning',
        ];
    }

    /**
     * 取得 PHP Runtime 指標.
     *
     * @return array<string, mixed>
     */
    private function getPhpRuntimeMetrics(): array
    {
        $opcacheEnabled = false;
        $opcacheHitRate = 0.0;

        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            if (is_array($status) && isset($status['opcache_enabled']) && $status['opcache_enabled'] === true) {
                $opcacheEnabled = true;
                if (isset($status['opcache_statistics']) && is_array($status['opcache_statistics']) && isset($status['opcache_statistics']['opcache_hit_rate']) && is_numeric($status['opcache_statistics']['opcache_hit_rate'])) {
                    $opcacheHitRate = round((float) $status['opcache_statistics']['opcache_hit_rate'], 1);
                }
            }
        }

        return [
            'version'             => PHP_VERSION,
            'sapi'                => php_sapi_name(),
            'opcache_enabled'     => $opcacheEnabled,
            'opcache_hit_rate'    => $opcacheHitRate,
            'memory_limit'        => (string) ini_get('memory_limit'),
            'max_execution_time'  => (int) ini_get('max_execution_time'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
        ];
    }

    /**
     * 取得系統整體運作時間與 OS 數據.
     *
     * @return array<string, mixed>
     */
    private function getSystemMetrics(): array
    {
        $uptimeSeconds = 0;
        if (is_readable('/proc/uptime')) {
            $content = file_get_contents('/proc/uptime');
            if ($content !== false) {
                $parts = explode(' ', trim($content));
                $uptimeSeconds = (int) (float) ($parts[0] ?? 0);
            }
        }

        $days = (int) floor($uptimeSeconds / 86400);
        $hours = (int) floor(($uptimeSeconds % 86400) / 3600);
        $minutes = (int) floor(($uptimeSeconds % 3600) / 60);

        $uptimeFormatted = sprintf('%d 天 %d 小時 %d 分鐘', $days, $hours, $minutes);

        return [
            'os'               => PHP_OS_FAMILY,
            'hostname'         => gethostname() !== false ? gethostname() : 'localhost',
            'kernel'           => php_uname('r'),
            'uptime_seconds'   => $uptimeSeconds,
            'uptime_formatted' => $uptimeFormatted,
            'app_env'          => $_ENV['APP_ENV'] ?? 'development',
        ];
    }

    /**
     * 取得今日應用程式活動統計摘要.
     *
     * @return array<string, mixed>
     */
    private function getActivitySummaryMetrics(): array
    {
        $todayActivities = 0;
        $loginSuccessToday = 0;
        $loginFailureToday = 0;

        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN action_type = 'auth.login' AND status = 'success' THEN 1 ELSE 0 END) as login_success,
                    SUM(CASE WHEN (action_type = 'auth.login_failed' OR action_type = 'auth.login') AND status != 'success' THEN 1 ELSE 0 END) as login_failure
                FROM user_activity_logs
                WHERE created_at >= DATETIME('now', '-1 day')
            ");

            if ($stmt !== false) {
                /** @var mixed $row */
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($row)) {
                    $todayActivities = isset($row['total']) && is_numeric($row['total']) ? (int) $row['total'] : 0;
                    $loginSuccessToday = isset($row['login_success']) && is_numeric($row['login_success']) ? (int) $row['login_success'] : 0;
                    $loginFailureToday = isset($row['login_failure']) && is_numeric($row['login_failure']) ? (int) $row['login_failure'] : 0;
                }
            }
        } catch (Throwable) {
            // Ignore errors if table missing
        }

        return [
            'total_activities_24h' => $todayActivities,
            'login_success_24h'    => $loginSuccessToday,
            'login_failure_24h'    => $loginFailureToday,
        ];
    }

    /**
     * 解析位元組字串 (如 '256M', '2G').
     */
    private function parseSize(string $size): int
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $cleanSize = (float) preg_replace('/[^0-9\.]/', '', $size);

        if (is_string($unit) && strlen($unit) > 0) {
            $index = stripos('bkmgtpezy', $unit[0]);
            if ($index !== false) {
                return (int) round($cleanSize * (1024 ** $index));
            }
        }

        return (int) round($cleanSize);
    }

    /**
     * 取得 Docker 容器自身資源與配額資訊 (cgroups).
     *
     * @return array<string, mixed>
     */
    private function getContainerMetrics(): array
    {
        $isContainer = file_exists('/.dockerenv') || file_exists('/proc/1/cgroup');

        $memUsed = 0;
        $memLimit = 0; // 0 = unlimited
        $memLimitFormatted = '無限制 (共享 Host)';
        $memUsedFormatted = '0 B';
        $memUsagePercent = 0.0;

        $cpuQuotaCores = null;
        $cpuQuotaFormatted = '無限制 (共享 Host CPU)';

        if ($isContainer) {
            // 1. 記憶體使用量 (cgroups v2 或 v1)
            $cgroupMemCurrent = null;
            if (is_readable('/sys/fs/cgroup/memory.current')) {
                $cgroupMemCurrent = trim((string) file_get_contents('/sys/fs/cgroup/memory.current'));
            } elseif (is_readable('/sys/fs/cgroup/memory/memory.usage_in_bytes')) {
                $cgroupMemCurrent = trim((string) file_get_contents('/sys/fs/cgroup/memory/memory.usage_in_bytes'));
            }

            if ($cgroupMemCurrent !== null && is_numeric($cgroupMemCurrent)) {
                $memUsed = (int) $cgroupMemCurrent;
                $memUsedFormatted = $this->formatBytes($memUsed);
            }

            // 2. 記憶體配額上限 (cgroups v2 或 v1)
            $cgroupMemMax = null;
            if (is_readable('/sys/fs/cgroup/memory.max')) {
                $cgroupMemMax = trim((string) file_get_contents('/sys/fs/cgroup/memory.max'));
            } elseif (is_readable('/sys/fs/cgroup/memory/memory.limit_in_bytes')) {
                $cgroupMemMax = trim((string) file_get_contents('/sys/fs/cgroup/memory/memory.limit_in_bytes'));
            }

            if ($cgroupMemMax !== null && $cgroupMemMax !== 'max' && is_numeric($cgroupMemMax)) {
                $limitVal = (int) $cgroupMemMax;
                if ($limitVal < 100 * 1024 * 1024 * 1024 * 1024) {
                    $memLimit = $limitVal;
                    $memLimitFormatted = $this->formatBytes($memLimit);
                    if ($memLimit > 0) {
                        $memUsagePercent = round(($memUsed / $memLimit) * 100, 1);
                    }
                }
            }

            // 3. CPU 限額 (cgroups v2 或 v1)
            if (is_readable('/sys/fs/cgroup/cpu.max')) {
                $cpuMaxContent = trim((string) file_get_contents('/sys/fs/cgroup/cpu.max'));
                $parts = explode(' ', $cpuMaxContent);
                if (count($parts) >= 2 && $parts[0] !== 'max' && is_numeric($parts[0]) && is_numeric($parts[1]) && (int) $parts[1] > 0) {
                    $quota = (int) $parts[0];
                    $period = (int) $parts[1];
                    $cpuQuotaCores = round($quota / $period, 2);
                    $cpuQuotaFormatted = "限額 {$cpuQuotaCores} 核";
                }
            } elseif (is_readable('/sys/fs/cgroup/cpu/cpu.cfs_quota_us') && is_readable('/sys/fs/cgroup/cpu/cpu.cfs_period_us')) {
                $quotaStr = trim((string) file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us'));
                $periodStr = trim((string) file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us'));
                if (is_numeric($quotaStr) && is_numeric($periodStr) && (int) $quotaStr > 0 && (int) $periodStr > 0) {
                    $cpuQuotaCores = round(((int) $quotaStr) / ((int) $periodStr), 2);
                    $cpuQuotaFormatted = "限額 {$cpuQuotaCores} 核";
                }
            }
        }

        return [
            'is_container' => $isContainer,
            'memory'       => [
                'used_bytes'      => $memUsed,
                'used_formatted'  => $memUsedFormatted,
                'limit_bytes'     => $memLimit,
                'limit_formatted' => $memLimitFormatted,
                'usage_percent'   => $memUsagePercent,
            ],
            'cpu' => [
                'quota_cores'     => $cpuQuotaCores,
                'quota_formatted' => $cpuQuotaFormatted,
            ],
        ];
    }

    /**
     * 格式化位元組數字為可讀字串.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $val = $bytes / (1024 ** $pow);

        return round($val, $precision) . ' ' . $units[$pow];
    }
}
