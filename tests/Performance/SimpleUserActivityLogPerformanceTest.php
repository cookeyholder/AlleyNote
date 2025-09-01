<?php

declare(strict_types=1);

namespace Tests\Performance;

use Exception;
use PDO;
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
        } catch (Exception $e) {
            $this->markTestSkipped('無法連接資料庫: ' . $e->getMessage());
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
            // 清理測試資料
            $this->pdo->exec('DELETE FROM user_activity_logs WHERE description LIKE "批次測試記錄%"');

            $startTime = microtime(true);

            // 準備批次插入語句
            $sql = 'INSERT INTO user_activity_logs (
                uuid, user_id, session_id, action_type, action_category,
                target_type, target_id, status, description, metadata,
                ip_address, user_agent, request_method, request_path,
                created_at, occurred_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $this->pdo->prepare($sql);

            for ($i = 0; $i < $batchSize; $i++) {
                $uuid = $this->generateUuid();
                $now = date('Y-m-d H:i:s');

                $stmt->execute([
                    $uuid,
                    1, // user_id
                    'perf-session-' . uniqid(),
                    'auth.login.success',
                    'authentication',
                    'user',
                    '1',
                    'success',
                    "批次測試記錄 #{$i}",
                    json_encode([
                        'batch_test' => true,
                        'sequence' => $i,
                        'batch_size' => $batchSize,
                    ]),
                    '127.0.0.1',
                    'PHPUnit Performance Test',
                    'POST',
                    '/test/batch',
                    $now,
                    $now,
                ]);
            }

            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $throughput = $batchSize / $duration;

            $results[$batchSize] = [
                'duration' => $duration,
                'throughput' => $throughput,
                'avg_per_record' => $duration / $batchSize,
            ];

            // 效能斷言
            $this->assertLessThan(30.0, $duration, "批次插入 {$batchSize} 筆記錄應在 30 秒內完成");
            $this->assertGreaterThan(10.0, $throughput, '每秒應能處理至少 10 筆記錄');

            // 驗證資料是否正確插入
            $countStmt = $this->pdo->query('SELECT COUNT(*) FROM user_activity_logs WHERE description LIKE "批次測試記錄%"');
            if ($countStmt === false) {
                $this->fail('無法執行計數查詢');
            }

            $count = $countStmt->fetchColumn();
            if ($count === false) {
                $this->fail('無法取得計數結果');
            }

            $this->assertEquals($batchSize, (int) $count, "應該插入 {$batchSize} 筆記錄");
        }

        // 輸出效能報告
        $this->outputPerformanceReport('批次插入效能測試', $results);
    }

    /**
     * 測試查詢效能.
     */
    #[Test]
    public function testQueryPerformance(): void
    {
        // 先建立測試資料
        $this->setupTestData(1000);

        $queries = [
            '根據用戶ID查詢' => 'SELECT * FROM user_activity_logs WHERE user_id = 1 LIMIT 10',
            '根據動作類型查詢' => 'SELECT * FROM user_activity_logs WHERE action_type = "auth.login.success" LIMIT 10',
            '根據狀態查詢' => 'SELECT * FROM user_activity_logs WHERE status = "success" LIMIT 10',
            '根據時間範圍查詢' => 'SELECT * FROM user_activity_logs WHERE created_at >= datetime("now", "-1 day") LIMIT 10',
        ];

        foreach ($queries as $scenario => $query) {
            $iterations = 100;
            $startTime = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                $stmt = $this->pdo->query($query);
                if ($stmt === false) {
                    $this->fail("查詢執行失敗: {$scenario}");
                }

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->assertIsArray($results, '查詢結果應為陣列');
            }

            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $avgQueryTime = $duration / $iterations;

            // 效能斷言
            $this->assertLessThan(0.1, $avgQueryTime, "{$scenario} 平均查詢時間應小於 100ms");

            echo "\n{$scenario} 效能報告:\n";
            echo '- 總執行時間: ' . number_format($duration, 4) . " 秒\n";
            echo '- 平均查詢時間: ' . number_format($avgQueryTime * 1000, 2) . " ms\n";
            echo '- 每秒查詢數: ' . number_format($iterations / $duration, 2) . " QPS\n";
        }
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
        $results = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $offset = ($page - 1) * $pageSize;

            $startTime = microtime(true);

            $stmt = $this->pdo->prepare(
                'SELECT * FROM user_activity_logs WHERE user_id = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?',
            );
            $stmt->execute([$pageSize, $offset]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            $results[$page] = [
                'duration' => $duration,
                'count' => count($logs),
            ];

            // 每頁查詢時間不應超過 200ms
            $this->assertLessThan(0.2, $duration, "第 {$page} 頁查詢時間應小於 200ms");
        }

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
        $this->pdo->exec('DELETE FROM user_activity_logs WHERE description LIKE "效能測試資料%"');

        $sql = 'INSERT INTO user_activity_logs (
            uuid, user_id, session_id, action_type, action_category,
            target_type, target_id, status, description, metadata,
            ip_address, user_agent, request_method, request_path,
            created_at, occurred_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->pdo->prepare($sql);

        for ($i = 1; $i <= $count; $i++) {
            $uuid = $this->generateUuid();
            $now = date('Y-m-d H:i:s', time() - ($count - $i)); // 模擬時間分佈

            $stmt->execute([
                $uuid,
                ($i % 10) + 1, // 循環使用 1-10 的用戶ID
                'test-session-' . uniqid(),
                $this->getRandomActionType($i),
                $this->getRandomCategory($i),
                'performance_test',
                (string) $i,
                'success',
                "效能測試資料 #{$i}",
                json_encode([
                    'performance_test' => true,
                    'sequence' => $i,
                    'timestamp' => time(),
                ]),
                '127.0.0.1',
                'PHPUnit Performance Setup',
                'POST',
                '/test/performance',
                $now,
                $now,
            ]);
        }
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
     * @param array<int, array{duration: float, throughput: float, avg_per_record: float}> $results
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
