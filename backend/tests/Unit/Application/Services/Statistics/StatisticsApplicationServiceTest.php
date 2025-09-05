<?php
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SystemStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\Services\SourceAnalysisService;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\Services\StatisticsCacheService;
use App\Domains\Statistics\Services\StatisticsValidationService;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

namespace Tests\Unit\Application\Services\Statistics;

use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\Services\SourceAnalysisService;
use App\Domains\Statistics\Services\StatisticsCacheService;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\Services\StatisticsValidationService;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \App\Application\Services\Statistics\StatisticsApplicationService
 */
final class StatisticsApplicationServiceTest extends TestCase
{
    private StatisticsRepositoryInterface $statisticsRepository;

    private PostStatisticsRepositoryInterface $postStatisticsRepository;

    private StatisticsCalculationService $calculationService;

    private StatisticsValidationService $validationService;

    private SourceAnalysisService $sourceAnalysisService;

    private StatisticsCacheService $cacheService;

    private StatisticsApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statisticsRepository = Mockery::mock(StatisticsRepositoryInterface::class);
        $this->postStatisticsRepository = Mockery::mock(PostStatisticsRepositoryInterface::class);
        $this->userStatisticsRepository = Mockery::mock(UserStatisticsRepositoryInterface::class);
        $this->systemStatisticsRepository = Mockery::mock(SystemStatisticsRepositoryInterface::class);
        $this->calculationService = Mockery::mock(StatisticsCalculationService::class);
        $this->validationService = Mockery::mock(StatisticsValidationService::class);
        $this->sourceAnalysisService = Mockery::mock(SourceAnalysisService::class);
        $this->cacheService = Mockery::mock(StatisticsCacheService::class);

        $this->service = new StatisticsApplicationService(
            $this->statisticsRepository,
            $this->postStatisticsRepository,
            $this->userStatisticsRepository,
            $this->systemStatisticsRepository,
            $this->calculationService,
            $this->validationService,
            $this->sourceAnalysisService,
            $this->cacheService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     * @group T3.1
     */
    public function generatePeriodSnapshot_應該成功生成新的統計快照(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $totalPosts = StatisticsMetric::create(100);
        $totalViews = StatisticsMetric::create(1500);
        $sourceStats = [
            SourceStatistics::create(SourceType::WEB, 80, 1200, 0.8),
            SourceStatistics::create(SourceType::MOBILE, 20, 300, 0.2),
        ];

        $expectedSnapshot = StatisticsSnapshot::create($period, $totalPosts, $totalViews, $sourceStats);

        // 設定 Mock 期望
        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->once()
            ->with($period)
            ->andReturn(null);

        $this->postStatisticsRepository
            ->shouldReceive('countPostsByPeriod')
            ->once()
            ->with($period)
            ->andReturn(100);

        $this->postStatisticsRepository
            ->shouldReceive('countViewsByPeriod')
            ->once()
            ->with($period)
            ->andReturn(1500);

        $this->sourceAnalysisService
            ->shouldReceive('analyzeByPeriod')
            ->once()
            ->with($period)
            ->andReturn($sourceStats);

        $this->statisticsRepository
            ->shouldReceive('saveSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsSnapshot::class));

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->once()
            ->with($period);

        // Act
        $result = $this->service->generatePeriodSnapshot($period);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals($period, $result->getPeriod());
        $this->assertEquals(100, $result->getTotalPosts()->getValue());
        $this->assertEquals(1500, $result->getTotalViews()->getValue());
        $this->assertCount(2, $result->getSourceStats());
    }

    /**
     * @test
     * @group T3.1
     */
    public function generatePeriodSnapshot_當已存在非過期快照時_應該回傳現有快照(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $existingSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $existingSnapshot->shouldReceive('isStale')->andReturn(false);

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->once()
            ->with($period)
            ->andReturn($existingSnapshot);

        // Act
        $result = $this->service->generatePeriodSnapshot($period);

        // Assert
        $this->assertSame($existingSnapshot, $result);
    }

    /**
     * @test
     * @group T3.1
     */
    public function getPeriodStatistics_應該優先從快取取得統計資料(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $cachedSnapshot = Mockery::mock(StatisticsSnapshot::class);

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period);

        $this->cacheService
            ->shouldReceive('getPeriodStatistics')
            ->once()
            ->with($period)
            ->andReturn($cachedSnapshot);

        // Act
        $result = $this->service->getPeriodStatistics($period);

        // Assert
        $this->assertSame($cachedSnapshot, $result);
    }

    /**
     * @test
     * @group T3.1
     */
    public function getPeriodStatistics_當快取不存在時_應該查詢資料庫(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $dbSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $dbSnapshot->shouldReceive('isStale')->andReturn(false);

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period);

        $this->cacheService
            ->shouldReceive('getPeriodStatistics')
            ->once()
            ->with($period)
            ->andReturn(null);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->once()
            ->with($period)
            ->andReturn($dbSnapshot);

        $this->cacheService
            ->shouldReceive('cachePeriodStatistics')
            ->once()
            ->with($period, $dbSnapshot);

        // Act
        $result = $this->service->getPeriodStatistics($period);

        // Assert
        $this->assertSame($dbSnapshot, $result);
    }

