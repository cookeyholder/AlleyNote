<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use App\Infrastructure\Services\OutputSanitizerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\SecureDDDTestCase;

/**
 * @covers \App\Infrastructure\Services\OutputSanitizerService
 */
class OutputSanitizerServiceTest extends SecureDDDTestCase
{
    private OutputSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new OutputSanitizerService();
    }

    #[Test]
    public function sanitizeHtmlEscapesAllTags(): void
    {
        $input = '<b>Bold</b><script>alert(1)</script>';
        $expected = '&lt;b&gt;Bold&lt;/b&gt;&lt;script&gt;alert(1)&lt;/script&gt;';

        $this->assertEquals($expected, $this->sanitizer->sanitizeHtml($input));
    }

    #[Test]
    public function sanitizeRichTextPreservesAllowedTags(): void
    {
        $input = '<h1>Title</h1><p>Para</p><strong>Bold</strong><script>evil()</script>';
        $result = $this->sanitizer->sanitizeRichText($input);

        $this->assertStringContainsString('<h1>Title</h1>', $result);
        $this->assertStringContainsString('<p>Para</p>', $result);
        $this->assertStringContainsString('<strong>Bold</strong>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    #[Test]
    public function sanitizeRichTextRemovesEvilAttributes(): void
    {
        $input = '<p onclick="alert(1)" class="safe">Text</p><a href="javascript:alert(1)">Link</a>';
        $result = $this->sanitizer->sanitizeRichText($input);

        $this->assertStringContainsString('<p class="safe">Text</p>', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    #[Test]
    public function sanitizeAndTruncateWorksCorrectly(): void
    {
        $input = 'Hello <b>World</b>';
        $result = $this->sanitizer->sanitizeAndTruncate($input, 5);

        $this->assertEquals('Hello...', $result);
    }
}
