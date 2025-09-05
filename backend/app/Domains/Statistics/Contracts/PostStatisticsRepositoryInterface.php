<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;

/**
 * 文章統計資料存取介面.
 *
 * 定義文章相關統計資料的查詢方法。
 * 專注於文章數量、觀看次數、來源分布等統計資料的存取。
 */
interface PostStatisticsRepositoryInterface
{
    /**
     * 計算指定週期內的文章總數.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return int 文章總數
     */
    public function countPostsByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的總觀看次數.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return int 總觀看次數
     */
    public function countViewsByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的不重複觀看者數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return int 不重複觀看者數量
     */
    public function countUniqueViewersByPeriod(StatisticsPeriod $period): int;

    /**
     * 取得指定週期內最受歡迎的文章.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 限制回傳數量，預設為 10
     * @return array<array{id: int, title: string, views: int, created_at: string}> 熱門文章資料
     */
    public function getPopularPostsByPeriod(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 取得指定週期內各來源的文章統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{source_type: string, post_count: int, view_count: int, percentage: float}> 來源統計資料
     */
    public function getSourceDistributionByPeriod(StatisticsPeriod $period): array;

    /**
     * 計算指定來源類型在指定週期內的文章數量.
     *
     * @param SourceType $sourceType 來源類型
     * @param StatisticsPeriod $period 統計週期
     * @return int 文章數量
     */
    public function countPostsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int;

    /**
     * 計算指定來源類型在指定週期內的觀看次數.
     *
     * @param SourceType $sourceType 來源類型
     * @param StatisticsPeriod $period 統計週期
     * @return int 觀看次數
     */
    public function countViewsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int;

    /**
     * 取得文章發布趨勢資料（按日期分組）.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, post_count: int, view_count: int}> 趨勢資料
     */
    public function getPostTrendsByPeriod(StatisticsPeriod $period): array;

    /**
     * 取得觀看次數趨勢資料（按日期分組）.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, view_count: int, unique_views: int}> 觀看趨勢資料
     */
    public function getViewTrendsByPeriod(StatisticsPeriod $period): array;

    /**
     * 計算指定週期內的平均每篇文章觀看次數.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return float 平均觀看次數
     */
    public function getAverageViewsPerPostByPeriod(StatisticsPeriod $period): float;

    /**
     * 取得指定週期內觀看次數最高的文章.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{id: int, title: string, views: int, created_at: string}|null 最高觀看文章資料
     */
    public function getMostViewedPostByPeriod(StatisticsPeriod $period): ?array;

    /**
     * 取得指定週期內新發布的文章統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_new_posts: int, total_views: int, avg_views_per_post: float} 新文章統計
     */
    public function getNewPostsStatsByPeriod(StatisticsPeriod $period): array;

    /**
     * 取得文章觀看次數分布.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{range: string, count: int, percentage: float}> 觀看次數分布
     */
    public function getViewsDistributionByPeriod(StatisticsPeriod $period): array;

    /**
     * 計算文章總數（截至指定日期）.
     *
     * @param DateTimeInterface $date 截止日期
     * @return int 文章總數
     */
    public function getTotalPostsAsOfDate(DateTimeInterface $date): int;

    /**
     * 計算總觀看次數（截至指定日期）.
     *
     * @param DateTimeInterface $date 截止日期
     * @return int 總觀看次數
     */
    public function getTotalViewsAsOfDate(DateTimeInterface $date): int;

    /**
     * 取得文章活動熱圖資料（小時級別）.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, hour: int, activity_count: int}> 活動熱圖資料
     */
    public function getPostActivityHeatmapByPeriod(StatisticsPeriod $period): array;

    /**
     * 取得最活躍的文章作者統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 限制回傳數量，預設為 10
     * @return array<array{user_id: int, username: string, post_count: int, total_views: int}> 活躍作者統計
     */
    public function getMostActiveAuthorsByPeriod(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 計算文章互動率.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return float 平均互動率（百分比）
     */
    public function getEngagementRateByPeriod(StatisticsPeriod $period): float;

    /**
     * 取得文章標籤使用統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 限制回傳數量，預設為 20
     * @return array<array{tag: string, usage_count: int, post_count: int}> 標籤統計（如果系統支援標籤）
     */
    public function getTagUsageStatsByPeriod(StatisticsPeriod $period, int $limit = 20): array;

    /**
     * 檢查指定週期是否有文章資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return bool 有資料時回傳 true，否則回傳 false
     */
    public function hasPostDataInPeriod(StatisticsPeriod $period): bool;

    /**
     * 取得指定文章在特定週期的統計資料.
     *
     * @param int $postId 文章ID
     * @param StatisticsPeriod $period 統計週期
     * @return array{views: int, comments: int, likes: int, shares: int, source: string} 文章統計資料
     */
    public function getPostStatsByPeriod(int $postId, StatisticsPeriod $period): array;

    /**
     * 取得按發布時間分組的文章統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{publish_hour: string, publish_day: string, avg_views: float}> 發布時間統計
     */
    public function getPostsByPublishTime(StatisticsPeriod $period): array;

    /**
     * 取得文章歷史表現資料.
     *
     * @param int $postId 文章ID
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, daily_views: int}> 歷史表現資料
     */
    public function getPostHistoricalPerformance(int $postId, StatisticsPeriod $period): array;

    /**
     * 取得文章統計趨勢資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $dataPoints 資料點數量，預設為 30
     * @return array<array{date: string, post_count: int, view_count: int, unique_views: int}> 趨勢資料
     */
    public function getStatisticsTrends(StatisticsPeriod $period, int $dataPoints = 30): array;
}
