<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\SlowQueryMonitoringServiceInterface;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use DateTime;
use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use ReflectionMethod;
use RuntimeException;
use Stringable;
use Tests\Support\UnitTestCase;

/**
 * 統計監控服務單元測試.
 *
 * 測試統計功能的監控、健康檢查和日誌記錄功能。
 */
final class StatisticsMonitoringServiceTest extends UnitTestCase
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
            'total_calculations'   => 100,
            'failed_calculations'  => 2,
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
            'type'   => 'daily',
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

    // ========== 以真實 SQLite 與可控模擬驗證資料庫相關路徑 ==========

    private PDO $monitorPdo;

    /**
     * 建立帶有監控資料表與真實 SQLite 連線的監控服務.
     */
    private function createDatabaseBackedService(): StatisticsMonitoringService
    {
        $this->monitorPdo = new PDO('sqlite::memory:');
        $this->monitorPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->monitorPdo->exec('
            CREATE TABLE statistics_query_monitoring (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_type TEXT NOT NULL,
                execution_time REAL NOT NULL,
                status TEXT NOT NULL,
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        return new StatisticsMonitoringService(
            $this->createTestSlowQueryService(),
            $this->monitorPdo,
            $this->createTestLogger(),
        );
    }

    private function createTestLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            use LoggerTrait;

            public function log(mixed $level, string|Stringable $message, array $context = []): void {}
        };
    }

    public function testLogStatisticsEventPersistsToMonitoringTable(): void
    {
        $service = $this->createDatabaseBackedService();

        $this->assertTrue($service->logStatisticsEvent('snapshot_created', ['id' => 1]));

        $countStmt = $this->monitorPdo
            ->query("SELECT COUNT(*) FROM statistics_query_monitoring WHERE query_type = 'snapshot_created'");
        assert($countStmt !== false);
        $count = (int) $countStmt->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function testLogStatisticsEventSwallowsDatabaseErrors(): void
    {
        $service = $this->createDatabaseBackedService();
        $this->monitorPdo->exec('DROP TABLE statistics_query_monitoring');

        $this->assertFalse($service->logStatisticsEvent('event'));
    }

    public function testCleanupOldMonitoringDataRemovesExpiredRows(): void
    {
        $service = $this->createDatabaseBackedService();
        $this->monitorPdo->exec("
            INSERT INTO statistics_query_monitoring (query_type, execution_time, status, created_at) VALUES
            ('calculation', 1.0, 'done', '2020-01-01 00:00:00'),
            ('calculation', 2.0, 'done', datetime('now'))
        ");

        $deleted = $service->cleanupOldMonitoringData();

        $this->assertSame(1, $deleted);
    }

    public function testCleanupOldMonitoringDataHandlesFailuresGracefully(): void
    {
        $service = $this->createDatabaseBackedService();
        $this->monitorPdo->exec('DROP TABLE statistics_query_monitoring');

        $this->assertSame(0, $service->cleanupOldMonitoringData());
    }

    public function testGetCalculationTimeMetricsFallsBackWhenSqlUnsupported(): void
    {
        // SQLite 不支援 DATE_SUB/NOW()，應記錄錯誤並回傳模擬指標
        $service = $this->createDatabaseBackedService();
        $metrics = $service->getCalculationTimeMetrics();

        $this->assertArrayHasKey('total_calculations', $metrics);
        $this->assertArrayHasKey('failed_calculations', $metrics);

        // 查詢失敗不會影響健康檢查流程
        $health = $service->performHealthCheck();
        assert(is_array($health['checks']) && is_array($health['checks']['database']));
        $this->assertArrayHasKey('checks', $health);
        $this->assertSame('healthy', $health['checks']['database']['status']);
    }

    public function testPerformHealthCheckAggregatesAllChecks(): void
    {
        $service = $this->createDatabaseBackedService();
        $health = $service->performHealthCheck();

        $this->assertSame(['database', 'cache', 'statistics_calculation', 'slow_queries', 'disk_space', 'memory_usage'], array_keys($health['checks']));
        $this->assertContains($health['status'], ['healthy', 'degraded', 'unhealthy']);
        $this->assertIsInt($health['overall_health']);
    }

    public function testCheckAlertConditionsDetectsThresholdBreaches(): void
    {
        // 慢查詢數量由模擬服務控制，可穩定觸發 slow_query 警告
        $highSlowQuery = new class implements SlowQueryMonitoringServiceInterface {
            public function recordSlowQuery(string $queryType, string $query, float $executionTime, array $parameters = []): bool
            {
                return true;
            }

            public function executeAndMonitor(string $query, array $params = [], string $queryType = 'unknown'): mixed
            {
                return [];
            }

            public function getSlowQueryStats(int $days = 7): array
            {
                return [['query_type' => 'x', 'slow_query_count' => 99]];
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

        $service = new StatisticsMonitoringService($highSlowQuery, null, $this->createTestLogger());

        // 錯誤率與快取命中率含隨機成分，反覆呼叫以涵蓋各個警告分支
        $seenTypes = [];
        for ($i = 0; $i < 300; $i++) {
            foreach ($service->checkAlertConditions() as $alert) {
                $seenTypes[$alert['type']] = true;
            }
        }

        $this->assertArrayHasKey('slow_query', $seenTypes);
        $this->assertArrayHasKey('low_cache_hit_rate', $seenTypes);
    }

    public function testGetErrorMetricsReturnsZerosWhenSlowQueryServiceFails(): void
    {
        $failingSlowQuery = new class implements SlowQueryMonitoringServiceInterface {
            public function recordSlowQuery(string $queryType, string $query, float $executionTime, array $parameters = []): bool
            {
                return true;
            }

            public function executeAndMonitor(string $query, array $params = [], string $queryType = 'unknown'): mixed
            {
                return [];
            }

            public function getSlowQueryStats(int $days = 7): array
            {
                throw new RuntimeException('stats unavailable');
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

        $service = new StatisticsMonitoringService($failingSlowQuery, null, $this->createTestLogger());

        $metrics = $service->getErrorMetrics();

        $this->assertSame(0, $metrics['total_errors']);
        $this->assertSame(0.0, $metrics['error_rate']);
        $this->assertSame(0, $metrics['slow_query_count']);
        $this->assertSame(0, $metrics['critical_errors']);
    }

    public function testCalculationHealthDegradesWhenFailuresOccur(): void
    {
        // 模擬指標含隨機失敗次數，反覆執行直到觸發降級狀態
        $service = $this->createDatabaseBackedService();

        $sawDegraded = false;
        for ($i = 0; $i < 100 && !$sawDegraded; $i++) {
            $health = $service->performHealthCheck();
            if ((is_array($health['checks']['statistics_calculation'] ?? null) ? ($health['checks']['statistics_calculation']['status'] ?? '') : '') === 'degraded') {
                $sawDegraded = true;
            }
        }

        $this->assertTrue($sawDegraded, '應觀察到計算健康降級狀態');
    }

    public function testGetMemoryLimitParsesAllSuffixes(): void
    {
        $service = $this->createDatabaseBackedService();
        $method = new ReflectionMethod(StatisticsMonitoringService::class, 'getMemoryLimit');
        $method->setAccessible(true);

        $originalLimit = ini_get('memory_limit');

        try {
            ini_set('memory_limit', '-1');
            $this->assertSame(0, $method->invoke($service));

            ini_set('memory_limit', '2G');
            $this->assertSame(2 * 1024 * 1024 * 1024, $method->invoke($service));

            // K 與無後綴（位元組）格式需高於目前用量，PHP 才會接受設定
            $kLimit = (int) (ceil(memory_get_usage(true) / 1024) + 2048);
            ini_set('memory_limit', "{$kLimit}K");
            $this->assertSame($kLimit * 1024, $method->invoke($service));

            $byteLimit = memory_get_usage(true) + 10 * 1024 * 1024;
            ini_set('memory_limit', (string) $byteLimit);
            $this->assertSame($byteLimit, $method->invoke($service));
        } finally {
            ini_set('memory_limit', (string) $originalLimit);
        }
    }

    public function testCheckMemoryUsageHealthReportsDegradedAndCriticalStates(): void
    {
        $service = $this->createDatabaseBackedService();
        $originalLimit = ini_get('memory_limit');

        try {
            // 提高限制以確保配置空間足夠，且不影響其他測試
            ini_set('memory_limit', '512M');
            $bytesLimit = 512 * 1024 * 1024;

            // 配置至約 85%：應回報 degraded
            $target85 = (int) ($bytesLimit * 0.85);
            $usedNow = memory_get_usage(true);
            $ballast = $usedNow < $target85 ? str_repeat('a', $target85 - $usedNow) : null;
            $firstRaw = $service->performHealthCheck()['checks']['memory_usage'] ?? null;

            // 釋放後重新配置至約 92%：歷史峰值仍在，應回報 critical
            unset($ballast);
            $target92 = (int) ($bytesLimit * 0.92);
            gc_collect_cycles();
            $usedAfterFirst = memory_get_usage(true);
            $secondBallast = $usedAfterFirst < $target92 ? str_repeat('b', $target92 - $usedAfterFirst) : null;
            $secondRaw = $service->performHealthCheck()['checks']['memory_usage'] ?? null;
            unset($secondBallast);

            assert(is_array($firstRaw));
            assert(is_array($secondRaw));
            $first = $firstRaw;
            $second = $secondRaw;

            $this->assertContains($first['status'], ['degraded', 'critical']);
            $this->assertSame('critical', $second['status']);
        } finally {
            ini_set('memory_limit', (string) $originalLimit);
        }
    }
}
