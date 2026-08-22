<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Core\Route;
use InvalidArgumentException;
use Mockery;
use Tests\Support\UnitTestCase;

/**
 * Route 類別單元測試.
 */
class RouteTest extends UnitTestCase
{
    /**
     * 測試建構函式與基本屬性取得方法.
     */
    public function testConstructAndGetters(): void
    {
        $handler = 'App\Controllers\PostController@index';
        $route = new Route(['GET', 'POST'], '/posts/{id}', $handler);

        $this->assertEquals(['GET', 'POST'], $route->getMethods());
        $this->assertEquals('/posts/{id}', $route->getPattern());
        $this->assertEquals($handler, $route->getHandler());
        $this->assertNull($route->getName());
        $this->assertEmpty($route->getMiddlewares());
    }

    /**
     * 測試設定與取得路由名稱.
     */
    public function testSetNameAndGetName(): void
    {
        $route = new Route(['GET'], '/posts', 'PostController@index');
        $route->setName('posts.index');

        $this->assertEquals('posts.index', $route->getName());
    }

    /**
     * 測試中介軟體新增功能（單個、多個、別名字串、陣列）.
     */
    public function testMiddlewareManagement(): void
    {
        $route = new Route(['GET'], '/posts', 'PostController@index');

        $middlewareMock = Mockery::mock(MiddlewareInterface::class);
        $route->addMiddleware($middlewareMock);

        $this->assertCount(1, $route->getMiddlewares());
        $this->assertSame($middlewareMock, $route->getMiddlewares()[0]);

        // 測試 addMiddlewares 混用字串與實例
        $middlewareMock2 = Mockery::mock(MiddlewareInterface::class);
        $route->addMiddlewares(['auth', $middlewareMock2]);

        $this->assertCount(3, $route->getMiddlewares());
        $this->assertEquals('auth', $route->getMiddlewares()[1]);
        $this->assertSame($middlewareMock2, $route->getMiddlewares()[2]);

        // 測試流暢介面 middleware()
        $route->middleware('admin');
        $this->assertCount(4, $route->getMiddlewares());

        $middlewareMock3 = Mockery::mock(MiddlewareInterface::class);
        $route->middleware($middlewareMock3);
        $this->assertCount(5, $route->getMiddlewares());

        $route->middleware(['rate_limit', 'csrf']);
        $this->assertCount(7, $route->getMiddlewares());
    }

    /**
     * 測試 HTTP 方法匹配.
     */
    public function testMatchesMethod(): void
    {
        $route = new Route(['GET', 'POST'], '/posts', 'PostController@index');

        $this->assertTrue($route->matchesMethod('GET'));
        $this->assertTrue($route->matchesMethod('get'));
        $this->assertTrue($route->matchesMethod('POST'));
        $this->assertTrue($route->matchesMethod('post'));
        $this->assertFalse($route->matchesMethod('DELETE'));
        $this->assertFalse($route->matchesMethod('PUT'));
    }

    /**
     * 測試路徑匹配與參數解析.
     */
    public function testMatchesPath(): void
    {
        $route = new Route(['GET'], '/posts/{id}/comments/{commentId}', 'PostController@comment');

        $matchResult = $route->matchesPath('/posts/123/comments/456');
        $this->assertTrue($matchResult->isMatched());
        $this->assertSame($route, $matchResult->getRoute());
        $this->assertEquals(['id' => '123', 'commentId' => '456'], $matchResult->getParameters());

        $failedResult = $route->matchesPath('/posts/123');
        $this->assertFalse($failedResult->isMatched());
        $this->assertNull($failedResult->getRoute());
        $this->assertEmpty($failedResult->getParameters());
    }

    /**
     * 測試從請求進行路由匹配.
     */
    public function testMatchesRequest(): void
    {
        $route = new Route(['GET'], '/posts/{id}', 'PostController@show');

        $matchedRequest = new ServerRequest('GET', new Uri('http://localhost/posts/99'));
        $result = $route->matches($matchedRequest);
        $this->assertTrue($result->isMatched());
        $this->assertEquals(['id' => '99'], $result->getParameters());

        $wrongMethodRequest = new ServerRequest('POST', new Uri('http://localhost/posts/99'));
        $this->assertFalse($route->matches($wrongMethodRequest)->isMatched());

        $wrongPathRequest = new ServerRequest('GET', new Uri('http://localhost/users/99'));
        $this->assertFalse($route->matches($wrongPathRequest)->isMatched());
    }

    /**
     * 測試 URL 生成與錯誤處理.
     */
    public function testGenerateUrl(): void
    {
        $route = new Route(['GET'], '/posts/{id}/tags/{tag}', 'PostController@tag');

        $url = $route->generateUrl(['id' => 10, 'tag' => 'php'], ['page' => 2, 'sort' => 'desc']);
        $this->assertEquals('/posts/10/tags/php?page=2&sort=desc', $url);

        // 缺少參數拋出例外
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('缺少必要的路由參數: tag');
        $route->generateUrl(['id' => 10]);
    }

    /**
     * 測試 URL 生成時非純量參數拋出例外.
     */
    public function testGenerateUrlNonScalarParameter(): void
    {
        $route = new Route(['GET'], '/posts/{id}', 'PostController@show');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("參數 'id' 必須是純量值");
        $route->generateUrl(['id' => ['not', 'scalar']]);
    }

    /**
     * 測試 withAttributes 複製與屬性套用.
     */
    public function testWithAttributes(): void
    {
        $route = new Route(['GET'], '/posts', 'PostController@index');
        $newRoute = $route->withAttributes([
            'name'        => 'posts.list',
            'middlewares' => ['auth', 'csrf'],
        ]);

        $this->assertNotSame($route, $newRoute);
        $this->assertEquals('posts.list', $newRoute->getName());
        $this->assertEquals(['auth', 'csrf'], $newRoute->getMiddlewares());
        $this->assertNull($route->getName());
        $this->assertEmpty($route->getMiddlewares());
    }

    /**
     * 測試 extractParameters 方法.
     */
    public function testExtractParameters(): void
    {
        $route = new Route(['GET'], '/users/{userId}/profile', 'UserController@profile');

        $params = $route->extractParameters('/users/42/profile');
        $this->assertEquals(['userId' => '42'], $params);

        $unmatchedParams = $route->extractParameters('/not/matched');
        $this->assertEmpty($unmatchedParams);
    }

    /**
     * 測試靜態快捷工廠方法.
     */
    public function testStaticFactoryMethods(): void
    {
        $get = Route::get('/get', 'Handler@get');
        $this->assertEquals(['GET'], $get->getMethods());

        $post = Route::post('/post', 'Handler@post');
        $this->assertEquals(['POST'], $post->getMethods());

        $put = Route::put('/put', 'Handler@put');
        $this->assertEquals(['PUT'], $put->getMethods());

        $patch = Route::patch('/patch', 'Handler@patch');
        $this->assertEquals(['PATCH'], $patch->getMethods());

        $delete = Route::delete('/delete', 'Handler@delete');
        $this->assertEquals(['DELETE'], $delete->getMethods());

        $any = Route::any('/any', 'Handler@any');
        $this->assertEquals(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $any->getMethods());

        $match = Route::match(['GET', 'POST'], '/match', 'Handler@match');
        $this->assertEquals(['GET', 'POST'], $match->getMethods());
    }
}
