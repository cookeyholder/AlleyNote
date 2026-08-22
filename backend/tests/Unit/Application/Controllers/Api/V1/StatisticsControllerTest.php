<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\StatisticsController;
use App\Domains\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use App\Domains\Statistics\DTOs\StatisticsQueryDTO;
use App\Domains\Statistics\Services\StatisticsQueryService;
use App\Shared\Contracts\ValidatorInterface;
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
 * StatisticsController 單元測試.
 */
#[CoversClass(StatisticsController::class)]
class StatisticsControllerTest extends UnitTestCase
{
    private StatisticsController $controller;

    private StatisticsQueryService&MockInterface $statisticsQueryService;

    private ValidatorInterface&MockInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statisticsQueryService = Mockery::mock(StatisticsQueryService::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        $this->controller = new StatisticsController(
            $this->statisticsQueryService,
            $this->validator,
        );
    }

    #[Test]
    public function testGetOverviewSuccessWithAdminRole(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date'     => '2025-01-01',
            'end_date'       => '2025-01-31',
            'sort_by'        => 'created_at',
            'sort_direction' => 'desc',
            'page'           => '1',
            'limit'          => '20',
        ]);
        $response = $this->createMockResponse();

        $overviewDTO = Mockery::mock(StatisticsOverviewDTO::class);
        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn($overviewDTO);

        $result = $this->controller->getOverview($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewSuccessWithSuperAdminRole(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('super_admin');
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $overviewDTO = Mockery::mock(StatisticsOverviewDTO::class);
        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andReturn($overviewDTO);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewSuccessWithWildcardPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('user');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['*']);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $overviewDTO = Mockery::mock(StatisticsOverviewDTO::class);
        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andReturn($overviewDTO);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewSuccessWithStatisticsWildcardPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('user');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['statistics.*']);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $overviewDTO = Mockery::mock(StatisticsOverviewDTO::class);
        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andReturn($overviewDTO);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewSuccessWithStatisticsReadPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('user');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['statistics.read']);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $overviewDTO = Mockery::mock(StatisticsOverviewDTO::class);
        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andReturn($overviewDTO);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewPermissionDenied(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('user');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['other.permission']);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewInvalidPermissionsFormat(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('user');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn('not-an-array');
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewValidationErrorsOnDateRange(): void
    {
        // 1. start_date > end_date
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn([
            'start_date' => '2025-02-01',
            'end_date'   => '2025-01-01',
        ]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // 2. Future date
        $requestFuture = $this->createMockRequest();
        $requestFuture->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $requestFuture->shouldReceive('getQueryParams')->andReturn([
            'start_date' => '2099-01-01',
            'end_date'   => '2099-01-02',
        ]);
        $resultFuture = $this->controller->getOverview($requestFuture, $response);
        $this->assertEquals(400, $resultFuture->getStatusCode());

        // 3. Range > 1 year
        $requestWide = $this->createMockRequest();
        $requestWide->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $requestWide->shouldReceive('getQueryParams')->andReturn([
            'start_date' => '2020-01-01',
            'end_date'   => '2022-01-01',
        ]);
        $resultWide = $this->controller->getOverview($requestWide, $response);
        $this->assertEquals(400, $resultWide->getStatusCode());
    }

    #[Test]
    public function testGetOverviewValidationErrorsOnParams(): void
    {
        $response = $this->createMockResponse(400);

        // Invalid date format pattern
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['start_date' => 'invalid-date']);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // Invalid page min
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['page' => 0]);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // Invalid page max
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['page' => 2000]);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // Invalid limit max
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['limit' => 500]);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // Invalid sort_by enum
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['sort_by' => 'invalid_col']);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());

        // Invalid sort_direction enum
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn(['sort_direction' => 'diagonal']);
        $result = $this->controller->getOverview($request, $response);
        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetOverviewInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role', '')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andThrow(new Exception('Database connection failed'));

        $result = $this->controller->getOverview($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetPostsSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $paginatedDTO = new PaginatedStatisticsDTO([['post_id' => 1, 'views' => 10]], 1, 1, 20);

        $this->statisticsQueryService
            ->shouldReceive('getPostStatistics')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn($paginatedDTO);

        $result = $this->controller->getPosts($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetPostsValidationError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn(['page' => -5]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getPosts($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetPostsInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->statisticsQueryService
            ->shouldReceive('getPostStatistics')
            ->once()
            ->andThrow(new Exception('Service crash'));

        $result = $this->controller->getPosts($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetSourcesSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->statisticsQueryService
            ->shouldReceive('getSourceDistribution')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn([['source' => 'web', 'count' => 10, 'percentage' => 100.0]]);

        $result = $this->controller->getSources($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetSourcesValidationError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn(['start_date' => 'bad-date']);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getSources($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetSourcesInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->statisticsQueryService
            ->shouldReceive('getSourceDistribution')
            ->once()
            ->andThrow(new Exception('Error'));

        $result = $this->controller->getSources($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetUsersSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $paginatedDTO = new PaginatedStatisticsDTO([['user_id' => 1, 'posts_count' => 5]], 1, 1, 20);

        $this->statisticsQueryService
            ->shouldReceive('getUserStatistics')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn($paginatedDTO);

        $result = $this->controller->getUsers($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetUsersValidationError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn(['limit' => 200]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getUsers($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetUsersInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->statisticsQueryService
            ->shouldReceive('getUserStatistics')
            ->once()
            ->andThrow(new Exception('Error'));

        $result = $this->controller->getUsers($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testGetPopularSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->statisticsQueryService
            ->shouldReceive('getPopularContent')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn([['title' => 'Top Post', 'views' => 100]]);

        $result = $this->controller->getPopular($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testGetPopularValidationError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn(['page' => 'abc']);
        $response = $this->createMockResponse(400);

        $result = $this->controller->getPopular($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testGetPopularInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->statisticsQueryService
            ->shouldReceive('getPopularContent')
            ->once()
            ->andThrow(new Exception('Error'));

        $result = $this->controller->getPopular($request, $response);

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
