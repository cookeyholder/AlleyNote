<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\ValueObjects;

use App\Domains\Post\ValueObjects\PostContent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PostContentTest extends TestCase
{
    public function test_can_create_valid_post_content(): void
    {
        $content = new PostContent('This is a valid post content');

        $this->assertInstanceOf(PostContent::class, $content);
        $this->assertEquals('This is a valid post content', $content->getValue());
    }

    public function test_can_create_from_string(): void
    {
        $content = PostContent::fromString('Another content');

        $this->assertInstanceOf(PostContent::class, $content);
    }

    public function test_throws_exception_for_empty_content(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章內容不能為空');

        new PostContent('');
    }

    public function test_throws_exception_for_whitespace_only_content(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章內容不能為空');

        new PostContent('   ');
    }

    public function test_trims_content(): void
    {
        $content = new PostContent('  Content with spaces  ');

        $this->assertEquals('Content with spaces', $content->getValue());
    }

    public function test_can_get_content_length(): void
    {
        $content = new PostContent('Hello World');

        $this->assertEquals(11, $content->getLength());
    }

    public function test_can_get_excerpt(): void
    {
        $longContent = str_repeat('a', 300);
        $content = new PostContent($longContent);

        $excerpt = $content->getExcerpt(100);
        $this->assertEquals(103, mb_strlen($excerpt)); // 100 + '...'
        $this->assertStringEndsWith('...', $excerpt);
    }

    public function test_excerpt_returns_full_content_if_shorter(): void
    {
        $shortContent = 'Short content';
        $content = new PostContent($shortContent);

        $excerpt = $content->getExcerpt(100);
        $this->assertEquals($shortContent, $excerpt);
    }

    public function test_can_check_if_contains_text(): void
    {
        $content = new PostContent('This is a test content');

        $this->assertTrue($content->contains('test'));
        $this->assertFalse($content->contains('missing'));
    }

    public function test_can_get_word_count(): void
    {
        $content = new PostContent('One two three four five');

        $this->assertEquals(5, $content->getWordCount());
    }

    public function test_can_check_if_empty(): void
    {
        $content = new PostContent('Not empty');

        $this->assertFalse($content->isEmpty());
    }

    public function test_can_check_equality(): void
    {
        $content1 = new PostContent('Same content');
        $content2 = new PostContent('Same content');
        $content3 = new PostContent('Different content');

        $this->assertTrue($content1->equals($content2));
        $this->assertFalse($content1->equals($content3));
    }

    public function test_can_convert_to_string(): void
    {
        $content = new PostContent('Test content');

        $this->assertEquals('Test content', $content->toString());
        $this->assertEquals('Test content', (string) $content);
    }

    public function test_can_json_serialize(): void
    {
        $content = new PostContent('JSON content');

        $this->assertEquals('"JSON content"', json_encode($content));
    }

    public function test_can_convert_to_array(): void
    {
        $content = new PostContent('Array content');

        $array = $content->toArray();
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('length', $array);
        $this->assertArrayHasKey('word_count', $array);
        $this->assertArrayHasKey('excerpt', $array);
    }
}
