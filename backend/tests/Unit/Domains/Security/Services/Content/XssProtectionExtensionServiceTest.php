<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Content;

use App\Domains\Post\Services\ContentModerationService;
use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Services\Content\XssProtectionExtensionService;
use App\Domains\Security\Services\Core\XssProtectionService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * XssProtectionExtensionService 測試.
 *
 * 使用真實的 XSS 防護與富文本處理富文本處理鏈，僅隔離活動記錄器。
 */
#[CoversClass(XssProtectionExtensionService::class)]
class XssProtectionExtensionServiceTest extends UnitTestCase
{
    /** @var ActivityLoggingServiceInterface&MockInterface */
    private ActivityLoggingServiceInterface $logger;

    private XssProtectionExtensionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->service = $this->makeService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 建立被測服務（可注入自訂設定）.
     *
     * @param array<string, mixed> $config
     */
    private function makeService(array $config = []): XssProtectionExtensionService
    {
        $xss = new XssProtectionService($this->logger);
        $richText = new RichTextProcessorService($xss);
        $moderator = new ContentModerationService($xss, $richText);

        return new XssProtectionExtensionService($xss, $richText, $moderator, $config);
    }

    #[Test]
    public function it_sanitizes_rich_text_editor_content_and_reports_changes(): void
    {
        $result = $this->service->protectByContext(
            '<p>Hello</p><script>alert(1)</script>',
            'rich_text_editor',
        );

        $this->assertSame('rich_text_editor', $this->str($result, 'context'));
        $this->assertSame('enhanced', $this->str($result, 'protection_level'));
        $this->assertStringNotContainsString('<script', $this->str($result, 'protected_content'));
        $this->assertContains('html_sanitization', $this->modificationTypes($result));
        $this->assertNotEmpty($this->warningTypes($result));
        $this->assertSame(30, $this->scoreOf($result));
    }

