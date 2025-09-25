<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 統計聚合服務 - 領域服務.
 *
 * 負責處理統計資料的計算、聚合與快照管理。
 * 此服務封裝複雜的統計業務邏輯，確保統計資料的一致性與準確性。
 * 遵循 DDD 領域服務原則，專注於跨多個聚合根的業務邏輯協調。
 */
class StatisticsAggregationService implements StatisticsAggregationServiceInterface
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    /**
     * 建立綜合統計快照.
     *
     * 聚合文章與使用者統計資料，產生綜合性的統計快照。
     * 此方法協調多個聚合根的資料，體現領域服務的核心職責。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的統計快照
     * @throws RuntimeException 當統計資料不完整或計算失敗時
     */
    public function createOverviewSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        // 檢查是否已存在相同週期的快照
        if ($this->statisticsRepository->exists(StatisticsSnapshot::TYPE_OVERVIEW, $period)) {
            throw new InvalidArgumentException('Overview snapshot already exists for this period');
        }

        // 聚合各種統計資料
        $overviewData = $this->aggregateOverviewData($period);

        // 建立統計快照
        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            $overviewData,
            array_merge($this->generateBaseMetadata(), $metadata),
            $expiresAt,
        );

        // 儲存快照
        $savedSnapshot = $this->statisticsRepository->save($snapshot);

        // 發布統計快照已建立事件
        $this->dispatchSnapshotCreatedEvent($savedSnapshot);

        return $savedSnapshot;
    }

    /**
     * 建立文章統計快照.
     *
     * 專門針對文章相關的統計資料進行聚合與快照建立。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的文章統計快照
     * @throws RuntimeException 當文章統計資料計算失敗時
     */
    public function createPostsSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        // 檢查資料可用性
        if (!$this->postStatisticsRepository->hasDataForPeriod($period)) {
            throw new RuntimeException('No post data available for the specified period');
        }

        // 聚合文章統計資料
        $postsData = $this->aggregatePostsData($period);

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_POSTS,
            $period,
            $postsData,
            array_merge($this->generateBaseMetadata(), $metadata),
            $expiresAt,
        );

        $savedSnapshot = $this->statisticsRepository->save($snapshot);

        // 發布統計快照已建立事件
        $this->dispatchSnapshotCreatedEvent($savedSnapshot);

        return $savedSnapshot;
    }

    /**
     * 建立使用者統計快照.
     *
     * 專門針對使用者相關的統計資料進行聚合與快照建立。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $metadata 額外的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return StatisticsSnapshot 建立的使用者統計快照
     * @throws RuntimeException 當使用者統計資料計算失敗時
     */
    public function createUsersSnapshot(
        StatisticsPeriod $period,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        // 檢查資料可用性
        if (!$this->userStatisticsRepository->hasDataForPeriod($period)) {
            throw new RuntimeException('No user data available for the specified period');
        }

        // 聚合使用者統計資料
        $usersData = $this->aggregateUsersData($period);

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_USERS,
            $period,
            $usersData,
            array_merge($this->generateBaseMetadata(), $metadata),
            $expiresAt,
        );

        $savedSnapshot = $this->statisticsRepository->save($snapshot);

        // 發布統計快照已建立事件
        $this->dispatchSnapshotCreatedEvent($savedSnapshot);

        return $savedSnapshot;
    }

    /**
     * 建立熱門內容統計快照.
     *
     * 聚合熱門文章、活躍使用者等熱門內容統計資料。
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
    ): StatisticsSnapshot {
        $this->validatePeriod($period);

        // 聚合熱門內容資料
        $popularData = $this->aggregatePopularData($period);

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_POPULAR,
            $period,
            $popularData,
            array_merge($this->generateBaseMetadata(), $metadata),
            $expiresAt,
        );

        $savedSnapshot = $this->statisticsRepository->save($snapshot);

        // 發布統計快照已建立事件
        $this->dispatchSnapshotCreatedEvent($savedSnapshot);

        return $savedSnapshot;
    }

    /**
     * 批量建立多種類型的統計快照.
     *
     * 一次性建立多個統計快照，確保資料一致性。
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string> $types 要建立的快照類型
     * @param array<string, mixed> $metadata 共用的元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     * @return array<string, StatisticsSnapshot> 建立的快照陣列，以類型為鍵
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

        $snapshots = [];
        $errors = [];

        foreach ($types as $type) {
            try {
                $snapshots[$type] = match ($type) {
                    StatisticsSnapshot::TYPE_OVERVIEW => $this->createOverviewSnapshot($period, $metadata, $expiresAt),
                    StatisticsSnapshot::TYPE_POSTS => $this->createPostsSnapshot($period, $metadata, $expiresAt),
                    StatisticsSnapshot::TYPE_USERS => $this->createUsersSnapshot($period, $metadata, $expiresAt),
                    StatisticsSnapshot::TYPE_POPULAR => $this->createPopularSnapshot($period, $metadata, $expiresAt),
                    default => throw new InvalidArgumentException("Unsupported snapshot type: {$type}"),
                };
            } catch (RuntimeException|InvalidArgumentException $e) {
                $errors[$type] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException('Failed to create some snapshots: ' . json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return $snapshots;
    }

    /**
     * 更新現有的統計快照.
     *
     * 重新計算指定快照的統計資料並更新。
     *
     * @param StatisticsSnapshot $snapshot 要更新的快照
     * @return StatisticsSnapshot 更新後的快照
     * @throws RuntimeException 當更新過程中發生錯誤時
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        $this->validateSnapshot($snapshot);

        // 根據快照類型重新計算統計資料
        $newData = match ($snapshot->getSnapshotType()) {
            StatisticsSnapshot::TYPE_OVERVIEW => $this->aggregateOverviewData($snapshot->getPeriod()),
            StatisticsSnapshot::TYPE_POSTS => $this->aggregatePostsData($snapshot->getPeriod()),
            StatisticsSnapshot::TYPE_USERS => $this->aggregateUsersData($snapshot->getPeriod()),
            StatisticsSnapshot::TYPE_POPULAR => $this->aggregatePopularData($snapshot->getPeriod()),
            default => throw new InvalidArgumentException("Unsupported snapshot type: {$snapshot->getSnapshotType()}"),
        };

        // 更新統計資料
        $snapshot->updateStatistics($newData);

        // 更新元資料
        $snapshot->updateMetadata([
            'last_updated_by' => 'StatisticsAggregationService',
            'data_points' => count($newData),
            'calculation_method' => 'aggregated',
        ]);

        return $this->statisticsRepository->update($snapshot);
    }

    /**
     * 計算統計趨勢.
     *
     * 比較兩個週期的統計資料，計算成長率與趨勢指標。
     *
     * @param StatisticsPeriod $currentPeriod 當前週期
     * @param StatisticsPeriod $previousPeriod 上一週期
     * @param string $snapshotType 快照類型
     * @return array<string, mixed> 趨勢分析資料
     * @throws RuntimeException 當趨勢計算失敗時
     */
    public function calculateTrends(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
        string $snapshotType,
    ): array {
        $this->validatePeriod($currentPeriod);
        $this->validatePeriod($previousPeriod);

        // 獲取兩個週期的快照
        $currentSnapshot = $this->statisticsRepository->findByTypeAndPeriod($snapshotType, $currentPeriod);

        if ($currentSnapshot === null) {
            throw new RuntimeException('Current period snapshot not found');
        }

        $previousSnapshot = $this->statisticsRepository->findByTypeAndPeriod($snapshotType, $previousPeriod);

        if ($previousSnapshot === null) {
            throw new RuntimeException('Previous period snapshot not found');
        }

        return $this->computeTrendMetrics($currentSnapshot, $previousSnapshot);
    }

    /**
     * 清理過期的統計快照.
     *
     * 刪除已過期的統計快照，維護資料庫效能。
     *
     * @param DateTimeInterface|null $beforeDate 指定日期前的快照，null 表示當前時間
     * @return int 清理的快照數量
     */
    public function cleanExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        return $this->statisticsRepository->deleteExpiredSnapshots($beforeDate);
    }

    /**
     * 驗證統計週期的有效性.
     *
     * @throws InvalidArgumentException 當週期無效時
     */
    private function validatePeriod(StatisticsPeriod $period): void
    {
        if ($period->getDurationInSeconds() <= 0) {
            throw new InvalidArgumentException('Invalid period duration');
        }
    }

    /**
     * 驗證統計快照的有效性.
     *
     * @throws InvalidArgumentException 當快照無效時
     */
    private function validateSnapshot(StatisticsSnapshot $snapshot): void
    {
        if ($snapshot->isExpired()) {
            throw new InvalidArgumentException('Cannot update expired snapshot');
        }

        if (!$snapshot->validateDataIntegrity()) {
            throw new InvalidArgumentException('Snapshot data integrity validation failed');
        }
    }

    /**
     * 驗證快照類型陣列的有效性.
     *
     * @param array<string> $types 快照類型陣列
     * @throws InvalidArgumentException 當包含無效類型時
     */
    private function validateSnapshotTypes(array $types): void
    {
        $validTypes = [
            StatisticsSnapshot::TYPE_OVERVIEW,
            StatisticsSnapshot::TYPE_POSTS,
            StatisticsSnapshot::TYPE_USERS,
            StatisticsSnapshot::TYPE_POPULAR,
        ];

        $invalidTypes = array_diff($types, $validTypes);
        if (!empty($invalidTypes)) {
            throw new InvalidArgumentException('Invalid snapshot types: ' . implode(', ', $invalidTypes));
        }
    }

    /**
     * 聚合綜合統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed> 聚合的統計資料
     */
    private function aggregateOverviewData(StatisticsPeriod $period): array
    {
        // 獲取基本統計數據
        $postsCount = $this->postStatisticsRepository->getTotalPostsCount($period);
        $activeUsersCount = $this->userStatisticsRepository->getActiveUsersCount($period);
        $newUsersCount = $this->userStatisticsRepository->getNewUsersCount($period);

        // 獲取文章活動摘要
        $postActivity = $this->postStatisticsRepository->getPostActivitySummary($period);

        // 獲取使用者活動摘要
        $userActivity = $this->userStatisticsRepository->getUserActivitySummary($period);

        return [
            'total_posts' => $postsCount,
            'active_users' => $activeUsersCount,
            'new_users' => $newUsersCount,
            'post_activity' => $postActivity,
            'user_activity' => $userActivity,
            'engagement_metrics' => [
                'posts_per_active_user' => $activeUsersCount > 0 ? round($postsCount / $activeUsersCount, 2) : 0,
                'user_growth_rate' => $this->calculateGrowthRate($newUsersCount, $activeUsersCount),
                'content_velocity' => $this->calculateContentVelocity($period, $postsCount),
            ],
            'period_summary' => [
                'type' => $period->type->value,
                'duration_days' => $period->getDurationInDays(),
                'start' => $period->startTime->format('Y-m-d H:i:s'),
                'end' => $period->endTime->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 聚合文章統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed> 聚合的文章統計資料
     */
    private function aggregatePostsData(StatisticsPeriod $period): array
    {
        return [
            'by_status' => $this->postStatisticsRepository->getPostsCountByStatus($period),
            'by_source' => $this->postStatisticsRepository->getPostsCountBySource($period),
            'views_statistics' => $this->postStatisticsRepository->getPostViewsStatistics($period),
            'top_posts' => $this->postStatisticsRepository->getPopularPosts($period, 10),
            'length_statistics' => $this->postStatisticsRepository->getPostsLengthStatistics($period),
            'time_distribution' => $this->postStatisticsRepository->getPostsPublishTimeDistribution($period),
            'top_authors' => $this->postStatisticsRepository->getPostsCountByUser($period, 5),
            'pinned_stats' => $this->postStatisticsRepository->getPinnedPostsStatistics($period),
        ];
    }

    /**
     * 聚合使用者統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed> 聚合的使用者統計資料
     */
    private function aggregateUsersData(StatisticsPeriod $period): array
    {
        return [
            'active_users' => $this->userStatisticsRepository->getActiveUsersCount($period),
            'by_activity_type' => $this->userStatisticsRepository->getActiveUsersByActivityType($period),
            'login_activity' => $this->userStatisticsRepository->getUserLoginActivity($period),
            'most_active' => $this->userStatisticsRepository->getMostActiveUsers($period, 10),
            'engagement_stats' => $this->userStatisticsRepository->getUserEngagementStatistics($period),
            'registration_sources' => $this->userStatisticsRepository->getUserRegistrationSources($period),
            'geographical_distribution' => $this->userStatisticsRepository->getUserGeographicalDistribution($period),
            'by_role' => $this->userStatisticsRepository->getUsersCountByRole($period),
        ];
    }

    /**
     * 聚合熱門內容資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed> 聚合的熱門內容資料
     */
    private function aggregatePopularData(StatisticsPeriod $period): array
    {
        return [
            'top_posts' => [
                'by_views' => $this->postStatisticsRepository->getPopularPosts($period, 10, 'views'),
                'by_comments' => $this->postStatisticsRepository->getPopularPosts($period, 10, 'comments'),
                'by_likes' => $this->postStatisticsRepository->getPopularPosts($period, 10, 'likes'),
            ],
            'top_users' => [
                'by_posts' => $this->userStatisticsRepository->getMostActiveUsers($period, 10, 'posts'),
                'by_activity' => $this->userStatisticsRepository->getMostActiveUsers($period, 10, 'activity_score'),
                'by_logins' => $this->userStatisticsRepository->getMostActiveUsers($period, 10, 'logins'),
            ],
            'trending_sources' => $this->postStatisticsRepository->getPostsCountBySource($period),
            'peak_activity_times' => $this->userStatisticsRepository->getUserActivityTimeDistribution($period),
        ];
    }

    /**
     * 生成基礎元資料.
     *
     * @return array<string, mixed> 基礎元資料
     */
    private function generateBaseMetadata(): array
    {
        return [
            'generated_by' => 'StatisticsAggregationService',
            'version' => '1.0.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'data_sources' => [
                'posts' => 'PostStatisticsRepository',
                'users' => 'UserStatisticsRepository',
            ],
        ];
    }

    /**
     * 計算成長率.
     *
     * @param int $current 當前數值
     * @param int $previous 上期數值
     * @return float 成長率百分比
     */
    private function calculateGrowthRate(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 2);
    }

    /**
     * 計算內容速度（每日平均發布量）.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $totalPosts 總文章數
     * @return float 每日平均發布量
     */
    private function calculateContentVelocity(StatisticsPeriod $period, int $totalPosts): float
    {
        $days = max(1, $period->getDurationInDays());

        return round($totalPosts / $days, 2);
    }

    /**
     * 計算趨勢指標.
     *
     * @param StatisticsSnapshot $current 當前快照
     * @param StatisticsSnapshot $previous 上期快照
     * @return array<string, mixed> 趨勢指標
     */
    private function computeTrendMetrics(StatisticsSnapshot $current, StatisticsSnapshot $previous): array
    {
        $currentTotal = $current->getTotalCount();
        $previousTotal = $previous->getTotalCount();

        return [
            'current_value' => $currentTotal,
            'previous_value' => $previousTotal,
            'absolute_change' => $currentTotal - $previousTotal,
            'percentage_change' => $this->calculateGrowthRate($currentTotal, $previousTotal),
            'trend_direction' => $this->determineTrendDirection($currentTotal, $previousTotal),
            'comparison_period' => [
                'current' => $current->getPeriod()->format(),
                'previous' => $previous->getPeriod()->format(),
            ],
        ];
    }

    /**
     * 判斷趨勢方向.
     *
     * @param int $current 當前數值
     * @param int $previous 上期數值
     * @return string 趨勢方向 ('up', 'down', 'stable')
     */
    private function determineTrendDirection(int $current, int $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }

        if ($current < $previous) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * 發布統計快照已建立事件.
     */
    private function dispatchSnapshotCreatedEvent(StatisticsSnapshot $snapshot): void
    {
        if ($this->eventDispatcher === null) {
            return;
        }

        try {
            $event = StatisticsSnapshotCreated::forNewSnapshot($snapshot);
            $this->eventDispatcher->dispatch($event);
        } catch (Throwable $e) {
            // 事件分派失敗不應該影響統計功能的主流程
            // 只記錄錯誤但不重新拋出異常
            error_log('Failed to dispatch StatisticsSnapshotCreated event: ' . $e->getMessage());
        }
    }
}
