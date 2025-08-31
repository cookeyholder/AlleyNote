<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 快取監控服務。
 *
 * 實作快取系統的監控功能，收集效能指標和健康狀態
 */
class CacheMonitor implements CacheMonitorInterface
{
    /** @var array<string, array<string, mixed>> 快取操作統計 */
    private array $operationStats = [];

    /** @var array<string, array<string, mixed>> 快取命中統計 */
    private array $hitStats = [];

    /** @var array<string, array<string, mixed>> 快取錯誤統計 */
    private array $errorStats = [];

    /** @var array<string, array<string, mixed>> 健康狀態記錄 */
    private array $healthRecords = [];

    /** @var array<array<string, mixed>> 操作歷史記錄 */
    private array $operationHistory = [];

    /** @var LoggerInterface 記錄器 */
    private LoggerInterface $logger;

    /** @var array<string, mixed> 設定 */
    private array $config;

    public function __construct(LoggerInterface $logger = null, array $config = [])
    {
        $this->logger = $logger ?? new NullLogger();
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeStats();
    }

    public function recordOperation(
        string $operation,
        string $driver,
        bool $success,
        float $duration,
        array $context = []
    ): void {
        $timestamp = microtime(true);

        // 更新操作統計
        if (!isset($this->operationStats[$driver])) {
            $this->initializeDriverStats($driver);
        }

        $driverStats = &$this->operationStats[$driver];
        $driverStats['operations'][$operation] = ($driverStats['operations'][$operation] ?? 0) + 1;
        $driverStats['total_operations']++;

        if ($success) {
            $driverStats['successful_operations']++;
        } else {
            $driverStats['failed_operations']++;
        }

        // 更新效能統計
        $driverStats['total_duration'] += $duration;
        $driverStats['avg_duration'] = $driverStats['total_duration'] / $driverStats['total_operations'];

        if ($duration > $driverStats['max_duration']) {
            $driverStats['max_duration'] = $duration;
        }

        if ($duration < $driverStats['min_duration'] || $driverStats['min_duration'] === 0) {
            $driverStats['min_duration'] = $duration;
        }

        // 記錄操作歷史（限制數量以避免記憶體過度使用）
        if (count($this->operationHistory) >= $this->config['max_history_size']) {
            array_shift($this->operationHistory);
        }

        $this->operationHistory[] = [
            'timestamp' => $timestamp,
            'operation' => $operation,
            'driver' => $driver,
            'success' => $success,
            'duration' => $duration,
            'context' => $context,
        ];

        // 記錄慢操作
        if ($duration > $this->config['slow_operation_threshold']) {
            $this->logger->warning('慢速快取操作', [
                'operation' => $operation,
                'driver' => $driver,
                'duration' => $duration,
                'threshold' => $this->config['slow_operation_threshold'],
                'context' => $context,
            ]);
        }

        // 記錄失敗操作
        if (!$success) {
            $this->logger->error('快取操作失敗', [
                'operation' => $operation,
                'driver' => $driver,
                'duration' => $duration,
                'context' => $context,
            ]);
        }
    }

    public function recordHit(string $driver, string $key, float $duration): void
    {
        if (!isset($this->hitStats[$driver])) {
            $this->hitStats[$driver] = [
                'hits' => 0,
                'misses' => 0,
                'total_requests' => 0,
                'hit_rate' => 0.0,
                'total_hit_duration' => 0.0,
                'avg_hit_duration' => 0.0,
            ];
        }

        $this->hitStats[$driver]['hits']++;
        $this->hitStats[$driver]['total_requests']++;
        $this->hitStats[$driver]['total_hit_duration'] += $duration;
        $this->hitStats[$driver]['avg_hit_duration'] =
            $this->hitStats[$driver]['total_hit_duration'] / $this->hitStats[$driver]['hits'];

        $this->updateHitRate($driver);
        $this->recordOperation('get', $driver, true, $duration, ['result' => 'hit', 'key' => $key]);
    }

