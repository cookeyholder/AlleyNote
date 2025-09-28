<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Models\StatisticsSnapshot;

/**
 * 統計快照存儲介面.
 */
interface StatisticsSnapshotRepositoryInterface
{
    /**
     * 根據 ID 取得統計快照.
     */
    public function findById(int $id): ?StatisticsSnapshot;

    /**
     * 根據 UUID 取得統計快照.
     */
    public function findByUuid(string $uuid): ?StatisticsSnapshot;

    /**
     * 根據類型和週期取得統計快照.
     */
    public function findByTypeAndPeriod(
        string $snapshotType,
        string $periodType,
        string $periodStart,
        string $periodEnd,
    ): ?StatisticsSnapshot;

    /**
     * 取得特定類型的統計快照列表.
     *
     * @return StatisticsSnapshot[]
     */
    public function findBySnapshotType(string $snapshotType, int $limit = 100, int $offset = 0): array;

    /**
     * 取得特定週期類型的統計快照列表.
     *
     * @return StatisticsSnapshot[]
     */
    public function findByPeriodType(string $periodType, int $limit = 100, int $offset = 0): array;

    /**
     * 取得指定時間範圍內的統計快照.
     *
     * @return StatisticsSnapshot[]
     */
    public function findByDateRange(string $startDate, string $endDate, int $limit = 100, int $offset = 0): array;

    /**
     * 建立新的統計快照.
     */
    public function create(array $data): StatisticsSnapshot;

    /**
     * 更新統計快照.
     */
    public function update(int $id, array $data): bool;

    /**
     * 刪除統計快照.
     */
    public function delete(int $id): bool;

    /**
     * 計算總數.
     */
    public function count(): int;

    /**
     * 計算特定類型的總數.
     */
    public function countBySnapshotType(string $snapshotType): int;

    /**
     * 檢查是否存在特定類型和週期的快照.
     */
    public function exists(string $snapshotType, string $periodType, string $periodStart, string $periodEnd): bool;

    /**
     * 取得最新的統計快照.
     *
     * @return StatisticsSnapshot[]
     */
    public function findLatest(int $limit = 10): array;

    /**
     * 根據觀看次數排序取得統計快照.
     *
     * @return StatisticsSnapshot[]
     */
    public function findByTotalViews(int $minViews = 0, string $order = 'DESC', int $limit = 100): array;
}
