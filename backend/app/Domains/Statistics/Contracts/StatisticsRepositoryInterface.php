<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;
use RuntimeException;

/**
 * 統計快照 Repository 介面.
 *
 * 定義統計快照的基本存取操作，遵循 DDD Repository 模式。
 * 此介面專注於統計快照實體的持久化與檢索，不包含業務邏輯。
 */
interface StatisticsRepositoryInterface
{
    /**
     * 根據 ID 查找統計快照.
     *
     * @param int $id 快照 ID
     */
    public function findById(int $id): ?StatisticsSnapshot;

    /**
     * 根據 UUID 查找統計快照.
     *
     * @param string $uuid 快照 UUID
     */
    public function findByUuid(string $uuid): ?StatisticsSnapshot;

    /**
     * 根據類型和週期查找統計快照.
     *
     * @param string $snapshotType 快照類型
     * @param StatisticsPeriod $period 統計週期
     */
    public function findByTypeAndPeriod(string $snapshotType, StatisticsPeriod $period): ?StatisticsSnapshot;

    /**
     * 根據類型查找最新的統計快照.
     *
     * @param string $snapshotType 快照類型
     */
    public function findLatestByType(string $snapshotType): ?StatisticsSnapshot;

    /**
     * 查找指定類型在日期範圍內的所有快照.
     *
     * @param string $snapshotType 快照類型
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @return StatisticsSnapshot[] 快照陣列
     */
    public function findByTypeAndDateRange(
        string $snapshotType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array;

    /**
     * 查找已過期的統計快照.
     *
     * @param DateTimeInterface|null $beforeDate 指定日期前的過期快照，null 表示當前時間
     * @return StatisticsSnapshot[] 過期快照陣列
     */
    public function findExpiredSnapshots(?DateTimeInterface $beforeDate = null): array;

    /**
     * 儲存統計快照.
     *
     * @param StatisticsSnapshot $snapshot 要儲存的統計快照
     * @return StatisticsSnapshot 儲存後的統計快照
     * @throws RuntimeException 當儲存失敗時
     */
    public function save(StatisticsSnapshot $snapshot): StatisticsSnapshot;

    /**
     * 更新統計快照.
     *
     * @param StatisticsSnapshot $snapshot 要更新的統計快照
     * @return StatisticsSnapshot 更新後的統計快照
     * @throws RuntimeException 當更新失敗時
     */
    public function update(StatisticsSnapshot $snapshot): StatisticsSnapshot;

    /**
     * 刪除統計快照.
     *
     * @param StatisticsSnapshot $snapshot 要刪除的統計快照
     * @return bool 刪除是否成功
     */
    public function delete(StatisticsSnapshot $snapshot): bool;

    /**
     * 根據 ID 刪除統計快照.
     *
     * @param int $id 要刪除的快照 ID
     * @return bool 刪除是否成功
     */
    public function deleteById(int $id): bool;

    /**
     * 批量刪除過期的統計快照.
     *
     * @param DateTimeInterface|null $beforeDate 指定日期前的過期快照，null 表示當前時間
     * @return int 刪除的快照數量
     */
    public function deleteExpiredSnapshots(?DateTimeInterface $beforeDate = null): int;

    /**
     * 檢查指定類型和週期的快照是否存在.
     *
     * @param string $snapshotType 快照類型
     * @param StatisticsPeriod $period 統計週期
     * @return bool 是否存在
     */
    public function exists(string $snapshotType, StatisticsPeriod $period): bool;

    /**
     * 計算指定類型的快照總數.
     *
     * @param string|null $snapshotType 快照類型，null 表示所有類型
     * @return int 快照總數
     */
    public function count(?string $snapshotType = null): int;

    /**
     * 查找指定類型的快照，支援分頁
     *
     * @param string $snapshotType 快照類型
     * @param int $page 頁碼（從 1 開始）
     * @param int $limit 每頁數量
     * @param string $orderBy 排序欄位
     * @param string $direction 排序方向（asc|desc）
     * @return StatisticsSnapshot[] 快照陣列
     */
    public function findByTypeWithPagination(
        string $snapshotType,
        int $page = 1,
        int $limit = 20,
        string $orderBy = 'created_at',
        string $direction = 'desc',
    ): array;
}
