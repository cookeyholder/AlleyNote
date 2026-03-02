<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Services\Core\XssProtectionService;
use Mockery;
use Tests\Support\IntegrationTestCase;

class XssPayloadTest extends IntegrationTestCase
{
    private XssProtectionService $xssService;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = Mockery::mock(ActivityLoggingServiceInterface::class);
        $logger->shouldReceive('logSecurityEvent')->andReturn(true);

        $this->xssService = new XssProtectionService($logger);
    }

    public function test_xss_payload_in_post_content_is_sanitized(): void
    {
        $maliciousPayload = '<p>Normal text <script>alert("XSS")</script><img src="x" onerror="alert(1)"></p>';

        $sanitizedContent = $this->xssService->clean($maliciousPayload);

        $this->assertStringNotContainsString('<script>', $sanitizedContent);
        $this->assertStringNotContainsString('onerror', $sanitizedContent);
        $this->assertStringContainsString('Normal text', $sanitizedContent);
    }

    public function test_xss_payload_in_post_title_is_sanitized(): void
    {
        $maliciousTitle = 'My Title <script>alert("XSS")</script>';

        $sanitizedTitle = $this->xssService->strictClean($maliciousTitle);

        $this->assertStringNotContainsString('<script>', $sanitizedTitle);
        $this->assertStringContainsString('My Title', $sanitizedTitle);
    }
}
