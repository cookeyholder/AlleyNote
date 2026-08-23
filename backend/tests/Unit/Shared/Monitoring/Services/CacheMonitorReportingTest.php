<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring\Services;

use App\Shared\Monitoring\Services\CacheMonitor;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use ReflectionClass;
use Tests\Support\UnitTestCase;

/**
 * CacheMonitor 報表、匯出與清理功能的補充單元測試.
 */
final class CacheMonitorReportingTest extends UnitTestCase
{
    private CacheMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = new CacheMonitor(new NullLogger());
        $this->seedMonitorData();
    }

    /**
     * 建立涵蓋命中、未命中、錯誤與健康狀態的樣本資料。
     */
    private function seedMonitorData(): void
    {
        $this->monitor->recordHit('memory', 'k1', 1.0);
        $this->monitor->recordHit('memory', 'k2', 3.0);
        $this->monitor->recordMiss('memory', 'k3', 0.5);
        $this->monitor->recordOperation('set', 'redis', true, 250.0);
        $this->monitor->recordOperation('get', 'redis', false, 120.0);
        $this->monitor->recordError('redis', 'get', 'timeout');
        $this->monitor->recordHealthStatus('memory', true);
        $this->monitor->recordHealthStatus('redis', false, ['reason' => 'connection refused']);
    }

    #[Test]
    public function getCacheStatsReturnsGlobalSummaryAndPerDriverDetails(): void
    {
        $stats = $this->monitor->getCacheStats(null, '1h');

        $summary = $stats['summary'];
        $this->assertIsArray($summary);
        $this->assertGreaterThan(0, $summary['total_operations']);
        // 命中統計：2 次 recordHit + 1 次 recordMiss = 3 次請求（以浮點累加）
        $this->assertEqualsWithDelta(3.0, $summary['total_cache_requests'], 0.001);
        $this->assertEqualsWithDelta(66.67, $summary['global_hit_rate'], 0.01);

        $drivers = $stats['drivers'];
        $this->assertIsArray($drivers);
        $this->assertArrayHasKey('memory', $drivers);
        $memoryEntry = $drivers['memory'];
        $this->assertIsArray($memoryEntry);
        $hitStats = $memoryEntry['hit_stats'];
        $this->assertIsArray($hitStats);
        $this->assertSame(2, $hitStats['hits']);
        $this->assertArrayHasKey('error_stats', $memoryEntry);
        $this->assertArrayHasKey('health_status', $memoryEntry);
        $this->assertSame('1h', $stats['time_range']);

        // 指定驅動時僅回傳該驅動統計
        $driverOnly = $this->monitor->getCacheStats('redis');
        $redisEntry = $driverOnly['operations'];
        $this->assertIsArray($redisEntry);
        $this->assertSame(2, $redisEntry['total_operations']);
    }

    #[Test]
    public function getHitRateStatsAggregatesGlobalRates(): void
    {
        $stats = $this->monitor->getHitRateStats();

        $this->assertEqualsWithDelta(66.67, $stats['global_hit_rate'], 0.01); // 2 hits / 3 requests
        $this->assertSame(3, $stats['total_requests']);
        $this->assertSame(2, $stats['total_hits']);
        $drivers = $stats['drivers'];
        $this->assertIsArray($drivers);
        $memoryEntry = $drivers['memory'];
        $this->assertIsArray($memoryEntry);
        $this->assertEqualsWithDelta(66.67, $memoryEntry['hit_rate'], 0.01);
        $this->assertGreaterThan(0, $memoryEntry['avg_hit_duration']);
    }

    #[Test]
    public function getDriverPerformanceComparisonSortsByAvgDuration(): void
    {
        $comparison = $this->monitor->getDriverPerformanceComparison();

        $this->assertArrayHasKey('memory', $comparison);
        $this->assertArrayHasKey('redis', $comparison);
        $keys = array_keys($comparison);
        // memory 的平均時間較低，應排在前面
        $this->assertSame('memory', $keys[0]);
        $redisEntry = $comparison['redis'];
        $this->assertIsArray($redisEntry);
        $this->assertEqualsWithDelta(50.0, $redisEntry['success_rate'], 0.01);
        $this->assertArrayHasKey('operations_per_second', $redisEntry);
    }

    #[Test]
    public function getSlowCacheOperationsFiltersByThreshold(): void
    {
        // 預設閾值 100 毫秒：只有 redis 的操作符合
        $slowOps = $this->monitor->getSlowCacheOperations(10, 100);
        $this->assertCount(2, $slowOps);
        $durations = array_column($slowOps, 'duration');
        $this->assertSame(250.0, $durations[0]);
        $this->assertSame(120.0, $durations[1]);

        $limited = $this->monitor->getSlowCacheOperations(1, 100);
        $this->assertCount(1, $limited);
        $firstSlowOp = $limited[0];
        $this->assertIsArray($firstSlowOp);
        $this->assertSame(250.0, $firstSlowOp['duration']);

        // 提高閾值後無結果
        $this->assertSame([], $this->monitor->getSlowCacheOperations(10, 500));
    }

    #[Test]
    public function getCacheCapacityStatsReturnsPlaceholderStructure(): void
    {
        $capacity = $this->monitor->getCacheCapacityStats();
        $this->assertArrayHasKey('drivers', $capacity);
        $this->assertArrayHasKey('note', $capacity);
    }

    #[Test]
    public function getErrorStatsComputesErrorRatePerDriver(): void
    {
        $stats = $this->monitor->getErrorStats();

        $this->assertSame(1, $stats['global_error_count']);
        $drivers = $stats['drivers'];
        $this->assertIsArray($drivers);
        $redisErrors = $drivers['redis'];
        $this->assertIsArray($redisErrors);
        $this->assertSame(1, $redisErrors['total_errors']);
        $this->assertSame(['get' => 1], $redisErrors['errors_by_operation']);
        $this->assertSame(1, $redisErrors['recent_errors_count']);
        // redis 有 2 次操作、1 次錯誤 → 50%
        $this->assertEqualsWithDelta(50.0, $redisErrors['error_rate'], 0.01);
    }

    #[Test]
    public function getHealthOverviewSummarizesHealthyRatio(): void
    {
        $overview = $this->monitor->getHealthOverview();

        $this->assertSame(2, $overview['total_drivers']);
        $this->assertSame(1, $overview['healthy_drivers']);
        $this->assertEqualsWithDelta(50.0, $overview['overall_health'], 0.01);
        $issues = $overview['issues'];
        $this->assertIsArray($issues);
        $this->assertCount(1, $issues);
        $firstIssue = $issues[0];
        $this->assertIsArray($firstIssue);
        $this->assertSame('redis', $firstIssue['driver']);
        $this->assertSame(['reason' => 'connection refused'], $firstIssue['details']);
    }

    #[Test]
    public function cleanupRemovesOldHistoryEntries(): void
    {
        // 以反射將歷史記錄時間戳改為 8 天前
        $reflection = new ReflectionClass($this->monitor);
        $historyProperty = $reflection->getProperty('operationHistory');
        /** @var array<int, array<string, mixed>> $history */
        $history = $historyProperty->getValue($this->monitor);
        foreach ($history as $index => $entry) {
            $entry['timestamp'] = time() - (8 * 24 * 3600);
            $history[$index] = $entry;
        }
        $historyProperty->setValue($this->monitor, $history);

        $cleaned = $this->monitor->cleanup(7);
        // 操作歷史全部過期；錯誤記錄的 recent_errors 也同樣被清空
        $this->assertSame(5, $cleaned);
    }

    #[Test]
    public function exportDataSupportsJsonAndCsvFormats(): void
    {
        $json = $this->monitor->exportData('json', '24h');
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('cache_stats', $decoded);
        $this->assertArrayHasKey('hit_rate_stats', $decoded);
        $this->assertArrayHasKey('health_overview', $decoded);
        $exportInfo = $decoded['export_info'];
        $this->assertIsArray($exportInfo);
        $this->assertSame('json', $exportInfo['format']);
        $this->assertSame('24h', $exportInfo['time_range']);

        $csv = $this->monitor->exportData('csv');
        $this->assertStringContainsString('快取監控報告', $csv);
        $this->assertStringContainsString('匯出時間,', $csv);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的匯出格式: xml');
        $this->monitor->exportData('xml');
    }

    #[Test]
    public function slowOperationLoggingRespectsConfiguredThreshold(): void
    {
        // 以較低的慢操作閾值建立監控器
        $monitor = new CacheMonitor(new NullLogger(), ['slow_operation_threshold' => 5.0]);
        $monitor->recordOperation('get', 'memory', true, 6.0);

        $slowOps = $monitor->getSlowCacheOperations(10, 5);
        $this->assertCount(1, $slowOps);
    }

    #[Test]
    public function recentErrorsAreCappedByConfiguration(): void
    {
        $monitor = new CacheMonitor(new NullLogger(), ['max_recent_errors' => 2]);
        for ($i = 0; $i < 5; $i++) {
            $monitor->recordError('memory', 'get', "error {$i}");
        }

        $stats = $monitor->getErrorStats();
        $drivers = $stats['drivers'];
        $this->assertIsArray($drivers);
        $memoryErrors = $drivers['memory'];
        $this->assertIsArray($memoryErrors);
        // 總錯誤數不受上限影響，僅保留最近的詳細記錄
        $this->assertSame(5, $memoryErrors['total_errors']);
        $this->assertSame(2, $memoryErrors['recent_errors_count']);
    }
}
