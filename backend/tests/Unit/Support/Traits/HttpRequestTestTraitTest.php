<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Traits;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\Traits\HttpRequestTestTrait;
use Tests\TestCase;

class HttpRequestTestTraitTest extends TestCase
{
    use HttpRequestTestTrait;

    public function testCreateRequestShouldReturnStandardServerRequest(): void
    {
        $request = $this->createRequest('GET', '/api/posts');

        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/api/posts', $request->getUri()->getPath());
        $this->assertEquals('localhost', $request->getUri()->getHost());
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
    }

    public function testWithJwtAuthShouldInjectBearerToken(): void
    {
        $request = $this->createRequest('GET', '/api/me');
        $token = 'test-token-123';

        $authenticatedRequest = $this->withJwtAuth($request, $token);

        // 驗證不可變性
        $this->assertNotSame($request, $authenticatedRequest);
        $this->assertEquals('', $request->getHeaderLine('Authorization'));
        
        // 驗證標頭注入
        $this->assertEquals('Bearer test-token-123', $authenticatedRequest->getHeaderLine('Authorization'));
    }

    public function testWithJsonBodyShouldEncodeDataAndSetContentType(): void
    {
        $request = $this->createRequest('POST', '/api/posts');
        $data = ['title' => 'TDD', 'content' => 'Works'];

        $jsonRequest = $this->withJsonBody($request, $data);

        $this->assertEquals('application/json', $jsonRequest->getHeaderLine('Content-Type'));
        
        $jsonRequest->getBody()->rewind();
        $this->assertEquals(json_encode($data), $jsonRequest->getBody()->getContents());
    }
}
