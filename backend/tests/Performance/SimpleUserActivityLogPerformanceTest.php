<?php

declare(strict_types=1);

namespace Tests\Performance;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 簡化版使用者活動記錄效能測試.
 *
 * 測試資料庫直接操作的效能，不依賴服務容器
 */
#[Group('performance')]
class SimpleUserActivityLogPerformanceTest extends TestCase
{
    private PDO $pdo;

    /**
     * 設定測試環境.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            // 使用 SQLite 記憶體資料庫進行效能測試
            $this->pdo = new PDO('sqlite:database/alleynote.sqlite3');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->markTestSkipped('無法建立資料庫連線：' . $e->getMessage());
        }
    }

    /**
     * 測試批次插入效能.
     */
    #[Test]
    public function testBatchInsertPerformance(): void
    {
        $batchSizes = [100, 500, 1000];
        $results = [];

        foreach ($batchSizes as $batchSize) {
            $results[$batchSize] = $this->executeBatchInsertTest($batchSize);
        }

        // 輸出效能報告
        $this->outputPerformanceReport('批次插入效能測試', $results);
    }

    /**
     * 執行單一批次插入測試.
     *
     * @return array{duration: float, throughput: float, avg_per_record: float}
     */
    private function executeBatchInsertTest(int $batchSize): array
    {
        // 清理測試資料
        $this->cleanupTestData();

        $startTime = microtime(true);
        $this->performBatchInsert($batchSize);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $throughput = $batchSize / $duration;

        // 效能斷言
        $this->assertPerformanceThresholds($batchSize, $duration, $throughput);

        // 驗證資料完整性
        $this->verifyInsertedData($batchSize);

        return [
            'duration' => $duration,
            'throughput' => $throughput,
            'avg_per_record' => $duration / $batchSize,
        ];
    }

    /**
     * 執行批次插入操作.
     */
    private function performBatchInsert(int $batchSize): void
    {
        $sql = $this->getBatchInsertSql();
        $stmt = $this->pdo->prepare($sql);

        for ($i = 0; $i < $batchSize; $i++) {
            $params = $this->createBatchInsertParams($i, $batchSize);
            $stmt->execute($params);
        }
    }

    /**
     * 取得批次插入 SQL.
     */
    private function getBatchInsertSql(): string
    {
        return 'INSERT INTO user_activity_logs (
            uuid, user_id, session_id, action_type, action_category,
            target_type, target_id, status, description, metadata,
            ip_address, user_agent, request_method, request_path,
            created_at, occurred_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    }

    /**
     * 建立批次插入參數.
     *
     * @return array
     */
    private function createBatchInsertParams(int $index, int $batchSize): array
    {
        $uuid = $this->generateUuid();
        $now = date('Y-m-d H:i:s');

        return [
            $uuid,
            1, // user_id
            'perf-session-' . uniqid(),
            'auth.login.success',
            'authentication',
            'user',
            '1',
            'success',
            sprintf('批次測試記錄 #%d', $index),
            json_encode([
                'batch_test' => true,
                'sequence' => $index,
                'batch_size' => $batchSize,
            ]),
            '127.0.0.1',
            'PHPUnit Performance Test',
            'POST',
            '/test/batch',
            $now,
            $now,
        ];
    }

    /**
     * 清理測試資料.
     */
    private function cleanupTestData(): void
    {
        $this->pdo->exec('DELETE FROM user_activity_logs WHERE description LIKE "批次測試記錄%"');
    }

    /**
     * 驗證效能閾值
     */
    private function assertPerformanceThresholds(int $batchSize, float $duration, float $throughput): void
    {
        $this->assertLessThan(30.0, $duration, "批次插入 {$batchSize} 筆記錄應在 30 秒內完成");
        $this->assertGreaterThan(10.0, $throughput, '每秒應能處理至少 10 筆記錄');
    }

