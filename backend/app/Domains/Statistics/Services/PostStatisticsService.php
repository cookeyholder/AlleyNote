<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 文章統計核心領域服務.
 *
 * 專門處理文章相關統計的業務邏輯，包含文章觀看、互動、
 * 內容分析等複雜統計計算。封裝文章統計的領域知識。
 *
 * 主要功能：
 * - 文章觀看統計計算
 * - 內容互動分析
 * - 熱門文章排行
 * - 文章效能指標
 * - 讀者行為分析
 *
 * 設計原則：
 * - 純領域邏輯，不依賴外部資源
 * - 業務規則集中管理
 * - 高可測試性
 * - 符合 DDD 模式
 */
class PostStatisticsService
{
    public function __construct(
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
    ) {}

    /**
     * 計算文章基本統計指標.
     *
     * @return array{
     *     total_posts: StatisticsMetric,
     *     total_views: StatisticsMetric,
     *     unique_viewers: StatisticsMetric,
     *     average_views_per_post: StatisticsMetric,
     *     engagement_rate: StatisticsMetric
     * }
     */
    public function calculateBasicMetrics(StatisticsPeriod $period): array
    {
        $totalPosts = $this->postStatisticsRepository->countPostsByPeriod($period);
        $totalViews = $this->postStatisticsRepository->countViewsByPeriod($period);
        $uniqueViewers = $this->postStatisticsRepository->countUniqueViewersByPeriod($period);

        $averageViewsPerPost = $totalPosts > 0 ? $totalViews / $totalPosts : 0.0;
        $engagementRate = $this->calculateEngagementRate($period);

        return [
            'total_posts' => StatisticsMetric::count($totalPosts, '總文章數'),
            'total_views' => StatisticsMetric::count($totalViews, '總觀看次數'),
            'unique_viewers' => StatisticsMetric::count($uniqueViewers, '不重複觀看者'),
            'average_views_per_post' => StatisticsMetric::create($averageViewsPerPost, '次', '平均每篇觀看次數', 2),
            'engagement_rate' => StatisticsMetric::percentage($engagementRate, '互動率'),
        ];
    }

    /**
     * 分析來源分佈.
     *
     * @return array<array{
     *     source_type: string,
     *     view_count: int,
     *     unique_viewers: int,
     *     percentage: float,
     *     performance_score: float
     * }>
     */
    public function analyzeSourceDistribution(StatisticsPeriod $period): array
    {
        $distribution = [];
        $totalViews = $this->postStatisticsRepository->countViewsByPeriod($period);

        if ($totalViews === 0) {
            return $this->createEmptySourceDistribution();
        }

        foreach (SourceType::cases() as $sourceType) {
            $sourceStats = $this->postStatisticsRepository->getViewStatisticsBySource(
                $period,
                $sourceType,
            );

            $viewCount = (int) ($sourceStats[0]['view_count'] ?? 0);
            $uniqueViewers = (int) ($sourceStats[0]['unique_viewers'] ?? 0);
            $percentage = ($viewCount / $totalViews) * 100;
            $performanceScore = $this->calculateSourcePerformanceScore($sourceType, $viewCount, $uniqueViewers);

            $distribution[] = [
                'source_type' => $sourceType->value,
                'view_count' => $viewCount,
                'unique_viewers' => $uniqueViewers,
                'percentage' => $percentage,
                'performance_score' => $performanceScore,
            ];
        }

        return $distribution;
    }    /**
     * 取得熱門文章排行榜.
     *
     * @return array<array{
     *     post_id: int,
     *     title: string,
     *     view_count: int,
     *     unique_viewers: int,
     *     author: string,
     *     popularity_score: float,
     *     performance_tier: string
     * }>
     */
    public function getPopularPostsRanking(StatisticsPeriod $period, int $limit = 10): array
    {
        $popularPosts = $this->postStatisticsRepository->getPopularPosts($period, $limit);

        if (empty($popularPosts)) {
            return [];
        }

        $maxViews = max(array_column($popularPosts, 'view_count'));
        $ranking = [];

        foreach ($popularPosts as $index => $post) {
            $popularityScore = $maxViews > 0 ? ($post['view_count'] / $maxViews) * 100 : 0;
            $performanceTier = $this->determinePerformanceTier($popularityScore);

            $ranking[] = [
                'rank' => $index + 1,
                'post_id' => $post['post_id'],
                'title' => $post['post_title'],
                'view_count' => $post['view_count'],
                'unique_viewers' => $post['unique_viewers'],
                'author' => $post['author'],
                'popularity_score' => round($popularityScore, 2),
                'performance_tier' => $performanceTier,
                'engagement_ratio' => $this->calculatePostEngagementRatio($post),
            ];
        }

        return $ranking;
    }

