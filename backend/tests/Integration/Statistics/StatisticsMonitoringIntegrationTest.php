<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Infrastructure\Statistics\Services\SlowQueryMonitoringService;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use DateTime;
use PDO;
use Tests\TestCase;

/**
 * 統計監控服務整合測試.
 *
 * 測試統計監控服務與相關元件的整合功能。
 */
final class StatisticsMonitoringIntegrationTest extends TestCase
{
    private StatisticsMonitoringService $monitoringService;

    private SlowQueryMonitoringService $slowQueryService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立統計監控相關資料表
        $this->createStatisticsMonitoringTables();

        $this->slowQueryService = new SlowQueryMonitoringService($this->db);
        $this->monitoringService = new StatisticsMonitoringService(
            $this->slowQueryService,
            $this->db,
        );
    }

    /**
     * 建立統計監控相關資料表.
     */
    private function createStatisticsMonitoringTables(): void
    {
        // 建立統計監控記錄表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS statistics_query_monitoring (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_type TEXT NOT NULL,
                execution_time REAL NOT NULL,
                status TEXT NOT NULL,
                metadata TEXT,
                created_at TEXT NOT NULL
            )
        ');

        // 建立慢查詢記錄表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS statistics_slow_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                query_sql TEXT NOT NULL,
                execution_time REAL NOT NULL,
                query_params TEXT,
                created_at TEXT NOT NULL
            )
        ');

        // 建立效能監控記錄表
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS statistics_query_performance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                avg_execution_time REAL NOT NULL,
                execution_count INTEGER NOT NULL,
                last_executed_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');
    }

    public function test監控服務整合功能應該正常運作(): void
    {
        // 先記錄一些監控資料
        $this->slowQueryService->recordSlowQuery(
            'posts_by_source',
            'SELECT * FROM posts WHERE source = ?',
            2.5,
            ['source' => 'web'],
        );

        $this->slowQueryService->recordSlowQuery(
            'posts_by_status',
            'SELECT * FROM posts WHERE status = ?',
            3.2,
            ['status' => 'published'],
        );

        // 測試取得錯誤監控資料
        $errorMetrics = $this->monitoringService->getErrorMetrics();

        $this->assertIsArray($errorMetrics);
        $this->assertArrayHasKey('slow_query_count', $errorMetrics);
        $this->assertEquals(2, $errorMetrics['slow_query_count']);
    }

    public function test健康檢查應該回報正確的系統狀態(): void
    {
        // Act
        $healthCheck = $this->monitoringService->performHealthCheck();

        // Assert
        $this->assertIsArray($healthCheck);
        $this->assertArrayHasKey('status', $healthCheck);
        $this->assertArrayHasKey('checks', $healthCheck);
        $this->assertArrayHasKey('overall_health', $healthCheck);

        // 檢查所有子系統檢查
        $requiredChecks = [
            'database', 'cache', 'statistics_calculation',
            'slow_queries', 'disk_space', 'memory_usage',
        ];

        foreach ($requiredChecks as $check) {
            $this->assertArrayHasKey($check, $healthCheck['checks']);
            $this->assertArrayHasKey('status', $healthCheck['checks'][$check]);
            $this->assertArrayHasKey('message', $healthCheck['checks'][$check]);
        }

        // 資料庫檢查應該是健康的
        $this->assertEquals('healthy', $healthCheck['checks']['database']['status']);
    }

    public function test監控摘要報告應該包含完整資訊(): void
    {
        // Act
        $summary = $this->monitoringService->generateMonitoringSummary();

        // Assert
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('summary', $summary);
        $this->assertArrayHasKey('metrics', $summary);
        $this->assertArrayHasKey('health_status', $summary);
        $this->assertArrayHasKey('alerts', $summary);
        $this->assertArrayHasKey('generated_at', $summary);

        // 檢查指標包含所有類型
        $this->assertArrayHasKey('calculation', $summary['metrics']);
        $this->assertArrayHasKey('cache', $summary['metrics']);
        $this->assertArrayHasKey('api', $summary['metrics']);
        $this->assertArrayHasKey('errors', $summary['metrics']);

        // 檢查摘要是字串且不為空
        $this->assertIsString($summary['summary']);
        $this->assertNotEmpty($summary['summary']);
    }

    public function test事件記錄功能應該正常運作(): void
    {
        // Act
        $result = $this->monitoringService->logStatisticsEvent('test_calculation', [
            'type' => 'unit_test',
            'duration' => 1.23,
        ]);

        // Assert
        $this->assertTrue($result);

        // 檢查資料庫中是否有記錄
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM statistics_query_monitoring
            WHERE query_type = 'test_calculation'
              AND status = 'event'
        ");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $this->assertEquals(1, $count);
    }

    public function test監控資料清理功能應該正常運作(): void
    {
        // 插入一些舊資料
        $oldDate = new DateTime()->modify('-35 days')->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO statistics_query_monitoring
            (query_type, execution_time, status, created_at)
            VALUES ('old_record', 1.0, 'completed', ?)
        ");
        $stmt->execute([$oldDate]);

        // 插入一些新資料
        $stmt = $this->db->prepare("
            INSERT INTO statistics_query_monitoring
            (query_type, execution_time, status, created_at)
            VALUES ('new_record', 1.0, 'completed', datetime('now'))
        ");
        $stmt->execute();

        // Act - 清理舊資料
        $deletedCount = $this->monitoringService->cleanupOldMonitoringData();

        // Assert - 應該刪除舊資料
        $this->assertGreaterThan(0, $deletedCount);

        // 檢查舊資料已被刪除，新資料仍存在
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM statistics_query_monitoring
            WHERE query_type IN ('old_record', 'new_record')
        ");
        $remainingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $this->assertEquals(1, $remainingCount); // 只剩新資料
    }

    public function test警報系統應該能檢測異常狀況(): void
    {
        // 建立大量慢查詢以觸發警報
        for ($i = 0; $i < 15; $i++) {
            $this->slowQueryService->recordSlowQuery(
                'trigger_alert_' . $i,
                'SELECT * FROM posts LIMIT 1000',
                5.0,
                [],
            );
        }

        // Act
        $alerts = $this->monitoringService->checkAlertConditions();

        // Assert
        $this->assertIsArray($alerts);

        // 應該有慢查詢警報
        $slowQueryAlerts = array_filter($alerts, fn($alert) => $alert['type'] === 'slow_query');
        $this->assertNotEmpty($slowQueryAlerts);

        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('type', $alert);
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('timestamp', $alert);
        }
    }

    public function test時間範圍統計查詢應該正常運作(): void
    {
        // Arrange
        $startDate = new DateTime('2025-09-20');
        $endDate = new DateTime('2025-09-23');

        // Act
        $statistics = $this->monitoringService->getMonitoringStatistics($startDate, $endDate);

        // Assert
        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('period', $statistics);
        $this->assertArrayHasKey('total_calculations', $statistics);
        $this->assertArrayHasKey('avg_response_time', $statistics);
        $this->assertArrayHasKey('error_count', $statistics);
        $this->assertArrayHasKey('cache_performance', $statistics);

        $this->assertEquals('2025-09-20 to 2025-09-23', $statistics['period']);
        $this->assertIsInt($statistics['total_calculations']);
        $this->assertIsFloat($statistics['avg_response_time']);
        $this->assertIsInt($statistics['error_count']);
        $this->assertIsArray($statistics['cache_performance']);
    }

    public function test所有監控指標都應該返回有效資料(): void
    {
        // 測試計算時間指標
        $calculationMetrics = $this->monitoringService->getCalculationTimeMetrics();
        $this->assertIsArray($calculationMetrics);
        $this->assertArrayHasKey('avg_calculation_time', $calculationMetrics);
        $this->assertIsFloat($calculationMetrics['avg_calculation_time']);

        // 測試快取指標
        $cacheMetrics = $this->monitoringService->getCacheMetrics();
        $this->assertIsArray($cacheMetrics);
        $this->assertArrayHasKey('hit_rate', $cacheMetrics);
        $this->assertGreaterThanOrEqual(0, $cacheMetrics['hit_rate']);
        $this->assertLessThanOrEqual(100, $cacheMetrics['hit_rate']);

        // 測試 API 指標
        $apiMetrics = $this->monitoringService->getApiResponseTimeMetrics();
        $this->assertIsArray($apiMetrics);
        $this->assertArrayHasKey('avg_response_time', $apiMetrics);
        $this->assertIsFloat($apiMetrics['avg_response_time']);

        // 測試錯誤指標
        $errorMetrics = $this->monitoringService->getErrorMetrics();
        $this->assertIsArray($errorMetrics);
        $this->assertArrayHasKey('error_rate', $errorMetrics);
        $this->assertIsFloat($errorMetrics['error_rate']);
    }
}
