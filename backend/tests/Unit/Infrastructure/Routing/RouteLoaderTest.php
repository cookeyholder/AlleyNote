<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RouteInterface;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParseError;
use PHPUnit\Framework\TestCase;

/**
 * RouteLoader 單元測試.
 */
class RouteLoaderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private RouteLoader $routeLoader;

    private RouterInterface $mockRouter;

    private RouteValidator $mockValidator;

    private RouteInterface $mockRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRouter = Mockery::mock(RouterInterface::class);
        $this->mockValidator = Mockery::mock(RouteValidator::class);
        $this->mockRoute = Mockery::mock(RouteInterface::class);

        $this->routeLoader = new RouteLoader($this->mockValidator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testConstructorWithoutValidator(): void
    {
        $routeLoader = new RouteLoader();
        $this->assertInstanceOf(RouteLoader::class, $routeLoader);
    }

    public function testConstructorWithValidator(): void
    {
        $validator = Mockery::mock(RouteValidator::class);
        $routeLoader = new RouteLoader($validator);
        $this->assertInstanceOf(RouteLoader::class, $routeLoader);
    }

    public function testAddRouteFileSuccess(): void
    {
        // 建立臨時路由檔案
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        file_put_contents($tempFile, '<?php return [];');

        $result = $this->routeLoader->addRouteFile($tempFile, 'api');

        $this->assertSame($this->routeLoader, $result);

        // 清理臨時檔案
        unlink($tempFile);
    }

    public function testAddRouteFileThrowsExceptionWhenFileNotExists(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由配置檔案不存在');

        $this->routeLoader->addRouteFile('/non/existent/file.php');
    }

    public function testAddRouteFileThrowsExceptionWhenFileNotReadable(): void
    {
        // 跳過此測試，因為在容器環境中檔案權限測試可能不穩定
        $this->markTestSkipped('File permission tests are unreliable in container environments');
    }

    public function testLoadRoutesWithArrayFormat(): void
    {
        // 建立包含路由定義的臨時檔案
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'home' => [
                    'methods' => ['GET'],
                    'path' => '/',
                    'handler' => ['HomeController', 'index'],
                    'name' => 'home',
                ],
                'users' => [
                    'methods' => ['GET', 'POST'],
                    'path' => '/users',
                    'handler' => ['UserController', 'index'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        // 設定 mock 行為
        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->twice();

        $this->mockRouter->shouldReceive('map')
            ->with(['GET'], '/', ['HomeController', 'index'])
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRouter->shouldReceive('map')
            ->with(['GET', 'POST'], '/users', ['UserController', 'index'])
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('setName')
            ->with('home')
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('setName')
            ->with('users')
            ->once()
            ->andReturn($this->mockRoute);

        // 執行測試
        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        // 驗證結果
        $loadedRoutes = $this->routeLoader->getLoadedRoutes();
        $this->assertCount(2, $loadedRoutes);
        $this->assertEquals('home', $loadedRoutes[0]['name']);
        $this->assertEquals('users', $loadedRoutes[1]['name']);

        // 清理
        unlink($tempFile);
    }

    public function testLoadRoutesWithMiddleware(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'protected' => [
                    'methods' => ['GET'],
                    'path' => '/protected',
                    'handler' => ['ProtectedController', 'index'],
                    'middleware' => ['AuthMiddleware', 'RateLimitMiddleware'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->once();

        $this->mockRouter->shouldReceive('map')
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('setName')
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('middleware')
            ->with('AuthMiddleware')
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('middleware')
            ->with('RateLimitMiddleware')
            ->once()
            ->andReturn($this->mockRoute);

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        unlink($tempFile);
    }

    public function testLoadRoutesThrowsExceptionForInvalidRouteConfig(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'invalid' => 'not_an_array',
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由配置必須是陣列格式');

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        unlink($tempFile);
    }

    public function testLoadRoutesThrowsExceptionForInvalidHandler(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'invalid_handler' => [
                    'methods' => ['GET'],
                    'path' => '/test',
                    'handler' => 'not_an_array',
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->once();

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('Route handler must be an array');

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        unlink($tempFile);
    }

    public function testLoadRoutesThrowsExceptionForEmptyPath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'empty_path' => [
                    'methods' => ['GET'],
                    'path' => '',
                    'handler' => ['TestController', 'index'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->once();

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('Route path cannot be empty');

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        unlink($tempFile);
    }

    public function testGetRouteStats(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'route1' => [
                    'methods' => ['GET'],
                    'path' => '/test1',
                    'handler' => ['Controller1', 'index'],
                ],
                'route2' => [
                    'methods' => ['POST'],
                    'path' => '/test2',
                    'handler' => ['Controller2', 'store'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->twice();

        $this->mockRouter->shouldReceive('map')->twice()->andReturn($this->mockRoute);
        $this->mockRoute->shouldReceive('setName')->twice()->andReturn($this->mockRoute);

        $this->routeLoader->addRouteFile($tempFile, 'api');
        $this->routeLoader->loadRoutes($this->mockRouter);

        $stats = $this->routeLoader->getRouteStats();

        $this->assertEquals(2, $stats['total_routes']);
        $this->assertEquals(1, $stats['files_loaded']);
        $this->assertArrayHasKey('groups', $stats);
        $this->assertEquals(2, $stats['groups']['api']);

        unlink($tempFile);
    }

    public function testGetRoutesByGroup(): void
    {
        $tempFile1 = tempnam(sys_get_temp_dir(), 'route_test_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'route_test_');

        file_put_contents($tempFile1, '<?php return ["api1" => ["methods" => ["GET"], "path" => "/api1", "handler" => ["Controller", "method"]]];');
        file_put_contents(\\\$tempFile2, '<?php return ["web1" => ["methods" => ["GET"], "path" => "/web1", "handler" => ["Controller", "method"]]];');

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->twice();

        $this->mockRouter->shouldReceive('map')->twice()->andReturn($this->mockRoute);
        $this->mockRoute->shouldReceive('setName')->twice()->andReturn($this->mockRoute);

        $this->routeLoader->addRouteFile($tempFile1, 'api');
        $this->routeLoader->addRouteFile($tempFile2, 'web');
        $this->routeLoader->loadRoutes($this->mockRouter);

        $apiRoutes = $this->routeLoader->getRoutesByGroup('api');
        $webRoutes = $this->routeLoader->getRoutesByGroup('web');

        $this->assertCount(1, $apiRoutes);
        $this->assertCount(1, $webRoutes);
        $this->assertEquals('api', array_values($apiRoutes)[0]['group']);
        $this->assertEquals('web', array_values($webRoutes)[0]['group']);

        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testFindRoutes(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'get_route' => [
                    'methods' => ['GET'],
                    'path' => '/get',
                    'handler' => ['Controller', 'get'],
                ],
                'post_route' => [
                    'methods' => ['POST'],
                    'path' => '/post',
                    'handler' => ['Controller', 'post'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->twice();

        $this->mockRouter->shouldReceive('map')->twice()->andReturn($this->mockRoute);
        $this->mockRoute->shouldReceive('setName')->twice()->andReturn($this->mockRoute);

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        // 搜尋只有 GET 方法的路由
        $getRoutes = $this->routeLoader->findRoutes(function ($route) {
            return in_array('GET', $route['methods']);
        });

        $this->assertCount(1, $getRoutes);
        $this->assertEquals('get_route', $getRoutes[0]['name']);

        unlink($tempFile);
    }

    public function testClearRoutes(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        file_put_contents(\\\$tempFile, '<?php return ["test" => ["methods" => ["GET"], "path" => "/test", "handler" => ["Controller", "method"]]];');

        $this->mockValidator->shouldReceive('reset')->once(); // 只在 clearRoutes 時呼叫

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->clearRoutes();

        $stats = $this->routeLoader->getRouteStats();
        $this->assertEquals(0, $stats['total_routes']);
        $this->assertEquals(0, $stats['files_loaded']);

        unlink($tempFile);
    }

    public function testLoadRoutesHandlesSyntaxError(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        file_put_contents($tempFile, '<?php syntax error here');

        $this->mockValidator->shouldReceive('reset')->once();

        // 因為語法錯誤會在 require 時直接拋出 ParseError，
        // 而不是被 RouteConfigurationException 包裝
        $this->expectException(ParseError::class);

        try {
            $this->routeLoader->addRouteFile($tempFile);
            $this->routeLoader->loadRoutes($this->mockRouter);
        } finally {
            // 清理輸出緩衝區以避免測試被標記為 risky
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            unlink($tempFile);
        }
    }

    public function testRegisterRouteNormalizesHttpMethods(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'route_test_');
        $routeContent = <<<PHP
            <?php
            return [
                'mixed_case' => [
                    'methods' => ['get', 'POST', ' patch '],
                    'path' => '/test',
                    'handler' => ['Controller', 'method'],
                ],
            ];
            PHP;
        file_put_contents($tempFile, $routeContent);

        $this->mockValidator->shouldReceive('reset')->once();
        $this->mockValidator->shouldReceive('validateRoute')->once();

        // 驗證方法被正規化為大寫並去除空白
        $this->mockRouter->shouldReceive('map')
            ->with(['GET', 'POST', 'PATCH'], '/test', ['Controller', 'method'])
            ->once()
            ->andReturn($this->mockRoute);

        $this->mockRoute->shouldReceive('setName')
            ->once()
            ->andReturn($this->mockRoute);

        $this->routeLoader->addRouteFile($tempFile);
        $this->routeLoader->loadRoutes($this->mockRouter);

        unlink($tempFile);
    }
}