    /**
     * 分析文章內容效能.
     *
     * @return array{
     *     content_length_analysis: array,
     *     read_completion_analysis: array,
     *     engagement_patterns: array
     * }
     */
    public function analyzeContentPerformance(StatisticsPeriod $period): array
    {
        return [
            'content_length_analysis' => $this->analyzeContentLength($period),
            'read_completion_analysis' => $this->analyzeReadCompletion($period),
            'engagement_patterns' => $this->analyzeEngagementPatterns($period),
        ];
    }

    /**
     * 計算文章趨勢指標.
     *
     * @return array{
     *     publishing_trend: array,
     *     viewing_trend: array,
     *     engagement_trend: array,
     *     quality_indicators: array
     * }
     */
    public function calculateTrendIndicators(StatisticsPeriod $period): array
    {
        return [
            'publishing_trend' => $this->analyzePublishingTrend($period),
            'viewing_trend' => $this->analyzeViewingTrend($period),
            'engagement_trend' => $this->analyzeEngagementTrend($period),
            'quality_indicators' => $this->calculateQualityIndicators($period),
        ];
    }

    /**
     * 分析讀者行為模式.
     *
     * @return array{
     *     time_patterns: array,
     *     device_preferences: array,
     *     reading_behavior: array,
     *     retention_analysis: array
     * }
     */
    public function analyzeReaderBehavior(StatisticsPeriod $period): array
    {
        return [
            'time_patterns' => $this->analyzeViewingTimePatterns($period),
            'device_preferences' => $this->analyzeDevicePreferences($period),
            'reading_behavior' => $this->analyzeReadingBehavior($period),
            'retention_analysis' => $this->analyzeReaderRetention($period),
        ];
    }

    /**
     * 計算文章SEO效能.
     *
     * @return array{
     *     search_performance: array,
     *     keyword_effectiveness: array,
     *     click_through_rates: array,
     *     organic_traffic_analysis: array
     * }
     */
    public function calculateSeoPerformance(StatisticsPeriod $period): array
    {
        return [
            'search_performance' => $this->analyzeSearchPerformance($period),
            'keyword_effectiveness' => $this->analyzeKeywordEffectiveness($period),
            'click_through_rates' => $this->analyzeClickThroughRates($period),
            'organic_traffic_analysis' => $this->analyzeOrganicTraffic($period),
        ];
    }

    /**
     * 評估文章品質指標.
     *
     * @return array{
     *     content_quality_score: float,
     *     user_satisfaction_score: float,
     *     engagement_quality_score: float,
     *     overall_rating: string
     * }
     */
    public function evaluateContentQuality(StatisticsPeriod $period): array
    {
        $engagementStats = $this->postStatisticsRepository->getEngagementStats($period);
        $readCompletionStats = $this->postStatisticsRepository->getReadCompletionStats($period, 100);
        $bounceRateStats = $this->postStatisticsRepository->getBounceRateStats($period);

        $contentQualityScore = $this->calculateContentQualityScore($engagementStats, $readCompletionStats);
        $userSatisfactionScore = $this->calculateUserSatisfactionScore($bounceRateStats, $readCompletionStats);
        $engagementQualityScore = $this->calculateEngagementQualityScore($engagementStats);

        $overallScore = ($contentQualityScore + $userSatisfactionScore + $engagementQualityScore) / 3;
        $overallRating = $this->determineQualityRating($overallScore);

        return [
            'content_quality_score' => round($contentQualityScore, 2),
            'user_satisfaction_score' => round($userSatisfactionScore, 2),
            'engagement_quality_score' => round($engagementQualityScore, 2),
            'overall_score' => round($overallScore, 2),
            'overall_rating' => $overallRating,
            'recommendations' => $this->generateQualityRecommendations($overallScore, $engagementStats),
        ];
    }

