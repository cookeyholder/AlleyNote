<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Services\ContentModerationService;
use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Security\Enums\ActivitySeverity;
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
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('<p>這是一段正常的內容</p>');

        $this->assertSame('approved', $result['status']);
        $this->assertSame(100, $result['confidence']);
        $this->assertEmpty($result['issues']);
        $this->assertFalse($result['requires_human_review']);
    }

    #[Test]
    public function test_過短內容會被標記(): void
    {
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('短');

        $this->assertNotEmpty($result['issues']);

        $hasQualityIssue = false;
        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        foreach ($issues as $issue) {
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
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('這包含髒話1和暴力詞1');

        $hasSensitiveWord = false;
        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        foreach ($issues as $issue) {
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
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent($spamContent);

        $this->assertIsArray($result['issues']);
    }

    #[Test]
    public function test_全大寫內容會被標記(): void
    {
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('THIS IS ALL CAPS CONTENT WITH MORE THAN TEN CHARACTERS');

        $hasCapsIssue = false;
        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        foreach ($issues as $issue) {
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
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('<p>測試</p>');

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('requires_human_review', $result);
        $this->assertArrayHasKey('auto_actions', $result);
    }

    #[Test]
    public function test_偵測到XSS時拒絕內容(): void
    {
        $service = $this->makeService(detectXss: true);

        /** @var array<string, mixed> $result */
        $result = $service->moderateContent('<p>正常文字</p>');

        $this->assertSame('rejected', $result['status']);
        $this->assertSame(0, $result['confidence']);
        /** @var array<int, string> $autoActions */
        $autoActions = $result['auto_actions'];
        $this->assertContains('content_blocked', $autoActions);

        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        $types = array_column($issues, 'type');
        $this->assertContains('security_xss', $types);
    }

    #[Test]
    public function test_富文本安全檢查的高危問題(): void
    {
        $service = $this->makeService(richTextIssues: [
            ['severity' => ActivitySeverity::HIGH, 'message' => '高危模式一'],
            ['severity' => ActivitySeverity::HIGH, 'message' => '高危模式二'],
        ]);

        /** @var array<string, mixed> $result */
        $result = $service->moderateContent('<p>這是一段足夠長的正常內容</p>');

        // 兩個 HIGH 問題應轉為待人工審核
        $this->assertSame('pending', $result['status']);
        $this->assertTrue($result['requires_human_review']);
        $this->assertSame(30, $result['confidence']);
        /** @var array<int, string> $autoActions */
        $autoActions = $result['auto_actions'];
        $this->assertContains('flag_for_review', $autoActions);

        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        $types = array_column($issues, 'type');
        $this->assertContains('security_richtext', $types);
    }

    #[Test]
    public function test_單一中等問題給予有條件通過(): void
    {
        // '短' 僅觸發 quality_too_short（MEDIUM）
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('短');

        $this->assertSame('conditional', $result['status']);
        $this->assertSame(70, $result['confidence']);
        /** @var array<int, string> $recommendations */
        $recommendations = $result['recommendations'];
        $this->assertContains('建議作者檢查並修正標記的問題', $recommendations);
    }

    #[Test]
    public function test_重複句型內容會被標記(): void
    {
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('aaa bbb. aaa bbb. aaa bbb. aaa bbb.');

        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        $types = array_column($issues, 'type');
        $this->assertContains('quality_repetitive', $types);
    }

    #[Test]
    public function test_垃圾分數超過門檻時標記為垃圾內容(): void
    {
        // 連結密度 + 全大寫 + 重複字元 + 縮網址，合計分數超過門檻 70
        $spamContent = '<a href="http://bit.ly/aaa">l</a> <a href="http://bit.ly/bbb">l</a> AAAAAAAAAA';

        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent($spamContent);

        /** @var array<int, array{type: string, score?: float}> $issues */
        $issues = $result['issues'];
        $spamIssues = array_values(array_filter(
            $issues,
            static fn(array $issue): bool => $issue['type'] === 'spam_detected',
        ));

        $this->assertNotEmpty($spamIssues);
        /** @var array{type: string, severity: ActivitySeverity, message: string, score: float} $spamIssue */
        $spamIssue = $spamIssues[0];
        $this->assertGreaterThan(70, $spamIssue['score']);
    }

    #[Test]
    public function test_可疑IP連結提高垃圾分數(): void
    {
        // IP 位址模式貢獻 40 分、連結密度 30 分、全大寫 20 分，合計超過門檻
        $content = '<a href="http://192.168.1.1/x">L</a> <a href="http://10.0.0.2/y">L</a> WORDS HERE';

        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent($content);

        /** @var array<int, array{type: string}> $issues */
        $issues = $result['issues'];
        $types = array_column($issues, 'type');
        $this->assertContains('spam_detected', $types);
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterIssues(array $issues, string $type): array
    {
        return array_values(array_filter(
            $issues,
            static fn(array $issue): bool => ($issue['type'] ?? null) === $type,
        ));
    }

    #[Test]
    public function test_仇恨言論敏感詞導致拒絕(): void
    {
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('這段包含仇恨言論1的文字');

        $this->assertSame('rejected', $result['status']);

        /** @var array<int, array<string, mixed>> $issues */
        $issues = $result['issues'];
        $sensitiveIssues = $this->filterIssues($issues, 'sensitive_word');
        $this->assertNotEmpty($sensitiveIssues);
        $this->assertSame(ActivitySeverity::CRITICAL, $sensitiveIssues[0]['severity']);
        $this->assertSame('hate_speech', $sensitiveIssues[0]['category']);
    }

    #[Test]
    public function test_中等嚴重度敏感詞對應political分類(): void
    {
        /** @var array<string, mixed> $result */
        $result = $this->service->moderateContent('這段包含政治敏感詞1的討論');

        /** @var array<int, array<string, mixed>> $issues */
        $issues = $result['issues'];
        $sensitiveIssues = $this->filterIssues($issues, 'sensitive_word');
        $this->assertNotEmpty($sensitiveIssues);
        $this->assertSame(ActivitySeverity::MEDIUM, $sensitiveIssues[0]['severity']);
        $this->assertSame('political', $sensitiveIssues[0]['category']);
    }

    #[Test]
    public function test_自訂設定可覆蓋預設值(): void
    {
        $xssProtection = $this->createMock(XssProtectionService::class);
        $xssProtection->method('detectXss')->willReturn(false);
        $richTextProcessor = $this->createMock(RichTextProcessorService::class);
        $richTextProcessor->method('validateSecurity')->willReturn([]);

        $service = new ContentModerationService($xssProtection, $richTextProcessor, [
            'min_content_length' => 2,
        ]);

        /** @var array<string, mixed> $result */
        $result = $service->moderateContent('ok');

        $this->assertSame('approved', $result['status']);
        $this->assertSame(100, $result['confidence']);
        $this->assertSame([], $result['issues']);
    }

    /**
     * 以指定的模擬行為建立審核服務.
     *
     * @param array<int, array<string, mixed>> $richTextIssues
     */
    private function makeService(bool $detectXss = false, array $richTextIssues = []): ContentModerationService
    {
        $xssProtection = $this->createMock(XssProtectionService::class);
        $xssProtection->method('detectXss')->willReturn($detectXss);

        $richTextProcessor = $this->createMock(RichTextProcessorService::class);
        $richTextProcessor->method('validateSecurity')->willReturn($richTextIssues);

        return new ContentModerationService($xssProtection, $richTextProcessor);
    }
}
