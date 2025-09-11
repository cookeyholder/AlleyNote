<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Repositories\Statistics\PostStatisticsRepository;
use App\Infrastructure\Repositories\Statistics\UserStatisticsRepository;
use DateTimeImmutable;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 統計 Repository 單元測試.
 *
 * 測試統計資料存取層的核心功能，包含：
 * - 文章統計查詢
 * - 使用者統計查詢
 * - 資料庫互動
 * - 錯誤處理
 */
#[CoversClass(PostStatisticsRepository => class)]
#[CoversClass(UserStatisticsRepository => class)]
final class StatisticsRepositoryTest extends TestCase
{
    private PostStatisticsRepository $postRepository;

    private UserStatisticsRepository $userRepository;

    private PDO&MockObject $mockPdo;

    private PDOStatement&MockObject $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);

        $this->postRepository = new PostStatisticsRepository($this->mockPdo);
        $this->userRepository = new UserStatisticsRepository($this->mockPdo);
    }

    /**
     * 測試計算週期內文章總數.
     */
    #[Test]
    public function should_count_posts_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $expectedCount = 150;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedCount);

        // Act
        $result = $this->postRepository->countPostsByPeriod($period);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * 測試計算週期內總觀看次數.
     */
    #[Test]
    public function should_count_views_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $expectedViews = 3750;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedViews);

        // Act
        $result = $this->postRepository->countViewsByPeriod($period);

        // Assert
        $this->assertEquals($expectedViews, $result);
    }

    /**
     * 測試計算週期內不重複觀看者數量.
     */
    #[Test]
    public function should_count_unique_viewers_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $expectedViewers = 285;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedViewers);

        // Act
        $result = $this->postRepository->countUniqueViewersByPeriod($period);

        // Assert
        $this->assertEquals($expectedViewers, $result);
    }

    /**
     * 測試取得週期內熱門文章.
     */
    #[Test]
    public function should_get_popular_posts_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $limit = 5;
        $expectedPosts = [
            ['id' => 1, 'title' => '熱門文章1', 'views' => 500, 'created_at' => '2024-01-01 10 => 00 => 00'],
            ['id' => 2, 'title' => '熱門文章2', 'views' => 450, 'created_at' => '2024-01-01 11 => 00 => 00'],
            ['id' => 3, 'title' => '熱門文章3', 'views' => 400, 'created_at' => '2024-01-01 12 => 00 => 00'],
            ['id' => 4, 'title' => '熱門文章4', 'views' => 350, 'created_at' => '2024-01-01 13 => 00 => 00'],
            ['id' => 5, 'title' => '熱門文章5', 'views' => 300, 'created_at' => '2024-01-01 14 => 00 => 00'],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedPosts);

        // Act
        $result = $this->postRepository->getPopularPostsByPeriod($period, $limit);

        // Assert
        $this->assertCount(5, $result);
        $this->assertEquals($expectedPosts, $result);

        // 驗證資料結構
        foreach ($result as $post) {
            $this->assertArrayHasKey('id', $post);
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayHasKey('views', $post);
            $this->assertArrayHasKey('created_at', $post);
        }
    }

    /**
     * 測試週期內活躍使用者數量.
     */
    #[Test]
    public function should_count_active_users_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $expectedUsers = 185;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedUsers);

        // Act
        $result = $this->userRepository->countActiveUsersByPeriod($period);

        // Assert
        $this->assertEquals($expectedUsers, $result);
    }

    /**
     * 測試週期內新註冊使用者數量.
     */
    #[Test]
    public function should_count_new_users_by_period_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();
        $expectedNewUsers = 25;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedNewUsers);

        // Act
        $result = $this->userRepository->countNewUsersByPeriod($period);

        // Assert
        $this->assertEquals($expectedNewUsers, $result);
    }

    /**
     * 測試資料庫查詢失敗處理.
     */
    #[Test]
    public function should_handle_database_query_failure(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('資料庫連線失敗'));

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('計算週期內文章總數失敗');

        $this->postRepository->countPostsByPeriod($period);
    }

    /**
     * 測試查詢參數綁定.
     */
    #[Test]
    public function should_bind_query_parameters_correctly(): void
    {
        // Arrange
        $period = $this->createWeeklyPeriod();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE created_at >= :start_date'))
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) use ($period) {
                return count($params) === 2
                       && $params['start_date'] === $period->startDate->format('Y-m-d H:i:s')
                       && $params['end_date'] === $period->endDate->format('Y-m-d H:i:s');
            }))
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(500);

        // Act
        $result = $this->postRepository->countPostsByPeriod($period);

        // Assert
        $this->assertEquals(500, $result);
    }

    /**
     * 測試不同週期類型的查詢.
     */
    #[Test]
    public function should_handle_different_period_types(): void
    {
        // Arrange
        $dailyPeriod = $this->createDailyPeriod();
        $weeklyPeriod = $this->createWeeklyPeriod();
        $monthlyPeriod = $this->createMonthlyPeriod();

        // 設定連續的mock調用
        $this->mockPdo
            ->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->exactly(3))
            ->method('fetchColumn')
            ->willReturn(100);

        // Act & Assert - 每日週期
        $result1 = $this->postRepository->countPostsByPeriod($dailyPeriod);
        $this->assertEquals(100, $result1);

        // Act & Assert - 每週週期
        $result2 = $this->postRepository->countPostsByPeriod($weeklyPeriod);
        $this->assertEquals(100, $result2);

        // Act & Assert - 每月週期
        $result3 = $this->postRepository->countPostsByPeriod($monthlyPeriod);
        $this->assertEquals(100, $result3);
    }

    /**
     * 測試空結果處理.
     */
    #[Test]
    public function should_handle_empty_results_gracefully(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false); // 模擬無結果

        // Act
        $result = $this->postRepository->countPostsByPeriod($period);

        // Assert
        $this->assertEquals(0, $result);
    }

    /**
     * 測試大數據量查詢效能.
     */
    #[Test]
    public function should_handle_large_dataset_queries(): void
    {
        // Arrange
        $period = $this->createYearlyPeriod();
        $expectedCount = 50000; // 大量資料

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedCount);

        // Act
        $result = $this->postRepository->countPostsByPeriod($period);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * 建立每日週期測試資料.
     */
    private function createDailyPeriod(): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-01 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::DAILY);
    }

    /**
     * 建立每週週期測試資料.
     */
    private function createWeeklyPeriod(): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-07 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::WEEKLY);
    }

    /**
     * 建立每月週期測試資料.
     */
    private function createMonthlyPeriod(): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-31 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::MONTHLY);
    }

    /**
     * 建立每年週期測試資料.
     */
    private function createYearlyPeriod(): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-12-31 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::YEARLY);
    }
}
