<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\RateLimitMiddleware;
use App\Domains\Security\Contracts\RateLimitServiceInterface;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\UnitTestCase;

/**
 * RateLimitMiddleware 單元測試.
 */
#[CoversClass(RateLimitMiddleware::class)]
class RateLimitMiddlewareTest extends UnitTestCase
{
    private RateLimitMiddleware $middleware;

    private RateLimitServiceInterface&MockInterface $rateLimitService;

    private RequestHandlerInterface&MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
        $this->middleware = new RateLimitMiddleware(
            $this->rateLimitService,
            ['max_requests' => 60, 'time_window' => 60],
        );
    }

    #[Test]
    public function testMetadataAndPriority(): void
    {
        $this->assertEquals(10, $this->middleware->getPriority());
        $this->assertEquals('rate-limit', $this->middleware->getName());
    }

    #[Test]
    public function testShouldProcessReturnsFalseForSkippedPaths(): void
    {
        $skipPaths = ['/health', '/status', '/favicon.ico'];

        foreach ($skipPaths as $path) {
            $request = $this->createMockRequest('GET', $path);
            $this->assertFalse($this->middleware->shouldProcess($request));
        }
    }

    #[Test]
    public function testShouldProcessReturnsTrueForRegularPaths(): void
    {
        $request = $this->createMockRequest('GET', '/api/posts');
        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testProcessSkipsRateLimitingForSkippedPaths(): void
    {
        $request = $this->createMockRequest('GET', '/health');
        $expectedResponse = new Response(200);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($expectedResponse);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testProcessAllowedRequestAddsHeaders(): void
    {
        $request = $this->createMockRequest('GET', '/api/posts');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '192.168.1.100']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('5');

        $resetTime = time() + 60;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('192.168.1.100', 60, 60)
            ->andReturn([
                'allowed'   => true,
                'remaining' => 55,
                'reset'     => $resetTime,
            ]);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn(new Response(200));

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('60', $result->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('55', $result->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertEquals((string) $resetTime, $result->getHeaderLine('X-RateLimit-Reset'));
    }

    #[Test]
    public function testProcessBlockedJsonRequestReturns429(): void
    {
        $request = $this->createMockRequest('POST', '/auth/login', 'application/json');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '10.0.0.1']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);

        $resetTime = time() + 45;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('10.0.0.1', 60, 60)
            ->andReturn([
                'allowed'   => false,
                'remaining' => 0,
                'reset'     => $resetTime,
            ]);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(429, $result->getStatusCode());
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Rate limit exceeded', (string) $result->getBody());
    }

    #[Test]
    public function testProcessBlockedHtmlRequestReturns429Html(): void
    {
        $request = $this->createMockRequest('GET', '/home', 'text/html');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '10.0.0.2']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);

        $resetTime = time() + 30;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('10.0.0.2', 60, 60)
            ->andReturn([
                'allowed'   => false,
                'remaining' => 0,
                'reset'     => $resetTime,
            ]);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(429, $result->getStatusCode());
        $this->assertStringContainsString('text/html', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('請求過於頻繁', (string) $result->getBody());
    }

    #[Test]
    public function testDetermineActionVariations(): void
    {
        $pathsAndMethods = [
            ['GET', '/auth/register'],
            ['POST', '/auth/password-reset'],
            ['POST', '/posts/create'],
            ['GET', '/other'],
        ];

        foreach ($pathsAndMethods as [$method, $path]) {
            $request = $this->createMockRequest($method, $path);
            $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
            $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);

            $this->rateLimitService->shouldReceive('checkLimit')
                ->once()
                ->andReturn([
                    'allowed'   => true,
                    'remaining' => 50,
                    'reset'     => time() + 60,
                ]);

            $this->handler->shouldReceive('handle')->once()->andReturn(new Response(200));

            $result = $this->middleware->process($request, $this->handler);
            $this->assertEquals(200, $result->getStatusCode());
        }
    }

    private function createMockRequest(
        string $method = 'GET',
        string $path = '/',
        string $accept = 'application/json',
    ): ServerRequestInterface&MockInterface {
        $request = Mockery::mock(ServerRequestInterface::class);
        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('getPath')->andReturn($path);
        $request->shouldReceive('getUri')->andReturn($uri);
        $request->shouldReceive('getMethod')->andReturn($method);
        $request->shouldReceive('getHeaderLine')->with('Accept')->andReturn($accept);

        return $request;
    }
}