    public function recordMiss(string $driver, string $key, float $duration = 0.0): void
    {
        if (!isset($this->hitStats[$driver])) {
            $this->hitStats[$driver] = [
                'hits' => 0,
                'misses' => 0,
                'total_requests' => 0,
                'hit_rate' => 0.0,
                'total_hit_duration' => 0.0,
                'avg_hit_duration' => 0.0,
            ];
        }

        $this->hitStats[$driver]['misses']++;
        $this->hitStats[$driver]['total_requests']++;

        $this->updateHitRate($driver);
        $this->recordOperation('get', $driver, true, $duration, ['result' => 'miss', 'key' => $key]);
    }

    public function recordError(string $driver, string $operation, string $error, array $context = []): void
    {
        $timestamp = microtime(true);

        if (!isset($this->errorStats[$driver])) {
            $this->errorStats[$driver] = [
                'total_errors' => 0,
                'errors_by_operation' => [],
                'recent_errors' => [],
            ];
        }

        $this->errorStats[$driver]['total_errors']++;
        $this->errorStats[$driver]['errors_by_operation'][$operation] =
            ($this->errorStats[$driver]['errors_by_operation'][$operation] ?? 0) + 1;

        // 保留最近的錯誤記錄
        if (count($this->errorStats[$driver]['recent_errors']) >= $this->config['max_recent_errors']) {
            array_shift($this->errorStats[$driver]['recent_errors']);
        }

        $this->errorStats[$driver]['recent_errors'][] = [
            'timestamp' => $timestamp,
            'operation' => $operation,
            'error' => $error,
            'context' => $context,
        ];

        $this->logger->error('快取錯誤', [
            'driver' => $driver,
            'operation' => $operation,
            'error' => $error,
            'context' => $context,
        ]);
    }

    public function recordHealthStatus(string $driver, bool $healthy, array $details = []): void
    {
        $timestamp = microtime(true);

        $this->healthRecords[$driver] = [
            'healthy' => $healthy,
            'timestamp' => $timestamp,
            'details' => $details,
        ];

        if (!$healthy) {
            $this->logger->warning('快取驅動不健康', [
                'driver' => $driver,
                'details' => $details,
            ]);
        }
    }

    public function getCacheStats(?string $driver = null, ?string $timeRange = null): array
    {
        if ($driver !== null) {
            return $this->getDriverStats($driver);
        }

        $allStats = [];
        foreach ($this->operationStats as $driverName => $stats) {
            $allStats[$driverName] = $this->getDriverStats($driverName);
        }

        return [
            'summary' => $this->calculateGlobalStats(),
            'drivers' => $allStats,
            'generated_at' => date('Y-m-d H:i:s'),
            'time_range' => $timeRange,
        ];
    }

    public function getHitRateStats(?string $timeRange = null): array
    {
        $stats = [];
        $totalRequests = 0;
        $totalHits = 0;

        foreach ($this->hitStats as $driver => $driverStats) {
            $stats[$driver] = [
                'hit_rate' => $driverStats['hit_rate'],
                'hits' => $driverStats['hits'],
                'misses' => $driverStats['misses'],
                'total_requests' => $driverStats['total_requests'],
                'avg_hit_duration' => $driverStats['avg_hit_duration'],
            ];

            $totalRequests += $driverStats['total_requests'];
            $totalHits += $driverStats['hits'];
        }

        $globalHitRate = $totalRequests > 0 ? ($totalHits / $totalRequests) * 100 : 0;

        return [
            'global_hit_rate' => round($globalHitRate, 2),
            'total_requests' => $totalRequests,
            'total_hits' => $totalHits,
            'drivers' => $stats,
            'time_range' => $timeRange,
        ];
    }

