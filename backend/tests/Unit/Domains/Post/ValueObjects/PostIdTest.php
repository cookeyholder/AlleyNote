<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\PostId;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * PostId 值物件測試.
 */
final class PostIdTest extends UnitTestCase
{
    public function test_can_create_with_positive_int(): void
    {
        $postId = new PostId(1);

        $this->assertSame(1, $postId->getValue());
    }

    public function test_throws_exception_for_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是正整數');

        new PostId(0);
    }

    public function test_throws_exception_for_negative_int(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是正整數');

        new PostId(-5);
    }

    public function test_can_create_from_int(): void
    {
        $postId = PostId::fromInt(123);

        $this->assertSame(123, $postId->getValue());
    }

    public function test_can_create_from_numeric_string(): void
    {
        $postId = PostId::fromString('456');

        $this->assertSame(456, $postId->getValue());
    }

    public function test_from_string_throws_exception_for_non_numeric_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是數字');

        PostId::fromString('not-a-number');
    }

    public function test_equals_compares_values(): void
    {
        $a = PostId::fromInt(9);
        $b = PostId::fromString('9');
        $c = PostId::fromInt(10);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string_and_magic_to_string(): void
    {
        $postId = new PostId(88);

        $this->assertSame('88', $postId->toString());
        $this->assertSame('88', (string) $postId);
    }

    public function test_json_serialize_returns_int(): void
    {
        $postId = new PostId(64);

        $this->assertSame(64, $postId->jsonSerialize());
        $this->assertSame('64', json_encode($postId));
    }

    public function test_to_array_returns_post_id(): void
    {
        $postId = new PostId(32);

        $this->assertSame(['post_id' => 32], $postId->toArray());
    }
}
