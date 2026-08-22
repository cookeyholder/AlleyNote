<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Domains\Statistics\Services\StatisticsQueryService;
use App\Infrastructure\Statistics\Adapters\StatisticsQueryAdapter;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * StatisticsQueryAdapter 單元測試.
 *
 * 驗證查詢轉發與模擬資料生成（時間序列、分類分布）的結構。
 */
final class StatisticsQueryAdapterTest extends UnitTestCase
{
    private MockInterface&StatisticsQueryService $baseService;

    private StatisticsQueryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var MockInterface&StatisticsQueryService $baseService */
        $baseService = Mockery::mock(StatisticsQueryService::class);
        $this->baseService = $baseService;
        $this->adapter = new StatisticsQueryAdapter($this->baseService);
    }

    #[Test]
    public function getPostsTimeSeriesDataQueriesBaseAndReturnsSeries(): void
    {
        $this->baseService->shouldReceive('getPostStatistics')->once()->andReturn(new PaginatedStatisticsDTO([], 0, 1, 20));

        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-03');
        $data = $this->adapter->getPostsTimeSeriesData($start, $end, 'day');

        // 3 天範圍 → 3 個資料點
        $this->assertCount(3, $data);
        foreach ($data as $point) {
            $this->assertIsArray($point);
            $this->assertArrayHasKey('timestamp', $point);
            $this->assertArrayHasKey('value', $point);
            $this->assertIsFloat($point['value']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $point['timestamp']);
        }
        $this->assertSame('2025-01-01 00:00:00', $data[0]['timestamp']);
    }

    #[Test]
    public function getUserActivityTimeSeriesDataDelegatesToUserStatistics(): void
    {
        $this->baseService->shouldReceive('getUserStatistics')->once()->andReturn(new PaginatedStatisticsDTO([], 0, 1, 20));

        $start = new DateTimeImmutable('2025-02-01 00:00:00');
        $end = new DateTimeImmutable('2025-02-01 03:00:00');
        $data = $this->adapter->getUserActivityTimeSeriesData($start, $end, 'hour');

        // 小時粒度：0,1,2,3 共 4 點
        $this->assertCount(4, $data);
    }

    #[Test]
    public function getPostSourceDistributionSupportsOptionalRange(): void
    {
        // 有日期範圍時呼叫基礎服務
        $this->baseService->shouldReceive('getSourceDistribution')->once()->andReturn([]);
        $withRange = $this->adapter->getPostSourceDistributionData(
            new DateTimeImmutable('2025-03-01'),
            new DateTimeImmutable('2025-03-31'),
            3,
        );
        $this->assertCount(3, $withRange);
        $first = $withRange[0];
        $this->assertIsArray($first);
        $this->assertArrayHasKey('category', $first);
        $this->assertArrayHasKey('value', $first);

        // 無日期範圍時使用預設查詢
        $withoutRange = $this->adapter->getPostSourceDistributionData(null, null, 2);
        $this->assertCount(2, $withoutRange);
    }

    #[Test]
    public function getPopularTagsDistributionLimitsCategories(): void
    {
        $data = $this->adapter->getPopularTagsDistributionData(null, null, 100);
        // 標籤類別最多 10 項
        $this->assertCount(10, $data);
    }

    #[Test]
    public function trendAndCommentSeriesReturnRequestedGranularity(): void
    {
        $start = new DateTimeImmutable('2024-01-01');
        $end = new DateTimeImmutable('2026-06-30');

        // 年度粒度
        $registrations = $this->adapter->getUserRegistrationTrendData($start, $end, 'year');
        $this->assertCount(3, $registrations);

        // 月粒度
        $comments = $this->adapter->getCommentsTimeSeriesData(
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-04-30'),
            'month',
        );
        $this->assertCount(4, $comments);
    }

    #[Test]
    public function getPopularContentRankingQueriesBaseWhenRangeGiven(): void
    {
        $this->baseService->shouldReceive('getPopularContent')->once()->andReturn([]);

        $ranking = $this->adapter->getPopularContentRankingData(
            new DateTimeImmutable('2025-05-01'),
            new DateTimeImmutable('2025-05-31'),
            'views',
            4,
        );
        $this->assertCount(4, $ranking);

        // 無範圍時不呼叫基礎服務
        $noRangeRanking = $this->adapter->getPopularContentRankingData(null, null, 'views', 2);
        $this->assertCount(2, $noRangeRanking);
    }

    #[Test]
    public function getUserEngagementDistributionReturnsFixedSegments(): void
    {
        $distribution = $this->adapter->getUserEngagementDistributionData();
        $this->assertCount(3, $distribution);
        $labels = array_column($distribution, 'category');
        $this->assertSame(['高活躍', '中活躍', '低活躍'], $labels);
    }

    #[Test]
    public function getCustomMetricDataSwitchesOnParameterShape(): void
    {
        // 時間序列模式
        $series = $this->adapter->getCustomMetricData('custom.series', [
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-01-05',
            'granularity' => 'day',
        ]);
        $this->assertCount(5, $series);
        $this->assertArrayHasKey('timestamp', $series[0]);

        // 型別不符時退回分類模式（limit 參數生效）
        $categoryFallback = $this->adapter->getCustomMetricData('custom.fallback', [
            'start_date' => ['not-a-string'],
            'end_date'   => 123,
            'limit'      => 3,
        ]);
        $this->assertNotEmpty($categoryFallback);
        $this->assertLessThanOrEqual(3, count($categoryFallback));

        // 分類模式預設 5 項
        $defaultCategories = $this->adapter->getCustomMetricData('custom.default', []);
        $this->assertCount(5, $defaultCategories);
    }

    #[Test]
    public function getMetricTimeSeriesDefaultsToLastThirtyDays(): void
    {
        $data = $this->adapter->getMetricTimeSeriesData('metric.daily');
        // -30 天至今天，約 31 天
        $this->assertGreaterThanOrEqual(30, count($data));
        $this->assertLessThanOrEqual(32, count($data));
    }

    #[Test]
    public function performanceMetricsCoverEveryRequestedMetric(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-10');

        $single = $this->adapter->getPerformanceMetricData('response_time', $start, $end, 'day');
        $this->assertCount(10, $single);

        $multi = $this->adapter->getPerformanceMetricsData(['response_time', 'error_rate'], $start, $end, 'day');
        $this->assertSame(['response_time', 'error_rate'], array_keys($multi));
        foreach ($multi as $metricData) {
            $this->assertCount(10, $metricData);
        }
    }

    #[Test]
    public function genericAccessorsReturnExpectedShapes(): void
    {
        $timeSeries = $this->adapter->getTimeSeriesData('generic', null, null, 'week');
        $this->assertNotEmpty($timeSeries);
        $firstPoint = $timeSeries[0] ?? null;
        $this->assertIsArray($firstPoint);
        $this->assertArrayHasKey('value', $firstPoint);

        $categories = $this->adapter->getCategoryDistributionData('tech', null, null, 3);
        $this->assertCount(3, $categories);
        $firstCategory = $categories[0] ?? null;
        $this->assertIsArray($firstCategory);
        $this->assertArrayHasKey('percentage', $firstCategory);

        $topContent = $this->adapter->getTopContentData(4, [], 'views');
        $this->assertCount(4, $topContent);
        $firstContent = $topContent[0];
        $this->assertIsArray($firstContent);
        $this->assertArrayHasKey('title', $firstContent);
        $this->assertArrayHasKey('views', $firstContent);

        $engagement = $this->adapter->getUserEngagementData(
            new DateTimeImmutable('2025-06-01'),
            new DateTimeImmutable('2025-06-08'),
            'week',
        );
        $this->assertNotEmpty($engagement);

        // content growth 與 time series metric 別名
        $growth = $this->adapter->getContentGrowthTrendData(
            new DateTimeImmutable('2025-07-01'),
            new DateTimeImmutable('2025-07-14'),
            'week',
        );
        $this->assertCount(2, $growth);

        $metricSeries = $this->adapter->getTimeSeriesMetricData(
            'alias.metric',
            new DateTimeImmutable('2025-08-01'),
            new DateTimeImmutable('2025-08-03'),
            'day',
        );
        $this->assertCount(3, $metricSeries);
    }
}
