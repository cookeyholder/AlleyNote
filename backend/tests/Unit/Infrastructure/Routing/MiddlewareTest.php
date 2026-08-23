<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use App\Infrastructure\Routing\Middleware\AbstractMiddleware;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\Middleware\MiddlewareManager;
use App\Infrastructure\Routing\Middleware\MiddlewareResolver;
use App\Infrastructure\Routing\Middleware\NextHandlerWrapper;
use App\Infrastructure\Routing\Middleware\RouteInfoMiddleware;
use App\Infrastructure\Routing\Middleware\RouteParametersMiddleware;
use InvalidArgumentException;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tests\Support\UnitTestCase;

/**
 * 路由中介軟體系統單元測試.
 */
class MiddlewareTest extends UnitTestCase
{
    /**
     * 測試 AbstractMiddleware 基本功能.
     */
    public function testAbstractMiddleware(): void
    {
        $middleware = new class('test-mw', 10, true) extends AbstractMiddleware {
            protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request->withAttribute('executed', true));
            }
        };

        $this->assertEquals('test-mw', $middleware->getName());
        $this->assertEquals(10, $middleware->getPriority());
        $this->assertTrue($middleware->isEnabled());

        $middleware->setName('renamed-mw');
        $this->assertEquals('renamed-mw', $middleware->getName());

        $middleware->setPriority(20);
        $this->assertEquals(20, $middleware->getPriority());

        $middleware->disable();
        $this->assertFalse($middleware->isEnabled());

        $middleware->enable();
        $this->assertTrue($middleware->isEnabled());

        $request = new ServerRequest('GET', new Uri('http://localhost/test'));
        $handlerMock = Mockery::mock(RequestHandlerInterface::class);
        $responseMock = Mockery::mock(ResponseInterface::class);

        $handlerMock->shouldReceive('handle')
            ->with(Mockery::on(fn($req): bool => $req instanceof ServerRequestInterface && $req->getAttribute('executed') === true))
            ->once()
            ->andReturn($responseMock);

        $result = $middleware->process($request, $handlerMock);
        $this->assertSame($responseMock, $result);

        // 停用時直接略過
        $middleware->disable();
        $handlerMock->shouldReceive('handle')->with($request)->once()->andReturn($responseMock);
        $this->assertSame($responseMock, $middleware->process($request, $handlerMock));
    }

    /**
     * 測試 NextHandlerWrapper.
     */
    public function testNextHandlerWrapper(): void
    {
        $middlewareMock = Mockery::mock(MiddlewareInterface::class);
        $finalHandlerMock = Mockery::mock(RequestHandlerInterface::class);
        $responseMock = Mockery::mock(ResponseInterface::class);
        $request = new ServerRequest('GET', new Uri('http://localhost/test'));

        $middlewareMock->shouldReceive('process')
            ->with($request, $finalHandlerMock)
            ->once()
            ->andReturn($responseMock);

        $wrapper = new NextHandlerWrapper($middlewareMock, $finalHandlerMock);
        $this->assertSame($responseMock, $wrapper->handle($request));
    }

    /**
     * 測試 MiddlewareDispatcher.
     */
    public function testMiddlewareDispatcher(): void
    {
        $dispatcher = new MiddlewareDispatcher();
        $request = new ServerRequest('GET', new Uri('http://localhost/test'));

        $mw1 = new class('mw1', 10) extends AbstractMiddleware {
            protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $order = $request->getAttribute('order', []);
                $req = $request->withAttribute('order', array_merge(is_array($order) ? $order : [], ['mw1']));

                return $handler->handle($req);
            }
        };

        $mw2 = new class('mw2', 20) extends AbstractMiddleware {
            protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $order = $request->getAttribute('order', []);
                $req = $request->withAttribute('order', array_merge(is_array($order) ? $order : [], ['mw2']));

                return $handler->handle($req);
            }
        };

        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $order = $request->getAttribute('order', []);
                $parts = is_array($order) ? array_map(
                    static fn($value): string => is_scalar($value) ? (string) $value : '',
                    $order,
                ) : [];

                return new Response(200, ['X-Order' => implode(',', $parts)]);
            }
        };

        $response = $dispatcher->dispatch($request, [$mw1, $mw2], $finalHandler);
        $this->assertEquals('mw1,mw2', $response->getHeaderLine('X-Order'));

        // 測試 buildChain
        $chain = $dispatcher->buildChain([$mw1, $mw2], $finalHandler);
        $chainResponse = $chain->handle($request);
        $this->assertEquals('mw1,mw2', $chainResponse->getHeaderLine('X-Order'));
    }

    /**
     * 測試 MiddlewareManager (add, remove, getSorted, process, setPriorities, setStates).
     */
    public function testMiddlewareManager(): void
    {
        $dispatcher = new MiddlewareDispatcher();
        $manager = new MiddlewareManager($dispatcher);

        $mw1 = new class('mw1', 50) extends AbstractMiddleware {
            protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request->withAttribute('mw1', true));
            }
        };

        $mw2 = new class('mw2', 10) extends AbstractMiddleware {
            protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request->withAttribute('mw2', true));
            }
        };

        $manager->add($mw1);
        $this->assertEquals(1, $manager->count());
        $this->assertTrue($manager->has('mw1'));
        $this->assertSame($mw1, $manager->get('mw1'));
        $this->assertNull($manager->get('nonexistent'));

        $manager->addMultiple([$mw2, 'invalid_item']); // @phpstan-ignore argument.type (刻意混入非 MiddlewareInterface 項目以測試過濾行為)
        $this->assertEquals(2, $manager->count());
        $this->assertEquals(['mw1', 'mw2'], $manager->getNames());
        $this->assertCount(2, $manager->getAll());

        // 測試排序 (優先權小的在前)
        $sorted = $manager->getSorted();
        $this->assertSame($mw2, $sorted[0]);
        $this->assertSame($mw1, $sorted[1]);

        // 測試批次設定優先度
        $manager->setPriorities(['mw1' => 5, 'mw2' => 20]);
        $sortedAfterPriority = $manager->getSorted();
        $this->assertSame($mw1, $sortedAfterPriority[0]);
        $this->assertSame($mw2, $sortedAfterPriority[1]);

        // 測試批次啟用/停用
        $manager->setStates(['mw1' => false]);
        $this->assertFalse($mw1->isEnabled());
        $manager->setStates(['mw1' => true]);
        $this->assertTrue($mw1->isEnabled());

        // 測試 process
        $request = new ServerRequest('GET', new Uri('http://localhost/test'));
        $finalHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
        $response = $manager->process($request, $finalHandler);
        $this->assertEquals(200, $response->getStatusCode());

        // 測試 remove 與 clear
        $manager->remove('mw1');
        $this->assertFalse($manager->has('mw1'));
        $this->assertEquals(1, $manager->count());

        $manager->clear();
        $this->assertEquals(0, $manager->count());
    }

    /**
     * 測試 RouteParametersMiddleware.
     */
    public function testRouteParametersMiddleware(): void
    {
        $middleware = new RouteParametersMiddleware(['id' => 123, 'slug' => 'test-post']);
        $this->assertEquals('route-parameters', $middleware->getName());
        $this->assertEquals(-100, $middleware->getPriority());
        $this->assertEquals(['id' => 123, 'slug' => 'test-post'], $middleware->getParameters());

        $middleware->addParameter('category', 'news');
        $this->assertEquals('news', $middleware->getParameters()['category']);

        $middleware->removeParameter('slug');
        $this->assertArrayNotHasKey('slug', $middleware->getParameters());

        $middleware->setParameters(['id' => 456]);
        $this->assertEquals(['id' => 456], $middleware->getParameters());

        $request = new ServerRequest('GET', new Uri('http://localhost/test'));
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $id = $request->getAttribute('id');
                $routeParameters = $request->getAttribute('route_parameters');

                return new Response(200, [
                    'X-Param-Id' => is_scalar($id) ? (string) $id : '',
                    'X-Has-All'  => is_array($routeParameters) && isset($routeParameters['id']) ? 'yes' : 'no',
                ]);
            }
        };

        $response = $middleware->process($request, $handler);
        $this->assertEquals('456', $response->getHeaderLine('X-Param-Id'));
        $this->assertEquals('yes', $response->getHeaderLine('X-Has-All'));

        $middleware->clearParameters();
        $this->assertEmpty($middleware->getParameters());
    }

    /**
     * 測試 RouteInfoMiddleware.
     */
    public function testRouteInfoMiddleware(): void
    {
        $middleware = new RouteInfoMiddleware(
            routeName: 'posts.show',
            routePattern: '/posts/{id}',
            methods: ['GET'],
            handler: 'PostController@show',
            priority: -90,
        );

        $this->assertEquals('route-info', $middleware->getName());
        $this->assertEquals(-90, $middleware->getPriority());
        $this->assertEquals('posts.show', $middleware->getRouteName());
        $this->assertEquals('/posts/{id}', $middleware->getRoutePattern());
        $this->assertEquals(['GET'], $middleware->getMethods());
        $this->assertEquals('PostController@show', $middleware->getHandler());

        $middleware->setRouteName('posts.detail');
        $middleware->setRoutePattern('/detail/{id}');
        $middleware->setMethods(['GET', 'HEAD']);
        $middleware->setHandler('DetailController@index');

        $this->assertEquals('posts.detail', $middleware->getRouteName());
        $this->assertEquals('/detail/{id}', $middleware->getRoutePattern());
        $this->assertEquals(['GET', 'HEAD'], $middleware->getMethods());
        $this->assertEquals('DetailController@index', $middleware->getHandler());

        $request = new ServerRequest('GET', new Uri('http://localhost/test'));
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $info = $request->getAttribute('route_info');

                return new Response(200, [
                    'X-Route-Name'    => $request->getAttribute('route_name') ?? '',
                    'X-Route-Pattern' => $request->getAttribute('route_pattern') ?? '',
                    'X-Has-Info'      => is_array($info) ? 'yes' : 'no',
                ]);
            }
        };

        $response = $middleware->process($request, $handler);
        $this->assertEquals('posts.detail', $response->getHeaderLine('X-Route-Name'));
        $this->assertEquals('/detail/{id}', $response->getHeaderLine('X-Route-Pattern'));
        $this->assertEquals('yes', $response->getHeaderLine('X-Has-Info'));
    }

    /**
     * 測試 MiddlewareResolver.
     */
    public function testMiddlewareResolver(): void
    {
        $containerMock = Mockery::mock(ContainerInterface::class);
        $resolver = new MiddlewareResolver($containerMock);

        $this->assertArrayHasKey('auth', $resolver->getAliases());
        $this->assertArrayHasKey('csrf', $resolver->getAliases());

        $resolver->registerAlias('custom_alias', 'custom_target');
        $this->assertEquals('custom_target', $resolver->getAliases()['custom_alias']);

        // 解析直接實例
        $mwMock = Mockery::mock(MiddlewareInterface::class);
        $this->assertSame($mwMock, $resolver->resolve($mwMock));
        $this->assertTrue($resolver->canResolve($mwMock));

        // 解析別名透過容器
        $containerMock->shouldReceive('has')->with('jwt.auth')->andReturn(true);
        $containerMock->shouldReceive('get')->with('jwt.auth')->andReturn($mwMock);
        $this->assertSame($mwMock, $resolver->resolve('auth'));
        $this->assertTrue($resolver->canResolve('auth'));

        // 容器回傳非 MiddlewareInterface
        $containerMock->shouldReceive('has')->with('jwt.authorize')->andReturn(true);
        $containerMock->shouldReceive('get')->with('jwt.authorize')->andReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not implement MiddlewareInterface');
        $resolver->resolve('admin');
    }

    /**
     * 測試 MiddlewareResolver 非法中介軟體參數拋出例外.
     */
    public function testMiddlewareResolverInvalidTypes(): void
    {
        $containerMock = Mockery::mock(ContainerInterface::class);
        $resolver = new MiddlewareResolver($containerMock);

        $this->assertFalse($resolver->canResolve(12345)); // @phpstan-ignore argument.type (刻意傳入非法型別以測試防禦邏輯)

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware must be a string or MiddlewareInterface instance');
        $resolver->resolve(12345); // @phpstan-ignore argument.type (刻意傳入非法型別以測試例外路徑)
    }

    /**
     * 測試 MiddlewareResolver 批次解析 resolveMultiple.
     */
    public function testMiddlewareResolverResolveMultiple(): void
    {
        $containerMock = Mockery::mock(ContainerInterface::class);
        $resolver = new MiddlewareResolver($containerMock);

        $mw1 = Mockery::mock(MiddlewareInterface::class);
        $mw2 = Mockery::mock(MiddlewareInterface::class);

        $containerMock->shouldReceive('has')->with('jwt.auth')->andReturn(true);
        $containerMock->shouldReceive('get')->with('jwt.auth')->andReturn($mw1);

        $results = $resolver->resolveMultiple(['auth', $mw2]);
        $this->assertCount(2, $results);
        $this->assertSame($mw1, $results[0]);
        $this->assertSame($mw2, $results[1]);
    }
}
