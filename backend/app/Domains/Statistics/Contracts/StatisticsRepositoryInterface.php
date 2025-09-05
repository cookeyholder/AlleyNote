<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Contracts\RepositoryInterface;
use DateTimeInterface;

/**
 * 統計資料存取介面
 * 定義統計快照的存取方法，遵循領域語言與業務需求.
 */
interface StatisticsRepositoryInterface extends RepositoryInterface
{
    /**
     * 儲存統計快照.
     */
    public function saveSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot;

    /**
     * 依週期查詢統計快照.
     */
    public function findByPeriod(StatisticsPeriod $period): ?StatisticsSnapshot;

    /**
     * 依日期範圍查詢統計快照列表.
     *
     * @return StatisticsSnapshot[]
     */
    public function findByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?PeriodType $periodType = null,
    ): array;

    /**
     * 取得最新的統計快照.
     */
    public function findLatest(?PeriodType $periodType = null): ?StatisticsSnapshot;

    /**
     * 取得指定週期類型的最近幾筆統計快照.
     *
     * @return StatisticsSnapshot[]
     */
    public function findRecent(PeriodType $periodType, int $limit = 10): array;

    /**
     * 依來源類型查詢統計快照.
     *
     * @return StatisticsSnapshot[]
     */
    public function findBySourceType(
        SourceType $sourceType,
        ?StatisticsPeriod $period = null,
        int $limit = 100,
    ): array;

    /**
     * 刪除過期的統計快照.
     *
     * @param int $daysToKeep 保留天數
     */
    public function deleteExpiredSnapshots(int $daysToKeep = 90): int;

    /**
     * 取得統計快照總數.
     */
    public function getTotalSnapshotCount(?PeriodType $periodType = null): int;

    /**
     * 檢查指定週期是否已有統計快照.
     */
    public function existsForPeriod(StatisticsPeriod $period): bool;

    /**
     * 取得統計快照的時間序列資料（用於圖表顯示）.
     *
     * @return array<array{
     *     date: string,
     *     total_count: int,
     *     unique_count: int,
     *     period_type: string
     * }>
     */
    public function getTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $periodType,
    ): array;

    /**
     * 取得來源統計分布資料.
     *
     * @return array<array{
     *     source_type: string,
     *     total_count: int,
     *     percentage: float,
     *     period_start: string,
     *     period_end: string
     * }>
     */
    public function getSourceDistribution(
        StatisticsPeriod $period,
    ): array;

    /**
     * 批次儲存多個統計快照.
     *
     * @param StatisticsSnapshot[] $snapshots
     * @return StatisticsSnapshot[]
     */
    public function saveBatch(array $snapshots): array;

    /**
     * 更新或建立統計快照（如果已存在則更新，否則建立）.
     */
    public function upsertSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot;

    /**
     * 依 UUID 查詢統計快照.
     */
    public function findByUuid(string $uuid): ?StatisticsSnapshot;

    /**
     * 取得指定日期之前的最後一筆統計快照.
     */
    public function findLastBeforeDate(
        DateTimeInterface $date,
        ?PeriodType $periodType = null,
    ): ?StatisticsSnapshot;

    /**
     * 取得指定日期之後的第一筆統計快照.
     */
    public function findFirstAfterDate(
        DateTimeInterface $date,
        ?PeriodType $periodType = null,
    ): ?StatisticsSnapshot;

    /**
     * 聚合指定週期內的統計資料（用於產生上層週期統計）.
     *
     * @return array{
     *     total_count: int,
     *     unique_count: int,
     *     source_distribution: array,
     *     period_start: string,
     *     period_end: string
     * }
     */
    public function aggregateByPeriod(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $sourcePeriodType,
        PeriodType $targetPeriodType,
    ): array;

    /**
     * 計算成長率統計.
     *
     * @return array{
     *     current_count: int,
     *     previous_count: int,
     *     growth_rate: float,
     *     growth_percentage: float
     * }
     */
    public function calculateGrowthRate(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
    ): array;

    /**
     * 取得熱門時段分析.
     *
     * @return array<array{
     *     hour: int,
     *     count: int,
     *     percentage: float
     * }>
     */
    public function getPopularTimeSlots(
        StatisticsPeriod $period,
    ): array;

    /**
     * 刪除統計快照.
     */
    public function deleteSnapshot(string $uuid): bool;

    /**
     * 軟刪除統計快照（標記為刪除但不實際移除）.
     */
    public function softDeleteSnapshot(string $uuid): bool;

    /**
     * 恢復軟刪除的統計快照.
     */
    public function restoreSnapshot(string $uuid): bool;
}
