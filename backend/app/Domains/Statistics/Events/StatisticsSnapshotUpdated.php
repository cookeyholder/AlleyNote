<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Events;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;

/**
 * 統計快照更新事件
 * 當統計快照被更新時觸發.
 */
readonly class StatisticsSnapshotUpdated
{
    public function __construct(
        public Uuid $id,
        public StatisticsPeriod $period,
        public StatisticsMetric $totalPosts,
        public StatisticsMetric $totalViews,
        public DateTimeImmutable $updatedAt
    ) {
    }

    /**
     * 取得事件名稱.
     */
    public function getEventName(): string
    {
        return 'statistics.snapshot.updated';
    }

    /**
     * 取得事件資料.
     */
    public function getEventData(): array
    {
        return [
            'id' => $this->id->toString(),
            'period' => $this->period->toArray(),
            'total_posts' => $this->totalPosts->toArray(),
            'total_views' => $this->totalViews->toArray(),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得事件發生時間.
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
