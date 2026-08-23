<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\StatisticsChartController;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Shared\Exceptions\ValidationException;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsChartController 單元測試.
 */
#[CoversClass(StatisticsChartController::class)]
class StatisticsChartControllerTest extends UnitTestCase
{
    private StatisticsChartController $controller;

    private StatisticsVisualizationServiceInterface&MockInterface $visualizationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visualizationService = Mockery::mock(StatisticsVisualizationServiceInterface::class);
        $this->controller = new StatisticsChartController(
            $this->visualizationService,
        );
    }

    #[Test]
    public function testGetPostsTimeSeriesSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-01-10',
            'granularity' => 'day',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['2025-01-01'], []);

        $this->visualizationService
            ->shouldReceive('getPostsTimeSeriesData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getPostsTimeSeries($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetPostsTimeSeriesValidationException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date'  => '2025-01-10',
            'end_date'    => '2025-01-01', // start > end
            'granularity' => 'day',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getPostsTimeSeries($request, $response);
    }

    #[Test]
    public function testGetPostsTimeSeriesGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getPostsTimeSeriesData')
            ->once()
            ->andThrow(new Exception('Error in visualization'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('取得統計資料失敗');

        $this->controller->getPostsTimeSeries($request, $response);
    }

    #[Test]
    public function testGetUserActivityTimeSeriesSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'granularity' => 'week',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['2025-W01'], []);

        $this->visualizationService
            ->shouldReceive('getUserActivityTimeSeriesData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getUserActivityTimeSeries($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetUserActivityTimeSeriesInvalidGranularity(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'granularity' => 'decade',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getUserActivityTimeSeries($request, $response);
    }

    #[Test]
    public function testGetUserActivityTimeSeriesGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getUserActivityTimeSeriesData')
            ->once()
            ->andThrow(new Exception('Failure'));

        $this->expectException(RuntimeException::class);
        $this->controller->getUserActivityTimeSeries($request, $response);
    }

    #[Test]
    public function testGetViewsTimeSeriesSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'granularity' => 'month',
        ]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getViewsTimeSeriesData')
            ->once()
            ->andReturn([['date' => '2025-01', 'views' => 100]]);

        $result = $this->controller->getViewsTimeSeries($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetViewsTimeSeriesValidationException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date' => 'invalid-format',
            'end_date'   => '2025-01-01',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getViewsTimeSeries($request, $response);
    }

    #[Test]
    public function testGetViewsTimeSeriesGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getViewsTimeSeriesData')
            ->once()
            ->andThrow(new Exception('Failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('取得瀏覽量統計失敗: Failure');

        $this->controller->getViewsTimeSeries($request, $response);
    }

    #[Test]
    public function testGetCategoryChartSources(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-31',
            'limit'      => '15',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['web', 'mobile'], []);

        $this->visualizationService
            ->shouldReceive('getPostSourceDistributionData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getCategoryChart($request, $response, ['type' => 'sources']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetCategoryChartTags(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['tag1', 'tag2'], []);

        $this->visualizationService
            ->shouldReceive('getPopularTagsDistributionData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getCategoryChart($request, $response, ['type' => 'tags']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetCategoryChartEngagement(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['high', 'low'], []);

        $this->visualizationService
            ->shouldReceive('getUserEngagementDistributionData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getCategoryChart($request, $response, ['type' => 'engagement']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetCategoryChartUnsupportedType(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getCategoryChart($request, $response, ['type' => 'unsupported']);
    }

    #[Test]
    public function testGetCategoryChartGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getPostSourceDistributionData')
            ->once()
            ->andThrow(new Exception('Error'));

        $this->expectException(RuntimeException::class);
        $this->controller->getCategoryChart($request, $response, ['type' => 'sources']);
    }

    #[Test]
    public function testGetTrendChartRegistration(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'granularity' => 'day',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['2025-01-01'], []);

        $this->visualizationService
            ->shouldReceive('getUserRegistrationTrendData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getTrendChart($request, $response, ['type' => 'registration']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetTrendChartContentGrowth(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'granularity' => 'month',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['2025-01'], []);

        $this->visualizationService
            ->shouldReceive('getContentGrowthTrendData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getTrendChart($request, $response, ['type' => 'content-growth']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetTrendChartUnsupportedType(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getTrendChart($request, $response, ['type' => 'invalid_trend']);
    }

    #[Test]
    public function testGetTrendChartGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getUserRegistrationTrendData')
            ->once()
            ->andThrow(new Exception('Error'));

        $this->expectException(RuntimeException::class);
        $this->controller->getTrendChart($request, $response, ['type' => 'registration']);
    }

    #[Test]
    public function testGetContentRankingSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-31',
            'sort_by'    => 'views',
            'limit'      => '20',
        ]);
        $response = $this->createMockResponse();

        $chartData = new ChartData(['Post A', 'Post B'], []);

        $this->visualizationService
            ->shouldReceive('getPopularContentRankingData')
            ->once()
            ->andReturn($chartData);

        $result = $this->controller->getContentRanking($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetContentRankingInvalidSortBy(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'sort_by' => 'invalid_ranking_field',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getContentRanking($request, $response);
    }

    #[Test]
    public function testGetContentRankingInvalidLimit(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'limit' => '999', // max is 50
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getContentRanking($request, $response);
    }

    #[Test]
    public function testGetContentRankingNonNumericLimit(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'limit' => 'abc',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getContentRanking($request, $response);
    }

    #[Test]
    public function testGetContentRankingGenericException(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->visualizationService
            ->shouldReceive('getPopularContentRankingData')
            ->once()
            ->andThrow(new Exception('Error'));

        $this->expectException(RuntimeException::class);
        $this->controller->getContentRanking($request, $response);
    }

    #[Test]
    public function testParseOptionalDateInvalid(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date' => 'not-a-valid-date',
        ]);
        $response = $this->createMockResponse();

        $this->expectException(ValidationException::class);
        $this->controller->getCategoryChart($request, $response, ['type' => 'sources']);
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
