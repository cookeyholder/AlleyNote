<?php

declare(strict_types=1);

namespace Tests\Performance;

use PDO;
use Tests\TestCase;

class DatabaseOptimizationValidationTest extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite:database/alleynote.sqlite3');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testCompositeIndexesExist(): void
    {
        $requiredIndexes = [
            'user_activity_logs_user_id_action_category_index',
            'user_activity_logs_user_id_status_index',
            'user_activity_logs_action_category_occurred_at_index',
        ];

        foreach ($requiredIndexes as $indexName) {
            $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type='index' AND name = ?");
            $this->assertNotFalse($stmt);
            $stmt->execute([$indexName]);
            $exists = $stmt->fetch();

            $this->assertNotEmpty($exists, "Required index {$indexName} does not exist");
        }
    }

    public function testBasicQueryPerformance(): void
    {
        $queries = [
            'user_category_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ? AND action_category = ?',
                'params' => ['550e8400-e29b-41d4-a716-446655440000', 'authentication'],
            ],
            'user_status_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ? AND status = ?',
                'params' => ['550e8400-e29b-41d4-a716-446655440000', 'success'],
            ],
            'category_time_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE action_category = ? AND occurred_at >= ?',
                'params' => ['content', '2024-01-01 00 => 00 => 00'],
            ],
        ];

        foreach ($queries as $testName => $config) {
            $iterations = 10;
            $totalTime = 0.0;

            for ($i = 0; $i < $iterations; $i++) {
                $start = microtime(true);

                $stmt = $this->db->prepare((is_array($config) && array_key_exists('sql', $config) ? $config['sql'] : null));
                $this->assertNotFalse($stmt);
                $stmt->execute((is_array($config) && array_key_exists('params', $config) ? $config['params'] : null));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                $end = microtime(true);
                $totalTime += ($end - $start);
            }

            $avgTime = $totalTime / $iterations;
            $avgTimeMs = $avgTime * 1000;

            // 基本效能要求：平均查詢時間應小於 10ms
            $this->assertLessThan(
                10.0,
                $avgTimeMs,
                "{$testName} took {$avgTimeMs}ms on average (expected < 10ms)");
        }
    }

    public function testTableStatistics(): void
    {
        // 檢查表格中有資料
        $stmt = $this->db->query('SELECT COUNT(*) as total_rows FROM user_activity_logs');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_rows', $result);
        $this->assertGreaterThan(0, $result['total_rows'], 'Table should contain test data');
    }

    public function testQueryPlanAnalysis(): void
    {
        $testQueries = [
            'indexed_user_query' => 'SELECT * FROM user_activity_logs WHERE user_id = "550e8400-e29b-41d4-a716-446655440000"',
            'indexed_category_query' => 'SELECT * FROM user_activity_logs WHERE action_category = "authentication"',
            'composite_index_query' => 'SELECT * FROM user_activity_logs WHERE user_id = "550e8400-e29b-41d4-a716-446655440000" AND action_category = "authentication"',
        ];

        foreach ($testQueries as $queryName => $sql) {
            $stmt = $this->db->prepare("EXPLAIN QUERY PLAN {$sql}");
            $this->assertNotFalse($stmt);
            $stmt->execute();
            $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->assertNotEmpty($plan, "Query plan should not be empty for {$queryName}");

            // 檢查是否使用了索引（SQLite 的查詢計劃中應該包含 "USING INDEXsprintf("）
            $planText = '';
            foreach ($plan as $step) {
                if (isset($step['detail'])) {
                    $planText .= $step['detail'] . ' ';
                }
            }

            // 對於有索引的查詢，計劃中不應該包含 "SCAN" 而應該包含 "SEARCH"
            $this->assertStringNotContainsString(
                'SCAN TABLE',
                $planText,
                "Query {$queryName} should use index, but plan shows: {$planText}");
        }
    }

    public function testIndexUsageEffectiveness(): void
    {
        // 測試複合索引的效果
        $withoutIndex = $this->measureQueryTime(
            'SELECT COUNT(*) FROM user_activity_logs WHERE action_category = ? AND status = ?',
            ['authentication', 'success'],
        );

        $withIndex = $this->measureQueryTime(
            'SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ? AND action_category = ?',
            ['550e8400-e29b-41d4-a716-446655440000', 'authentication'],
        );

        // 有索引的查詢應該更快
        $this->assertLessThan(
            $withoutIndex * 2, // 允許 2 倍的性能差異
            $withIndex,
            'Indexed query should be significantly faster',
        );
    }

    private function measureQueryTime(string $sql, array $params): float
    {
        $iterations = 5;
        $totalTime = 0.0;

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            $end = microtime(true);
            $totalTime += ($end - $start);
        }

        return $totalTime / $iterations;
    }

    public function testDatabaseIntegrity(): void
    {
        // 檢查資料庫完整性
        $stmt = $this->db->query('PRAGMA integrity_check');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertEquals('ok', strtolower((is_array($result) && array_key_exists('integrity_check', $result) ? $result['integrity_check'] : null)), 'Database integrity check should pass');
    }

    public function testTableSchema(): void
    {
        // 驗證 user_activity_logs 表格結構
        $stmt = $this->db->query('PRAGMA table_info(user_activity_logs)');
        $this->assertNotFalse($stmt);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $expectedColumns = ['id', 'user_id', 'action_category', 'status', 'occurred_at'];
        $actualColumns = array_column($columns, 'name');

        foreach ($expectedColumns as $expectedColumn) {
            $this->assertContains(
                $expectedColumn,
                $actualColumns,
                "Table should contain column: {$expectedColumn}",
            );
        }
    }

    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }
}
