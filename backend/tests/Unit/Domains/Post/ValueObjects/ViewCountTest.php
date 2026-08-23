<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\ViewCount;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * ViewCount 值物件測試.
 */
final class ViewCountTest extends UnitTestCase
{
    public function test_can_create_with_valid_count(): void
    {
        $viewCount = new ViewCount(100);

        $this->assertSame(100, $viewCount->getValue());
    }

    public function test_throws_exception_for_negative_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('瀏覽次數不能為負數');

        new ViewCount(-1);
    }

    public function test_can_create_from_int(): void
    {
        $viewCount = ViewCount::fromInt(25);

        $this->assertSame(25, $viewCount->getValue());
    }

    public function test_can_create_zero(): void
    {
        $viewCount = ViewCount::zero();

        $this->assertSame(0, $viewCount->getValue());
        $this->assertTrue($viewCount->isZero());
    }

    public function test_is_zero_returns_false_for_positive_count(): void
    {
        $this->assertFalse(new ViewCount(3)->isZero());
    }

    public function test_increment_adds_one_by_default(): void
    {
        $viewCount = new ViewCount(10)->increment();

        $this->assertSame(11, $viewCount->getValue());
    }

    public function test_increment_with_custom_amount(): void
    {
        $viewCount = new ViewCount(10)->increment(50);

        $this->assertSame(60, $viewCount->getValue());
    }

    public function test_increment_throws_exception_for_invalid_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('增加數量必須至少為 1');

        new ViewCount(10)->increment(0);
    }

    public function test_is_greater_than(): void
    {
        $viewCount = new ViewCount(20);

        $this->assertTrue($viewCount->isGreaterThan(19));
        $this->assertFalse($viewCount->isGreaterThan(20));
    }

    public function test_is_less_than(): void
    {
        $viewCount = new ViewCount(20);

        $this->assertTrue($viewCount->isLessThan(21));
        $this->assertFalse($viewCount->isLessThan(20));
    }

    public function test_equals_compares_values(): void
    {
        $a = new ViewCount(9);
        $b = new ViewCount(9);
        $c = new ViewCount(10);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_format_for_small_numbers(): void
    {
        $this->assertSame('999', new ViewCount(999)->format());
    }

    public function test_format_for_thousands(): void
    {
        $this->assertSame('1.2K', new ViewCount(1200)->format());
    }

    public function test_format_for_millions(): void
    {
        $this->assertSame('1.5M', new ViewCount(1500000)->format());
    }

    public function test_to_string_and_magic_to_string(): void
    {
        $viewCount = new ViewCount(42);

        $this->assertSame('42', $viewCount->toString());
        $this->assertSame('42', (string) $viewCount);
    }

    public function test_json_serialize_returns_int(): void
    {
        $viewCount = new ViewCount(77);

        $this->assertSame(77, $viewCount->jsonSerialize());
        $this->assertSame('77', json_encode($viewCount));
    }

    public function test_to_array_returns_views_and_formatted(): void
    {
        $viewCount = new ViewCount(2500);

        $this->assertSame([
            'views'     => 2500,
            'formatted' => '2.5K',
        ], $viewCount->toArray());
    }
}
