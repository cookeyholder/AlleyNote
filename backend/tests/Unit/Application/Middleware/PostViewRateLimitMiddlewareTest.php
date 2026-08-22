<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\PostViewRateLimitMiddleware;
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
 * PostViewRateLimitMiddleware 單元測試.
 */
#[CoversClass(PostViewRateLimitMiddleware::class)]
class PostViewRateLimitMiddlewareTest extends UnitTestCase
{
    private PostViewRateLimitMiddleware $middleware;

    private RateLimitServiceInterface&MockInterface $rateLimitService;

    private RequestHandlerInterface&MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimitService = Mockery::mock(RateLimitServiceInterface::class);
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
        $this->middleware = new PostViewRateLimitMiddleware(
            $this->rateLimitService,
        );
    }

    #[Test]
    public function testMetadataAndPriority(): void
    {
        $this->assertEquals(50, $this->middleware->getPriority());
        $this->assertEquals('post_view_rate_limit', $this->middleware->getName());
    }

    #[Test]
    public function testShouldProcessWithRouteName(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('route_name')->andReturn('posts.view.show');

        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testShouldProcessWithRouteObject(): void
    {
        $route = new class {
            public function getName(): string
            {
                return 'post_view_record';
            }
        };

        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('route_name')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('route')->andReturn($route);

        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testShouldProcessWithPath(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('route_name')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('route')->andReturn(null);

        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('getPath')->andReturn('/api/posts/123/views');
        $request->shouldReceive('getUri')->andReturn($uri);

        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testShouldProcessReturnsFalseForUnrelatedPaths(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('route_name')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('route')->andReturn(null);

        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('getPath')->andReturn('/api/auth/login');
        $request->shouldReceive('getUri')->andReturn($uri);

        $this->assertFalse($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testProcessAllowedForAuthenticatedUser(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(42);

        $resetTime = time() + 60;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('post_view_user_42', 300, 60)
            ->andReturn([
                'allowed'   => true,
                'remaining' => 299,
                'reset'     => $resetTime,
            ]);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn(new Response(200));

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('300', $result->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('299', $result->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertTrue($result->hasHeader('X-Processing-Time'));
    }

    #[Test]
    public function testProcessAllowedForAnonymousUser(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '203.0.113.50']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);

        $resetTime = time() + 60;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('post_view_ip_203.0.113.50', 120, 60)
            ->andReturn([
                'allowed'   => true,
                'remaining' => 119,
                'reset'     => $resetTime,
            ]);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn(new Response(200));

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('120', $result->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('119', $result->getHeaderLine('X-RateLimit-Remaining'));
    }

    #[Test]
    public function testProcessBlockedReturns429(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '1.2.3.4']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);

        $resetTime = time() + 30;
        $this->rateLimitService->shouldReceive('checkLimit')
            ->once()
            ->with('post_view_ip_1.2.3.4', 120, 60)
            ->andReturn([
                'allowed'   => false,
                'remaining' => 0,
                'reset'     => $resetTime,
            ]);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(429, $result->getStatusCode());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('RATE_LIMIT_EXCEEDED', (string) $result->getBody());
    }
}
