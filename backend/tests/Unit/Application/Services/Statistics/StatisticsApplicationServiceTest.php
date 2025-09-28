<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services\Statistics;

use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StatisticsApplicationService 單元測試.
 *
 * 測試統計應用服務的核心功能，包括：
 * - 協調多個領域服務
 * - 處理應用層的事務邏輯
 * - 快取策略實作
 * - 錯誤處理
 */
final class StatisticsApplicationServiceTest extends TestCase
{
    private StatisticsApplicationService $service;

    private StatisticsAggregationServiceInterface|MockInterface $aggregationService;

    private StatisticsCacheServiceInterface|MockInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregationService = Mockery::mock(StatisticsAggregationServiceInterface::class);
        $this->cacheService = Mockery::mock(StatisticsCacheServiceInterface::class);

        $this->service = new StatisticsApplicationService(
            $this->aggregationService,
            $this->cacheService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試建立綜合統計快照.
     */
    public function testCreateOverviewSnapshotSuccessfully(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $metadata = ['source' => 'test'];
        $expectedSnapshot = $this->createMockSnapshot(StatisticsSnapshot::TYPE_OVERVIEW, $period);

        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->with($period, $metadata, null)
            ->andReturn($expectedSnapshot);

        $this->cacheService
            ->shouldReceive('forget')
            ->once()
            ->with(Mockery::type('array'));

        // Act
        $result = $this->service->createOverviewSnapshot($period, $metadata);

        // Assert
        $this->assertSame($expectedSnapshot, $result);
    }

    /**
     * 測試建立綜合統計快照時的錯誤處理.
     */
    public function testCreateOverviewSnapshotHandlesException(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));

        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create overview snapshot: Database error');

