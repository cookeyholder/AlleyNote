<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Security\Services\Core\XssProtectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

#[CoversClass(RichTextProcessorService::class)]
final class RichTextProcessorServiceTest extends UnitTestCase
{
    private RichTextProcessorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $xssProtection = $this->createMock(XssProtectionService::class);
        $xssProtection->method('detectXss')->willReturn(false);
        $this->service = new RichTextProcessorService($xssProtection);
    }

    #[Test]
    public function test_basic等級會淨化危險標籤(): void
    {
        $maliciousContent = '<script>alert("xss")</script><p>安全內容</p>';
        $result = $this->service->processContent($maliciousContent, 'basic');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringNotContainsString('<script>', $result['content']);
        $this->assertStringContainsString('<p>', $result['content']);
    }

    #[Test]
    public function test_extended等級允許表格標籤(): void
    {
        $content = '<table><tr><td>測試</td></tr></table>';
        $result = $this->service->processContent($content, 'extended');

        $this->assertIsArray($result);
        $this->assertStringContainsString('<table>', $result['content']);
    }

    #[Test]
    public function test_admin等級允許更多標籤(): void
    {
        $content = '<h1>標題</h1><table><tr><td>內容</td></tr></table><hr>';
        $result = $this->service->processContent($content, 'admin');

        $this->assertIsArray($result);
        $this->assertStringContainsString('<h1>', $result['content']);
        $this->assertStringContainsString('<table>', $result['content']);
        $this->assertStringContainsString('<hr>', $result['content']);
    }

    #[Test]
    public function test_所有等級都禁止script標籤(): void
    {
        $maliciousContent = '<script>alert("xss")</script>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            $result = $this->service->processContent($maliciousContent, $level);
            $this->assertStringNotContainsString(
                '<script>',
                $result['content'],
                "等級 {$level} 不應該允許 script 標籤"
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止iframe標籤(): void
    {
        $content = '<iframe src="https://evil.com"></iframe>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<iframe>',
                $result['content'],
                "等級 {$level} 不應該允許 iframe 標籤"
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止object標籤(): void
    {
        $content = '<object data="evil.swf"></object>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<object>',
                $result['content'],
                "等級 {$level} 不應該允許 object 標籤"
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止embed標籤(): void
    {
        $content = '<embed src="evil.swf">';

        foreach (['basic', 'extended', 'admin'] as $level) {
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<embed>',
                $result['content'],
                "等級 {$level} 不應該允許 embed 標籤"
            );
        }
    }

    #[Test]
    public function test_預設等級為basic(): void
    {
        $content = '<p>測試</p>';
        $result = $this->service->processContent($content);

        $this->assertIsArray($result);
        $this->assertStringContainsString('<p>', $result['content']);
    }

    #[Test]
    public function test_內容被修改時會產生警告(): void
    {
        $maliciousContent = '<script>alert("xss")</script><p>安全</p>';
        $result = $this->service->processContent($maliciousContent, 'basic');

        $this->assertArrayHasKey('warnings', $result);
        $this->assertNotEmpty($result['warnings']);
    }

    #[Test]
    public function test_安全內容不會產生警告(): void
    {
        $safeContent = '<p>這是一段安全的內容</p>';
        $result = $this->service->processContent($safeContent, 'basic');

        $this->assertArrayHasKey('warnings', $result);
        $this->assertEmpty($result['warnings']);
    }
}
