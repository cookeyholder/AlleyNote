<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryLoggingAdapter;
use App\Shared\Enums\LogLevel;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsRepositoryLoggingAdapter 單元測試.
 *
 * 驗證所有倉庫操作的委派、日誌記錄與錯誤傳播。
 */
final class StatisticsRepositoryLoggingAdapterTest extends UnitTestCase
{
    private MockInterface&StatisticsRepositoryInterface $repository;

    private MockInterface&LoggerInterface $logger;

    private StatisticsRepositoryLoggingAdapter $adapter;

    private StatisticsSnapshot $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var MockInterface&StatisticsRepositoryInterface $repository */
        $repository = Mockery::mock(StatisticsRepositoryInterface::class);
        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $this->repository = $repository;
        $this->logger = $logger;
        $this->adapter = new StatisticsRepositoryLoggingAdapter($repository, $logger);
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
            ['views' => 100],
        );
    }

    #[Test]
    public function findByIdDelegatesAndLogsDebug(): void
    {
        $this->repository->shouldReceive('findById')->with(7)->once()->andReturn($this->snapshot);
        $this->logger->shouldReceive('debug')
            ->with(Mockery::pattern('/findById/'), Mockery::on(
                static fn(array $context): bool => ($context['id'] ?? null) === 7 && ($context['found'] ?? false) === true,
            ))
            ->once();

        $this->assertSame($this->snapshot, $this->adapter->findById(7));
    }

    #[Test]
    public function findByIdRethrowsAfterLoggingError(): void
    {
        $failure = new RuntimeException('db unavailable');
        $this->repository->shouldReceive('findById')->andThrow($failure);
        $this->logger->shouldReceive('error')->once();

        try {
            $this->adapter->findById(1);
            $this->fail('預期例外被重新拋出');
        } catch (RuntimeException $e) {
            $this->assertSame('db unavailable', $e->getMessage());
        }
    }

    #[Test]
    public function findByUuidDelegatesAndLogs(): void
    {
        $uuid = 'abc-123';
        $this->repository->shouldReceive('findByUuid')->with($uuid)->once()->andReturn(null);
        $this->logger->shouldReceive('debug')->once();

        $this->assertNull($this->adapter->findByUuid($uuid));
    }

    #[Test]
    public function findByTypeAndPeriodPassesContext(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2025-02-01 00:00:00'),
            new DateTimeImmutable('2025-02-01 23:59:59'),
        );
        $this->repository->shouldReceive('findByTypeAndPeriod')
            ->with(StatisticsSnapshot::TYPE_POSTS, $period)
            ->once()
            ->andReturn($this->snapshot);
        $this->logger->shouldReceive('debug')->once();

        $result = $this->adapter->findByTypeAndPeriod(StatisticsSnapshot::TYPE_POSTS, $period);
        $this->assertSame($this->snapshot, $result);
    }

    #[Test]
    public function findLatestByTypeDelegates(): void
    {
        $this->repository->shouldReceive('findLatestByType')
            ->with(StatisticsSnapshot::TYPE_USERS)
            ->once()
            ->andReturn(null);
        $this->logger->shouldReceive('debug')->once();

        $this->assertNull($this->adapter->findLatestByType(StatisticsSnapshot::TYPE_USERS));
    }

    #[Test]
    public function findersLogCountInformation(): void
    {
        $start = new DateTimeImmutable('2025-03-01');
        $end = new DateTimeImmutable('2025-03-31');

        $this->repository->shouldReceive('findByTypeAndDateRange')
            ->with(StatisticsSnapshot::TYPE_POPULAR, $start, $end)
            ->once()
            ->andReturn([$this->snapshot]);
        $this->logger->shouldReceive('debug')->once();
        $result = $this->adapter->findByTypeAndDateRange(StatisticsSnapshot::TYPE_POPULAR, $start, $end);
        $this->assertCount(1, $result);

        $this->repository->shouldReceive('findExpiredSnapshots')->with(null)->once()->andReturn([]);
        $this->logger->shouldReceive('debug')->once();
        $this->assertSame([], $this->adapter->findExpiredSnapshots());
    }

    #[Test]
    public function saveLogsAtInfoLevelWithPayloadSize(): void
    {
        $saved = $this->createSnapshot();
        $this->repository->shouldReceive('save')->with($this->snapshot)->once()->andReturn($saved);
        $this->logger->shouldReceive('info')
            ->with(Mockery::pattern('/save/'), Mockery::on(static fn(array $c): bool => isset($c['data_size'])))
            ->once();

        $result = $this->adapter->save($this->snapshot);
        $this->assertSame($saved, $result);
    }

    #[Test]
    public function updateDeleteAndCountersDelegate(): void
    {
        $updated = $this->createSnapshot();
        $this->repository->shouldReceive('update')->with($this->snapshot)->once()->andReturn($updated);
        $this->logger->shouldReceive('debug')->twice();
        $this->assertSame($updated, $this->adapter->update($this->snapshot));

        $this->repository->shouldReceive('delete')->with($this->snapshot)->once()->andReturn(true);
        $this->assertTrue($this->adapter->delete($this->snapshot));

        $this->repository->shouldReceive('deleteById')->with(42)->once()->andReturn(false);
        $this->assertFalse($this->adapter->deleteById(42));
    }

    #[Test]
    public function deleteExpiredSnapshotsLogsInfoLevel(): void
    {
        $before = new DateTimeImmutable('2025-04-01');
        $this->repository->shouldReceive('deleteExpiredSnapshots')->with($before)->once()->andReturn(3);
        // 刪除操作以 INFO 等級記錄
        $this->logger->shouldReceive('info')->once();

        $this->assertSame(3, $this->adapter->deleteExpiredSnapshots($before));
    }

    #[Test]
    public function existsAndCountDelegateWithResults(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2025-05-01'),
            new DateTimeImmutable('2025-05-31'),
        );
        $this->repository->shouldReceive('exists')->with(StatisticsSnapshot::TYPE_OVERVIEW, $period)->once()->andReturn(true);
        $this->logger->shouldReceive('debug')->once();
        $this->assertTrue($this->adapter->exists(StatisticsSnapshot::TYPE_OVERVIEW, $period));

        $this->repository->shouldReceive('count')->with(StatisticsSnapshot::TYPE_USERS)->once()->andReturn(9);
        $this->logger->shouldReceive('debug')->once();
        $this->assertSame(9, $this->adapter->count(StatisticsSnapshot::TYPE_USERS));
    }

    #[Test]
    public function findByTypeWithPaginationPassesAllArguments(): void
    {
        $this->repository->shouldReceive('findByTypeWithPagination')
            ->with(StatisticsSnapshot::TYPE_POSTS, 2, 50, 'created_at', 'asc')
            ->once()
            ->andReturn([$this->snapshot]);
        $this->logger->shouldReceive('debug')->once();

        $result = $this->adapter->findByTypeWithPagination(StatisticsSnapshot::TYPE_POSTS, 2, 50, 'created_at', 'asc');
        $this->assertCount(1, $result);
    }

    #[Test]
    public function logLevelsMapCorrectly(): void
    {
        // 以反射驗證 logOperation 的等級對應（INFO/WARNING/ERROR/DEBUG）
        $reflection = new ReflectionClass($this->adapter);
        $method = $reflection->getMethod('logOperation');
        $method->setAccessible(true);

        $this->logger->shouldReceive('info')->once();
        $method->invoke($this->adapter, 'op-info', [], LogLevel::INFO);

        $this->logger->shouldReceive('warning')->once();
        $method->invoke($this->adapter, 'op-warning', [], LogLevel::WARNING);

        $this->logger->shouldReceive('error')->once();
        $method->invoke($this->adapter, 'op-error', [], LogLevel::ERROR);

        $this->logger->shouldReceive('debug')->once();
        $method->invoke($this->adapter, 'op-debug');

        // Mockery 期望已驗證四種等級的呼叫
        $this->addToAssertionCount(4);
    }
}
