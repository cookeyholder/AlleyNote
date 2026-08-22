<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\TimeSeriesDataPoint;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 時間序列資料點值物件測試.
 */
final class TimeSeriesDataPointTest extends UnitTestCase
{
    public function testConstructAndSerializationWithoutLabel(): void
    {
        $point = new TimeSeriesDataPoint('2025-01-01', 123.45);

        $this->assertSame('2025-01-01', $point->timestamp);
        $this->assertSame(123.45, $point->value);
        $this->assertNull($point->label);

        $json = $point->jsonSerialize();
        $this->assertSame([
            'x' => '2025-01-01',
            'y' => 123.45,
        ], $json);
    }

    public function testConstructAndSerializationWithLabel(): void
    {
        $point = new TimeSeriesDataPoint('2025-01-01', 123.45, 'Point 1');

        $this->assertSame('Point 1', $point->label);
        $json = $point->jsonSerialize();
        $this->assertSame([
            'x'     => '2025-01-01',
            'y'     => 123.45,
            'label' => 'Point 1',
        ], $json);
    }

    public function testForDate(): void
    {
        $dt = new DateTimeImmutable('2025-06-15 14:30:00');
        $point = TimeSeriesDataPoint::forDate($dt, 50.0, 'Day Point');

        $this->assertSame('2025-06-15', $point->timestamp);
        $this->assertSame(50.0, $point->value);
        $this->assertSame('Day Point', $point->label);
    }

    public function testForMonth(): void
    {
        $dt = new DateTimeImmutable('2025-06-15 14:30:00');
        $point = TimeSeriesDataPoint::forMonth($dt, 800.0, 'Month Point');

        $this->assertSame('2025-06', $point->timestamp);
        $this->assertSame(800.0, $point->value);
        $this->assertSame('Month Point', $point->label);
    }

    public function testForHour(): void
    {
        $dt = new DateTimeImmutable('2025-06-15 14:30:00');
        $point = TimeSeriesDataPoint::forHour($dt, 22.0, 'Hour Point');

        $this->assertSame('2025-06-15 14:30', $point->timestamp);
        $this->assertSame(22.0, $point->value);
        $this->assertSame('Hour Point', $point->label);
    }
}
