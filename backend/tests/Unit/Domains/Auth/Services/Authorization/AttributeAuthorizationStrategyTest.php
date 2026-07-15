<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AttributeAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class AttributeAuthorizationStrategyTest extends TestCase
{
    public function testPassesWhenNoTimeRestrictions(): void
    {
        $strategy = new AttributeAuthorizationStrategy();
        $context = $this->createContext(action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('ATTRIBUTE_CHECK_FAILED', $result->getCode());
    }

    public function testDeniesWhenTimeRestrictionViolated(): void
    {
        $currentHour = (int) date('H');
        $restrictedHour = ($currentHour + 1) % 24;
        $timeRestrictions = [
            [
                'roles'   => ['user'],
                'actions' => ['delete'],
                'hours'   => [$restrictedHour],
            ],
        ];

        $strategy = new AttributeAuthorizationStrategy(
            timeRestrictions: $timeRestrictions,
        );
        $context = $this->createContext(userRole: 'user', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('TIME_RESTRICTION_VIOLATED', $result->getCode());
    }

    public function testPassesTimeCheckWhenCurrentHourIsAllowed(): void
    {
        $currentHour = (int) date('H');
        $timeRestrictions = [
            [
                'roles'   => ['user'],
                'actions' => ['delete'],
                'hours'   => [$currentHour],
            ],
        ];

        $strategy = new AttributeAuthorizationStrategy(
            timeRestrictions: $timeRestrictions,
        );
        $context = $this->createContext(userRole: 'user', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('ATTRIBUTE_CHECK_FAILED', $result->getCode());
    }

    public function testDeniesWhenDayRestrictionViolated(): void
    {
        $currentDay = date('w');
        $restrictedDay = ((int) $currentDay + 1) % 7;

        $timeRestrictions = [
            [
                'roles'   => ['user'],
                'actions' => ['delete'],
                'days'    => [$restrictedDay],
            ],
        ];

        $strategy = new AttributeAuthorizationStrategy(
            timeRestrictions: $timeRestrictions,
        );
        $context = $this->createContext(userRole: 'user', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('TIME_RESTRICTION_VIOLATED', $result->getCode());
    }

    public function testAllowsResourceOwnershipForUpdateAction(): void
    {
        $strategy = new AttributeAuthorizationStrategy(
            ownershipRules: ['posts' => true],
        );
        $request = new ServerRequest('PUT', new Uri('/api/v1/posts/1'));
        $context = new AuthorizationContext(
            userId: 1,
            userRole: 'user',
            userPermissions: [],
            resource: 'posts',
            action: 'update',
            request: $request,
        );

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('RESOURCE_OWNER_ACCESS', $result->getCode());
    }

    public function testDeniesResourceOwnershipWhenNotOwner(): void
    {
        $strategy = new AttributeAuthorizationStrategy(
            ownershipRules: ['posts' => true],
        );
        $request = new ServerRequest('DELETE', new Uri('/api/v1/posts/5'));
        $context = new AuthorizationContext(
            userId: 1,
            userRole: 'user',
            userPermissions: [],
            resource: 'posts',
            action: 'delete',
            request: $request,
        );

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('ATTRIBUTE_CHECK_FAILED', $result->getCode());
    }

    public function testDefaultsToOwnerWhenNoOwnershipRules(): void
    {
        $strategy = new AttributeAuthorizationStrategy();
        $request = new ServerRequest('PUT', new Uri('/api/v1/posts/1'));
        $context = new AuthorizationContext(
            userId: 1,
            userRole: 'user',
            userPermissions: [],
            resource: 'posts',
            action: 'update',
            request: $request,
        );

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('RESOURCE_OWNER_ACCESS', $result->getCode());
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