        // Act
        $this->service->createOverviewSnapshot($period);
    }

    /**
     * 測試建立文章統計快照.
     */
    public function testCreatePostsSnapshotSuccessfully(): void
    {
        // Arrange
        $period = StatisticsPeriod::createWeekly(new DateTimeImmutable('2025-01-01'));
        $metadata = ['include_drafts' => false];
        $expectedSnapshot = $this->createMockSnapshot(StatisticsSnapshot::TYPE_POSTS, $period);

        $this->aggregationService
            ->shouldReceive('createPostsSnapshot')
            ->once()
            ->with($period, $metadata, null)
            ->andReturn($expectedSnapshot);

        $this->cacheService
            ->shouldReceive('forget')
            ->once()
            ->with(Mockery::type('array'));

        // Act
        $result = $this->service->createPostsSnapshot($period, $metadata);

        // Assert
        $this->assertSame($expectedSnapshot, $result);
    }

    /**
     * 測試建立使用者統計快照.
     */
    public function testCreateUsersSnapshotSuccessfully(): void
    {
        // Arrange
        $period = StatisticsPeriod::createMonthly(new DateTimeImmutable('2025-01-01'));
        $metadata = ['include_inactive' => true];
        $expectedSnapshot = $this->createMockSnapshot(StatisticsSnapshot::TYPE_USERS, $period);

        $this->aggregationService
            ->shouldReceive('createUsersSnapshot')
            ->once()
            ->with($period, $metadata, null)
            ->andReturn($expectedSnapshot);

        $this->cacheService
            ->shouldReceive('forget')
            ->once()
            ->with(Mockery::type('array'));

        // Act
        $result = $this->service->createUsersSnapshot($period, $metadata);

        // Assert
        $this->assertSame($expectedSnapshot, $result);
    }

    /**
     * 測試批量建立統計快照.
     */
    public function testCreateBatchSnapshotsSuccessfully(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $types = [StatisticsSnapshot::TYPE_OVERVIEW, StatisticsSnapshot::TYPE_POSTS];
        $metadata = ['batch' => true];

        $expectedSnapshots = [
            StatisticsSnapshot::TYPE_OVERVIEW => $this->createMockSnapshot(StatisticsSnapshot::TYPE_OVERVIEW, $period),
            StatisticsSnapshot::TYPE_POSTS => $this->createMockSnapshot(StatisticsSnapshot::TYPE_POSTS, $period),
        ];

        $this->aggregationService
            ->shouldReceive('createBatchSnapshots')
            ->once()
            ->with($period, $types, $metadata, null)
            ->andReturn($expectedSnapshots);

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics']);

        // Act
        $result = $this->service->createBatchSnapshots($period, $types, $metadata);

        // Assert
        $this->assertSame($expectedSnapshots, $result);
    }

    /**
     * 測試批量建立統計快照時的錯誤處理.
     */
    public function testCreateBatchSnapshotsHandlesException(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $types = [StatisticsSnapshot::TYPE_OVERVIEW];

        $this->aggregationService
            ->shouldReceive('createBatchSnapshots')
            ->once()
            ->andThrow(new RuntimeException('Failed to create snapshots'));

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create batch snapshots: Failed to create snapshots');

        // Act
        $this->service->createBatchSnapshots($period, $types);
    }

    /**
     * 測試更新統計快照.
     */
    public function testUpdateSnapshotSuccessfully(): void
    {
        // Arrange
        $snapshot = $this->createMockSnapshot(
            StatisticsSnapshot::TYPE_OVERVIEW,
            StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01')),
        );

        $this->aggregationService
            ->shouldReceive('updateSnapshot')
            ->once()
            ->with($snapshot)
            ->andReturn($snapshot);

        $this->cacheService
            ->shouldReceive('forget')
            ->once()
            ->with(Mockery::type('array'));

        // Act
        $result = $this->service->updateSnapshot($snapshot);

        // Assert
        $this->assertSame($snapshot, $result);
    }

    /**
     * 測試計算統計趨勢.
     */
    public function testCalculateTrendsSuccessfully(): void
    {
        // Arrange
        $currentPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-02'));
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;

        $expectedTrends = [
            'current_value' => 100,
            'previous_value' => 80,
            'percentage_change' => 25.0,
            'trend_direction' => 'up',
        ];

        $cacheKey = "trends.{$snapshotType}.{$currentPeriod->type->value}.{$currentPeriod->startTime->format('Y-m-d')}-{$previousPeriod->startTime->format('Y-m-d')}";

        $this->cacheService
            ->shouldReceive('remember')
            ->once()
            ->with($cacheKey, Mockery::type('callable'), 3600)
            ->andReturnUsing(function ($key, callable $callback) {
                return $callback();
            });

        $this->aggregationService
            ->shouldReceive('calculateTrends')
            ->once()
            ->with($currentPeriod, $previousPeriod, $snapshotType)
            ->andReturn($expectedTrends);

        // Act
        $result = $this->service->calculateTrends($currentPeriod, $previousPeriod, $snapshotType);

        // Assert
        $this->assertEquals($expectedTrends, $result);
    }

    /**
     * 測試清理過期快照.
     */
    public function testCleanExpiredSnapshotsSuccessfully(): void
    {
        // Arrange
        $beforeDate = new DateTimeImmutable('2025-01-01');
        $expectedCount = 5;

        $this->aggregationService
            ->shouldReceive('cleanExpiredSnapshots')
            ->once()
            ->with($beforeDate)
            ->andReturn($expectedCount);

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics']);

        // Act
        $result = $this->service->cleanExpiredSnapshots($beforeDate);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * 測試帶快取的統計查詢.
     */
    public function testGetCachedStatisticsReturnsFromCache(): void
    {
        // Arrange
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $cacheKey = "statistics.{$snapshotType}.daily.2025-01-01";
        $cachedData = ['total_posts' => 100];

        $this->cacheService
            ->shouldReceive('remember')
            ->once()
            ->with($cacheKey, Mockery::type('callable'), 1800)
            ->andReturn($cachedData);

        // Act
        $result = $this->service->getCachedStatistics($snapshotType, $period);

        // Assert
        $this->assertEquals($cachedData, $result);
    }

    /**
     * 測試快取失效.
     */
    public function testInvalidateCacheSuccessfully(): void
    {
        // Arrange
        $tags = ['statistics', 'overview'];

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with($tags);

        // Act
        $this->service->invalidateCache($tags);

        // Assert - 透過 mock 驗證
        $this->addToAssertionCount(1);
    }

    /**
     * 測試驗證統計週期的邊界情況.
     */
    public function testValidatesPeriodBoundaries(): void
    {
        // Arrange
        $futurePeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('+1 day'));

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create statistics for future periods');

        // Act
        $this->service->createOverviewSnapshot($futurePeriod);
    }

    /**
     * 測試空的快照類型陣列驗證.
     */
    public function testValidatesEmptySnapshotTypesArray(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $emptyTypes = [];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Snapshot types array cannot be empty');

        // Act
        $this->service->createBatchSnapshots($period, $emptyTypes);
    }

    /**
     * 測試不支援的快照類型驗證.
     */
    public function testValidatesUnsupportedSnapshotTypes(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $invalidTypes = ['invalid_type'];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported snapshot types');

        // Act
        $this->service->createBatchSnapshots($period, $invalidTypes);
    }

    /**
     * 測試快取鍵生成.
     */
    public function testGeneratesCacheKeysCorrectly(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));
        $snapshotType = StatisticsSnapshot::TYPE_POSTS;
        $expectedData = ['cached' => true];

        $this->cacheService
            ->shouldReceive('remember')
            ->once()
            ->with('statistics.posts.daily.2025-01-01', Mockery::type('callable'), 1800)
            ->andReturn($expectedData);

        // Act
        $result = $this->service->getCachedStatistics($snapshotType, $period);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    /**
     * 測試事務回滾處理.
     */
    public function testHandlesTransactionRollback(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-01-01'));

        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->andThrow(new RuntimeException('Transaction failed'));

        // 確保即使發生錯誤，也不會影響快取
        $this->cacheService
            ->shouldNotReceive('forget');

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        $this->service->createOverviewSnapshot($period);
    }

    /**
     * 建立 Mock StatisticsSnapshot 物件.
     */
    private function createMockSnapshot(string $type, StatisticsPeriod $period): StatisticsSnapshot
    {
        $snapshot = Mockery::mock(StatisticsSnapshot::class);
        $snapshot->shouldReceive('getUuid')->andReturn('test-uuid-' . uniqid());
        $snapshot->shouldReceive('getSnapshotType')->andReturn($type);
        $snapshot->shouldReceive('getPeriod')->andReturn($period);
        $snapshot->shouldReceive('getStatisticsData')->andReturn(['test' => 'data']);

        return $snapshot;
    }
}
