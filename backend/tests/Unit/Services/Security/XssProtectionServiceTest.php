<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Services\Core\XssProtectionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class XssProtectionServiceTest extends TestCase
{
    private XssProtectionService $service;

    protected function setUp(): void
    {
        $mockActivityLogger = $this->createMock(ActivityLoggingServiceInterface::class);
        $this->service = new XssProtectionService($mockActivityLogger);
    }

    #[Test]
    public function removesScriptTags(): void
    {
        $input = '<script>alert("XSS");</script>';
        $expected = ''; // HTMLPurifier 移除 script 標籤

        $result = $this->service->clean($input);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function removesHarmfulAttributes(): void
    {
        $input = '<a href="javascript:alert(\'XSS\')" onclick="alert(\'XSS\')">Click me</a>';
        $expected = '<a>Click me</a>'; // HTMLPurifier 移除有害屬性但保留基本標籤

        $result = $this->service->clean($input);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function handlesEmptyInput(): void
    {
        $result = $this->service->clean('');
        $this->assertEquals('', $result);
    }

    #[Test]
    public function cleansArrayOfStrings(): void
    {
        $input = [
            'title' => '<script>alert("XSS");</script>',
            'content' => '<img src="x" onerror="alert(\'XSS\')" />',
        ];

        $result = $this->service->cleanArray($input, ['title' => true, 'content' => true]);

        $this->assertEquals('', $result['title']); // script 標籤被移除
        $this->assertEquals('', $result['content']); // 有害 img 標籤被移除
    }
}
