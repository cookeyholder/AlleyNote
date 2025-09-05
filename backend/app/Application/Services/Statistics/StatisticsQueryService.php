<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\InvalidStatisticsPeriodException;
use App\Domains\Statistics\Exceptions\StatisticsQueryException;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * 統計查詢服務.
 *
 * 專門處理複雜的統計查詢和資料檢索，
 * 負責查詢最佳化、分頁和參數驗證。
 *
 * 主要功能：
 * - 複雜統計查詢
 * - 查詢參數驗證
 * - 分頁支援
 * - 查詢最佳化
 *
 * 遵循 CQRS 模式，專注於查詢操作。
 */
final readonly class StatisticsQueryService
{
    public function __construct(
        private StatisticsRepositoryInterface $statisticsRepository,
        private PostStatisticsRepositoryInterface $postStatisticsRepository,
    ) {}

    /**
     * 查詢統計概覽.
     *
     * 提供系統統計的整體概覽資訊
     */
    public function getStatisticsOverview(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        array $sourceTypes = [],
    ): array {
        try {
            // 參數驗證
            $this->validateDateRange($startDate, $endDate);
            $this->validateSourceTypes($sourceTypes);

            // 設定預設日期範圍（如果未提供）
            $startDate ??= new DateTimeImmutable('-30 days');
            $endDate ??= new DateTimeImmutable();

            // 建立週期物件進行查詢
            $period = StatisticsPeriod::create(
                $startDate,
                $endDate,
                PeriodType::DAILY,  // 使用日統計作為預設類型
            );

            // 查詢基本統計
            $totalPosts = $this->postStatisticsRepository->countPostsByPeriod($period);
            $totalViews = $this->postStatisticsRepository->countViewsByPeriod($period);
            $uniqueViewers = $this->postStatisticsRepository->countUniqueViewersByPeriod($period);

            // 來源分布統計
            $sourceDistribution = $this->postStatisticsRepository->getViewStatisticsBySource($period);

            // 熱門內容
            $popularPosts = $this->postStatisticsRepository->getPopularPosts($period, 10);

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration_days' => $startDate->diff($endDate)->days,
                ],
                'totals' => [
                    'posts' => $totalPosts,
                    'views' => $totalViews,
                    'unique_viewers' => $uniqueViewers,
                    'avg_views_per_post' => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0,
                    'avg_views_per_viewer' => $uniqueViewers > 0 ? round($totalViews / $uniqueViewers, 2) : 0,
                ],
                'source_distribution' => $sourceDistribution,
                'popular_posts' => $popularPosts,
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                    'source_filters' => $sourceTypes,
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "統計概覽查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 取得分頁的統計快照列表.
     *
     * 支援基本的分頁功能
     */
    public function getStatisticsSnapshots(
        int $page = 1,
        int $perPage = 20,
        ?PeriodType $periodType = null,
    ): array {
        try {
            // 參數驗證
            $this->validatePaginationParams($page, $perPage);

            // 計算分頁
            $offset = ($page - 1) * $perPage;

            // 查詢所有快照
            $allSnapshots = $this->statisticsRepository->findByDateRange(
                new DateTimeImmutable('-1 year'),
                new DateTimeImmutable(),
            );

            // 按期間類型過濾
            if ($periodType !== null) {
                $allSnapshots = array_filter($allSnapshots, function (StatisticsSnapshot $snapshot) use ($periodType) {
                    return $snapshot->getPeriod()->type === $periodType;
                });
            }

            // 排序（最新的在前）
            usort($allSnapshots, function (StatisticsSnapshot $a, StatisticsSnapshot $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });

            // 計算總數和分頁
            $totalCount = count($allSnapshots);
            $snapshots = array_slice($allSnapshots, $offset, $perPage);
            $totalPages = (int) ceil($totalCount / $perPage);
            $hasNextPage = $page < $totalPages;
            $hasPreviousPage = $page > 1;

            return [
                'data' => $snapshots,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_items' => $totalCount,
                    'total_pages' => $totalPages,
                    'has_next_page' => $hasNextPage,
                    'has_previous_page' => $hasPreviousPage,
                    'next_page' => $hasNextPage ? $page + 1 : null,
                    'previous_page' => $hasPreviousPage ? $page - 1 : null,
                ],
                'filters' => [
                    'period_type' => $periodType,
                ],
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "統計快照查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 取得特定週期的統計資料.
     *
     * 提供單一週期的詳細統計資訊
     */
    public function getPeriodStatistics(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        try {
            return $this->statisticsRepository->findByPeriod($period);
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "週期統計查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 取得來源統計分析.
     *
     * 分析特定週期內各來源的統計資料
     */
    public function getSourceAnalysis(
        StatisticsPeriod $period,
        ?SourceType $sourceType = null,
    ): array {
        try {
            // 查詢來源統計
            $sourceStats = $this->postStatisticsRepository->getViewStatisticsBySource(
                $period,
                $sourceType,
            );

            // 計算總數
            $totalViews = array_sum(array_column($sourceStats, 'view_count'));
            $totalUniqueViewers = array_sum(array_column($sourceStats, 'unique_viewers'));

            // 添加百分比計算
            $enrichedStats = array_map(function ($stat) use ($totalViews, $totalUniqueViewers) {
                return [
                    'source_type' => $stat['source_type'],
                    'view_count' => $stat['view_count'],
                    'unique_viewers' => $stat['unique_viewers'],
                    'view_percentage' => $totalViews > 0 ? round(($stat['view_count'] / $totalViews) * 100, 2) : 0,
                    'viewer_percentage' => $totalUniqueViewers > 0 ? round(($stat['unique_viewers'] / $totalUniqueViewers) * 100, 2) : 0,
                    'avg_views_per_viewer' => $stat['unique_viewers'] > 0 ? round($stat['view_count'] / $stat['unique_viewers'], 2) : 0,
                ];
            }, $sourceStats);

            return [
                'period' => $period,
                'source_filter' => $sourceType,
                'statistics' => $enrichedStats,
                'totals' => [
                    'total_views' => $totalViews,
                    'total_unique_viewers' => $totalUniqueViewers,
                    'source_count' => count($sourceStats),
                ],
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "來源分析查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 取得熱門內容排行.
     *
     * 提供特定週期內最受歡迎的內容
     */
    public function getPopularContentRanking(
        StatisticsPeriod $period,
        int $limit = 20,
    ): array {
        try {
            // 參數驗證
            $this->validateLimit($limit);

            // 查詢熱門文章 - 使用現有方法
            $popularPosts = $this->postStatisticsRepository->getPopularPosts($period, $limit);

            // 計算排行統計
            $views = array_column($popularPosts, 'view_count');
            $totalViews = array_sum($views);
            $averageViews = count($popularPosts) > 0 ? round($totalViews / count($popularPosts), 2) : 0;
            $medianViews = $this->calculateMedian($views);
            $topViews = !empty($views) ? max($views) : 0;

            return [
                'period' => $period,
                'ranking' => $popularPosts,
                'statistics' => [
                    'total_items' => count($popularPosts),
                    'total_views' => $totalViews,
                    'average_views' => $averageViews,
                    'median_views' => $medianViews,
                    'top_views' => $topViews,
                ],
                'parameters' => [
                    'limit' => $limit,
                ],
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "熱門內容排行查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 取得統計摘要
     *
     * 提供系統統計的整體摘要
     */
    public function getStatisticsSummary(): array
    {
        try {
            // 查詢最新快照
            $latestSnapshot = $this->statisticsRepository->findLatest();

            // 查詢最舊快照 - 使用其他方法實現
            $oldestSnapshot = null;
            $allSnapshots = $this->statisticsRepository->findByDateRange(
                new DateTimeImmutable('-10 years'),
                new DateTimeImmutable(),
            );
            if (!empty($allSnapshots)) {
                // 找最舊的
                usort($allSnapshots, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
                $oldestSnapshot = $allSnapshots[0];
            }

            // 統計快照總數
            $totalSnapshots = $this->statisticsRepository->getTotalSnapshotCount();

            // 按週期類型統計
            $snapshotsByType = [];
            foreach (PeriodType::cases() as $periodType) {
                $count = $this->countSnapshotsByPeriodType($periodType);
                if ($count > 0) {
                    $snapshotsByType[$periodType->value] = $count;
                }
            }

            return [
                'totals' => [
                    'total_snapshots' => $totalSnapshots,
                    'period_types' => count($snapshotsByType),
                ],
                'snapshots_by_type' => $snapshotsByType,
                'date_range' => [
                    'earliest_snapshot' => $oldestSnapshot?->getCreatedAt(),
                    'latest_snapshot' => $latestSnapshot?->getCreatedAt(),
                    'data_span_days' => $oldestSnapshot && $latestSnapshot
                        ? $oldestSnapshot->getCreatedAt()->diff($latestSnapshot->getCreatedAt())->days
                        : 0,
                ],
                'latest_statistics' => $latestSnapshot ? [
                    'period' => $latestSnapshot->getPeriod(),
                    'total_posts' => $latestSnapshot->getTotalPosts()->value,
                    'total_views' => $latestSnapshot->getTotalViews()->value,
                    'source_count' => count($latestSnapshot->getSourceStats()),
                ] : null,
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "統計摘要查詢失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 搜尋統計快照.
     *
     * 簡單的搜尋功能
     */
    public function searchSnapshots(
        string $query,
        int $page = 1,
        int $perPage = 20,
    ): array {
        try {
            // 參數驗證
            $this->validateSearchQuery($query);
            $this->validatePaginationParams($page, $perPage);

            // 查詢所有快照進行搜尋
            $allSnapshots = $this->statisticsRepository->findByDateRange(
                new DateTimeImmutable('-1 year'),
                new DateTimeImmutable(),
            );

            // 簡單搜尋邏輯（根據週期類型過濾）
            $query = strtolower(trim($query));
            $filteredSnapshots = array_filter($allSnapshots, function (StatisticsSnapshot $snapshot) use ($query) {
                $periodType = strtolower($snapshot->getPeriod()->type->value);

                return str_contains($periodType, $query);
            });

            // 排序
            usort($filteredSnapshots, function (StatisticsSnapshot $a, StatisticsSnapshot $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });

            // 分頁
            $offset = ($page - 1) * $perPage;
            $totalCount = count($filteredSnapshots);
            $paginatedResults = array_slice($filteredSnapshots, $offset, $perPage);
            $totalPages = (int) ceil($totalCount / $perPage);

            return [
                'results' => $paginatedResults,
                'search_query' => $query,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_items' => $totalCount,
                    'total_pages' => $totalPages,
                ],
                'query_metadata' => [
                    'generated_at' => new DateTimeImmutable(),
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsQueryException(
                "統計搜尋失敗: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * 計算中位數.
     *
     * 私有輔助方法
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        // 確保都是數字
        $values = array_map(fn($v) => is_numeric($v) ? (float) $v : 0.0, $values);
        sort($values);
        $count = count($values);

        if ($count % 2 === 0) {
            return ((float) $values[$count / 2 - 1] + (float) $values[$count / 2]) / 2.0;
        }

        return (float) $values[intval($count / 2)];
    }

    /**
     * 統計指定週期類型的快照數量.
     *
     * 私有輔助方法
     */
    private function countSnapshotsByPeriodType(PeriodType $periodType): int
    {
        $allSnapshots = $this->statisticsRepository->findByDateRange(
            new DateTimeImmutable('-1 year'),
            new DateTimeImmutable(),
        );

        return count(array_filter($allSnapshots, function (StatisticsSnapshot $snapshot) use ($periodType) {
            return $snapshot->getPeriod()->type === $periodType;
        }));
    }

    // 驗證方法
    private function validateDateRange(?DateTimeInterface $startDate, ?DateTimeInterface $endDate): void
    {
        if ($startDate && $endDate && $startDate > $endDate) {
            throw new InvalidStatisticsPeriodException('開始日期不能晚於結束日期');
        }
    }

    private function validateSourceTypes(array $sourceTypes): void
    {
        foreach ($sourceTypes as $sourceType) {
            if (!is_string($sourceType)) {
                throw new StatisticsQueryException('來源類型必須為字串');
            }
        }
    }

    private function validatePaginationParams(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new StatisticsQueryException('頁碼必須大於 0');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new StatisticsQueryException('每頁筆數必須介於 1-100 之間');
        }
    }

    private function validateLimit(int $limit): void
    {
        if ($limit < 1 || $limit > 1000) {
            throw new StatisticsQueryException('查詢限制必須介於 1-1000 之間');
        }
    }

    private function validateSearchQuery(string $query): void
    {
        if (strlen(trim($query)) < 1) {
            throw new StatisticsQueryException('搜尋關鍵字至少需要 1 個字元');
        }
    }
}
