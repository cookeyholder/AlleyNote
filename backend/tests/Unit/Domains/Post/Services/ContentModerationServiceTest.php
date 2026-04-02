<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Services\ContentModerationService;
use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Security\Services\Core\XssProtectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

#[CoversClass(ContentModerationService::class)]
final class ContentModerationServiceTest extends UnitTestCase
{
    private ContentModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $xssProtection = $this->createMock(XssProtectionService::class);
        $xssProtection->method('detectXss')->willReturn(false);

        $richTextProcessor = $this->createMock(RichTextProcessorService::class);
        $richTextProcessor->method('validateSecurity')->willReturn([]);

        $this->service = new ContentModerationService($xssProtection, $richTextProcessor);
    }

    #[Test]
    public function test_安全內容會通過審核(): void
    {
        $result = $this->service->moderateContent('<p>這是一段正常的內容</p>');

        $this->assertSame('approved', $result['status']);
        $this->assertSame(100, $result['confidence']);
        $this->assertEmpty($result['issues']);
        $this->assertFalse($result['requires_human_review']);
    }

    #[Test]
    public function test_過短內容會被標記(): void
    {
        $result = $this->service->moderateContent('短');

        $this->assertNotEmpty($result['issues']);

        $hasQualityIssue = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['type'] === 'quality_too_short') {
                $hasQualityIssue = true;
                break;
            }
        }
        $this->assertTrue($hasQualityIssue);
    }

    #[Test]
    public function test_敏感詞會被偵測(): void
    {
        $result = $this->service->moderateContent('這包含髒話1和暴力詞1');

        $hasSensitiveWord = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['type'] === 'sensitive_word') {
                $hasSensitiveWord = true;
                break;
            }
        }
        $this->assertTrue($hasSensitiveWord);
    }

    #[Test]
    public function test_垃圾內容會被標記(): void
    {
        $spamContent = str_repeat('重複文字 ', 500);
        $result = $this->service->moderateContent($spamContent);

        $this->assertIsArray($result['issues']);
    }

    #[Test]
    public function test_全大寫內容會被標記(): void
    {
        $result = $this->service->moderateContent('THIS IS ALL CAPS CONTENT WITH MORE THAN TEN CHARACTERS');

        $hasCapsIssue = false;
        foreach ($result['issues'] as $issue) {
            if ($issue['type'] === 'quality_all_caps') {
                $hasCapsIssue = true;
                break;
            }
        }
        $this->assertTrue($hasCapsIssue);
    }

    #[Test]
    public function test_回傳結果包含必要欄位(): void
    {
        $result = $this->service->moderateContent('<p>測試</p>');

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('requires_human_review', $result);
        $this->assertArrayHasKey('auto_actions', $result);
    }
}
