<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\SuperAdminAuthorizationStrategy;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class SuperAdminAuthorizationStrategyTest extends TestCase
{
    private SuperAdminAuthorizationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new SuperAdminAuthorizationStrategy();
    }

    public function testAllowsSuperAdminRole(): void
    {
        $context = $this->createContext(userRole: 'admin');

        $result = $this->strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('SUPER_ADMIN_ACCESS', $result->getCode());
    }

    public function testAllowsSuperAdminCustomRole(): void
    {
        $strategy = new SuperAdminAuthorizationStrategy(adminRoles: ['root']);
        $context = $this->createContext(userRole: 'root');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
    }

    public function testAllowsSystemAdminRole(): void
    {
        $context = $this->createContext(userRole: 'system_admin');

        $result = $this->strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
    }

    public function testDeniesNonAdminRole(): void
    {
        $context = $this->createContext(userRole: 'user');

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NOT_SUPER_ADMIN', $result->getCode());
    }

    public function testDeniesNullRole(): void
    {
        $context = $this->createContext(userRole: null);

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
    }

    public function testDeniesEmptyRole(): void
    {
        $context = $this->createContext(userRole: '');

        $result = $this->strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
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
