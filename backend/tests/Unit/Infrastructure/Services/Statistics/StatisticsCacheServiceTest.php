<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Statistics;

use App\Domains\Statistics\Services\StatisticsCacheService;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\Enums\PeriodType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * 統計快取服務單元測試
 *
 * 測試統計快取服務的核心功能，包含：
 * - 快取寫入與讀取
 * - 快取失效機制
 * - 快取鍵管理
 * - 錯誤處理
 *
 * @covers \App\Domains\Statistics\Services\StatisticsCacheService
 */
final class StatisticsCacheServiceTest extends TestCase
{
    private StatisticsCacheService $cacheService;
    private MockObject|CacheManagerInterface $mockCacheManager;
    private MockObject|TaggedCacheInterface $mockTaggedCache;
    private MockObject|LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCacheManager = $this->createMock(CacheManagerInterface::class);
        $this->mockTaggedCache = $this->createMock(TaggedCacheInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->cacheService = new StatisticsCacheService(
            $this->mockCacheManager,
            $this->mockLogger
        );
    }

    /**
     * 測試快取資料寫入
     *
     * @test
     */
    public function should_store_cache_data_correctly(): void
    {
        // Arrange
        $cacheKey = 'test_statistics_key';
        $normalizedKey = 'statistics:v1:test_statistics_key';
        $statisticsData = [
            'posts' => ['total_count' => 100],
            'users' => ['active_users' => 50]
        ];
        $ttl = 300; // 5 分鐘

        $this->mockCacheManager
            ->expects($this->once())
            ->method('set')
            ->with($normalizedKey, $statisticsData, $ttl)
            ->willReturn(true);

        // Act
        $result = $this->cacheService->set($cacheKey, $statisticsData, $ttl);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * 測試快取資料讀取
     *
     * @test
     */
    public function should_retrieve_cache_data_correctly(): void
    {
        // Arrange
        $cacheKey = 'test_statistics_key';
        $normalizedKey = 'statistics:v1:test_statistics_key';
        $expectedData = [
            'posts' => ['total_count' => 100],
            'users' => ['active_users' => 50]
        ];

        $this->mockCacheManager
            ->expects($this->once())
            ->method('get')
            ->with($normalizedKey, null)
            ->willReturn($expectedData);

        // Act
        $result = $this->cacheService->get($cacheKey);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    /**
     * 測試快取未命中情況
     *
     * @test
     */
    public function should_return_default_when_cache_miss(): void
    {
        // Arrange
        $cacheKey = 'test_statistics_key';
        $normalizedKey = 'statistics:v1:test_statistics_key';
        $defaultValue = ['default' => 'data'];

        $this->mockCacheManager
            ->expects($this->once())
            ->method('get')
            ->with($normalizedKey, $defaultValue)
            ->willReturn($defaultValue);

        // Act
        $result = $this->cacheService->get($cacheKey, $defaultValue);

        // Assert
        $this->assertEquals($defaultValue, $result);
    }

    /**
     * 測試概覽快取鍵生成
     *
     * @test
     */
    public function should_generate_overview_cache_key_correctly(): void
    {
        // Arrange
        $period = $this->createDailyPeriod();

        // Act
        $cacheKey = $this->cacheService->getOverviewCacheKey($period);

        // Assert
        $this->assertEquals('overview:daily:2024-01-01', $cacheKey);
    }

    /**
     * 建立每日週期測試資料
     */
    private function createDailyPeriod(): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable('2024-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-01 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::DAILY);
    }
}