    public function getDriverPerformanceComparison(): array
    {
        $comparison = [];

        foreach ($this->operationStats as $driver => $stats) {
            $comparison[$driver] = [
                'avg_duration' => $stats['avg_duration'],
                'min_duration' => $stats['min_duration'],
                'max_duration' => $stats['max_duration'],
                'total_operations' => $stats['total_operations'],
                'success_rate' => $stats['total_operations'] > 0
                    ? ($stats['successful_operations'] / $stats['total_operations']) * 100
                    : 0,
                'operations_per_second' => $this->calculateOperationsPerSecond($driver),
            ];
        }

        // 排序：按照平均響應時間排序
        uasort($comparison, fn($a, $b) => $a['avg_duration'] <=> $b['avg_duration']);

        return $comparison;
    }

    public function getSlowCacheOperations(int $limit = 10, int $thresholdMs = 100): array
    {
        $slowOps = array_filter($this->operationHistory, fn($op) => $op['duration'] >= $thresholdMs);

        // 按持續時間降序排序
        usort($slowOps, fn($a, $b) => $b['duration'] <=> $a['duration']);

        return array_slice($slowOps, 0, $limit);
    }

    public function getCacheCapacityStats(): array
    {
        // 這裡需要與具體的快取驅動整合來取得容量資訊
        // 目前返回模擬資料，實際實作需要整合驅動
        return [
            'drivers' => [],
            'note' => '需要與快取驅動整合來取得實際容量資訊',
        ];
    }

    public function getErrorStats(?string $timeRange = null): array
    {
        $stats = [];
        $totalErrors = 0;

        foreach ($this->errorStats as $driver => $driverErrors) {
            $stats[$driver] = [
                'total_errors' => $driverErrors['total_errors'],
                'errors_by_operation' => $driverErrors['errors_by_operation'],
                'recent_errors_count' => count($driverErrors['recent_errors']),
                'error_rate' => $this->calculateErrorRate($driver),
            ];

            $totalErrors += $driverErrors['total_errors'];
        }

        return [
            'global_error_count' => $totalErrors,
            'drivers' => $stats,
            'time_range' => $timeRange,
        ];
    }

    public function getHealthOverview(): array
    {
        $healthySystems = 0;
        $totalSystems = count($this->healthRecords);
        $issues = [];

        foreach ($this->healthRecords as $driver => $health) {
            if ($health['healthy']) {
                $healthySystems++;
            } else {
                $issues[] = [
                    'driver' => $driver,
                    'details' => $health['details'],
                    'since' => date('Y-m-d H:i:s', (int)$health['timestamp']),
                ];
            }
        }

        return [
            'overall_health' => $totalSystems > 0 ? ($healthySystems / $totalSystems) * 100 : 0,
            'healthy_drivers' => $healthySystems,
            'total_drivers' => $totalSystems,
            'issues' => $issues,
            'last_check' => date('Y-m-d H:i:s'),
        ];
    }

    public function cleanup(int $daysToKeep = 7): int
    {
        $cutoffTime = time() - ($daysToKeep * 24 * 3600);
        $cleaned = 0;

        // 清理操作歷史
        $originalCount = count($this->operationHistory);
        $this->operationHistory = array_filter(
            $this->operationHistory,
            fn($op) => $op['timestamp'] > $cutoffTime
        );
        $cleaned += $originalCount - count($this->operationHistory);

        // 清理錯誤記錄
        foreach ($this->errorStats as $driver => &$errorData) {
            $originalErrorCount = count($errorData['recent_errors']);
            $errorData['recent_errors'] = array_filter(
                $errorData['recent_errors'],
                fn($error) => $error['timestamp'] > $cutoffTime
            );
            $cleaned += $originalErrorCount - count($errorData['recent_errors']);
        }

        $this->logger->info('快取監控資料清理完成', [
            'cleaned_records' => $cleaned,
            'days_kept' => $daysToKeep,
        ]);

        return $cleaned;
    }

