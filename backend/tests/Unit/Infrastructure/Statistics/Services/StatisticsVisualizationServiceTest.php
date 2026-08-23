<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Infrastructure\Statistics\Adapters\StatisticsQueryAdapter;
use App\Infrastructure\Statistics\Processors\CategoryProcessor;
use App\Infrastructure\Statistics\Processors\TimeSeriesProcessor;
use App\Infrastructure\Statistics\Services\StatisticsVisualizationService;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\UnitTestCase;

/**
 * StatisticsVisualizationService 簡化單元測試.
 *
 * 專注於測試服務的核心邏輯和方法調用，而不深入快取機制的複雜性。
 */
final class StatisticsVisualizationServiceTest extends UnitTestCase
{
    private StatisticsVisualizationService $service;

    /** @var MockObject&StatisticsQueryAdapter */
    private MockObject $mockQueryAdapter;

    /** @var MockObject&CategoryProcessor */
    private MockObject $mockCategoryProcessor;

    /** @var MockObject&TimeSeriesProcessor */
    private MockObject $mockTimeSeriesProcessor;

    /** @var MockObject&StatisticsCacheServiceInterface */
    private MockObject $mockCacheService;

    /** @var MockObject&PostStatisticsRepositoryInterface */
    private MockObject $mockPostRepository;

    private DateTimeImmutable $startDate;

    private DateTimeImmutable $endDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockQueryAdapter = $this->createMock(StatisticsQueryAdapter::class);
        $this->mockCategoryProcessor = $this->createMock(CategoryProcessor::class);
        $this->mockTimeSeriesProcessor = $this->createMock(TimeSeriesProcessor::class);
        $this->mockCacheService = $this->createMock(StatisticsCacheServiceInterface::class);
        $this->mockPostRepository = $this->createMock(PostStatisticsRepositoryInterface::class);

        $this->service = new StatisticsVisualizationService(
            $this->mockQueryAdapter,
            $this->mockCategoryProcessor,
            $this->mockTimeSeriesProcessor,
            $this->mockCacheService,
            $this->mockPostRepository,
        );

