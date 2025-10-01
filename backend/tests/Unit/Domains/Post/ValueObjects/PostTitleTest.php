<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\PostTitle;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * PostTitle 值物件測試.
 */
class PostTitleTest extends TestCase
{
    public function test_can_create_valid_title(): void
    {
        $title = new PostTitle('Test Post Title');

        $this->assertInstanceOf(PostTitle::class, $title);
        $this->assertEquals('Test Post Title', $title->getValue());
    }

    public function test_can_create_from_string(): void
    {
        $title = PostTitle::fromString('Test Title');

        $this->assertInstanceOf(PostTitle::class, $title);
    }

    public function test_throws_exception_for_empty_title(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章標題不能為空');

        new PostTitle('');
    }

    public function test_throws_exception_for_too_long_title(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章標題長度不能超過 255 個字元');

        $longTitle = str_repeat('a', 256);
        new PostTitle($longTitle);
    }

    public function test_throws_exception_for_invalid_content(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章標題必須包含有效的字母或數字');

        new PostTitle('!!!');
    }

    public function test_can_get_length(): void
    {
        $title = new PostTitle('Test');

        $this->assertEquals(4, $title->getLength());
    }

    public function test_can_get_length_for_unicode(): void
    {
        $title = new PostTitle('測試標題');

        $this->assertEquals(4, $title->getLength());
    }

    public function test_can_truncate_long_title(): void
    {
        $title = new PostTitle('This is a very long title for testing');

        $truncated = $title->truncate(10);
        $this->assertEquals('This is a ...', $truncated);
    }

    public function test_does_not_truncate_short_title(): void
    {
        $title = new PostTitle('Short');

        $truncated = $title->truncate(10);
        $this->assertEquals('Short', $truncated);
    }

    public function test_can_check_equality(): void
    {
        $title1 = new PostTitle('Test Title');
        $title2 = new PostTitle('Test Title');
        $title3 = new PostTitle('Other Title');

        $this->assertTrue($title1->equals($title2));
        $this->assertFalse($title1->equals($title3));
    }

    public function test_can_convert_to_string(): void
    {
        $title = new PostTitle('Test Title');

        $this->assertEquals('Test Title', $title->toString());
        $this->assertEquals('Test Title', (string) $title);
    }

    public function test_can_json_serialize(): void
    {
        $title = new PostTitle('Test Title');

        $this->assertEquals('"Test Title"', json_encode($title));
    }

    public function test_can_convert_to_array(): void
    {
        $title = new PostTitle('Test Title');

        $array = $title->toArray();
        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals(10, $array['length']);
    }
}
