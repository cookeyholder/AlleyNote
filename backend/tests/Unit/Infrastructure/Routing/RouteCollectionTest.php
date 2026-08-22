<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use Tests\Support\UnitTestCase;

/**
 * RouteCollection 類別單元測試.
 */
class RouteCollectionTest extends UnitTestCase
{
    private RouteCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new RouteCollection();
    }

    /**
     * 測試新增路由與方法索引、命名索引.
     */
    public function testAddAndIndexes(): void
    {
        $route1 = new Route(['GET', 'HEAD'], '/posts', 'PostController@index');
        $route1->setName('posts.index');

        $route2 = new Route(['POST'], '/posts', 'PostController@store');

        $this->collection->add($route1);
        $this->collection->add($route2);

        $this->assertSame($route1, $this->collection->getByName('posts.index'));
        $this->assertNull($this->collection->getByName('unknown'));
        $this->assertTrue($this->collection->has('posts.index'));
        $this->assertFalse($this->collection->has('unknown'));

        $this->assertCount(2, $this->collection->all());
        $this->assertEquals(2, $this->collection->count());

        $getRoutes = $this->collection->getByMethod('GET');
        $this->assertCount(1, $getRoutes);
        $this->assertSame($route1, $getRoutes[0]);

        $headRoutes = $this->collection->getByMethod('head');
        $this->assertCount(1, $headRoutes);
        $this->assertSame($route1, $headRoutes[0]);

        $postRoutes = $this->collection->getByMethod('POST');
        $this->assertCount(1, $postRoutes);
        $this->assertSame($route2, $postRoutes[0]);

        $deleteRoutes = $this->collection->getByMethod('DELETE');
        $this->assertEmpty($deleteRoutes);
    }

    /**
     * 測試批次新增路由 addRoutes.
     */
    public function testAddRoutes(): void
    {
        $route1 = new Route(['GET'], '/a', 'Controller@a');
        $route2 = new Route(['GET'], '/b', 'Controller@b');

        $this->collection->addRoutes([$route1, $route2, 'invalid_route_item']);
        $this->assertEquals(2, $this->collection->count());
    }

    /**
     * 測試根據 HTTP 請求匹配路由.
     */
    public function testMatch(): void
    {
        $route = new Route(['GET'], '/users/{id}', 'UserController@show');
        $this->collection->add($route);

        $matchedRequest = new ServerRequest('GET', new Uri('http://localhost/users/123'));
        $this->assertSame($route, $this->collection->match($matchedRequest));

        $unmatchedRequest = new ServerRequest('GET', new Uri('http://localhost/not-found'));
        $this->assertNull($this->collection->match($unmatchedRequest));

        $unmatchedMethodRequest = new ServerRequest('POST', new Uri('http://localhost/users/123'));
        $this->assertNull($this->collection->match($unmatchedMethodRequest));
    }

    /**
     * 測試移除命名路由.
     */
    public function testRemove(): void
    {
        $route = new Route(['GET', 'POST'], '/posts', 'PostController@index');
        $route->setName('posts');
        $this->collection->add($route);

        $this->assertTrue($this->collection->has('posts'));
        $this->assertTrue($this->collection->remove('posts'));
        $this->assertFalse($this->collection->has('posts'));
        $this->assertEmpty($this->collection->all());
        $this->assertEmpty($this->collection->getByMethod('GET'));
        $this->assertEmpty($this->collection->getByMethod('POST'));

        // 移除不存在的路由回傳 false
        $this->assertFalse($this->collection->remove('nonexistent'));
    }

    /**
     * 測試清空所有路由 clear.
     */
    public function testClear(): void
    {
        $route = new Route(['GET'], '/posts', 'PostController@index');
        $route->setName('posts');
        $this->collection->add($route);

        $this->collection->clear();
        $this->assertEquals(0, $this->collection->count());
        $this->assertFalse($this->collection->has('posts'));
        $this->assertEmpty($this->collection->all());
        $this->assertEmpty($this->collection->getByMethod('GET'));
    }

    /**
     * 測試序列化與反序列化 toArray 與 fromArray.
     */
    public function testToArrayAndFromArray(): void
    {
        $route1 = new Route(['GET'], '/posts', 'PostController@index');
        $route1->setName('posts.index');
        $route1->middleware('auth');

        $route2 = new Route(['POST'], '/posts', ['PostController', 'store']);
        $route2->setName('posts.store');

        $closure = static fn() => 'hello';
        $route3 = new Route(['PUT'], '/closure', $closure);

        $this->collection->add($route1);
        $this->collection->add($route2);
        $this->collection->add($route3);

        $array = $this->collection->toArray();
        $this->assertCount(3, $array);

        $this->assertEquals([
            'methods'    => ['GET'],
            'pattern'    => '/posts',
            'handler'    => 'PostController@index',
            'name'       => 'posts.index',
            'middleware' => ['auth'],
        ], $array[0]);

        $this->assertEquals(['PostController', 'store'], $array[1]['handler']);
        $this->assertEquals('callable', $array[2]['handler']);

        // 從陣列重建
        $restoredCollection = RouteCollection::fromArray($array);
        $this->assertEquals(3, $restoredCollection->count());
        $this->assertTrue($restoredCollection->has('posts.index'));
        $this->assertTrue($restoredCollection->has('posts.store'));
        $this->assertEquals(['auth'], $restoredCollection->getByName('posts.index')->getMiddlewares());
    }
}