    /**
     * @test
     * @group T3.1
     */
    public function getPostAnalytics_應該回傳完整的文章分析資料(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $snapshot = Mockery::mock(StatisticsSnapshot::class);
        $snapshot->shouldReceive('getTotalPosts->getValue')->andReturn(100);
        $snapshot->shouldReceive('getTotalViews->getValue')->andReturn(1500);
        $snapshot->shouldReceive('getSourceStats')->andReturn([]);

        $sourceAnalysis = ['web' => 80, 'mobile' => 20];
        $popularPosts = [['id' => 1, 'views' => 500]];

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period);

        $this->cacheService
            ->shouldReceive('getPeriodStatistics')
            ->once()
            ->with($period)
            ->andReturn($snapshot);

        $this->sourceAnalysisService
            ->shouldReceive('getDetailedAnalysis')
            ->once()
            ->with($period)
            ->andReturn($sourceAnalysis);

        $this->postStatisticsRepository
            ->shouldReceive('getPopularPostsByPeriod')
            ->once()
            ->with($period, 10)
            ->andReturn($popularPosts);

        $this->calculationService
            ->shouldReceive('calculateAverageViewsPerPost')
            ->once()
            ->with($snapshot)
            ->andReturn(15.0);

        $this->calculationService
            ->shouldReceive('getPreviousPeriod')
            ->andReturn($period);

        $this->calculationService
            ->shouldReceive('calculateTrendDirection')
            ->andReturn('up');

        $this->calculationService
            ->shouldReceive('calculateVolatility')
            ->andReturn(0.1);

        // Act
        $result = $this->service->getPostAnalytics($period);

        // Assert
        $this->assertArrayHasKey('snapshot', $result);
        $this->assertArrayHasKey('source_analysis', $result);
        $this->assertArrayHasKey('trend_analysis', $result);
        $this->assertArrayHasKey('popular_posts', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertEquals(100, $result['summary']['total_posts']);
        $this->assertEquals(1500, $result['summary']['total_views']);
        $this->assertEquals(15.0, $result['summary']['avg_views_per_post']);
    }

    /**
     * @test
     * @group T3.1
     */
    public function batchGenerateSnapshots_應該處理多個週期的快照生成(): void
    {
        // Arrange
        $period1 = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $period2 = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-08'),
            new DateTimeImmutable('2024-01-14'),
        );

        $periods = [$period1, $period2];

        // 設定第一個週期成功
        $this->validationService
            ->shouldReceive('validatePeriod')
            ->with($period1);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->with($period1)
            ->andReturn(null);

        $this->postStatisticsRepository
            ->shouldReceive('countPostsByPeriod')
            ->with($period1)
            ->andReturn(50);

        $this->postStatisticsRepository
            ->shouldReceive('countViewsByPeriod')
            ->with($period1)
            ->andReturn(750);

        $this->sourceAnalysisService
            ->shouldReceive('analyzeByPeriod')
            ->with($period1)
            ->andReturn([]);

        $this->statisticsRepository
            ->shouldReceive('saveSnapshot')
            ->with(Mockery::type(StatisticsSnapshot::class));

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->with($period1);

        // 設定第二個週期成功
        $this->validationService
            ->shouldReceive('validatePeriod')
            ->with($period2);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->with($period2)
            ->andReturn(null);

        $this->postStatisticsRepository
            ->shouldReceive('countPostsByPeriod')
            ->with($period2)
            ->andReturn(60);

