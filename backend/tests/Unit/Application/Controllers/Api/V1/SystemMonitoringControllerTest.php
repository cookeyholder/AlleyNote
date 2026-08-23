<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\SystemMonitoringController;
use App\Domains\Statistics\Contracts\SystemMonitoringServiceInterface;
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
 * SystemMonitoringController 單元測試.
 */
#[CoversClass(SystemMonitoringController::class)]
class SystemMonitoringControllerTest extends UnitTestCase
{
    private SystemMonitoringController $controller;

    private SystemMonitoringServiceInterface&MockInterface $systemMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->systemMonitoringService = Mockery::mock(SystemMonitoringServiceInterface::class);
        $this->controller = new SystemMonitoringController(
            $this->systemMonitoringService,
        );
    }

    #[Test]
    public function testGetSystemStatusSuccessWithAdminRole(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $response = $this->createMockResponse();

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andReturn([
                'cpu'    => ['usage' => 15.5],
                'memory' => ['used' => 128],
            ]);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusSuccessWithSuperAdminRole(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('super_admin');
        $response = $this->createMockResponse();

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andReturn(['status' => 'healthy']);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusSuccessWithWildcardPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('operator');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['*']);
        $response = $this->createMockResponse();

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andReturn(['status' => 'healthy']);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusSuccessWithAdminWildcardPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('operator');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['admin.*']);
        $response = $this->createMockResponse();

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andReturn(['status' => 'healthy']);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusSuccessWithStatisticsAdminPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('operator');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['statistics.admin']);
        $response = $this->createMockResponse();

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andReturn(['status' => 'healthy']);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusPermissionDenied(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('guest');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['posts.view']);
        $response = $this->createMockResponse(403);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(403, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusNonArrayPermissions(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('guest');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn('invalid_permissions');
        $response = $this->createMockResponse(403);

        $result = $this->controller->getSystemStatus($request, $response);

        $this->assertEquals(403, $result->getStatusCode());
    }

    #[Test]
    public function testGetSystemStatusServiceExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $response = $this->createMockResponse(500);

        $this->systemMonitoringService
            ->shouldReceive('getSystemHealthStatus')
            ->once()
            ->andThrow(new Exception('Monitoring probe failed'));

        $result = $this->controller->getSystemStatus($request, $response);

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
