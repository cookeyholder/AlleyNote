<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * JWT 授權中介軟體測試.
 */
final class JwtAuthorizationMiddlewareTest extends TestCase
{
    private JwtAuthorizationMiddleware $middleware;

    private RequestHandlerInterface|MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new JwtAuthorizationMiddleware();
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testMiddlewareIsDisabledWhenNotEnabled(): void
    {
        $middleware = new JwtAuthorizationMiddleware(enabled: false);
        $request = $this->createRequest('/api/v1/posts');

        $expectedResponse = new Response(200);
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($expectedResponse);

        $response = $middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testSkipsProcessingForNonApiPaths(): void
    {
        $request = $this->createRequest('/home');

        $expectedResponse = new Response(200);
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testReturns403WhenUserNotAuthenticated(): void
    {
        $request = $this->createRequest('/api/v1/posts');

        $this->handler->shouldNotReceive('handle');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseData['success'] ?? null);
        $this->assertSame('NOT_AUTHENTICATED', $responseData['code'] ?? null);
    }

    public function testAllowsSuperAdminAccess(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts', 'DELETE')
            ->withAttribute('role', 'admin')
            ->withAttribute('user_id', 1);

        $expectedResponse = new Response(200);
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAllowsAccessWithValidRolePermissions(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts/123', 'GET')
            ->withAttribute('role', 'user')
            ->withAttribute('user_id', 1);

        $expectedResponse = new Response(200);
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeniesAccessWithInvalidRolePermissions(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts', 'DELETE')
            ->withAttribute('role', 'user')
            ->withAttribute('user_id', 1);

        $this->handler->shouldNotReceive('handle');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseData['success'] ?? null);
    }

    public function testAllowsAccessWithValidDirectPermissions(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts', 'POST')
            ->withAttribute('role', 'guest')
            ->withAttribute('permissions', ['posts.create'])
            ->withAttribute('user_id', 1);

        $expectedResponse = new Response(200);
        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewarePriorityAndEnabledSettings(): void
    {
        $this->assertSame(20, $this->middleware->getPriority());
        $this->assertSame('jwt-authorization', $this->middleware->getName());
        $this->assertTrue($this->middleware->isEnabled());

        $this->middleware->setPriority(30);
        $this->middleware->setEnabled(false);

        $this->assertSame(30, $this->middleware->getPriority());
        $this->assertFalse($this->middleware->isEnabled());
    }

    /**
     * 建立基本請求.
     */
    private function createRequest(string $path, string $method = 'GET'): ServerRequest
    {
        return new ServerRequest($method, new Uri($path));
    }

    /**
     * 建立已認證的請求.
     */
    private function createAuthenticatedRequest(string $path, string $method = 'GET'): ServerRequest
    {
        return $this->createRequest($path, $method)
            ->withAttribute('authenticated', true)
            ->withAttribute('user_id', 1)
            ->withAttribute('role', 'user')
            ->withAttribute('permissions', []);
    }
}
