<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\AuthorizationOrchestratorService;
use App\Domains\Auth\ValueObjects\AuthorizationResult;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * JWT 授權中介軟體測試.
 */
final class JwtAuthorizationMiddlewareTest extends UnitTestCase
{
    private JwtAuthorizationMiddleware $middleware;

    private RequestHandlerInterface|MockInterface $handler;

    /** @var AuthorizationOrchestratorService&MockInterface */
    private $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AuthorizationOrchestratorService&MockInterface $orchestrator */
        $orchestrator = Mockery::mock(AuthorizationOrchestratorService::class);
        $this->orchestrator = $orchestrator;
        $this->middleware = new JwtAuthorizationMiddleware(
            authorizationOrchestrator: $orchestrator,
        );
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testMiddlewareIsDisabledWhenNotEnabled(): void
    {
        /** @var AuthorizationOrchestratorService&MockInterface $orch */
        $orch = Mockery::mock(AuthorizationOrchestratorService::class);
        $middleware = new JwtAuthorizationMiddleware(
            authorizationOrchestrator: $orch,
            enabled: false,
        );
        $request = $this->createLocalRequest('/api/v1/posts');

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
        $request = $this->createLocalRequest('/home');

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
        $request = $this->createLocalRequest('/api/v1/posts');

        $this->handler->shouldNotReceive('handle');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseData['success']);
        $this->assertSame('NOT_AUTHENTICATED', $responseData['code']);
    }

    public function testAllowsSuperAdminAccess(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts', 'DELETE')
            ->withAttribute('role', 'admin')
            ->withAttribute('user_id', 1);

        $this->orchestrator
            ->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(AuthorizationContext::class))
            ->andReturn(new AuthorizationResult(true, '超級管理員擁有所有權限', 'SUPER_ADMIN_ACCESS'));

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

        $this->orchestrator
            ->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(AuthorizationContext::class))
            ->andReturn(new AuthorizationResult(true, '角色 user 擁有權限 posts.show', 'ROLE_SPECIFIC_ACCESS'));

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

        $this->orchestrator
            ->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(AuthorizationContext::class))
            ->andReturn(new AuthorizationResult(false, '角色 user 沒有權限 posts.delete', 'ROLE_INSUFFICIENT'));

        $this->handler->shouldNotReceive('handle');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertSame(403, $response->getStatusCode());
        $responseData = json_decode((string) $response->getBody(), true);
        $this->assertFalse($responseData['success']);
    }

    public function testAllowsAccessWithValidDirectPermissions(): void
    {
        $request = $this->createAuthenticatedRequest('/api/v1/posts', 'POST')
            ->withAttribute('role', 'guest')
            ->withAttribute('permissions', ['posts.create'])
            ->withAttribute('user_id', 1);

        $this->orchestrator
            ->shouldReceive('authorize')
            ->once()
            ->with(Mockery::type(AuthorizationContext::class))
            ->andReturn(new AuthorizationResult(true, '使用者擁有權限 posts.create', 'PERMISSION_SPECIFIC_ACCESS'));

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

    private function createLocalRequest(string $path, string $method = 'GET'): ServerRequest
    {
        return new ServerRequest($method, new Uri($path));
    }

    private function createAuthenticatedRequest(string $path, string $method = 'GET'): ServerRequest
    {
        return $this->createLocalRequest($path, $method)
            ->withAttribute('authenticated', true)
            ->withAttribute('user_id', 1)
            ->withAttribute('role', 'user')
            ->withAttribute('permissions', []);
    }
}
