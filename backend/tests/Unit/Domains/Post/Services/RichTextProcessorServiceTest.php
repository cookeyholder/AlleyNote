<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Security\Enums\ActivitySeverity;
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
        /** @var array<string, string> $result */
        $result = $this->service->processContent($maliciousContent, 'basic');

        $this->assertArrayHasKey('content', $result);
        $this->assertStringNotContainsString('<script>', $result['content']);
        $this->assertStringContainsString('<p>', $result['content']);
    }

    #[Test]
    public function test_extended等級允許表格標籤(): void
    {
        $content = '<table><tr><td>測試</td></tr></table>';
        /** @var array<string, string> $result */
        $result = $this->service->processContent($content, 'extended');

        $this->assertStringContainsString('<table>', $result['content']);
    }

    #[Test]
    public function test_admin等級允許更多標籤(): void
    {
        $content = '<h1>標題</h1><table><tr><td>內容</td></tr></table><hr>';
        /** @var array<string, string> $result */
        $result = $this->service->processContent($content, 'admin');

        $this->assertStringContainsString('<h1>', $result['content']);
        $this->assertStringContainsString('<table>', $result['content']);
        $this->assertStringContainsString('<hr>', $result['content']);
    }

    #[Test]
    public function test_所有等級都禁止script標籤(): void
    {
        $maliciousContent = '<script>alert("xss")</script>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            /** @var array<string, string> $result */
            $result = $this->service->processContent($maliciousContent, $level);
            $this->assertStringNotContainsString(
                '<script>',
                $result['content'],
                "等級 {$level} 不應該允許 script 標籤",
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止iframe標籤(): void
    {
        $content = '<iframe src="https://evil.com"></iframe>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            /** @var array<string, string> $result */
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<iframe>',
                $result['content'],
                "等級 {$level} 不應該允許 iframe 標籤",
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止object標籤(): void
    {
        $content = '<object data="evil.swf"></object>';

        foreach (['basic', 'extended', 'admin'] as $level) {
            /** @var array<string, string> $result */
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<object>',
                $result['content'],
                "等級 {$level} 不應該允許 object 標籤",
            );
        }
    }

    #[Test]
    public function test_所有等級都禁止embed標籤(): void
    {
        $content = '<embed src="evil.swf">';

        foreach (['basic', 'extended', 'admin'] as $level) {
            /** @var array<string, string> $result */
            $result = $this->service->processContent($content, $level);
            $this->assertStringNotContainsString(
                '<embed>',
                $result['content'],
                "等級 {$level} 不應該允許 embed 標籤",
            );
        }
    }

    #[Test]
    public function test_預設等級為basic(): void
    {
        $content = '<p>測試</p>';
        /** @var array<string, string> $result */
        $result = $this->service->processContent($content);

        $this->assertStringContainsString('<p>', $result['content']);
    }

    #[Test]
    public function test_內容被修改時會產生警告(): void
    {
        $maliciousContent = '<script>alert("xss")</script><p>安全</p>';
        /** @var array<string, string> $result */
        $result = $this->service->processContent($maliciousContent, 'basic');

        $this->assertArrayHasKey('warnings', $result);
        $this->assertNotEmpty($result['warnings']);
    }

    #[Test]
    public function test_安全內容不會產生警告(): void
    {
        $safeContent = '<p>這是一段安全的內容</p>';
        /** @var array<string, string> $result */
        $result = $this->service->processContent($safeContent, 'basic');

        $this->assertArrayHasKey('warnings', $result);
        $this->assertEmpty($result['warnings']);
    }

    #[Test]
    public function test_processCKEditorContent移除CKEditor專屬屬性(): void
    {
        // 已知缺陷：preprocessCKEditorContent 的空段落移除規則 '/]*>(\s|&nbsp;)*/i'
        // 會誤刪第一個標籤的結尾 '>'，使後續淨化流程丟棄標籤內的文字。
        // 此測試僅驗證屬性確實被移除；內容損毀問題另行以專屬測試記錄。
        $content = '<p data-cke-pa-on-click="x" contenteditable="true" spellcheck="false">編輯器內容</p>';
        /** @var array<string, string> $result */
        $result = $this->service->processCKEditorContent($content);

        $this->assertStringNotContainsString('data-cke', $result['content']);
        $this->assertStringNotContainsString('contenteditable', $result['content']);
        $this->assertStringNotContainsString('spellcheck', $result['content']);
    }

    #[Test]
    public function test_processCKEditorContent空段落規則缺陷會破壞HTML內文(): void
    {
        // 記錄生產碼缺陷現狀：含標籤的內容經前置處理後文字遺失
        /** @var array<string, string> $result */
        $result = $this->service->processCKEditorContent('<p>編輯器內容</p>');

        $this->assertSame('<p></p>', $result['content']);
    }

    #[Test]
    public function test_processCKEditorContent正規化換行符號(): void
    {
        // 純文字不含標籤，可完整保留，用以驗證換行正規化與去除頭尾空白
        /** @var array<string, string> $result */
        $result = $this->service->processCKEditorContent("  第一行\r\n第二行\r第三行  ");

        $this->assertSame("第一行\n第二行\n第三行", $result['content']);
    }

    #[Test]
    public function test_getAllowedElements依層級回傳標籤清單(): void
    {
        /** @var array{tags: array<int, string>, attributes: array<int, string>} $basic */
        $basic = $this->service->getAllowedElements('basic');
        /** @var array{tags: array<int, string>, attributes: array<int, string>} $extended */
        $extended = $this->service->getAllowedElements('extended');
        /** @var array{tags: array<int, string>, attributes: array<int, string>} $admin */
        $admin = $this->service->getAllowedElements('admin');

        $this->assertContains('p', $basic['tags']);
        $this->assertNotContains('table', $basic['tags']);

        $this->assertContains('table', $extended['tags']);
        $this->assertContains('img', $extended['tags']);
        $this->assertNotContains('hr', $extended['tags']);

        $this->assertContains('hr', $admin['tags']);
        $this->assertContains('sub', $admin['tags']);
        $this->assertNotEmpty($admin['attributes']);
    }

    #[Test]
    public function test_generatePreview移除標籤並截斷長文字(): void
    {
        $longContent = '<p>' . str_repeat('字', 300) . '</p>';
        $preview = $this->makeService()->generatePreview($longContent, 200);

        $this->assertSame(203, mb_strlen($preview));
        $this->assertTrue(str_ends_with($preview, '...'));
        $this->assertFalse(str_contains($preview, '<p>'));
    }

    #[Test]
    public function test_generatePreview短內容原樣保留(): void
    {
        $preview = $this->makeService()->generatePreview('<p>短內容</p>', 200);

        $this->assertSame('短內容', $preview);
    }

    #[Test]
    public function test_validateSecurity偵測到XSS模式(): void
    {
        $service = $this->makeService(detectXss: true);

        $issues = $service->validateSecurity('<script>alert(1)</script>');

        $this->assertCount(1, $issues);
        /** @var array{type: string, severity: ActivitySeverity} $firstIssue */
        $firstIssue = $issues[0];
        $this->assertSame('xss_pattern', $firstIssue['type']);
        $this->assertSame(ActivitySeverity::HIGH, $firstIssue['severity']);
    }

    #[Test]
    public function test_validateSecurity偵測過長內容(): void
    {
        $issues = $this->service->validateSecurity(str_repeat('a', 100001));

        $types = array_column($issues, 'type');
        $this->assertContains('content_too_long', $types);
    }

    #[Test]
    public function test_validateSecurity偵測過多標籤(): void
    {
        $issues = $this->service->validateSecurity(str_repeat('<br>', 1001));

        $types = array_column($issues, 'type');
        $this->assertContains('too_many_tags', $types);
    }

    #[Test]
    public function test_validateSecurity安全內容無問題(): void
    {
        $issues = $this->service->validateSecurity('<p>正常內容</p>');

        $this->assertSame([], $issues);
    }

    /**
     * 以指定的 XSS 偵測行為建立服務實例.
     */
    private function makeService(bool $detectXss = false): RichTextProcessorService
    {
        $xssProtection = $this->createMock(XssProtectionService::class);
        $xssProtection->method('detectXss')->willReturn($detectXss);
        $xssProtection->method('clean')->willReturnCallback(static fn(string $input): string => $input);

        return new RichTextProcessorService($xssProtection);
    }
}
