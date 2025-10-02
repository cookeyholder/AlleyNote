<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\PostSlug;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PostSlugTest extends TestCase
{
    public function test_can_create_valid_post_slug(): void
    {
        $slug = new PostSlug('valid-slug');

        $this->assertInstanceOf(PostSlug::class, $slug);
        $this->assertEquals('valid-slug', $slug->getValue());
    }

    public function test_can_create_from_string(): void
    {
        $slug = PostSlug::fromString('another-slug');

        $this->assertInstanceOf(PostSlug::class, $slug);
    }

    public function test_can_create_from_title(): void
    {
        $slug = PostSlug::fromTitle('This Is A Title');

        $this->assertEquals('this-is-a-title', $slug->getValue());
    }

    public function test_from_title_handles_special_characters(): void
    {
        $slug = PostSlug::fromTitle('Title with @#$ special chars!');

        $this->assertEquals('title-with-special-chars', $slug->getValue());
    }

    public function test_throws_exception_for_empty_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug 不能為空');

        new PostSlug('');
    }

    public function test_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug 只能包含小寫字母、數字和連字號');

        new PostSlug('Invalid_Slug!');
    }

    public function test_can_get_length(): void
    {
        $slug = new PostSlug('test-slug');

        $this->assertEquals(9, $slug->getLength());
    }

    public function test_can_check_equality(): void
    {
        $slug1 = new PostSlug('same-slug');
        $slug2 = new PostSlug('same-slug');
        $slug3 = new PostSlug('different-slug');

        $this->assertTrue($slug1->equals($slug2));
        $this->assertFalse($slug1->equals($slug3));
    }

    public function test_can_convert_to_string(): void
    {
        $slug = new PostSlug('test-slug');

        $this->assertEquals('test-slug', $slug->toString());
        $this->assertEquals('test-slug', (string) $slug);
    }

    public function test_can_json_serialize(): void
    {
        $slug = new PostSlug('json-slug');

        $this->assertEquals('"json-slug"', json_encode($slug));
    }
}