    public function getMetrics(): array
    {
        $stats = $this->calculateGlobalStats();

        return [
            'total_hits' => array_sum(array_column($this->hitStats, 'hits')),
            'total_misses' => array_sum(array_column($this->hitStats, 'misses')),
            'total_sets' => $this->getOperationCount('set'),
            'total_deletes' => $this->getOperationCount('delete'),
            'total_errors' => array_sum(array_column($this->errorStats, 'total_errors')),
            'total_operations' => $stats['total_operations'],
            'success_rate' => $stats['success_rate'],
            'avg_duration' => $stats['avg_duration'],
            'hit_rate' => $stats['global_hit_rate'],
        ];
    }

    public function getDriverPerformance(): array
    {
        $performance = [];

        // 獲取所有驅動程式（來自操作統計和錯誤統計）
        $allDrivers = array_unique(array_merge(
            array_keys($this->operationStats),
            array_keys($this->errorStats)
        ));

        foreach ($allDrivers as $driver) {
            $stats = $this->operationStats[$driver] ?? [
                'total_operations' => 0,
                'total_duration' => 0.0,
                'avg_duration' => 0.0,
                'min_duration' => 0.0,
                'max_duration' => 0.0,
                'successful_operations' => 0,
            ];

            $performance[$driver] = [
                'total_operations' => $stats['total_operations'],
                'total_time' => $stats['total_duration'],
                'avg_time' => $stats['avg_duration'],
                'min_time' => $stats['min_duration'],
                'max_time' => $stats['max_duration'],
                'success_rate' => $stats['total_operations'] > 0
                    ? ($stats['successful_operations'] / $stats['total_operations']) * 100
                    : 0,
                'total_errors' => $this->errorStats[$driver]['total_errors'] ?? 0,
            ];
        }

        return $performance;
    }

    public function getHealth(): array
    {
        return $this->getHealthOverview();
    }

    public function reset(): void
    {
        $this->initializeStats();
    }

    public function exportData(string $format = 'json', ?string $timeRange = null): string
    {
        $data = [
            'export_info' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'format' => $format,
                'time_range' => $timeRange,
            ],
            'cache_stats' => $this->getCacheStats(),
            'hit_rate_stats' => $this->getHitRateStats($timeRange),
            'error_stats' => $this->getErrorStats($timeRange),
            'health_overview' => $this->getHealthOverview(),
            'performance_comparison' => $this->getDriverPerformanceComparison(),
        ];