    /**
     * 計算互動率.
     */
    private function calculateEngagementRate(StatisticsPeriod $period): float
    {
        $engagementStats = $this->postStatisticsRepository->getEngagementStats($period);

        return $engagementStats['engagement_rate'] ?? 0.0;
    }

    /**
     * 計算來源效能分數.
     */
    private function calculateSourcePerformanceScore(
        SourceType $sourceType,
        int $viewCount,
        int $uniqueViewers,
    ): float {
        $baseScore = $sourceType->getPriority() * 10; // 基礎分數
        $volumeScore = min($viewCount / 100, 50); // 量級分數，最高50分
        $qualityScore = $viewCount > 0 ? ($uniqueViewers / $viewCount) * 40 : 0; // 品質分數，最高40分

        return round($baseScore + $volumeScore + $qualityScore, 2);
    }

    /**
     * 判斷效能等級.
     */
    private function determinePerformanceTier(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'average',
            $score >= 20 => 'below_average',
            default => 'poor',
        };
    }

    /**
     * 計算文章互動比率.
     */
    private function calculatePostEngagementRatio(array $post): float
    {
        $viewCount = $post['view_count'];
        $uniqueViewers = $post['unique_viewers'];

        return $viewCount > 0 ? round(($uniqueViewers / $viewCount) * 100, 2) : 0.0;
    }

    /**
     * 建立空的來源分布.
     */
    private function createEmptySourceDistribution(): array
    {
        $distribution = [];
        foreach (SourceType::cases() as $sourceType) {
            $distribution[] = [
                'source_type' => $sourceType->value,
                'source_name' => $sourceType->getDisplayName(),
                'view_count' => 0,
                'unique_viewers' => 0,
                'percentage' => 0.0,
                'performance_score' => 0.0,
                'is_primary_source' => $sourceType->isPrimarySource(),
            ];
        }

        return $distribution;
    }

    /**
     * 分析內容長度與效能關係.
     */
    private function analyzeContentLength(StatisticsPeriod $period): array
    {
        $lengthStats = $this->postStatisticsRepository->getContentLengthStats($period);

        return array_map(function ($stat) {
            return [
                'length_range' => $stat['length_range'],
                'post_count' => $stat['post_count'],
                'average_views' => round($stat['avg_views'], 2),
                'total_views' => $stat['total_views'],
                'performance_rating' => $this->rateContentLengthPerformance($stat['avg_views']),
            ];
        }, $lengthStats);
    }

    /**
     * 分析閱讀完成度.
     */
    private function analyzeReadCompletion(StatisticsPeriod $period): array
    {
        $completionStats = $this->postStatisticsRepository->getReadCompletionStats($period, 20);

        return array_map(function ($stat) {
            return [
                'post_id' => $stat['post_id'],
                'title' => $stat['post_title'],
                'completion_rate' => round($stat['completion_rate'], 2),
                'average_read_percentage' => round($stat['avg_read_percentage'], 2),
                'engagement_quality' => $this->rateEngagementQuality($stat['completion_rate']),
            ];
        }, $completionStats);
    }

    /**
     * 分析互動模式.
     */
    private function analyzeEngagementPatterns(StatisticsPeriod $period): array
    {
        $timeTrends = $this->postStatisticsRepository->getViewingTimeTrends($period);
        $socialSharing = $this->postStatisticsRepository->getSocialSharingStats($period, 10);

        return [
            'peak_hours' => $this->identifyPeakEngagementHours($timeTrends),
            'social_sharing_leaders' => $socialSharing,
            'engagement_distribution' => $this->analyzeEngagementDistribution($timeTrends),
        ];
    }

    /**
     * 分析發布趨勢.
     */
    private function analyzePublishingTrend(StatisticsPeriod $period): array
    {
        $publishingStats = $this->postStatisticsRepository->getPostPublishingStats($period);

        return array_map(function ($stat) {
            return [
                'date' => $stat['date'],
                'post_count' => $stat['post_count'],
                'total_views' => $stat['total_views'],
                'average_views_per_post' => $stat['post_count'] > 0
                    ? round($stat['total_views'] / $stat['post_count'], 2) : 0,
            ];
        }, $publishingStats);
    }

    /**
     * 分析觀看趨勢.
     */
    private function analyzeViewingTrend(StatisticsPeriod $period): array
    {
        return $this->postStatisticsRepository->getViewingTimeTrends($period);
    }

    /**
     * 分析互動趨勢.
     */
    private function analyzeEngagementTrend(StatisticsPeriod $period): array
    {
        $engagementStats = $this->postStatisticsRepository->getEngagementStats($period);
        $returningReaderStats = $this->postStatisticsRepository->getReturningReaderStats($period);

        return [
            'overall_engagement_rate' => $engagementStats['engagement_rate'],
            'return_rate' => $returningReaderStats['return_rate'],
            'new_vs_returning' => [
                'new_viewers' => $returningReaderStats['new_viewers'],
                'returning_viewers' => $returningReaderStats['returning_viewers'],
                'ratio' => $returningReaderStats['return_rate'],
            ],
        ];
    }

    /**
     * 計算品質指標.
     */
    private function calculateQualityIndicators(StatisticsPeriod $period): array
    {
        $bounceRate = $this->postStatisticsRepository->getBounceRateStats($period);
        $loadPerformance = $this->postStatisticsRepository->getLoadPerformanceStats($period);

        return [
            'bounce_rate' => $bounceRate['bounce_rate'],
            'average_load_time' => $loadPerformance['avg_load_time'],
            'performance_rating' => $this->rateOverallPerformance($bounceRate, $loadPerformance),
        ];
    }

    /**
     * 分析觀看時間模式.
     */
    private function analyzeViewingTimePatterns(StatisticsPeriod $period): array
    {
        return $this->postStatisticsRepository->getViewingTimeTrends($period);
    }

    /**
     * 分析裝置偏好.
     */
    private function analyzeDevicePreferences(StatisticsPeriod $period): array
    {
        return $this->postStatisticsRepository->getMobileViewStats($period);
    }

    /**
     * 分析閱讀行為.
     */
    private function analyzeReadingBehavior(StatisticsPeriod $period): array
    {
        $readCompletionStats = $this->postStatisticsRepository->getReadCompletionStats($period, 50);
        $bounceRateStats = $this->postStatisticsRepository->getBounceRateStats($period);

        return [
            'completion_patterns' => $readCompletionStats,
            'session_behavior' => $bounceRateStats,
        ];
    }

    /**
     * 分析讀者留存.
     */
    private function analyzeReaderRetention(StatisticsPeriod $period): array
    {
        return $this->postStatisticsRepository->getReturningReaderStats($period);
    }

    /**
     * 分析搜尋效能.
     */
    private function analyzeSearchPerformance(StatisticsPeriod $period): array
    {
        return $this->postStatisticsRepository->getSearchKeywordStats($period, 30);
    }

    /**
     * 分析關鍵字效果.
     */
    private function analyzeKeywordEffectiveness(StatisticsPeriod $period): array
    {
        $keywordStats = $this->postStatisticsRepository->getSearchKeywordStats($period, 50);

        return array_map(function ($stat) {
            return [
                'keyword' => $stat['keyword'],
                'search_count' => $stat['search_count'],
                'result_clicks' => $stat['result_clicks'],
                'click_through_rate' => $stat['click_through_rate'],
                'effectiveness_rating' => $this->rateKeywordEffectiveness($stat['click_through_rate']),
            ];
        }, $keywordStats);
    }

    /**
     * 分析點擊率.
     */
    private function analyzeClickThroughRates(StatisticsPeriod $period): array
    {
        $keywordStats = $this->postStatisticsRepository->getSearchKeywordStats($period, 100);

        $totalSearches = array_sum(array_column($keywordStats, 'search_count'));
        $totalClicks = array_sum(array_column($keywordStats, 'result_clicks'));

        return [
            'overall_ctr' => $totalSearches > 0 ? round(($totalClicks / $totalSearches) * 100, 2) : 0,
            'keyword_performance' => $keywordStats,
        ];
    }

    /**
     * 分析自然流量.
     */
    private function analyzeOrganicTraffic(StatisticsPeriod $period): array
    {
        $sourceStats = $this->postStatisticsRepository->getViewStatisticsBySource($period, SourceType::SEARCH_ENGINE);

        return [
            'organic_views' => $sourceStats[0]['view_count'] ?? 0,
            'organic_percentage' => $sourceStats[0]['percentage'] ?? 0,
            'unique_organic_visitors' => $sourceStats[0]['unique_viewers'] ?? 0,
        ];
    }

    // 私有輔助方法用於評分和評級...

    private function calculateContentQualityScore(array $engagementStats, array $readCompletionStats): float
    {
        $engagementScore = min($engagementStats['engagement_rate'] * 10, 50);
        $completionScore = !empty($readCompletionStats)
            ? array_sum(array_column($readCompletionStats, 'completion_rate')) / count($readCompletionStats) : 0;

        return ($engagementScore + $completionScore) / 2;
    }

    private function calculateUserSatisfactionScore(array $bounceRateStats, array $readCompletionStats): float
    {
        $bounceScore = (100 - $bounceRateStats['bounce_rate']) * 0.6;
        $completionScore = !empty($readCompletionStats)
            ? array_sum(array_column($readCompletionStats, 'avg_read_percentage')) / count($readCompletionStats) * 0.4 : 0;

        return $bounceScore + $completionScore;
    }

    private function calculateEngagementQualityScore(array $engagementStats): float
    {
        return min($engagementStats['engagement_rate'] * 20, 100);
    }

    private function determineQualityRating(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 70 => 'very_good',
            $score >= 60 => 'good',
            $score >= 50 => 'fair',
            $score >= 40 => 'poor',
            default => 'very_poor',
        };
    }

    private function generateQualityRecommendations(float $score, array $engagementStats): array
    {
        $recommendations = [];

        if ($score < 60) {
            $recommendations[] = '建議改善內容品質以提高讀者參與度';
        }

        if ($engagementStats['engagement_rate'] < 0.3) {
            $recommendations[] = '考慮優化文章標題和摘要以提高點擊率';
        }

        return $recommendations;
    }

    // 其他評分輔助方法...
    private function rateContentLengthPerformance(float $avgViews): string
    {
        return $avgViews > 1000 ? 'high' : ($avgViews > 500 ? 'medium' : 'low');
    }

    private function rateEngagementQuality(float $completionRate): string
    {
        return $completionRate > 70 ? 'high' : ($completionRate > 40 ? 'medium' : 'low');
    }

    private function rateOverallPerformance(array $bounceRate, array $loadPerformance): string
    {
        $bounceScore = 100 - $bounceRate['bounce_rate'];
        $loadScore = max(0, 100 - ($loadPerformance['avg_load_time'] * 10));
        $overallScore = ($bounceScore + $loadScore) / 2;

        return $overallScore > 70 ? 'good' : ($overallScore > 50 ? 'average' : 'needs_improvement');
    }

    private function rateKeywordEffectiveness(float $ctr): string
    {
        return $ctr > 10 ? 'high' : ($ctr > 5 ? 'medium' : 'low');
    }

    private function identifyPeakEngagementHours(array $timeTrends): array
    {
        if (empty($timeTrends)) {
            return [];
        }

        usort($timeTrends, fn($a, $b) => $b['view_count'] <=> $a['view_count']);

        return array_slice($timeTrends, 0, 3);
    }

    private function analyzeEngagementDistribution(array $timeTrends): array
    {
        $totalViews = array_sum(array_column($timeTrends, 'view_count'));

        return array_map(function ($trend) use ($totalViews) {
            return [
                'hour' => $trend['hour'],
                'view_count' => $trend['view_count'],
                'percentage' => $totalViews > 0 ? round(($trend['view_count'] / $totalViews) * 100, 2) : 0,
            ];
        }, $timeTrends);
    }
}
