<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Infrastructure\Statistics\Services\SlowQueryMonitoringService;
use PDO;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * 慢查詢監控服務測試.
 */
final class SlowQueryMonitoringServiceTest extends UnitTestCase
{
    private PDO $pdo;

    private SlowQueryMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE statistics_slow_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                query_sql TEXT,
                execution_time REAL NOT NULL,
                result_count INTEGER DEFAULT 0,
                query_params TEXT,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE statistics_query_performance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                execution_time REAL NOT NULL,
                result_count INTEGER NOT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE statistics_failed_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                query_sql TEXT,
                execution_time REAL NOT NULL,
                error_message TEXT,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE test_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            );
        ');

        $this->service = new SlowQueryMonitoringService($this->pdo);
    }

    public function testRecordSlowQuery(): void
    {
        $result = $this->service->recordSlowQuery('posts_query', 'SELECT * FROM posts WHERE id = :id', 1.5, ['id' => 1]);
        $this->assertTrue($result);

        $stmt = $this->pdo->query('SELECT * FROM statistics_slow_queries');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
        $this->assertSame('posts_query', $rows[0]['query_type']);
        $this->assertSame(1.5, (float) $rows[0]['execution_time']);
    }

    public function testExecuteAndMonitorSuccess(): void
    {
        $this->pdo->exec("INSERT INTO test_items (name) VALUES ('Item A'), ('Item B')");

        $results = $this->service->executeAndMonitor('SELECT * FROM test_items WHERE name = :name', ['name' => 'Item A'], 'items_fetch');
        $this->assertCount(1, $results);
        $this->assertSame('Item A', $results[0]['name']);

        $stmt = $this->pdo->query('SELECT * FROM statistics_query_performance');
        $perf = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $perf);
        $this->assertSame('items_fetch', $perf[0]['query_type']);
        $this->assertSame(1, (int) $perf[0]['result_count']);
    }

    public function testExecuteAndMonitorFailedQuery(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('查詢執行失敗');

        $this->service->executeAndMonitor('SELECT * FROM non_existent_table', [], 'error_query');
    }

    public function testGetSlowQueryStats(): void
    {
        $this->service->recordSlowQuery('type_a', 'SELECT 1', 1.2);
        $this->service->recordSlowQuery('type_a', 'SELECT 2', 2.0);
        $this->service->recordSlowQuery('type_b', 'SELECT 3', 1.5);

        $stats = $this->service->getSlowQueryStats(7);
        $this->assertCount(2, $stats);
        $this->assertSame('type_a', $stats[0]['query_type']);
        $this->assertSame(2, (int) $stats[0]['slow_query_count']);
    }

    public function testGetPerformanceTrend(): void
    {
        $this->pdo->exec("
            INSERT INTO statistics_query_performance (query_hash, query_type, execution_time, result_count, created_at)
            VALUES 
                ('hash1', 'post_type', 0.5, 10, datetime('now')),
                ('hash1', 'post_type', 1.5, 10, datetime('now'))
        ");

        $trend = $this->service->getPerformanceTrend('post_type', 30);
        $this->assertNotEmpty($trend);
        $this->assertSame(2, (int) $trend[0]['query_count']);
        $this->assertSame(1, (int) $trend[0]['slow_count']);
    }

    public function testGetSlowestQueries(): void
    {
        $this->service->recordSlowQuery('type1', 'SELECT 1', 1.2, ['p' => 1]);
        $this->service->recordSlowQuery('type2', 'SELECT 2', 3.5, ['p' => 2]);

        $slowest = $this->service->getSlowestQueries(5, 7);
        $this->assertCount(2, $slowest);
        $this->assertSame(3.5, (float) $slowest[0]['execution_time']);
    }

    public function testAnalyzeQueryPerformance(): void
    {
        // 1. 無記錄
        $emptyAnalysis = $this->service->analyzeQueryPerformance('unknown_hash');
        $this->assertSame(['error' => '找不到查詢記錄'], $emptyAnalysis);

        // 2. 資料不足 5 筆
        $this->pdo->exec("
            INSERT INTO statistics_query_performance (query_hash, query_type, execution_time, result_count, created_at)
            VALUES 
                ('h1', 'type1', 0.2, 5, '2025-01-01 10:00:00'),
                ('h1', 'type1', 0.4, 5, '2025-01-01 11:00:00')
        ");
        $analysisSmall = $this->service->analyzeQueryPerformance('h1');
        $this->assertSame('insufficient_data', $analysisSmall['performance_trend']);
        $this->assertSame(2, $analysisSmall['total_executions']);
        $this->assertSame(0.3, $analysisSmall['avg_execution_time']);

        // 3. 超過 5 筆並計算 deteriorating / improving / stable 趨勢
        for ($i = 0; $i < 15; $i++) {
            $execTime = 2.0; // 最近的較慢
            $this->pdo->exec("
                INSERT INTO statistics_query_performance (query_hash, query_type, execution_time, result_count, created_at)
                VALUES ('h2', 'type2', {$execTime}, 5, '2025-01-02 10:{$i}:00')
            ");
        }
        for ($i = 0; $i < 10; $i++) {
            $execTime = 0.5; // 較舊的較快
            $this->pdo->exec("
                INSERT INTO statistics_query_performance (query_hash, query_type, execution_time, result_count, created_at)
                VALUES ('h2', 'type2', {$execTime}, 5, '2025-01-01 10:{$i}:00')
            ");
        }
        $analysisTrend = $this->service->analyzeQueryPerformance('h2');
        $this->assertSame('deteriorating', $analysisTrend['performance_trend']);
        $this->assertSame(25, $analysisTrend['total_executions']);
    }

    public function testGetSlowQueryDetails(): void
    {
        $this->service->recordSlowQuery('type_json', 'SELECT * FROM users', 2.1, ['filter' => 'active']);

        $details = $this->service->getSlowQueryDetails(10);
        $this->assertCount(1, $details);
        $this->assertSame('type_json', $details[0]['query_type']);
        $this->assertSame(['filter' => 'active'], $details[0]['parameters']);
    }

    public function testCleanupOldRecords(): void
    {
        // 插入舊資料與新資料
        $this->pdo->exec("
            INSERT INTO statistics_query_performance (query_hash, query_type, execution_time, result_count, created_at)
            VALUES 
                ('old', 'type', 0.1, 1, '2020-01-01 00:00:00'),
                ('new', 'type', 0.1, 1, datetime('now'));
            INSERT INTO statistics_slow_queries (query_hash, query_type, query_sql, execution_time, query_params, created_at)
            VALUES 
                ('old', 'type', 'SQL', 2.0, '{}', '2020-01-01 00:00:00'),
                ('new', 'type', 'SQL', 2.0, '{}', datetime('now'));
        ");

        $deleted = $this->service->cleanupOldRecords(30);
        $this->assertSame(2, $deleted);

        $perfCount = (int) $this->pdo->query('SELECT COUNT(*) FROM statistics_query_performance')->fetchColumn();
        $slowCount = (int) $this->pdo->query('SELECT COUNT(*) FROM statistics_slow_queries')->fetchColumn();

        $this->assertSame(1, $perfCount);
        $this->assertSame(1, $slowCount);
    }
}
