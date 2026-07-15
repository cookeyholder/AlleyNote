<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services\Authorization;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\AuthorizationOrchestratorService;
use App\Domains\Auth\Services\Authorization\AuthorizationStrategyInterface;
use App\Domains\Auth\ValueObjects\AuthorizationResult;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use PHPUnit\Framework\TestCase;

final class AuthorizationOrchestratorServiceTest extends TestCase
{
    public function testShortCircuitsOnFirstAllowedStrategy(): void
    {
        $denyStrategy = $this->createMock(AuthorizationStrategyInterface::class);
        $denyStrategy->method('evaluate')
            ->willReturn(new AuthorizationResult(false, 'denied', 'DENIED'));

        $allowStrategy = $this->createMock(AuthorizationStrategyInterface::class);
        $allowStrategy->method('evaluate')
            ->willReturn(new AuthorizationResult(true, 'allowed', 'ALLOWED'));

        $neverCalledStrategy = $this->createMock(AuthorizationStrategyInterface::class);
        $neverCalledStrategy->expects($this->never())->method('evaluate');

        $orchestrator = new AuthorizationOrchestratorService([
            $denyStrategy,
            $allowStrategy,
            $neverCalledStrategy,
        ]);

        $context = $this->createContext();
        $result = $orchestrator->authorize($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('ALLOWED', $result->getCode());
    }

    public function testReturnsDefaultDenyWhenAllReject(): void
    {
        $strategy1 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy1->method('evaluate')
            ->willReturn(new AuthorizationResult(false, 'denied 1', 'DENIED_1'));

        $strategy2 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy2->method('evaluate')
            ->willReturn(new AuthorizationResult(false, 'denied 2', 'DENIED_2'));

        $orchestrator = new AuthorizationOrchestratorService([$strategy1, $strategy2]);

        $context = $this->createContext();
        $result = $orchestrator->authorize($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('INSUFFICIENT_PERMISSIONS', $result->getCode());
    }

    public function testReturnsDefaultAllowWhenPolicyIsAllow(): void
    {
        $strategy = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy->method('evaluate')
            ->willReturn(new AuthorizationResult(false, 'denied', 'DENIED'));

        $orchestrator = new AuthorizationOrchestratorService([$strategy], defaultPolicy: 'allow');

        $context = $this->createContext();
        $result = $orchestrator->authorize($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('DEFAULT_ALLOW', $result->getCode());
    }

    public function testExecutesStrategiesInGivenOrder(): void
    {
        $order = [];
        $strategy1 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy1->method('evaluate')
            ->willReturnCallback(function () use (&$order) {
                $order[] = 'first';

                return new AuthorizationResult(false, 'denied', 'DENIED');
            });

        $strategy2 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy2->method('evaluate')
            ->willReturnCallback(function () use (&$order) {
                $order[] = 'second';

                return new AuthorizationResult(false, 'denied', 'DENIED');
            });

        $orchestrator = new AuthorizationOrchestratorService([$strategy1, $strategy2]);

        $context = $this->createContext();
        $orchestrator->authorize($context);

        $this->assertSame(['first', 'second'], $order);
    }

    public function testReturnsDefaultDenyWhenNoStrategies(): void
    {
        $orchestrator = new AuthorizationOrchestratorService([]);

        $context = $this->createContext();
        $result = $orchestrator->authorize($context);

        $this->assertFalse($result->isAllowed());
        $this->assertSame('INSUFFICIENT_PERMISSIONS', $result->getCode());
    }

    public function testFirstAllowedStrategyReturnsImmediately(): void
    {
        $strategy1 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy1->method('evaluate')
            ->willReturn(new AuthorizationResult(true, 'first allowed', 'FIRST_ALLOW'));

        $strategy2 = $this->createMock(AuthorizationStrategyInterface::class);
        $strategy2->expects($this->never())->method('evaluate');

        $orchestrator = new AuthorizationOrchestratorService([$strategy1, $strategy2]);

        $context = $this->createContext();
        $result = $orchestrator->authorize($context);

        $this->assertTrue($result->isAllowed());
        $this->assertSame('FIRST_ALLOW', $result->getCode());
    }

    private function createContext(): AuthorizationContext
    {
        return new AuthorizationContext(
            userId: 1,
            userRole: 'user',
            userPermissions: [],
            resource: 'posts',
            action: 'show',
            request: new ServerRequest('GET', new Uri('/api/v1/posts/1')),
        );
    }
}
