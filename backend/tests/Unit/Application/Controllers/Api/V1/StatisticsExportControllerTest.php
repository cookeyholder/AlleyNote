<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\StatisticsExportController;
use App\Domains\Statistics\Services\StatisticsExportService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * StatisticsExportController 單元測試.
 */
#[CoversClass(StatisticsExportController::class)]
class StatisticsExportControllerTest extends UnitTestCase
{
    private StatisticsExportController $controller;

    private StatisticsExportService&MockInterface $exportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportService = Mockery::mock(StatisticsExportService::class);
        $this->controller = new StatisticsExportController(
            $this->exportService,
        );
    }

    #[Test]
    public function testExportViewsCSVWithParams(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'post_id'    => '42',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-31',
        ]);
        $response = $this->createMockResponse();

        $csvContent = "id,post_id,views,created_at\n1,42,100,2025-01-01";
        $this->exportService
            ->shouldReceive('exportViewsToCSV')
            ->once()
            ->with(42, '2025-01-01', '2025-01-31')
            ->andReturn($csvContent);

        $result = $this->controller->exportViewsCSV($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testExportViewsCSVWithoutParams(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->exportService
            ->shouldReceive('exportViewsToCSV')
            ->once()
            ->with(null, null, null)
            ->andReturn("id,views\n");

        $result = $this->controller->exportViewsCSV($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testExportComprehensiveCSV(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'post_id'    => '10',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-15',
        ]);
        $response = $this->createMockResponse();

        $this->exportService
            ->shouldReceive('exportComprehensiveReportToCSV')
            ->once()
            ->with(10, '2025-01-01', '2025-01-15')
            ->andReturn("metric,value\n");

        $result = $this->controller->exportComprehensiveCSV($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function testExportJSON(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'post_id'    => '10',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-15',
        ]);
        $response = $this->createMockResponse();

        $this->exportService
            ->shouldReceive('exportToJSON')
            ->once()
            ->with(10, '2025-01-01', '2025-01-15')
            ->andReturn('{"report": "data"}');

        $result = $this->controller->exportJSON($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
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
