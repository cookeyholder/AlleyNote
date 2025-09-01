<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Services;

use App\Shared\Monitoring\Contracts\PerformanceMonitorInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * 效能監控服務實作。
 *
 * 提供詳細的應用程式效能監控功能，包含操作追蹤、指標收集和效能分析
 */
class PerformanceMonitorService implements PerformanceMonitorInterface
{
    /** @var array<string, array> 進行中的監控會話 */
    private array $activeMonitoringSessions = [];

    /** @var array<string, array<int, array<string, mixed>>> 效能指標暫存 */
    private array $metrics = [];

    /** @var array<string, int> 計數器暫存 */
    private array $counters = [];

    /** @var array<string, array> 直方圖資料暫存 */
    private array $histograms = [];

    /** @var array<array> 慢查詢記錄 */
    private array $slowQueries = [];

    /** @var float 慢查詢閾值（毫秒） */
    private float $slowQueryThreshold = 1000.0;

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * 設定慢查詢閾值。
     */
    public function setSlowQueryThreshold(float $threshold): void
    {
        $this->slowQueryThreshold = $threshold;
    }

    /**
     * 設定慢操作閾值（別名方法）。
     */
    public function setSlowOperationThreshold(float $threshold): void
    {
        $this->setSlowQueryThreshold($threshold);
    }

    /**
     * 開始監控一個操作。
     */
    public function startMonitoring(string $operation, array $context = []): string
    {
        $monitoringId = Uuid::uuid4()->toString();

        $this->activeMonitoringSessions[$monitoringId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'created_at' => time(),
        ];

        $this->logger->debug("Started monitoring operation: {$operation}", [
            'monitoring_id' => $monitoringId,
            'context' => $context,
        ]);

        return $monitoringId;
    }

    /**
     * 結束監控一個操作。
     */
    public function endMonitoring(string $monitoringId, array $context = []): void
    {
        if (!isset($this->activeMonitoringSessions[$monitoringId])) {
            $this->logger->warning("Attempted to end non-existent monitoring session: {$monitoringId}");

            return;
        }

        $session = $this->activeMonitoringSessions[$monitoringId];

        if (!is_array($session)) {
            $this->logger->warning('Invalid monitoring session data', ['monitoring_id' => $monitoringId]);

            return;
        }

        $sessionStartTime = $session['start_time'] ?? 0;
        $startTime = is_numeric($sessionStartTime) ? (float) $sessionStartTime : microtime(true);

        $sessionStartMemory = $session['start_memory'] ?? 0;
        $startMemory = is_numeric($sessionStartMemory) ? (int) $sessionStartMemory : memory_get_usage(true);

        $operationValue = $session['operation'] ?? '';
        $operation = is_string($operationValue) ? $operationValue : 'unknown';

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = ($endTime - $startTime) * 1000; // 轉換為毫秒
        $memoryDiff = $endMemory - $startMemory;

        // 記錄效能指標
        $this->recordMetric("operation.{$operation}.duration", $duration, 'ms', [
            'operation' => $operation,
        ]);

        $this->recordMetric("operation.{$operation}.memory_delta", $memoryDiff, 'bytes', [
            'operation' => $operation,
        ]);

        // 檢查是否為慢操作
        if ($duration > $this->slowQueryThreshold) {
            $sessionContext = is_array($session['context'] ?? null) ? $session['context'] : [];
            $mergedContext = array_merge($sessionContext, $context);
            $this->recordSlowOperation($operation, $duration, $mergedContext);
        }

        // 記錄詳細資訊
        $this->logger->info("Operation completed: {$operation}", [
            'operation' => $operation,
            'duration_ms' => (float) $duration,
            'status' => 'success',
            'memory_peak' => memory_get_peak_usage(true),
            'context' => $context,
        ]);
    }

    /**
     * 記錄一個性能指標。
     */
    public function recordMetric(string $name, float $value, string $unit = 'ms', array $tags = []): void
    {
        $metricKey = $this->buildMetricKey($name, $tags);

        if (!isset($this->metrics[$metricKey])) {
            $this->metrics[$metricKey] = [];
        }

        $metricsForKey = $this->metrics[$metricKey];
        assert(is_array($metricsForKey));
        $metricsForKey[] = [
            'value' => $value,
            'unit' => $unit,
            'tags' => $tags,
            'timestamp' => microtime(true),
        ];
        $this->metrics[$metricKey] = $metricsForKey;

        $this->logger->debug("Recorded metric: {$name}", [
            'value' => $value,
            'unit' => $unit,
            'tags' => $tags,
        ]);
    }

