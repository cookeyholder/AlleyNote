<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\UnitTestCase;

/**
 * 授權上下文物件單元測試.
 */
final class AuthorizationContextTest extends UnitTestCase
{
    public function testAuthorizationContextProperties(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        $context = new AuthorizationContext(
            userId: 42,
            userRole: 'editor',
            userPermissions: ['posts.create', 'posts.edit'],
            resource: 'posts',
            action: 'create',
            request: $request,
        );

        $this->assertSame(42, $context->userId);
        $this->assertSame('editor', $context->userRole);
        $this->assertSame(['posts.create', 'posts.edit'], $context->userPermissions);
        $this->assertSame('posts', $context->resource);
        $this->assertSame('create', $context->action);
        $this->assertSame($request, $context->request);
    }
}
