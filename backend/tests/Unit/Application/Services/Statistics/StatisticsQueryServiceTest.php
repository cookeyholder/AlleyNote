<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Services\Statistics;

use App\Application\Services\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Application\Services\Statistics\DTOs\StatisticsQueryDTO;
use App\Application\Services\Statistics\StatisticsQueryService;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Application\Services\Statistics\StatisticsQueryService
 */
final class StatisticsQueryServiceTest extends TestCase
{
    private StatisticsQueryService $service;

    private StatisticsRepositoryInterface&MockInterface $statisticsRepository;

    private StatisticsCacheServiceInterface&MockInterface $cacheService;

    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statisticsRepository = Mockery::mock(StatisticsRepositoryInterface::class);
        $this->cacheService = Mockery::mock(StatisticsCacheServiceInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->service = new StatisticsQueryService(
            $this->statisticsRepository,
            $this->cacheService,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetOverviewWithValidQuery(): void
    {
        // Given
        $query = new StatisticsQueryDTO(
            startDate: new DateTimeImmutable('2024-01-01'),
            endDate: new DateTimeImmutable('2024-01-31'),
        );

        $expectedOverview = new StatisticsOverviewDTO(
            totalPosts: 1000,
            activeUsers: 200,
            newUsers: 50,
            postActivity: [
                'total_posts' => 1000,
                'published_posts' => 800,
                'draft_posts' => 200,
            ],
            userActivity: [
                'total_users' => 200,
                'active_users' => 150,
                'new_users' => 50,
            ],
            engagementMetrics: [
                'posts_per_active_user' => 5.0,
                'user_growth_rate' => 25.0,
            ],
            periodSummary: [
                'type' => 'monthly',
                'duration_days' => 31,
            ],
        );

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice(); // 一次查詢開始，一次快取設定

        $this->cacheService->shouldReceive('put')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::type(StatisticsOverviewDTO::class),
                3600,
                ['statistics', 'overview'],
            );

        // When
        $result = $this->service->getOverview($query);

        // Then
        $this->assertInstanceOf(StatisticsOverviewDTO::class, $result);
        $this->assertSame(1000, $result->getTotalPosts());
        $this->assertSame(200, $result->getActiveUsers());
        $this->assertSame(50, $result->getNewUsers());
    }

    public function testGetOverviewWithCacheHit(): void
    {
        // Given
        $query = new StatisticsQueryDTO();

        $cachedOverview = new StatisticsOverviewDTO(
            totalPosts: 500,
            activeUsers: 100,
            newUsers: 25,
            postActivity: [
                'total_posts' => 500,
                'published_posts' => 400,
                'draft_posts' => 100,
            ],
            userActivity: [
                'total_users' => 100,
                'active_users' => 75,
                'new_users' => 25,
            ],
            engagementMetrics: [
                'posts_per_active_user' => 4.0,
                'user_growth_rate' => 33.0,
            ],
            periodSummary: [
                'type' => 'monthly',
                'duration_days' => 31,
            ],
        );

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn($cachedOverview);

        $this->logger->shouldReceive('debug')
            ->once()
            ->with('統計概覽快取命中', Mockery::type('array'));

        // When
        $result = $this->service->getOverview($query);

        // Then
        $this->assertSame($cachedOverview, $result);
        $this->assertSame(500, $result->getTotalPosts());
        $this->assertSame(25, $result->getNewUsers());
    }

    public function testGetPostStatisticsWithPagination(): void
    {
        // Given
        $query = new StatisticsQueryDTO(
            page: 2,
            limit: 10,
            sortBy: 'view_count',
            sortDirection: 'desc',
        );

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice();

        $this->cacheService->shouldReceive('put')
            ->once();

        // When
        $result = $this->service->getPostStatistics($query);

        // Then
        $this->assertInstanceOf(PaginatedStatisticsDTO::class, $result);
        $this->assertSame(2, $result->getCurrentPage());
        $this->assertSame(10, $result->getPerPage());
        $this->assertIsArray($result->getData());
    }

    public function testGetSourceDistribution(): void
    {
        // Given
        $query = new StatisticsQueryDTO();

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice();

        $this->cacheService->shouldReceive('put')
            ->once();

        // When
        $result = $this->service->getSourceDistribution($query);

        // Then
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // 模擬資料有 3 個來源
        $this->assertSame('web', $result[0]['source']);
        $this->assertSame(80.0, $result[0]['percentage']);
    }

    public function testGetUserStatisticsWithValidQuery(): void
    {
        // Given
        $query = new StatisticsQueryDTO(
            page: 1,
            limit: 20,
        );

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice();

        $this->cacheService->shouldReceive('put')
            ->once();

        // When
        $result = $this->service->getUserStatistics($query);

        // Then
        $this->assertInstanceOf(PaginatedStatisticsDTO::class, $result);
        $this->assertSame(1, $result->getCurrentPage());
        $this->assertSame(20, $result->getPerPage());
        $this->assertSame(200, $result->getTotalCount()); // 模擬總數
    }

    public function testGetPopularContent(): void
    {
        // Given
        $query = new StatisticsQueryDTO();

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice();

        $this->cacheService->shouldReceive('put')
            ->once();

        // When
        $result = $this->service->getPopularContent($query);

        // Then
        $this->assertIsArray($result);
        $this->assertArrayHasKey('most_viewed_posts', $result);
        $this->assertArrayHasKey('trending_categories', $result);
        $this->assertArrayHasKey('active_users', $result);
    }

    public function testSearchWithValidKeyword(): void
    {
        // Given
        $keyword = 'technology';
        $query = new StatisticsQueryDTO(page: 1, limit: 10);

        $this->cacheService->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $this->logger->shouldReceive('debug')
            ->twice();

        $this->cacheService->shouldReceive('put')
            ->once();

        // When
        $result = $this->service->search($keyword, $query);

        // Then
        $this->assertInstanceOf(PaginatedStatisticsDTO::class, $result);
        $this->assertArrayHasKey('search_keyword', $result->getMetadata());
        $this->assertSame($keyword, $result->getMetadata()['search_keyword']);
        $this->assertSame(50, $result->getTotalCount()); // 模擬搜尋結果總數
    }

    public function testSearchWithEmptyKeywordThrowsException(): void
    {
        // Given
        $keyword = '   ';
        $query = new StatisticsQueryDTO();

        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('搜尋關鍵字不能為空');

        // When
        $this->service->search($keyword, $query);
    }

    public function testValidateQueryWithTooLargeDateRangeThrowsException(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('查詢時間範圍不能超過 1 年');

        // When - 建立超過 365 天範圍的查詢
        new StatisticsQueryDTO(
            startDate: new DateTimeImmutable('2023-01-01'),
            endDate: new DateTimeImmutable('2025-01-01'), // 超過 365 天
        );
    }

    public function testValidateQueryWithTooLargeLimitThrowsException(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1-100 之間');

        // When
        new StatisticsQueryDTO(limit: 150); // 超過 100
    }

    public function testStatisticsQueryDTOValidationWithInvalidPage(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('頁數必須大於 0');

        // When
        new StatisticsQueryDTO(page: 0);
    }

    public function testStatisticsQueryDTOValidationWithInvalidLimit(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1-100 之間');

        // When
        new StatisticsQueryDTO(limit: 0);
    }

    public function testStatisticsQueryDTOValidationWithInvalidDateRange(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('開始日期不能大於結束日期');

        // When
        new StatisticsQueryDTO(
            startDate: new DateTimeImmutable('2024-02-01'),
            endDate: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function testStatisticsQueryDTOValidationWithInvalidSortDirection(): void
    {
        // Then
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('排序方向只能為 asc 或 desc');

        // When
        new StatisticsQueryDTO(sortDirection: 'invalid');
    }

    public function testStatisticsQueryDTOGettersReturnCorrectValues(): void
    {
        // Given
        $startDate = new DateTimeImmutable('2024-01-01');
        $endDate = new DateTimeImmutable('2024-01-31');
        $filters = ['category' => 'tech'];

        $query = new StatisticsQueryDTO(
            startDate: $startDate,
            endDate: $endDate,
            page: 3,
            limit: 25,
            sortBy: 'created_at',
            sortDirection: 'asc',
            filters: $filters,
        );

        // When & Then
        $this->assertSame($startDate, $query->getStartDate());
        $this->assertSame($endDate, $query->getEndDate());
        $this->assertSame(3, $query->getPage());
        $this->assertSame(25, $query->getLimit());
        $this->assertSame('created_at', $query->getSortBy());
        $this->assertSame('asc', $query->getSortDirection());
        $this->assertSame($filters, $query->getFilters());
        $this->assertSame(50, $query->getOffset()); // (3-1) * 25 = 50
        $this->assertTrue($query->hasDateRange());
        $this->assertSame(30, $query->getDateRangeInDays());
    }

    public function testPaginatedStatisticsDTOGettersReturnCorrectValues(): void
    {
        // Given
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];
        $metadata = ['extra' => 'info'];

        $paginatedDto = new PaginatedStatisticsDTO(
            data: $data,
            totalCount: 100,
            currentPage: 2,
            perPage: 20,
            metadata: $metadata,
        );

        // When & Then
        $this->assertSame($data, $paginatedDto->getData());
        $this->assertSame(100, $paginatedDto->getTotalCount());
        $this->assertSame(2, $paginatedDto->getCurrentPage());
        $this->assertSame(20, $paginatedDto->getPerPage());
        $this->assertSame($metadata, $paginatedDto->getMetadata());
        $this->assertSame(5, $paginatedDto->getTotalPages()); // ceil(100/20) = 5
        $this->assertTrue($paginatedDto->hasNextPage());
        $this->assertTrue($paginatedDto->hasPreviousPage());
        $this->assertSame(21, $paginatedDto->getFrom()); // ((2-1) * 20) + 1 = 21
        $this->assertSame(40, $paginatedDto->getTo()); // 2 * 20 = 40
    }

    public function testPaginatedStatisticsDTOToArrayReturnsCorrectStructure(): void
    {
        // Given
        $data = ['item1', 'item2'];
        $metadata = ['key' => 'value'];

        $paginatedDto = new PaginatedStatisticsDTO(
            data: $data,
            totalCount: 50,
            currentPage: 1,
            perPage: 10,
            metadata: $metadata,
        );

        // When
        $array = $paginatedDto->toArray();

        // Then
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('pagination', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertSame($data, $array['data']);
        $this->assertSame($metadata, $array['metadata']);

        $pagination = $array['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total_count', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
        $this->assertArrayHasKey('has_next_page', $pagination);
        $this->assertArrayHasKey('has_previous_page', $pagination);
        $this->assertArrayHasKey('from', $pagination);
        $this->assertArrayHasKey('to', $pagination);
    }
}
