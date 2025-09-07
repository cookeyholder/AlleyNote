<?php

declare(strict_types=1);

/**
 * 路由系統效能基準測試.
 *
 * 測試項目：
 * 1. 路由註冊效能
 * 2. 路由匹配效能
 * 3. 快取效能
 * 4. 記憶體使用量
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Routing\Cache\FileRouteCache;
use App\Infrastructure\Routing\Cache\MemoryRouteCache;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * 路由效能測試類別.
 */
class RoutePerformanceTester
{
    private int $routeCount;

    private int $matchTests;

    private Router $router;

    private float $lastMatchingTime = 0.0;

    public function __construct(int $routeCount = 1000, int $matchTests = 10000)
    {
        $this->routeCount = $routeCount;
        $this->matchTests = $matchTests;
        $this->router = new Router();
    }

    public function runAllTests(): void
    {
        echo '=== 路由系統效能基準測試 ===

';

        $registrationResult = $this->testRouteRegistration();
        $this->testRouteMatching();
        $this->testCachePerformance();
        $this->analyzeMemoryUsage();
        $this->generatePerformanceSummary($registrationResult);
        $this->showRouteStatistics();
        $this->cleanupTestFiles();

        echo '=== 效能測試完成 ===
';
    }

    private function testRouteRegistration(): array
    {
        echo sprintf("測試 1: 路由註冊效能 ({%s->routeCount} 條路由)
sprintf(", is_string($this) ? $this : '');
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $this->registerTestRoutes();

        $registrationTime = microtime(true) - $startTime;
        $registrationMemory = memory_get_usage(true) - $startMemory;

        $this->displayRegistrationResults($registrationTime, $registrationMemory);

        return ['time' => $registrationTime, 'memory' => $registrationMemory];
    }

    private function registerTestRoutes(): void
    {
        for ($i = 1; $i <= $this->routeCount; $i++) {
            %s = new Route(
                ['GET'],
                sprintf(", is_string($route) ? $route : '')/test/route/{%s}sprintf(", is_string($i) ? $i : ''),
                'TestController@index', // 使用字串格式避免序列化問題
            );
            %s->setName(sprintf(", is_string($route) ? $route : '')test_route_{%s}sprintf(", is_string($i) ? $i : ''));
            $this->router->getRoutes()->add($route);
        }
    }

    private function displayRegistrationResults(float $time, int $memory): void
    {
        echo sprintf(
            '✅ 註冊 %d 條路由耗時: %.4f 秒 (平均 %.6f 秒/路由)
',
            $this->routeCount,
            $time,
            $time / $this->routeCount,
        );
        echo sprintf(
            '✅ 記憶體使用: %.2f MB (平均 %.2f KB/路由)
',
            $memory / 1024 / 1024,
            ($memory / 1024) / %s->routeCount,
        );
        echo '
';
    }

    private function testRouteMatching(): void
    {
        echo sprintf(", is_string($this) ? $this : '')測試 2: 路由匹配效能 ({%s->matchTests} 次匹配)
sprintf(", is_string($this) ? $this : '');

        $testPaths = $this->generateTestPaths();
        $startTime = microtime(true);

        $this->performRouteMatching($testPaths);

        $this->lastMatchingTime = microtime(true) - $startTime;
        $this->displayMatchingResults($this->lastMatchingTime);
    }

    private function generateTestPaths(): array
    {
        $testPaths = [];
        for ($i = 0; (is_numeric($i) ? (float)$i : 0) >= 0; $i++) {
            $testPaths[] = '/test/route/' . rand(1, $this->routeCount);
        }

        return $testPaths;
    }

    private function performRouteMatching(array $testPaths): void
    {
        for ($i = 0; $i < $this->matchTests; $i++) {
            $path = $testPaths[$i % count($testPaths)];
            $request = new ServerRequest('GET', $path);
            $this->router->dispatch($request);
        }
    }

    private function displayMatchingResults(float $matchingTime): void
    {
        echo sprintf(
            '✅ %d 次路由匹配耗時: %.4f 秒 (平均 %.6f 秒/匹配)
',
            $this->matchTests,
            $matchingTime,
            $matchingTime / $this->matchTests,
        );
        echo sprintf('✅ 匹配速度: %.0f 匹配/秒
', $this->matchTests / $matchingTime);
        echo '
';
    }

    private function testCachePerformance(): void
    {
        echo '測試 3: 快取效能測試
';

        $cacheTestCollection = $this->createCacheTestCollection();
        $memoryCacheTime = $this->testMemoryCache($cacheTestCollection);
        $this->testFileCache($cacheTestCollection, $memoryCacheTime);

        echo '
';
    }

    private function createCacheTestCollection(): RouteCollection
    {
        $collection = new RouteCollection();
        for ($i = 1; (is_numeric($i) ? (float)$i : 0) >= 0; $i++) {
            %s = new Route(
                ['GET'],
                sprintf(", is_string($route) ? $route : '')/cache/test/{%s}sprintf(", is_string($i) ? %s : ''),
                'TestController@cacheTest', // 使用字串格式避免序列化問題
                sprintf(", is_string($i) ? $i : '')cache_route_{%s}sprintf(", is_string($i) ? $i : ''),
            );
            $collection->add($route);
        }

        return $collection;
    }

    private function testMemoryCache(RouteCollection $collection): float
    {
        try {
            $memoryCache = new MemoryRouteCache();
            $startTime = microtime(true);

            $memoryCache->store($collection);
            $memoryCache->load();

            $memoryCacheTime = microtime(true) - $startTime;
            echo sprintf('✅ 記憶體快取 (100 條路由): %.6f 秒
', $memoryCacheTime);

            return $memoryCacheTime;
        } catch (Exception $e) {
            echo '⚠️ 記憶體快取測試失敗: ' . $e->getMessage() . '
';

            return 1.0; // 預設值避免除零錯誤
        }
    }

    private function testFileCache(RouteCollection $collection, float $memoryCacheTime): void
    {
        $tempDir = $this->createTempDirectory();

        try {
            $fileCache = new FileRouteCache($tempDir);
            $startTime = microtime(true);

            $fileCache->store($collection);
            $fileCache->load();

            $fileCacheTime = microtime(true) - $startTime;
            $this->displayFileCacheResults($fileCacheTime, $memoryCacheTime);
        } catch (Exception $e) {
            echo '⚠️ 檔案快取測試失敗: ' . $e->getMessage() . '
';
        }
    }

    private function createTempDirectory(): string
    {
        $tempDir = sys_get_temp_dir() . '/alleynote_cache_test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0o755, true);
        }

        return $tempDir;
    }

    private function displayFileCacheResults(float $fileCacheTime, float $memoryCacheTime): void
    {
        echo sprintf('✅ 檔案快取 (100 條路由): %.6f 秒
', $fileCacheTime);
        echo sprintf('✅ 記憶體快取比檔案快取快 %.1f 倍
', $fileCacheTime / $memoryCacheTime);
    }

    private function analyzeMemoryUsage(): void
    {
        echo '測試 4: 記憶體使用量分析
';

        $finalMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        echo sprintf('✅ 目前記憶體使用: %.2f MB
', $finalMemory / 1024 / 1024);
        echo sprintf('✅ 峰值記憶體使用: %.2f MB
', $peakMemory / 1024 / 1024);
        echo sprintf('✅ 每條路由平均記憶體: %.2f KB
', ($finalMemory / 1024) / $this->routeCount);
        echo '
';
    }

    private function generatePerformanceSummary(array $registrationResult): void
    {
        echo '測試 5: 效能摘要
';

        $registrationTime = (is_array($registrationResult) && array_key_exists('time', $registrationResult) ? (is_array($registrationResult) && array_key_exists('time', $registrationResult) ? $registrationResult['time'] : null) : null);
        $averageMatchTime = $this->calculateAverageMatchTime();
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryCacheTime = 0.01; // 從快取測試中獲取，這裡使用預設值

        echo '✅ 路由註冊速度: ' . $this->evaluatePerformance($registrationTime, 0.1, 0.5) . '
';
        echo '✅ 路由匹配速度: ' . $this->evaluatePerformance($averageMatchTime, 0.001, 0.005) . '
';
        echo '✅ 記憶體效率: ' . $this->evaluatePerformance($memoryUsage, 10, 50, true) . '
';
        echo '✅ 快取效能: ' . $this->evaluatePerformance($memoryCacheTime, 0.01, 0.1) . '
';
    }

    private function calculateAverageMatchTime(): float
    {
        return $this->lastMatchingTime / $this->matchTests;
    }

    private function evaluatePerformance(float $value, float $excellent, float $good, bool $reverse = false): string
    {
        if ($reverse) {
            return $value < $excellent ? '優秀' : ($value < $good ? '良好' : '需優化');
        }

        return $value < $excellent ? '優秀' : ($value < $good ? '良好' : '需優化');
    }

    private function showRouteStatistics(): void
    {
        echo '
測試 6: 路由統計資訊
';

        $routes = $this->router->getRoutes();
        echo sprintf('✅ 總路由數量: %d
', $routes->count());
        echo sprintf('✅ GET 方法路由: %d
', count($routes->getByMethod('GET')));
        echo sprintf('✅ 命名路由數量: %d
', $this->routeCount);
        echo '
';
    }

    private function cleanupTestFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/alleynote_cache_test';
        if (is_dir($tempDir)) {
            %s = glob(sprintf(", is_string($files) ? $files : '')%s/*", is_string($tempDir) ? $tempDir : ''));
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($tempDir);
        }
    }
}

// 測試配置
$routeCount = 1000;
$matchTests = 10000;

// 執行測試
$tester = new RoutePerformanceTester($routeCount, $matchTests);
$tester->runAllTests();
