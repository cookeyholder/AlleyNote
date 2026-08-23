<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\ClosureRequestHandler;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\Middleware\MiddlewareResolver;
use App\Infrastructure\Routing\Providers\RoutingServiceProvider;
use App\Infrastructure\Routing\RouteDispatcher;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use stdClass;
use Tests\Support\UnitTestCase;

/**
 * ClosureRequestHandler, RouteConfigurationException, RoutingServiceProvider 單元測試.
 */
class ClosureRequestHandlerTest extends UnitTestCase
{
    /**
     * 測試 ClosureRequestHandler.
     */
    public function testClosureRequestHandler(): void
    {
        $handler = new ClosureRequestHandler(function (ServerRequestInterface $request): ResponseInterface {
            return new Response(201, ['X-Custom' => 'hello'], 'created content');
        });

        $request = new ServerRequest('GET', new Uri('http://localhost/test'));
        $response = $handler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('hello', $response->getHeaderLine('X-Custom'));
        $this->assertEquals('created content', (string) $response->getBody());
    }

    /**
     * 測試 RouteConfigurationException 各工廠方法.
     */
    public function testRouteConfigurationException(): void
    {
        $e1 = RouteConfigurationException::fileNotFound('/path/to/file.php');
        $this->assertStringContainsString('路由配置檔案不存在: /path/to/file.php', $e1->getMessage());

        $e2 = RouteConfigurationException::unreadableFile('/path/to/file.php');
        $this->assertStringContainsString('無法讀取路由配置檔案: /path/to/file.php', $e2->getMessage());

        $e3 = RouteConfigurationException::invalidRouteDefinition('posts.index', '缺少參數');
        $this->assertStringContainsString("路由 'posts.index' 定義無效: 缺少參數", $e3->getMessage());

        $e4 = RouteConfigurationException::duplicateRoute('GET', '/posts');
        $this->assertStringContainsString('重複的路由定義: GET /posts', $e4->getMessage());

        $e5 = RouteConfigurationException::invalidHandler('users.show', new stdClass());
        $this->assertStringContainsString("路由 'users.show' 的處理器無效: stdClass", $e5->getMessage());

        $e6 = RouteConfigurationException::invalidHandler('users.show', 'invalid_string');
        $this->assertStringContainsString("路由 'users.show' 的處理器無效: string", $e6->getMessage());

        $e7 = RouteConfigurationException::syntaxError('/path/to/routes.php', 'unexpected token');
        $this->assertStringContainsString('路由配置檔案語法錯誤 (/path/to/routes.php): unexpected token', $e7->getMessage());
    }

    /**
     * 測試 RoutingServiceProvider 定義與工廠方法.
     */
    public function testRoutingServiceProvider(): void
    {
        $definitions = RoutingServiceProvider::getDefinitions();
        $this->assertArrayHasKey(Router::class, $definitions);
        $this->assertArrayHasKey(RouteValidator::class, $definitions);
        $this->assertArrayHasKey(RouteLoader::class, $definitions);
        $this->assertArrayHasKey(ControllerResolver::class, $definitions);
        $this->assertArrayHasKey(MiddlewareResolver::class, $definitions);
        $this->assertArrayHasKey(MiddlewareDispatcher::class, $definitions);
        $this->assertArrayHasKey(RouteDispatcher::class, $definitions);

        $routeFiles = RoutingServiceProvider::getRouteFiles();
        $this->assertArrayHasKey('api', $routeFiles);
        $this->assertArrayHasKey('web', $routeFiles);
        $this->assertArrayHasKey('auth', $routeFiles);

        // 測試工廠方法
        $containerMock = Mockery::mock(ContainerInterface::class);

        // 建立 RouteLoader
        $validator = new RouteValidator();
        $containerMock->shouldReceive('get')->with(RouteValidator::class)->andReturn($validator);
        $loader = RoutingServiceProvider::createRouteLoader($containerMock);
        $this->assertInstanceOf(RouteLoader::class, $loader);

        // 建立 ControllerResolver
        $controllerResolver = RoutingServiceProvider::createControllerResolver($containerMock);
        $this->assertInstanceOf(ControllerResolver::class, $controllerResolver);

        // 建立 MiddlewareResolver
        $middlewareResolver = RoutingServiceProvider::createMiddlewareResolver($containerMock);
        $this->assertInstanceOf(MiddlewareResolver::class, $middlewareResolver);

        // 建立 RouteDispatcher
        $router = new Router();
        $middlewareDispatcher = new MiddlewareDispatcher();
        $containerMock->shouldReceive('get')->with(RouterInterface::class)->andReturn($router);
        $containerMock->shouldReceive('get')->with(ControllerResolver::class)->andReturn($controllerResolver);
        $containerMock->shouldReceive('get')->with(MiddlewareDispatcher::class)->andReturn($middlewareDispatcher);
        $containerMock->shouldReceive('get')->with(MiddlewareResolver::class)->andReturn($middlewareResolver);

        $routeDispatcher = RoutingServiceProvider::createRouteDispatcher($containerMock);
        $this->assertInstanceOf(RouteDispatcher::class, $routeDispatcher);

        // 測試 getRoutingStats
        $containerMock->shouldReceive('get')->with(RouteLoader::class)->once()->andReturn($loader);
        $stats = RoutingServiceProvider::getRoutingStats($containerMock);
        $this->assertArrayHasKey('total_routes', $stats);

        // 測試 getRoutingStats 發生例外
        $containerMock->shouldReceive('get')->with(RouteLoader::class)->once()->andThrow(new RuntimeException('Stats error'));
        $errorStats = RoutingServiceProvider::getRoutingStats($containerMock);
        $this->assertArrayHasKey('error', $errorStats);
    }
}
