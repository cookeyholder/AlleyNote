<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use RuntimeException;
use Throwable;

/**
 * 統計快照事務適配器.
 *
 * 在統計 Repository 之上添加事務管理功能，確保資料一致性。
 * 適用於需要原子性操作的統計資料處理場景。
 */
final class StatisticsRepositoryTransactionAdapter implements StatisticsRepositoryInterface
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $repository,
        private readonly PDO $db,
    ) {}

    public function findById(int $id): ?StatisticsSnapshot
    {
        return $this->repository->findById($id);
    }

    public function findByUuid(string $uuid): ?StatisticsSnapshot
    {
        return $this->repository->findByUuid($uuid);
    }

    public function findByTypeAndPeriod(string $snapshotType, StatisticsPeriod $period): ?StatisticsSnapshot
    {
        return $this->repository->findByTypeAndPeriod($snapshotType, $period);
    }

    public function findLatestByType(string $snapshotType): ?StatisticsSnapshot
    {
        return $this->repository->findLatestByType($snapshotType);
    }

    public function findByTypeAndDateRange(
        string $snapshotType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array {
        return $this->repository->findByTypeAndDateRange($snapshotType, $startDate, $endDate);
    }

    public function findExpiredSnapshots(?DateTimeInterface $beforeDate = null): array
    {
        return $this->repository->findExpiredSnapshots($beforeDate);
    }

    public function save(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        return $this->executeInTransaction(function () use ($snapshot) {
            return $this->repository->save($snapshot);
        });
    }

    public function update(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        return $this->executeInTransaction(function () use ($snapshot) {
            return $this->repository->update($snapshot);
        });
    }

    public function delete(StatisticsSnapshot $snapshot): bool
    {
        return $this->executeInTransaction(function () use ($snapshot) {
            return $this->repository->delete($snapshot);
        });
    }

    public function deleteById(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            return $this->repository->deleteById($id);
        });
    }

    public function deleteExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        return $this->executeInTransaction(function () use ($beforeDate) {
            return $this->repository->deleteExpiredSnapshots($beforeDate);
        });
    }

    public function exists(string $snapshotType, StatisticsPeriod $period): bool
    {
        return $this->repository->exists($snapshotType, $period);
    }

    public function count(?string $snapshotType = null): int
    {
        return $this->repository->count($snapshotType);
    }

    public function findByTypeWithPagination(
        string $snapshotType,
        int $page = 1,
        int $limit = 20,
        string $orderBy = 'created_at',
        string $direction = 'desc',
    ): array {
        return $this->repository->findByTypeWithPagination($snapshotType, $page, $limit, $orderBy, $direction);
    }

    /**
     * 批量儲存統計快照（事務處理）.
     *
     * @param StatisticsSnapshot[] $snapshots 要儲存的快照陣列
     * @return StatisticsSnapshot[] 儲存後的快照陣列
     * @throws RuntimeException 當任一快照儲存失敗時
     */
    public function batchSave(array $snapshots): array
    {
        return $this->executeInTransaction(function () use ($snapshots) {
            $savedSnapshots = [];
            foreach ($snapshots as $snapshot) {
                $savedSnapshots[] = $this->repository->save($snapshot);
            }

            return $savedSnapshots;
        });
    }

    /**
     * 批量更新統計快照（事務處理）.
     *
     * @param StatisticsSnapshot[] $snapshots 要更新的快照陣列
     * @return StatisticsSnapshot[] 更新後的快照陣列
     * @throws RuntimeException 當任一快照更新失敗時
     */
    public function batchUpdate(array $snapshots): array
    {
        return $this->executeInTransaction(function () use ($snapshots) {
            $updatedSnapshots = [];
            foreach ($snapshots as $snapshot) {
                $updatedSnapshots[] = $this->repository->update($snapshot);
            }

            return $updatedSnapshots;
        });
    }

    /**
     * 批量刪除統計快照（事務處理）.
     *
     * @param StatisticsSnapshot[] $snapshots 要刪除的快照陣列
     * @return int 成功刪除的數量
     * @throws RuntimeException 當任一快照刪除失敗時
     */
    public function batchDelete(array $snapshots): int
    {
        return $this->executeInTransaction(function () use ($snapshots) {
            $deletedCount = 0;
            foreach ($snapshots as $snapshot) {
                if ($this->repository->delete($snapshot)) {
                    $deletedCount++;
                }
            }

            return $deletedCount;
        });
    }

    /**
     * 替換指定類型的統計快照（先刪除再新增）.
     *
     * @param string $snapshotType 快照類型
     * @param StatisticsSnapshot[] $newSnapshots 新的快照陣列
     * @return StatisticsSnapshot[] 儲存後的快照陣列
     * @throws RuntimeException 當操作失敗時
     */
    public function replaceByType(string $snapshotType, array $newSnapshots): array
    {
        return $this->executeInTransaction(function () use ($snapshotType, $newSnapshots) {
            // 先取得現有的快照
            $existingSnapshots = $this->repository->findByTypeAndDateRange(
                $snapshotType,
                new DateTimeImmutable('1970-01-01'),
                new DateTimeImmutable('2099-12-31'),
            );

            // 刪除現有快照
            foreach ($existingSnapshots as $snapshot) {
                $this->repository->delete($snapshot);
            }

            // 儲存新快照
            $savedSnapshots = [];
            foreach ($newSnapshots as $snapshot) {
                $savedSnapshots[] = $this->repository->save($snapshot);
            }

            return $savedSnapshots;
        });
    }

    /**
     * 在事務中執行操作.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws RuntimeException 當操作失敗時
     */
    private function executeInTransaction(callable $operation): mixed
    {
        try {
            $this->db->beginTransaction();

            $result = $operation();

            $this->db->commit();

            return $result;
        } catch (Throwable $e) {
            $this->db->rollBack();

            throw new RuntimeException(
                '事務執行失敗: ' . $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }
}
