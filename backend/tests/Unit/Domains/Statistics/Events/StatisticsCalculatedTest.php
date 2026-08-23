<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Events;

use App\Domains\Statistics\Events\StatisticsCalculated;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 統計計算完成事件測試.
 */
final class StatisticsCalculatedTest extends UnitTestCase
{
    public function testEventPropertiesAndSerialization(): void
    {
        $calculatedAt = new DateTimeImmutable('2025-01-01 12:00:00');
        $data = ['total_views' => 100];

        $event = new StatisticsCalculated(
            statisticsType: 'daily',
            period: '2025-01-01',
            calculatedAt: $calculatedAt,
            statisticsData: $data,
            recordCount: 50,
            calculationTimeMs: 12.34,
        );

        $this->assertSame('statistics.calculated', $event->getEventName());
        $this->assertSame('daily', $event->statisticsType);
        $this->assertSame('2025-01-01', $event->period);
        $this->assertSame($calculatedAt, $event->calculatedAt);
        $this->assertSame($data, $event->statisticsData);
        $this->assertSame(50, $event->recordCount);
        $this->assertSame(12.34, $event->calculationTimeMs);

        $eventData = $event->getEventData();
        $this->assertSame([
            'statistics_type'     => 'daily',
            'period'              => '2025-01-01',
            'calculated_at'       => '2025-01-01 12:00:00',
            'statistics_data'     => $data,
            'record_count'        => 50,
            'calculation_time_ms' => 12.34,
        ], $eventData);
    }
}
