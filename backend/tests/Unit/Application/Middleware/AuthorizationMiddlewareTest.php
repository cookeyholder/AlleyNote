<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\AuthorizationMiddleware;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * AuthorizationMiddleware 單元測試.
 */
#[CoversClass(AuthorizationMiddleware::class)]
class AuthorizationMiddlewareTest extends UnitTestCase
{
    private AuthorizationMiddleware $middleware;

    private AuthorizationServiceInterface&MockInterface $authorizationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
        $this->middleware = new AuthorizationMiddleware(
            $this->authorizationService,
        );
    }

    #[Test]
    public function testCheckPermission(): void
    {
        $this->authorizationService->shouldReceive('can')
            ->once()
            ->with(1, 'posts', 'create')
            ->andReturn(true);

        $this->assertTrue($this->middleware->checkPermission(1, 'posts', 'create'));
    }

    #[Test]
    public function testRequirePermissionAllowed(): void
    {
        $this->authorizationService->shouldReceive('can')
            ->once()
            ->with(1, 'posts', 'edit')
            ->andReturn(true);

        $response = $this->middleware->requirePermission(1, 'posts', 'edit');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function testRequirePermissionForbidden(): void
    {
        $this->authorizationService->shouldReceive('can')
            ->once()
            ->with(1, 'posts', 'delete')
            ->andReturn(false);

        $response = $this->middleware->requirePermission(1, 'posts', 'delete');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('FORBIDDEN', (string) $response->getBody());
    }

    #[Test]
    public function testRequireRoleAllowed(): void
    {
        $this->authorizationService->shouldReceive('hasRole')
            ->once()
            ->with(2, 'admin')
            ->andReturn(true);

        $response = $this->middleware->requireRole(2, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function testRequireRoleForbidden(): void
    {
        $this->authorizationService->shouldReceive('hasRole')
            ->once()
            ->with(2, 'admin')
            ->andReturn(false);

        $response = $this->middleware->requireRole(2, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('FORBIDDEN', (string) $response->getBody());
    }

    #[Test]
    public function testExtractResourceFromPath(): void
    {
        $this->assertEquals('post', $this->middleware->extractResourceFromPath('/api/posts/1'));
        $this->assertEquals('attachment', $this->middleware->extractResourceFromPath('/api/attachments/upload'));
        $this->assertEquals('ip', $this->middleware->extractResourceFromPath('/api/ip/whitelist'));
        $this->assertEquals('user', $this->middleware->extractResourceFromPath('/api/users/profile'));
        $this->assertEquals('system', $this->middleware->extractResourceFromPath('/api/system/info'));
        $this->assertEquals('unknown', $this->middleware->extractResourceFromPath('/api/unsupported/route'));
    }

    #[Test]
    public function testExtractActionFromMethod(): void
    {
        $this->assertEquals('read', $this->middleware->extractActionFromMethod('GET'));
        $this->assertEquals('read', $this->middleware->extractActionFromMethod('get'));
        $this->assertEquals('create', $this->middleware->extractActionFromMethod('POST'));
        $this->assertEquals('update', $this->middleware->extractActionFromMethod('PUT'));
        $this->assertEquals('update', $this->middleware->extractActionFromMethod('PATCH'));
        $this->assertEquals('delete', $this->middleware->extractActionFromMethod('DELETE'));
        $this->assertEquals('unknown', $this->middleware->extractActionFromMethod('OPTIONS'));
        $this->assertEquals('unknown', $this->middleware->extractActionFromMethod('HEAD'));
    }
}
