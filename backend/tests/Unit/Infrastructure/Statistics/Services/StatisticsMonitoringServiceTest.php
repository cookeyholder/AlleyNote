<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\SlowQueryMonitoringServiceInterface;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * 統計監控服務單元測試.
 *
 * 測試統計功能的監控、健康檢查和日誌記錄功能。
 */
final class StatisticsMonitoringServiceTest extends TestCase
{
    private StatisticsMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立不依賴外部服務的監控服務實例進行單元測試
        $this->monitoringService = new StatisticsMonitoringService(
            $this->createTestSlowQueryService(),
        );
    }

    /**
     * 建立測試用的慢查詢服務.
     */
    private function createTestSlowQueryService(): SlowQueryMonitoringServiceInterface
    {
        return new class implements SlowQueryMonitoringServiceInterface {
            public function recordSlowQuery(
                string $queryType,
                string $query,
                float $executionTime,
                array $parameters = [],
            ): bool {
                return true;
            }

            public function getSlowQueryStats(int $days = 7): array
            {
                return [
                    ['query_type' => 'posts_by_source', 'slow_query_count' => 3],
                    ['query_type' => 'posts_by_status', 'slow_query_count' => 2],
                ];
            }

            public function getSlowQueryDetails(int $limit = 50): array
            {
                return [];
            }

            public function cleanupOldRecords(int $days = 30): int
            {
                return 0;
            }
        };
    }

    public function test應該能取得統計計算時間監控資料(): void
    {
        // Arrange
        $expectedMetrics = [
            'avg_calculation_time' => 2.5,
            'max_calculation_time' => 5.2,
            'total_calculations' => 100,
            'failed_calculations' => 2,
        ];

        // Act
        $result = $this->monitoringService->getCalculationTimeMetrics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('avg_calculation_time', $result);
        $this->assertArrayHasKey('max_calculation_time', $result);
        $this->assertArrayHasKey('total_calculations', $result);
        $this->assertArrayHasKey('failed_calculations', $result);
    }

    public function test應該能取得快取命中率監控資料(): void
    {
        // Act
        $result = $this->monitoringService->getCacheMetrics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('hit_rate', $result);
        $this->assertArrayHasKey('miss_rate', $result);
        $this->assertArrayHasKey('total_requests', $result);
        $this->assertArrayHasKey('cache_size', $result);

        // 命中率應該在 0-100 之間
        $this->assertGreaterThanOrEqual(0, $result['hit_rate']);
        $this->assertLessThanOrEqual(100, $result['hit_rate']);
    }

    public function test應該能取得API回應時間監控資料(): void
    {
        // Act
        $result = $this->monitoringService->getApiResponseTimeMetrics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('avg_response_time', $result);
        $this->assertArrayHasKey('p95_response_time', $result);
        $this->assertArrayHasKey('p99_response_time', $result);
        $this->assertArrayHasKey('total_requests', $result);
        $this->assertArrayHasKey('error_rate', $result);
    }

    public function test應該能取得錯誤率監控資料(): void
    {
        // Act
        $result = $this->monitoringService->getErrorMetrics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_errors', $result);
        $this->assertArrayHasKey('error_rate', $result);
        $this->assertArrayHasKey('slow_query_count', $result);
        $this->assertArrayHasKey('critical_errors', $result);

        $this->assertEquals(5, $result['slow_query_count']);
    }

    public function test應該能執行完整的健康檢查(): void
    {
        // Act
        $result = $this->monitoringService->performHealthCheck();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('overall_health', $result);

        // 狀態應該是 healthy, degraded 或 unhealthy 之一
        $this->assertContains($result['status'], ['healthy', 'degraded', 'unhealthy']);
    }

    public function test健康檢查應該包含所有子系統檢查(): void
    {
        // Act
        $result = $this->monitoringService->performHealthCheck();

        // Assert
        $checks = $result['checks'];
        $expectedChecks = [
            'database',
            'cache',
            'statistics_calculation',
            'slow_queries',
            'disk_space',
            'memory_usage',
        ];

        foreach ($expectedChecks as $check) {
            $this->assertArrayHasKey($check, $checks);
            $this->assertArrayHasKey('status', $checks[$check]);
            $this->assertArrayHasKey('message', $checks[$check]);
        }
    }

    public function test應該能記錄統計操作事件(): void
    {
        // Act
        $result = $this->monitoringService->logStatisticsEvent('calculation_started', [
            'type' => 'daily',
            'period' => '2025-09-23',
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function test應該能產生監控摘要報告(): void
    {
        // Act
        $result = $this->monitoringService->generateMonitoringSummary();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('health_status', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('generated_at', $result);

        // 檢查生成時間格式
        $generatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $result['generated_at']);
        $this->assertInstanceOf(DateTime::class, $generatedAt);
    }

    public function test應該能清理過期的監控記錄(): void
    {
        // Act
        $result = $this->monitoringService->cleanupOldMonitoringData();

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function test應該能檢測系統警告條件(): void
    {
        // Act
        $result = $this->monitoringService->checkAlertConditions();

        // Assert
        $this->assertIsArray($result);

        foreach ($result as $alert) {
            $this->assertArrayHasKey('type', $alert);
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);

            // 檢查嚴重性級別
            $this->assertContains($alert['severity'], ['info', 'warning', 'critical']);
        }
    }

    public function test當系統出現嚴重錯誤時健康檢查應該返回不健康狀態(): void
    {
        // Arrange - 建立有大量慢查詢的服務
        $highSlowQueryService = new class implements SlowQueryMonitoringServiceInterface {
            public function recordSlowQuery(
                string $queryType,
                string $query,
                float $executionTime,
                array $parameters = [],
            ): bool {
                return true;
            }

            public function getSlowQueryStats(int $days = 7): array
            {
                return [
                    ['query_type' => 'critical_query', 'slow_query_count' => 100], // 大量慢查詢
                ];
            }

            public function getSlowQueryDetails(int $limit = 50): array
            {
                return [];
            }

            public function cleanupOldRecords(int $days = 30): int
            {
                return 0;
            }
        };

        $monitoringService = new StatisticsMonitoringService($highSlowQueryService);

        // Act
        $result = $monitoringService->performHealthCheck();

        // Assert - 當有大量慢查詢時，系統健康狀態應該受影響
        $this->assertContains($result['status'], ['degraded', 'unhealthy']);
    }

    public function test應該能取得特定時間範圍的監控統計(): void
    {
        // Arrange
        $startDate = new DateTime('2025-09-20');
        $endDate = new DateTime('2025-09-23');

        // Act
        $result = $this->monitoringService->getMonitoringStatistics($startDate, $endDate);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('total_calculations', $result);
        $this->assertArrayHasKey('avg_response_time', $result);
        $this->assertArrayHasKey('error_count', $result);
        $this->assertArrayHasKey('cache_performance', $result);

        $this->assertEquals('2025-09-20 to 2025-09-23', $result['period']);
    }
}
