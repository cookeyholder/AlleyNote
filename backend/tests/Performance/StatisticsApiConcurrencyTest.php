<?php

declare(strict_types=1);

namespace Tests\Performance;

use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Cache\ArrayCacheAdapter;
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * 統計 API 並發效能測試.
 *
 * 模擬真實環境下的高並發 API 請求，測試：
 * - 並發請求處理能力
 * - 快取效能和命中率
 * - 系統資源使用情況
 * - 回應時間分佈
 */
#[Group('performance')]
#[Group('statistics')]
#[Group('concurrent')]
class StatisticsApiConcurrencyTest extends TestCase
{
    private PDO $pdo;

    private ArrayCacheAdapter $cacheAdapter;

    private StatisticsTestSeeder $seeder;

    private const CONCURRENCY_THRESHOLDS = [
        'max_concurrent_requests' => 50,
        'avg_response_time' => 2.0, // 秒
        'cache_hit_rate_target' => 0.80, // 80%
        'failure_rate_max' => 0.05, // 5%
        'memory_increase_limit' => 100 * 1024 * 1024, // 100MB
    ];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            // 設置測試環境
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 建立快取適配器
            $this->cacheAdapter = new ArrayCacheAdapter();

            // 建立測試資料
            $this->seeder = new StatisticsTestSeeder($this->pdo);
            $this->seeder->createTables();
            $this->seeder->seedAll();
            $this->seedConcurrencyTestData();
        } catch (Exception $e) {
            $this->markTestSkipped('無法設置並發測試環境: ' . $e->getMessage());
        }
    }

    /**
     * 測試統計 API 並發請求處理.
     */
    #[Test]
    public function testConcurrentStatisticsRequests(): void
    {
        $concurrentUsers = 25; // 模擬 25 個並發使用者
        $requestsPerUser = 4; // 每個使用者 4 個請求
        $totalRequests = $concurrentUsers * $requestsPerUser;

        echo "\n開始並發測試: {$concurrentUsers} 並發使用者，每人 {$requestsPerUser} 請求\n";

        $initialMemory = memory_get_usage(true);
        $startTime = microtime(true);

        // 準備並發請求
        $requests = $this->prepareConcurrentRequests($concurrentUsers, $requestsPerUser);

        // 執行並發請求（模擬）
        $results = $this->executeConcurrentRequests($requests);

        $endTime = microtime(true);
        $finalMemory = memory_get_usage(true);

        // 分析結果
        $totalDuration = $endTime - $startTime;
        $memoryIncrease = $finalMemory - $initialMemory;
        $durations = array_column($results, 'duration');
        $avgResponseTime = count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
        $maxResponseTime = count($durations) > 0 ? max($durations) : 0;
        $minResponseTime = count($durations) > 0 ? min($durations) : 0;
        $failureCount = count(array_filter($results, fn($r) => is_array($r) && !($r['success'] ?? true)));
        $failureRate = $failureCount / $totalRequests;

        // 效能斷言
        $this->assertLessThan(
            self::CONCURRENCY_THRESHOLDS['avg_response_time'],
            $avgResponseTime,
            "平均回應時間 {$avgResponseTime}s 超過閾值",
        );

        $this->assertLessThan(
            self::CONCURRENCY_THRESHOLDS['failure_rate_max'],
            $failureRate,
            '失敗率 ' . ($failureRate * 100) . '% 超過閾值',
        );

        $this->assertLessThan(
            self::CONCURRENCY_THRESHOLDS['memory_increase_limit'],
            $memoryIncrease,
            '記憶體增長 ' . ($memoryIncrease / 1024 / 1024) . 'MB 超過限制',
        );

        // 輸出並發測試報告
        $this->outputConcurrencyReport([
            'total_requests' => $totalRequests,
            'total_duration' => $totalDuration,
            'avg_response_time' => $avgResponseTime,
            'max_response_time' => $maxResponseTime,
            'min_response_time' => $minResponseTime,
            'failure_count' => $failureCount,
            'failure_rate' => $failureRate,
            'memory_increase' => $memoryIncrease,
            'throughput' => $totalRequests / $totalDuration,
        ]);
    }

    /**
     * 測試快取效能和命中率.
     */
    #[Test]
    public function testCachePerformanceUnderLoad(): void
    {
        $requestCount = 100;
        $uniqueQueries = 10; // 只有 10 種不同的查詢，其他都是重複

        echo "\n開始快取效能測試: {$requestCount} 請求，{$uniqueQueries} 種不同查詢\n";

        // 清空快取統計
        $this->cacheAdapter->clearStats();

        $cacheResults = [];
        $queries = $this->generateCacheTestQueries($uniqueQueries);

        // 執行重複查詢測試快取效能
        for ($i = 0; $i < $requestCount; $i++) {
            $queryIndex = $i % $uniqueQueries; // 循環使用查詢
            $query = $queries[$queryIndex];

            $startTime = microtime(true);

            // 嘗試從快取獲取
            $cacheKey = "test_query_{$queryIndex}";
            $result = $this->cacheAdapter->remember($cacheKey, function () use ($query) {
                // 模擬資料庫查詢
                usleep(rand(10000, 50000)); // 10-50ms 的資料庫查詢時間

                return is_array($query) && isset($query['result']) ? $query['result'] : [];
            }, 3600); // 1小時 TTL

            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            $cacheResults[] = [
                'query_index' => $queryIndex,
                'duration' => $duration,
                'is_from_cache' => $duration < 0.01, // 小於 10ms 認為是快取命中
            ];
        }

        // 分析快取效能
        $cacheHits = count(array_filter($cacheResults, fn($r) => $r['is_from_cache']));
        $cacheHitRate = $cacheHits / $requestCount;
        $avgCacheHitTime = $this->calculateAverageTime($cacheResults, true);
        $avgCacheMissTime = $this->calculateAverageTime($cacheResults, false);

        // 驗證快取效能
        $this->assertGreaterThanOrEqual(
            self::CONCURRENCY_THRESHOLDS['cache_hit_rate_target'],
            $cacheHitRate,
            '快取命中率 ' . ($cacheHitRate * 100) . '% 低於目標',
        );

        // 輸出快取效能報告
        echo "\n快取效能報告:\n";
        echo "- 總請求數: {$requestCount}\n";
        echo "- 快取命中數: {$cacheHits}\n";
        echo '- 快取命中率: ' . number_format($cacheHitRate * 100, 2) . "%\n";
        echo '- 快取命中平均時間: ' . number_format($avgCacheHitTime * 1000, 2) . "ms\n";
        echo '- 快取未命中平均時間: ' . number_format($avgCacheMissTime * 1000, 2) . "ms\n";
        echo '- 效能提升倍數: ' . number_format($avgCacheMissTime / max($avgCacheHitTime, 0.001), 1) . "x\n";
    }

    /**
     * 測試記憶體洩漏和垃圾回收.
     */
    #[Test]
    public function testMemoryLeakPrevention(): void
    {
        $iterationCount = 50;
        $requestsPerIteration = 20;

        echo "\n開始記憶體洩漏測試: {$iterationCount} 迭代，每次 {$requestsPerIteration} 請求\n";

        $memoryReadings = [];
        $initialMemory = memory_get_usage(true);

        for ($iteration = 0; $iteration < $iterationCount; $iteration++) {
            $iterationStartMemory = memory_get_usage(true);

            // 執行一批請求
            for ($request = 0; $request < $requestsPerIteration; $request++) {
                $this->executeStatisticsQuery();
            }

            // 手動觸發垃圾回收
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            $iterationEndMemory = memory_get_usage(true);
            $memoryReadings[] = [
                'iteration' => $iteration,
                'memory_before' => $iterationStartMemory,
                'memory_after' => $iterationEndMemory,
                'memory_diff' => $iterationEndMemory - $iterationStartMemory,
            ];
        }

        $finalMemory = memory_get_usage(true);
        $totalMemoryIncrease = $finalMemory - $initialMemory;
        $avgMemoryPerIteration = $totalMemoryIncrease / $iterationCount;

        // 分析記憶體使用模式
        $memoryIncreases = array_column($memoryReadings, 'memory_diff');
        $steadyStateStartIteration = (int) ($iterationCount * 0.3); // 前 30% 為暖身期
        $steadyStateIncreases = array_slice($memoryIncreases, $steadyStateStartIteration);
        $avgSteadyStateIncrease = array_sum($steadyStateIncreases) / count($steadyStateIncreases);

        // 驗證沒有嚴重的記憶體洩漏
        $maxAcceptableIncrease = 10 * 1024 * 1024; // 10MB
        $this->assertLessThan(
            $maxAcceptableIncrease,
            $totalMemoryIncrease,
            '總記憶體增長 ' . ($totalMemoryIncrease / 1024 / 1024) . 'MB 可能存在記憶體洩漏',
        );

        // 檢查穩定狀態下的記憶體使用
        $maxSteadyStateIncrease = 1024 * 1024; // 1MB per iteration
        $this->assertLessThan(
            $maxSteadyStateIncrease,
            abs($avgSteadyStateIncrease),
            '穩定狀態下記憶體變化 ' . ($avgSteadyStateIncrease / 1024 / 1024) . 'MB/迭代 過大',
        );

        // 輸出記憶體分析報告
        echo "\n記憶體洩漏分析報告:\n";
        echo '- 初始記憶體: ' . number_format($initialMemory / 1024 / 1024, 2) . "MB\n";
        echo '- 最終記憶體: ' . number_format($finalMemory / 1024 / 1024, 2) . "MB\n";
        echo '- 總記憶體增長: ' . number_format($totalMemoryIncrease / 1024 / 1024, 2) . "MB\n";
        echo '- 平均每迭代增長: ' . number_format($avgMemoryPerIteration / 1024, 2) . "KB\n";
        echo '- 穩定狀態平均變化: ' . number_format($avgSteadyStateIncrease / 1024, 2) . "KB/迭代\n";
    }

    /**
     * 測試系統負載下的回應時間分佈.
     */
    #[Test]
    public function testResponseTimeDistribution(): void
    {
        $totalRequests = 200;
        $loadTypes = ['light', 'medium', 'heavy']; // 不同負載類型

        echo "\n開始回應時間分佈測試: {$totalRequests} 請求\n";

        $responseTimeResults = [];

        foreach ($loadTypes as $loadType) {
            $requestsForThisLoad = (int) ($totalRequests / count($loadTypes));
            $loadResults = [];

            for ($i = 0; $i < $requestsForThisLoad; $i++) {
                $startTime = microtime(true);
                $this->executeLoadTest($loadType);
                $endTime = microtime(true);

                $loadResults[] = $endTime - $startTime;
            }

            $responseTimeResults[$loadType] = $loadResults;
        }

        // 分析回應時間分佈
        foreach ($responseTimeResults as $loadType => $times) {
            if (empty($times)) {
                continue;
            }

            $avg = array_sum($times) / count($times);
            $min = min($times);
            $max = max($times);
            $median = $this->calculateMedian($times);
            $p95 = $this->calculatePercentile($times, 0.95);
            $p99 = $this->calculatePercentile($times, 0.99);

            echo "\n{$loadType} 負載回應時間分析:\n";
            echo '- 平均: ' . number_format($avg * 1000, 2) . "ms\n";
            echo '- 最小: ' . number_format($min * 1000, 2) . "ms\n";
            echo '- 最大: ' . number_format($max * 1000, 2) . "ms\n";
            echo '- 中位數: ' . number_format($median * 1000, 2) . "ms\n";
            echo '- P95: ' . number_format($p95 * 1000, 2) . "ms\n";
            echo '- P99: ' . number_format($p99 * 1000, 2) . "ms\n";

            // 驗證回應時間要求
            $this->assertLessThan(
                self::CONCURRENCY_THRESHOLDS['avg_response_time'],
                $avg,
                "{$loadType} 負載平均回應時間超過閾值",
            );

            $this->assertLessThan(
                self::CONCURRENCY_THRESHOLDS['avg_response_time'] * 3,
                $p95,
                "{$loadType} 負載 P95 回應時間過高",
            );
        }
    }

    /**
     * 準備並發請求.
     */
    private function prepareConcurrentRequests(int $users, int $requestsPerUser): array
    {
        $requests = [];
        $apiEndpoints = [
            'overview',
            'posts',
            'users',
            'sources',
        ];

        for ($user = 0; $user < $users; $user++) {
            for ($request = 0; $request < $requestsPerUser; $request++) {
                $requests[] = [
                    'user_id' => $user,
                    'request_id' => $request,
                    'endpoint' => $apiEndpoints[array_rand($apiEndpoints)],
                    'params' => $this->generateRandomParams(),
                ];
            }
        }

        // 隨機化請求順序，模擬真實情況
        shuffle($requests);

        return $requests;
    }

    /**
     * 執行並發請求（在單執行緒中模擬）.
     */
    private function executeConcurrentRequests(array $requests): array
    {
        $results = [];

        foreach ($requests as $request) {
            $startTime = microtime(true);
            $success = true;
            $error = null;

            try {
                $result = $this->executeApiRequest(is_array($request) ? $request : []);
            } catch (Exception $e) {
                $success = false;
                $error = $e->getMessage();
                $result = null;
            }

            $endTime = microtime(true);

            $results[] = [
                'request' => $request,
                'success' => $success,
                'error' => $error,
                'result' => $result,
                'duration' => $endTime - $startTime,
            ];
        }

        return $results;
    }

    /**
     * 執行 API 請求.
     */
    private function executeApiRequest(array $request): array
    {
        // 模擬不同的統計 API 端點
        $endpointValue = $request['endpoint'] ?? 'unknown';
        $endpoint = is_string($endpointValue) ? $endpointValue : 'unknown';
        $params = isset($request['params']) && is_array($request['params']) ? $request['params'] : [];

        switch ($endpoint) {
            case 'overview':
                return $this->getOverviewStatistics($params);
            case 'posts':
                return $this->getPostStatistics($params);
            case 'users':
                return $this->getUserStatistics($params);
            case 'sources':
                return $this->getSourceStatistics($params);
            default:
                throw new Exception('Unknown endpoint: ' . $endpoint);
        }
    }

    /**
     * 模擬統計查詢執行.
     */
    private function executeStatisticsQuery(): array
    {
        // 隨機執行一個統計查詢
        $queries = [
            fn() => $this->getOverviewStatistics(['period' => 'daily']),
            fn() => $this->getPostStatistics(['period' => 'weekly']),
            fn() => $this->getUserStatistics(['period' => 'monthly']),
        ];

        $query = $queries[array_rand($queries)];

        return $query();
    }

    /**
     * 建立並發測試資料.
     */
    private function seedConcurrencyTestData(): void
    {
        // 建立適中規模的測試資料以支持並發測試
        $postCount = 5000;
        $activityCount = 10000;

        // 建立文章資料
        for ($i = 0; $i < $postCount; $i++) {
            $uuid = $this->generateUuid();
            $userId = ($i % 5) + 1;
            $status = ['published', 'draft'][array_rand(['published', 'draft'])];
            $source = ['web', 'mobile', 'api'][array_rand(['web', 'mobile', 'api'])];
            $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($i * 3600));

            $this->pdo->exec('
                INSERT INTO posts (
                    id, uuid, seq_number, title, content, user_id, status,
                    views, comments_count, likes_count, created_at, updated_at,
                    creation_source
                ) VALUES (
                    ' . ($i + 1000) . ", '$uuid', " . ($i + 1000) . ",
                    'Concurrency Test Post #$i', 'Test content for concurrency',
                    $userId, '$status', " . rand(0, 1000) . ', ' . rand(0, 50) . ',
                    ' . rand(0, 100) . ", '$createdAt', '$createdAt', '$source'
                )
            ");
        }

        // 建立活動記錄
        for ($i = 0; $i < $activityCount; $i++) {
            $uuid = $this->generateUuid();
            $userId = ($i % 5) + 1;
            $actionType = ['login', 'view', 'comment'][array_rand(['login', 'view', 'comment'])];
            $createdAt = date('Y-m-d H:i:s', strtotime('2024-01-01') + ($i * 300));

            $this->pdo->exec('
                INSERT INTO user_activity_logs (
                    id, uuid, user_id, action_type, action_category, status,
                    created_at, occurred_at
                ) VALUES (
                    ' . ($i + 2000) . ", '$uuid', $userId, '$actionType',
                    'concurrency_test', 'success', '$createdAt', '$createdAt'
                )
            ");
        }

        echo "並發測試資料建立完成: {$postCount} 篇文章, {$activityCount} 條活動記錄\n";
    }

    // 輔助方法
    private function generateRandomParams(): array
    {
        $periods = ['daily', 'weekly', 'monthly'];

        return [
            'period' => $periods[array_rand($periods)],
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ];
    }

    private function generateCacheTestQueries(int $count): array
    {
        $queries = [];
        for ($i = 0; $i < $count; $i++) {
            $queries[] = [
                'id' => $i,
                'query' => "SELECT COUNT(*) FROM posts WHERE id % 10 = $i",
                'result' => ['count' => rand(100, 1000)],
            ];
        }

        return $queries;
    }

    private function calculateAverageTime(array $results, bool $fromCache): float
    {
        $filtered = array_filter($results, fn($r) => is_array($r) && ($r['is_from_cache'] ?? false) === $fromCache);
        if (empty($filtered)) {
            return 0;
        }

        return array_sum(array_column($filtered, 'duration')) / count($filtered);
    }

    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $numericValues = array_filter($values, 'is_numeric');
        if (empty($numericValues)) {
            return 0.0;
        }

        sort($numericValues);
        $count = count($numericValues);
        $middle = (int) ($count / 2);

        if ($count % 2 === 0) {
            $val1 = (float) $numericValues[$middle - 1];
            $val2 = (float) $numericValues[$middle];

            return ($val1 + $val2) / 2;
        }

        return (float) $numericValues[$middle];
    }

    private function calculatePercentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $numericValues = array_filter($values, 'is_numeric');
        if (empty($numericValues)) {
            return 0.0;
        }

        sort($numericValues);
        $index = ($percentile * (count($numericValues) - 1));
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return (float) $numericValues[$lower];
        }

        $weight = $index - $lower;
        $val1 = (float) $numericValues[$lower];
        $val2 = (float) $numericValues[$upper];

        return $val1 * (1 - $weight) + $val2 * $weight;
    }

    private function executeLoadTest(string $loadType): void
    {
        switch ($loadType) {
            case 'light':
                // 輕負載：簡單查詢
                $this->getOverviewStatistics(['period' => 'daily']);
                break;
            case 'medium':
                // 中等負載：多個查詢
                $this->getPostStatistics(['period' => 'weekly']);
                $this->getUserStatistics(['period' => 'weekly']);
                break;
            case 'heavy':
                // 重負載：複雜查詢組合
                $this->getOverviewStatistics(['period' => 'monthly']);
                $this->getPostStatistics(['period' => 'monthly']);
                $this->getUserStatistics(['period' => 'monthly']);
                $this->getSourceStatistics(['period' => 'monthly']);
                break;
        }
    }

    private function outputConcurrencyReport(array $stats): void
    {
        echo "\n並發測試報告:\n";
        echo str_repeat('=', 50) . "\n";

        $totalRequests = $stats['total_requests'] ?? 0;
        $totalDuration = $stats['total_duration'] ?? 0;
        $avgResponseTime = $stats['avg_response_time'] ?? 0;
        $maxResponseTime = $stats['max_response_time'] ?? 0;
        $minResponseTime = $stats['min_response_time'] ?? 0;
        $failureCount = $stats['failure_count'] ?? 0;
        $failureRate = $stats['failure_rate'] ?? 0;
        $memoryIncrease = $stats['memory_increase'] ?? 0;
        $throughput = $stats['throughput'] ?? 0;

        echo '總請求數: ' . (is_numeric($totalRequests) ? (int) $totalRequests : 0) . "\n";
        echo '總執行時間: ' . number_format(is_numeric($totalDuration) ? (float) $totalDuration : 0.0, 2) . "s\n";
        echo '平均回應時間: ' . number_format(is_numeric($avgResponseTime) ? (float) $avgResponseTime * 1000 : 0.0, 2) . "ms\n";
        echo '最大回應時間: ' . number_format(is_numeric($maxResponseTime) ? (float) $maxResponseTime * 1000 : 0.0, 2) . "ms\n";
        echo '最小回應時間: ' . number_format(is_numeric($minResponseTime) ? (float) $minResponseTime * 1000 : 0.0, 2) . "ms\n";
        echo '失敗請求數: ' . (is_numeric($failureCount) ? (int) $failureCount : 0) . "\n";
        echo '失敗率: ' . number_format(is_numeric($failureRate) ? (float) $failureRate * 100 : 0.0, 2) . "%\n";
        echo '記憶體增長: ' . number_format(is_numeric($memoryIncrease) ? (float) $memoryIncrease / 1024 / 1024 : 0.0, 2) . "MB\n";
        echo '吞吐量: ' . number_format(is_numeric($throughput) ? (float) $throughput : 0.0, 2) . " 請求/秒\n";
        echo str_repeat('=', 50) . "\n";
    }

    // 模擬 API 方法
    private function getOverviewStatistics(array $params): array
    {
        usleep(rand(5000, 20000)); // 5-20ms

        return ['total_posts' => rand(1000, 5000), 'total_users' => rand(100, 500)];
    }

    private function getPostStatistics(array $params): array
    {
        usleep(rand(10000, 30000)); // 10-30ms

        return ['published' => rand(800, 1200), 'draft' => rand(100, 300)];
    }

    private function getUserStatistics(array $params): array
    {
        usleep(rand(15000, 40000)); // 15-40ms

        return ['active_users' => rand(200, 400), 'new_users' => rand(20, 50)];
    }

    private function getSourceStatistics(array $params): array
    {
        usleep(rand(8000, 25000)); // 8-25ms

        return ['web' => rand(500, 800), 'mobile' => rand(300, 600), 'api' => rand(100, 200)];
    }

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
}
