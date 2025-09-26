<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use App\Infrastructure\Statistics\Repositories\StatisticsRepository;
use App\Infrastructure\Statistics\Repositories\UserStatisticsRepository;
use DateTimeImmutable;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * 統計功能效能測試.
 *
 * 測試統計功能在大量資料下的效能表現，包括：
 * - 大量資料統計計算（10萬+ 記錄）
 * - 複雜查詢效能測試
 * - 快取效能測試
 * - 記憶體使用量監控
 */
#[Group('performance')]
#[Group('statistics')]
class StatisticsPerformanceTest extends TestCase
{
    private PDO $pdo;

    private PostStatisticsRepository $postStatsRepo;

    private UserStatisticsRepository $userStatsRepo;

    private StatisticsRepository $statsRepo;

    private StatisticsTestSeeder $seeder;

    private const PERFORMANCE_THRESHOLDS = [
        'api_response_time' => 2.0, // 秒
        'large_dataset_query_time' => 5.0, // 秒
        'cache_hit_rate_minimum' => 0.80, // 80%
        'memory_usage_limit' => 256 * 1024 * 1024, // 256MB
        'concurrent_requests_max_time' => 3.0, // 秒
    ];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            // 使用 SQLite 記憶體資料庫進行效能測試
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 建立測試用的 Repository
            $this->postStatsRepo = new PostStatisticsRepository($this->pdo);
            $this->userStatsRepo = new UserStatisticsRepository($this->pdo);
            $this->statsRepo = new StatisticsRepository($this->pdo);

