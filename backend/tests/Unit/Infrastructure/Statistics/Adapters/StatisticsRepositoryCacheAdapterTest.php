<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryCacheAdapter;
use App\Shared\Contracts\CacheServiceInterface;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * StatisticsRepositoryCacheAdapter 單元測試.
 *
 * 驗證快取讀取優先、未命中回寫、以及寫入操作後的快取失效邏輯。
 */
final class StatisticsRepositoryCacheAdapterTest extends UnitTestCase
{
    private const PREFIX = 'statistics_snapshot';

    private MockInterface&StatisticsRepositoryInterface $repository;

    private MockInterface&CacheServiceInterface $cache;

    private StatisticsRepositoryCacheAdapter $adapter;

    private StatisticsSnapshot $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var MockInterface&StatisticsRepositoryInterface $repository */
        $repository = Mockery::mock(StatisticsRepositoryInterface::class);
        /** @var MockInterface&CacheServiceInterface $cache */
        $cache = Mockery::mock(CacheServiceInterface::class);
        $this->repository = $repository;
        $this->cache = $cache;
        $this->adapter = new StatisticsRepositoryCacheAdapter($repository, $cache);
        $this->snapshot = $this->createSnapshot();
    }

    private function createSnapshot(): StatisticsSnapshot
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-01-01 23:59:59'),
        );

        return StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            ['views' => 42],
        );
    }

    #[Test]
    public function findByIdReturnsCachedValueWithoutHittingRepository(): void
    {
        $this->cache->shouldReceive('get')
            ->with(self::PREFIX . ':id:7')
            ->once()
            ->andReturn($this->snapshot);

        $result = $this->adapter->findById(7);
        $this->assertSame($this->snapshot, $result);
    }

    #[Test]
    public function findByIdFallsBackToRepositoryAndCachesResult(): void
    {
        $this->cache->shouldReceive('get')->once()->andReturn(null);
        $this->repository->shouldReceive('findById')->with(9)->once()->andReturn($this->snapshot);
        $this->cache->shouldReceive('set')
            ->with(self::PREFIX . ':id:9', $this->snapshot, 3600)
            ->once()
            ->andReturn(true);

        $this->assertSame($this->snapshot, $this->adapter->findById(9));
    }

    #[Test]
    public function findByUuidUsesDedicatedCacheKey(): void
    {
        $uuid = 'uuid-xyz';
        $this->cache->shouldReceive('get')->with(self::PREFIX . ':uuid:' . $uuid)->once()->andReturn(null);
        $this->repository->shouldReceive('findByUuid')->with($uuid)->once()->andReturn(null);
        // 未命中倉庫時不寫入快取
        $this->assertSame([], []);

        $this->assertNull($this->adapter->findByUuid($uuid));
    }

    #[Test]
    public function findByTypeAndPeriodBuildsKeyFromTypeAndRange(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2025-06-01 00:00'),
            new DateTimeImmutable('2025-06-30 23:59'),
        );
        $expectedKey = self::PREFIX . ':type_period:' . StatisticsSnapshot::TYPE_POSTS
            . '_monthly_2025-06-01-00-00_2025-06-30-23-59';

        $this->cache->shouldReceive('get')->with($expectedKey)->once()->andReturn(null);
        $this->repository->shouldReceive('findByTypeAndPeriod')->once()->andReturn($this->snapshot);
        $this->cache->shouldReceive('set')->with($expectedKey, $this->snapshot, 3600)->once()->andReturn(true);

        $this->assertSame(
            $this->snapshot,
            $this->adapter->findByTypeAndPeriod(StatisticsSnapshot::TYPE_POSTS, $period),
        );
    }

    #[Test]
    public function findLatestByTypeDelegatesWithCache(): void
    {
        $key = self::PREFIX . ':latest_type:' . StatisticsSnapshot::TYPE_USERS;
        $this->cache->shouldReceive('get')->with($key)->once()->andReturn(null);
        $this->repository->shouldReceive('findLatestByType')->once()->andReturn($this->snapshot);
        $this->cache->shouldReceive('set')->with($key, $this->snapshot, 3600)->once()->andReturn(true);

        $this->assertSame($this->snapshot, $this->adapter->findLatestByType(StatisticsSnapshot::TYPE_USERS));
    }

    #[Test]
    public function findByTypeAndDateRangeCachesOnlyNonEmptyResults(): void
    {
        $start = new DateTimeImmutable('2025-07-01');
        $end = new DateTimeImmutable('2025-07-31');
        $type = StatisticsSnapshot::TYPE_POPULAR;
        $key = self::PREFIX . ':type_range:' . $type . '_2025-07-01_2025-07-31';

        // 有結果時寫入快取
        $this->cache->shouldReceive('get')->with($key)->once()->andReturn(null);
        $this->repository->shouldReceive('findByTypeAndDateRange')->once()->andReturn([$this->snapshot]);
        $this->cache->shouldReceive('set')->with($key, [$this->snapshot], 3600)->once()->andReturn(true);
        $results = $this->adapter->findByTypeAndDateRange($type, $start, $end);
        $this->assertCount(1, $results);

        // 無結果時不寫入
        $emptyStart = new DateTimeImmutable('2025-08-01');
        $emptyEnd = new DateTimeImmutable('2025-08-31');
        $emptyKey = self::PREFIX . ':type_range:' . $type . '_2025-08-01_2025-08-31';
        $this->cache->shouldReceive('get')->with($emptyKey)->once()->andReturn(null);
        $this->repository->shouldReceive('findByTypeAndDateRange')->once()->andReturn([]);
        $this->assertSame([], $this->adapter->findByTypeAndDateRange($type, $emptyStart, $emptyEnd));

        // 命中快取時直接回傳陣列
        $cachedList = [$this->snapshot];
        $this->cache->shouldReceive('get')->with($key)->once()->andReturn($cachedList);
        $this->assertSame($cachedList, $this->adapter->findByTypeAndDateRange($type, $start, $end));
    }

    #[Test]
    public function findExpiredSnapshotsBypassesCache(): void
    {
        $before = new DateTimeImmutable('2025-09-01');
        $this->repository->shouldReceive('findExpiredSnapshots')->with($before)->once()->andReturn([]);

        $this->assertSame([], $this->adapter->findExpiredSnapshots($before));
    }

    #[Test]
    public function saveUpdateDeleteInvalidateRelatedCachePatterns(): void
    {
        $type = StatisticsSnapshot::TYPE_OVERVIEW;
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':*' . $type . '*')->times(3)->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':latest_type:' . $type)->times(3)->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':count:' . $type)->times(3)->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':count:all')->times(3)->andReturn(1);

        // save
        $saved = $this->createSnapshot();
        $this->repository->shouldReceive('save')->once()->andReturn($saved);
        $this->assertSame($saved, $this->adapter->save($this->snapshot));

        // update
        $updated = $this->createSnapshot();
        $this->repository->shouldReceive('update')->once()->andReturn($updated);
        $this->assertSame($updated, $this->adapter->update($this->snapshot));

        // delete 成功時才失效快取
        $this->repository->shouldReceive('delete')->once()->andReturn(true);
        $this->assertTrue($this->adapter->delete($this->snapshot));
    }

    #[Test]
    public function deleteSkipsInvalidationWhenRepositoryReportsFailure(): void
    {
        $this->repository->shouldReceive('delete')->once()->andReturn(false);

        $this->assertFalse($this->adapter->delete($this->snapshot));
    }

    #[Test]
    public function deleteByIdFetchesThenDeletesAndInvalidates(): void
    {
        $id = 15;
        // 先透過快取取得實體
        $this->cache->shouldReceive('get')->with(self::PREFIX . ':id:' . $id)->once()->andReturn($this->snapshot);
        $this->repository->shouldReceive('deleteById')->with($id)->once()->andReturn(true);
        $type = StatisticsSnapshot::TYPE_OVERVIEW;
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':*' . $type . '*')->once()->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':latest_type:' . $type)->once()->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':count:' . $type)->once()->andReturn(1);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':count:all')->once()->andReturn(1);

        $this->assertTrue($this->adapter->deleteById($id));
    }

    #[Test]
    public function deleteExpiredSnapshotsFlushesPatternWhenItemsRemoved(): void
    {
        $this->repository->shouldReceive('deleteExpiredSnapshots')->with(null)->once()->andReturn(4);
        $this->cache->shouldReceive('deletePattern')->with(self::PREFIX . ':*')->once()->andReturn(4);

        $this->assertSame(4, $this->adapter->deleteExpiredSnapshots());

        // 無刪除項目時不清快取
        $this->repository->shouldReceive('deleteExpiredSnapshots')->with(null)->once()->andReturn(0);
        $this->assertSame(0, $this->adapter->deleteExpiredSnapshots());
    }

    #[Test]
    public function existsCachesBooleanOutcome(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2025-10-06 00:00:00'),
            new DateTimeImmutable('2025-10-12 23:59:59'),
        );
        $type = StatisticsSnapshot::TYPE_OVERVIEW;
        $key = self::PREFIX . ':exists:' . $type . '_weekly_' . $period->startTime->format('Y-m-d-H-i')
            . '_' . $period->endTime->format('Y-m-d-H-i');

        $this->cache->shouldReceive('get')->with($key)->once()->andReturn(null);
        $this->repository->shouldReceive('exists')->once()->andReturn(true);
        // 布林結果也會被快取（false 為有效值）
        $this->cache->shouldReceive('set')->with($key, true, 3600)->once()->andReturn(true);
        $this->assertTrue($this->adapter->exists($type, $period));
    }

    #[Test]
    public function countCachesAggregatedValue(): void
    {
        $type = StatisticsSnapshot::TYPE_USERS;

        // 指定類型
        $typedKey = self::PREFIX . ':count:' . $type;
        $this->cache->shouldReceive('get')->with($typedKey)->once()->andReturn(null);
        $this->repository->shouldReceive('count')->with($type)->once()->andReturn(11);
        $this->cache->shouldReceive('set')->with($typedKey, 11, 3600)->once()->andReturn(true);
        $this->assertSame(11, $this->adapter->count($type));

        // 全部類型，且直接命中快取
        $allKey = self::PREFIX . ':count:all';
        $this->cache->shouldReceive('get')->with($allKey)->once()->andReturn(99);
        $this->assertSame(99, $this->adapter->count());
    }

    #[Test]
    public function findByTypeWithPaginationBuildsCompositeKey(): void
    {
        $type = StatisticsSnapshot::TYPE_POSTS;
        $key = self::PREFIX . ':paginated:' . $type . '_2_20_created_at_desc';

        $this->cache->shouldReceive('get')->with($key)->once()->andReturn(null);
        $this->repository->shouldReceive('findByTypeWithPagination')
            ->with($type, 2, 20, 'created_at', 'desc')
            ->once()
            ->andReturn([$this->snapshot]);
        $this->cache->shouldReceive('set')->with($key, [$this->snapshot], 3600)->once()->andReturn(true);

        $results = $this->adapter->findByTypeWithPagination($type, 2);
        $this->assertCount(1, $results);
    }
}