        return match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'csv' => $this->convertToCsv($data),
            default => throw new \InvalidArgumentException("不支援的匯出格式: {$format}"),
        };
    }

    /**
     * 初始化統計資料。
     */
    private function initializeStats(): void
    {
        $this->operationStats = [];
        $this->hitStats = [];
        $this->errorStats = [];
        $this->healthRecords = [];
        $this->operationHistory = [];
    }

    /**
     * 初始化驅動統計資料。
     */
    private function initializeDriverStats(string $driver): void
    {
        $this->operationStats[$driver] = [
            'operations' => [],
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'total_duration' => 0.0,
            'avg_duration' => 0.0,
            'min_duration' => 0.0,
            'max_duration' => 0.0,
        ];
    }

    /**
     * 更新命中率。
     */
    private function updateHitRate(string $driver): void
    {
        $stats = &$this->hitStats[$driver];
        $stats['hit_rate'] = $stats['total_requests'] > 0
            ? ($stats['hits'] / $stats['total_requests']) * 100
            : 0;
    }

    /**
     * 取得單一驅動統計資料。
     */
    private function getDriverStats(string $driver): array
    {
        $operationStats = $this->operationStats[$driver] ?? $this->getEmptyDriverStats();
        $hitStats = $this->hitStats[$driver] ?? $this->getEmptyHitStats();
        $errorStats = $this->errorStats[$driver] ?? $this->getEmptyErrorStats();
        $healthStatus = $this->healthRecords[$driver] ?? ['healthy' => true, 'timestamp' => time(), 'details' => []];

        return [
            'operations' => $operationStats,
            'hit_stats' => $hitStats,
            'error_stats' => $errorStats,
            'health_status' => $healthStatus,
        ];
    }

    /**
     * 計算全域統計資料。
     */
    private function calculateGlobalStats(): array
    {
        $totalOps = 0;
        $totalSuccessful = 0;
        $totalDuration = 0.0;
        $totalRequests = 0;
        $totalHits = 0;
        $totalErrors = 0;

        foreach ($this->operationStats as $stats) {
            $totalOps += $stats['total_operations'];
            $totalSuccessful += $stats['successful_operations'];
            $totalDuration += $stats['total_duration'];
        }

        foreach ($this->hitStats as $stats) {
            $totalRequests += $stats['total_requests'];
            $totalHits += $stats['hits'];
        }

        foreach ($this->errorStats as $stats) {
            $totalErrors += $stats['total_errors'];
        }

        return [
            'total_operations' => $totalOps,
            'success_rate' => $totalOps > 0 ? ($totalSuccessful / $totalOps) * 100 : 0,
            'avg_duration' => $totalOps > 0 ? $totalDuration / $totalOps : 0,
            'total_cache_requests' => $totalRequests,
            'global_hit_rate' => $totalRequests > 0 ? ($totalHits / $totalRequests) * 100 : 0,
            'total_errors' => $totalErrors,
        ];
    }

    /**
     * 計算每秒操作數。
     */
    private function calculateOperationsPerSecond(string $driver): float
    {
        if (empty($this->operationHistory)) {
            return 0.0;
        }

        $driverOps = array_filter($this->operationHistory, fn($op) => $op['driver'] === $driver);

        if (empty($driverOps)) {
            return 0.0;
        }

        $firstOp = reset($driverOps);
        $lastOp = end($driverOps);
        $timeSpan = $lastOp['timestamp'] - $firstOp['timestamp'];

        return $timeSpan > 0 ? count($driverOps) / $timeSpan : 0.0;
    }

    /**
     * 計算錯誤率。
     */
    private function calculateErrorRate(string $driver): float
    {
        $totalOps = $this->operationStats[$driver]['total_operations'] ?? 0;
        $totalErrors = $this->errorStats[$driver]['total_errors'] ?? 0;

        return $totalOps > 0 ? ($totalErrors / $totalOps) * 100 : 0;
    }

    /**
     * 轉換為 CSV 格式。
     */
    private function convertToCsv(array $data): string
    {
        // 簡化的 CSV 實作
        $csv = "快取監控報告\n";
        $csv .= "匯出時間," . $data['export_info']['timestamp'] . "\n\n";

        // 可以根據需要擴展 CSV 格式
        return $csv . "請使用 JSON 格式取得完整資料\n";
    }

    /**
     * 取得空的驅動統計資料。
     */
    private function getEmptyDriverStats(): array
    {
        return [
            'operations' => [],
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'total_duration' => 0.0,
            'avg_duration' => 0.0,
            'min_duration' => 0.0,
            'max_duration' => 0.0,
        ];
    }

    /**
     * 取得空的命中統計資料。
     */
    private function getEmptyHitStats(): array
    {
        return [
            'hits' => 0,
            'misses' => 0,
            'total_requests' => 0,
            'hit_rate' => 0.0,
            'total_hit_duration' => 0.0,
            'avg_hit_duration' => 0.0,
        ];
    }

    /**
     * 取得空的錯誤統計資料。
     */
    private function getEmptyErrorStats(): array
    {
        return [
            'total_errors' => 0,
            'errors_by_operation' => [],
            'recent_errors' => [],
        ];
    }

    /**
     * 取得特定操作的總數。
     */
    private function getOperationCount(string $operation): int
    {
        $count = 0;
        foreach ($this->operationStats as $stats) {
            $count += $stats['operations'][$operation] ?? 0;
        }
        return $count;
    }

    /**
     * 取得預設設定。
     */
    private function getDefaultConfig(): array
    {
        return [
            'slow_operation_threshold' => 100.0, // 毫秒
            'max_history_size' => 1000,
            'max_recent_errors' => 50,
        ];
    }
}
