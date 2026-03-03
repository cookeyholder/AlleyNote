<?php

declare(strict_types=1);

namespace Tests;

use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Helpers\NetworkHelper;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\BaseTestCase;

/**
 * Secure-DDD 模式專用測試基類.
 *
 * 提供對安全性組件（如 NetworkHelper, OutputSanitizer）的統一模擬支援。
 */
abstract class SecureDDDTestCase extends BaseTestCase
{
    /**
     * 模擬 OutputSanitizer 服務.
     */
    protected function mockOutputSanitizer(): OutputSanitizerInterface|MockInterface
    {
        $mock = Mockery::mock(OutputSanitizerInterface::class);
        
        // 預設行為：標題轉義，內容過濾（模擬 HTMLPurifier）
        $mock->shouldReceive('sanitizeHtml')
            ->andReturnUsing(fn($content) => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
            
        $mock->shouldReceive('sanitizeRichText')
            ->andReturnUsing(fn($content) => strip_tags($content, '<h1><h2><h3><h4><h5><h6><p><br><strong><em><u><s><a><img><ul><ol><li><blockquote><pre><code><table><thead><tbody><tr><th><td><div><span>'));

        return $mock;
    }

    /**
     * 為請求模擬客戶端 IP.
     */
    protected function createRequestWithIp(string $ip, string $method = 'GET', string $path = '/'): ServerRequestInterface
    {
        $factory = new \Slim\Psr7\Factory\ServerRequestFactory();
        return $factory->createServerRequest($method, $path)
            ->withServerParams(['REMOTE_ADDR' => $ip]);
    }

    /**
     * 斷言 API 回應是否符合 Secure-DDD 標準格式.
     */
    protected function assertSafeApiResponse(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
        if (isset($response['success']) && $response['success'] === false) {
            $this->assertArrayHasKey('error', $response);
            if (isset($response['error'])) {
                $this->assertArrayHasKey('code', $response['error']);
            }
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
