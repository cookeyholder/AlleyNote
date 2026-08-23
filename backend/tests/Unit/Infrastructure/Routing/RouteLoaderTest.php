<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;
use Tests\Support\UnitTestCase;

/**
 * RouteLoader 類別單元測試.
 */
class RouteLoaderTest extends UnitTestCase
{
    private string $tempDir;

    private RouteLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/routetest_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
        $this->loader = new RouteLoader(new RouteValidator());
    }

    protected function tearDown(): void
    {
        // 清理暫存檔案
        $files = glob($this->tempDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    /**
     * 測試添加不存在的路由檔案拋出例外.
     */
    public function testAddRouteFileNotFound(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由配置檔案不存在');
        $this->loader->addRouteFile($this->tempDir . '/nonexistent.php');
    }

    /**
     * 測試載入陣列格式的路由定義與統計.
     */
    public function testLoadRoutesArrayDefinition(): void
    {
        $routeFile1 = $this->tempDir . '/api.php';
        file_put_contents($routeFile1, '<?php return [
            "posts.index" => [
                "methods" => "GET",
                "path" => "/posts",
                "handler" => "PostController@index",
                "middleware" => "auth"
            ],
            "posts.store" => [
                "methods" => ["POST"],
                "path" => "/posts",
                "handler" => "PostController@store",
                "middleware" => ["auth", "csrf"]
            ]
        ];');

        $routeFile2 = $this->tempDir . '/admin.php';
        file_put_contents($routeFile2, '<?php return [
            [
                "methods" => "GET",
                "path" => "/admin/dashboard",
                "handler" => "AdminController@dashboard"
            ]
        ];');

        $router = new Router();
        $this->loader->addRouteFile($routeFile1, 'api');
        $this->loader->addRouteFile($routeFile2, 'admin');
        $this->loader->loadRoutes($router);

        $loadedRoutes = $this->loader->getLoadedRoutes();
        $this->assertCount(3, $loadedRoutes);

        $stats = $this->loader->getRouteStats();
        $this->assertEquals(3, $stats['total_routes']);
        $this->assertEquals(2, $stats['files_loaded']);
        $groups = $stats['groups'];
        $this->assertIsArray($groups);
        $this->assertEquals(2, $groups['api']);
        $this->assertEquals(1, $groups['admin']);

        // 測試根據群組篩選
        $apiRoutes = $this->loader->getRoutesByGroup('api');
        $this->assertCount(2, $apiRoutes);

        $adminRoutes = $this->loader->getRoutesByGroup('admin');
        $this->assertCount(1, $adminRoutes);

        // 測試 findRoutes
        $findResult = $this->loader->findRoutes(fn($r): bool => is_array($r) && ($r['path'] ?? null) === '/admin/dashboard');
        $this->assertCount(1, $findResult);

        // 測試 clearRoutes
        $this->loader->clearRoutes();
        $this->assertEmpty($this->loader->getLoadedRoutes());
        $clearedStats = $this->loader->getRouteStats();
        $this->assertEquals(0, $clearedStats['total_routes']);
        $this->assertEquals(0, $clearedStats['files_loaded']);
    }

    /**
     * 測試載入非陣列格式的無效路由定義拋出例外.
     */
    public function testLoadInvalidRouteConfigType(): void
    {
        $routeFile = $this->tempDir . '/invalid.php';
        file_put_contents($routeFile, '<?php return [ "route_name" => "not_an_array" ];');

        $router = new Router();
        $this->loader->addRouteFile($routeFile);

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由配置必須是陣列格式');
        $this->loader->loadRoutes($router);
    }

    /**
     * 測試載入語法錯誤的路由檔案拋出例外.
     */
    public function testLoadSyntaxErrorFile(): void
    {
        $routeFile = $this->tempDir . '/syntax_error.php';
        file_put_contents($routeFile, '<?php return [ invalid syntax here');

        $router = new Router();
        $this->loader->addRouteFile($routeFile);

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('語法錯誤');
        $this->loader->loadRoutes($router);
    }

    /**
     * 測試載入回傳 Closure 或直接呼叫 Router 的檔案.
     */
    public function testLoadCallableRouteFile(): void
    {
        $routeFile = $this->tempDir . '/callable.php';
        file_put_contents($routeFile, '<?php return function($router) {
            $router->get("/closure-route", "ClosureController@index");
        };');

        $router = new Router();
        $this->loader->addRouteFile($routeFile);
        $this->loader->loadRoutes($router);

        $this->assertEmpty($this->loader->getLoadedRoutes());
    }
}
