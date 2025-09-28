<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Contracts\CacheServiceInterface;
use DateTimeInterface;

/**
 * 統計快照快取適配器.
 *
 * 在統計 Repository 之上添加快取層，提升查詢效能。
 * 使用基本快取操作來管理統計快照的快取。
 */
final class StatisticsRepositoryCacheAdapter implements StatisticsRepositoryInterface
{
    private const CACHE_PREFIX = 'statistics_snapshot';

    private const CACHE_TTL = 3600; // 1 小時

    public function __construct(
        private readonly StatisticsRepositoryInterface $repository,
        private readonly CacheServiceInterface $cache,
    ) {}

    public function findById(int $id): ?StatisticsSnapshot
    {
        $cacheKey = $this->getCacheKey('id', (string) $id);

        /** @var StatisticsSnapshot|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshot = $this->repository->findById($id);
        if ($snapshot !== null) {
            $this->cacheSnapshot($snapshot, $cacheKey);
        }

        return $snapshot;
    }

    public function findByUuid(string $uuid): ?StatisticsSnapshot
    {
        $cacheKey = $this->getCacheKey('uuid', $uuid);

        /** @var StatisticsSnapshot|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshot = $this->repository->findByUuid($uuid);
        if ($snapshot !== null) {
            $this->cacheSnapshot($snapshot, $cacheKey);
        }

        return $snapshot;
    }

    public function findByTypeAndPeriod(string $snapshotType, StatisticsPeriod $period): ?StatisticsSnapshot
    {
        $cacheKey = $this->getCacheKey('type_period', $snapshotType . '_' . $this->getPeriodKey($period));

        /** @var StatisticsSnapshot|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshot = $this->repository->findByTypeAndPeriod($snapshotType, $period);
        if ($snapshot !== null) {
            $this->cacheSnapshot($snapshot, $cacheKey);
        }

        return $snapshot;
    }

    public function findLatestByType(string $snapshotType): ?StatisticsSnapshot
    {
        $cacheKey = $this->getCacheKey('latest_type', $snapshotType);

        /** @var StatisticsSnapshot|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshot = $this->repository->findLatestByType($snapshotType);
        if ($snapshot !== null) {
            $this->cacheSnapshot($snapshot, $cacheKey);
        }

        return $snapshot;
    }

    public function findByTypeAndDateRange(
        string $snapshotType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array {
        $cacheKey = $this->getCacheKey(
            'type_range',
            $snapshotType . '_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'),
        );

        /** @var StatisticsSnapshot[]|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshots = $this->repository->findByTypeAndDateRange($snapshotType, $startDate, $endDate);

        if (!empty($snapshots)) {
            $this->cache->set($cacheKey, $snapshots, self::CACHE_TTL);
        }

        return $snapshots;
    }

    public function findExpiredSnapshots(?DateTimeInterface $beforeDate = null): array
    {
        // 過期快照查詢不適合快取，直接委託給 Repository
        return $this->repository->findExpiredSnapshots($beforeDate);
    }

    public function save(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        $savedSnapshot = $this->repository->save($snapshot);

        // 儲存後清除相關快取
        $this->invalidateRelatedCache($savedSnapshot);

        return $savedSnapshot;
    }

    public function update(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        $updatedSnapshot = $this->repository->update($snapshot);

        // 更新後清除相關快取
        $this->invalidateRelatedCache($updatedSnapshot);

        return $updatedSnapshot;
    }

    public function delete(StatisticsSnapshot $snapshot): bool
    {
        $result = $this->repository->delete($snapshot);

        if ($result) {
            $this->invalidateRelatedCache($snapshot);
        }

        return $result;
    }

    public function deleteById(int $id): bool
    {
        // 先嘗試從快取或資料庫取得快照以便清除相關快取
        $snapshot = $this->findById($id);

        $result = $this->repository->deleteById($id);

        if ($result && $snapshot !== null) {
            $this->invalidateRelatedCache($snapshot);
        }

        return $result;
    }

    public function deleteExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        $deletedCount = $this->repository->deleteExpiredSnapshots($beforeDate);

        if ($deletedCount > 0) {
            // 批量刪除後清除統計相關的快取模式
            $this->cache->deletePattern(self::CACHE_PREFIX . ':*');
        }

        return $deletedCount;
    }

    public function exists(string $snapshotType, StatisticsPeriod $period): bool
    {
        // exists 查詢輕量，可以快取
        $cacheKey = $this->getCacheKey('exists', $snapshotType . '_' . $this->getPeriodKey($period));

        /** @var bool|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $exists = $this->repository->exists($snapshotType, $period);

        $this->cache->set($cacheKey, $exists, self::CACHE_TTL);

        return $exists;
    }

    public function count(?string $snapshotType = null): int
    {
        $cacheKey = $this->getCacheKey('count', $snapshotType ?? 'all');

        /** @var int|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $count = $this->repository->count($snapshotType);

        $this->cache->set($cacheKey, $count, self::CACHE_TTL);

        return $count;
    }

    public function findByTypeWithPagination(
        string $snapshotType,
        int $page = 1,
        int $limit = 20,
        string $orderBy = 'created_at',
        string $direction = 'desc',
    ): array {
        $cacheKey = $this->getCacheKey(
            'paginated',
            $snapshotType . '_' . $page . '_' . $limit . '_' . $orderBy . '_' . $direction,
        );

        /** @var StatisticsSnapshot[]|null $cached */
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $snapshots = $this->repository->findByTypeWithPagination(
            $snapshotType,
            $page,
            $limit,
            $orderBy,
            $direction,
        );

        if (!empty($snapshots)) {
            $this->cache->set($cacheKey, $snapshots, self::CACHE_TTL);
        }

        return $snapshots;
    }

    /**
     * 產生快取金鑰.
     */
    private function getCacheKey(string $operation, string $identifier): string
    {
        return self::CACHE_PREFIX . ':' . $operation . ':' . $identifier;
    }

    /**
     * 從統計週期產生快取金鑰識別符.
     */
    private function getPeriodKey(StatisticsPeriod $period): string
    {
        return $period->type->value . '_'
               . $period->startTime->format('Y-m-d-H-i') . '_'
               . $period->endTime->format('Y-m-d-H-i');
    }

    /**
     * 快取統計快照.
     */
    private function cacheSnapshot(StatisticsSnapshot $snapshot, string $cacheKey): void
    {
        $this->cache->set($cacheKey, $snapshot, self::CACHE_TTL);
    }

    /**
     * 清除與快照相關的快取.
     */
    private function invalidateRelatedCache(StatisticsSnapshot $snapshot): void
    {
        // 使用模式匹配清除相關的快取項目
        $snapshotType = $snapshot->getSnapshotType();

        // 清除特定類型相關的快取模式
        $this->cache->deletePattern(self::CACHE_PREFIX . ':*' . $snapshotType . '*');

        // 清除可能包含此快照的其他快取項目
        $this->cache->deletePattern(self::CACHE_PREFIX . ':latest_type:' . $snapshotType);
        $this->cache->deletePattern(self::CACHE_PREFIX . ':count:' . $snapshotType);
        $this->cache->deletePattern(self::CACHE_PREFIX . ':count:all');
    }
}
