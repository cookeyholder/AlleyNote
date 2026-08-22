<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryTransactionAdapter;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsRepositoryTransactionAdapter 單元測試.
 *
 * 使用真實 SQLite 連線驗證交易包裝、批次操作與失敗回滾。
 */
final class StatisticsRepositoryTransactionAdapterTest extends UnitTestCase
{
    private PDO $pdo;

    private MockInterface&StatisticsRepositoryInterface $repository;

    private StatisticsRepositoryTransactionAdapter $adapter;

    private StatisticsSnapshot $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        /** @var MockInterface&StatisticsRepositoryInterface $repository */
        $repository = Mockery::mock(StatisticsRepositoryInterface::class);
        $this->repository = $repository;
        $this->adapter = new StatisticsRepositoryTransactionAdapter($repository, $this->pdo);
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
            ['views' => 10],
        );
    }

    #[Test]
    public function readOperationsBypassTransactions(): void
    {
        $this->repository->shouldReceive('findById')->with(1)->once()->andReturn($this->snapshot);
        $this->assertSame($this->snapshot, $this->adapter->findById(1));

        $this->repository->shouldReceive('findByUuid')->with('u')->once()->andReturn(null);
        $this->assertNull($this->adapter->findByUuid('u'));

        $start = new DateTimeImmutable('2025-02-01');
        $end = new DateTimeImmutable('2025-02-28');
        $this->repository->shouldReceive('findByTypeAndDateRange')->once()->andReturn([]);
        $this->assertSame([], $this->adapter->findByTypeAndDateRange(StatisticsSnapshot::TYPE_POSTS, $start, $end));

        $this->repository->shouldReceive('findExpiredSnapshots')->with(null)->once()->andReturn([]);
        $this->assertSame([], $this->adapter->findExpiredSnapshots());

        $this->repository->shouldReceive('exists')->once()->andReturn(false);
        $this->assertFalse($this->adapter->exists(StatisticsSnapshot::TYPE_OVERVIEW, $this->snapshot->getPeriod()));

        $this->repository->shouldReceive('count')->with(null)->once()->andReturn(5);
        $this->assertSame(5, $this->adapter->count());

        $this->repository->shouldReceive('findByTypeWithPagination')->once()->andReturn([$this->snapshot]);
        $paginated = $this->adapter->findByTypeWithPagination(StatisticsSnapshot::TYPE_POSTS, 1, 20);
        $this->assertCount(1, $paginated);

        // 讀取操作不應開啟交易
        $this->assertFalse($this->pdo->inTransaction());
    }

    #[Test]
    public function saveUpdateDeleteRunInsideCommittedTransactions(): void
    {
        $saved = $this->createSnapshot();
        $this->repository->shouldReceive('save')->once()->andReturn($saved);
        $result = $this->adapter->save($this->snapshot);
        $this->assertSame($saved, $result);
        $this->assertFalse($this->pdo->inTransaction(), '交易已提交');

        $updated = $this->createSnapshot();
        $this->repository->shouldReceive('update')->once()->andReturn($updated);
        $this->assertSame($updated, $this->adapter->update($this->snapshot));

        $this->repository->shouldReceive('delete')->once()->andReturn(true);
        $this->assertTrue($this->adapter->delete($this->snapshot));

        $this->repository->shouldReceive('deleteById')->with(3)->once()->andReturn(true);
        $this->assertTrue($this->adapter->deleteById(3));

        $before = new DateTimeImmutable('2024-01-01');
        $this->repository->shouldReceive('deleteExpiredSnapshots')->with(null)->once()->andReturn(2);
        $this->assertSame(2, $this->adapter->deleteExpiredSnapshots());
    }

    #[Test]
    public function transactionFailureRollsBackAndWrapsException(): void
    {
        $failure = new RuntimeException('constraint violation');
        $this->repository->shouldReceive('save')->andThrow($failure);

        try {
            $this->adapter->save($this->snapshot);
            $this->fail('預期拋出 RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith('事務執行失敗', $e->getMessage());
            $this->assertSame($failure, $e->getPrevious());
        }
        // 回滾後不應處於交易中
        $this->assertFalse($this->pdo->inTransaction());
    }

    #[Test]
    public function batchSavePersistsEverySnapshot(): void
    {
        $first = $this->snapshot;
        $second = $this->createSnapshot();
        $savedFirst = $this->createSnapshot();
        $savedSecond = $this->createSnapshot();

        $matcher = $this->repository->shouldReceive('save')->times(2);
        $matcher->andReturn($savedFirst, $savedSecond);

        $results = $this->adapter->batchSave([$first, $second]);
        $this->assertSame([$savedFirst, $savedSecond], $results);
    }

    #[Test]
    public function batchOperationsPropagateFailureAfterRollback(): void
    {
        $this->repository->shouldReceive('update')->andThrow(new RuntimeException('stale data'));

        try {
            $this->adapter->batchUpdate([$this->snapshot]);
            $this->fail('預期拋出例外');
        } catch (RuntimeException) {
            $this->addToAssertionCount(1);
        }

        // batchDelete 統計成功刪除數
        $first = $this->createSnapshot();
        $second = $this->createSnapshot();
        $deleteMatcher = $this->repository->shouldReceive('delete')->times(2);
        $deleteMatcher->andReturn(true, false);
        $this->assertSame(1, $this->adapter->batchDelete([$first, $second]));
    }

    #[Test]
    public function replaceByTypeDeletesExistingThenInsertsNew(): void
    {
        $existingOld = $this->createSnapshot();
        $newSnapshot = $this->createSnapshot();
        $savedNew = $this->createSnapshot();
        $type = StatisticsSnapshot::TYPE_POPULAR;

        $rangeMatcher = $this->repository->shouldReceive('findByTypeAndDateRange')
            ->with($type, Mockery::on(static fn($d): bool => $d instanceof DateTimeImmutable), Mockery::on(static fn($d): bool => $d instanceof DateTimeImmutable))
            ->once()
            ->andReturn([$existingOld]);
        $rangeMatcher->ordered();

        $deleteMatcher = $this->repository->shouldReceive('delete')->with($existingOld)->once()->andReturn(true);
        $deleteMatcher->ordered();

        $saveMatcher = $this->repository->shouldReceive('save')->with($newSnapshot)->once()->andReturn($savedNew);
        $saveMatcher->ordered();

        $results = $this->adapter->replaceByType($type, [$newSnapshot]);
        $this->assertSame([$savedNew], $results);
    }
}
