<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use App\Domains\Auth\ValueObjects\UserId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * UserId 值物件測試.
 */
class UserIdTest extends TestCase
{
    public function test_can_create_valid_user_id(): void
    {
        $userId = new UserId(1);

        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertEquals(1, $userId->getValue());
    }

    public function test_can_create_from_int(): void
    {
        $userId = UserId::fromInt(123);

        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertEquals(123, $userId->getValue());
    }

    public function test_can_create_from_string(): void
    {
        $userId = UserId::fromString('456');

        $this->assertInstanceOf(UserId::class, $userId);
        $this->assertEquals(456, $userId->getValue());
    }

    public function test_throws_exception_for_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('使用者 ID 必須是正整數');

        new UserId(0);
    }

    public function test_throws_exception_for_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('使用者 ID 必須是正整數');

        new UserId(-1);
    }

    public function test_throws_exception_for_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('使用者 ID 必須是數字');

        UserId::fromString('abc');
    }

    public function test_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('使用者 ID 必須是數字');

        UserId::fromString('');
    }

    public function test_can_check_equality(): void
    {
        $userId1 = new UserId(1);
        $userId2 = new UserId(1);
        $userId3 = new UserId(2);

        $this->assertTrue($userId1->equals($userId2));
        $this->assertFalse($userId1->equals($userId3));
    }

    public function test_can_convert_to_string(): void
    {
        $userId = new UserId(123);

        $this->assertEquals('123', $userId->toString());
        $this->assertEquals('123', (string) $userId);
    }

    public function test_can_json_serialize(): void
    {
        $userId = new UserId(123);

        $this->assertEquals('123', json_encode($userId));
    }

    public function test_can_convert_to_array(): void
    {
        $userId = new UserId(123);

        $array = $userId->toArray();
        $this->assertEquals(123, $array['user_id']);
    }
}
