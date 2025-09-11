<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * 統計計算服務單元測試.
 *
 * 測試統計計算服務的核心業務邏輯，包含：
 * - 平均值計算
 * - 成長率計算
 * - 趨勢分析
 * - 波動性計算
 * - 效能評分
 */
#[CoversClass(StatisticsCalculationService => class)]
final class StatisticsCalculatorServiceTest extends TestCase
{
    private StatisticsCalculationService $service;

    private UserStatisticsRepositoryInterface&MockObject $mockUserRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUserRepository = $this->createMock(UserStatisticsRepositoryInterface::class);
        $this->service = new StatisticsCalculationService(
            $this->mockUserRepository);
    }

    /**
     * 測試平均每篇文章觀看次數計算.
     */
    #[Test]
    public function should_calculate_average_views_per_post_correctly(): void
    {
        // Arrange
        $snapshot = $this->createStatisticsSnapshot(
            totalPosts: 100,
            totalViews: 2500,
        );

        // Act
        $result = $this->service->calculateAverageViewsPerPost($snapshot);

        // Assert
        $this->assertEquals(25.0, $result);
    }

    /**
     * 測試零文章數的平均值計算.
     */
    #[Test]
    public function should_return_zero_when_no_posts_exist(): void
    {
        // Arrange
        $snapshot = $this->createStatisticsSnapshot(
            totalPosts: 0,
            totalViews: 0,
        );

        // Act
        $result = $this->service->calculateAverageViewsPerPost($snapshot);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    /**
     * 測試成長率計算（正成長）.
     */
    #[Test]
    public function should_calculate_positive_growth_rate(): void
    {
        // Arrange
        $currentSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 120,
            totalViews: 3000,
        );

        $previousSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 100,
            totalViews: 2500,
        );

        // Act
        $growthRates = $this->service->calculateGrowthRate($previousSnapshot, $currentSnapshot);

        // Assert
        $this->assertArrayHasKey('posts', $growthRates);
        $this->assertArrayHasKey('views', $growthRates);
        $this->assertArrayHasKey('users', $growthRates);
        $this->assertEquals(20.0, $growthRates['posts']); // (120-100)/100 * 100 = 20%
        $this->assertEquals(20.0, $growthRates['views']); // (3000-2500)/2500 * 100 = 20%
    }

    /**
     * 測試成長率計算（負成長）.
     */
    #[Test]
    public function should_calculate_negative_growth_rate(): void
    {
        // Arrange
        $currentSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 80,
            totalViews: 2000,
        );

        $previousSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 100,
            totalViews: 2500,
        );

        // Act
        $growthRates = $this->service->calculateGrowthRate($previousSnapshot, $currentSnapshot);

        // Assert

        $this->assertEquals(-20.0, $growthRates['posts']); // (80-100)/100 * 100 = -20%
        $this->assertEquals(-20.0, $growthRates['views']); // (2000-2500)/2500 * 100 = -20%
    }

    /**
     * 測試零基準值的成長率計算.
     */
    #[Test]
    public function should_handle_zero_baseline_in_growth_rate(): void
    {
        // Arrange
        $currentSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 100,
            totalViews: 2500,
        );

        $previousSnapshot = $this->createStatisticsSnapshot(
            totalPosts: 0,
            totalViews: 0,
        );

        // Act
        $growthRates = $this->service->calculateGrowthRate($previousSnapshot, $currentSnapshot);

        // Assert
        $this->assertEquals(100.0, $growthRates['posts']); // 從0增長到100應該是100%
        $this->assertEquals(100.0, $growthRates['views']); // 從0增長到2500應該是100%
    }

    /**
     * 測試取得前一週期
     */
    #[Test]
    public function should_get_previous_period_correctly(): void
    {
        // Arrange
        $startDate = new DateTimeImmutable('2024-01-15 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-15 23:59:59');
        $currentPeriod = StatisticsPeriod::create($startDate, $endDate, PeriodType::DAILY);

        // Act
        $previousPeriod = $this->service->getPreviousPeriod($currentPeriod);

        // Assert
        $this->assertEquals('2024-01-14 00:00:00', $previousPeriod->startDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-14 23:59:59', $previousPeriod->endDate->format('Y-m-d H:i:s'));
        $this->assertEquals(PeriodType::DAILY, $previousPeriod->type);
    }

    /**
     * 測試趨勢方向計算（上升趨勢）.
     */
    #[Test]
    public function should_calculate_upward_trend_direction(): void
    {
        // Arrange
        $previousSnapshot = $this->createStatisticsSnapshot(100, 2000);
        $currentSnapshot = $this->createStatisticsSnapshot(130, 2600);

        // Act
        $trend = $this->service->calculateTrendDirection($previousSnapshot, $currentSnapshot);

        // Assert
        $this->assertEquals('up', $trend);
    }

    /**
     * 測試趨勢方向計算（下降趨勢）.
     */
    #[Test]
    public function should_calculate_downward_trend_direction(): void
    {
        // Arrange
        $previousSnapshot = $this->createStatisticsSnapshot(130, 2600);
        $currentSnapshot = $this->createStatisticsSnapshot(100, 2000);

        // Act
        $trend = $this->service->calculateTrendDirection($previousSnapshot, $currentSnapshot);

        // Assert
        $this->assertEquals('down', $trend);
    }

    /**
     * 測試趨勢方向計算（穩定趨勢）.
     */
    #[Test]
    public function should_calculate_stable_trend_direction(): void
    {
        // Arrange
        $previousSnapshot = $this->createStatisticsSnapshot(100, 2000);
        $currentSnapshot = $this->createStatisticsSnapshot(102, 2040); // 輕微增長，不足5%

        // Act
        $trend = $this->service->calculateTrendDirection($previousSnapshot, $currentSnapshot);

        // Assert
        $this->assertEquals('stable', $trend);
    }

    /**
     * 測試波動性計算（低波動）.
     */
    #[Test]
    public function should_calculate_low_volatility(): void
    {
        // Arrange
        $snapshots = [
            $this->createStatisticsSnapshot(100, 2000),
            $this->createStatisticsSnapshot(101, 2020),
            $this->createStatisticsSnapshot(99, 1980),
            $this->createStatisticsSnapshot(100, 2000),
        ];

        // Act
        $volatility = $this->service->calculateVolatility($snapshots);

        // Assert
        $this->assertLessThan(5.0, $volatility); // 低波動應該小於5%
    }

    /**
     * 測試波動性計算（高波動）.
     */
    #[Test]
    public function should_calculate_high_volatility(): void
    {
        // Arrange
        $snapshots = [
            $this->createStatisticsSnapshot(100, 2000),
            $this->createStatisticsSnapshot(150, 3000),
            $this->createStatisticsSnapshot(80, 1600),
            $this->createStatisticsSnapshot(120, 2400),
        ];

        // Act
        $volatility = $this->service->calculateVolatility($snapshots);

        // Assert
        $this->assertGreaterThan(0.1, $volatility); // 高波動應該大於0.1
    }

    /**
     * 測試效能評分計算（高分）.
     */
    #[Test]
    public function should_calculate_high_performance_score(): void
    {
        // Arrange
        $snapshot = $this->createStatisticsSnapshot(
            totalPosts: 200,
            totalViews: 10000, // 高觀看率 50 views per post
        );

        // Act
        $score = $this->service->calculatePerformanceScore($snapshot);

        // Assert
        $this->assertGreaterThan(80.0, $score); // 高效能應該大於80分
        $this->assertLessThanOrEqual(100.0, $score); // 不應超過100分
    }

    /**
     * 測試效能評分計算（低分）.
     */
    #[Test]
    public function should_calculate_low_performance_score(): void
    {
        // Arrange
        $snapshot = $this->createStatisticsSnapshot(
            totalPosts: 100,
            totalViews: 500, // 低觀看率 5 views per post
        );

        // Act
        $score = $this->service->calculatePerformanceScore($snapshot);

        // Assert
        $this->assertLessThan(50.0, $score); // 低效能應該小於50分
        $this->assertGreaterThanOrEqual(0.0, $score); // 不應小於0分
    }

    /**
     * 測試預測計算.
     */
    #[Test]
    public function should_calculate_forecast(): void
    {
        // Arrange
        $historicalSnapshots = [
            $this->createStatisticsSnapshot(100, 2000),
            $this->createStatisticsSnapshot(110, 2200),
            $this->createStatisticsSnapshot(120, 2400),
            $this->createStatisticsSnapshot(130, 2600),
        ];

        // Act
        $forecast = $this->service->calculateForecast($historicalSnapshots, 3);

        // Assert

        $this->assertArrayHasKey('posts', $forecast);
        $this->assertArrayHasKey('views', $forecast);
        $this->assertArrayHasKey('confidence', $forecast);

        $this->assertGreaterThan(130, $forecast['posts']); // 應該預測繼續增長
        $this->assertGreaterThan(2600, $forecast['views']);
        $this->assertGreaterThanOrEqual(0.0, $forecast['confidence']);
        $this->assertLessThanOrEqual(1.0, $forecast['confidence']);
    }

    /**
     * 測試相關性計算（強正相關）.
     */
    #[Test]
    public function should_calculate_strong_positive_correlation(): void
    {
        // Arrange
        $x = [100, 110, 120, 130, 140];
        $y = [2000, 2200, 2400, 2600, 2800];

        // Act
        $correlation = $this->service->calculateCorrelation($x, $y);

        // Assert
        $this->assertGreaterThan(0.9, $correlation); // 強正相關應該大於0.9
        $this->assertLessThanOrEqual(1.0, $correlation);
    }

    /**
     * 測試相關性計算（無相關）.
     */
    #[Test]
    public function should_calculate_no_correlation(): void
    {
        // Arrange
        $x = [100, 110, 120, 130, 140];
        $y = [2500, 2200, 2800, 2100, 2400]; // 隨機數值

        // Act
        $correlation = $this->service->calculateCorrelation($x, $y);

        // Assert
        $this->assertLessThan(0.5, abs($correlation)); // 無相關應該接近0
    }

    /**
     * 測試季節性指數計算.
     */
    #[Test]
    public function should_calculate_seasonality_index(): void
    {
        // Arrange
        $snapshots = [];

        // 模擬一年的資料，週末較高
        for ($day = 1; $day <= 28; $day++) { // 4週
            $isWeekend = ($day % 7 === 0 || $day % 7 === 6);
            $posts = $isWeekend ? 150 : 100; // 週末較多文章
            $views = $isWeekend ? 3000 : 2000; // 週末較多觀看

            $snapshots[] = $this->createStatisticsSnapshot($posts, $views);
        }

        // Act
        $seasonalityIndex = $this->service->calculateSeasonalityIndex($snapshots);

        // Assert

        $this->assertNotEmpty($seasonalityIndex);
        // 檢查是否包含月份資料
        foreach ($seasonalityIndex as $month => $index) {
            $this->assertIsString($month);
            $this->assertIsFloat($index);
        }
    }

    /**
     * 建立測試用的統計快照.
     */
    private function createStatisticsSnapshot(int $totalPosts, int $totalViews): StatisticsSnapshot
    {
        $id = Uuid::generate();
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-01 23:59:59');

        // 建立來源統計
        $webSource = SourceStatistics::create(
            SourceType::WEB,
            $totalPosts,      // count: int
            80.0,             // percentage: float
            [],                // additionalMetrics: array
        );

        return StatisticsSnapshot::create(
            id: $id,
            period: StatisticsPeriod::create($startDate, $endDate, PeriodType::DAILY),
            totalPosts: $totalPosts,
            totalViews: $totalViews,
            sourceStats: [$webSource],
            additionalMetrics: [],
        );
    }
}
