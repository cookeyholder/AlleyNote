<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use App\Domains\Statistics\DTOs\StatisticsQueryDTO;
use App\Domains\Statistics\Services\StatisticsQueryService;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsQueryService 單元測試.
 *
 * 以 SQLite in-memory 資料庫驗證查詢建構邏輯，
 * 快取與儲存庫依賴則以模擬物件取代。
 */
final class StatisticsQueryServiceTest extends UnitTestCase
{
    private PDO $pdo;

    /** @var StatisticsRepositoryInterface&MockInterface */
    private $repository;

    /** @var StatisticsCacheServiceInterface&MockInterface */
    private $cacheService;

    /** @var LoggerInterface&MockInterface */
    private $logger;

    private StatisticsQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema();
        $this->seedData();

        $this->repository = Mockery::mock(StatisticsRepositoryInterface::class);
        $this->cacheService = Mockery::mock(StatisticsCacheServiceInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('debug')->byDefault();
        $this->logger->shouldReceive('error')->byDefault();

        $this->service = new StatisticsQueryService(
            $this->repository,
            $this->cacheService,
            $this->logger,
            $this->pdo,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                views INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT "draft",
                deleted_at DATETIME,
                publish_date DATETIME,
                created_at DATETIME NOT NULL,
                user_id INTEGER
            )
        ');
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                created_at DATETIME NOT NULL
            )
        ');
        $this->pdo->exec('
            CREATE TABLE user_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                occurred_at DATETIME,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function seedData(): void
    {
        $this->pdo->exec("
            INSERT INTO posts (title, views, status, deleted_at, publish_date, created_at, user_id) VALUES
            ('公告一', 500, 'published', NULL, '2025-01-02 10:00:00', '2025-01-01 08:00:00', 1),
            ('公告二', 300, 'published', NULL, '2025-01-03 12:00:00', '2025-01-02 09:00:00', 1),
            ('草稿三', 0,   'draft',     NULL, NULL,                  '2025-01-03 14:00:00', 2),
            ('已刪除', 999, 'published', '2025-01-04 00:00:00', '2025-01-03 16:00:00', '2025-01-03 15:00:00', 2)
        ");
        $this->pdo->exec("
            INSERT INTO users (username, created_at) VALUES
            ('alice', '2024-11-20 08:00:00'),
            ('bob',   '2025-01-05 10:00:00')
        ");
        $this->pdo->exec("
            INSERT INTO user_activity_logs (user_id, occurred_at) VALUES
            (1, '2025-01-05 09:00:00'),
            (NULL, '2025-01-05 09:30:00')
        ");
    }

    private function createRangeQuery(string $start, string $end): StatisticsQueryDTO
    {
        return new StatisticsQueryDTO(
            startDate: new DateTimeImmutable($start),
            endDate: new DateTimeImmutable($end),
        );
    }

    public function testGetOverviewBuildsFromDatabaseAndCachesResult(): void
    {
        $query = $this->createRangeQuery('2024-12-01 00:00:00', '2025-01-05 23:59:59');

        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()
            ->with(Mockery::pattern('/^stats:overview:/'), Mockery::type(StatisticsOverviewDTO::class), 3600, ['statistics', 'overview'])
            ->andReturnTrue();
        $this->logger->shouldReceive('debug')->twice();

        $overview = $this->service->getOverview($query);

        $this->assertSame(3, $overview->getTotalPosts());
        $this->assertSame(2, $overview->getActiveUsers());
        $this->assertSame(1, $overview->getNewUsers());
        $this->assertSame(3, $overview->getPostActivity()['total_posts']);
        $this->assertSame(2, $overview->getPostActivity()['published_posts']);
        $this->assertSame(1, $overview->getPostActivity()['draft_posts']);
        $this->assertSame(2, $overview->getUserActivity()['total_users']);
        $this->assertSame('custom', $overview->getPeriodSummary()['type']);
        $this->assertSame(36, $overview->getPeriodSummary()['duration_days']);
    }

    public function testGetOverviewDeterminesWeeklyMonthlyAndCustomPeriodTypes(): void
    {
        $cases = [
            ['2025-01-05 00:00:00', '2025-01-05 23:59:59', 'daily'],
            ['2025-01-01 00:00:00', '2025-01-05 23:59:59', 'weekly'],
            ['2025-01-01 00:00:00', '2025-01-15 23:59:59', 'monthly'],
            ['2025-01-01 00:00:00', '2025-03-15 23:59:59', 'custom'],
        ];

        foreach ($cases as [$start, $end, $expectedType]) {
            $query = $this->createRangeQuery($start, $end);
            $this->cacheService->shouldReceive('get')->andReturnNull();
            $this->cacheService->shouldReceive('put')->andReturnTrue();

            $overview = $this->service->getOverview($query);

            $this->assertSame($expectedType, $overview->getPeriodSummary()['type'], "範圍 {$start} ~ {$end}");
        }
    }

    public function testGetOverviewUsesDefaultsWithoutDateRange(): void
    {
        $query = new StatisticsQueryDTO();

        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()->andReturnTrue();

        $overview = $this->service->getOverview($query);

        // 未指定日期時使用最近 30 天，成長率計算應正常運作
        $this->assertGreaterThanOrEqual(0, $overview->getEngagementMetrics()['user_growth_rate']);
        $this->assertArrayHasKey('posts_per_active_user', $overview->getEngagementMetrics());
    }

    public function testGetOverviewReturnsCachedDtoInstance(): void
    {
        $cached = new StatisticsOverviewDTO(
            totalPosts: 42,
            activeUsers: 7,
            newUsers: 3,
            postActivity: ['total_posts' => 42, 'published_posts' => 40, 'draft_posts' => 2],
            userActivity: ['total_users' => 100, 'active_users' => 7, 'new_users' => 3],
            engagementMetrics: ['posts_per_active_user' => 6.0, 'user_growth_rate' => 1.5],
            periodSummary: ['type' => 'daily', 'duration_days' => 1],
        );

        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);
        $this->logger->shouldReceive('debug')->once();

        $result = $this->service->getOverview(new StatisticsQueryDTO());

        $this->assertSame($cached, $result);
    }

    public function testGetPostStatisticsBuildsPaginatedResult(): void
    {
        $query = new StatisticsQueryDTO(limit: 5);

        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()
            ->with(Mockery::pattern('/^stats:posts:/'), Mockery::type(PaginatedStatisticsDTO::class), 3600, ['statistics', 'posts'])
            ->andReturnTrue();

        $result = $this->service->getPostStatistics($query);

        $this->assertCount(5, $result->getData());
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(5, $result->getPerPage());
    }

    public function testGetPostStatisticsReturnsCachedResult(): void
    {
        $cached = new PaginatedStatisticsDTO(data: [['id' => 'post-1']], totalCount: 1, currentPage: 1, perPage: 1);
        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);

        $result = $this->service->getPostStatistics(new StatisticsQueryDTO());

        $this->assertSame($cached, $result);
    }

    public function testGetSourceDistributionBuildsDistributionArray(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()
            ->with(Mockery::pattern('/^stats:sources:/'), Mockery::type('array'), 3600, ['statistics', 'sources'])
            ->andReturnTrue();

        $distribution = $this->service->getSourceDistribution(new StatisticsQueryDTO());

        $this->assertCount(3, $distribution);
        assert(isset($distribution[0]) && is_array($distribution[0]));
        $this->assertSame('web', $distribution[0]['source']);
    }

    public function testGetSourceDistributionReturnsCachedArray(): void
    {
        $cached = [['source' => 'api', 'count' => 1, 'percentage' => 100.0]];
        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);

        $distribution = $this->service->getSourceDistribution(new StatisticsQueryDTO());

        $this->assertSame($cached, $distribution);
    }

    public function testGetUserStatisticsBuildsPaginatedResult(): void
    {
        $query = new StatisticsQueryDTO(limit: 3);

        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()
            ->with(Mockery::pattern('/^stats:users:/'), Mockery::type(PaginatedStatisticsDTO::class), 3600, ['statistics', 'users'])
            ->andReturnTrue();

        $result = $this->service->getUserStatistics($query);

        $this->assertCount(3, $result->getData());
        $firstUser = $result->getData()[0] ?? null;
        assert(is_array($firstUser));
        $this->assertSame('user1', $firstUser['username']);
    }

    public function testGetUserStatisticsReturnsCachedResult(): void
    {
        $cached = new PaginatedStatisticsDTO(data: [], totalCount: 0, currentPage: 1, perPage: 10);
        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);

        $result = $this->service->getUserStatistics(new StatisticsQueryDTO());

        $this->assertSame($cached, $result);
    }

