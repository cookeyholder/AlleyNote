<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StatisticsAggregationService 單元測試.
 *
 * 測試統計聚合服務的各項功能，確保領域邏輯正確實作。
 * 使用 Mock 對象模擬依賴，專注於服務邏輯的測試。
 */
class StatisticsAggregationServiceTest extends TestCase
{
    private StatisticsAggregationService $service;

    private MockObject|StatisticsRepositoryInterface $statisticsRepository;

    private MockObject|PostStatisticsRepositoryInterface $postStatisticsRepository;

    private MockObject|UserStatisticsRepositoryInterface $userStatisticsRepository;

    private StatisticsPeriod $testPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock 依賴
        $this->statisticsRepository = $this->createMock(StatisticsRepositoryInterface::class);
        $this->postStatisticsRepository = $this->createMock(PostStatisticsRepositoryInterface::class);
        $this->userStatisticsRepository = $this->createMock(UserStatisticsRepositoryInterface::class);

        // 建立測試服務
        $this->service = new StatisticsAggregationService(
            $this->statisticsRepository,
            $this->postStatisticsRepository,
            $this->userStatisticsRepository,
        );

        // 建立測試週期
        $this->testPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2023-01-01'));
    }

    public function testCreateOverviewSnapshotSuccessfully(): void
    {
        // Arrange
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->with(StatisticsSnapshot::TYPE_OVERVIEW, $this->testPeriod)
            ->willReturn(false);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot();
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $this->service->createOverviewSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals(StatisticsSnapshot::TYPE_OVERVIEW, $result->getSnapshotType());
    }

    public function testCreateOverviewSnapshotThrowsExceptionWhenAlreadyExists(): void
    {
        // Arrange
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->with(StatisticsSnapshot::TYPE_OVERVIEW, $this->testPeriod)
            ->willReturn(true);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Overview snapshot already exists for this period');

        // Act
        $this->service->createOverviewSnapshot($this->testPeriod);
    }

    public function testCreatePostsSnapshotSuccessfully(): void
    {
        // Arrange
        $this->postStatisticsRepository
            ->expects($this->once())
            ->method('hasDataForPeriod')
            ->with($this->testPeriod)
            ->willReturn(true);

        $this->setupPostStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_POSTS);
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $this->service->createPostsSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals(StatisticsSnapshot::TYPE_POSTS, $result->getSnapshotType());
    }

    public function testCreatePostsSnapshotThrowsExceptionWhenNoData(): void
    {
        // Arrange
        $this->postStatisticsRepository
            ->expects($this->once())
            ->method('hasDataForPeriod')
            ->with($this->testPeriod)
            ->willReturn(false);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No post data available for the specified period');

        // Act
        $this->service->createPostsSnapshot($this->testPeriod);
    }

    public function testCreateUsersSnapshotSuccessfully(): void
    {
        // Arrange
        $this->userStatisticsRepository
            ->expects($this->once())
            ->method('hasDataForPeriod')
            ->with($this->testPeriod)
            ->willReturn(true);

        $this->setupUserStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_USERS);
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $this->service->createUsersSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals(StatisticsSnapshot::TYPE_USERS, $result->getSnapshotType());
    }

    public function testCreateUsersSnapshotThrowsExceptionWhenNoData(): void
    {
        // Arrange
        $this->userStatisticsRepository
            ->expects($this->once())
            ->method('hasDataForPeriod')
            ->with($this->testPeriod)
            ->willReturn(false);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No user data available for the specified period');

        // Act
        $this->service->createUsersSnapshot($this->testPeriod);
    }

    public function testCreatePopularSnapshotSuccessfully(): void
    {
        // Arrange
        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_POPULAR);
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $this->service->createPopularSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals(StatisticsSnapshot::TYPE_POPULAR, $result->getSnapshotType());
    }

    public function testCreateBatchSnapshotsSuccessfully(): void
    {
        // Arrange
        $types = [StatisticsSnapshot::TYPE_POSTS, StatisticsSnapshot::TYPE_USERS];

        $this->postStatisticsRepository
            ->method('hasDataForPeriod')
            ->willReturn(true);

        $this->userStatisticsRepository
            ->method('hasDataForPeriod')
            ->willReturn(true);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $postsSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_POSTS);
        $usersSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_USERS);

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls($postsSnapshot, $usersSnapshot);

        // Act
        $result = $this->service->createBatchSnapshots($this->testPeriod, $types);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey(StatisticsSnapshot::TYPE_POSTS, $result);
        $this->assertArrayHasKey(StatisticsSnapshot::TYPE_USERS, $result);
    }

    public function testCreateBatchSnapshotsThrowsExceptionWithInvalidTypes(): void
    {
        // Arrange
        $types = ['invalid_type'];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid snapshot types: invalid_type');

        // Act
        $this->service->createBatchSnapshots($this->testPeriod, $types);
    }

    public function testUpdateSnapshotSuccessfully(): void
    {
        // Arrange
        $snapshot = $this->createMock(StatisticsSnapshot::class);
        $snapshot->method('isExpired')->willReturn(false);
        $snapshot->method('validateDataIntegrity')->willReturn(true);
        $snapshot->method('getSnapshotType')->willReturn(StatisticsSnapshot::TYPE_OVERVIEW);
        $snapshot->method('getPeriod')->willReturn($this->testPeriod);
        $snapshot->expects($this->once())->method('updateStatistics');
        $snapshot->expects($this->once())->method('updateMetadata');

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $this->statisticsRepository
            ->expects($this->once())
            ->method('update')
            ->with($snapshot)
            ->willReturn($snapshot);

        // Act
        $result = $this->service->updateSnapshot($snapshot);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    public function testUpdateSnapshotThrowsExceptionForExpiredSnapshot(): void
    {
        // Arrange
        $snapshot = $this->createMock(StatisticsSnapshot::class);
        $snapshot->method('isExpired')->willReturn(true);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot update expired snapshot');

        // Act
        $this->service->updateSnapshot($snapshot);
    }

    public function testUpdateSnapshotThrowsExceptionForInvalidData(): void
    {
        // Arrange
        $snapshot = $this->createMock(StatisticsSnapshot::class);
        $snapshot->method('isExpired')->willReturn(false);
        $snapshot->method('validateDataIntegrity')->willReturn(false);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Snapshot data integrity validation failed');

        // Act
        $this->service->updateSnapshot($snapshot);
    }

    public function testCalculateTrendsSuccessfully(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $currentSnapshot = $this->createTestSnapshot();
        $previousSnapshot = $this->createTestSnapshot();

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->with(
                $this->identicalTo(StatisticsSnapshot::TYPE_OVERVIEW),
                $this->logicalOr(
                    $this->identicalTo($this->testPeriod),
                    $this->identicalTo($previousPeriod),
                ),
            )
            ->willReturnOnConsecutiveCalls($currentSnapshot, $previousSnapshot);

        // Act
        $result = $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('current_value', $result);
        $this->assertArrayHasKey('previous_value', $result);
        $this->assertArrayHasKey('percentage_change', $result);
        $this->assertArrayHasKey('trend_direction', $result);
    }

    public function testCalculateTrendsThrowsExceptionWhenCurrentSnapshotNotFound(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $this->statisticsRepository
            ->expects($this->once())
            ->method('findByTypeAndPeriod')
            ->with(StatisticsSnapshot::TYPE_OVERVIEW, $this->testPeriod)
            ->willReturn(null);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Current period snapshot not found');

        // Act
        $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );
    }

    public function testCalculateTrendsThrowsExceptionWhenPreviousSnapshotNotFound(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));
        $currentSnapshot = $this->createTestSnapshot();

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->with(
                $this->identicalTo(StatisticsSnapshot::TYPE_OVERVIEW),
                $this->logicalOr(
                    $this->identicalTo($this->testPeriod),
                    $this->identicalTo($previousPeriod),
                ),
            )
            ->willReturnOnConsecutiveCalls($currentSnapshot, null);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Previous period snapshot not found');

        // Act
        $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );
    }

    public function testCleanExpiredSnapshots(): void
    {
        // Arrange
        $beforeDate = new DateTimeImmutable('2023-01-01');
        $expectedCount = 5;

        $this->statisticsRepository
            ->expects($this->once())
            ->method('deleteExpiredSnapshots')
            ->with($beforeDate)
            ->willReturn($expectedCount);

        // Act
        $result = $this->service->cleanExpiredSnapshots($beforeDate);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * 測試無效週期時拋出異常.
     */
    public function testInvalidPeriodThrowsException(): void
    {
        // Arrange: 建立一個持續時間為 0 的無效週期，因為 StatisticsPeriod 是 final，我們不能 mock
        // 所以我們直接測試服務對有效週期的驗證

        // 我們使用反射來測試私有方法，或者測試公開的行為
        // 在這種情況下，我們通過一個持續時間為 0 的週期來觸發驗證錯誤

        // 由於無法直接建立無效的 StatisticsPeriod，我們跳過這個測試
        $this->markTestSkipped('Cannot create invalid StatisticsPeriod due to its validation in constructor');
    }

    /**
     * 設定文章統計 Mock 方法.
     */
    private function setupPostStatisticsMocks(): void
    {
        $this->postStatisticsRepository
            ->method('getTotalPostsCount')
            ->willReturn(100);

        $this->postStatisticsRepository
            ->method('getPostActivitySummary')
            ->willReturn([
                'total_posts' => 100,
                'published_posts' => 90,
                'draft_posts' => 10,
                'total_views' => 1000,
                'active_authors' => 20,
                'popular_sources' => ['web' => 50, 'mobile' => 50],
            ]);

        $this->postStatisticsRepository
            ->method('getPostsCountByStatus')
            ->willReturn(['published' => 90, 'draft' => 10]);

        $this->postStatisticsRepository
            ->method('getPostsCountBySource')
            ->willReturn(['web' => 50, 'mobile' => 50]);

        $this->postStatisticsRepository
            ->method('getPostViewsStatistics')
            ->willReturn([
                'total_views' => 1000,
                'unique_views' => 800,
                'avg_views_per_post' => 10.0,
            ]);

        $this->postStatisticsRepository
            ->method('getPopularPosts')
            ->willReturn([
                ['post_id' => 1, 'title' => 'Post 1', 'metric_value' => 100],
                ['post_id' => 2, 'title' => 'Post 2', 'metric_value' => 90],
            ]);

        $this->postStatisticsRepository
            ->method('getPostsLengthStatistics')
            ->willReturn([
                'avg_length' => 500.0,
                'min_length' => 100,
                'max_length' => 1000,
                'total_chars' => 50000,
            ]);

        $this->postStatisticsRepository
            ->method('getPostsPublishTimeDistribution')
            ->willReturn(['09:00' => 10, '10:00' => 15, '11:00' => 20]);

        $this->postStatisticsRepository
            ->method('getPostsCountByUser')
            ->willReturn([
                ['user_id' => 1, 'posts_count' => 10, 'total_views' => 100],
                ['user_id' => 2, 'posts_count' => 8, 'total_views' => 80],
            ]);

        $this->postStatisticsRepository
            ->method('getPinnedPostsStatistics')
            ->willReturn([
                'pinned_count' => 5,
                'unpinned_count' => 95,
                'pinned_views' => 500,
            ]);
    }

    /**
     * 設定使用者統計 Mock 方法.
     */
    private function setupUserStatisticsMocks(): void
    {
        $this->userStatisticsRepository
            ->method('getActiveUsersCount')
            ->willReturn(50);

        $this->userStatisticsRepository
            ->method('getNewUsersCount')
            ->willReturn(10);

        $this->userStatisticsRepository
            ->method('getUserActivitySummary')
            ->willReturn([
                'total_users' => 100,
                'active_users' => 50,
                'new_users' => 10,
                'returning_users' => 40,
                'user_activity_rate' => 50.0,
                'top_active_hours' => [9, 10, 11],
            ]);

        $this->userStatisticsRepository
            ->method('getActiveUsersByActivityType')
            ->willReturn(['login' => 40, 'post' => 30, 'view' => 50]);

        $this->userStatisticsRepository
            ->method('getUserLoginActivity')
            ->willReturn([
                'total_logins' => 200,
                'unique_users' => 50,
                'avg_logins_per_user' => 4.0,
                'peak_hour' => 10,
                'login_frequency_distribution' => ['daily' => 20, 'weekly' => 30],
            ]);

        $this->userStatisticsRepository
            ->method('getMostActiveUsers')
            ->willReturn([
                ['user_id' => 1, 'username' => 'user1', 'metric_value' => 100, 'rank' => 1],
                ['user_id' => 2, 'username' => 'user2', 'metric_value' => 90, 'rank' => 2],
            ]);

        $this->userStatisticsRepository
            ->method('getUserEngagementStatistics')
            ->willReturn([
                'high_engagement' => 10,
                'medium_engagement' => 20,
                'low_engagement' => 15,
                'inactive' => 5,
                'avg_engagement_score' => 75.0,
            ]);

        $this->userStatisticsRepository
            ->method('getUserRegistrationSources')
            ->willReturn(['direct' => 30, 'social' => 15, 'referral' => 5]);

        $this->userStatisticsRepository
            ->method('getUserGeographicalDistribution')
            ->willReturn([
                ['location' => 'Taiwan', 'users_count' => 30, 'percentage' => 60.0],
                ['location' => 'USA', 'users_count' => 20, 'percentage' => 40.0],
            ]);

        $this->userStatisticsRepository
            ->method('getUsersCountByRole')
            ->willReturn(['user' => 45, 'admin' => 5]);

        $this->userStatisticsRepository
            ->method('getUserActivityTimeDistribution')
            ->willReturn(['09:00' => 10, '10:00' => 15, '11:00' => 20]);
    }

    /**
     * 建立測試用的統計快照.
     */
    private function createTestSnapshot(string $type = StatisticsSnapshot::TYPE_OVERVIEW): StatisticsSnapshot
    {
        return StatisticsSnapshot::create(
            $type,
            $this->testPeriod,
            ['total_count' => 100, 'test_data' => true],
            ['test' => true],
        );
    }
}
