<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Services\Core\XssProtectionService;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

#[Group('integration')]
#[Group('security')]
final class RichTextSecurityIntegrationTest extends IntegrationTestCase
{
    private XssProtectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $logger->shouldReceive('log')->zeroOrMoreTimes()->andReturn(true);
        $logger->shouldReceive('logSecurityEvent')->zeroOrMoreTimes()->andReturn(true);

        $this->service = new XssProtectionService($logger);
    }

    public function testRichTextRemovesDangerousNodesButKeepsAllowedTags(): void
    {
        $payload = '<p>Hello <strong>World</strong><script>alert(1)</script><a href="https://example.com">link</a></p>';
        $cleaned = $this->service->clean($payload);

        $this->assertStringContainsString('<p>', $cleaned);
        $this->assertStringContainsString('<strong>World</strong>', $cleaned);
        $this->assertStringContainsString('<a href="https://example.com">link</a>', $cleaned);
        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringNotContainsString('alert(1)', $cleaned);
    }

    public function testStrictCleanRemovesAllHtmlForDetailSafeRendering(): void
    {
        $payload = '<p>Content <em>with style</em></p>';
        $cleaned = $this->service->strictClean($payload);

        $this->assertStringNotContainsString('<p>', $cleaned);
        $this->assertStringNotContainsString('<em>', $cleaned);
        $this->assertStringContainsString('Content', $cleaned);
    }
}
