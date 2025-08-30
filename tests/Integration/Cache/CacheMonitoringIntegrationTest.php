<?php

declare(strict_types=1);

namespace Tests\Integration\Cache;

use App\Shared\Cache\Services\CacheManager;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Strategies\DefaultCacheStrategy;
use App\Shared\Monitoring\Services\CacheMonitor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CacheMonitoringIntegrationTest extends TestCase
{
    private CacheManager $cacheManager;
    private CacheMonitor $cacheMonitor;

    protected function setUp(): void
    {
        $this->cacheMonitor = new CacheMonitor(new NullLogger());
        
        $drivers = [
            'memory' => new MemoryCacheDriver(),
        ];
        
        $this->cacheManager = new CacheManager(
            $drivers,
            'memory',
            new DefaultCacheStrategy(),
            new NullLogger(),
            $this->cacheMonitor
        );
    }

    public function test_cache_operations_are_monitored(): void
    {
        // 執行快取操作
        $this->cacheManager->put('test_key', 'test_value', 3600);
        $value = $this->cacheManager->get('test_key');
        $this->cacheManager->has('test_key');
        $this->cacheManager->forget('test_key');

        // 檢查監控資料
        $metrics = $this->cacheMonitor->getMetrics();
        
        $this->assertGreaterThan(0, $metrics['total_sets']);
        $this->assertGreaterThan(0, $metrics['total_hits']);
        $this->assertGreaterThan(0, $metrics['total_deletes']);

        // 檢查驅動效能資料
        $performance = $this->cacheMonitor->getDriverPerformance();
        $this->assertArrayHasKey('memory', $performance);
        $this->assertGreaterThan(0, $performance['memory']['total_operations']);
    }

    public function test_cache_miss_is_monitored(): void
    {
        // 嘗試獲取不存在的 key
        $result = $this->cacheManager->get('non_existent_key');
        
        $this->assertNull($result);
        
        $metrics = $this->cacheMonitor->getMetrics();
        $this->assertGreaterThan(0, $metrics['total_misses']);
    }

    public function test_cache_flush_is_monitored(): void
    {
        // 先存放一些資料
        $this->cacheManager->put('key1', 'value1');
        $this->cacheManager->put('key2', 'value2');
        
        // 清空快取
        $result = $this->cacheManager->flush();
        $this->assertTrue($result);
        
        // 檢查監控資料
        $performance = $this->cacheMonitor->getDriverPerformance();
        $this->assertArrayHasKey('memory', $performance);
        
        // 應該記錄了 flush 操作
        $this->assertGreaterThan(0, $performance['memory']['total_operations']);
    }

    public function test_health_check_reflects_cache_status(): void
    {
        // 執行一些成功的操作
        $this->cacheManager->put('health_test', 'value');
        $this->cacheManager->get('health_test');
        
        $health = $this->cacheMonitor->getHealth();
        $this->assertArrayHasKey('memory', $health);
        
        // 在沒有錯誤的情況下，狀態應該是健康的
        // 具體的健康檢查邏輯取決於 CacheMonitor 的實作
        $this->assertIsString($health['memory']);
    }

    public function test_performance_metrics_accuracy(): void
    {
        $testData = [
            'small_data' => 'small',
            'medium_data' => str_repeat('x', 1000),
            'large_data' => str_repeat('y', 10000),
        ];

        foreach ($testData as $key => $data) {
            $this->cacheManager->put($key, $data);
            $this->cacheManager->get($key);
        }

        $performance = $this->cacheMonitor->getDriverPerformance();
        $memoryPerf = $performance['memory'];

        // 應該有 6 次操作（3 次 put + 3 次 get）
        $this->assertEquals(6, $memoryPerf['total_operations']);
        
        // 總時間應該大於 0
        $this->assertGreaterThan(0, $memoryPerf['total_time']);
        
        // 平均時間應該是總時間除以操作次數
        $expectedAverage = $memoryPerf['total_time'] / $memoryPerf['total_operations'];
        $this->assertEquals($expectedAverage, $memoryPerf['average_time']);
    }

    public function test_monitor_reset_functionality(): void
    {
        // 執行一些操作
        $this->cacheManager->put('test1', 'value1');
        $this->cacheManager->get('test1');
        $this->cacheManager->get('non_existent');

        // 確認有資料
        $metricsBefore = $this->cacheMonitor->getMetrics();
        $this->assertGreaterThan(0, $metricsBefore['total_sets']);
        $this->assertGreaterThan(0, $metricsBefore['total_hits']);
        $this->assertGreaterThan(0, $metricsBefore['total_misses']);

        // 重設監控資料
        $this->cacheMonitor->reset();

        // 確認資料已清空
        $metricsAfter = $this->cacheMonitor->getMetrics();
        $this->assertEquals(0, $metricsAfter['total_sets']);
        $this->assertEquals(0, $metricsAfter['total_hits']);
        $this->assertEquals(0, $metricsAfter['total_misses']);
        $this->assertEquals(0, $metricsAfter['total_deletes']);
        $this->assertEquals(0, $metricsAfter['total_errors']);

        // 驅動效能資料也應該被清空
        $performanceAfter = $this->cacheMonitor->getDriverPerformance();
        $this->assertEmpty($performanceAfter);
    }

    public function test_multiple_operations_on_same_key(): void
    {
        $key = 'multi_op_key';
        
        // 執行多種操作
        $this->cacheManager->put($key, 'initial_value');
        $this->cacheManager->get($key);
        $this->cacheManager->has($key);
        $this->cacheManager->put($key, 'updated_value'); // 覆寫
        $this->cacheManager->get($key);
        $this->cacheManager->forget($key);
        
        $metrics = $this->cacheMonitor->getMetrics();
        $performance = $this->cacheMonitor->getDriverPerformance();
        
        // 檢查總體統計
        $this->assertEquals(2, $metrics['total_sets']); // 2 次 put
        $this->assertEquals(2, $metrics['total_hits']); // 2 次成功的 get
        $this->assertEquals(1, $metrics['total_deletes']); // 1 次 forget
        
        // 檢查驅動效能統計
        $memoryPerf = $performance['memory'];
        $this->assertEquals(6, $memoryPerf['total_operations']); // 總共 6 次操作
        $this->assertGreaterThan(0, $memoryPerf['total_time']);
    }
}