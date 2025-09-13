<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\Services\PostStatisticsService;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * 文章統計服務單元測試.
 *
 * 測試文章統計服務的核心業務邏輯，包含：
 * - 熱門文章分析
 * - 來源分析
 * - 文章品質評分
 * - 投資報酬率計算
 * - 最佳發布時間建議
 */
#[CoversClass(PostStatisticsService::class)]
final class PostStatisticsServiceTest extends TestCase
{
    private PostStatisticsService $service;

    private PostStatisticsRepositoryInterface&MockObject $mockPostRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPostRepository = $this->createMock(PostStatisticsRepositoryInterface::class);

        $this->service = new PostStatisticsService(
            $this->mockPostRepository);
    }

    /**
     * 測試分析熱門文章.
     */
    #[Test]
    public function shouldAnalyzePopularPosts(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $mockData = [
            ['id' => 1, 'title' => 'Test Post 1', 'views' => 1000, 'created_at' => '2024-01-15'],
            ['id' => 2, 'title' => 'Test Post 2', 'views' => 800, 'created_at' => '2024-01-10'],
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPopularPostsByPeriod')
            ->with($period, 10)
            ->willReturn($mockData);

        // Act
        $result = $this->service->analyzePopularPosts($period);

        // Assert
        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertCount(2, $result['posts']);
    }

    /**
     * 測試分析來源分佈.
     */
    #[Test]
    public function shouldAnalyzeSourceDistribution(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $mockData = [
            ['source_type' => 'web', 'post_count' => 50, 'view_count' => 5000, 'percentage' => 60.0],
            ['source_type' => 'mobile', 'post_count' => 30, 'view_count' => 3000, 'percentage' => 40.0],
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getSourceDistributionByPeriod')
            ->with($period)
            ->willReturn($mockData);

        // Act
        $result = $this->service->analyzeSourceDistribution($period);

        // Assert

        $this->assertArrayHasKey('distribution', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertEquals('web', $result['insights']['dominant_source']);
    }

    /**
     * 測試計算文章品質評分.
     */
    #[Test]
    public function shouldCalculatePostQualityScore(): void
    {
        // Arrange
        $postId = 1;
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $mockStats = [
            'views' => 1000,
            'comments' => 50,
            'likes' => 100,
            'shares' => 25,
            'source' => 'web',
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPostStatsByPeriod')
            ->with($postId, $period)
            ->willReturn($mockStats);

        // Act
        $result = $this->service->calculatePostQualityScore($postId, $period);

        // Assert

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertIsFloat($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    /**
     * 測試分析文章趨勢.
     */
    #[Test]
    public function shouldAnalyzeTrends(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $mockTrends = [
            ['date' => '2024-01-15', 'post_count' => 10, 'view_count' => 1000, 'growth_rate' => 15.0],
            ['date' => '2024-01-16', 'post_count' => 12, 'view_count' => 1200, 'growth_rate' => -5.0],
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPostTrendsByPeriod')
            ->with($period)
            ->willReturn($mockTrends);

        // Act
        $result = $this->service->analyzeTrends($period);

        // Assert

        $this->assertArrayHasKey('trending_up', $result);
        $this->assertArrayHasKey('trending_down', $result);
        $this->assertArrayHasKey('stable', $result);
    }

    /**
     * 測試計算文章投資報酬率 (ROI).
     */
    #[Test]
    public function shouldCalculatePostROI(): void
    {
        // Arrange
        $postId = 1;
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );
        $contentCost = 100.0;

        $mockPerformance = [
            'views' => 1000,
            'comments' => 50,
            'likes' => 100,
            'shares' => 25,
            'source' => 'web',
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPostStatsByPeriod')
            ->with($postId, $period)
            ->willReturn($mockPerformance);

        // Act
        $result = $this->service->calculatePostROI($postId, $period, $contentCost);

        // Assert

        $this->assertArrayHasKey('roi', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('cost', $result);
        $this->assertArrayHasKey('profit', $result);
        $this->assertIsFloat($result['roi']);
    }

    /**
     * 測試取得最佳發布時間建議.
     */
    #[Test]
    public function shouldGetBestPublishingTimes(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $mockTimeData = [
            ['publish_hour' => '09', 'publish_day' => 'Monday', 'avg_views' => 150.5],
            ['publish_hour' => '14', 'publish_day' => 'Tuesday', 'avg_views' => 120.3],
        ];

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPostsByPublishTime')
            ->with($period)
            ->willReturn($mockTimeData);

        // Act
        $result = $this->service->getBestPublishingTimes($period);

        // Assert

        $this->assertArrayHasKey('best_hours', $result);
        $this->assertArrayHasKey('best_days', $result);
        $this->assertArrayHasKey('insights', $result);
    }

    /**
     * 測試處理例外狀況
     */
    #[Test]
    public function shouldHandleRepositoryException(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPopularPostsByPeriod')
            ->willThrowException(new Exception('Database error'));

        // Act & Assert
        $this->expectException(StatisticsCalculationException::class);
        $this->service->analyzePopularPosts($period);
    }

    /**
     * 測試空資料處理.
     */
    #[Test]
    public function shouldHandleEmptyData(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            PeriodType::MONTHLY,
        );

        $this->mockPostRepository
            ->expects($this->once())
            ->method('getPopularPostsByPeriod')
            ->willReturn([]);

        // Act
        $result = $this->service->analyzePopularPosts($period);

        // Assert

        $this->assertArrayHasKey('posts', $result);
        $this->assertEmpty($result['posts']);
    }
}
