<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Enums\LogLevel;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 統計快照日誌適配器.
 *
 * 在統計 Repository 之上添加日誌記錄功能，用於監控和偵錯。
 * 記錄所有重要的資料庫操作，包括執行時間和結果。
 */
final class StatisticsRepositoryLoggingAdapter implements StatisticsRepositoryInterface
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
    ) {}

    public function findById(int $id): ?StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $snapshot = $this->repository->findById($id);

            $this->logOperation('findById', [
                'id' => $id,
                'found' => $snapshot !== null,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logError('findById', $e, ['id' => $id]);

            throw $e;
        }
    }

    public function findByUuid(string $uuid): ?StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $snapshot = $this->repository->findByUuid($uuid);

            $this->logOperation('findByUuid', [
                'uuid' => $uuid,
                'found' => $snapshot !== null,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logError('findByUuid', $e, ['uuid' => $uuid]);

            throw $e;
        }
    }

    public function findByTypeAndPeriod(string $snapshotType, StatisticsPeriod $period): ?StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $snapshot = $this->repository->findByTypeAndPeriod($snapshotType, $period);

            $this->logOperation('findByTypeAndPeriod', [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
                'found' => $snapshot !== null,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logError('findByTypeAndPeriod', $e, [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
            ]);

            throw $e;
        }
    }

    public function findLatestByType(string $snapshotType): ?StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $snapshot = $this->repository->findLatestByType($snapshotType);

            $this->logOperation('findLatestByType', [
                'snapshot_type' => $snapshotType,
                'found' => $snapshot !== null,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logError('findLatestByType', $e, ['snapshot_type' => $snapshotType]);

            throw $e;
        }
    }

    public function findByTypeAndDateRange(
        string $snapshotType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array {
        $startTime = microtime(true);

        try {
            $snapshots = $this->repository->findByTypeAndDateRange($snapshotType, $startDate, $endDate);

            $this->logOperation('findByTypeAndDateRange', [
                'snapshot_type' => $snapshotType,
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
                'count' => count($snapshots),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshots;
        } catch (Throwable $e) {
            $this->logError('findByTypeAndDateRange', $e, [
                'snapshot_type' => $snapshotType,
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
            ]);

            throw $e;
        }
    }

    public function findExpiredSnapshots(?DateTimeInterface $beforeDate = null): array
    {
        $startTime = microtime(true);

        try {
            $snapshots = $this->repository->findExpiredSnapshots($beforeDate);

            $this->logOperation('findExpiredSnapshots', [
                'before_date' => $beforeDate ? $beforeDate->format('Y-m-d H:i:s') : 'current_time',
                'count' => count($snapshots),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshots;
        } catch (Throwable $e) {
            $this->logError('findExpiredSnapshots', $e, [
                'before_date' => $beforeDate ? $beforeDate->format('Y-m-d H:i:s') : 'current_time',
            ]);

            throw $e;
        }
    }

    public function save(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $savedSnapshot = $this->repository->save($snapshot);

            $this->logOperation('save', [
                'snapshot_type' => $snapshot->getSnapshotType(),
                'period_type' => $snapshot->getPeriod()->type->value,
                'data_size' => strlen(json_encode($snapshot->getStatisticsData(), JSON_THROW_ON_ERROR)),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ], LogLevel::INFO);

            return $savedSnapshot;
        } catch (Throwable $e) {
            $this->logError('save', $e, [
                'snapshot_type' => $snapshot->getSnapshotType(),
                'period_type' => $snapshot->getPeriod()->type->value,
            ]);

            throw $e;
        }
    }

    public function update(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            $updatedSnapshot = $this->repository->update($snapshot);

            $this->logOperation('update', [
                'snapshot_type' => $snapshot->getSnapshotType(),
                'period_type' => $snapshot->getPeriod()->type->value,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $updatedSnapshot;
        } catch (Throwable $e) {
            $this->logError('update', $e, [
                'snapshot_id' => $snapshot->getId(),
                'snapshot_type' => $snapshot->getSnapshotType(),
            ]);

            throw $e;
        }
    }

    public function delete(StatisticsSnapshot $snapshot): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->repository->delete($snapshot);

            $this->logOperation('delete', [
                'snapshot_type' => $snapshot->getSnapshotType(),
                'result' => $result,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logError('delete', $e, [
                'snapshot_id' => $snapshot->getId(),
                'snapshot_type' => $snapshot->getSnapshotType(),
            ]);

            throw $e;
        }
    }

    public function deleteById(int $id): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->repository->deleteById($id);

            $this->logOperation('deleteById', [
                'id' => $id,
                'result' => $result,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logError('deleteById', $e, ['id' => $id]);

            throw $e;
        }
    }

    public function deleteExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        $startTime = microtime(true);

        try {
            $deletedCount = $this->repository->deleteExpiredSnapshots($beforeDate);

            $this->logOperation('deleteExpiredSnapshots', [
                'before_date' => $beforeDate ? $beforeDate->format('Y-m-d H:i:s') : 'current_time',
                'deleted_count' => $deletedCount,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ], LogLevel::INFO);

            return $deletedCount;
        } catch (Throwable $e) {
            $this->logError('deleteExpiredSnapshots', $e, [
                'before_date' => $beforeDate ? $beforeDate->format('Y-m-d H:i:s') : 'current_time',
            ]);

            throw $e;
        }
    }

    public function exists(string $snapshotType, StatisticsPeriod $period): bool
    {
        $startTime = microtime(true);

        try {
            $exists = $this->repository->exists($snapshotType, $period);

            $this->logOperation('exists', [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
                'result' => $exists,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $exists;
        } catch (Throwable $e) {
            $this->logError('exists', $e, [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
            ]);

            throw $e;
        }
    }

    public function count(?string $snapshotType = null): int
    {
        $startTime = microtime(true);

        try {
            $count = $this->repository->count($snapshotType);

            $this->logOperation('count', [
                'snapshot_type' => $snapshotType,
                'result' => $count,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $count;
        } catch (Throwable $e) {
            $this->logError('count', $e, ['snapshot_type' => $snapshotType]);

            throw $e;
        }
    }

    public function findByTypeWithPagination(
        string $snapshotType,
        int $page = 1,
        int $limit = 20,
        string $orderBy = 'created_at',
        string $direction = 'desc',
    ): array {
        $startTime = microtime(true);

        try {
            $snapshots = $this->repository->findByTypeWithPagination(
                $snapshotType,
                $page,
                $limit,
                $orderBy,
                $direction,
            );

            $this->logOperation('findByTypeWithPagination', [
                'snapshot_type' => $snapshotType,
                'page' => $page,
                'limit' => $limit,
                'order_by' => $orderBy,
                'direction' => $direction,
                'count' => count($snapshots),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            return $snapshots;
        } catch (Throwable $e) {
            $this->logError('findByTypeWithPagination', $e, [
                'snapshot_type' => $snapshotType,
                'page' => $page,
                'limit' => $limit,
            ]);

            throw $e;
        }
    }

    /**
     * 記錄操作日誌.
     */
    private function logOperation(string $operation, array $context = [], LogLevel $level = LogLevel::DEBUG): void
    {
        $message = sprintf('Statistics Repository Operation: %s', $operation);

        match ($level) {
            LogLevel::INFO => $this->logger->info($message, $context),
            LogLevel::WARNING => $this->logger->warning($message, $context),
            LogLevel::ERROR => $this->logger->error($message, $context),
            default => $this->logger->debug($message, $context),
        };
    }

    /**
     * 記錄錯誤日誌.
     */
    private function logError(string $operation, Throwable $exception, array $context = []): void
    {
        $this->logger->error(
            sprintf('Statistics Repository Error: %s failed', $operation),
            array_merge($context, [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]),
        );
    }
}