    /**
     * 驗證插入的資料.
     */
    private function verifyInsertedData(int $expectedCount): void
    {
        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM user_activity_logs WHERE description LIKE "批次測試記錄%"');
        if ($countStmt === false) {
            $this->fail('無法執行計數查詢');
        }

        $count = $countStmt->fetchColumn();
        if ($count === false) {
            $this->fail('無法取得計數結果');
        }

        $this->assertEquals($expectedCount, (int) $count, "應該插入 {$expectedCount} 筆記錄");
    }

    /**
     * 測試查詢效能.
     */
    #[Test]
    public function testQueryPerformance(): void
    {
        // 先建立測試資料
        $this->setupTestData(1000);

        $queries = $this->getQueryTestScenarios();

        foreach ($queries as $scenario => $query) {
            $this->executeQueryPerformanceTest($scenario, $query);
        }
    }

    /**
     * 取得查詢測試場景.
     *
     * @return array
     */
    private function getQueryTestScenarios(): array
    {
        return [
            '根據用戶ID查詢' => 'SELECT * FROM user_activity_logs WHERE user_id = 1 LIMIT 10',
            '根據動作類型查詢' => 'SELECT * FROM user_activity_logs WHERE action_type = "auth.login.success" LIMIT 10',
            '根據狀態查詢' => 'SELECT * FROM user_activity_logs WHERE status = "success" LIMIT 10',
            '根據時間範圍查詢' => 'SELECT * FROM user_activity_logs WHERE created_at >= datetime("now", "-1 days") LIMIT 10',
        ];
    }

    /**
     * 執行查詢效能測試.
     */
    private function executeQueryPerformanceTest(string $scenario, string $query): void
    {
        $iterations = 100;
        $startTime = microtime(true);

        $this->performRepeatedQueries($query, $iterations, $scenario);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $avgQueryTime = $duration / $iterations;

        // 效能斷言
        $this->assertLessThan(0.1, $avgQueryTime, "{$scenario} 平均查詢時間應小於 100ms");

        $this->outputQueryPerformanceReport($scenario, $duration, $avgQueryTime, $iterations);
    }

