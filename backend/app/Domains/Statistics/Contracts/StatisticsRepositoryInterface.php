<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeInterface;

/**
 * 統計快照資料存取介面
 *
 * 定義統計快照的基本 CRUD 操作和查詢方法。
 * 遵循領域語言，提供領域層所需的資料存取抽象。
 */
interface StatisticsRepositoryInterface
{
    /**
     * 儲存統計快照
     *
     * @param StatisticsSnapshot $snapshot 要儲存的統計快照
     * @throws \RuntimeException 當儲存失敗時
     */
    public function saveSnapshot(StatisticsSnapshot $snapshot): void;

    /**
     * 根據唯一識別符查找統計快照
     *
     * @param Uuid $id 統計快照的唯一識別符
     * @return StatisticsSnapshot|null 找到的統計快照，未找到時回傳 null
     */
    public function findById(Uuid $id): ?StatisticsSnapshot;

    /**
     * 根據統計週期查找統計快照
     *
     * @param StatisticsPeriod $period 統計週期
     * @return StatisticsSnapshot|null 找到的統計快照，未找到時回傳 null
     */
    public function findByPeriod(StatisticsPeriod $period): ?StatisticsSnapshot;

    /**
     * 查找指定時間範圍內的所有統計快照
     *
     * @param DateTimeInterface $startDate 開始時間
     * @param DateTimeInterface $endDate 結束時間
     * @param int $limit 限制回傳數量，預設為 100
     * @return array<StatisticsSnapshot> 統計快照陣列
     */
    public function findByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 100
    ): array;

    /**
     * 查找最新的統計快照
     *
     * @param int $limit 限制回傳數量，預設為 10
     * @return array<StatisticsSnapshot> 最新的統計快照陣列
     */
    public function findLatest(int $limit = 10): array;

    /**
     * 查找過期的統計快照
     *
     * @param DateTimeInterface $cutoffDate 截止日期，早於此日期的快照被視為過期
     * @return array<StatisticsSnapshot> 過期的統計快照陣列
     */
    public function findExpiredSnapshots(DateTimeInterface $cutoffDate): array;

    /**
     * 取得最舊的統計快照
     *
     * @return StatisticsSnapshot|null 最舊的統計快照，無資料時回傳 null
     */
    public function getOldestSnapshot(): ?StatisticsSnapshot;

    /**
     * 取得最新的統計快照
     *
     * @return StatisticsSnapshot|null 最新的統計快照，無資料時回傳 null
     */
    public function getLatestSnapshot(): ?StatisticsSnapshot;

    /**
     * 計算統計快照總數
     *
     * @return int 統計快照總數量
     */
    public function getTotalSnapshotCount(): int;

    /**
     * 刪除統計快照
     *
     * @param Uuid $id 要刪除的統計快照唯一識別符
     * @throws \RuntimeException 當刪除失敗時
     */
    public function deleteSnapshot(Uuid $id): void;

    /**
     * 批量刪除過期的統計快照
     *
     * @param DateTimeInterface $cutoffDate 截止日期
     * @return int 刪除的快照數量
     * @throws \RuntimeException 當批量刪除失敗時
     */
    public function deleteExpiredSnapshots(DateTimeInterface $cutoffDate): int;

    /**
     * 檢查指定週期的統計快照是否存在
     *
     * @param StatisticsPeriod $period 統計週期
     * @return bool 存在時回傳 true，否則回傳 false
     */
    public function existsByPeriod(StatisticsPeriod $period): bool;

    /**
     * 更新統計快照
     *
     * @param StatisticsSnapshot $snapshot 要更新的統計快照
     * @throws \RuntimeException 當更新失敗時
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): void;

    /**
     * 取得統計快照的建立時間範圍
     *
     * @return array{min: DateTimeInterface|null, max: DateTimeInterface|null} 最早和最晚的建立時間
     */
    public function getSnapshotDateRange(): array;
}
