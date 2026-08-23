<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\StatisticsAdminController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use App\Domains\Statistics\DTOs\StatisticsQueryDTO;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Services\StatisticsConfigService;
use App\Domains\Statistics\Services\StatisticsQueryService;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
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
 * StatisticsAdminController 單元測試.
 */
#[CoversClass(StatisticsAdminController::class)]
class StatisticsAdminControllerTest extends UnitTestCase
{
    private StatisticsAdminController $controller;

    private StatisticsAggregationServiceInterface&MockInterface $aggregationService;

    private StatisticsApplicationService $statisticsApplicationService;

    private StatisticsQueryService&MockInterface $statisticsQueryService;

    private StatisticsCacheServiceInterface&MockInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregationService = Mockery::mock(StatisticsAggregationServiceInterface::class);
        $this->cacheService = Mockery::mock(StatisticsCacheServiceInterface::class);
        $this->cacheService->shouldReceive('forget')->byDefault();

        $configService = new StatisticsConfigService([], 'testing');
        $this->statisticsApplicationService = new StatisticsApplicationService(
            $this->aggregationService,
            $this->cacheService,
            $configService,
        );

        $this->statisticsQueryService = Mockery::mock(StatisticsQueryService::class);

        $this->controller = new StatisticsAdminController(
            $this->statisticsApplicationService,
            $this->statisticsQueryService,
            $this->cacheService,
        );
    }

    #[Test]
    public function testRefreshSuccessWithForceRecalculate(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('PHPUnit/11');
        $request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->shouldReceive('getParsedBody')->andReturn([
            'types'             => ['overview', 'posts', 'users', 'popular', 'sources'],
            'force_recalculate' => true,
        ]);
        $response = $this->createMockResponse();

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics', 'overview', 'posts', 'users', 'popular', 'sources']);

        $period = new StatisticsPeriod(PeriodType::MONTHLY, new DateTimeImmutable('-30 days'), new DateTimeImmutable());

        $overviewSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $overviewSnapshot->shouldReceive('getSnapshotType')->andReturn('overview');
        $overviewSnapshot->shouldReceive('getPeriod')->andReturn($period);

        $postsSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $postsSnapshot->shouldReceive('getSnapshotType')->andReturn('posts');
        $postsSnapshot->shouldReceive('getPeriod')->andReturn($period);

        $usersSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $usersSnapshot->shouldReceive('getSnapshotType')->andReturn('users');
        $usersSnapshot->shouldReceive('getPeriod')->andReturn($period);

        $popularSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $popularSnapshot->shouldReceive('getSnapshotType')->andReturn('popular');
        $popularSnapshot->shouldReceive('getPeriod')->andReturn($period);

        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->andReturn($overviewSnapshot);

        $this->aggregationService
            ->shouldReceive('createPostsSnapshot')
            ->once()
            ->andReturn($postsSnapshot);

        $this->aggregationService
            ->shouldReceive('createUsersSnapshot')
            ->once()
            ->andReturn($usersSnapshot);

        $this->aggregationService
            ->shouldReceive('createPopularSnapshot')
            ->once()
            ->andReturn($popularSnapshot);

        $result = $this->controller->refresh($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testRefreshSuccessWithoutForceRecalculate(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('super_admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('admin-uuid');
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('TestAgent');
        $request->shouldReceive('getServerParams')->andReturn(['HTTP_CF_CONNECTING_IP' => '203.0.113.1']);
        $request->shouldReceive('getParsedBody')->andReturn([
            'types'             => ['overview'],
            'force_recalculate' => false,
        ]);
        $response = $this->createMockResponse();

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics', 'overview']);

        $result = $this->controller->refresh($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testRefreshSuccessWithWildcardPermission(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('editor');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['admin.*']);
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getParsedBody')->andReturn(null);
        $response = $this->createMockResponse();

        $this->cacheService
            ->shouldReceive('flushByTags')
            ->once()
            ->with(['statistics', 'overview', 'posts', 'users', 'popular', 'sources']);

        $period = new StatisticsPeriod(PeriodType::MONTHLY, new DateTimeImmutable('-30 days'), new DateTimeImmutable());
        $mockSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $mockSnapshot->shouldReceive('getSnapshotType')->andReturn('overview');
        $mockSnapshot->shouldReceive('getPeriod')->andReturn($period);

        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->andReturn($mockSnapshot);
        $this->aggregationService
            ->shouldReceive('createPostsSnapshot')
            ->andReturn($mockSnapshot);
        $this->aggregationService
            ->shouldReceive('createUsersSnapshot')
            ->andReturn($mockSnapshot);
        $this->aggregationService
            ->shouldReceive('createPopularSnapshot')
            ->andReturn($mockSnapshot);

        $result = $this->controller->refresh($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testRefreshPermissionDenied(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('guest');
        $request->shouldReceive('getAttribute')->with('permissions', [])->andReturn(['posts.read']);
        $response = $this->createMockResponse(400);

        $result = $this->controller->refresh($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testRefreshInvalidTypes(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getParsedBody')->andReturn([
            'types' => ['unknown_type'],
        ]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->refresh($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testRefreshHandlesSnapshotExceptionGracefully(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getParsedBody')->andReturn([
            'types' => ['overview'],
        ]);
        $response = $this->createMockResponse();

        $this->cacheService->shouldReceive('flushByTags')->once();
        $this->aggregationService
            ->shouldReceive('createOverviewSnapshot')
            ->once()
            ->andThrow(new Exception('Snapshot calculation failed'));

        $result = $this->controller->refresh($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testClearCacheAllSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getQueryParams')->andReturn([
            'all' => 'true',
        ]);
        $response = $this->createMockResponse();

        $this->cacheService->shouldReceive('flush')->once()->andReturn(true);

        $result = $this->controller->clearCache($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testClearCacheByValidTags(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getQueryParams')->andReturn([
            'tags' => 'overview,posts',
        ]);
        $response = $this->createMockResponse();

        $this->cacheService->shouldReceive('flushByTags')->once()->with(['overview', 'posts'])->andReturn(true);

        $result = $this->controller->clearCache($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testClearCacheInvalidTags(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getQueryParams')->andReturn([
            'tags' => 'invalid_tag_name',
        ]);
        $response = $this->createMockResponse(400);

        $result = $this->controller->clearCache($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testClearCacheDefaultTags(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->cacheService->shouldReceive('flushByTags')->once()->with(['statistics', 'overview', 'posts', 'users'])->andReturn(true);

        $result = $this->controller->clearCache($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testHealthHealthy(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $response = $this->createMockResponse(200);

        $this->cacheService
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['hits' => 90, 'misses' => 10]);

        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn(Mockery::mock(StatisticsOverviewDTO::class));

        $result = $this->controller->health($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testHealthWarning(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $response = $this->createMockResponse(200);

        // Low hit rate with > 100 requests
        $this->cacheService
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['hits' => 10, 'misses' => 100]);

        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->with(Mockery::type(StatisticsQueryDTO::class))
            ->andReturn(Mockery::mock(StatisticsOverviewDTO::class));

        $result = $this->controller->health($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testHealthCritical(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andReturn('admin');
        $response = $this->createMockResponse(503);

        $this->cacheService
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new Exception('Redis down'));

        $this->statisticsQueryService
            ->shouldReceive('getOverview')
            ->once()
            ->andThrow(new Exception('Database down'));

        $result = $this->controller->health($request, $response);

        $this->assertEquals(503, $result->getStatusCode());
    }

    #[Test]
    public function testHealthInternalError(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('role')->andThrow(new Exception('Unexpected server error'));
        $response = $this->createMockResponse(500);

        $result = $this->controller->health($request, $response);

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
