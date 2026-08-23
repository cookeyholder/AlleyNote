<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring\Services;

use App\Shared\Monitoring\Services\PerformanceMonitorService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use ReflectionClass;
use Tests\Support\UnitTestCase;

/**
 * PerformanceMonitorService 單元測試.
 */
final class PerformanceMonitorServiceTest extends UnitTestCase
{
    private PerformanceMonitorService $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new PerformanceMonitorService(new NullLogger());
    }

    #[Test]
    public function monitoringSessionLifecycleRecordsMetrics(): void
    {
        $this->monitor->setSlowQueryThreshold(5000.0);
        $this->assertSame(5000.0, $this->getThreshold());

        $sessionId = $this->monitor->startMonitoring('db.query', ['table' => 'posts']);
        $this->assertNotSame('', $sessionId);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', $sessionId);

        $statsBeforeEnd = $this->monitor->getPerformanceStats();
        $this->assertSame(1, $statsBeforeEnd['active_sessions']);

        $this->monitor->endMonitoring($sessionId);

        $statsAfterEnd = $this->monitor->getPerformanceStats();
        $this->assertSame(0, $statsAfterEnd['active_sessions']);
        $this->assertGreaterThan(0, $statsAfterEnd['total_metrics']);
    }

    #[Test]
    public function endUnknownSessionIsIgnored(): void
    {
        // 不應拋出例外
        $this->monitor->endMonitoring('nonexistent-session');
        $this->assertSame(0, $this->monitor->getPerformanceStats()['active_sessions']);
    }

    #[Test]
    public function slowOperationsAreRecordedAndSortedByDuration(): void
    {
        $this->monitor->setSlowOperationThreshold(1.0);

        $first = $this->monitor->startMonitoring('slow.op');
        usleep(3000); // 確保超過 1 毫秒閾值
        $this->monitor->endMonitoring($first, ['source' => 'test']);

        $second = $this->monitor->startMonitoring('slow.op');
        usleep(6000); // 更慢的操作，排序時應在前
        $this->monitor->endMonitoring($second);

        $queries = $this->monitor->getSlowQueries(5);
        $this->assertCount(2, $queries);
        $durations = array_column($queries, 'duration');
        $this->assertGreaterThanOrEqual($durations[1], $durations[0]);

        $limited = $this->monitor->getSlowQueries(1);
        $this->assertCount(1, $limited);

        $warnings = $this->monitor->getPerformanceWarnings();
        foreach ($warnings as $warning) {
            $warningType = is_array($warning) ? ($warning['type'] ?? '') : '';
            if ($warningType === 'long_running_operation') {
                $this->fail('剛結束的會話不應被標記為長時間運行');
            }
        }
    }

    #[Test]
    public function recordMetricWithTagsBuildsDeterministicKeys(): void
    {
        $this->monitor->recordMetric('latency', 12.5, 'ms', ['route' => '/api/posts', 'method' => 'GET']);
        $this->monitor->recordMetric('latency', 20.0, 'ms', ['method' => 'GET', 'route' => '/api/posts']);

        $stats = $this->monitor->getPerformanceStats('latency');
        $summary = $stats['metrics_summary'];
        $this->assertIsArray($summary);
        // 標籤順序不影響鍵名（ksort 正規化），兩筆記錄合併為同一鍵
        $keys = array_keys($summary);
        $this->assertCount(1, $keys);
        $entry = $summary[$keys[0]];
        $this->assertIsArray($entry);
        $this->assertSame(2, $entry['count']);
        $this->assertSame(12.5, $entry['min']);
        $this->assertSame(20.0, $entry['max']);
        $this->assertEqualsWithDelta(16.25, $entry['avg'], 0.001);
        $this->assertSame('ms', $entry['unit']);

        // operation_details 針對 "operation.{名稱}." 前綴過濾，此處無對應指標
        $operationDetails = $stats['operation_details'];
        $this->assertIsArray($operationDetails);
        $this->assertSame(0, $operationDetails['metrics_count']);
        $this->assertArrayHasKey('operation', $operationDetails);
    }

    #[Test]
    public function countersIncrementIndependentlyPerTagSet(): void
    {
        $this->monitor->incrementCounter('requests');
        $this->monitor->incrementCounter('requests');
        $this->monitor->incrementCounter('requests', ['status' => '200']);

        $stats = $this->monitor->getPerformanceStats();
        $countersSummary = $stats['counters_summary'];
        $this->assertIsArray($countersSummary);
        $this->assertSame(2, $countersSummary['requests']);
        $this->assertSame(1, $countersSummary['requests[status=200]']);
    }

    #[Test]
    public function gaugesDelegateToMetricsRecorder(): void
    {
        $this->monitor->recordGauge('queue.depth', 42.0);
        $stats = $this->monitor->getPerformanceStats();
        $summary = $stats['metrics_summary'];
        $this->assertIsArray($summary);
        $gaugeEntry = $summary['queue.depth'];
        $this->assertIsArray($gaugeEntry);
        $this->assertSame('gauge', $gaugeEntry['unit']);
    }

    #[Test]
    public function histogramsComputePercentiles(): void
    {
        $values = [10.0, 20.0, 30.0, 40.0, 50.0];
        foreach ($values as $value) {
            $this->monitor->recordHistogram('response.size', $value);
        }

        $stats = $this->monitor->getPerformanceStats();
        $histogramSummary = $stats['histogram_summary'];
        $this->assertIsArray($histogramSummary);
        $bucket = $histogramSummary['response.size'];
        $this->assertIsArray($bucket);
        $this->assertSame(5, $bucket['count']);
        $this->assertSame(10.0, $bucket['min']);
        $this->assertSame(50.0, $bucket['max']);
        $this->assertEqualsWithDelta(30.0, $bucket['avg'], 0.001);
        $this->assertEqualsWithDelta(30.0, $bucket['p50'], 0.001);
        $this->assertEqualsWithDelta(46.0, $bucket['p90'], 0.001);
        $this->assertEqualsWithDelta(48.0, $bucket['p95'], 0.001);
        $this->assertEqualsWithDelta(49.6, $bucket['p99'], 0.001);
    }

    #[Test]
    public function cleanupOldDataRemovesStaleEntriesOnly(): void
    {
        $this->monitor->setSlowQueryThreshold(0.1);
        $session = $this->monitor->startMonitoring('old.op');
        usleep(1000);
        $this->monitor->endMonitoring($session);
        $this->monitor->recordHistogram('h', 1.0);
        $this->monitor->recordGauge('g', 2.0);

        // 剛產生的資料以 7 天保留期清理後應全部保留
        $cleaned = $this->monitor->cleanupOldData(7);
        $this->assertSame(0, $cleaned);

        $stats = $this->monitor->getPerformanceStats();
        $this->assertSame(1, $stats['slow_operations_count']);
        $this->assertGreaterThan(0, $stats['total_metrics']);
        $this->assertGreaterThan(0, $stats['total_histograms']);
    }

    #[Test]
    public function getPerformanceWarningsDetectsLongRunningSessions(): void
    {
        $sessionId = $this->monitor->startMonitoring('long.task');

        // 以反射將會話開始時間改到 60 秒前
        $reflection = new ReflectionClass($this->monitor);
        $sessionsProperty = $reflection->getProperty('activeMonitoringSessions');
        /** @var array<string, array<string, mixed>> $sessions */
        $sessions = $sessionsProperty->getValue($this->monitor);
        $sessions[$sessionId]['start_time'] = microtime(true) - 60;
        $sessionsProperty->setValue($this->monitor, $sessions);

        $warnings = $this->monitor->getPerformanceWarnings();
        $longRunning = array_values(array_filter(
            $warnings,
            static fn(mixed $warning): bool => is_array($warning) && ($warning['type'] ?? '') === 'long_running_operation',
        ));
        $this->assertCount(1, $longRunning);
        $firstWarning = $longRunning[0];
        $this->assertIsArray($firstWarning);
        $this->assertSame('long.task', $firstWarning['operation']);
        $this->assertSame($sessionId, $firstWarning['monitoring_id']);
    }

    /**
     * 以反射讀取慢查詢閾值。
     */
    private function getThreshold(): float
    {
        $reflection = new ReflectionClass($this->monitor);
        $property = $reflection->getProperty('slowQueryThreshold');
        $value = $property->getValue($this->monitor);
        $this->assertIsFloat($value);

        return $value;
    }
}
