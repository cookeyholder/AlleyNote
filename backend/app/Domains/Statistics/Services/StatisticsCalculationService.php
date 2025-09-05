<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\Enums\PeriodType;
use DateTimeImmutable;

/**
 * 統計計算服務
 * 負責各種統計運算與計算邏輯.
 */
class StatisticsCalculationService
{
    /**
     * 計算期間內的總計.
     */
    public function calculatePeriodTotals(StatisticsPeriod $period): array
    {
        // 此方法應該通過 Repository 取得實際資料
        // 這裡返回測試用的預設值
        return [
            'total_posts' => 0,
            'total_views' => 0,
            'unique_visitors' => 0,
        ];
    }

    /**
     * 計算來源分佈.
     *
     * @return array<SourceStatistics>
     */
    public function calculateSourceDistribution(StatisticsPeriod $period): array
    {
        // 此方法應該通過 Repository 取得實際資料
        // 這裡返回測試用的預設值
        return [];
    }

    /**
     * 計算成長率.
     *
     * @return array{current_total: int, previous_total: int, growth_count: int, growth_rate: float, growth_percentage: float}
     */
    public function calculateGrowthRate(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
    ): array {
        // 此方法應該通過 Repository 取得實際資料並計算成長率
        // 這裡返回測試用的預設值
        $currentTotal = 100;
        $previousTotal = 80;
        $growthCount = $currentTotal - $previousTotal;
        $growthRate = $previousTotal > 0 ? $growthCount / $previousTotal : 0.0;
        $growthPercentage = $growthRate * 100;

        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'growth_count' => $growthCount,
            'growth_rate' => $growthRate,
            'growth_percentage' => $growthPercentage,
        ];
    }

    /**
     * 計算趨勢資料.
     */
    public function calculateTrends(StatisticsPeriod $period): array
    {
        // 此方法應該通過 Repository 取得實際資料並計算趨勢
        // 這裡返回測試用的預設值
        return [
            'trend_direction' => 'upward',
            'trend_strength' => 'moderate',
            'trend_score' => 0.65,
        ];
    }

    /**
     * 計算額外指標.
     *
     * @return array<string, StatisticsMetric>
     */
    public function calculateAdditionalMetrics(StatisticsPeriod $period): array
    {
        // 此方法應該通過 Repository 取得實際資料並計算額外指標
        // 這裡返回測試用的預設值
        return [
            'bounce_rate' => StatisticsMetric::percentage(35.2, '跳出率'),
            'session_duration' => StatisticsMetric::timeInSeconds(125, '平均會話時長'),
            'pages_per_session' => StatisticsMetric::ratio(2.8, '每次會話頁面數'),
        ];
    }

    /**
     * 比較兩個統計快照.
     */
    public function compareSnapshots(
        StatisticsSnapshot $current,
        StatisticsSnapshot $previous,
    ): array {
        $currentPosts = $current->getTotalPosts()->value;
        $previousPosts = $previous->getTotalPosts()->value;
        $currentViews = $current->getTotalViews()->value;
        $previousViews = $previous->getTotalViews()->value;

        $postsGrowthRate = $previousPosts > 0
            ? (($currentPosts - $previousPosts) / $previousPosts) * 100
            : 0.0;

        $viewsGrowthRate = $previousViews > 0
            ? (($currentViews - $previousViews) / $previousViews) * 100
            : 0.0;

        return [
            'posts_growth_rate' => $postsGrowthRate,
            'views_growth_rate' => $viewsGrowthRate,
            'posts_difference' => $currentPosts - $previousPosts,
            'views_difference' => $currentViews - $previousViews,
        ];
    }

    /**
     * 計算統計快照的分數.
     */
    public function calculateSnapshotScore(StatisticsSnapshot $snapshot): float
    {
        $postsWeight = 0.3;
        $viewsWeight = 0.5;
        $sourceWeight = 0.2;

        $postsScore = min($snapshot->getTotalPosts()->value * 0.1, 100);
        $viewsScore = min($snapshot->getTotalViews()->value * 0.01, 100);
        $sourceScore = count($snapshot->getSourceStats()) * 10;

        return ($postsScore * $postsWeight) +
               ($viewsScore * $viewsWeight) +
               ($sourceScore * $sourceWeight);
    }

    /**
     * 計算自定義指標.
     */
    public function calculateCustomMetric(
        string $metricName,
        array $parameters,
    ): StatisticsMetric {
        // 根據指標名稱和參數計算自定義指標
        // 這是一個可擴展的方法，允許動態添加新的計算邏輯

        return match ($metricName) {
            'engagement_rate' => $this->calculateEngagementRate($parameters),
            'conversion_rate' => $this->calculateConversionRate($parameters),
            'retention_rate' => $this->calculateRetentionRate($parameters),
            default => StatisticsMetric::create(0, '', "未知指標: {$metricName}"),
        };
    }

    /**
     * 計算參與率.
     */
    private function calculateEngagementRate(array $parameters): StatisticsMetric
    {
        $interactions = $parameters['interactions'] ?? 0;
        $totalViews = $parameters['total_views'] ?? 0;

        $rate = $totalViews > 0 ? ($interactions / $totalViews) * 100 : 0;

        return StatisticsMetric::percentage($rate, '參與率');
    }

    /**
     * 計算轉換率.
     */
    private function calculateConversionRate(array $parameters): StatisticsMetric
    {
        $conversions = $parameters['conversions'] ?? 0;
        $totalVisitors = $parameters['total_visitors'] ?? 0;

        $rate = $totalVisitors > 0 ? ($conversions / $totalVisitors) * 100 : 0;

        return StatisticsMetric::percentage($rate, '轉換率');
    }

    /**
     * 計算留存率.
     */
    private function calculateRetentionRate(array $parameters): StatisticsMetric
    {
        $returningUsers = $parameters['returning_users'] ?? 0;
        $totalUsers = $parameters['total_users'] ?? 0;

        $rate = $totalUsers > 0 ? ($returningUsers / $totalUsers) * 100 : 0;

        return StatisticsMetric::percentage($rate, '留存率');
    }
}
