<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\StatisticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * StatisticsRepository 單元測試.
 */
#[Group('statistics')]
#[Group('repository')]
#[Group('unit')]
final class StatisticsRepositoryTest extends UnitTestCase
{
    private PDO&MockObject $mockPdo;

    private PDOStatement&MockObject $mockStatement;

    private StatisticsRepository $repository;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->repository = new StatisticsRepository($this->mockPdo);
    }

    public function testFindByIdReturnsSnapshotWhenFound(): void
    {
        $id = 1;
        $row = $this->createSampleRow();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':id', $id, PDO::PARAM_INT);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($row);

        $result = $this->repository->findById($id);

        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertSame($row['id'], $result->getId());
        $this->assertSame($row['uuid'], $result->getUuid());
        $this->assertSame($row['snapshot_type'], $result->getSnapshotType());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $id = 999;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->findById($id);

        $this->assertNull($result);
    }

    public function testFindByIdThrowsExceptionOnDatabaseError(): void
    {
        $id = 1;
        $exception = new PDOException('Database error');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('查詢統計快照失敗 (ID: 1)');

        $this->repository->findById($id);
    }

    public function testFindByUuidReturnsSnapshotWhenFound(): void
    {
        $uuid = 'test-uuid';
        $row = $this->createSampleRow(['uuid' => $uuid]);

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':uuid', $uuid, PDO::PARAM_STR);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($row);

        $result = $this->repository->findByUuid($uuid);

        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertSame($uuid, $result->getUuid());
    }

    public function testFindByUuidThrowsExceptionForEmptyUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID 不能為空');

        $this->repository->findByUuid('');
    }

    public function testFindByTypeAndPeriodReturnsSnapshotWhenFound(): void
    {
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;
        $period = $this->createSamplePeriod();
        $row = $this->createSampleRow([
            'snapshot_type' => $snapshotType,
            'period_type'   => $period->type->value,
        ]);

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(4))
            ->method('bindValue')
            ->with(
                $this->callback(static function ($param) {
                    return in_array($param, [':snapshot_type', ':period_type', ':period_start', ':period_end'], true);
                }),
            );

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($row);

        $result = $this->repository->findByTypeAndPeriod($snapshotType, $period);

        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertSame($snapshotType, $result->getSnapshotType());
    }

    public function testFindByTypeAndPeriodThrowsExceptionForEmptyType(): void
    {
        $period = $this->createSamplePeriod();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('快照類型不能為空');

        $this->repository->findByTypeAndPeriod('', $period);
    }

    public function testSaveReturnsSnapshotWithId(): void
    {
        $snapshot = $this->createSampleSnapshot();
        $insertedId = 123;

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        // First prepare call for insert
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt
            ->expects($this->once())
            ->method('execute');

        // Second prepare call for findById
        $findStmt = $this->createMock(PDOStatement::class);
        $findStmt
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($this->createSampleRow(['id' => $insertedId]));

        $this->mockPdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($insertStmt, $findStmt);

        $this->mockPdo
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn((string) $insertedId);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->save($snapshot);

        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
    }

    public function testSaveRollsBackOnException(): void
    {
        $snapshot = $this->createSampleSnapshot();
        $exception = new PDOException('Insert failed');

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->mockPdo
            ->expects($this->once())
            ->method('rollBack');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('儲存統計快照失敗');

        $this->repository->save($snapshot);
    }

    public function testUpdateThrowsExceptionForSnapshotWithoutId(): void
    {
        $snapshot = $this->createSampleSnapshot();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無法更新沒有 ID 的統計快照');

        $this->repository->update($snapshot);
    }

    public function testDeleteByIdReturnsTrue(): void
    {
        $id = 1;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':id', $id, PDO::PARAM_INT);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->deleteById($id);

        $this->assertTrue($result);
    }

    public function testDeleteByIdReturnsFalse(): void
    {
        $id = 999;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->repository->deleteById($id);

        $this->assertFalse($result);
    }

    public function testExistsReturnsTrue(): void
    {
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;
        $period = $this->createSamplePeriod();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(4))
            ->method('bindValue');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $result = $this->repository->exists($snapshotType, $period);

        $this->assertTrue($result);
    }

    public function testExistsReturnsFalse(): void
    {
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;
        $period = $this->createSamplePeriod();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(0);

        $result = $this->repository->exists($snapshotType, $period);

        $this->assertFalse($result);
    }

    public function testCountReturnsTotal(): void
    {
        $expectedCount = 42;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedCount);

        $result = $this->repository->count();

        $this->assertSame($expectedCount, $result);
    }

    public function testCountByTypeReturnsCount(): void
    {
        $snapshotType = StatisticsSnapshot::TYPE_OVERVIEW;
        $expectedCount = 10;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with(':snapshot_type', $snapshotType, PDO::PARAM_STR);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expectedCount);

        $result = $this->repository->count($snapshotType);

        $this->assertSame($expectedCount, $result);
    }

    public function testFindByTypeWithPaginationThrowsExceptionForInvalidPage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('頁碼必須大於 0');

        $this->repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 0);
    }

    public function testFindByTypeWithPaginationThrowsExceptionForInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁數量必須在 1-1000 之間');

        $this->repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 1, 0);
    }

    public function testFindByTypeWithPaginationThrowsExceptionForInvalidDirection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('排序方向只能是 asc 或 desc');

        $this->repository->findByTypeWithPagination(
            StatisticsSnapshot::TYPE_OVERVIEW,
            1,
            20,
            'created_at',
            'invalid',
        );
    }

    public function testFindByTypeWithPaginationThrowsExceptionForInvalidOrderBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的排序欄位: invalid_field');

        $this->repository->findByTypeWithPagination(
            StatisticsSnapshot::TYPE_OVERVIEW,
            1,
            20,
            'invalid_field',
        );
    }

    /**
     * 建立測試用的統計快照.
     */
    private function createSampleSnapshot(): StatisticsSnapshot
    {
        $period = $this->createSamplePeriod();
        $data = [
            'uuid'            => 'test-uuid-123',
            'snapshot_type'   => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type'     => $period->type->value,
            'period_start'    => $period->startTime->format('Y-m-d H:i:s'),
            'period_end'      => $period->endTime->format('Y-m-d H:i:s'),
            'statistics_data' => '{"total_posts": 100}',
            'metadata'        => '{}',
            'created_at'      => '2023-01-01 00:00:00',
            'updated_at'      => '2023-01-01 00:00:00',
        ];

        return StatisticsSnapshot::fromArray($data);
    }

    /**
     * 建立測試用的統計週期.
     */
    private function createSamplePeriod(): StatisticsPeriod
    {
        return new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2023-01-01 00:00:00'),
            new DateTimeImmutable('2023-01-01 23:59:59'),
        );
    }

    /**
     * 建立測試用的資料庫記錄.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function createSampleRow(array $overrides = []): array
    {
        return array_merge([
            'id'                   => 1,
            'uuid'                 => 'test-uuid-123',
            'snapshot_type'        => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type'          => PeriodType::DAILY->value,
            'period_start'         => '2023-01-01 00:00:00',
            'period_end'           => '2023-01-01 23:59:59',
            'statistics_data'      => '{"total_posts": 100}',
            'metadata'             => '{}',
            'expires_at'           => null,
            'total_views'          => 0,
            'total_unique_viewers' => 0,
            'created_at'           => '2023-01-01 00:00:00',
            'updated_at'           => '2023-01-01 00:00:00',
        ], $overrides);
    }

    // ========== 以下使用真實 SQLite 資料庫驗證完整 CRUD 流程 ==========

    private PDO $realDb;

    /**
     * 建立真實 SQLite 連線與 statistics_snapshots 表.
     */
    private function createRealRepository(): StatisticsRepository
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE statistics_snapshots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                snapshot_type TEXT NOT NULL,
                period_type TEXT NOT NULL,
                period_start DATETIME NOT NULL,
                period_end DATETIME NOT NULL,
                statistics_data TEXT NOT NULL DEFAULT "{}",
                metadata TEXT NOT NULL DEFAULT "{}",
                expires_at DATETIME,
                total_views INTEGER NOT NULL DEFAULT 0,
                total_unique_viewers INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ');
        $this->realDb = $pdo;

        return new StatisticsRepository($pdo);
    }

    private function createTestSnapshot(string $type = StatisticsSnapshot::TYPE_OVERVIEW, ?DateTimeImmutable $expiresAt = null): StatisticsSnapshot
    {
        return StatisticsSnapshot::create(
            $type,
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2023-05-01 00:00:00'),
                new DateTimeImmutable('2023-05-01 23:59:59'),
            ),
            ['total_count' => 42],
            ['source'      => 'test'],
            $expiresAt,
        );
    }

    public function testSaveAndFindRoundTripWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $snapshot = $this->createTestSnapshot();

        $saved = $repository->save($snapshot);

        $this->assertGreaterThan(0, $saved->getId());
        $found = $repository->findById($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getUuid(), $found->getUuid());
        $this->assertSame(42, $found->getTotalCount());
    }

    public function testFindByUuidWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $saved = $repository->save($this->createTestSnapshot());

        $found = $repository->findByUuid($saved->getUuid());
        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID 不能為空');
        $repository->findByUuid('   ');
    }

    public function testFindLatestByTypeReturnsMostRecentSnapshot(): void
    {
        $repository = $this->createRealRepository();
        $first = $repository->save($this->createTestSnapshot());
        $second = $repository->save($this->createTestSnapshot());

        $latest = $repository->findLatestByType(StatisticsSnapshot::TYPE_OVERVIEW);

        $this->assertNotNull($latest);
        $this->assertSame($second->getId(), $latest->getId());
        $this->assertNotSame($first->getId(), $latest->getId());
    }

    public function testFindByTypeAndPeriodWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $snapshot = $this->createTestSnapshot();
        $saved = $repository->save($snapshot);

        $found = $repository->findByTypeAndPeriod(StatisticsSnapshot::TYPE_OVERVIEW, $snapshot->getPeriod());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
    }

    public function testExistsWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $snapshot = $this->createTestSnapshot();

        $this->assertFalse($repository->exists(StatisticsSnapshot::TYPE_OVERVIEW, $snapshot->getPeriod()));

        $repository->save($snapshot);

        $this->assertTrue($repository->exists(StatisticsSnapshot::TYPE_OVERVIEW, $snapshot->getPeriod()));
    }

    public function testCountWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $this->assertSame(0, $repository->count());

        $repository->save($this->createTestSnapshot());
        $repository->save($this->createTestSnapshot(StatisticsSnapshot::TYPE_POSTS));

        $this->assertSame(2, $repository->count());
        $this->assertSame(1, $repository->count(StatisticsSnapshot::TYPE_POSTS));
    }

    public function testUpdateWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $saved = $repository->save($this->createTestSnapshot());

        $saved->updateStatistics(['total_count' => 999]);
        $updated = $repository->update($saved);

        $this->assertSame(999, $updated->getTotalCount());

        // 更新不存在的快照應拋出例外（rowCount 為 0）
        $ghost = $this->createTestSnapshot(StatisticsSnapshot::TYPE_USERS);
        $reflection = new ReflectionProperty($ghost, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($ghost, 987654);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('統計快照不存在或更新失敗');
        $repository->update($ghost);
    }

    public function testDeleteByIdAndDeleteWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $saved = $repository->save($this->createTestSnapshot());

        $this->assertTrue($repository->deleteById($saved->getId()));
        $this->assertFalse($repository->deleteById($saved->getId()));

        $another = $repository->save($this->createTestSnapshot());
        $this->assertTrue($repository->delete($another));
    }

    public function testExpiredSnapshotsLifecycleWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $repository->save($this->createTestSnapshot(
            StatisticsSnapshot::TYPE_POPULAR,
            new DateTimeImmutable('2023-01-02 00:00:00'),
        ));
        $repository->save($this->createTestSnapshot());

        $expiredBefore = new DateTimeImmutable('2024-01-01');
        $expired = $repository->findExpiredSnapshots($expiredBefore);
        $this->assertCount(1, $expired);

        $deletedCount = $repository->deleteExpiredSnapshots($expiredBefore);
        $this->assertSame(1, $deletedCount);
        $this->assertSame(1, $repository->count());
    }

    public function testFindByTypeAndDateRangeWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $repository->save($this->createTestSnapshot());

        $results = $repository->findByTypeAndDateRange(
            StatisticsSnapshot::TYPE_OVERVIEW,
            new DateTimeImmutable('2023-04-30 00:00:00'),
            new DateTimeImmutable('2023-05-31 23:59:59'),
        );

        $this->assertCount(1, $results);

        // 開始日期晚於結束日期應拋出例外
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('開始日期必須小於結束日期');
        $repository->findByTypeAndDateRange(
            StatisticsSnapshot::TYPE_OVERVIEW,
            new DateTimeImmutable('2023-06-01'),
            new DateTimeImmutable('2023-05-01'),
        );
    }

    public function testFindByTypeWithPaginationValidationAndResults(): void
    {
        $repository = $this->createRealRepository();
        for ($i = 0; $i < 5; $i++) {
            $repository->save($this->createTestSnapshot());
        }

        $page1 = $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 1, 3, 'created_at', 'desc');
        $this->assertCount(3, $page1);

        $page2 = $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 2, 3);
        $this->assertCount(2, $page2);

        // 參數驗證分支
        try {
            $repository->findByTypeWithPagination('', 1, 20);
            $this->fail('空白類型應拋出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('快照類型不能為空', $e->getMessage());
        }

        try {
            $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 0, 20);
            $this->fail('頁碼 0 應拋出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('頁碼必須大於 0', $e->getMessage());
        }

        try {
            $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 1, 1001);
            $this->fail('超量 limit 應拋出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('每頁數量必須在 1-1000 之間', $e->getMessage());
        }

        try {
            $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 1, 10, 'created_at', 'sideways');
            $this->fail('無效排序方向應拋出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('排序方向只能是 asc 或 desc', $e->getMessage());
        }

        try {
            $repository->findByTypeWithPagination(StatisticsSnapshot::TYPE_OVERVIEW, 1, 10, 'password');
            $this->fail('非白名單排序欄位應拋出例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('不支援的排序欄位', $e->getMessage());
        }
    }

    public function testFindMethodsReturnNullWhenRecordMissing(): void
    {
        $repository = $this->createRealRepository();

        $this->assertNull($repository->findById(99999));
        $this->assertNull($repository->findByUuid('no-such-uuid'));
        $this->assertNull($repository->findLatestByType(StatisticsSnapshot::TYPE_USERS));
        $this->assertNull($repository->findByTypeAndPeriod(
            StatisticsSnapshot::TYPE_OVERVIEW,
            new StatisticsPeriod(
                PeriodType::WEEKLY,
                new DateTimeImmutable('2030-01-01 00:00:00'),
                new DateTimeImmutable('2030-01-07 23:59:59'),
            ),
        ));
    }

    public function testValidationBranchesForEmptyArguments(): void
    {
        $repository = $this->createRealRepository();
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2023-05-01 00:00:00'),
            new DateTimeImmutable('2023-05-01 23:59:59'),
        );

        $emptyTypeCases = [
            'findByUuid'               => static fn() => $repository->findByUuid(''),
            'findByTypeAndPeriod'      => static fn() => $repository->findByTypeAndPeriod('', $period),
            'findLatestByType'         => static fn() => $repository->findLatestByType('  '),
            'exists'                   => static fn() => $repository->exists('', $period),
            'findByTypeWithPagination' => static fn() => $repository->findByTypeWithPagination(' ', 1, 20),
            'findByTypeAndDateRange'   => static fn() => $repository->findByTypeAndDateRange(
                '',
                new DateTimeImmutable('2023-01-01'),
                new DateTimeImmutable('2023-02-01'),
            ),
        ];

        foreach ($emptyTypeCases as $name => $invoke) {
            try {
                $invoke();
                $this->fail("{$name} 空參數應拋出例外");
            } catch (InvalidArgumentException $e) {
                $this->addToAssertionCount(1);
            }
        }

        // 刪除沒有 ID 的快照應拋出例外
        $unsaved = $this->createTestSnapshot();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無法刪除沒有 ID 的統計快照');
        $repository->delete($unsaved);
    }

    public function testAllMethodsWrapUnexpectedPdoErrors(): void
    {
        $repository = $this->createRealRepository();
        $snapshot = $this->createTestSnapshot();
        $period = $snapshot->getPeriod();

        // 移除資料表，讓所有查詢拋出 PDOException
        $this->realDb->exec('DROP TABLE statistics_snapshots');

        // 為 update 準備帶有 ID 的快照（通過 ID 前置檢查）
        $withId = $this->createTestSnapshot();
        $idProperty = new ReflectionProperty($withId, 'id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($withId, 1);

        $cases = [
            'findById'               => static fn() => $repository->findById(1),
            'findByUuid'             => static fn() => $repository->findByUuid('uuid-x'),
            'findByTypeAndPeriod'    => static fn() => $repository->findByTypeAndPeriod('overview', $period),
            'findLatestByType'       => static fn() => $repository->findLatestByType('overview'),
            'findByTypeAndDateRange' => static fn() => $repository->findByTypeAndDateRange(
                'overview',
                new DateTimeImmutable('2023-01-01'),
                new DateTimeImmutable('2023-02-01'),
            ),
            'findExpiredSnapshots'   => static fn() => $repository->findExpiredSnapshots(),
            'save'                   => static fn() => $repository->save($snapshot),
            'update'                 => static fn() => $repository->update($withId),
            'delete'                 => static fn() => $repository->deleteById(1),
            'deleteExpiredSnapshots' => static fn() => $repository->deleteExpiredSnapshots(),
            'exists'                 => static fn() => $repository->exists('overview', $period),
            'countAll'               => static fn() => $repository->count(),
            'countByType'            => static fn() => $repository->count('overview'),
            'pagination'             => static fn() => $repository->findByTypeWithPagination('overview', 1, 20),
        ];

        foreach ($cases as $name => $invoke) {
            try {
                $invoke();
                $this->fail("{$name} 在資料表不存在時應拋出 RuntimeException");
            } catch (RuntimeException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testMapRowToEntityWrapsInvalidSnapshotRows(): void
    {
        $repository = $this->createRealRepository();

        // 直接寫入無效的快照類型，映射實體時應失敗並包裝例外
        $this->realDb->exec("
            INSERT INTO statistics_snapshots
                (uuid, snapshot_type, period_type, period_start, period_end, statistics_data, metadata, created_at, updated_at)
            VALUES
                ('bad-uuid', 'not_a_type', 'daily', '2023-05-01 00:00:00', '2023-05-01 23:59:59', '{}', '{}', '2023-05-02 00:00:00', '2023-05-02 00:00:00')
        ");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('實體映射失敗');
        $repository->findByUuid('bad-uuid');
    }
}
