<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Admin;

use App\Application\Controllers\Admin\CacheMonitorController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * CacheMonitorController 單元測試.
 */
#[CoversClass(CacheMonitorController::class)]
class CacheMonitorControllerTest extends UnitTestCase
{
    private CacheMonitorController $controller;

    private CacheMonitorInterface&MockInterface $cacheMonitor;

    private CacheManagerInterface&MockInterface $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheMonitor = Mockery::mock(CacheMonitorInterface::class);
        $this->cacheManager = Mockery::mock(CacheManagerInterface::class);

        $this->controller = new CacheMonitorController(
            $this->cacheMonitor,
            $this->cacheManager,
        );
    }

    #[Test]
    public function testGetStatsSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->cacheManager->shouldReceive('getStats')
            ->once()
            ->andReturn(['hits' => 100, 'misses' => 20]);

        $this->cacheManager->shouldReceive('getHealthStatus')
            ->once()
            ->andReturn(['redis' => 'healthy']);

        $result = $this->controller->getStats($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetStatsExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheManager->shouldReceive('getStats')
            ->once()
            ->andThrow(new Exception('Cache manager error'));

        $result = $this->controller->getStats($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetMetricsSuccessWithCustomTimeRange(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn(['timeRange' => '24h']);
        $response = $this->createMockResponse();

        $this->cacheMonitor->shouldReceive('getHitRateStats')
            ->once()
            ->with('24h')
            ->andReturn(['hitRate' => 95.5]);

        $this->cacheMonitor->shouldReceive('getDriverPerformanceComparison')
            ->once()
            ->andReturn(['redis' => 0.5]);

        $this->cacheMonitor->shouldReceive('getCacheCapacityStats')
            ->once()
            ->andReturn(['used' => 50]);

        $this->cacheMonitor->shouldReceive('getErrorStats')
            ->once()
            ->with('24h')
            ->andReturn(['errorCount' => 0]);

        $this->cacheMonitor->shouldReceive('getSlowCacheOperations')
            ->once()
            ->with(10, 100)
            ->andReturn([]);

        $result = $this->controller->getMetrics($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetMetricsDefaultTimeRange(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->cacheMonitor->shouldReceive('getHitRateStats')
            ->once()
            ->with('1h')
            ->andReturn(['hitRate' => 90.0]);

        $this->cacheMonitor->shouldReceive('getDriverPerformanceComparison')
            ->once()
            ->andReturn([]);

        $this->cacheMonitor->shouldReceive('getCacheCapacityStats')
            ->once()
            ->andReturn([]);

        $this->cacheMonitor->shouldReceive('getErrorStats')
            ->once()
            ->with('1h')
            ->andReturn([]);

        $this->cacheMonitor->shouldReceive('getSlowCacheOperations')
            ->once()
            ->with(10, 100)
            ->andReturn([]);

        $result = $this->controller->getMetrics($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetMetricsExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->cacheMonitor->shouldReceive('getHitRateStats')
            ->once()
            ->andThrow(new Exception('Monitoring error'));

        $result = $this->controller->getMetrics($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetHealthSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->cacheMonitor->shouldReceive('getHealthOverview')
            ->once()
            ->andReturn(['status' => 'healthy']);

        $this->cacheManager->shouldReceive('getHealthStatus')
            ->once()
            ->andReturn(['driver' => 'ok']);

        $result = $this->controller->getHealth($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetHealthExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheMonitor->shouldReceive('getHealthOverview')
            ->once()
            ->andThrow(new Exception('Health check failed'));

        $result = $this->controller->getHealth($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testResetStatsSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->cacheMonitor->shouldReceive('cleanup')
            ->once()
            ->with(0)
            ->andReturn(42);

        $result = $this->controller->resetStats($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testResetStatsExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheMonitor->shouldReceive('cleanup')
            ->once()
            ->with(0)
            ->andThrow(new Exception('Cleanup failed'));

        $result = $this->controller->resetStats($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testFlushCacheSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $this->cacheManager->shouldReceive('clear')
            ->once()
            ->andReturn(true);

        $result = $this->controller->flushCache($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testFlushCacheFailure(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheManager->shouldReceive('clear')
            ->once()
            ->andReturn(false);

        $result = $this->controller->flushCache($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testFlushCacheExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheManager->shouldReceive('clear')
            ->once()
            ->andThrow(new Exception('Flush failed'));

        $result = $this->controller->flushCache($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetDriverInfoSuccess(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $mockDriver = new class {
            public function isAvailable(): bool
            {
                return true;
            }
        };

        $this->cacheManager->shouldReceive('getDrivers')
            ->once()
            ->andReturn(['redis' => $mockDriver]);

        $result = $this->controller->getDriverInfo($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetDriverInfoExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $response = $this->createMockResponse(500);

        $this->cacheManager->shouldReceive('getDrivers')
            ->once()
            ->andThrow(new Exception('Driver inspection failed'));

        $result = $this->controller->getDriverInfo($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        return Mockery::mock(ServerRequestInterface::class);
    }

    private function createMockResponse(int $statusCode = 200): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnUsing(fn(string $string): int => strlen($string));

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnUsing(function ($code) use ($response, &$statusCode) {
                $statusCode = $code;

                return $response;
            });

        $response->shouldReceive('getStatusCode')
            ->andReturnUsing(fn() => $statusCode);

        return $response;
    }
}
