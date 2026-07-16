<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\CsrfMiddleware;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * CSRF 中介軟體單元測試.
 */
final class CsrfMiddlewareTest extends UnitTestCase
{
    private CsrfMiddleware $middleware;

    private RequestHandlerInterface|MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CsrfMiddleware(
            skipPaths: ['/api/csrf-token'],
        );
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testShouldSkipCsrfTokenEndpoint(): void
    {
        $request = $this->createRequest('GET', '/api/csrf-token');

        $this->assertFalse($this->middleware->shouldProcess($request));
    }

    public function testShouldProcessNonSkippedPaths(): void
    {
        $request = $this->createRequest('POST', '/api/posts');

        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    public function testShouldProcessGetRequestForCsrfAttachment(): void
    {
        $request = $this->createRequest('GET', '/api/posts');

        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    public function testCsrfTokenEndpointPassesThroughWithoutValidation(): void
    {
        $request = $this->createRequest('GET', '/api/csrf-token');

        $this->assertFalse($this->middleware->shouldProcess($request));
    }

    public function testDisabledMiddlewareDoesNotProcess(): void
    {
        $middleware = new CsrfMiddleware(
            enabled: false,
        );
        $request = $this->createRequest('POST', '/api/posts');

        $this->assertFalse($middleware->shouldProcess($request));
    }

    public function testGetRequestAttachesCsrfCookie(): void
    {
        $request = $this->createRequest('GET', '/api/posts');

        $expectedResponse = new Response(200, ['Content-Type' => 'application/json'], '{"data":[]}');
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($expectedResponse);

        // @phpstan-ignore-next-line - MockInterface satisfies RequestHandlerInterface at runtime
        $response = $this->middleware->process($request, $this->handler);

        $setCookieHeaders = $response->getHeader('Set-Cookie');
        $this->assertNotEmpty($setCookieHeaders);
        $hasCsrfCookie = false;
        foreach ($setCookieHeaders as $cookie) {
            if (str_starts_with($cookie, 'csrf_token=')) {
                $hasCsrfCookie = true;
                $this->assertStringContainsString('SameSite=Strict', $cookie);
                break;
            }
        }
        $this->assertTrue($hasCsrfCookie, 'Response should contain csrf_token cookie');
    }
}
