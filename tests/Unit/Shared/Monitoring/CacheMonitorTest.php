<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring;

use App\Shared\Monitoring\Services\CacheMonitor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CacheMonitorTest extends TestCase
{
    private CacheMonitor $cacheMonitor;

    protected function setUp(): void
    {
        $this->cacheMonitor = new CacheMonitor(new NullLogger());
    }

    public function test_initial_metrics_are_zero(): void
    {
        $metrics = $this->cacheMonitor->getMetrics();

        $this->assertEquals(0, $metrics['total_hits']);
        $this->assertEquals(0, $metrics['total_misses']);
        $this->assertEquals(0, $metrics['total_sets']);
        $this->assertEquals(0, $metrics['total_deletes']);
        $this->assertEquals(0, $metrics['total_errors']);
    }

    public function test_record_hit_increases_hit_count(): void
    {
        $this->cacheMonitor->recordHit('memory', 'test_key', 1.5);

        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(1, $metrics['total_hits']);

        // 測試驅動效能資料
        $performance = $this->cacheMonitor->getDriverPerformance();
        $this->assertArrayHasKey('memory', $performance);
        $this->assertEquals(1, $performance['memory']['total_operations']);
        $this->assertEquals(1.5, $performance['memory']['total_time']);
    }

    public function test_record_miss_increases_miss_count(): void
    {
        $this->cacheMonitor->recordMiss('file', 'missing_key');

        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(1, $metrics['total_misses']);
    }

    public function test_record_operation_updates_metrics(): void
    {
        $this->cacheMonitor->recordOperation('put', 'redis', true, 2.5, [
            'key' => 'test_key',
            'value_size' => 100,
        ]);

        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(1, $metrics['total_sets']);

        $performance = $this->cacheMonitor->getDriverPerformance();
        $this->assertArrayHasKey('redis', $performance);
        $this->assertEquals(1, $performance['redis']['total_operations']);
        $this->assertEquals(2.5, $performance['redis']['total_time']);
    }

    public function test_record_error_increases_error_count(): void
    {
        $this->cacheMonitor->recordError('redis', 'get', 'Connection failed', [
            'key' => 'test_key',
        ]);

        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(1, $metrics['total_errors']);
    }

    public function test_get_health_returns_correct_status(): void
    {
        // 初始狀態應該是健康的
        $health = $this->cacheMonitor->getHealth();
        $this->assertIsArray($health);

        // 記錄一些成功的操作
        $this->cacheMonitor->recordHit('memory', 'key1', 1.0);
        $this->cacheMonitor->recordOperation('put', 'memory', true, 1.0);

        $health = $this->cacheMonitor->getHealth();
        $this->assertArrayHasKey('memory', $health);
    }

    public function test_reset_clears_all_metrics(): void
    {
        // 記錄一些資料
        $this->cacheMonitor->recordHit('memory', 'key1', 1.0);
        $this->cacheMonitor->recordMiss('memory', 'key2');
        $this->cacheMonitor->recordOperation('put', 'memory', true, 1.5);

        // 確認資料存在
        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(1, $metrics['total_hits']);
        $this->assertEquals(1, $metrics['total_misses']);
        $this->assertEquals(1, $metrics['total_sets']);

        // 重設
        $this->cacheMonitor->reset();

        // 確認資料已清空
        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(0, $metrics['total_hits']);
        $this->assertEquals(0, $metrics['total_misses']);
        $this->assertEquals(0, $metrics['total_sets']);
    }

    public function test_get_driver_performance_with_multiple_operations(): void
    {
        // 記錄多個操作
        $this->cacheMonitor->recordHit('memory', 'key1', 1.0);
        $this->cacheMonitor->recordHit('memory', 'key2', 2.0);
        $this->cacheMonitor->recordOperation('put', 'memory', true, 1.5);

        $performance = $this->cacheMonitor->getDriverPerformance();

        $this->assertArrayHasKey('memory', $performance);
        $memoryPerf = $performance['memory'];

        $this->assertEquals(3, $memoryPerf['total_operations']);
        $this->assertEquals(4.5, $memoryPerf['total_time']); // 1.0 + 2.0 + 1.5
        $this->assertEquals(1.5, $memoryPerf['average_time']); // 4.5 / 3
    }

    public function test_concurrent_operations_tracking(): void
    {
        // 模擬不同驅動的並行操作
        $this->cacheMonitor->recordHit('memory', 'key1', 0.5);
        $this->cacheMonitor->recordHit('redis', 'key1', 1.2);
        $this->cacheMonitor->recordMiss('file', 'key2');

        $performance = $this->cacheMonitor->getDriverPerformance();

        $this->assertArrayHasKey('memory', $performance);
        $this->assertArrayHasKey('redis', $performance);
        $this->assertArrayHasKey('file', $performance);

        $this->assertEquals(1, $performance['memory']['total_operations']);
        $this->assertEquals(1, $performance['redis']['total_operations']);
        $this->assertEquals(1, $performance['file']['total_operations']);
    }

    public function test_error_tracking_by_driver(): void
    {
        $this->cacheMonitor->recordError('redis', 'put', 'Connection timeout', ['key' => 'test']);
        $this->cacheMonitor->recordError('redis', 'get', 'Network error', ['key' => 'test2']);
        $this->cacheMonitor->recordError('file', 'put', 'Permission denied', ['key' => 'test3']);

        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertEquals(3, $metrics['total_errors']);

        $performance = $this->cacheMonitor->getDriverPerformance();

        // Redis 應有 2 個錯誤
        $this->assertArrayHasKey('redis', $performance);
        $this->assertEquals(2, $performance['redis']['total_errors']);

        // File 應有 1 個錯誤
        $this->assertArrayHasKey('file', $performance);
        $this->assertEquals(1, $performance['file']['total_errors']);
    }
}
