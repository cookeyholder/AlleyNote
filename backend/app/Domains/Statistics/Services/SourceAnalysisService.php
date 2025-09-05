<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\Enums\SourceType;

/**
 * 來源分析服務
 * 負責分析不同來源的統計資料.
 */
class SourceAnalysisService
{
    /**
     * 分析來源分佈.
     *
     * @return array<SourceStatistics>
     */
    public function analyzeSourceDistribution(StatisticsPeriod $period): array
    {
        // 此方法應該通過 Repository 取得實際資料
        // 這裡返回測試用的預設值
        return [
            SourceStatistics::create(SourceType::WEB, 150, 60.0),
            SourceStatistics::create(SourceType::MOBILE_APP, 80, 32.0),
            SourceStatistics::create(SourceType::API, 20, 8.0),
        ];
    }

    /**
     * 比較來源效能.
     */
    public function compareSourcePerformance(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
    ): array {
        $currentSources = $this->analyzeSourceDistribution($currentPeriod);
        $previousSources = $this->analyzeSourceDistribution($previousPeriod);

        $comparison = [];

        foreach ($currentSources as $currentSource) {
            $sourceType = $currentSource->sourceType;
            $previousSource = $this->findSourceByType($previousSources, $sourceType);

            $growth = $previousSource
                ? (($currentSource->count->value - $previousSource->count->value) / $previousSource->count->value) * 100
                : 100; // 如果之前沒有資料，視為 100% 成長

            $comparison[] = [
                'source_type' => $sourceType,
                'current_count' => $currentSource->count->value,
                'previous_count' => $previousSource?->count->value ?? 0,
                'growth_rate' => $growth,
                'trend' => $growth > 0 ? 'increasing' : ($growth < 0 ? 'decreasing' : 'stable'),
            ];
        }

        return $comparison;
    }

    /**
     * 分析來源轉換率.
     */
    public function analyzeSourceConversion(StatisticsPeriod $period): array
    {
        $sources = $this->analyzeSourceDistribution($period);
        $conversions = [];

        foreach ($sources as $source) {
            // 模擬轉換率計算
            $conversionRate = match ($source->sourceType) {
                SourceType::WEB => 2.5,
                SourceType::MOBILE_APP => 1.8,
                SourceType::API => 5.2,
                default => 2.0,
            };

            $conversions[] = [
                'source_type' => $source->sourceType,
                'view_count' => $source->count->value,
                'conversion_rate' => $conversionRate,
                'estimated_conversions' => round($source->count->value * $conversionRate / 100),
            ];
        }

        return $conversions;
    }

    /**
     * 取得來源品質分數.
     */
    public function getSourceQualityScore(SourceType $sourceType): float
    {
        // 根據來源類型返回品質分數
        return match ($sourceType) {
            SourceType::WEB => 8.5,
            SourceType::MOBILE_APP => 7.2,
            SourceType::API => 9.1,
            default => 6.0,
        };
    }

    /**
     * 分析來源時間分佈.
     */
    public function analyzeSourceTimeDistribution(
        StatisticsPeriod $period,
        SourceType $sourceType,
    ): array {
        // 此方法應該通過 Repository 取得實際資料
        // 這裡返回測試用的預設值
        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = [
                'hour' => $hour,
                'view_count' => rand(10, 100),
                'percentage' => rand(1, 10),
            ];
        }

        return $hours;
    }

    /**
     * 取得來源總結報告.
     */
    public function getSourceSummaryReport(StatisticsPeriod $period): array
    {
        $sources = $this->analyzeSourceDistribution($period);
        $totalViews = array_sum(array_map(fn($s) => $s->count->value, $sources));

        $topSource = $this->getTopSource($sources);
        $diversityScore = $this->calculateSourceDiversity($sources);

        return [
            'total_sources' => count($sources),
            'total_views' => $totalViews,
            'top_source' => [
                'type' => $topSource->sourceType,
                'count' => $topSource->count->value,
                'percentage' => $topSource->percentage->value,
            ],
            'diversity_score' => $diversityScore,
            'source_distribution' => array_map(function ($source) {
                return [
                    'type' => $source->sourceType,
                    'count' => $source->count->value,
                    'percentage' => $source->percentage->value,
                    'quality_score' => $this->getSourceQualityScore($source->sourceType),
                ];
            }, $sources),
        ];
    }

    /**
     * 找出特定類型的來源統計.
     *
     * @param array<SourceStatistics> $sources
     */
    private function findSourceByType(array $sources, SourceType $sourceType): ?SourceStatistics
    {
        foreach ($sources as $source) {
            if ($source->sourceType === $sourceType) {
                return $source;
            }
        }

        return null;
    }

    /**
     * 取得排名最高的來源.
     *
     * @param array<SourceStatistics> $sources
     */
    private function getTopSource(array $sources): SourceStatistics
    {
        if (empty($sources)) {
            return SourceStatistics::empty(SourceType::WEB);
        }

        return array_reduce($sources, function ($carry, $source) {
            return $carry === null || $source->count->value > $carry->count->value
                ? $source
                : $carry;
        });
    }

    /**
     * 計算來源多樣性分數.
     *
     * @param array<SourceStatistics> $sources
     */
    private function calculateSourceDiversity(array $sources): float
    {
        if (count($sources) <= 1) {
            return 0.0;
        }

        // 使用 Shannon 多樣性指數
        $totalViews = array_sum(array_map(fn($s) => $s->count->value, $sources));

        if ($totalViews === 0) {
            return 0.0;
        }

        $diversity = 0.0;
        foreach ($sources as $source) {
            $proportion = $source->count->value / $totalViews;
            if ($proportion > 0) {
                $diversity -= $proportion * log($proportion);
            }
        }

        // 正規化到 0-10 分
        return min($diversity * 2, 10.0);
    }

    /**
     * 預測來源趨勢.
     */
    public function predictSourceTrends(array $historicalData): array
    {
        // 簡單的線性趨勢預測
        $predictions = [];

        foreach (SourceType::cases() as $sourceType) {
            $sourceData = array_filter($historicalData, fn($data) => $data['source_type'] === $sourceType);

            if (count($sourceData) >= 2) {
                $trend = $this->calculateLinearTrend($sourceData);
                $predictions[] = [
                    'source_type' => $sourceType,
                    'predicted_growth' => $trend['slope'],
                    'confidence' => $trend['confidence'],
                    'next_period_estimate' => $trend['next_value'],
                ];
            }
        }

        return $predictions;
    }

    /**
     * 計算線性趨勢.
     */
    private function calculateLinearTrend(array $data): array
    {
        $n = count($data);
        $sumX = $sumY = $sumXY = $sumX2 = 0;

        foreach ($data as $i => $point) {
            $x = $i + 1;
            $y = $point['count'];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // 簡單的信心度計算
        $confidence = min(abs($slope) * 10, 100);

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'confidence' => $confidence,
            'next_value' => $slope * ($n + 1) + $intercept,
        ];
    }
}
