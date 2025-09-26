<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    public function testCreateOverviewSnapshotWithEventDispatcher(): void
    {
        // Arrange
        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $mockEventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(StatisticsSnapshotCreated::class));

        $serviceWithEvent = new StatisticsAggregationService(
            $this->statisticsRepository,
            $this->postStatisticsRepository,
            $this->userStatisticsRepository,
            $mockEventDispatcher,
        );

        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot();
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $serviceWithEvent->createOverviewSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    public function testCreateOverviewSnapshotWithEventDispatcherException(): void
    {
        // Arrange
        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $mockEventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new RuntimeException('Event dispatch failed'));

        $serviceWithEvent = new StatisticsAggregationService(
            $this->statisticsRepository,
            $this->postStatisticsRepository,
            $this->userStatisticsRepository,
            $mockEventDispatcher,
        );

        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot();
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act & Assert - 即使事件分發失敗，服務也應該正常運作
        $result = $serviceWithEvent->createOverviewSnapshot($this->testPeriod);
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    public function testUpdateSnapshotThrowsExceptionForUnsupportedType(): void
    {
        // Arrange
        $snapshot = $this->createMock(StatisticsSnapshot::class);
        $snapshot->method('isExpired')->willReturn(false);
        $snapshot->method('validateDataIntegrity')->willReturn(true);
        $snapshot->method('getSnapshotType')->willReturn('unsupported_type');
        $snapshot->method('getPeriod')->willReturn($this->testPeriod);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported snapshot type: unsupported_type');

        // Act
        $this->service->updateSnapshot($snapshot);
    }

    public function testCreateBatchSnapshotsWithPartialFailure(): void
    {
        // Arrange
        $types = [StatisticsSnapshot::TYPE_POSTS, StatisticsSnapshot::TYPE_USERS];

        // Posts 有資料，Users 沒有資料
        $this->postStatisticsRepository
            ->method('hasDataForPeriod')
            ->willReturn(true);

        $this->userStatisticsRepository
            ->method('hasDataForPeriod')
            ->willReturn(false);

        $this->setupPostStatisticsMocks();

        // Assert - 預期會拋出異常，因為批次操作中有失敗
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create some snapshots:');

        // Act
        $this->service->createBatchSnapshots($this->testPeriod, $types);
    }

    public function testCreatePopularSnapshotWithCustomMetadata(): void
    {
        // Arrange
        $customMetadata = ['custom_field' => 'custom_value', 'priority' => 'high'];
        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (StatisticsSnapshot $snapshot) use ($customMetadata) {
                $metadata = $snapshot->getMetadata();

                return $metadata['custom_field'] === 'custom_value' && $metadata['priority'] === 'high';
            }))
            ->willReturn($this->createTestSnapshot(StatisticsSnapshot::TYPE_POPULAR));

        // Act
        $result = $this->service->createPopularSnapshot($this->testPeriod, $customMetadata);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    public function testCreateOverviewSnapshotWithCustomExpirationDate(): void
    {
        // Arrange
        $expirationDate = new DateTimeImmutable('2023-12-31');
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $capturedSnapshot = null;
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (StatisticsSnapshot $snapshot) use (&$capturedSnapshot) {
                $capturedSnapshot = $snapshot;

                return $snapshot;
            });

        // Act
        $result = $this->service->createOverviewSnapshot($this->testPeriod, [], $expirationDate);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertNotNull($capturedSnapshot);
        $this->assertEquals($expirationDate, $capturedSnapshot->getExpiresAt());
    }

    public function testCalculateGrowthRateEdgeCases(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateGrowthRate');
        $method->setAccessible(true);

        // Test case 1: previous = 0, current > 0
        $result = $method->invokeArgs($this->service, [100, 0]);
        $this->assertEquals(100.0, $result);

        // Test case 2: previous = 0, current = 0
        $result = $method->invokeArgs($this->service, [0, 0]);
        $this->assertEquals(0.0, $result);

        // Test case 3: normal growth
        $result = $method->invokeArgs($this->service, [120, 100]);
        $this->assertEquals(20.0, $result);

        // Test case 4: negative growth
        $result = $method->invokeArgs($this->service, [80, 100]);
        $this->assertEquals(-20.0, $result);
    }

    public function testCalculateContentVelocityEdgeCases(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateContentVelocity');
        $method->setAccessible(true);

        // Test case 1: normal case
        $result = $method->invokeArgs($this->service, [$this->testPeriod, 100]);
        $expected = round(100 / max(1, $this->testPeriod->getDurationInDays()), 2);
        $this->assertEquals($expected, $result);

        // Test case 2: zero posts
        $result = $method->invokeArgs($this->service, [$this->testPeriod, 0]);
        $this->assertEquals(0.0, $result);
    }

    public function testValidateSnapshotTypesWithEmptyArray(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('validateSnapshotTypes');
        $method->setAccessible(true);

        // Empty array should not throw exception
        $method->invokeArgs($this->service, [[]]);
        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    public function testValidateSnapshotTypesWithMixedValidInvalid(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('validateSnapshotTypes');
        $method->setAccessible(true);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid snapshot types: invalid_type1, invalid_type2');

        // Act
        $method->invokeArgs($this->service, [[
            StatisticsSnapshot::TYPE_OVERVIEW,
            'invalid_type1',
            StatisticsSnapshot::TYPE_POSTS,
            'invalid_type2',
        ]]);
    }

    public function testCreateOverviewSnapshotWithRepositorySaveFailure(): void
    {
        // Arrange
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database save failed'));

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database save failed');

        // Act
        $this->service->createOverviewSnapshot($this->testPeriod);
    }

    public function testCreateOverviewSnapshotWithDataAggregationFailure(): void
    {
        // Arrange
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        // Mock 文章統計資料庫拋出異常
        $this->postStatisticsRepository
            ->method('getTotalPostsCount')
            ->willThrowException(new RuntimeException('Post statistics query failed'));

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Post statistics query failed');

        // Act
        $this->service->createOverviewSnapshot($this->testPeriod);
    }

    public function testUpdateSnapshotWithRepositoryUpdateFailure(): void
    {
        // Arrange
        $snapshot = $this->createMock(StatisticsSnapshot::class);
        $snapshot->method('isExpired')->willReturn(false);
        $snapshot->method('validateDataIntegrity')->willReturn(true);
        $snapshot->method('getSnapshotType')->willReturn(StatisticsSnapshot::TYPE_OVERVIEW);
        $snapshot->method('getPeriod')->willReturn($this->testPeriod);

        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        $this->statisticsRepository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('Update failed'));

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Update failed');

        // Act
        $this->service->updateSnapshot($snapshot);
    }

    public function testCreateBatchSnapshotsWithEmptyTypesArray(): void
    {
        // Act
        $result = $this->service->createBatchSnapshots($this->testPeriod, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCalculateTrendsWithIdenticalSnapshots(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 100]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->willReturnOnConsecutiveCalls($currentSnapshot, $previousSnapshot);

        // Act
        $result = $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );

        // Assert
        $this->assertEquals(100, $result['current_value']);
        $this->assertEquals(100, $result['previous_value']);
        $this->assertEquals(0.0, $result['percentage_change']);
        $this->assertEquals('stable', $result['trend_direction']);
    }

    public function testCalculateTrendsWithGrowingTrend(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 120]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->willReturnOnConsecutiveCalls($currentSnapshot, $previousSnapshot);

        // Act
        $result = $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );

        // Assert
        $this->assertEquals(120, $result['current_value']);
        $this->assertEquals(100, $result['previous_value']);
        $this->assertEquals(20.0, $result['percentage_change']);
        $this->assertEquals('up', $result['trend_direction']);
    }

    public function testCalculateTrendsWithDecliningTrend(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 80]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->willReturnOnConsecutiveCalls($currentSnapshot, $previousSnapshot);

        // Act
        $result = $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );

        // Assert
        $this->assertEquals(80, $result['current_value']);
        $this->assertEquals(100, $result['previous_value']);
        $this->assertEquals(-20.0, $result['percentage_change']);
        $this->assertEquals('down', $result['trend_direction']);
    }

    public function testCalculateTrendsWithZeroPreviousValue(): void
    {
        // Arrange
        $previousPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2022-12-31'));

        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 100]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 0]);

        $this->statisticsRepository
            ->expects($this->exactly(2))
            ->method('findByTypeAndPeriod')
            ->willReturnOnConsecutiveCalls($currentSnapshot, $previousSnapshot);

        // Act
        $result = $this->service->calculateTrends(
            $this->testPeriod,
            $previousPeriod,
            StatisticsSnapshot::TYPE_OVERVIEW,
        );

        // Assert
        $this->assertEquals(100, $result['current_value']);
        $this->assertEquals(0, $result['previous_value']);
        $this->assertEquals(100.0, $result['percentage_change']);
        $this->assertEquals('up', $result['trend_direction']);
    }

    /**
     * 測試私有方法 validatePeriod 的邊界條件.
     * 由於 StatisticsPeriod 是 final 類別且建構式有驗證，
     * 這個測試驗證正常情況不會拋出異常。
     */
    public function testValidatePeriodWithValidDuration(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePeriod');
        $method->setAccessible(true);

        // Act & Assert - 正常週期不應拋出異常
        $method->invokeArgs($this->service, [$this->testPeriod]);
        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    public function testCleanExpiredSnapshotsWithZeroDeleted(): void
    {
        // Arrange
        $beforeDate = new DateTimeImmutable('2023-01-01');

        $this->statisticsRepository
            ->expects($this->once())
            ->method('deleteExpiredSnapshots')
            ->with($beforeDate)
            ->willReturn(0);

        // Act
        $result = $this->service->cleanExpiredSnapshots($beforeDate);

        // Assert
        $this->assertEquals(0, $result);
    }

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

    /**
     * 測試聚合資料的完整性和結構.
     */
    public function testAggregateOverviewDataStructure(): void
    {
        // Arrange
        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('aggregateOverviewData');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$this->testPeriod]);

        // Assert - 驗證返回結構
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_posts', $result);
        $this->assertArrayHasKey('active_users', $result);
        $this->assertArrayHasKey('new_users', $result);
        $this->assertArrayHasKey('post_activity', $result);
        $this->assertArrayHasKey('user_activity', $result);
        $this->assertArrayHasKey('engagement_metrics', $result);
        $this->assertArrayHasKey('period_summary', $result);

        // 驗證巢狀結構
        $this->assertArrayHasKey('posts_per_active_user', $result['engagement_metrics']);
        $this->assertArrayHasKey('user_growth_rate', $result['engagement_metrics']);
        $this->assertArrayHasKey('content_velocity', $result['engagement_metrics']);

        $this->assertArrayHasKey('type', $result['period_summary']);
        $this->assertArrayHasKey('duration_days', $result['period_summary']);
        $this->assertArrayHasKey('start', $result['period_summary']);
        $this->assertArrayHasKey('end', $result['period_summary']);
    }

    public function testAggregatePostsDataStructure(): void
    {
        // Arrange
        $this->setupPostStatisticsMocks();

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('aggregatePostsData');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$this->testPeriod]);

        // Assert - 驗證文章統計資料結構
        $this->assertIsArray($result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_source', $result);
        $this->assertArrayHasKey('views_statistics', $result);
        $this->assertArrayHasKey('top_posts', $result);
        $this->assertArrayHasKey('length_statistics', $result);
        $this->assertArrayHasKey('time_distribution', $result);
        $this->assertArrayHasKey('top_authors', $result);
        $this->assertArrayHasKey('pinned_stats', $result);
    }

    public function testAggregateUsersDataStructure(): void
    {
        // Arrange
        $this->setupUserStatisticsMocks();

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('aggregateUsersData');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$this->testPeriod]);

        // Assert - 驗證使用者統計資料結構
        $this->assertIsArray($result);
        $this->assertArrayHasKey('active_users', $result);
        $this->assertArrayHasKey('by_activity_type', $result);
        $this->assertArrayHasKey('login_activity', $result);
        $this->assertArrayHasKey('most_active', $result);
        $this->assertArrayHasKey('engagement_stats', $result);
        $this->assertArrayHasKey('registration_sources', $result);
        $this->assertArrayHasKey('geographical_distribution', $result);
        $this->assertArrayHasKey('by_role', $result);
    }

    public function testAggregatePopularDataStructure(): void
    {
        // Arrange
        $this->setupPostStatisticsMocks();
        $this->setupUserStatisticsMocks();

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('aggregatePopularData');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$this->testPeriod]);

        // Assert - 驗證熱門統計資料結構
        $this->assertIsArray($result);
        $this->assertArrayHasKey('top_posts', $result);
        $this->assertArrayHasKey('top_users', $result);
        $this->assertArrayHasKey('trending_sources', $result);
        $this->assertArrayHasKey('peak_activity_times', $result);
    }

    public function testGenerateBaseMetadataStructure(): void
    {
        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generateBaseMetadata');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('generated_by', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('data_sources', $result);

        $this->assertEquals('StatisticsAggregationService', $result['generated_by']);
        $this->assertEquals('1.0.0', $result['version']);
        $this->assertArrayHasKey('posts', $result['data_sources']);
        $this->assertArrayHasKey('users', $result['data_sources']);
    }

    public function testComputeTrendMetricsWithUpTrend(): void
    {
        // Arrange
        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 150]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('computeTrendMetrics');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$currentSnapshot, $previousSnapshot]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(150, $result['current_value']);
        $this->assertEquals(100, $result['previous_value']);
        $this->assertEquals(50.0, $result['percentage_change']);
        $this->assertEquals('up', $result['trend_direction']);
    }

    public function testComputeTrendMetricsWithDownTrend(): void
    {
        // Arrange
        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 75]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('computeTrendMetrics');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$currentSnapshot, $previousSnapshot]);

        // Assert
        $this->assertEquals('down', $result['trend_direction']);
        $this->assertEquals(-25.0, $result['percentage_change']);
    }

    public function testComputeTrendMetricsWithStableTrend(): void
    {
        // Arrange
        $currentSnapshot = $this->createTestSnapshot();
        $currentSnapshot->updateStatistics(['total_count' => 100]);

        $previousSnapshot = $this->createTestSnapshot();
        $previousSnapshot->updateStatistics(['total_count' => 100]);

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('computeTrendMetrics');
        $method->setAccessible(true);

        // Act
        $result = $method->invokeArgs($this->service, [$currentSnapshot, $previousSnapshot]);

        // Assert
        $this->assertEquals('stable', $result['trend_direction']);
        $this->assertEquals(0.0, $result['percentage_change']);
    }

    /**
     * 測試不同的統計聚合服務 Mock 組合.
     */
    public function testCreateOverviewSnapshotWithMinimalData(): void
    {
        // Arrange - 設定最小的必要資料
        $this->statisticsRepository
            ->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->postStatisticsRepository
            ->method('getTotalPostsCount')
            ->willReturn(0);

        $this->userStatisticsRepository
            ->method('getActiveUsersCount')
            ->willReturn(0);

        $this->userStatisticsRepository
            ->method('getNewUsersCount')
            ->willReturn(0);

        $this->postStatisticsRepository
            ->method('getPostActivitySummary')
            ->willReturn([]);

        $this->userStatisticsRepository
            ->method('getUserActivitySummary')
            ->willReturn([]);

        $expectedSnapshot = $this->createTestSnapshot();
        $this->statisticsRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $result = $this->service->createOverviewSnapshot($this->testPeriod);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    /**
     * 測試成功建立快照後的事件分發.
     */
    public function testSnapshotCreatedEventIsDispatchedCorrectly(): void
    {
        // Arrange
        $mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // 使用 callback 來驗證事件的內容
        $mockEventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof StatisticsSnapshotCreated
                    && $event->getSnapshot()->getSnapshotType() === StatisticsSnapshot::TYPE_POSTS;
            }));

        $serviceWithEvent = new StatisticsAggregationService(
            $this->statisticsRepository,
            $this->postStatisticsRepository,
            $this->userStatisticsRepository,
            $mockEventDispatcher,
        );

        $this->postStatisticsRepository
            ->method('hasDataForPeriod')
            ->willReturn(true);

        $this->setupPostStatisticsMocks();

        $expectedSnapshot = $this->createTestSnapshot(StatisticsSnapshot::TYPE_POSTS);
        $this->statisticsRepository
            ->method('save')
            ->willReturn($expectedSnapshot);

        // Act
        $serviceWithEvent->createPostsSnapshot($this->testPeriod);
    }
}
