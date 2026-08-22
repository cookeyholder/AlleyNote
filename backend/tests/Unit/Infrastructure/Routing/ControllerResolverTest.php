<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequestFactory;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Core\Route;
use DI\Container;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\UnitTestCase;

class DummyTestController
{
    /**
     * @param array<string, mixed> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($args) ?: '{}');
    }
}

class ControllerResolverTest extends UnitTestCase
{
    #[Test]
    public function resolvesArgsParameterCorrectly(): void
    {
        $container = new Container();
        $resolver = new ControllerResolver($container);

        $route = new Route(['GET'], '/posts/{id}', [DummyTestController::class, 'show']);
        $request = ServerRequestFactory::fromGlobals();
        $routeParams = ['id' => '42'];

        $response = $resolver->resolve($route, $request, $routeParams);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"id":"42"}', (string) $response->getBody());
    }
}
