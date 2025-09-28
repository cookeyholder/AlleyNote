<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * StatisticsPeriod 值物件單元測試.
 */
final class StatisticsPeriodTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_valid_parameters(): void
    {
        // Arrange
        $type = PeriodType::DAILY;
        $startTime = new DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new DateTimeImmutable('2025-09-21 23:59:59');
        $timezone = 'Asia/Taipei';

        // Act
        $period = new StatisticsPeriod($type, $startTime, $endTime, $timezone);

        // Assert
        $this->assertSame($type, $period->type);
        $this->assertEquals($startTime, $period->startTime);
        $this->assertEquals($endTime, $period->endTime);
        $this->assertSame($timezone, $period->timezone);
    }

    #[Test]
    public function it_throws_exception_when_start_time_is_after_end_time(): void
    {
        // Arrange
        $type = PeriodType::DAILY;
        $startTime = new DateTimeImmutable('2025-09-21 23:59:59');
        $endTime = new DateTimeImmutable('2025-09-21 00:00:00');

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start time must be before end time');

        // Act
        new StatisticsPeriod($type, $startTime, $endTime);
    }

    #[Test]
    public function it_throws_exception_with_invalid_timezone(): void
    {
        // Arrange
        $type = PeriodType::DAILY;
        $startTime = new DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new DateTimeImmutable('2025-09-21 23:59:59');
        $invalidTimezone = 'Invalid/Timezone';

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid timezone: {$invalidTimezone}");

        // Act
        new StatisticsPeriod($type, $startTime, $endTime, $invalidTimezone);
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        // Arrange
        $data = [
            'type' => 'daily',
            'start_time' => '2025-09-21T00:00:00+00:00',
            'end_time' => '2025-09-21T23:59:59+00:00',
            'timezone' => 'UTC',
        ];

        // Act
        $period = StatisticsPeriod::fromArray($data);

        // Assert
        $this->assertSame(PeriodType::DAILY, $period->type);
        $this->assertSame('UTC', $period->timezone);
    }

    #[Test]
    public function it_throws_exception_when_creating_from_incomplete_array(): void
    {
        // Arrange
        /** @var array{type: string} $data */
        $data = [
            'type' => 'daily',
            // 缺少 start_time 和 end_time
        ];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: type, start_time, end_time');

        // Act
        /** @phpstan-ignore-next-line */
        StatisticsPeriod::fromArray($data);
    }

    #[Test]
    public function it_can_create_daily_period(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-09-21 15:30:45');

        // Act
        $period = StatisticsPeriod::createDaily($date);

        // Assert
        $this->assertSame(PeriodType::DAILY, $period->type);
        $this->assertSame('2025-09-21 00:00:00', $period->startTime->format('Y-m-d H:i:s'));
        $this->assertSame('2025-09-21 23:59:59', $period->endTime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_create_weekly_period(): void
    {
        // Arrange - 使用週三
        $date = new DateTimeImmutable('2025-09-24'); // 週三

        // Act
        $period = StatisticsPeriod::createWeekly($date);

        // Assert
        $this->assertSame(PeriodType::WEEKLY, $period->type);
        $this->assertSame('2025-09-22 00:00:00', $period->startTime->format('Y-m-d H:i:s')); // 週一
        $this->assertSame('2025-09-28 23:59:59', $period->endTime->format('Y-m-d H:i:s')); // 週日
    }

    #[Test]
    public function it_can_create_monthly_period(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-09-15'); // 9月中

        // Act
        $period = StatisticsPeriod::createMonthly($date);

        // Assert
        $this->assertSame(PeriodType::MONTHLY, $period->type);
        $this->assertSame('2025-09-01 00:00:00', $period->startTime->format('Y-m-d H:i:s'));
        $this->assertSame('2025-09-30 23:59:59', $period->endTime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_create_yearly_period(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2025-09-21');

        // Act
        $period = StatisticsPeriod::createYearly($date);

        // Assert
        $this->assertSame(PeriodType::YEARLY, $period->type);
        $this->assertSame('2025-01-01 00:00:00', $period->startTime->format('Y-m-d H:i:s'));
        $this->assertSame('2025-12-31 23:59:59', $period->endTime->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_can_check_if_contains_date(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));
        $insideDate = new DateTimeImmutable('2025-09-21 12:00:00');
        $outsideDate = new DateTimeImmutable('2025-09-22 00:00:00');

        // Act & Assert
        $this->assertTrue($period->contains($insideDate));
        $this->assertFalse($period->contains($outsideDate));
    }

    #[Test]
    public function it_can_calculate_duration_in_seconds(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));

        // Act
        $duration = $period->getDurationInSeconds();

        // Assert
        $this->assertSame(86399, $duration); // 23:59:59 的秒數
    }

    #[Test]
    public function it_can_calculate_duration_in_days(): void
    {
        // Arrange
        $period = StatisticsPeriod::createWeekly(new DateTimeImmutable('2025-09-21'));

        // Act
        $duration = $period->getDurationInDays();

        // Assert
        $this->assertSame(7, $duration);
    }

    #[Test]
    public function it_can_format_period_strings(): void
    {
        // Arrange
        $dailyPeriod = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));
        $weeklyPeriod = StatisticsPeriod::createWeekly(new DateTimeImmutable('2025-09-21'));
        $monthlyPeriod = StatisticsPeriod::createMonthly(new DateTimeImmutable('2025-09-21'));
        $yearlyPeriod = StatisticsPeriod::createYearly(new DateTimeImmutable('2025-09-21'));

        // Act & Assert
        $this->assertSame('2025-09-21', $dailyPeriod->format());
        $this->assertStringContainsString('~', $weeklyPeriod->format());
        $this->assertSame('2025-09', $monthlyPeriod->format());
        $this->assertSame('2025', $yearlyPeriod->format());
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));

        // Act
        $array = $period->toArray();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('start_time', $array);
        $this->assertArrayHasKey('end_time', $array);
        $this->assertArrayHasKey('timezone', $array);
        $this->assertSame('daily', $array['type']);
    }

    #[Test]
    public function it_can_be_json_serialized(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));

        // Act
        $json = json_encode($period);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertSame('daily', $decoded['type']);
    }

    #[Test]
    public function it_can_check_equality(): void
    {
        // Arrange
        $period1 = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));
        $period2 = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));
        $period3 = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-22'));

        // Act & Assert
        $this->assertTrue($period1->equals($period2));
        $this->assertFalse($period1->equals($period3));
    }

    #[Test]
    public function it_can_be_converted_to_string(): void
    {
        // Arrange
        $period = StatisticsPeriod::createDaily(new DateTimeImmutable('2025-09-21'));

        // Act
        $string = (string) $period;

        // Assert
        $this->assertStringContainsString('daily', $string);
        $this->assertStringContainsString('2025-09-21', $string);
    }

    #[Test]
    public function it_throws_exception_for_invalid_daily_period_length(): void
    {
        // Arrange
        $type = PeriodType::DAILY;
        $startTime = new DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new DateTimeImmutable('2025-09-22 23:59:59'); // 2天，不是1天

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Daily period must be exactly 1 day');

        // Act
        new StatisticsPeriod($type, $startTime, $endTime);
    }

    #[Test]
    public function it_throws_exception_for_invalid_weekly_period_length(): void
    {
        // Arrange
        $type = PeriodType::WEEKLY;
        $startTime = new DateTimeImmutable('2025-09-21 00:00:00');
        $endTime = new DateTimeImmutable('2025-09-25 23:59:59'); // 5天，不是7天

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Weekly period must be exactly 7 days');

        // Act
        new StatisticsPeriod($type, $startTime, $endTime);
    }
}
