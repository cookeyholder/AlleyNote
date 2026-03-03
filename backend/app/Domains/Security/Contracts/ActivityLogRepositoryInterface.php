<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityType;
use DateTimeInterface;

/**
 * 活動記錄存儲庫介面.
 */
interface ActivityLogRepositoryInterface
{
    /**
     * 建立活動記錄.
     */
    public function create(CreateActivityLogDTO $dto): ?array;

    /**
     * 批次建立多個活動記錄.
     */
    public function createBatch(array $dtos): int;

    /**
     * 根據 ID 查詢活動記錄.
     */
    public function findById(int $id): ?array;

    /**
     * 根據 UUID 查詢活動記錄.
     */
    public function findByUuid(string $uuid): ?array;

    /**
     * 取得所有活動記錄.
     */
    public function findAll(int $limit = 20, int $offset = 0): array;

    /**
     * 查詢使用者的活動記錄.
     */
    public function findByUser(
        int $userId,
        int $limit = 50,
        int $offset = 0,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null,
    ): array;

    /**
     * 查詢指定時間範圍的活動記錄.
     */
    public function findByTimeRange(
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
        int $limit = 100,
        int $offset = 0,
        ?ActivityCategory $category = null,
    ): array;

    /**
     * 查詢使用者在指定時間範圍的活動記錄.
     */
    public function findByUserAndTimeRange(
        int $userId,
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
        int $limit = 1000,
        int $offset = 0,
    ): array;

    /**
     * 查詢指定 IP 在指定時間範圍的活動記錄.
     */
    public function findByIpAddressAndTimeRange(
        string $ipAddress,
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
        int $limit = 1000,
        int $offset = 0,
    ): array;

    /**
     * 查詢安全相關的活動記錄.
     */
    public function findSecurityEvents(
        int $limit = 100,
        int $offset = 0,
        ?string $ipAddress = null,
    ): array;

    /**
     * 查詢失敗的活動記錄.
     */
    public function findFailedActivities(
        int $limit = 100,
        int $offset = 0,
        ?int $userId = null,
        ?ActivityType $actionType = null,
    ): array;

    /**
     * 統計活動記錄數量.
     */
    public function countByCategory(ActivityCategory $category): int;

    /**
     * 統計使用者在指定時間內的活動數量.
     */
    public function countUserActivities(
        int $userId,
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
    ): int;

    /**
     * 取得活動統計資料（依類型分組）.
     */
    public function getActivityStatistics(
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
    ): array;

    /**
     * 取得熱門活動類型.
     */
    public function getPopularActivityTypes(int $limit = 10): array;

    /**
     * 取得可疑 IP 清單（基於失敗嘗試次數）.
     */
    public function getSuspiciousIPs(int $minFailedAttempts = 5): array;

    /**
     * Find activity logs by user ID within time window.
     *
     * @param int $userId User ID to filter by
     * @param DateTimeInterface|null $timeWindow Time window to filter by (null means no time filter)
     */
    public function findByUserIdAndTimeWindow(int $userId, ?DateTimeInterface $timeWindow = null): array;

    /**
     * 刪除舊的活動記錄.
     */
    public function deleteOldRecords(DateTimeInterface $before): int;

    /**
     * 根據條件刪除記錄.
     */
    public function deleteByConditions(array $conditions): int;

    /**
     * 搜尋活動記錄.
     */
    public function search(
        ?string $searchTerm = null,
        ?int $userId = null,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null,
        ?DateTimeInterface $startTime = null,
        ?DateTimeInterface $endTime = null,
        int $limit = 50,
        int $offset = 0,
        string $sortBy = 'occurred_at',
        string $sortOrder = 'DESC',
    ): array;

    /**
     * 取得搜尋結果總數.
     */
    public function getSearchCount(
        ?string $searchTerm = null,
        ?int $userId = null,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null,
        ?DateTimeInterface $startTime = null,
        ?DateTimeInterface $endTime = null,
    ): int;

    /**
     * 取得登入失敗統計資料.
     *
     * @param DateTimeInterface $startTime 開始時間
     * @param DateTimeInterface $endTime 結束時間
     * @param int $limit 限制筆數
     * @return array{total: int, accounts: array<array{username: string, email: string|null, count: int, latest_attempt: string|null}>, trend: array<array{date: string, count: int}>}
     */
    public function getLoginFailureStatistics(
        DateTimeInterface $startTime,
        DateTimeInterface $endTime,
        int $limit = 10,
    ): array;
}
