<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Services\Core\XssProtectionService;
use PHPUnit\Framework\TestCase;


class XssProtectionServiceTest extends TestCase
{
    private XssProtectionService $service;

    protected function setUp(): void
    {
        $this->service = new XssProtectionService();
    }

    /** @test */
    public function escapesBasicHtml(): void
    {
        $input = '<script>alert("XSS");</script>';
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';

        $result = $this->service->clean($input);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function escapesHtmlAttributes(): void
    {
        $input = '<a href="javascript:alert(\'XSS\')" onclick="alert(\'XSS\')">Click me</a>';
        $expected = '&lt;a href=&quot;javascript:alert(&#039;XSS&#039;)&quot; onclick=&quot;alert(&#039;XSS&#039;)&quot;&gt;Click me&lt;/a&gt;';

        $result = $this->service->clean($input);

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function handlesNullInput(): void
    {
        $result = $this->service->clean(null);
        $this->assertNull($result);
    }

    /** @test */
    public function cleansArrayOfStrings(): void
    {
        $input = [
            'title' => '<script>alert("XSS");</script>',
            'content' => '<img src="x" onerror="alert(\'XSS\')" />',
        ];

        $result = $this->service->cleanArray($input, ['title', 'content']);

        $this->assertEquals(
            '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;',
            $result['title']
        );
        $this->assertEquals(
            '&lt;img src=&quot;x&quot; onerror=&quot;alert(&#039;XSS&#039;)&quot; /&gt;',
            $result['content']
        );
    }
}
