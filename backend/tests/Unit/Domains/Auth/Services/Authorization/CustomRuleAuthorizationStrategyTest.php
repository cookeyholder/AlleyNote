<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\CustomRuleAuthorizationStrategy;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class CustomRuleAuthorizationStrategyTest extends TestCase
{
    public function testAllowsWithAllowRule(): void
    {
        $customRules = [
            'maintenance_bypass' => [
                'type'       => 'allow',
                'conditions' => [
                    'resource' => 'maintenance',
                    'action'   => 'access',
                ],
                'message' => '維護模式繞過規則',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(resource: 'maintenance', action: 'access');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('CUSTOM_RULE_ALLOW', $result->getCode());
    }

    public function testDeniesWithDenyRule(): void
    {
        $customRules = [
            'block_sensitive' => [
                'type'       => 'deny',
                'conditions' => [
                    'resource' => 'admin',
                ],
                'message' => '敏感操作封鎖',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(resource: 'admin', action: 'delete');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('CUSTOM_RULE_DENY', $result->getCode());
    }

    public function testConditionalRuleFailsWhenParamMissing(): void
    {
        $customRules = [
            'requires_token' => [
                'type'       => 'conditional',
                'conditions' => [
                    'resource'        => 'api',
                    'required_params' => ['token'],
                ],
                'message' => '需要 API Token',
                'result'  => 'allow',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(resource: 'api', action: 'access');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('MISSING_REQUIRED_PARAM', $result->getCode());
    }

    public function testConditionalRulePassesWhenParamPresent(): void
    {
        $customRules = [
            'requires_token' => [
                'type'       => 'conditional',
                'conditions' => [
                    'resource'        => 'api',
                    'required_params' => ['token'],
                ],
                'message' => '需要 API Token',
                'result'  => 'allow',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $request = new ServerRequest('GET', new Uri('/api/v1/api'));
        $request = $request->withQueryParams(['token' => 'abc123']);
        $context = new AuthorizationContext(
            userId: 1,
            userRole: 'user',
            userPermissions: [],
            resource: 'api',
            action: 'access',
            request: $request,
        );

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('CONDITIONAL_ALLOW', $result->getCode());
    }

    public function testConditionalRuleDeniesWhenResultIsDeny(): void
    {
        $customRules = [
            'audit_log' => [
                'type'       => 'conditional',
                'conditions' => [
                    'resource'        => 'audit',
                    'required_params' => [],
                ],
                'message' => '稽核日誌操作需審核',
                'result'  => 'deny',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(resource: 'audit', action: 'view');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('CONDITIONAL_DENY', $result->getCode());
    }

    public function testReturnsNoCustomRuleWhenNoMatch(): void
    {
        $customRules = [
            'specific_rule' => [
                'type'       => 'allow',
                'conditions' => [
                    'resource' => 'special',
                    'action'   => 'do_something',
                ],
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(resource: 'other', action: 'do_something');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_CUSTOM_RULE', $result->getCode());
    }

    public function testSkipsWhenNoCustomRules(): void
    {
        $strategy = new CustomRuleAuthorizationStrategy();
        $context = $this->createContext();

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_CUSTOM_RULE', $result->getCode());
    }

    public function testMatchesMultipleConditions(): void
    {
        $customRules = [
            'editor_access' => [
                'type'       => 'allow',
                'conditions' => [
                    'resource' => 'posts',
                    'action'   => 'edit',
                    'role'     => 'editor',
                ],
                'message' => '編輯者可以編輯貼文',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(userRole: 'editor', resource: 'posts', action: 'edit');

        $result = $strategy->evaluate($context);

        $this->assertTrue($result->isAllowed());
    }

    public function testDoesNotMatchWhenOneConditionFails(): void
    {
        $customRules = [
            'editor_access' => [
                'type'       => 'allow',
                'conditions' => [
                    'resource' => 'posts',
                    'action'   => 'edit',
                    'role'     => 'editor',
                ],
                'message' => '編輯者可以編輯貼文',
            ],
        ];
        $strategy = new CustomRuleAuthorizationStrategy($customRules);
        $context = $this->createContext(userRole: 'user', resource: 'posts', action: 'edit');

        $result = $strategy->evaluate($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('NO_CUSTOM_RULE', $result->getCode());
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
