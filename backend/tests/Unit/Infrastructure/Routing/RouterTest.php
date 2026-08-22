<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\MiddlewareManagerInterface;
use App\Infrastructure\Routing\Contracts\RouteCacheInterface;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;
use InvalidArgumentException;
use Mockery;
use Tests\Support\UnitTestCase;

/**
 * Router 類別單元測試.
 */
class RouterTest extends UnitTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    /**
     * 測試各 HTTP 方法的捷徑方法.
     */
    public function testHttpShortcutMethods(): void
    {
        $getRoute = $this->router->get('/users', 'UserController@index');
        $this->assertEquals(['GET'], $getRoute->getMethods());
        $this->assertEquals('/users', $getRoute->getPattern());

        $postRoute = $this->router->post('/users', 'UserController@store');
        $this->assertEquals(['POST'], $postRoute->getMethods());

        $putRoute = $this->router->put('/users/{id}', 'UserController@update');
        $this->assertEquals(['PUT'], $putRoute->getMethods());

        $patchRoute = $this->router->patch('/users/{id}', 'UserController@patch');
        $this->assertEquals(['PATCH'], $patchRoute->getMethods());

        $deleteRoute = $this->router->delete('/users/{id}', 'UserController@destroy');
        $this->assertEquals(['DELETE'], $deleteRoute->getMethods());

        $optionsRoute = $this->router->options('/users', 'UserController@options');
        $this->assertEquals(['OPTIONS'], $optionsRoute->getMethods());

        $anyRoute = $this->router->any('/any', 'AnyController@handle');
        $this->assertEquals(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $anyRoute->getMethods());

        $this->assertCount(7, $this->router->getRoutes()->all());
    }

    /**
     * 測試群組路由（包含巢狀前綴、中介軟體、命名空間與其他屬性）.
     */
    public function testRouteGroups(): void
    {
        $this->router->group([
            'prefix'     => '/api/v1',
            'namespace'  => 'App\Api\V1',
            'middleware' => ['auth'],
            'custom'     => 'value',
        ], function (Router $router) {
            $router->get('/posts', ['PostController', 'index'])->setName('api.v1.posts.index');

            // 巢狀群組
            $router->group([
                'prefix'     => 'admin',
                'namespace'  => 'Admin',
                'middleware' => 'role:admin',
            ], function (Router $nestedRouter) {
                $nestedRouter->post('/settings', ['SettingController', 'update']);
            });
        });

        $routes = $this->router->getRoutes()->all();
        $this->assertCount(2, $routes);

        // 檢查第一條路由
        $postRoute = $routes[0];
        $this->assertEquals('/api/v1/posts', $postRoute->getPattern());
        $this->assertEquals(['App\Api\V1\PostController', 'index'], $postRoute->getHandler());
        $this->assertEquals(['auth'], $postRoute->getMiddlewares());
        $this->assertEquals('api.v1.posts.index', $postRoute->getName());

        // 檢查巢狀路由
        $nestedRoute = $routes[1];
        $this->assertEquals('/api/v1/admin/settings', $nestedRoute->getPattern());
        $this->assertEquals(['App\Api\V1\Admin\SettingController', 'update'], $nestedRoute->getHandler());
        $this->assertEquals(['auth', 'role:admin'], $nestedRoute->getMiddlewares());
    }

    /**
     * 測試群組空前綴邊界情況.
     */
    public function testRouteGroupEmptyPrefix(): void
    {
        $this->router->group(['prefix' => ''], function (Router $router) {
            $router->get('/home', 'HomeController@index');
        });

        $this->router->group(['prefix' => '/api'], function (Router $router) {
            $router->get('', 'ApiController@index');
        });

        $routes = $this->router->getRoutes()->all();
        $this->assertEquals('/home', $routes[0]->getPattern());
        $this->assertEquals('/api', $routes[1]->getPattern());
    }

    /**
     * 測試路由分派成功與失敗.
     */
    public function testDispatch(): void
    {
        $this->router->get('/posts/{id}', 'PostController@show');

        // 匹配成功
        $request = new ServerRequest('GET', new Uri('http://localhost/posts/100'));
        $result = $this->router->dispatch($request);
        $this->assertTrue($result->isMatched());
        $this->assertNotNull($result->getRoute());
        $this->assertEquals(['id' => '100'], $result->getParameters());

        // 匹配失敗
        $failedRequest = new ServerRequest('GET', new Uri('http://localhost/unknown'));
        $failedResult = $this->router->dispatch($failedRequest);
        $this->assertFalse($failedResult->isMatched());
        $this->assertEquals('No route matched the request', $failedResult->getError());
    }

    /**
     * 測試分派時從快取載入路由.
     */
    public function testDispatchWithValidCache(): void
    {
        $cachedCollection = new RouteCollection();
        $cachedCollection->add(new Route(['GET'], '/cached-path', 'CachedController@index'));

        $cacheMock = Mockery::mock(RouteCacheInterface::class);
        $cacheMock->shouldReceive('isValid')->andReturn(true);
        $cacheMock->shouldReceive('load')->andReturn($cachedCollection);

        $this->router->setCache($cacheMock);
        $this->assertSame($cacheMock, $this->router->getCache());

        $request = new ServerRequest('GET', new Uri('http://localhost/cached-path'));
        $result = $this->router->dispatch($request);

        $this->assertTrue($result->isMatched());
        $this->assertEquals('/cached-path', $result->getRoute()->getPattern());
    }

    /**
     * 測試快取有效但 load 回傳 null.
     */
    public function testDispatchWithNullFromCache(): void
    {
        $this->router->get('/local-path', 'LocalController@index');

        $cacheMock = Mockery::mock(RouteCacheInterface::class);
        $cacheMock->shouldReceive('isValid')->andReturn(true);
        $cacheMock->shouldReceive('load')->andReturn(null);

        $this->router->setCache($cacheMock);

        $request = new ServerRequest('GET', new Uri('http://localhost/local-path'));
        $result = $this->router->dispatch($request);

        $this->assertTrue($result->isMatched());
    }

    /**
     * 測試快取儲存方法 cacheRoutes.
     */
    public function testCacheRoutes(): void
    {
        $this->assertFalse($this->router->cacheRoutes());

        $cacheMock = Mockery::mock(RouteCacheInterface::class);
        $cacheMock->shouldReceive('store')->with(Mockery::type(RouteCollection::class))->andReturn(true);

        $this->router->setCache($cacheMock);
        $this->assertTrue($this->router->cacheRoutes());
    }

    /**
     * 測試 url 生成方法與例外.
     */
    public function testUrlGeneration(): void
    {
        $route = $this->router->get('/posts/{category}/{id}', 'PostController@show');
        $route->setName('posts.show');

        $url = $this->router->url('posts.show', ['category' => 'tech', 'id' => 42]);
        $this->assertEquals('/posts/tech/42', $url);

        // 路由不存在
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Route named 'nonexistent' not found");
        $this->router->url('nonexistent');
    }

    /**
     * 測試 url 生成時缺少參數拋出例外.
     */
    public function testUrlGenerationMissingParams(): void
    {
        $route = $this->router->get('/posts/{id}', 'PostController@show');
        $route->setName('posts.id');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameters for route 'posts.id'");
        $this->router->url('posts.id', []);
    }

    /**
     * 測試中介軟體管理器與全域中介軟體加入.
     */
    public function testMiddlewareManagerIntegration(): void
    {
        $middlewareManagerMock = Mockery::mock(MiddlewareManagerInterface::class);
        $middlewareMock = Mockery::mock(MiddlewareInterface::class);
        $middlewareMock2 = Mockery::mock(MiddlewareInterface::class);

        $middlewareManagerMock->shouldReceive('add')->with($middlewareMock)->once();
        $middlewareManagerMock->shouldReceive('addMultiple')->with([$middlewareMock, $middlewareMock2])->once();

        $this->router->setMiddlewareManager($middlewareManagerMock);
        $this->assertSame($middlewareManagerMock, $this->router->getMiddlewareManager());

        $this->router->addGlobalMiddleware($middlewareMock);
        $this->router->addGlobalMiddlewares([$middlewareMock, $middlewareMock2]);
    }
}
