<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Services\StatisticsCacheService;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Domain\ValueObjects\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * StatisticsCacheService 測試類別.
 *
 * 驗證 T4.2 - 實作統計快取服務的各項功能
 */
class StatisticsCacheServiceTest extends TestCase
{
    private StatisticsCacheService $cacheService;

    private CacheServiceInterface $mockPersistentCache;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock 持久快取
        $this->mockPersistentCache = $this->createMock(CacheServiceInterface::class);

        // 建立測試對象
        $this->cacheService = new StatisticsCacheService($this->mockPersistentCache);
    }

    /**
     * @test
     * T4.2 驗收標準：支援多層次快取策略
     */
    public function 支援多層次快取策略(): void
    {
        // 準備測試資料
        $period = StatisticsPeriod::today();
        $snapshot = $this->createTestSnapshot($period);

        // 測試快取儲存
        $this->cacheService->cacheSnapshot($period, $snapshot);

        // 測試從記憶體快取取得
        $cachedSnapshot = $this->cacheService->getCachedSnapshot($period);

        $this->assertNotNull($cachedSnapshot);
        $this->assertEquals($snapshot->getId()->toString(), $cachedSnapshot->getId()->toString());
    }

    /**
     * @test
     * T4.2 驗收標準：快取標籤管理
     */
    public function 支援快取標籤管理(): void
    {
        // 準備測試資料
        $dailyPeriod = StatisticsPeriod::today();
        $weeklyPeriod = StatisticsPeriod::thisWeek();

        $dailySnapshot = $this->createTestSnapshot($dailyPeriod);
        $weeklySnapshot = $this->createTestSnapshot($weeklyPeriod);

        // 快取不同週期的統計
        $this->cacheService->cacheSnapshot($dailyPeriod, $dailySnapshot);
        $this->cacheService->cacheSnapshot($weeklyPeriod, $weeklySnapshot);

        // 測試按標籤使快取失效
        $invalidatedCount = $this->cacheService->invalidatePostStatistics();

        // 驗證快取被清除
        $this->assertNull($this->cacheService->getCachedSnapshot($dailyPeriod));
        $this->assertNull($this->cacheService->getCachedSnapshot($weeklyPeriod));
    }

    /**
     * @test
     * T4.2 驗收標準：快取預熱機制
     */
    public function 支援快取預熱機制(): void
    {
        // 測試智能預熱
        $dataProvider = function (StatisticsPeriod $period): StatisticsSnapshot {
            return $this->createTestSnapshot($period);
        };

        $result = $this->cacheService->intelligentWarmup($dataProvider);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warmed_up_count', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('total_requested', $result);
        $this->assertGreaterThan(0, $result['warmed_up_count']);
    }

    /**
     * @test
     * T4.2 驗收標準：快取失效邏輯
     */
    public function 包含快取失效邏輯(): void
    {
        // 準備測試資料
        $period = StatisticsPeriod::today();
        $snapshot = $this->createTestSnapshot($period);

        // 快取資料
        $this->cacheService->cacheSnapshot($period, $snapshot);
        $this->assertNotNull($this->cacheService->getCachedSnapshot($period));

        // 測試使快取失效
        $this->cacheService->invalidateCache($period);
        $this->assertNull($this->cacheService->getCachedSnapshot($period));
    }

    /**
     * @test
     * T4.2 驗收標準：快取統計資訊
     */
    public function 提供快取統計資訊(): void
    {
        // 準備測試資料
        $period = StatisticsPeriod::today();
        $snapshot = $this->createTestSnapshot($period);

        // 快取資料
        $this->cacheService->cacheSnapshot($period, $snapshot);

        // 取得統計資訊
        $stats = $this->cacheService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('memory_cache', $stats);
        $this->assertArrayHasKey('persistent_cache', $stats);
        $this->assertArrayHasKey('total_tags', $stats);
        $this->assertArrayHasKey('cache_levels', $stats);
    }

    /**
     * @test
     * T4.2 驗收標準：批量快取操作
     */
    public function 支援批量快取操作(): void
    {
        // 準備測試資料
        $items = [
            [
                'period' => StatisticsPeriod::today(),
                'snapshot' => $this->createTestSnapshot(StatisticsPeriod::today()),
            ],
            [
                'period' => StatisticsPeriod::yesterday(),
                'snapshot' => $this->createTestSnapshot(StatisticsPeriod::yesterday()),
            ],
        ];

        // 批量快取
        $this->cacheService->batchCacheSnapshots($items);

        // 驗證所有項目都被快取
        foreach ($items as $item) {
            $cachedSnapshot = $this->cacheService->getCachedSnapshot($item['period']);
            $this->assertNotNull($cachedSnapshot);
        }
    }

    /**
     * 建立測試用的統計快照.
     */
    private function createTestSnapshot(StatisticsPeriod $period): StatisticsSnapshot
    {
        $sourceStats = [
            SourceStatistics::create(
                SourceType::WEB,
                60, // count
                60.0, // percentage
            ),
            SourceStatistics::create(
                SourceType::MOBILE_APP,
                40, // count
                40.0, // percentage
            ),
        ];

        $additionalMetrics = [
            'avg_views_per_post' => StatisticsMetric::ratio(10.0, 'views per post'),
        ];

        return StatisticsSnapshot::create(
            Uuid::generate(),
            $period,
            100, // totalPosts
            1000, // totalViews
            $sourceStats,
            $additionalMetrics,
        );
    }
}