        $this->postStatisticsRepository
            ->shouldReceive('countViewsByPeriod')
            ->with($period2)
            ->andReturn(900);

        $this->sourceAnalysisService
            ->shouldReceive('analyzeByPeriod')
            ->with($period2)
            ->andReturn([]);

        $this->statisticsRepository
            ->shouldReceive('saveSnapshot')
            ->with(Mockery::type(StatisticsSnapshot::class));

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->with($period2);

        // Act
        $result = $this->service->batchGenerateSnapshots($periods);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(0, $result['error_count']);
        $this->assertCount(2, $result['successful']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     * @group T3.1
     */
    public function batchGenerateSnapshots_應該處理無效週期物件的錯誤(): void
    {
        // Arrange
        $validPeriod = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $invalidPeriod = 'invalid_period';

        $periods = [$validPeriod, $invalidPeriod];

        // 設定有效週期成功
        $this->validationService
            ->shouldReceive('validatePeriod')
            ->with($validPeriod);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->with($validPeriod)
            ->andReturn(null);

        $this->postStatisticsRepository
            ->shouldReceive('countPostsByPeriod')
            ->with($validPeriod)
            ->andReturn(50);

        $this->postStatisticsRepository
            ->shouldReceive('countViewsByPeriod')
            ->with($validPeriod)
            ->andReturn(750);

        $this->sourceAnalysisService
            ->shouldReceive('analyzeByPeriod')
            ->with($validPeriod)
            ->andReturn([]);

        $this->statisticsRepository
            ->shouldReceive('saveSnapshot')
            ->with(Mockery::type(StatisticsSnapshot::class));

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->with($validPeriod);

        // Act
        $result = $this->service->batchGenerateSnapshots($periods);

        // Assert
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['error_count']);
        $this->assertCount(1, $result['successful']);
        $this->assertCount(1, $result['failed']);

        $this->assertStringContains('無效的週期物件', $result['failed'][0]['error']);
    }

    /**
     * @test
     * @group T3.1
     */
    public function getStatisticsSummary_應該回傳系統統計摘要(): void
    {
        // Arrange
        $currentSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $currentSnapshot->shouldReceive('getTotalPosts->getValue')->andReturn(100);
        $currentSnapshot->shouldReceive('getTotalViews->getValue')->andReturn(1500);
        $currentSnapshot->shouldReceive('getSourceStats')->andReturn([[], []]);

        $monthSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $monthSnapshot->shouldReceive('getTotalPosts->getValue')->andReturn(400);
        $monthSnapshot->shouldReceive('getTotalViews->getValue')->andReturn(6000);
        $monthSnapshot->shouldReceive('getSourceStats')->andReturn([[], []]);

        $lastWeekSnapshot = Mockery::mock(StatisticsSnapshot::class);

        $oldestSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $oldestSnapshot->shouldReceive('getCreatedAt')->andReturn(new DateTimeImmutable('2024-01-01'));

        $latestSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $latestSnapshot->shouldReceive('getCreatedAt')->andReturn(new DateTimeImmutable('2024-01-15'));

        // 設定快取回傳
        $this->cacheService
            ->shouldReceive('getPeriodStatistics')
            ->andReturn($currentSnapshot, $monthSnapshot);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->andReturn($lastWeekSnapshot);

        $this->calculationService
            ->shouldReceive('calculateGrowthRate')
            ->with($lastWeekSnapshot, $currentSnapshot)
            ->andReturn(['posts' => 10.5, 'views' => 15.2]);

        $this->statisticsRepository
            ->shouldReceive('getTotalSnapshotCount')
            ->andReturn(25);

        $this->statisticsRepository
            ->shouldReceive('getOldestSnapshot')
            ->andReturn($oldestSnapshot);

        $this->statisticsRepository
            ->shouldReceive('getLatestSnapshot')
            ->andReturn($latestSnapshot);

        // Mock 週期驗證
        $this->validationService
            ->shouldReceive('validatePeriod')
            ->times(2);

        // Act
        $result = $this->service->getStatisticsSummary();

        // Assert
        $this->assertArrayHasKey('current_week', $result);
        $this->assertArrayHasKey('current_month', $result);
        $this->assertArrayHasKey('growth', $result);
        $this->assertArrayHasKey('system_stats', $result);

        $this->assertEquals(100, $result['current_week']['posts']);
        $this->assertEquals(1500, $result['current_week']['views']);
        $this->assertEquals(2, $result['current_week']['sources']);

        $this->assertEquals(10.5, $result['growth']['posts_growth_rate']);
        $this->assertEquals(15.2, $result['growth']['views_growth_rate']);

        $this->assertEquals(25, $result['system_stats']['total_snapshots']);
    }

