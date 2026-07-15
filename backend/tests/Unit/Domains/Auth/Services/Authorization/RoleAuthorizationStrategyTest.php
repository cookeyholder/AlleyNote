<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\RoleAuthorizationStrategy;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class RoleAuthorizationStrategyTest extends TestCase
{
    /** @var array<string, list<string>> */
    private array $defaultRolePermissions;

    protected function setUp(): void
    {
        $this->defaultRolePermissions = [
            'admin'     => ['*'],
            'moderator' => ['posts.*', 'comments.*'],
            'user'      => ['posts.show', 'posts.create', 'comments.show', 'comments.create'],
            'guest'     => ['posts.show', 'comments.show'],
        ];
    }

    public function testAllowsWildcardRoleAccess(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: 'admin', resource: 'posts', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('ROLE_WILDCARD_ACCESS', $result->getCode());
    }

    public function testAllowsResourceWildcardAccess(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: 'moderator', resource: 'posts', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('ROLE_WILDCARD_ACCESS', $result->getCode());
    }

    public function testAllowsSpecificPermissionAccess(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: 'user', resource: 'posts', action: 'show');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('ROLE_SPECIFIC_ACCESS', $result->getCode());
    }

    public function testDeniesInsufficientPermission(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: 'guest', resource: 'posts', action: 'create');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('ROLE_INSUFFICIENT', $result->getCode());
    }

    public function testDeniesUnknownRole(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: 'unknown_role', resource: 'posts', action: 'show');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
    }

    public function testDeniesNullRole(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: null);

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_ROLE', $result->getCode());
    }

    public function testDeniesEmptyRole(): void
    {
        $strategy = new RoleAuthorizationStrategy($this->defaultRolePermissions);
        $context = $this->createContext(userRole: '');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_ROLE', $result->getCode());
    }

    public function testAllowsWithCustomRolePermissions(): void
    {
        $customPermissions = [
            'editor' => ['posts.*', 'settings.show'],
        ];
        $strategy = new RoleAuthorizationStrategy($customPermissions);
        $context = $this->createContext(userRole: 'editor', resource: 'settings', action: 'show');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
    }

    /**
     * @param array<string> $userPermissions
     */
    private function createContext(
        int $userId = 1,
        ?string $userRole = null,
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
