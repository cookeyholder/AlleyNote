<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Shared\ValueObjects;

use App\Domains\Shared\ValueObjects\Timestamp;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Timestamp 值物件測試.
 */
class TimestampTest extends TestCase
{
    public function test_can_create_from_datetime(): void
    {
        $dateTime = new DateTimeImmutable('2024-01-01 12:00:00');
        $timestamp = new Timestamp($dateTime);

        $this->assertInstanceOf(Timestamp::class, $timestamp);
    }

    public function test_can_create_from_unix_timestamp(): void
    {
        $timestamp = new Timestamp(1704110400); // 2024-01-01 12:00:00 UTC

        $this->assertInstanceOf(Timestamp::class, $timestamp);
        $this->assertEquals(1704110400, $timestamp->toUnixTimestamp());
    }

    public function test_can_create_from_string(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00');

        $this->assertInstanceOf(Timestamp::class, $timestamp);
    }

    public function test_can_create_now(): void
    {
        $timestamp = Timestamp::now();

        $this->assertInstanceOf(Timestamp::class, $timestamp);
    }

    public function test_can_create_from_unix_timestamp_static(): void
    {
        $timestamp = Timestamp::fromUnixTimestamp(1704110400);

        $this->assertEquals(1704110400, $timestamp->toUnixTimestamp());
    }

    public function test_can_create_from_string_static(): void
    {
        $timestamp = Timestamp::fromString('2024-01-01 12:00:00');

        $this->assertInstanceOf(Timestamp::class, $timestamp);
    }

    public function test_throws_exception_for_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的時間戳記格式');

        new Timestamp('invalid-date');
    }

    public function test_can_format(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $formatted = $timestamp->format('Y-m-d H:i:s');
        $this->assertEquals('2024-01-01 12:00:00', $formatted);
    }

    public function test_can_convert_to_iso8601(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $iso = $timestamp->toIso8601();
        $this->assertStringContainsString('2024-01-01T12:00:00', $iso);
    }

    public function test_can_convert_to_rfc3339(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $rfc = $timestamp->toRfc3339();
        $this->assertStringContainsString('2024-01-01T12:00:00', $rfc);
    }

    public function test_can_add_seconds(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));
        $newTimestamp = $timestamp->addSeconds(30);

        $this->assertEquals('2024-01-01 12:00:30', $newTimestamp->format('Y-m-d H:i:s'));
    }

    public function test_can_add_minutes(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));
        $newTimestamp = $timestamp->addMinutes(5);

        $this->assertEquals('2024-01-01 12:05:00', $newTimestamp->format('Y-m-d H:i:s'));
    }

    public function test_can_add_hours(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));
        $newTimestamp = $timestamp->addHours(2);

        $this->assertEquals('2024-01-01 14:00:00', $newTimestamp->format('Y-m-d H:i:s'));
    }

    public function test_can_add_days(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));
        $newTimestamp = $timestamp->addDays(1);

        $this->assertEquals('2024-01-02 12:00:00', $newTimestamp->format('Y-m-d H:i:s'));
    }

    public function test_can_check_is_before(): void
    {
        $timestamp1 = new Timestamp('2024-01-01 12:00:00');
        $timestamp2 = new Timestamp('2024-01-02 12:00:00');

        $this->assertTrue($timestamp1->isBefore($timestamp2));
        $this->assertFalse($timestamp2->isBefore($timestamp1));
    }

    public function test_can_check_is_after(): void
    {
        $timestamp1 = new Timestamp('2024-01-02 12:00:00');
        $timestamp2 = new Timestamp('2024-01-01 12:00:00');

        $this->assertTrue($timestamp1->isAfter($timestamp2));
        $this->assertFalse($timestamp2->isAfter($timestamp1));
    }

    public function test_can_calculate_diff_in_seconds(): void
    {
        $timestamp1 = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));
        $timestamp2 = new Timestamp('2024-01-01 12:01:00', new DateTimeZone('UTC'));

        $this->assertEquals(60, $timestamp1->diffInSeconds($timestamp2));
    }

    public function test_can_check_equality(): void
    {
        $timestamp1 = new Timestamp(1704110400);
        $timestamp2 = new Timestamp(1704110400);
        $timestamp3 = new Timestamp(1704110401);

        $this->assertTrue($timestamp1->equals($timestamp2));
        $this->assertFalse($timestamp1->equals($timestamp3));
    }

    public function test_can_get_date_string(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00');

        $this->assertEquals('2024-01-01', $timestamp->toDateString());
    }

    public function test_can_get_time_string(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $this->assertEquals('12:00:00', $timestamp->toTimeString());
    }

    public function test_can_convert_to_string(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $this->assertEquals('2024-01-01 12:00:00', $timestamp->toString());
        $this->assertEquals('2024-01-01 12:00:00', (string) $timestamp);
    }

    public function test_can_json_serialize(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $json = json_encode($timestamp);
        $this->assertNotFalse($json);
        $this->assertStringContainsString('2024-01-01T12:00:00', $json);
    }

    public function test_can_convert_to_array(): void
    {
        $timestamp = new Timestamp('2024-01-01 12:00:00', new DateTimeZone('UTC'));

        $array = $timestamp->toArray();
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('iso8601', $array);
        $this->assertArrayHasKey('formatted', $array);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('time', $array);
    }
}
