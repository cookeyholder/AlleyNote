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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StatisticsRepository 單元測試.
 *
 * @group statistics
 * @group repository
 * @group unit
 */
final class StatisticsRepositoryTest extends TestCase
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
            'period_type' => $period->type->value,
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
            'uuid' => 'test-uuid-123',
            'snapshot_type' => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type' => $period->type->value,
            'period_start' => $period->startTime->format('Y-m-d H:i:s'),
            'period_end' => $period->endTime->format('Y-m-d H:i:s'),
            'statistics_data' => '{"total_posts": 100}',
            'metadata' => '{}',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
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
     * @return array<string, mixed>
     */
    private function createSampleRow(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'uuid' => 'test-uuid-123',
            'snapshot_type' => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type' => PeriodType::DAILY->value,
            'period_start' => '2023-01-01 00:00:00',
            'period_end' => '2023-01-01 23:59:59',
            'statistics_data' => '{"total_posts": 100}',
            'metadata' => '{}',
            'expires_at' => null,
            'total_views' => 0,
            'total_unique_viewers' => 0,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00',
        ], $overrides);
    }
}
