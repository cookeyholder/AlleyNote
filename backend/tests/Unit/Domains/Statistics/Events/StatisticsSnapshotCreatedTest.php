<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Events;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 統計快照建立事件測試.
 */
final class StatisticsSnapshotCreatedTest extends UnitTestCase
{
    private function createSnapshot(): StatisticsSnapshot
    {
        $period = new StatisticsPeriod(
            type: PeriodType::DAILY,
            startTime: new DateTimeImmutable('2025-01-01 00:00:00'),
            endTime: new DateTimeImmutable('2025-01-01 23:59:59'),
        );

        return StatisticsSnapshot::create(
            snapshotType: 'overview',
            period: $period,
            statisticsData: ['views' => 100],
            metadata: [],
        );
    }

    public function testConstructAndGetters(): void
    {
        $snapshot = $this->createSnapshot();
        $event = new StatisticsSnapshotCreated($snapshot, false);

        $this->assertSame('statistics.snapshot.created', $event->getEventName());
        $this->assertSame($snapshot, $event->getSnapshot());
        $this->assertFalse($event->isUpdate());
        $this->assertSame('overview', $event->getSnapshotType());
        $this->assertSame($snapshot->getId(), $event->getSnapshotId());
        $this->assertSame($snapshot->getUuid(), $event->getSnapshotUuid());

        $eventData = $event->getEventData();
        $this->assertSame($snapshot->getId(), $eventData['snapshot_id']);
        $this->assertSame($snapshot->getUuid(), $eventData['snapshot_uuid']);
        $this->assertSame('overview', $eventData['snapshot_type']);
        $this->assertSame('daily', $eventData['period_type']);
        $this->assertSame('2025-01-01 00:00:00', $eventData['period_start']);
        $this->assertSame('2025-01-01 23:59:59', $eventData['period_end']);
        $this->assertSame(1, $eventData['data_count']);
        $this->assertFalse($eventData['is_update']);
    }

    public function testFactoryMethods(): void
    {
        $snapshot = $this->createSnapshot();

        $newEvent = StatisticsSnapshotCreated::forNewSnapshot($snapshot);
        $this->assertFalse($newEvent->isUpdate());

        $updateEvent = StatisticsSnapshotCreated::forSnapshotUpdate($snapshot);
        $this->assertTrue($updateEvent->isUpdate());
    }
}
