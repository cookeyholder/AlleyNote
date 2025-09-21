<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 文章統計查詢 Repository 介面.
 *
 * 專門處理文章相關的統計查詢，提供豐富的文章維度統計資料。
 * 此介面遵循 ISP（Interface Segregation Principle），專注於文章統計查詢。
 */
interface PostStatisticsRepositoryInterface
{
    /**
     * 取得文章總數統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param string|null $status 文章狀態篩選（published, draft, archived），null 表示所有狀態
     * @return int 文章總數
     */
    public function getTotalPostsCount(StatisticsPeriod $period, ?string $status = null): int;

    /**
     * 根據狀態分組統計文章數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, int> 狀態為鍵，數量為值的陣列
     */
    public function getPostsCountByStatus(StatisticsPeriod $period): array;

    /**
     * 根據來源類型分組統計文章數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, int> 來源類型為鍵，數量為值的陣列
     */
    public function getPostsCountBySource(StatisticsPeriod $period): array;

    /**
     * 根據指定來源類型統計文章數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param SourceType $sourceType 來源類型
     * @param string|null $status 文章狀態篩選
     * @return int 指定來源的文章數量
     */
    public function getPostsCountBySourceType(
        StatisticsPeriod $period,
        SourceType $sourceType,
        ?string $status = null,
    ): int;

    /**
     * 取得文章瀏覽量統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_views: int, unique_views: int, avg_views_per_post: float}
     */
    public function getPostViewsStatistics(StatisticsPeriod $period): array;

    /**
     * 取得最熱門文章清單.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 取得數量
     * @param string $metric 排序指標（views, comments, likes）
     * @return array<int, array{post_id: int, title: string, metric_value: int}> 熱門文章陣列
     */
    public function getPopularPosts(StatisticsPeriod $period, int $limit = 10, string $metric = 'views'): array;

    /**
     * 根據使用者分組統計文章數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 取得前 N 名使用者
     * @return array<int, array{user_id: int, posts_count: int, total_views: int}> 使用者文章統計
     */
    public function getPostsCountByUser(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 取得文章發布時間分布統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param string $groupBy 分組方式（hour, day, week, month）
     * @return array<string, int> 時間段為鍵，文章數為值的陣列
     */
    public function getPostsPublishTimeDistribution(StatisticsPeriod $period, string $groupBy = 'day'): array;

    /**
     * 取得文章成長趨勢.
     *
     * @param StatisticsPeriod $currentPeriod 當前週期
     * @param StatisticsPeriod $previousPeriod 上一週期
     * @return array{current: int, previous: int, growth_rate: float, growth_count: int}
     */
    public function getPostsGrowthTrend(StatisticsPeriod $currentPeriod, StatisticsPeriod $previousPeriod): array;

    /**
     * 取得文章長度統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{avg_length: float, min_length: int, max_length: int, total_chars: int}
     */
    public function getPostsLengthStatistics(StatisticsPeriod $period): array;

    /**
     * 根據字數範圍分組統計文章.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, array{min: int, max: int}> $lengthRanges 字數範圍定義
     * @return array<string, int> 範圍名稱為鍵，文章數為值的陣列
     */
    public function getPostsCountByLengthRange(StatisticsPeriod $period, array $lengthRanges): array;

    /**
     * 取得置頂文章統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{pinned_count: int, unpinned_count: int, pinned_views: int}
     */
    public function getPinnedPostsStatistics(StatisticsPeriod $period): array;

    /**
     * 檢查指定週期是否有文章統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return bool 是否有資料
     */
    public function hasDataForPeriod(StatisticsPeriod $period): bool;

    /**
     * 取得指定週期的文章活動摘要
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{
     *   total_posts: int,
     *   published_posts: int,
     *   draft_posts: int,
     *   total_views: int,
     *   active_authors: int,
     *   popular_sources: array<string, int>
     * }
     */
    public function getPostActivitySummary(StatisticsPeriod $period): array;
}