    /**
     * 記錄一個計數器指標。
     */
    public function incrementCounter(string $name, array $tags = []): void
    {
        $counterKey = $this->buildMetricKey($name, $tags);

        if (!isset($this->counters[$counterKey])) {
            $this->counters[$counterKey] = 0;
        }

        $currentCounter = $this->counters[$counterKey];
        assert(is_int($currentCounter) || is_float($currentCounter));
        $this->counters[$counterKey] = $currentCounter + 1;

        $this->logger->debug("Incremented counter: {$name}", [
            'current_value' => $this->counters[$counterKey],
            'tags' => $tags,
        ]);
    }

    /**
     * 記錄一個計量表指標。
     */
    public function recordGauge(string $name, float $value, array $tags = []): void
    {
        $this->recordMetric($name, $value, 'gauge', $tags);
    }

    /**
     * 記錄一個直方圖指標。
     */
    public function recordHistogram(string $name, float $value, array $tags = []): void
    {
        $histogramKey = $this->buildMetricKey($name, $tags);

        if (!isset($this->histograms[$histogramKey])) {
            $this->histograms[$histogramKey] = [];
        }

        $this->histograms[$histogramKey][] = [
            'value' => $value,
            'timestamp' => microtime(true),
        ];

        $this->logger->debug("Recorded histogram value: {$name}", [
            'value' => $value,
            'tags' => $tags,
        ]);
    }

    /**
     * 取得效能統計資料。
     */
    public function getPerformanceStats(?string $operation = null): array
    {
        $stats = [
            'active_sessions' => count($this->activeMonitoringSessions),
            'total_metrics' => count($this->metrics),
            'total_counters' => count($this->counters),
            'total_histograms' => count($this->histograms),
            'slow_operations_count' => count($this->slowQueries),
        ];

        // 如果指定了操作，返回該操作的詳細統計
        if ($operation !== null) {
            $stats['operation_details'] = $this->getOperationStats($operation);
        }

        // 計算整體統計
        $stats['metrics_summary'] = $this->calculateMetricsSummary();
        $stats['counters_summary'] = $this->counters;
        $stats['histogram_summary'] = $this->calculateHistogramSummary();

        return $stats;
    }

    /**
     * 取得慢查詢記錄。
     */
    public function getSlowQueries(int $limit = 10): array
    {
        // 按持續時間排序
        $sorted = $this->slowQueries;
        usort($sorted, fn($a, $b) => $b['duration'] <=> $a['duration']);

        return array_slice($sorted, 0, $limit);
    }

    /**
     * 取得效能警告。
     */
    public function getPerformanceWarnings(): array
    {
        $warnings = [];

        // 檢查活躍會話數量
        if (count($this->activeMonitoringSessions) > 100) {
            $warnings[] = [
                'type' => 'high_active_sessions',
                'message' => 'Too many active monitoring sessions',
                'count' => count($this->activeMonitoringSessions),
                'severity' => 'warning',
            ];
        }

        // 檢查記憶體使用
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($memoryLimit > 0 && $memoryUsage > $memoryLimit * 0.8) {
            $warnings[] = [
                'type' => 'high_memory_usage',
                'message' => 'Memory usage is approaching limit',
                'usage_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimit,
                'usage_percent' => round(($memoryUsage / $memoryLimit) * 100, 1),
                'severity' => 'critical',
            ];
        }

        // 檢查長時間運行的操作
        $currentTime = microtime(true);
        foreach ($this->activeMonitoringSessions as $id => $session) {
            $sessionStartTime = $session['start_time'] ?? $currentTime;
            $duration = is_numeric($sessionStartTime)
                ? ($currentTime - (float) $sessionStartTime) * 1000
                : 0;
            if ($duration > 30000) { // 30 秒
                $warnings[] = [
                    'type' => 'long_running_operation',
                    'message' => 'Operation has been running for a long time',
                    'monitoring_id' => $id,
                    'operation' => $session['operation'],
                    'duration_ms' => round($duration, 2),
                    'severity' => 'warning',
                ];
            }
        }

        return $warnings;
    }

    /**
     * 清除舊的效能資料。
     */
    public function cleanupOldData(int $daysToKeep = 7): int
    {
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $cleanedCount = 0;

        // 清除舊的慢查詢記錄
        $originalCount = count($this->slowQueries);
        $this->slowQueries = array_filter(
            $this->slowQueries,
            fn($query) => $query['timestamp'] > $cutoffTime,
        );
        $cleanedCount += $originalCount - count($this->slowQueries);

        // 清除舊的指標
        foreach ($this->metrics as $key => $metricData) {
            $originalMetricCount = count($metricData);
            $this->metrics[$key] = array_filter(
                $metricData,
                fn($metric) => $metric['timestamp'] > $cutoffTime,
            );
            $cleanedCount += $originalMetricCount - count($this->metrics[$key]);

            // 如果指標陣列為空，移除整個鍵
            if (empty($this->metrics[$key])) {
                unset($this->metrics[$key]);
            }
        }

        // 清除舊的直方圖資料
        foreach ($this->histograms as $key => $histogramData) {
            $originalHistogramCount = count($histogramData);
            $this->histograms[$key] = array_filter(
                $histogramData,
                fn($histogram) => is_array($histogram) && isset($histogram['timestamp']) && is_numeric($histogram['timestamp']) && $histogram['timestamp'] > $cutoffTime,
            );
            $cleanedCount += $originalHistogramCount - count($this->histograms[$key]);

            // 如果直方圖陣列為空，移除整個鍵
            if (empty($this->histograms[$key])) {
                unset($this->histograms[$key]);
            }
        }

        $this->logger->info('Performance data cleanup completed', [
            'days_kept' => $daysToKeep,
            'items_cleaned' => $cleanedCount,
        ]);

        return $cleanedCount;
    }

