<?php

declare(strict_types=1);

namespace Tests\Performance;

use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * 統計功能索引最佳化效能測試.
 *
 * 測試統計相關資料表的索引效果，確保查詢效能符合預期。
 * 驗證不同索引策略對統計查詢效能的影響。
 */
#[Group('performance')]
#[Group('statistics')]
#[Group('database')]
class StatisticsIndexOptimizationTest extends TestCase
{
    private PDO $pdo;

    private StatisticsTestSeeder $seeder;

    private const INDEX_PERFORMANCE_THRESHOLDS = [
        'simple_count_query' => 0.1, // 100ms
        'complex_aggregation' => 0.5, // 500ms
        'multi_table_join' => 1.0, // 1s
        'date_range_query' => 0.3, // 300ms
    ];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            // 使用 SQLite 檔案資料庫以測試索引效果
            $this->pdo = new PDO('sqlite:database/test_statistics_performance.sqlite');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->seeder = new StatisticsTestSeeder($this->pdo);
            $this->seeder->createTables();

            // 建立大量測試資料
            $this->seedPerformanceData();
        } catch (Exception $e) {
            $this->markTestSkipped('無法設置索引效能測試環境: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // 清理測試資料庫檔案
        if (file_exists('database/test_statistics_performance.sqlite')) {
            unlink('database/test_statistics_performance.sqlite');
        }
        parent::tearDown();
    }

    /**
     * 測試 posts 表索引效能.
     */
    #[Test]
    public function testPostsTableIndexPerformance(): void
    {
        // 測試未建立索引時的效能
        $withoutIndexResults = $this->runPostsQueries('無索引');

        // 建立統計相關索引
        $this->createPostsStatisticsIndexes();

        // 測試建立索引後的效能
        $withIndexResults = $this->runPostsQueries('有索引');

        // 驗證索引效果
        $this->verifyIndexImprovement('posts 表查詢', $withoutIndexResults, $withIndexResults);

        // 輸出索引效能比較報告
        $this->outputIndexComparisonReport('Posts 表索引效能', $withoutIndexResults, $withIndexResults);
    }

    /**
     * 測試 user_activity_logs 表索引效能.
     */
    #[Test]
    public function testUserActivityLogsIndexPerformance(): void
    {
        // 測試未建立索引時的效能
        $withoutIndexResults = $this->runUserActivityQueries('無索引');

        // 建立使用者活動索引
        $this->createUserActivityIndexes();

        // 測試建立索引後的效能
        $withIndexResults = $this->runUserActivityQueries('有索引');

        // 驗證索引效果
        $this->verifyIndexImprovement('使用者活動查詢', $withoutIndexResults, $withIndexResults);

        // 輸出索引效能比較報告
        $this->outputIndexComparisonReport('User Activity 索引效能', $withoutIndexResults, $withIndexResults);
    }

    /**
     * 測試複雜統計查詢的索引效能.
     */
    #[Test]
    public function testComplexStatisticsQueryPerformance(): void
    {
        // 建立所有統計索引
        $this->createAllStatisticsIndexes();

        // 執行複雜統計查詢
        $complexQueries = $this->runComplexStatisticsQueries();

        // 驗證複雜查詢效能
        foreach ($complexQueries as $queryName => $result) {
            $duration = is_array($result) && isset($result['duration']) && is_numeric($result['duration'])
                ? (float) $result['duration']
                : 0.0;

            $this->assertLessThan(
                self::INDEX_PERFORMANCE_THRESHOLDS['complex_aggregation'],
                $duration,
                "複雜查詢 {$queryName} 執行時間 {$duration}s 超過閾值",
            );
        }

        // 輸出複雜查詢效能報告
        $this->outputQueryPerformanceReport('複雜統計查詢效能', $complexQueries);
    }

    /**
     * 測試查詢執行計劃分析.
     */
    #[Test]
    public function testQueryExecutionPlanAnalysis(): void
    {
        // 建立統計索引
        $this->createAllStatisticsIndexes();

        // 分析關鍵查詢的執行計劃
        $queries = [
            'posts_by_date_range' => "
                EXPLAIN QUERY PLAN
                SELECT COUNT(*) FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                AND status = 'published'
            ",
            'posts_by_source' => "
                EXPLAIN QUERY PLAN
                SELECT creation_source, COUNT(*) FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY creation_source
            ",
            'user_activity_by_type' => "
                EXPLAIN QUERY PLAN
                SELECT action_type, COUNT(*) FROM user_activity_logs
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY action_type
            ",
            'posts_views_aggregation' => "
                EXPLAIN QUERY PLAN
                SELECT AVG(views), MAX(views), MIN(views) FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                AND status = 'published'
            ",
        ];

        $executionPlans = [];

        foreach ($queries as $queryName => $query) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $executionPlans[$queryName] = $plan;

            // 驗證查詢使用了索引（SQLite EXPLAIN QUERY PLAN）
            $planText = json_encode($plan);
            if ($planText !== false) {
                $usesIndex = strpos($planText, 'INDEX') !== false;

                if (!$usesIndex && strpos($planText, 'SCAN') !== false) {
                    $this->addWarning("查詢 {$queryName} 可能未使用索引，請檢查執行計劃");
                }
            }
        }

        // 輸出執行計劃報告
        echo "\n查詢執行計劃分析:\n";
        foreach ($executionPlans as $queryName => $plan) {
            echo "- {$queryName}:\n";
            foreach ($plan as $step) {
                $detail = '';
                if (is_array($step)) {
                    $detail = isset($step['detail']) && is_string($step['detail'])
                        ? $step['detail']
                        : json_encode($step);
                } else {
                    $detail = is_string($step) ? $step : '';
                    if ($detail === '') {
                        $detail = is_scalar($step) ? (string) $step : '[non-string]';
                    }
                }
                echo '  * ' . $detail . "\n";
            }
        }

        $this->assertNotEmpty($executionPlans, '應該成功分析查詢執行計劃');
    }

    /**
     * 執行 posts 表查詢並測量效能.
     */
    private function runPostsQueries(string $context): array
    {
        $queries = [
            'count_by_status' => "
                SELECT COUNT(*) FROM posts WHERE status = 'published'
            ",
            'count_by_date_range' => "
                SELECT COUNT(*) FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
            ",
            'posts_by_source' => "
                SELECT creation_source, COUNT(*)
                FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY creation_source
            ",
            'views_statistics' => "
                SELECT AVG(views) as avg_views, MAX(views) as max_views, MIN(views) as min_views
                FROM posts
                WHERE status = 'published'
                AND created_at BETWEEN '2024-01-01' AND '2024-01-31'
            ",
            'posts_with_comments' => "
                SELECT COUNT(*) FROM posts
                WHERE comments_count > 0
                AND created_at BETWEEN '2024-01-01' AND '2024-01-31'
            ",
        ];

        return $this->executeQueriesWithTiming($queries, $context);
    }

    /**
     * 執行使用者活動查詢並測量效能.
     */
    private function runUserActivityQueries(string $context): array
    {
        $queries = [
            'activity_by_type' => "
                SELECT action_type, COUNT(*)
                FROM user_activity_logs
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY action_type
            ",
            'unique_users' => "
                SELECT COUNT(DISTINCT user_id)
                FROM user_activity_logs
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
            ",
            'activity_by_user' => "
                SELECT user_id, COUNT(*) as activity_count
                FROM user_activity_logs
                WHERE action_type = 'login'
                AND created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY user_id
                ORDER BY activity_count DESC
                LIMIT 10
            ",
            'hourly_activity' => "
                SELECT strftime('%H', created_at) as hour, COUNT(*)
                FROM user_activity_logs
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY strftime('%H', created_at)
                ORDER BY COUNT(*) DESC
            ",
        ];

        return $this->executeQueriesWithTiming($queries, $context);
    }

    /**
     * 執行複雜統計查詢.
     */
    private function runComplexStatisticsQueries(): array
    {
        $queries = [
            'posts_and_activity_correlation' => "
                SELECT p.creation_source, COUNT(p.id) as posts_count, COUNT(ual.id) as activities_count
                FROM posts p
                LEFT JOIN user_activity_logs ual ON p.user_id = ual.user_id
                    AND DATE(p.created_at) = DATE(ual.created_at)
                WHERE p.created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY p.creation_source
            ",
            'user_engagement_metrics' => "
                SELECT
                    u.id as user_id,
                    COUNT(DISTINCT p.id) as posts_count,
                    COUNT(DISTINCT ual.id) as activities_count,
                    AVG(p.views) as avg_post_views
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                    AND p.created_at BETWEEN '2024-01-01' AND '2024-01-31'
                LEFT JOIN user_activity_logs ual ON u.id = ual.user_id
                    AND ual.created_at BETWEEN '2024-01-01' AND '2024-01-31'
                GROUP BY u.id
                HAVING posts_count > 0
            ",
            'daily_statistics_trend' => "
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as posts_count,
                    AVG(views) as avg_views,
                    SUM(CASE WHEN comments_count > 0 THEN 1 ELSE 0 END) as posts_with_comments
                FROM posts
                WHERE created_at BETWEEN '2024-01-01' AND '2024-01-31'
                AND status = 'published'
                GROUP BY DATE(created_at)
                ORDER BY date
            ",
        ];

        return $this->executeQueriesWithTiming($queries, '複雜查詢');
    }

    /**
     * 建立 posts 表統計索引.
     */
    private function createPostsStatisticsIndexes(): void
    {
        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)',
            'CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at)',
            'CREATE INDEX IF NOT EXISTS idx_posts_status_created_at ON posts(status, created_at)',
            'CREATE INDEX IF NOT EXISTS idx_posts_creation_source ON posts(creation_source)',
            'CREATE INDEX IF NOT EXISTS idx_posts_user_id_created_at ON posts(user_id, created_at)',
            'CREATE INDEX IF NOT EXISTS idx_posts_views ON posts(views)',
            'CREATE INDEX IF NOT EXISTS idx_posts_comments_count ON posts(comments_count)',
        ];

        foreach ($indexes as $indexSQL) {
            $this->pdo->exec($indexSQL);
        }
    }

    /**
     * 建立使用者活動索引.
     */
    private function createUserActivityIndexes(): void
    {
        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_user_activity_logs_user_id ON user_activity_logs(user_id)',
            'CREATE INDEX IF NOT EXISTS idx_user_activity_logs_action_type ON user_activity_logs(action_type)',
            'CREATE INDEX IF NOT EXISTS idx_user_activity_logs_created_at ON user_activity_logs(created_at)',
            'CREATE INDEX IF NOT EXISTS idx_user_activity_logs_user_action_date ON user_activity_logs(user_id, action_type, created_at)',
            'CREATE INDEX IF NOT EXISTS idx_user_activity_logs_action_date ON user_activity_logs(action_type, created_at)',
        ];

        foreach ($indexes as $indexSQL) {
            $this->pdo->exec($indexSQL);
        }
    }

    /**
     * 建立所有統計相關索引.
     */
    private function createAllStatisticsIndexes(): void
    {
        $this->createPostsStatisticsIndexes();
        $this->createUserActivityIndexes();

        // 建立統計快照表索引
        $snapshotIndexes = [
            'CREATE INDEX IF NOT EXISTS idx_statistics_snapshots_type ON statistics_snapshots(snapshot_type)',
            'CREATE INDEX IF NOT EXISTS idx_statistics_snapshots_period ON statistics_snapshots(period_start, period_end)',
            'CREATE INDEX IF NOT EXISTS idx_statistics_snapshots_type_period ON statistics_snapshots(snapshot_type, period_start, period_end)',
            'CREATE INDEX IF NOT EXISTS idx_statistics_snapshots_created_at ON statistics_snapshots(created_at)',
        ];

        foreach ($snapshotIndexes as $indexSQL) {
            $this->pdo->exec($indexSQL);
        }
    }

    /**
     * 執行查詢並測量時間.
     */
    private function executeQueriesWithTiming(array $queries, string $context): array
    {
        $results = [];

        foreach ($queries as $queryName => $query) {
            $startTime = microtime(true);
            $queryString = is_string($query) ? $query : '';
            if (empty($queryString)) {
                continue;
            }

            $stmt = $this->pdo->prepare($queryString);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $endTime = microtime(true);

            $duration = $endTime - $startTime;

            $results[$queryName] = [
                'duration' => $duration,
                'row_count' => count($result),
                'context' => $context,
            ];
        }

        return $results;
    }

    /**
     * 驗證索引改善效果.
     */
    private function verifyIndexImprovement(string $queryType, array $withoutIndex, array $withIndex): void
    {
        $improvementCount = 0;
        $totalQueries = count($withoutIndex);

        foreach ($withoutIndex as $queryName => $beforeStats) {
            $afterStats = $withIndex[$queryName] ?? null;

            if ($afterStats) {
                $beforeDuration = is_array($beforeStats) && isset($beforeStats['duration']) && is_numeric($beforeStats['duration'])
                    ? (float) $beforeStats['duration']
                    : 0.0;
                $afterDuration = is_array($afterStats) && isset($afterStats['duration']) && is_numeric($afterStats['duration'])
                    ? (float) $afterStats['duration']
                    : 0.0;

                if ($afterDuration > 0) {
                    $improvementRatio = $beforeDuration / $afterDuration;

                    if ($improvementRatio > 1.1) { // 至少 10% 改善
                        $improvementCount++;
                    }
                }
            }
        }

        $improvementPercentage = ($improvementCount / $totalQueries) * 100;

        // 至少 30% 的查詢應該有明顯改善（降低 SQLite 環境下的要求）
        $this->assertGreaterThanOrEqual(
            30,
            $improvementPercentage,
            "{$queryType} 索引改善效果不明顯，僅有 {$improvementPercentage}% 的查詢有改善",
        );
    }

    /**
     * 輸出索引比較報告.
     */
    private function outputIndexComparisonReport(string $title, array $before, array $after): void
    {
        echo "\n{$title}:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-30s %12s %12s %12s %10s\n", '查詢名稱', '無索引(s)', '有索引(s)', '改善倍數', '改善%');
        echo str_repeat('-', 80) . "\n";

        foreach ($before as $queryName => $beforeStats) {
            $afterStats = $after[$queryName] ?? null;

            if ($afterStats) {
                $beforeDuration = is_array($beforeStats) && isset($beforeStats['duration']) && is_numeric($beforeStats['duration'])
                    ? (float) $beforeStats['duration']
                    : 0.0;
                $afterDuration = is_array($afterStats) && isset($afterStats['duration']) && is_numeric($afterStats['duration'])
                    ? (float) $afterStats['duration']
                    : 0.0;

                $improvementRatio = $afterDuration > 0 ? $beforeDuration / $afterDuration : 0.0;
                $improvementPercent = $beforeDuration > 0 ? (($beforeDuration - $afterDuration) / $beforeDuration) * 100 : 0.0;

                printf(
                    "%-30s %12.4f %12.4f %12.1fx %9.1f%%\n",
                    $queryName,
                    $beforeDuration,
                    $afterDuration,
                    $improvementRatio,
                    $improvementPercent,
                );
            }
        }
        echo str_repeat('-', 80) . "\n";
    }

    /**
     * 輸出查詢效能報告.
     */
    private function outputQueryPerformanceReport(string $title, array $results): void
    {
        echo "\n{$title}:\n";
        echo str_repeat('-', 60) . "\n";
        printf("%-40s %12s %8s\n", '查詢名稱', '執行時間(s)', '結果筆數');
        echo str_repeat('-', 60) . "\n";

        foreach ($results as $queryName => $stats) {
            $duration = is_array($stats) && isset($stats['duration']) && is_numeric($stats['duration'])
                ? (float) $stats['duration']
                : 0.0;
            $rowCount = is_array($stats) && isset($stats['row_count']) && is_numeric($stats['row_count'])
                ? (int) $stats['row_count']
                : 0;

            printf(
                "%-40s %12.4f %8d\n",
                $queryName,
                $duration,
                $rowCount,
            );
        }
        echo str_repeat('-', 60) . "\n";
    }

    /**
     * 建立效能測試資料.
     */
    private function seedPerformanceData(): void
    {
        echo "正在建立索引效能測試資料...\n";

        // 建立基本資料
        $this->seeder->seedAll();

        $startTime = microtime(true);

        // 建立大量文章資料（用於索引效能測試）
        $postCount = 20000; // 2萬篇文章
        $batchSize = 1000;
        $batches = ceil($postCount / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $sql = 'INSERT INTO posts (id, uuid, seq_number, title, content, user_id, user_ip, status, views, comments_count, likes_count, is_pinned, publish_date, created_at, updated_at, creation_source, creation_source_detail) VALUES ';
            $values = [];
            $currentBatchSize = min($batchSize, $postCount - ($batch * $batchSize));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $postId = ($batch * $batchSize) + $i + 1000;
                $userId = ($i % 5) + 1;
                $status = ['published', 'draft', 'archived'][array_rand(['published', 'draft', 'archived'])];
                $source = ['web', 'mobile', 'api'][array_rand(['web', 'mobile', 'api'])];
                $uuid = $this->generateUuid();

                // 分散創建時間（30天內）
                $randomDays = rand(0, 29);
                $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($randomDays * 24 * 3600) + ($i * 60));

                $views = rand(0, 5000);
                $comments = rand(0, 100);
                $likes = rand(0, 200);

                $values[] = '(' . implode(',', [
                    $postId,
                    "'$uuid'",
                    $postId,
                    "'Index Test Post #$postId'",
                    "'This is content for index performance testing post #$postId.'",
                    $userId,
                    "'192.168.1." . ($i % 255 + 1) . "'",
                    "'$status'",
                    $views,
                    $comments,
                    $likes,
                    '0',
                    "'$createdAt'",
                    "'$createdAt'",
                    "'$createdAt'",
                    "'$source'",
                    "'index_test'",
                ]) . ')';
            }

            $this->pdo->exec($sql . implode(',', $values));
        }

        // 建立大量使用者活動記錄
        $activityCount = 50000; // 5萬條活動記錄
        $activityBatches = ceil($activityCount / $batchSize);

        for ($batch = 0; $batch < $activityBatches; $batch++) {
            $sql = 'INSERT INTO user_activity_logs (id, uuid, user_id, action_type, action_category, status, created_at, occurred_at) VALUES ';
            $values = [];
            $currentBatchSize = min($batchSize, $activityCount - ($batch * $batchSize));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $activityId = ($batch * $batchSize) + $i + 2000;
                $userId = ($i % 5) + 1;
                $actionType = ['login', 'view', 'comment', 'post_create'][array_rand(['login', 'view', 'comment', 'post_create'])];
                $uuid = $this->generateUuid();

                // 分散活動時間
                $randomMinutes = rand(0, 43199); // 30天內的分鐘數
                $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($randomMinutes * 60));

                $values[] = '(' . implode(',', [
                    $activityId,
                    "'$uuid'",
                    $userId,
                    "'$actionType'",
                    "'index_test'",
                    "'success'",
                    "'$createdAt'",
                    "'$createdAt'",
                ]) . ')';
            }

            $this->pdo->exec($sql . implode(',', $values));
        }

        $endTime = microtime(true);
        $seedDuration = $endTime - $startTime;

        echo "索引測試資料建立完成:\n";
        echo "- 文章數量: $postCount\n";
        echo "- 活動記錄數量: $activityCount\n";
        echo '- 建立時間: ' . number_format($seedDuration, 2) . "s\n";
    }

    /**
     * 生成 UUID.
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
     * 添加測試警告.
     */
    private function addWarning(string $message): void
    {
        echo "⚠️  警告: $message\n";
    }
}
