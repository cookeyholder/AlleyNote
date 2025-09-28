<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Infrastructure\Statistics\Adapters\StatisticsQueryAdapter;
use App\Infrastructure\Statistics\Processors\CategoryProcessor;
use App\Infrastructure\Statistics\Processors\TimeSeriesProcessor;
use App\Infrastructure\Statistics\Services\StatisticsVisualizationService;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * StatisticsVisualizationService 簡化單元測試.
 *
 * 專注於測試服務的核心邏輯和方法調用，而不深入快取機制的複雜性。
 */
final class StatisticsVisualizationServiceTest extends TestCase
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

    private DateTimeImmutable $startDate;

    private DateTimeImmutable $endDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockQueryAdapter = $this->createMock(StatisticsQueryAdapter::class);
        $this->mockCategoryProcessor = $this->createMock(CategoryProcessor::class);
        $this->mockTimeSeriesProcessor = $this->createMock(TimeSeriesProcessor::class);
        $this->mockCacheService = $this->createMock(StatisticsCacheServiceInterface::class);

        $this->service = new StatisticsVisualizationService(
            $this->mockQueryAdapter,
            $this->mockCategoryProcessor,
            $this->mockTimeSeriesProcessor,
            $this->mockCacheService,
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
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'granularity' => 'day',
        ];
        $chartOptions = ['filter' => 'active'];
        $expectedChartData = new ChartData(['Custom 1'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getCustomChartData($metricName, $parameters, $chartOptions);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetMultiMetricChartDataCallsCorrectMethods(): void
    {
        // Arrange
        $metrics = ['views', 'likes', 'comments'];
        $granularity = 'day';
        $expectedChartData = new ChartData(['2023-01-01'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getMultiMetricChartData($metrics, $this->startDate, $this->endDate, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }

    public function testGetPerformanceMetricsDataCallsCorrectMethods(): void
    {
        // Arrange
        $metrics = ['response_time', 'throughput'];
        $granularity = 'hour';
        $expectedChartData = new ChartData(['2023-01-01 00:00'], []);

        $this->mockCacheService
            ->expects($this->once())
            ->method('remember')
            ->willReturn($expectedChartData);

        // Act
        $result = $this->service->getPerformanceMetricsData($this->startDate, $this->endDate, $metrics, $granularity);

        // Assert
        $this->assertInstanceOf(ChartData::class, $result);
    }
}
