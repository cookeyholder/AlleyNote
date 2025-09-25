<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Events;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Shared\Events\AbstractDomainEvent;

/**
 * 統計快照已建立事件.
 *
 * 當統計快照成功建立時觸發，用於觸發快取失效和預熱操作
 */
class StatisticsSnapshotCreated extends AbstractDomainEvent
{
    public function __construct(
        private readonly StatisticsSnapshot $snapshot,
        private readonly bool $isUpdate = false,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'statistics.snapshot.created';
    }

    public function getEventData(): array
    {
        return [
            'snapshot_id' => $this->snapshot->getId(),
            'snapshot_uuid' => $this->snapshot->getUuid(),
            'snapshot_type' => $this->snapshot->getSnapshotType(),
            'period_type' => $this->snapshot->getPeriod()->type->value,
            'period_start' => $this->snapshot->getPeriod()->startTime->format('Y-m-d H:i:s'),
            'period_end' => $this->snapshot->getPeriod()->endTime->format('Y-m-d H:i:s'),
            'data_count' => count($this->snapshot->getStatisticsData()),
            'is_update' => $this->isUpdate,
            'created_at' => $this->snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->snapshot->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public function getSnapshot(): StatisticsSnapshot
    {
        return $this->snapshot;
    }

    public function isUpdate(): bool
    {
        return $this->isUpdate;
    }

    public function getSnapshotType(): string
    {
        return $this->snapshot->getSnapshotType();
    }

    public function getSnapshotId(): int
    {
        return $this->snapshot->getId();
    }

    public function getSnapshotUuid(): string
    {
        return $this->snapshot->getUuid();
    }

    /**
     * 建立新快照事件.
     */
    public static function forNewSnapshot(StatisticsSnapshot $snapshot): self
    {
        return new self($snapshot, false);
    }

    /**
     * 建立快照更新事件.
     */
    public static function forSnapshotUpdate(StatisticsSnapshot $snapshot): self
    {
        return new self($snapshot, true);
    }
}
