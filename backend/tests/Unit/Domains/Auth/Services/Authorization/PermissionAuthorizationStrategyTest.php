<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\PermissionAuthorizationStrategy;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class PermissionAuthorizationStrategyTest extends TestCase
{
    private PermissionAuthorizationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new PermissionAuthorizationStrategy();
    }

    public function testAllowsWildcardPermission(): void
    {
        $context = $this->createContext(
            userPermissions: ['*'],
            resource: 'posts',
            action: 'delete',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('PERMISSION_WILDCARD_ACCESS', $result->getCode());
    }

    public function testAllowsResourceWildcardPermission(): void
    {
        $context = $this->createContext(
            userPermissions: ['posts.*'],
            resource: 'posts',
            action: 'delete',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('PERMISSION_WILDCARD_ACCESS', $result->getCode());
    }

    public function testAllowsSpecificPermission(): void
    {
        $context = $this->createContext(
            userPermissions: ['posts.create'],
            resource: 'posts',
            action: 'create',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('PERMISSION_SPECIFIC_ACCESS', $result->getCode());
    }

    public function testDeniesInsufficientPermission(): void
    {
        $context = $this->createContext(
            userPermissions: ['posts.show'],
            resource: 'posts',
            action: 'delete',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('PERMISSION_INSUFFICIENT', $result->getCode());
    }

    public function testDeniesEmptyPermissions(): void
    {
        $context = $this->createContext(
            userPermissions: [],
            resource: 'posts',
            action: 'show',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_PERMISSIONS', $result->getCode());
    }

    public function testDeniesNonMatchingResourceWildcard(): void
    {
        $context = $this->createContext(
            userPermissions: ['comments.*'],
            resource: 'posts',
            action: 'show',
        );

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
    }

    /**
     * @param array<string> $userPermissions
     */
    private function createContext(
        int $userId = 1,
        ?string $userRole = 'user',
        array $userPermissions = [],
        string $resource = 'posts',
        string $action = 'index',
    ): AuthorizationContext {
        return new AuthorizationContext(
            userId: $userId,
            userRole: $userRole,
            userPermissions: $userPermissions,
            resource: $resource,
            action: $action,
            request: new ServerRequest('GET', new Uri("/api/v1/{$resource}")),
        );
    }
}