    /**
     * @test
     * @group T3.1
     */
    public function cleanupExpiredSnapshots_應該清理過期的統計快照(): void
    {
        // Arrange
        $retentionDays = 30;
        $expectedCutoffDate = new DateTimeImmutable('-30 days');

        $expiredSnapshot1 = Mockery::mock(StatisticsSnapshot::class);
        $expiredSnapshot1->shouldReceive('getId')->andReturn(Uuid::generate());
        $expiredSnapshot1->shouldReceive('getPeriod')->andReturn(Mockery::mock(StatisticsPeriod::class));

        $expiredSnapshot2 = Mockery::mock(StatisticsSnapshot::class);
        $expiredSnapshot2->shouldReceive('getId')->andReturn(Uuid::generate());
        $expiredSnapshot2->shouldReceive('getPeriod')->andReturn(Mockery::mock(StatisticsPeriod::class));

        $expiredSnapshots = [$expiredSnapshot1, $expiredSnapshot2];

        $this->statisticsRepository
            ->shouldReceive('findExpiredSnapshots')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class))
            ->andReturn($expiredSnapshots);

        $this->statisticsRepository
            ->shouldReceive('deleteSnapshot')
            ->twice();

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->twice();

        $this->statisticsRepository
            ->shouldReceive('getTotalSnapshotCount')
            ->once()
            ->andReturn(23);

        // Act
        $result = $this->service->cleanupExpiredSnapshots($retentionDays);

        // Assert
        $this->assertEquals(2, $result['deleted_count']);
        $this->assertEquals(30, $result['retention_days']);
        $this->assertEquals(23, $result['remaining_snapshots']);
        $this->assertInstanceOf(DateTimeImmutable::class, $result['cutoff_date']);
    }

    /**
     * @test
     * @group T3.1
     */
    public function rebuildSnapshot_應該刪除現有快照並重新生成(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $existingSnapshot = Mockery::mock(StatisticsSnapshot::class);
        $existingSnapshot->shouldReceive('getId')->andReturn(Uuid::generate());

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->twice()
            ->with($period);

        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->once()
            ->with($period)
            ->andReturn($existingSnapshot);

        $this->statisticsRepository
            ->shouldReceive('deleteSnapshot')
            ->once()
            ->with($existingSnapshot->getId());

        $this->cacheService
            ->shouldReceive('invalidatePeriodCache')
            ->twice()
            ->with($period);

        // 設定重新生成的 Mock
        $this->statisticsRepository
            ->shouldReceive('findByPeriod')
            ->once()
            ->with($period)
            ->andReturn(null);

        $this->postStatisticsRepository
            ->shouldReceive('countPostsByPeriod')
            ->once()
            ->with($period)
            ->andReturn(100);

        $this->postStatisticsRepository
            ->shouldReceive('countViewsByPeriod')
            ->once()
            ->with($period)
            ->andReturn(1500);

        $this->sourceAnalysisService
            ->shouldReceive('analyzeByPeriod')
            ->once()
            ->with($period)
            ->andReturn([]);

        $this->statisticsRepository
            ->shouldReceive('saveSnapshot')
            ->once()
            ->with(Mockery::type(StatisticsSnapshot::class));

        // Act
        $result = $this->service->rebuildSnapshot($period);

        // Assert
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    /**
     * @test
     * @group T3.1
     */
    public function generatePeriodSnapshot_當發生異常時_應該拋出StatisticsCalculationException(): void
    {
        // Arrange
        $period = StatisticsPeriod::create(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-07'),
        );

        $this->validationService
            ->shouldReceive('validatePeriod')
            ->once()
            ->with($period)
            ->andThrow(new RuntimeException('驗證失敗'));

        // Act & Assert
        $this->expectException(StatisticsCalculationException::class);
        $this->expectExceptionMessage('生成統計快照失敗: 驗證失敗');

        $this->service->generatePeriodSnapshot($period);
    }
}
