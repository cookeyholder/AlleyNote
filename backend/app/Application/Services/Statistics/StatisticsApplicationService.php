<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\Services\StatisticsValidationService;
use App\Domains\Statistics\Services\SourceAnalysisService;
use App\Domains\Statistics\Services\StatisticsCacheService;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SystemStatisticsRepositoryInterface;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use Throwable;

/**
 * 統計應用服務
 * 統一管理統計相關的業務邏輯，協調各個領域服務.
 */
class StatisticsApplicationService
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly SystemStatisticsRepositoryInterface $systemStatisticsRepository,
        private readonly StatisticsCalculationService $calculationService,
        private readonly StatisticsValidationService $validationService,
        private readonly SourceAnalysisService $sourceAnalysisService,
        private readonly StatisticsCacheService $cacheService,
    ) {}

    /**
     * 產生指定期間的統計快照.
     */
    public function generatePeriodSnapshot(StatisticsPeriod $period): StatisticsSnapshot
    {
        try {
            // 驗證期間
            $this->validationService->validatePeriod($period);

            // 檢查是否已存在快照
            $existingSnapshot = $this->statisticsRepository->findByPeriod($period);
            if ($existingSnapshot && !$this->isSnapshotExpired($existingSnapshot)) {
                return $existingSnapshot;
            }

            // 計算統計資料
            $totals = $this->calculationService->calculatePeriodTotals($period);
            $sourceStats = $this->sourceAnalysisService->analyzeSourceDistribution($period);
            $additionalMetrics = $this->calculationService->calculateAdditionalMetrics($period);

            // 建立快照
            $snapshot = StatisticsSnapshot::create(
                Uuid::generate(),
                $period,
                $totals['total_posts'],
                $totals['total_views'],
                $sourceStats,
                $additionalMetrics,
            );

            // 儲存快照
            $this->statisticsRepository->saveSnapshot($snapshot);

            // 快取快照
            $this->cacheService->cacheSnapshot($period, $snapshot);

            return $snapshot;
        } catch (Throwable $e) {
            throw new StatisticsCalculationException(
                "產生統計快照時發生錯誤: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * 取得指定期間的統計資料.
     */
    public function getPeriodStatistics(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        // 驗證期間
        $this->validationService->validatePeriod($period);

        // 優先從快取取得
        $cachedSnapshot = $this->cacheService->getCachedSnapshot($period);
        if ($cachedSnapshot) {
            return $cachedSnapshot;
        }

        // 從資料庫查詢
        $snapshot = $this->statisticsRepository->findByPeriod($period);
        if ($snapshot) {
            // 更新快取
            $this->cacheService->cacheSnapshot($period, $snapshot);
        }

        return $snapshot;
    }

    /**
     * 取得文章分析資料.
     */
    public function getPostAnalytics(StatisticsPeriod $period): array
    {
        $this->validationService->validatePeriod($period);

        // 取得文章統計
        $popularPosts = $this->postStatisticsRepository->getPopularPosts($period, 10);
        $viewTrends = $this->postStatisticsRepository->getViewingTimeTrends($period);
        $sourceDistribution = $this->sourceAnalysisService->analyzeSourceDistribution($period);

        // 計算成長率
        $previousPeriod = $period->getPrevious();
        $growthData = $this->calculationService->calculateGrowthRate($period, $previousPeriod);

        return [
            'summary' => [
                'total_posts' => count($popularPosts),
                'total_views' => array_sum(array_column($popularPosts, 'view_count')),
                'avg_views_per_post' => count($popularPosts) > 0
                    ? array_sum(array_column($popularPosts, 'view_count')) / count($popularPosts)
                    : 0,
            ],
            'popular_posts' => $popularPosts,
            'view_trends' => $viewTrends,
            'source_distribution' => $sourceDistribution,
            'growth_data' => $growthData,
        ];
    }

    /**
     * 批量產生多個期間的統計快照.
     */
    public function batchGenerateSnapshots(array $periods): array
    {
        $this->validationService->validateBatchParameters($periods);

        $results = [
            'total' => count($periods),
            'success_count' => 0,
            'error_count' => 0,
            'successful' => [],
            'failed' => [],
        ];

        foreach ($periods as $period) {
            try {
                $snapshot = $this->generatePeriodSnapshot($period);
                $results['successful'][] = [
                    'period' => $period,
                    'snapshot_id' => $snapshot->getId()->toString(),
                ];
                $results['success_count']++;
            } catch (Throwable $e) {
                $results['failed'][] = [
                    'period' => $period,
                    'error' => $e->getMessage(),
                ];
                $results['error_count']++;
            }
        }

        return $results;
    }

    /**
     * 取得統計摘要.
     */
    public function getStatisticsSummary(): array
    {
        $currentWeek = StatisticsPeriod::thisWeek();
        $previousWeek = StatisticsPeriod::lastWeek();

        // 取得當前週期資料
        $currentSnapshot = $this->getPeriodStatistics($currentWeek);
        $previousSnapshot = $this->getPeriodStatistics($previousWeek);

        // 計算成長率
        $postsGrowth = $this->calculationService->calculateGrowthRate($currentWeek, $previousWeek);
        $viewsGrowth = $this->calculationService->calculateGrowthRate($currentWeek, $previousWeek);

        // 取得系統統計
        $systemStats = $this->systemStatisticsRepository->getSystemPerformanceStats($currentWeek);

        return [
            'current_week' => [
                'posts' => $currentSnapshot?->getTotalPosts()->value ?? 0,
                'views' => $currentSnapshot?->getTotalViews()->value ?? 0,
                'sources' => count($currentSnapshot?->getSourceStats() ?? []),
            ],
            'previous_week' => [
                'posts' => $previousSnapshot?->getTotalPosts()->value ?? 0,
                'views' => $previousSnapshot?->getTotalViews()->value ?? 0,
                'sources' => count($previousSnapshot?->getSourceStats() ?? []),
            ],
            'growth' => [
                'posts_growth_rate' => $postsGrowth['growth_rate'],
                'views_growth_rate' => $viewsGrowth['growth_rate'],
            ],
            'system_stats' => [
                'total_snapshots' => $this->statisticsRepository->getTotalSnapshotCount(),
            ],
        ];
    }

    /**
     * 清理過期的統計快照.
     */
    public function cleanupExpiredSnapshots(int $retentionDays = 90): array
    {
        $this->validationService->validateCleanupParameters($retentionDays);

        $cutoffDate = new DateTimeImmutable("-{$retentionDays} days");
        $deletedCount = $this->statisticsRepository->deleteExpiredSnapshots($retentionDays);

        // 清理快取中的過期項目
        $this->cacheService->cleanupExpiredCache();

        $remainingSnapshots = $this->statisticsRepository->getTotalSnapshotCount();

        return [
            'deleted_count' => $deletedCount,
            'retention_days' => $retentionDays,
            'remaining_snapshots' => $remainingSnapshots,
            'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 重建指定期間的統計快照.
     */
    public function rebuildSnapshot(StatisticsPeriod $period): StatisticsSnapshot
    {
        $this->validationService->validatePeriod($period);

        // 刪除現有快照
        $existingSnapshot = $this->statisticsRepository->findByPeriod($period);
        if ($existingSnapshot) {
            $this->statisticsRepository->deleteSnapshot($existingSnapshot->getId()->toString());
        }

        // 清除快取
        $this->cacheService->invalidateCache($period);

        // 重新產生快照
        return $this->generatePeriodSnapshot($period);
    }

    /**
     * 更新統計快照.
     */
    public function updateSnapshot(
        Uuid $snapshotId,
        int $totalPosts,
        int $totalViews,
        array $sourceStats,
    ): StatisticsSnapshot {
        $snapshot = $this->statisticsRepository->findByUuid($snapshotId->toString());
        if (!$snapshot) {
            throw new StatisticsCalculationException('找不到指定的統計快照');
        }

        // 驗證更新資料
        $newTotalPosts = $snapshot->getTotalPosts()->create($totalPosts);
        $newTotalViews = $snapshot->getTotalViews()->create($totalViews);
        $this->validationService->validateSnapshotUpdate(
            $newTotalPosts,
            $newTotalViews,
            $sourceStats,
        );

        // 儲存更新（簡化處理，重新儲存新快照）
        $this->statisticsRepository->saveSnapshot($snapshot);

        // 更新快取
        $this->cacheService->cacheSnapshot($snapshot->getPeriod(), $snapshot);

        return $snapshot;
    }

    /**
     * 檢查快照是否過期.
     */
    private function isSnapshotExpired(StatisticsSnapshot $snapshot): bool
    {
        $expireThreshold = $snapshot->getPeriod()->type->getDefaultCacheTtl();
        $expireTime = $snapshot->getCreatedAt()->modify("+{$expireThreshold} seconds");

        return $expireTime < new DateTimeImmutable();
    }
}
