<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 文章統計資料存取介面
 * 專門處理文章相關的統計資料存取與計算，遵循單一職責原則.
 */
interface PostStatisticsRepositoryInterface
{
    /**
     * 計算指定週期內的文章總數.
     */
    public function countPostsByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的文章觀看總次數.
     */
    public function countViewsByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的不重複觀看人數.
     */
    public function countUniqueViewersByPeriod(StatisticsPeriod $period): int;

    /**
     * 依來源類型統計文章觀看次數.
     *
     * @return array<array{
     *     source_type: string,
     *     view_count: int,
     *     unique_viewers: int,
     *     percentage: float
     * }>
     */
    public function getViewStatisticsBySource(
        StatisticsPeriod $period,
        ?SourceType $sourceType = null,
    ): array;

    /**
     * 取得指定週期內最受歡迎的文章列表.
     *
     * @return array<array{
     *     post_id: int,
     *     post_title: string,
     *     view_count: int,
     *     unique_viewers: int,
     *     author: string,
     *     created_at: string
     * }>
     */
    public function getPopularPosts(
        StatisticsPeriod $period,
        int $limit = 10,
    ): array;

    /**
     * 取得文章發布統計（按日期分組）.
     *
     * @return array<array{
     *     date: string,
     *     post_count: int,
     *     total_views: int
     * }>
     */
    public function getPostPublishingStats(StatisticsPeriod $period): array;

    /**
     * 計算文章平均觀看次數.
     */
    public function getAverageViewsPerPost(StatisticsPeriod $period): float;

    /**
     * 取得文章觀看次數分布.
     *
     * @return array<array{
     *     view_range: string,
     *     post_count: int,
     *     percentage: float
     * }>
     */
    public function getViewCountDistribution(StatisticsPeriod $period): array;

    /**
     * 計算文章互動率相關統計.
     *
     * @return array{
     *     total_posts: int,
     *     total_views: int,
     *     avg_views_per_post: float,
     *     median_views: int,
     *     engagement_rate: float
     * }
     */
    public function getEngagementStats(StatisticsPeriod $period): array;

    /**
     * 取得文章標籤使用統計.
     *
     * @return array<array{
     *     tag_id: int,
     *     tag_name: string,
     *     usage_count: int,
     *     total_views: int,
     *     avg_views_per_post: float
     * }>
     */
    public function getTagUsageStats(
        StatisticsPeriod $period,
        int $limit = 20,
    ): array;

    /**
     * 取得文章長度與觀看次數關聯統計.
     *
     * @return array<array{
     *     length_range: string,
     *     post_count: int,
     *     avg_views: float,
     *     total_views: int
     * }>
     */
    public function getContentLengthStats(StatisticsPeriod $period): array;

    /**
     * 計算新舊文章觀看比例.
     *
     * @return array{
     *     new_posts_views: int,
     *     old_posts_views: int,
     *     new_posts_percentage: float,
     *     old_posts_percentage: float,
     *     cutoff_date: string
     * }
     */
    public function getNewVsOldPostsRatio(
        StatisticsPeriod $period,
        int $newPostDays = 30,
    ): array;

    /**
     * 取得文章觀看時間趨勢分析.
     *
     * @return array<array{
     *     hour: int,
     *     view_count: int,
     *     unique_viewers: int,
     *     avg_session_duration: float
     * }>
     */
    public function getViewingTimeTrends(StatisticsPeriod $period): array;

    /**
     * 計算文章搜尋關鍵字統計.
     *
     * @return array<array{
     *     keyword: string,
     *     search_count: int,
     *     result_clicks: int,
     *     click_through_rate: float
     * }>
     */
    public function getSearchKeywordStats(
        StatisticsPeriod $period,
        int $limit = 50,
    ): array;

    /**
     * 取得文章分類瀏覽統計.
     *
     * @return array<array{
     *     category_id: int,
     *     category_name: string,
     *     post_count: int,
     *     total_views: int,
     *     avg_views_per_post: float,
     *     unique_viewers: int
     * }>
     */
    public function getCategoryViewStats(
        StatisticsPeriod $period,
    ): array;

    /**
     * 計算回訪讀者統計.
     *
     * @return array{
     *     total_unique_viewers: int,
     *     returning_viewers: int,
     *     new_viewers: int,
     *     return_rate: float
     * }
     */
    public function getReturningReaderStats(StatisticsPeriod $period): array;

    /**
     * 取得文章社交媒體分享統計.
     *
     * @return array<array{
     *     post_id: int,
     *     post_title: string,
     *     facebook_shares: int,
     *     twitter_shares: int,
     *     linkedin_shares: int,
     *     total_shares: int
     * }>
     */
    public function getSocialSharingStats(
        StatisticsPeriod $period,
        int $limit = 10,
    ): array;

    /**
     * 計算跳出率統計.
     *
     * @return array{
     *     total_sessions: int,
     *     bounce_sessions: int,
     *     bounce_rate: float,
     *     avg_pages_per_session: float
     * }
     */
    public function getBounceRateStats(StatisticsPeriod $period): array;

    /**
     * 取得文章載入效能統計.
     *
     * @return array{
     *     avg_load_time: float,
     *     median_load_time: float,
     *     slow_loads_count: int,
     *     slow_loads_percentage: float
     * }
     */
    public function getLoadPerformanceStats(StatisticsPeriod $period): array;

    /**
     * 計算文章完成閱讀率.
     *
     * @return array<array{
     *     post_id: int,
     *     post_title: string,
     *     total_views: int,
     *     completed_reads: int,
     *     completion_rate: float,
     *     avg_read_percentage: float
     * }>
     */
    public function getReadCompletionStats(
        StatisticsPeriod $period,
        int $limit = 20,
    ): array;

    /**
     * 取得行動裝置瀏覽統計.
     *
     * @return array{
     *     desktop_views: int,
     *     mobile_views: int,
     *     tablet_views: int,
     *     mobile_percentage: float,
     *     desktop_percentage: float,
     *     tablet_percentage: float
     * }
     */
    public function getMobileViewStats(StatisticsPeriod $period): array;
}