    public function testGetPopularContentQueriesPublishedPosts(): void
    {
        // 涵蓋有日期範圍與無日期範圍兩種 SQL 分支
        $queries = [
            new StatisticsQueryDTO(),
            $this->createRangeQuery('2025-01-01 00:00:00', '2025-01-31 23:59:59'),
        ];

        foreach ($queries as $query) {
            $this->cacheService->shouldReceive('get')->andReturnNull();
            $this->cacheService->shouldReceive('put')->andReturnTrue();

            $popular = $this->service->getPopularContent($query);

            $this->assertNotEmpty($popular);
            $topPost = $popular[0] ?? null;
            assert(is_array($topPost));
            $this->assertSame('公告一', $topPost['title']);
        }
    }

    public function testGetPopularContentReturnsCachedArray(): void
    {
        $cached = [['id' => 9, 'title' => '快取文章']];
        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);

        $popular = $this->service->getPopularContent(new StatisticsQueryDTO());

        $this->assertSame($cached, $popular);
    }

    public function testSearchBuildsResultsWithKeyword(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andReturnNull();
        $this->cacheService->shouldReceive('put')->once()
            ->with(Mockery::pattern('/^stats:search:/'), Mockery::type(PaginatedStatisticsDTO::class), 3600, ['statistics', 'search'])
            ->andReturnTrue();

        $result = $this->service->search('公告', new StatisticsQueryDTO());

        $this->assertSame('公告', $result->getMetadata()['search_keyword']);
        $this->assertNotEmpty($result->getData());
    }

    public function testSearchReturnsCachedResult(): void
    {
        $cached = new PaginatedStatisticsDTO(data: [], totalCount: 0, currentPage: 1, perPage: 10, metadata: ['search_keyword' => 'x']);
        $this->cacheService->shouldReceive('get')->once()->andReturn($cached);

        $result = $this->service->search('x', new StatisticsQueryDTO());

        $this->assertSame($cached, $result);
    }

    public function testSearchRejectsBlankKeyword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('搜尋關鍵字不能為空');

        $this->service->search('   ', new StatisticsQueryDTO());
    }

    public function testSearchLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('cache down'));
        $this->logger->shouldReceive('error')->once()->with('統計搜尋失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cache down');

        $this->service->search('公告', new StatisticsQueryDTO());
    }

    public function testGetOverviewLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('boom'));
        $this->logger->shouldReceive('error')->once()->with('統計概覽查詢失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $this->service->getOverview(new StatisticsQueryDTO());
    }

    public function testGetPostStatisticsLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('oops'));
        $this->logger->shouldReceive('error')->once()->with('文章統計查詢失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('oops');

        $this->service->getPostStatistics(new StatisticsQueryDTO());
    }

    public function testGetSourceDistributionLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('oops'));
        $this->logger->shouldReceive('error')->once()->with('來源分佈查詢失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('oops');

        $this->service->getSourceDistribution(new StatisticsQueryDTO());
    }

    public function testGetUserStatisticsLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('oops'));
        $this->logger->shouldReceive('error')->once()->with('使用者統計查詢失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('oops');

        $this->service->getUserStatistics(new StatisticsQueryDTO());
    }

    public function testGetPopularContentLogsAndRethrowsOnFailure(): void
    {
        $this->cacheService->shouldReceive('get')->once()->andThrow(new RuntimeException('oops'));
        $this->logger->shouldReceive('error')->once()->with('熱門內容查詢失敗', Mockery::type('array'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('oops');

        $this->service->getPopularContent(new StatisticsQueryDTO());
    }
}
