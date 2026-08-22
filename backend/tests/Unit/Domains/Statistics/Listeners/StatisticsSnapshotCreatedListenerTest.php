<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Listeners;

use App\Domains\Statistics\Contracts\SlowQueryMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Domains\Statistics\Listeners\StatisticsSnapshotCreatedListener;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use App\Shared\Events\Contracts\DomainEventInterface;
use DateTimeImmutable;
use Mockery;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * 統計快照建立監聽器測試.
 */
final class StatisticsSnapshotCreatedListenerTest extends UnitTestCase
{
    private StatisticsCacheServiceInterface $cacheService;

    private StatisticsMonitoringService $monitoringService;

    private SlowQueryMonitoringServiceInterface $slowQueryService;

    private LoggerInterface $logger;

    private StatisticsSnapshotCreatedListener $listener;

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('
            CREATE TABLE statistics_query_monitoring (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_type TEXT NOT NULL,
                execution_time REAL NOT NULL,
                status TEXT NOT NULL,
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->cacheService = Mockery::mock(StatisticsCacheServiceInterface::class);
        $this->slowQueryService = Mockery::mock(SlowQueryMonitoringServiceInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->byDefault();
        $this->logger->shouldReceive('warning')->byDefault();
        $this->logger->shouldReceive('error')->byDefault();

        $this->monitoringService = new StatisticsMonitoringService(
            $this->slowQueryService,
            $this->pdo,
            $this->logger,
        );

        $this->listener = new StatisticsSnapshotCreatedListener(
            $this->cacheService,
            $this->monitoringService,
            $this->logger,
        );
    }

    private function createSnapshot(string $type = 'overview'): StatisticsSnapshot
    {
        $period = new StatisticsPeriod(
            type: PeriodType::DAILY,
            startTime: new DateTimeImmutable('2025-01-01 00:00:00'),
            endTime: new DateTimeImmutable('2025-01-01 23:59:59'),
        );

        return StatisticsSnapshot::create(
            snapshotType: $type,
            period: $period,
            statisticsData: ['views' => 10],
            metadata: [],
        );
    }

    public function testMetadataMethods(): void
    {
        $this->assertSame(['statistics.snapshot.created'], $this->listener->getListenedEvents());
        $this->assertSame('statistics.snapshot_created_listener', $this->listener->getName());
    }

    public function testHandleWithNonSnapshotCreatedEvent(): void
    {
        $otherEvent = Mockery::mock(DomainEventInterface::class);
        $otherEvent->shouldReceive('getEventName')->andReturn('other.event');
        $otherEvent->shouldReceive('getEventId')->andReturn('evt-1');

        $this->logger->shouldReceive('warning')
            ->once()
            ->with('StatisticsSnapshotCreatedListener received non-StatisticsSnapshotCreated event', Mockery::type('array'));

        $this->listener->handle($otherEvent);
        $this->addToAssertionCount(1);
    }

    public function testHandleSuccessfullyForNewSnapshot(): void
    {
        $snapshot = $this->createSnapshot('overview');
        $event = StatisticsSnapshotCreated::forNewSnapshot($snapshot);

        $this->cacheService->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics', 'overview']);

        $this->listener->handle($event);

        // 驗證監控表記錄
        $stmt = $this->pdo->query("SELECT * FROM statistics_query_monitoring WHERE query_type = 'snapshot_created'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row);
    }

    public function testHandleSuccessfullyForSnapshotUpdateAndOtherTypes(): void
    {
        $types = ['posts', 'users', 'popular', 'sources'];
        $expectedTagsMap = [
            'posts'   => ['statistics', 'posts'],
            'users'   => ['statistics', 'users'],
            'popular' => ['statistics', 'popular', 'trends'],
            'sources' => ['statistics', 'sources'],
        ];

        foreach ($types as $type) {
            $snapshot = $this->createSnapshot($type);
            $event = StatisticsSnapshotCreated::forSnapshotUpdate($snapshot);

            $this->cacheService->shouldReceive('flushByTags')
                ->once()
                ->with($expectedTagsMap[$type]);

            $this->listener->handle($event);
        }
        $this->addToAssertionCount(4);
    }

    public function testHandleCatchesMonitoringServiceExceptionGracefully(): void
    {
        // 刪除資料表引發 monitoringService 寫入錯誤
        $this->pdo->exec('DROP TABLE statistics_query_monitoring');

        $snapshot = $this->createSnapshot('overview');
        $event = StatisticsSnapshotCreated::forNewSnapshot($snapshot);

        $this->cacheService->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics', 'overview']);

        $this->listener->handle($event);
        $this->addToAssertionCount(1);
    }

    public function testHandleCatchesCacheFlushExceptionGracefully(): void
    {
        $snapshot = $this->createSnapshot('overview');
        $event = StatisticsSnapshotCreated::forNewSnapshot($snapshot);

        $this->cacheService->shouldReceive('flushByTags')
            ->once()
            ->andThrow(new RuntimeException('Redis down'));

        $this->logger->shouldReceive('error')
            ->once()
            ->with('Failed to invalidate cache for snapshot', Mockery::type('array'));

        $this->listener->handle($event);
        $this->addToAssertionCount(1);
    }
}