    /**
     * 執行重複查詢.
     */
    private function performRepeatedQueries(string $query, int $iterations, string $scenario): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            $stmt = $this->pdo->query($query);
            if ($stmt === false) {
                $this->fail("查詢執行失敗: {$scenario}");
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->assertIsArray($results, '查詢結果應為陣列');
        }
    }

    /**
     * 輸出查詢效能報告.
     */
    private function outputQueryPerformanceReport(string $scenario, float $duration, float $avgQueryTime, int $iterations): void
    {
        echo "\n{$scenario} 效能報告:\n";
        echo '- 總執行時間: ' . number_format($duration, 4) . " 秒\n";
        echo '- 平均查詢時間: ' . number_format($avgQueryTime * 1000, 2) . " ms\n";
        echo '- 每秒查詢數: ' . number_format($iterations / $duration, 2) . " QPS\n";
    }

    /**
     * 測試分頁查詢效能.
     */
    #[Test]
    public function testPaginationPerformance(): void
    {
        // 建立大量測試資料
        $this->setupTestData(5000);

        $pageSize = 50;
        $totalPages = 20; // 測試前 20 頁
        $results = $this->executePaginationTests($pageSize, $totalPages);

        $this->outputPaginationReport($results, $totalPages);
    }

    /**
     * 執行分頁測試.
     *
     * @return array
     */
    private function executePaginationTests(int $pageSize, int $totalPages): array
    {
        $results = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $results[$page] = $this->executeSinglePageTest($page, $pageSize);
        }

        return $results;
    }

    /**
     * 執行單頁測試.
     *
     * @return array{duration: float, count: int}
     */
    private function executeSinglePageTest(int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;

        $startTime = microtime(true);
        $logs = $this->executePaginationQuery($pageSize, $offset);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;

        // 每頁查詢時間不應超過 200ms
        $this->assertLessThan(0.2, $duration, "第 {$page} 頁查詢時間應小於 200ms");

        return [
            'duration' => $duration,
            'count' => count($logs),
        ];
    }

    /**
     * 執行分頁查詢.
     *
     * @return array
     */
    private function executePaginationQuery(int $pageSize, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_activity_logs WHERE user_id = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$pageSize, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 輸出分頁效能報告.
     *
     * @param array $results
     */
    private function outputPaginationReport(array $results, int $totalPages): void
    {
        // 計算統計資訊
        $totalTime = array_sum(array_column($results, 'duration'));
        $avgTime = $totalTime / $totalPages;

        echo "\n分頁查詢效能報告:\n";
        echo '- 總查詢時間: ' . number_format($totalTime, 4) . " 秒\n";
        echo '- 平均查詢時間: ' . number_format($avgTime * 1000, 2) . " ms\n";
        echo '- 每秒頁面數: ' . number_format($totalPages / $totalTime, 2) . " PPS\n";
    }

    /**
     * 建立測試資料.
     */
    private function setupTestData(int $count): void
    {
        // 清理現有測試資料
        $this->cleanupPerformanceTestData();

        $sql = $this->getTestDataInsertSql();
        $stmt = $this->pdo->prepare($sql);

        for ($i = 1; $i <= $count; $i++) {
            $params = $this->createTestDataParams($i, $count);
            $stmt->execute($params);
        }
    }

    /**
     * 清理效能測試資料.
     */
    private function cleanupPerformanceTestData(): void
    {
        $this->pdo->exec('DELETE FROM user_activity_logs WHERE description LIKE "效能測試資料%"');
    }

    /**
     * 取得測試資料插入 SQL.
     */
    private function getTestDataInsertSql(): string
    {
        return 'INSERT INTO user_activity_logs (
            uuid, user_id, session_id, action_type, action_category,
            target_type, target_id, status, description, metadata,
            ip_address, user_agent, request_method, request_path,
            created_at, occurred_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
    }

    /**
     * 建立測試資料參數.
     *
     * @return array
     */
    private function createTestDataParams(int $index, int $count): array
    {
        $uuid = $this->generateUuid();
        $now = date('Y-m-d H:i:s', time() - ($count - $index)); // 模擬時間分佈

        return [
            $uuid,
            ($index % 10) + 1, // 循環使用 1-10 的用戶ID
            'test-session-' . uniqid(),
            $this->getRandomActionType($index),
            $this->getRandomCategory($index),
            'performance_test',
            (string) $index,
            'success',
            sprintf('效能測試資料 #%d', $index),
            json_encode([
                'performance_test' => true,
                'sequence' => $index,
                'timestamp' => time(),
            ]),
            '127.0.0.1',
            'PHPUnit Performance Setup',
            'POST',
            '/test/performance',
            $now,
            $now,
        ];
    }

    /**
     * 取得隨機動作類型.
     */
    private function getRandomActionType(int $seed): string
    {
        $types = [
            'auth.login.success',
            'auth.login.failed',
            'auth.logout',
            'post.created',
            'post.viewed',
            'attachment.uploaded',
            'attachment.downloaded',
        ];

        return $types[$seed % count($types)];
    }

    /**
     * 取得隨機類別.
     */
    private function getRandomCategory(int $seed): string
    {
        $categories = [
            'authentication',
            'content',
            'file_management',
            'security',
        ];

        return $categories[$seed % count($categories)];
    }

    /**
     * 產生 UUID.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );
    }

    /**
     * 輸出效能報告.
     *
     * @param array $results
     */
    private function outputPerformanceReport(string $title, array $results): void
    {
        echo "\n{$title}:\n";
        echo str_repeat('=', strlen($title) + 1) . "\n";

        foreach ($results as $size => $result) {
            echo "批次大小: {$size}\n";
            echo '  - 執行時間: ' . number_format($result['duration'], 4) . " 秒\n";
            echo '  - 吞吐量: ' . number_format($result['throughput'], 2) . " 筆/秒\n";
            echo '  - 平均每筆: ' . number_format($result['avg_per_record'] * 1000, 2) . " ms\n\n";
        }
    }
}