    #[Test]
    public function it_leaves_clean_rich_text_editor_content_untouched(): void
    {
        $result = $this->service->protectByContext('Hello world', 'rich_text_editor');

        $this->assertSame('Hello world', $this->str($result, 'protected_content'));
        $this->assertSame([], $this->modificationTypes($result));
        $this->assertSame([], $this->warningTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_restricts_user_bio_to_basic_tags(): void
    {
        $result = $this->service->protectByContext(
            '<b>Name</b><iframe src="https://evil.example"></iframe>',
            'user_bio',
        );

        $this->assertSame('Name', $this->str($result, 'protected_content'));
        $this->assertSame('strict', $this->str($result, 'protection_level'));
        $this->assertContains('tag_filtering', $this->modificationTypes($result));
        $this->assertSame([], $this->warningTypes($result));
    }

    #[Test]
    public function it_removes_all_html_from_post_title(): void
    {
        $result = $this->service->protectByContext('<h1>Important</h1>', 'post_title');

        $this->assertSame('Important', $this->str($result, 'protected_content'));
        $this->assertSame('maximum', $this->str($result, 'protection_level'));
        $this->assertContains('html_removal', $this->modificationTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_caps_post_title_length(): void
    {
        $result = $this->service->protectByContext(str_repeat('A', 260), 'post_title');

        $this->assertSame(200, mb_strlen($this->str($result, 'protected_content'), 'UTF-8'));
    }

    #[Test]
    public function it_honours_custom_title_length_config(): void
    {
        $custom = $this->makeService(['max_title_length' => 5]);

        $result = $custom->protectByContext(str_repeat('B', 20), 'post_title');

        $this->assertSame(5, mb_strlen($this->str($result, 'protected_content'), 'UTF-8'));
    }

    #[Test]
    public function it_passes_clean_post_content_through_moderation(): void
    {
        $content = 'Nice article about cats';

        $result = $this->service->protectByContext($content, 'post_content');

        $this->assertSame('enhanced', $this->str($result, 'protection_level'));
        $this->assertSame($content, $this->str($result, 'protected_content'));
        $this->assertSame([], $this->modificationTypes($result));
        $this->assertSame([], $this->warningTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_blocks_malicious_post_content(): void
    {
        $result = $this->service->protectByContext('<script>alert("xss")</script>', 'post_content');

        $this->assertSame('blocked', $this->str($result, 'protection_level'));
        $this->assertSame('', $this->str($result, 'protected_content'));
        $this->assertSame(0, $this->scoreOf($result));
        $this->assertContains('content_blocked', $this->modificationTypes($result));
        $this->assertContains('security_block', $this->warningTypes($result));
    }

    #[Test]
    public function it_filters_and_truncates_comments(): void
    {
        $longComment = str_repeat('<a href="https://example.test">link </a>', 60);

        $result = $this->service->protectByContext($longComment, 'comment');

        $this->assertSame('standard', $this->str($result, 'protection_level'));
        $this->assertSame(1003, mb_strlen($this->str($result, 'protected_content'), 'UTF-8'));
        $this->assertTrue(str_ends_with($this->str($result, 'protected_content'), '...'));
        $this->assertContains('html_filtering', $this->modificationTypes($result));
    }

    #[Test]
    public function it_strips_markup_from_search_queries(): void
    {
        $result = $this->service->protectByContext('<b>kitty</b>"cat"', 'search_query');

        $this->assertSame('kittycat', $this->str($result, 'protected_content'));
        $this->assertSame('maximum', $this->str($result, 'protection_level'));
        $this->assertContains('search_sanitization', $this->modificationTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_caps_search_query_length(): void
    {
        $result = $this->service->protectByContext(str_repeat('q', 150), 'search_query');

        $this->assertSame(100, mb_strlen($this->str($result, 'protected_content'), 'UTF-8'));
    }

    #[Test]
    public function it_encodes_url_parameters(): void
    {
        $result = $this->service->protectByContext('hello world&more', 'url_parameter');

        $this->assertSame('hello world&amp;more', $this->str($result, 'protected_content'));
        $this->assertSame('maximum', $this->str($result, 'protection_level'));
        $this->assertContains('url_encoding', $this->modificationTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_cleans_strings_inside_valid_json(): void
    {
        $result = $this->service->protectByContext('{"a":"<b>x</b>","n":1}', 'json_data');

        $this->assertSame('{"a":"x","n":1}', $this->str($result, 'protected_content'));
        $this->assertSame('enhanced', $this->str($result, 'protection_level'));
        $this->assertContains('json_sanitization', $this->modificationTypes($result));
    }

    #[Test]
    public function it_reports_no_modifications_for_clean_json(): void
    {
        $result = $this->service->protectByContext('{"k":"v"}', 'json_data');

        $this->assertSame('{"k":"v"}', $this->str($result, 'protected_content'));
        $this->assertSame([], $this->modificationTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    #[Test]
    public function it_blocks_invalid_json_payloads(): void
    {
        $result = $this->service->protectByContext('{bad json', 'json_data');

        $this->assertSame('blocked', $this->str($result, 'protection_level'));
        $this->assertSame('', $this->str($result, 'protected_content'));
        $this->assertSame(0, $this->scoreOf($result));
        $this->assertContains('invalid_json', $this->modificationTypes($result));
        $this->assertContains('json_error', $this->warningTypes($result));
    }

    #[Test]
    public function it_accepts_files_with_whitelisted_extensions(): void
    {
        $result = $this->service->protectByContext('ignored', 'file_upload', [
            'filename' => 'photo.JPG',
        ]);

        $this->assertSame('standard', $this->str($result, 'protection_level'));
        $this->assertSame('photo.JPG', $this->str($result, 'protected_content'));
        $this->assertSame([], $this->modificationTypes($result));
        $this->assertSame(90, $this->scoreOf($result));
    }

    #[Test]
    public function it_blocks_files_with_forbidden_extensions(): void
    {
        $result = $this->service->protectByContext('ignored', 'file_upload', [
            'filename' => 'malware.exe',
        ]);

        $this->assertSame('blocked', $this->str($result, 'protection_level'));
        $this->assertSame(0, $this->scoreOf($result));
        $this->assertContains('file_type_blocked', $this->modificationTypes($result));
        $this->assertStringContainsString('exe 不被允許', implode("\n", array_map(
            fn(array $warning): string => $this->toStr($warning['message'] ?? ''),
            $this->warningsOf($result),
        )));
    }

    #[Test]
    public function it_removes_path_traversal_from_filenames(): void
    {
        $result = $this->service->protectByContext('ignored', 'file_upload', [
            'filename' => '../../etc/passwd.txt',
        ]);

        $this->assertSame('....etcpasswd.txt', $this->str($result, 'protected_content'));
        $this->assertContains('filename_sanitization', $this->modificationTypes($result));
    }

    #[Test]
    public function it_falls_back_to_generic_protection_for_unknown_contexts(): void
    {
        $result = $this->service->protectByContext('plain text', 'unknown_context');

        $this->assertSame('generic', $this->str($result, 'context'));
        $this->assertSame('plain text', $this->str($result, 'protected_content'));
        $this->assertSame('standard', $this->str($result, 'protection_level'));
        $this->assertSame([], $this->modificationTypes($result));
        $this->assertSame(100, $this->scoreOf($result));
    }

    /**
     * 安全地取得結果中的字串欄位.
     *
     * @param array<array-key, mixed> $result 保護結果
     */
    private function str(array $result, string $key): string
    {
        return $this->toStr($result[$key] ?? '');
    }

    /**
     * 取得安全分數.
     *
     * @param array<array-key, mixed> $result 保護結果
     */
    private function scoreOf(array $result): int
    {
        return $this->toInt($result['security_score'] ?? 0);
    }

    /**
     * 取得所有修改類型.
     *
     * @param array<array-key, mixed> $result 保護結果
     *
     * @return array<int, mixed> 修改類型清單
     */
    private function modificationTypes(array $result): array
    {
        /** @var array<int, array<string, mixed>> $mods */
        $mods = $result['modifications'];

        /** @var array<int, mixed> */
        return array_column($mods, 'type');
    }

    /**
     * 取得所有警告類型.
     *
     * @param array<array-key, mixed> $result 保護結果
     *
     * @return array<int, mixed> 警告類型清單
     */
    private function warningTypes(array $result): array
    {
        /** @var array<int, array<string, mixed>> $warnings */
        $warnings = $result['warnings'];

        /** @var array<int, mixed> */
        return array_column($warnings, 'type');
    }

    /**
     * 取得警告原始陣列.
     *
     * @param array<array-key, mixed> $result 保護結果
     *
     * @return array<int, array<string, mixed>> 警告清單
     */
    private function warningsOf(array $result): array
    {
        /** @var array<int, array<string, mixed>> $warnings */
        $warnings = $result['warnings'];

        return $warnings;
    }

    /**
     * 安全地將混合型別轉為字串.
     */
    private function toStr(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : '';
    }

    /**
     * 安全地將混合型別轉為整數.
     */
    private function toInt(mixed $value): int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }
}
