<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * 統計應用服務.
 *
 * 應用層服務，負責協調統計相關的領域服務，處理應用層的事務邏輯，
 * 實作快取策略，並提供統一的錯誤處理。
 *
 * 職責：
 * - 協調多個領域服務的互動
 * - 處理應用層的事務邏輯
 * - 實作快取策略以提升效能
 * - 統一的錯誤處理與異常轉換
 * - 資料驗證與業務規則檢查
 */
final class StatisticsApplicationService
{
    private const CACHE_TTL_SHORT = 1800; // 30 分鐘

    private const CACHE_TTL_MEDIUM = 3600; // 1 小時

    private const CACHE_TTL_LONG = 7200; // 2 小時

    private const VALID_SNAPSHOT_TYPES = [
        StatisticsSnapshot::TYPE_OVERVIEW,
        StatisticsSnapshot::TYPE_POSTS,
        StatisticsSnapshot::TYPE_USERS,
        StatisticsSnapshot::TYPE_POPULAR,
    ];

    public function __construct(
        private readonly StatisticsAggregationServiceInterface $aggregationService,
        private readonly StatisticsCacheServiceInterface $cacheService,
    ) {}

    /**
     * 建立綜合統計快照.
     *
     * 建立包含所有統計資料的綜合快照，並處理相關的快取失效。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的統計快照
     * @throws InvalidArgumentException 當參數無效時
     * @throws RuntimeException 當建立過程中發生錯誤時
     */
    public function createOverviewSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        try {
            // 建立統計快照
            $snapshot = $this->aggregationService->createOverviewSnapshot($period, $metadata, $expiresAt);

            // 清除相關快取
            $this->invalidateRelatedCache($snapshot);

            return $snapshot;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to create overview snapshot: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 建立文章統計快照.
     *
     * 專門針對文章統計資料建立快照。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的文章統計快照
     * @throws InvalidArgumentException 當參數無效時
     * @throws RuntimeException 當建立過程中發生錯誤時
     */
    public function createPostsSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        try {
            $snapshot = $this->aggregationService->createPostsSnapshot($period, $metadata, $expiresAt);
            $this->invalidateRelatedCache($snapshot);

            return $snapshot;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to create posts snapshot: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 建立使用者統計快照.
     *
     * 專門針對使用者統計資料建立快照。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的使用者統計快照
     * @throws InvalidArgumentException 當參數無效時
     * @throws RuntimeException 當建立過程中發生錯誤時
     */
    public function createUsersSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        try {
            $snapshot = $this->aggregationService->createUsersSnapshot($period, $metadata, $expiresAt);
            $this->invalidateRelatedCache($snapshot);

            return $snapshot;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to create users snapshot: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 建立熱門內容統計快照.
     *
     * 建立包含熱門文章、活躍使用者等資料的快照。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的熱門內容統計快照
     * @throws InvalidArgumentException 當參數無效時
     * @throws RuntimeException 當建立過程中發生錯誤時
     */
    public function createPopularSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        try {
            $snapshot = $this->aggregationService->createPopularSnapshot($period, $metadata, $expiresAt);
            $this->invalidateRelatedCache($snapshot);

            return $snapshot;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to create popular snapshot: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 批量建立多種類型的統計快照.
     *
     * 一次性建立多個統計快照，確保資料一致性並最佳化效能。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string> $types 要建立的快照類型
     * @param array<string, mixed> $metadata 共用的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return array<string, StatisticsSnapshot> 建立的快照陣列
     * @throws InvalidArgumentException 當參數無效時
     * @throws RuntimeException 當批量建立過程中發生錯誤時
     */
    public function createBatchSnapshots(
        StatisticsPeriod $period,
        array $types,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): array {
        $this->validatePeriod($period);
        $this->validateSnapshotTypes($types);

        try {
            $snapshots = $this->aggregationService->createBatchSnapshots($period, $types, $metadata, $expiresAt);

            // 批量清除快取，使用標籤快取失效更高效
            $this->cacheService->flushByTags(['statistics']);

            return $snapshots;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to create batch snapshots: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 更新現有的統計快照.
     *
     * 重新計算並更新指定的統計快照，並清除相關快取。
     *
     * @param StatisticsSnapshot $snapshot 要更新的快照
     * @return StatisticsSnapshot 更新後的快照
     * @throws RuntimeException 當更新過程中發生錯誤時
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        try {
            $updatedSnapshot = $this->aggregationService->updateSnapshot($snapshot);
            $this->invalidateRelatedCache($updatedSnapshot);

            return $updatedSnapshot;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to update snapshot: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算統計趨勢.
     *
     * 比較兩個週期的統計資料，計算成長率與趨勢指標，並帶快取支援。
     *
     * @param StatisticsPeriod $currentPeriod 當前週期
     * @param StatisticsPeriod $previousPeriod 上一週期
     * @param string $snapshotType 快照類型
     * @param int $cacheTtl 快取存活時間（秒），預設1小時
     * @return array<string, mixed> 趨勢分析資料
     * @throws RuntimeException 當趨勢計算失敗時
     */
    public function calculateTrends(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
        string $snapshotType,
        int $cacheTtl = self::CACHE_TTL_MEDIUM,
    ): array {
        // 生成快取鍵
        $cacheKey = $this->generateTrendsCacheKey($currentPeriod, $previousPeriod, $snapshotType);

        /** @var array<string, mixed> $result */
        $result = $this->cacheService->remember(
            $cacheKey,
            fn(): array => $this->aggregationService->calculateTrends($currentPeriod, $previousPeriod, $snapshotType),
            $cacheTtl,
        );

        return $result;
    }

    /**
     * 清理過期的統計快照.
     *
     * 刪除過期的統計快照並清除相關快取。
     *
     * @param DateTimeInterface|null $beforeDate 指定日期前的快照，null 表示當前時間
     * @return int 清理的快照數量
     * @throws RuntimeException 當清理過程中發生錯誤時
     */
    public function cleanExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        try {
            $deletedCount = $this->aggregationService->cleanExpiredSnapshots($beforeDate);

            // 清理完成後清除所有統計快取
            $this->cacheService->flushByTags(['statistics']);

            return $deletedCount;
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to clean expired snapshots: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 獲取帶快取的統計資料.
     *
     * 透過快取機制獲取統計資料，提升查詢效能。
     *
     * @param string $snapshotType 快照類型
     * @param StatisticsPeriod $period 統計週期
     * @param int $cacheTtl 快取存活時間（秒），預設30分鐘
     * @return array<string, mixed> 統計資料
     */
    public function getCachedStatistics(
        string $snapshotType,
        StatisticsPeriod $period,
        int $cacheTtl = self::CACHE_TTL_SHORT,
    ): array {
        $cacheKey = $this->generateStatisticsCacheKey($snapshotType, $period);

        /** @var array<string, mixed> $result */
        $result = $this->cacheService->remember(
            $cacheKey,
            function (): array {
                // 這裡可以實作從 Repository 獲取統計資料的邏輯
                // 或者呼叫領域服務獲取最新統計資料
                return [];
            },
            $cacheTtl,
        );

        return $result;
    }

    /**
     * 預熱統計快取.
     *
     * 預先載入常用的統計資料到快取中。
     *
     * @param array<string> $snapshotTypes 要預熱的快照類型
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, bool> 預熱結果，true 表示成功
     */
    public function warmCache(array $snapshotTypes, StatisticsPeriod $period): array
    {
        $results = [];

        foreach ($snapshotTypes as $type) {
            try {
                $this->getCachedStatistics($type, $period, self::CACHE_TTL_LONG);
                $results[$type] = true;
            } catch (RuntimeException $e) {
                $results[$type] = false;
            }
        }

        return $results;
    }

    /**
     * 清除指定標籤的快取.
     *
     * @param array<string> $tags 要清除的快取標籤
     */
    public function invalidateCache(array $tags): void
    {
        $this->cacheService->flushByTags($tags);
    }

    /**
     * 驗證統計週期.
     *
     * @throws InvalidArgumentException 當週期無效時
     */
    private function validatePeriod(StatisticsPeriod $period): void
    {
        // 檢查是否為未來日期
        $now = new DateTimeImmutable();
        if ($period->endTime > $now) {
            throw new InvalidArgumentException('Cannot create statistics for future periods');
        }

        // 檢查週期長度是否合理
        if ($period->getDurationInSeconds() <= 0) {
            throw new InvalidArgumentException('Invalid period duration');
        }

        // 檢查週期是否過長（例如超過一年）
        $maxDuration = 365 * 24 * 3600; // 一年
        if ($period->getDurationInSeconds() > $maxDuration) {
            throw new InvalidArgumentException('Period duration too long (max 1 year)');
        }
    }

    /**
     * 驗證快照類型陣列.
     *
     * @param array<string> $types 快照類型陣列
     * @throws InvalidArgumentException 當類型陣列無效時
     */
    private function validateSnapshotTypes(array $types): void
    {
        if (empty($types)) {
            throw new InvalidArgumentException('Snapshot types array cannot be empty');
        }

        $invalidTypes = array_diff($types, self::VALID_SNAPSHOT_TYPES);
        if (!empty($invalidTypes)) {
            throw new InvalidArgumentException('Unsupported snapshot types: ' . implode(', ', $invalidTypes));
        }

        // 檢查是否有重複類型
        if (count($types) !== count(array_unique($types))) {
            throw new InvalidArgumentException('Duplicate snapshot types found');
        }

        // 限制批量操作的數量
        if (count($types) > 10) {
            throw new InvalidArgumentException('Too many snapshot types (max 10)');
        }
    }

    /**
     * 清除與快照相關的快取.
     */
    private function invalidateRelatedCache(StatisticsSnapshot $snapshot): void
    {
        $cacheKeys = [
            $this->generateStatisticsCacheKey($snapshot->getSnapshotType(), $snapshot->getPeriod()),
            "statistics.{$snapshot->getSnapshotType()}.*",
            "trends.{$snapshot->getSnapshotType()}.*",
        ];

        $this->cacheService->forget($cacheKeys);
    }

    /**
     * 生成統計資料快取鍵.
     */
    private function generateStatisticsCacheKey(string $snapshotType, StatisticsPeriod $period): string
    {
        return sprintf(
            'statistics.%s.%s.%s',
            $snapshotType,
            $period->type->value,
            $period->startTime->format('Y-m-d'),
        );
    }

    /**
     * 生成趨勢分析快取鍵.
     */
    private function generateTrendsCacheKey(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
        string $snapshotType,
    ): string {
        return sprintf(
            'trends.%s.%s.%s-%s',
            $snapshotType,
            $currentPeriod->type->value,
            $currentPeriod->startTime->format('Y-m-d'),
            $previousPeriod->startTime->format('Y-m-d'),
        );
    }
}