    // ===== 私有方法 =====

    /**
     * 建立指標鍵名。
     */
    private function buildMetricKey(string $name, array $tags): string
    {
        if (empty($tags)) {
            return $name;
        }

        ksort($tags);
        $tagString = implode(',', array_map(
            fn($k, $v) => $k . '=' . (is_scalar($v) ? (string) $v : 'complex'),
            array_keys($tags),
            array_values($tags),
        ));

        return "{$name}[{$tagString}]";
    }

    /**
     * 記錄慢操作。
     */
    private function recordSlowOperation(string $operation, float $duration, array $context): void
    {
        $this->slowQueries[] = [
            'operation' => $operation,
            'duration' => $duration,
            'context' => $context,
            'timestamp' => time(),
        ];

        $this->logger->warning("Slow operation detected: {$operation}", [
            'duration_ms' => round($duration, 2),
            'threshold_ms' => (float) $this->slowQueryThreshold,
            'context' => $context,
        ]);
    }

    /**
     * 取得特定操作的統計。
     */
    private function getOperationStats(string $operation): array
    {
        $operationMetrics = [];

        foreach ($this->metrics as $key => $metricData) {
            if (strpos($key, "operation.{$operation}.") === 0) {
                $operationMetrics[$key] = $metricData;
            }
        }

        $stats = [
            'operation' => $operation,
            'metrics_count' => count($operationMetrics),
            'active_sessions' => 0,
        ];

        // 計算活躍會話
        foreach ($this->activeMonitoringSessions as $session) {
            if ($session['operation'] === $operation) {
                $stats['active_sessions']++;
            }
        }

        // 計算統計摘要
        if (!empty($operationMetrics)) {
            $stats['metrics_summary'] = $this->calculateMetricsSummary($operationMetrics);
        }

        return $stats;
    }

    /**
     * 計算指標摘要。
     */
    private function calculateMetricsSummary(?array $metricsSubset = null): array
    {
        $metrics = $metricsSubset ?? $this->metrics;
        $summary = [];

        foreach ($metrics as $key => $metricData) {
            $values = is_array($metricData) ? array_column($metricData, 'value') : [];

            if (!empty($values)) {
                $summary[$key] = [
                    'count' => count($values),
                    'min' => min($values),
                    'max' => max($values),
                    'avg' => array_sum($values) / count($values),
                    'total' => array_sum($values),
                    'unit' => (is_array($metricData) && isset($metricData[0]) && is_array($metricData[0]) && isset($metricData[0]['unit']))
                        ? $metricData[0]['unit']
                        : 'unknown',
                ];
            }
        }

        return $summary;
    }

    /**
     * 計算直方圖摘要。
     */
    private function calculateHistogramSummary(): array
    {
        $summary = [];

        foreach ($this->histograms as $key => $histogramData) {
            $values = array_column($histogramData, 'value');
            sort($values);

            if (!empty($values)) {
                $count = count($values);
                $summary[$key] = [
                    'count' => $count,
                    'min' => min($values),
                    'max' => max($values),
                    'avg' => array_sum($values) / $count,
                    'p50' => $this->percentile($values, 50),
                    'p90' => $this->percentile($values, 90),
                    'p95' => $this->percentile($values, 95),
                    'p99' => $this->percentile($values, 99),
                ];
            }
        }

        return $summary;
    }

    /**
     * 計算百分位數。
     */
    private function percentile(array $values, int $percentile): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            $value = $values[$lower] ?? 0;

            return is_numeric($value) ? (float) $value : 0.0;
        }

        $lowerValue = $values[$lower] ?? 0;
        $upperValue = $values[$upper] ?? 0;

        if (!is_numeric($lowerValue) || !is_numeric($upperValue)) {
            return 0.0;
        }

        $weight = $index - $lower;

        return (float) $lowerValue * (1 - $weight) + (float) $upperValue * $weight;
    }

    /**
     * 解析記憶體限制。
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);

        if ($memoryLimit === '-1') {
            return 0;
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
}
