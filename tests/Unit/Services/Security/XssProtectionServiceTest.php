<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\XssProtectionService;
use PHPUnit\Framework\TestCase;

class XssProtectionServiceTest extends TestCase
{
    private XssProtectionService $service;

    protected function setUp(): void
    {
        $this->service = new XssProtectionService();
    }

    /** @test */
    public function it_escapes_basic_html(): void
    {
        $input = '<script>alert("XSS")</script>';
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        $this->assertEquals($expected, $this->service->clean($input));
    }

    /** @test */
    public function it_escapes_html_attributes(): void
    {
        $input = '<div onclick="alert(\'XSS\')" onmouseover="alert(\'XSS\')">Test</div>';
        $escaped = $this->service->clean($input);

        // 驗證關鍵的 HTML 實體轉換
        $this->assertStringContainsString('&lt;div', $escaped);
        $this->assertStringContainsString('onclick=&quot;', $escaped);
        $this->assertStringContainsString('&gt;Test&lt;/div&gt;', $escaped);
        // 確認單引號被正確跳脫（接受 &#039; 或 &apos;）
        $this->assertMatchesRegularExpression('/alert\((&#039;|&apos;)XSS(&#039;|&apos;)\)/', $escaped);
    }

    /** @test */
    public function it_handles_null_input(): void
    {
        $this->assertNull($this->service->clean(null));
    }

    /** @test */
    public function it_cleans_array_of_strings(): void
    {
        $input = [
            'title' => '<div onclick="alert(\'XSS\')">Test</div>',
            'content' => '<script>alert("XSS")</script>'
        ];

        $result = $this->service->cleanArray($input, ['title', 'content']);

        // 驗證 title 的跳脫
        $this->assertStringContainsString('&lt;div', $result['title']);
        $this->assertStringContainsString('onclick=&quot;', $result['title']);
        $this->assertMatchesRegularExpression('/alert\((&#039;|&apos;)XSS(&#039;|&apos;)\)/', $result['title']);

        // 驗證 content 的跳脫
        $this->assertEquals(
            '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            $result['content']
        );
    }
}
