<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

/**
 * 統計數據已計算事件.
 *
 * 當統計數據計算完成時觸發
 */
final class StatisticsCalculated extends AbstractDomainEvent
{
    /**
     * @param array<string, mixed> $statisticsData 統計數據
     */
    public function __construct(
        public readonly string $statisticsType,
        public readonly string $period,
        public readonly DateTimeImmutable $calculatedAt,
        public readonly array $statisticsData,
        public readonly int $recordCount,
        public readonly float $calculationTimeMs,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'statistics.calculated';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'statistics_type' => $this->statisticsType,
            'period' => $this->period,
            'calculated_at' => $this->calculatedAt->format('Y-m-d H:i:s'),
            'statistics_data' => $this->statisticsData,
            'record_count' => $this->recordCount,
            'calculation_time_ms' => $this->calculationTimeMs,
        ];
    }
}
