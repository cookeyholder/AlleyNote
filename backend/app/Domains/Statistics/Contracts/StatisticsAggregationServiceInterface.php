<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;

/**
 * 統計聚合服務介面.
 *
 * 定義統計聚合服務的合約，用於統計資料的計算、聚合與快照管理。
 */
interface StatisticsAggregationServiceInterface
{
    /**
     * 建立綜合統計快照.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的統計快照
     */
    public function createOverviewSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot;

    /**
     * 建立文章統計快照.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的文章統計快照
     */
    public function createPostsSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot;

    /**
     * 建立使用者統計快照.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的使用者統計快照
     */
    public function createUsersSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot;

    /**
     * 建立熱門內容統計快照.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的熱門內容統計快照
     */
    public function createPopularSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot;

    /**
     * 批量建立多種類型的統計快照.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string> $types 要建立的快照類型
     * @param array<string, mixed> $metadata 共用的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return array<string, StatisticsSnapshot> 建立的快照陣列
     */
    public function createBatchSnapshots(
        StatisticsPeriod $period,
        array $types,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): array;

    /**
     * 更新現有的統計快照.
     *
     * @param StatisticsSnapshot $snapshot 要更新的快照
     * @return StatisticsSnapshot 更新後的快照
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot;

    /**
     * 計算統計趨勢.
     *
     * @param StatisticsPeriod $currentPeriod 當前週期
     * @param StatisticsPeriod $previousPeriod 上一週期
     * @param string $snapshotType 快照類型
     * @return array<string, mixed> 趨勢分析資料
     */
    public function calculateTrends(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
        string $snapshotType,
    ): array;

    /**
     * 清理過期的統計快照.
     *
     * @param DateTimeInterface|null $beforeDate 指定日期前的快照，null 表示當前時間
     * @return int 清理的快照數量
     */
    public function cleanExpiredSnapshots(?DateTimeInterface $beforeDate = null): int;
}