        $this->startDate = new DateTimeImmutable('2023-01-01');
        $this->endDate = new DateTimeImmutable('2023-01-31');
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(StatisticsVisualizationService::class, $this->service);
    }

    public function testGetPostsTimeSeriesDataCallsCorrectMethods(): void
    {
        // Arrange
        $granularity = 'day';
        $expectedChartData = new ChartData(['2023-01-01'], []);

        // Mock cache remember to call callback directly
        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getPostsTimeSeriesData($this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetUserActivityTimeSeriesDataCallsCorrectMethods(): void
    {
        // Arrange
        $granularity = 'week';
        $expectedChartData = new ChartData(['2023-W1'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getUserActivityTimeSeriesData($this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetPostSourceDistributionDataCallsCorrectMethods(): void
    {
        // Arrange
        $limit = 5;
        $expectedChartData = new ChartData(['Web', 'Mobile'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getPostSourceDistributionData($this->startDate, $this->endDate, $limit);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetPopularTagsDistributionDataCallsCorrectMethods(): void
    {
        // Arrange
        $limit = 10;
        $expectedChartData = new ChartData(['PHP', 'JavaScript'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getPopularTagsDistributionData($this->startDate, $this->endDate, $limit);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetUserRegistrationTrendDataCallsCorrectMethods(): void
    {
        // Arrange
        $granularity = 'month';
        $expectedChartData = new ChartData(['2023-01'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getUserRegistrationTrendData($this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetContentGrowthTrendDataCallsCorrectMethods(): void
    {
        // Arrange
        $granularity = 'day';
        $expectedChartData = new ChartData(['2023-01-01'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getContentGrowthTrendData($this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetPopularContentRankingDataWithDefaultDates(): void
    {
        // Arrange
        $sortBy = 'views';
        $limit = 10;
        $expectedChartData = new ChartData(['Post 1', 'Post 2'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getPopularContentRankingData(null, null, $sortBy, $limit);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetUserEngagementDistributionDataCallsCorrectMethods(): void
    {
        // Arrange
        $expectedChartData = new ChartData(['High', 'Medium', 'Low'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getUserEngagementDistributionData($this->startDate, $this->endDate);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetCustomChartDataCallsCorrectMethods(): void
    {
        // Arrange
        $metricName = 'custom_metric';
        $parameters = [
            'start_date'  => $this->startDate,
            'end_date'    => $this->endDate,
            'granularity' => 'day',
        ];
        $chartOptions = ['filter' => 'active'];
        $expectedChartData = new ChartData(['Custom 1'], []);

        // Act
        $result = $this->service->getCustomChartData($metricName, $parameters, $chartOptions);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetMultiMetricChartDataReturnsExplicitEmptyState(): void
    {
        // Arrange
        $metrics = ['views', 'likes', 'comments'];
        $granularity = 'day';

        // Act
        $result = $this->service->getMultiMetricChartData($metrics, $this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
        $this->assertTrue($result->isEmpty());
        /** @phpstan-ignore-next-line */
        $this->assertSame($metrics, $result->options['requested_metrics']);
        /** @phpstan-ignore-next-line */
        $this->assertFalse($result->options['empty_state']['implemented']);
    }

    public function testGetPerformanceMetricsDataCallsCorrectMethods(): void
    {
        // Arrange
        $metrics = ['response_time', 'throughput'];
        $granularity = 'hour';

        // Act
        $result = $this->service->getPerformanceMetricsData($this->startDate, $this->endDate, $metrics, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetViewsTimeSeriesDataCallsRepository(): void
    {
        // Arrange
        $granularity = 'day';
        $expectedData = [['date' => '2023-01-01', 'views' => 10, 'visitors' => 5]];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getViewTimeSeriesData')
            ->with($this->startDate, $this->endDate, $granularity)
            ->willReturn($expectedData);

        // Act
        $result = $this->service->getViewsTimeSeriesData($this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    /**
     * 讓 remember 直接執行回呼，驗證各 getter 的資料取得與處理流程.
     */
    private function executeRememberCallbacks(): void
    {
        $chart = new ChartData(['label'], []);
        $this->mockCacheService
            ->method('remember')
            ->willReturnCallback(static fn(string $key, callable $callback): mixed => $callback());
    }

    public function testTimeSeriesGettersFetchAndProcessData(): void
    {
        $this->executeRememberCallbacks();

        $rawRows = [['date' => '2023-01-01', 'value' => 10]];
        $processed = new ChartData(['2023-01-01'], []);

        $matcher = $this->mockQueryAdapter->expects($this->exactly(6))->method('getTimeSeriesData');
        $matcher->willReturn($rawRows);

        $processorMatcher = $this->mockTimeSeriesProcessor->expects($this->exactly(4))->method('processTimeSeriesData');
        $processorMatcher->willReturn($processed);

        $this->assertSame($processed, $this->service->getPostsTimeSeriesData($this->startDate, $this->endDate));
        $this->assertSame($processed, $this->service->getUserActivityTimeSeriesData($this->startDate, $this->endDate));
        $this->assertSame($processed, $this->service->getUserRegistrationTrendData($this->startDate, $this->endDate));

        // 內容成長趨勢會查詢 posts、comments、attachments 三個指標
        $growth = $this->service->getContentGrowthTrendData($this->startDate, $this->endDate);
        $this->assertInstanceOf(ChartData::class, $growth);
    }

    public function testCategoryDistributionGettersFetchAndProcessData(): void
    {
        $this->executeRememberCallbacks();

        $processed = new ChartData(['Web'], []);
        $categoryMatcher = $this->mockQueryAdapter->expects($this->exactly(3))->method('getCategoryDistributionData');
        $categoryMatcher->willReturn([['category' => 'Web', 'value' => 1.0]]);
        $this->mockCategoryProcessor
            ->method('processCategoryData')
            ->willReturn($processed);

        $this->assertSame($processed, $this->service->getPostSourceDistributionData($this->startDate, $this->endDate, 3));
        $this->assertSame($processed, $this->service->getPopularTagsDistributionData(null, null, 7));
        $this->assertSame($processed, $this->service->getUserEngagementDistributionData());

        // 排行榜走 getTopContentData + processRankingData
        $this->mockQueryAdapter
            ->expects($this->once())
            ->method('getTopContentData')
            ->with(5, ['start' => null, 'end' => null], 'views')
            ->willReturn([['title' => 'A', 'views' => 9]]);
        $this->mockCategoryProcessor
            ->expects($this->once())
            ->method('processRankingData')
            ->willReturn($processed);
        $this->assertSame($processed, $this->service->getPopularContentRankingData(null, null, 'views', 5));
    }
}
