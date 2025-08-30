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
            
            $this->assertNotEmpty($exists, "Required index $indexName does not exist");
        }
    }

    public function testBasicQueryPerformance(): void
    {
        $queries = [
            'user_category_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ? AND action_category = ?',
                'params' => ['550e8400-e29b-41d4-a716-446655440000', 'authentication']
            ],
            'user_status_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ? AND status = ?',
                'params' => ['550e8400-e29b-41d4-a716-446655440000', 'success']
            ],
            'category_time_query' => [
                'sql' => 'SELECT COUNT(*) FROM user_activity_logs WHERE action_category = ? AND occurred_at >= ?',
                'params' => ['content', '2024-01-01 00:00:00']
            ],
        ];

        foreach ($queries as $testName => $config) {
            $iterations = 10;
            $totalTime = 0.0;

            for ($i = 0; $i < $iterations; $i++) {
                $start = microtime(true);
                
                $stmt = $this->db->prepare($config['sql']);
                $this->assertNotFalse($stmt);
                $stmt->execute($config['params']);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $end = microtime(true);
                $totalTime += ($end - $start);
            }

            $avgTime = $totalTime / $iterations;
            $avgTimeMs = $avgTime * 1000;

            // 基本效能要求：平均查詢時間應小於 10ms
            $this->assertLessThan(10.0, $avgTimeMs, 
                "$testName took {$avgTimeMs}ms on average (expected < 10ms)");
        }
    }

    public function testTableStatistics(): void
    {
        // 檢查表格中有資料
        $stmt = $this->db->query("SELECT COUNT(*) as total_rows FROM user_activity_logs");
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_rows', $result);
        $this->assertGreaterThan(0, $result['total_rows'], 'Table should contain test data');
    }
}