            // 建立測試資料種子
            $this->seeder = new StatisticsTestSeeder($this->pdo);
            $this->seeder->createTables();
        } catch (Exception $e) {
            $this->markTestSkipped('無法設置測試環境: ' . $e->getMessage());
        }
    }

    /**
     * 測試大量資料下的統計查詢效能.
     */
    #[Test]
    public function testLargeDatasetStatisticsPerformance(): void
    {
        // 建立大量測試資料
        $this->seedLargeDataset(50000); // 5萬筆記錄

        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-31 23:59:59'),
        );

        // 測試各種統計查詢的效能
        $queries = [
            'getTotalPostsCount' => fn() => $this->postStatsRepo->getTotalPostsCount($period),
            'getPostsCountByStatus' => fn() => $this->postStatsRepo->getPostsCountByStatus($period),
            'getPostsCountBySource' => fn() => $this->postStatsRepo->getPostsCountBySource($period),
            'getPostViewsStatistics' => fn() => $this->postStatsRepo->getPostViewsStatistics($period),
            'getPostsLengthStatistics' => fn() => $this->postStatsRepo->getPostsLengthStatistics($period),
            'getActiveUsersCount' => fn() => $this->userStatsRepo->getActiveUsersCount($period),
            'getTotalUsersCount' => fn() => $this->userStatsRepo->getTotalUsersCount($period),
        ];

        $performanceResults = [];
        $memoryUsage = [];

        foreach ($queries as $queryName => $queryFunc) {
            // 記錄初始記憶體使用量
            $initialMemory = memory_get_usage(true);

            $startTime = microtime(true);
            $result = $queryFunc();
            $endTime = microtime(true);

            // 記錄峰值記憶體使用量
            $peakMemory = memory_get_peak_usage(true);
            $memoryUsed = $peakMemory - $initialMemory;

            $duration = $endTime - $startTime;

            $performanceResults[$queryName] = [
                'duration' => $duration,
                'memory_used' => $memoryUsed,
                'result_size' => is_array($result) ? count($result) : 1,
            ];

            $memoryUsage[$queryName] = $memoryUsed;

            // 驗證查詢在可接受時間內完成
            $this->assertLessThan(
                self::PERFORMANCE_THRESHOLDS['large_dataset_query_time'],
                $duration,
                "查詢 {$queryName} 執行時間 {$duration}s 超過閾值 " . self::PERFORMANCE_THRESHOLDS['large_dataset_query_time'] . 's',
            );

            // 驗證記憶體使用在合理範圍內
            $this->assertLessThan(
                self::PERFORMANCE_THRESHOLDS['memory_usage_limit'],
                $memoryUsed,
                "查詢 {$queryName} 記憶體使用量 " . ($memoryUsed / 1024 / 1024) . 'MB 超過限制',
            );

            // 清理記憶體
            unset($result);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        // 輸出效能報告
        $this->outputPerformanceReport('大量資料統計查詢效能', $performanceResults);
    }

    /**
     * 測試統計 API 回應時間效能.
     */
    #[Test]
    public function testStatisticsApiResponseTime(): void
    {
        // 建立中等規模測試資料
        $this->seedLargeDataset(10000); // 1萬筆記錄

        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        // 模擬統計 API 的典型查詢組合
        $apiOperations = [
            'overview_stats' => function () use ($period) {
                return [
                    'total_posts' => $this->postStatsRepo->getTotalPostsCount($period),
                    'total_users' => $this->userStatsRepo->getTotalUsersCount($period),
                    'active_users' => $this->userStatsRepo->getActiveUsersCount($period),
                    'post_views' => $this->postStatsRepo->getPostViewsStatistics($period),
                ];
            },
            'posts_analysis' => function () use ($period) {
                return [
                    'by_status' => $this->postStatsRepo->getPostsCountByStatus($period),
                    'by_source' => $this->postStatsRepo->getPostsCountBySource($period),
                    'length_stats' => $this->postStatsRepo->getPostsLengthStatistics($period),
                ];
            },
            'user_insights' => function () use ($period) {
                return [
                    'total_users' => $this->userStatsRepo->getTotalUsersCount($period),
                    'active_users' => $this->userStatsRepo->getActiveUsersCount($period, 'login'),
                    'new_users' => $this->userStatsRepo->getNewUsersCount($period),
                ];
            },
        ];

        $apiResults = [];

        foreach ($apiOperations as $operationName => $operation) {
            $startTime = microtime(true);
            $result = $operation();
            $endTime = microtime(true);

            $duration = $endTime - $startTime;

            $apiResults[$operationName] = [
                'duration' => $duration,
                'data_points' => $this->countDataPoints($result),
            ];

            // 驗證 API 回應時間符合要求
            $this->assertLessThan(
                self::PERFORMANCE_THRESHOLDS['api_response_time'],
                $duration,
                "API 操作 {$operationName} 回應時間 {$duration}s 超過閾值 " . self::PERFORMANCE_THRESHOLDS['api_response_time'] . 's',
            );
        }

        // 輸出 API 效能報告
        $this->outputPerformanceReport('統計 API 回應時間效能', $apiResults);
    }

    /**
     * 測試並發查詢效能.
     */
    #[Test]
    public function testConcurrentQueryPerformance(): void
    {
        // 建立測試資料
        $this->seedLargeDataset(20000); // 2萬筆記錄

        $period = new StatisticsPeriod(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-07 23:59:59'),
        );

        // 模擬並發查詢（在同步環境中模擬）
        $concurrentQueries = [
            fn() => $this->postStatsRepo->getTotalPostsCount($period),
            fn() => $this->postStatsRepo->getPostsCountByStatus($period),
            fn() => $this->postStatsRepo->getPostViewsStatistics($period),
            fn() => $this->userStatsRepo->getActiveUsersCount($period),
            fn() => $this->userStatsRepo->getTotalUsersCount($period),
        ];

        $startTime = microtime(true);
        $results = [];

        // 執行所有查詢（模擬並發）
        foreach ($concurrentQueries as $i => $query) {
            $queryStartTime = microtime(true);
            $results[$i] = $query();
            $queryEndTime = microtime(true);

            // 確保查詢返回有效結果（數字或非空陣列）
            $result = $results[$i];
            if (is_int($result)) {
                $this->assertGreaterThanOrEqual(0, $result, "並發查詢 {$i} 應該返回非負數結果");
            } else {
                // 如果不是整數，就是陣列，檢查非空
                $this->assertNotEmpty($result, "並發查詢 {$i} 應該返回非空陣列結果");
            }
        }

        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;

        // 驗證並發查詢總時間在可接受範圍內
        $this->assertLessThan(
            self::PERFORMANCE_THRESHOLDS['concurrent_requests_max_time'],
            $totalDuration,
            "並發查詢總時間 {$totalDuration}s 超過閾值 " . self::PERFORMANCE_THRESHOLDS['concurrent_requests_max_time'] . 's',
        );

        // 輸出並發效能報告
        $this->addToAssertionCount(1); // 標記測試已執行斷言
        echo "\n並發查詢效能報告:\n";
        echo '- 查詢數量: ' . count($concurrentQueries) . "\n";
        echo '- 總執行時間: ' . number_format($totalDuration, 3) . "s\n";
        echo '- 平均每查詢時間: ' . number_format($totalDuration / count($concurrentQueries), 3) . "s\n";
    }

    /**
     * 測試記憶體使用效率.
     */
    #[Test]
    public function testMemoryUsageEfficiency(): void
    {
        $initialMemory = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);

        // 建立中等規模資料集
        $this->seedLargeDataset(30000); // 3萬筆記錄

        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-31 23:59:59'),
        );

        // 執行記憶體密集的統計操作
        $memoryTestResults = [];

        $queries = [
            'large_aggregation' => fn() => $this->postStatsRepo->getPostsCountBySource($period),
            'complex_calculation' => fn() => $this->postStatsRepo->getPostsLengthStatistics($period),
            'user_analysis' => fn() => $this->userStatsRepo->getActiveUsersCount($period),
        ];

        foreach ($queries as $queryName => $query) {
            $memoryBefore = memory_get_usage(true);
            $result = $query();
            $memoryAfter = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            $memoryIncrease = $memoryAfter - $memoryBefore;
            $peakIncrease = $peakMemory - $peakMemoryBefore;

            $memoryTestResults[$queryName] = [
                'memory_increase' => $memoryIncrease,
                'peak_increase' => $peakIncrease,
                'result_size' => is_array($result) ? count($result) : 1,
            ];

            // 驗證記憶體使用不會無限增長
            $this->assertLessThan(
                50 * 1024 * 1024, // 50MB
                $memoryIncrease,
                "查詢 {$queryName} 記憶體增長過多: " . ($memoryIncrease / 1024 / 1024) . 'MB',
            );

            // 清理記憶體
            unset($result);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // 輸出記憶體使用報告
        echo "\n記憶體使用效率報告:\n";
        echo '- 初始記憶體: ' . number_format($initialMemory / 1024 / 1024, 2) . "MB\n";
        echo '- 最終記憶體: ' . number_format($finalMemory / 1024 / 1024, 2) . "MB\n";
        echo '- 峰值記憶體: ' . number_format($peakMemory / 1024 / 1024, 2) . "MB\n";
        echo '- 記憶體增長: ' . number_format(($finalMemory - $initialMemory) / 1024 / 1024, 2) . "MB\n";

        foreach ($memoryTestResults as $queryName => $stats) {
            echo "- {$queryName}: +" . number_format($stats['memory_increase'] / 1024 / 1024, 2) . 'MB (峰值: +'
                 . number_format($stats['peak_increase'] / 1024 / 1024, 2) . "MB)\n";
        }
    }

    /**
     * 測試統計快照的儲存和檢索效能.
     */
    #[Test]
    public function testStatisticsSnapshotPerformance(): void
    {
        $snapshotCount = 1000;
        $batchSizes = [10, 50, 100];
        $results = [];

        foreach ($batchSizes as $batchSize) {
            $startTime = microtime(true);

            // 建立快照批次
            $batches = ceil($snapshotCount / $batchSize);
            for ($batch = 0; $batch < $batches; $batch++) {
                $currentBatchSize = min($batchSize, $snapshotCount - ($batch * $batchSize));

                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $period = new StatisticsPeriod(
                        PeriodType::DAILY,
                        new DateTimeImmutable('2024-01-' . str_pad((string) ($i % 30 + 1), 2, '0', STR_PAD_LEFT) . ' 00:00:00'),
                        new DateTimeImmutable('2024-01-' . str_pad((string) ($i % 30 + 1), 2, '0', STR_PAD_LEFT) . ' 23:59:59'),
                    );

                    $snapshot = StatisticsSnapshot::create(
                        'overview', // 使用支援的類型
                        $period,
                        [
                            'test_data' => [
                                'batch' => $batch,
                                'index' => $i,
                                'timestamp' => time(),
                                'random_data' => str_repeat('x', 100), // 增加資料大小
                            ],
                        ],
                        ['batch_size' => $batchSize],
                        new DateTimeImmutable('+1 day'),
                    );

                    $this->statsRepo->save($snapshot);
                }
            }

            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            $throughput = $snapshotCount / $duration;

            $results[$batchSize] = [
                'duration' => $duration,
                'throughput' => $throughput,
                'avg_per_snapshot' => $duration / $snapshotCount,
            ];

            // 清理測試資料
            $this->pdo->exec("DELETE FROM statistics_snapshots WHERE snapshot_type = 'overview'");
        }

        // 輸出快照效能報告
        echo "\n統計快照效能報告:\n";
        foreach ($results as $batchSize => $stats) {
            echo "- 批次大小 {$batchSize}: " . number_format($stats['throughput'], 2) . ' 快照/秒 '
                 . '(平均 ' . number_format($stats['avg_per_snapshot'] * 1000, 2) . "ms/快照)\n";
        }

        // 驗證至少達到基本效能要求
        $bestThroughput = max(array_column($results, 'throughput'));
        $this->assertGreaterThan(50, $bestThroughput, '快照儲存吞吐量應至少達到 50 快照/秒');
    }

    /**
     * 建立大量測試資料.
     */
    private function seedLargeDataset(int $recordCount): void
    {
        // 建立基本測試資料
        $this->seeder->seedAll();

        $startTime = microtime(true);

        // 建立大量文章資料
        $postsPerBatch = 1000;
        $totalPosts = min($recordCount, 50000); // 最多5萬篇文章
        $batches = ceil($totalPosts / $postsPerBatch);

        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($postsPerBatch, $totalPosts - ($batch * $postsPerBatch));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $postId = ($batch * $postsPerBatch) + $i + 100; // 避免與測試資料衝突
                $userId = ($i % 5) + 1; // 輪流分配給5個使用者
                $status = ['published', 'draft', 'archived'][array_rand(['published', 'draft', 'archived'])];
                $source = ['web', 'mobile', 'api'][array_rand(['web', 'mobile', 'api'])];

                $uuid = $this->generateUuid();
                $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($i * 3600)); // 每小時一篇

                $this->pdo->exec("
                    INSERT INTO posts (
                        id, uuid, seq_number, title, content, user_id, user_ip,
                        status, views, comments_count, likes_count, is_pinned,
                        publish_date, created_at, updated_at, creation_source, creation_source_detail
                    ) VALUES (
                        {$postId}, '{$uuid}', {$postId},
                        'Performance Test Post #{$postId}',
                        '" . str_repeat('This is test content for performance testing. ', 10) . "',
                        {$userId}, '192.168.1." . ($i % 255 + 1) . "',
                        '{$status}', " . rand(0, 1000) . ', ' . rand(0, 50) . ', ' . rand(0, 100) . ", 0,
                        '{$createdAt}', '{$createdAt}', '{$createdAt}',
                        '{$source}', 'performance_test'
                    )
                ");
            }
        }

        // 建立使用者活動記錄
        $activitiesPerBatch = 2000;
        $totalActivities = min($recordCount * 2, 100000); // 最多10萬條活動記錄
        $activityBatches = ceil($totalActivities / $activitiesPerBatch);

        for ($batch = 0; $batch < $activityBatches; $batch++) {
            $currentBatchSize = min($activitiesPerBatch, $totalActivities - ($batch * $activitiesPerBatch));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $activityId = ($batch * $activitiesPerBatch) + $i + 1000;
                $userId = ($i % 5) + 1;
                $actionType = ['login', 'view', 'comment', 'post_create'][array_rand(['login', 'view', 'comment', 'post_create'])];
                $actionCategory = 'performance_test';

                $uuid = $this->generateUuid();
                $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($i * 60)); // 每分鐘一條記錄

                $this->pdo->exec("
                    INSERT INTO user_activity_logs (
                        id, uuid, user_id, action_type, action_category, status, created_at, occurred_at
                    ) VALUES (
                        {$activityId}, '{$uuid}', {$userId},
                        '{$actionType}', '{$actionCategory}', 'success',
                        '{$createdAt}', '{$createdAt}'
                    )
                ");
            }
        }

        $endTime = microtime(true);
        $seedDuration = $endTime - $startTime;

        echo "\n大量資料建立完成:\n";
        echo "- 文章數量: {$totalPosts}\n";
        echo "- 活動記錄數量: {$totalActivities}\n";
        echo '- 建立時間: ' . number_format($seedDuration, 2) . "s\n";
        echo '- 建立速度: ' . number_format(($totalPosts + $totalActivities) / $seedDuration, 0) . " 記錄/秒\n";
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
     * 計算資料點數量.
     */
    private function countDataPoints(mixed $data): int
    {
        if (is_array($data)) {
            $count = 0;
            foreach ($data as $value) {
                $count += is_array($value) ? $this->countDataPoints($value) : 1;
            }

            return $count;
        }

        return 1;
    }

    /**
     * 輸出效能報告.
     */
    private function outputPerformanceReport(string $title, array $results): void
    {
        echo "\n{$title}:\n";
        foreach ($results as $name => $stats) {
            if (!is_array($stats)) {
                continue;
            }

            $duration = $stats['duration'] ?? 0;
            $durationFloat = is_numeric($duration) ? (float) $duration : 0.0;
            echo "- {$name}: " . number_format($durationFloat, 3) . 's';

            if (isset($stats['memory_used'])) {
                $memoryUsed = is_numeric($stats['memory_used']) ? (int) $stats['memory_used'] : 0;
                $memoryMB = $memoryUsed / 1024 / 1024;
                echo ' (記憶體: ' . number_format($memoryMB, 2) . 'MB)';
            }

            if (isset($stats['result_size'])) {
                $resultSize = is_numeric($stats['result_size']) ? (int) $stats['result_size'] : 0;
                echo ' (結果: ' . $resultSize . ' 項)';
            }

            if (isset($stats['data_points'])) {
                $dataPoints = is_numeric($stats['data_points']) ? (int) $stats['data_points'] : 0;
                echo ' (資料點: ' . $dataPoints . ' 個)';
            }

            echo "\n";
        }
    }
}
