<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Commands;

use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Commands\StatisticsCalculationCommand;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * StatisticsCalculationCommand 單元測試.
 */
#[CoversClass(StatisticsCalculationCommand::class)]
final class StatisticsCalculationCommandTest extends TestCase
{
    private StatisticsCalculationCommand $command;

    private MockInterface|StatisticsAggregationServiceInterface $mockAggregationService;

    private MockInterface|StatisticsRepositoryInterface $mockRepository;

    private MockInterface|StatisticsCacheServiceInterface $mockCacheService;

    private MockInterface|LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAggregationService = Mockery::mock(StatisticsAggregationServiceInterface::class);
        $this->mockRepository = Mockery::mock(StatisticsRepositoryInterface::class);
        $this->mockCacheService = Mockery::mock(StatisticsCacheServiceInterface::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);

        $this->command = new StatisticsCalculationCommand(
            $this->mockAggregationService,
            $this->mockRepository,
            $this->mockCacheService,
            $this->mockLogger,
            '/tmp',
        );

        // 預設 Logger 行為
        $this->mockLogger->shouldReceive('info')->byDefault();
        $this->mockLogger->shouldReceive('debug')->byDefault();
        $this->mockLogger->shouldReceive('warning')->byDefault();
        $this->mockLogger->shouldReceive('error')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試成功執行日統計計算.
     */
    public function testExecuteSuccessfullyCalculatesDailyStatistics(): void
    {
        // Arrange
        $periods = ['daily'];
        $snapshotTypes = [
            StatisticsSnapshot::TYPE_OVERVIEW,
            StatisticsSnapshot::TYPE_POSTS,
            StatisticsSnapshot::TYPE_USERS,
            StatisticsSnapshot::TYPE_POPULAR,
        ];

        // 模擬不存在的快照
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(count($snapshotTypes))
            ->andReturn(false);

        // 模擬聚合服務返回統計快照
        foreach ($snapshotTypes as $type) {
            $methodName = match ($type) {
                StatisticsSnapshot::TYPE_OVERVIEW => 'createOverviewSnapshot',
                StatisticsSnapshot::TYPE_POSTS => 'createPostsSnapshot',
                StatisticsSnapshot::TYPE_USERS => 'createUsersSnapshot',
                StatisticsSnapshot::TYPE_POPULAR => 'createPopularSnapshot',
            };

            $this->mockAggregationService
                ->shouldReceive($methodName)
                ->once()
                ->with(Mockery::type(StatisticsPeriod::class))
                ->andReturn(Mockery::mock(StatisticsSnapshot::class));
        }

        // 模擬儲存快照
        $this->mockRepository
            ->shouldReceive('save')
            ->times(count($snapshotTypes))
            ->with(Mockery::type(StatisticsSnapshot::class));

        // 模擬快取清除
        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->times(count($snapshotTypes));

        // Act
        $result = $this->command->execute($periods);

        // Assert
        $this->assertArrayHasKey('start_time', $result);
        $this->assertArrayHasKey('end_time', $result);
        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertEquals(count($snapshotTypes), $result['total_snapshots']);
        $this->assertEquals(count($snapshotTypes), $result['successful_snapshots']);
        $this->assertEquals(0, $result['failed_snapshots']);
        $this->assertEquals(0, $result['retries']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * 測試執行多種週期的統計計算.
     */
    public function testExecuteMultiplePeriods(): void
    {
        // Arrange
        $periods = ['daily', 'weekly', 'monthly'];
        $totalExpectedSnapshots = count($periods) * 4; // 4 種統計快照類型

        // 模擬不存在的快照
        $this->mockRepository
            ->shouldReceive('exists')
            ->times($totalExpectedSnapshots)
            ->andReturn(false);

        // 模擬聚合服務和儲存
        for ($i = 0; $i < $totalExpectedSnapshots; $i++) {
            $methodIndex = $i % 4;
            $methodName = match ($methodIndex) {
                0 => 'createOverviewSnapshot',
                1 => 'createPostsSnapshot',
                2 => 'createUsersSnapshot',
                3 => 'createPopularSnapshot',
            };

            $this->mockAggregationService
                ->shouldReceive($methodName)
                ->once()
                ->andReturn(Mockery::mock(StatisticsSnapshot::class));
        }

        $this->mockRepository
            ->shouldReceive('save')
            ->times($totalExpectedSnapshots);

        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->times($totalExpectedSnapshots);

        // Act
        $result = $this->command->execute($periods);

        // Assert
        $this->assertEquals($totalExpectedSnapshots, $result['total_snapshots']);
        $this->assertEquals($totalExpectedSnapshots, $result['successful_snapshots']);
        $this->assertEquals(0, $result['failed_snapshots']);
    }

    /**
     * 測試強制重新計算現有快照.
     */
    public function testExecuteWithForceFlag(): void
    {
        // Arrange
        $periods = ['daily'];
        $expectedSnapshots = 4;

        // 模擬已存在的快照（但使用 force = true）
        $this->mockRepository
            ->shouldReceive('exists')
            ->never(); // force = true 時不應檢查是否存在

        // 模擬聚合服務和儲存
        for ($i = 0; $i < $expectedSnapshots; $i++) {
            $methodIndex = $i % 4;
            $methodName = match ($methodIndex) {
                0 => 'createOverviewSnapshot',
                1 => 'createPostsSnapshot',
                2 => 'createUsersSnapshot',
                3 => 'createPopularSnapshot',
            };

            $this->mockAggregationService
                ->shouldReceive($methodName)
                ->once()
                ->andReturn(Mockery::mock(StatisticsSnapshot::class));
        }

        $this->mockRepository
            ->shouldReceive('save')
            ->times($expectedSnapshots);

        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->times($expectedSnapshots);

        // Act
        $result = $this->command->execute($periods, force: true);

        // Assert
        $this->assertEquals($expectedSnapshots, $result['total_snapshots']);
        $this->assertEquals($expectedSnapshots, $result['successful_snapshots']);
    }

    /**
     * 測試跳過已存在的快照.
     */
    public function testExecuteSkipsExistingSnapshots(): void
    {
        // Arrange
        $periods = ['daily'];

        // 模擬快照已存在
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(4)
            ->andReturn(true);

        // 不應該呼叫聚合和儲存方法
        $this->mockAggregationService->shouldNotReceive('aggregateByTypeAndPeriod');
        $this->mockRepository->shouldNotReceive('save');
        $this->mockCacheService->shouldNotReceive('flushByTags');

        // Act
        $result = $this->command->execute($periods);

        // Assert
        $this->assertEquals(4, $result['total_snapshots']);
        $this->assertEquals(4, $result['successful_snapshots']);
        $this->assertEquals(0, $result['failed_snapshots']);
    }

    /**
     * 測試聚合服務失敗時的重試機制.
     */
    public function testExecuteRetriesOnAggregationFailure(): void
    {
        // Arrange
        $periods = ['daily'];
        $maxRetries = 2;

        // 第一種快照類型會重試，所以 exists 被呼叫 3 次（初始 + 2 次重試）
        // 其他 3 種快照類型只呼叫 1 次 exists
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(6) // 3 + 1 + 1 + 1 = 6 次
            ->andReturn(false);

        // 第一種快照類型：前兩次失敗，第三次成功
        $this->mockAggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsPeriod::class))
            ->andThrow(new RuntimeException('聚合失敗'));

        $this->mockAggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsPeriod::class))
            ->andThrow(new RuntimeException('聚合失敗'));

        $this->mockAggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsPeriod::class))
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        // 其他快照類型成功
        $this->mockAggregationService
            ->shouldReceive('createPostsSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        $this->mockAggregationService
            ->shouldReceive('createUsersSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        $this->mockAggregationService
            ->shouldReceive('createPopularSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        // 儲存 4 次
        $this->mockRepository
            ->shouldReceive('save')
            ->times(4);

        // 清除快取 4 次
        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->times(4);

        // Act
        $result = $this->command->execute($periods, $maxRetries);

        // Assert
        $this->assertEquals(4, $result['total_snapshots']);
        $this->assertEquals(4, $result['successful_snapshots']);
        $this->assertEquals(0, $result['failed_snapshots']);
        $this->assertEquals(2, $result['retries']); // 兩次重試
    }

    /**
     * 測試重試次數超過限制時的失敗處理.
     */
    public function testExecuteFailsAfterMaxRetries(): void
    {
        // Arrange
        $periods = ['daily'];
        $maxRetries = 1;

        // 第一種快照類型會重試，所以 exists 被呼叫 2 次（初始 + 1 次重試）
        // 其他 3 種快照類型只呼叫 1 次 exists
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(5) // 2 + 1 + 1 + 1 = 5 次
            ->andReturn(false);

        // 第一種快照類型：持續失敗
        $this->mockAggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->times(2) // 初始嘗試 + 1 次重試
            ->with(Mockery::type(StatisticsPeriod::class))
            ->andThrow(new RuntimeException('持續失敗'));

        // 其他快照類型成功
        $this->mockAggregationService
            ->shouldReceive('createPostsSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        $this->mockAggregationService
            ->shouldReceive('createUsersSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        $this->mockAggregationService
            ->shouldReceive('createPopularSnapshot')
            ->once()
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        // 只儲存成功的 3 個
        $this->mockRepository
            ->shouldReceive('save')
            ->times(3);

        // 只清除成功的 3 個快取
        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->times(3);

        // Act
        $result = $this->command->execute($periods, $maxRetries);

        // Assert
        $this->assertEquals(4, $result['total_snapshots']); // 4 種快照類型
        $this->assertEquals(3, $result['successful_snapshots']); // 3 個成功
        $this->assertEquals(1, $result['failed_snapshots']); // 1 個失敗的
        $this->assertEquals(2, $result['retries']); // 第一個快照類型失敗 2 次

        $errors = $result['errors'] ?? [];
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors);
        $this->assertIsArray($errors[0] ?? null);
        if (isset($errors[0]['error']) && is_string($errors[0]['error'])) {
            $this->assertStringContainsString('持續失敗', $errors[0]['error']);
        }
    }

    /**
     * 測試快取清除失敗不影響主要流程.
     */
    public function testExecuteContinuesWhenCacheClearFails(): void
    {
        // Arrange
        $periods = ['daily'];

        // 只測試第一種快照類型
        $this->mockRepository
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->mockAggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsPeriod::class))
            ->andReturn(Mockery::mock(StatisticsSnapshot::class));

        $this->mockRepository
            ->shouldReceive('save')
            ->once();

        // 模擬快取清除失敗
        $this->mockCacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->andThrow(new RuntimeException('快取清除失敗'));

        // 模擬其他快照類型成功
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(3)  // 剩餘 3 種快照類型
            ->andReturn(true); // 跳過其他快照類型

        // Act
        $result = $this->command->execute($periods);

        // Assert
        $this->assertEquals(4, $result['total_snapshots']); // 4 種快照類型
        $this->assertEquals(4, $result['successful_snapshots']); // 全部成功
        $this->assertEquals(0, $result['failed_snapshots']);
    }

    /**
     * 測試無效週期參數拋出異常.
     */
    public function testExecuteThrowsExceptionForInvalidPeriods(): void
    {
        // Arrange
        $invalidPeriods = ['yearly', 'hourly'];

        // Expect
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('不支援的統計週期');

        // Act
        $this->command->execute($invalidPeriods);
    }

    /**
     * 測試空週期參數拋出異常.
     */
    public function testExecuteThrowsExceptionForEmptyPeriods(): void
    {
        // Expect
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('至少需要指定一個統計週期');

        // Act
        $this->command->execute([]);
    }

    /**
     * 測試獲取可用統計快照類型.
     */
    public function testGetAvailableSnapshotTypes(): void
    {
        // Act
        $types = $this->command->getAvailableSnapshotTypes();

        // Assert
        $expectedTypes = [
            StatisticsSnapshot::TYPE_OVERVIEW,
            StatisticsSnapshot::TYPE_POSTS,
            StatisticsSnapshot::TYPE_USERS,
            StatisticsSnapshot::TYPE_POPULAR,
        ];

        $this->assertEquals($expectedTypes, $types);
    }

    /**
     * 測試並行執行保護（模擬鎖定文件已存在）.
     */
    public function testExecuteThrowsExceptionWhenLockFileExists(): void
    {
        // Skip test if posix_kill function is not available (non-Unix environment)
        if (!function_exists('posix_kill')) {
            $this->markTestSkipped('posix_kill function not available');
        }

        // Arrange
        $lockFile = '/tmp/statistics_calculation_' . md5('daily') . '.lock';
        $lockData = [
            'pid' => getmypid(), // Use current process PID to ensure lock check fails
            'start_time' => time(),
            'periods' => ['daily'],
        ];

        file_put_contents($lockFile, json_encode($lockData));

        // Expect
        $this->expectException(RuntimeException::class);

        try {
            // Act
            $this->command->execute(['daily']);
        } finally {
            // Cleanup
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }

    /**
     * 測試執行完成後自動清理鎖定文件.
     */
    public function testExecuteAutomaticallyReleasesLockFile(): void
    {
        // Arrange
        $periods = ['daily'];
        $lockFile = '/tmp/statistics_calculation_' . md5('daily') . '.lock';

        // 設定基本模擬
        $this->mockRepository
            ->shouldReceive('exists')
            ->times(4)
            ->andReturn(true); // 跳過所有快照計算以簡化測試

        // Act
        $this->command->execute($periods);

        // Assert
        $this->assertFileDoesNotExist($lockFile, '執行完成後鎖定文件應該被自動清理');
    }

    /**
     * 測試執行異常時仍會清理鎖定文件.
     */
    public function testExecuteReleasesLockFileOnException(): void
    {
        // Arrange
        $periods = ['daily'];
        $lockFile = '/tmp/statistics_calculation_' . md5('daily') . '.lock';

        // 模擬在週期驗證階段拋出異常（這會導致整個 execute 拋出異常）
        $invalidPeriods = ['invalid_period'];

        // Act & Assert
        $exceptionThrown = false;

        try {
            $this->command->execute($invalidPeriods);
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('不支援的統計週期', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, '應該拋出異常');

        // 鎖定文件應該被清理
        $this->assertFileDoesNotExist($lockFile, '異常發生時鎖定文件應該被自動清理');
    }
}
