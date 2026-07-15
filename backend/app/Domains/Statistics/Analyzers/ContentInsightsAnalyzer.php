<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\DTOs\ContentInsightsDTO;
use App\Domains\Statistics\Enums\PerformanceGrade;

/**
 * 內容洞察分析器.
 *
 * 負責分析內容效能、策略建議、優化洞察、季節性策略與讀者行為
 */
class ContentInsightsAnalyzer
{
    /**
     * 執行完整內容洞察分析.
     */
    public function analyze(ContentInsightsDTO $dto): ContentInsightsResult
    {
        return new ContentInsightsResult(
            performanceGrade: $this->getPerformanceGrade($dto),
            contentStrategyRecommendations: $this->getContentStrategyRecommendations($dto),
            optimizationInsights: $this->getOptimizationInsights($dto),
            seasonalContentStrategy: $this->getSeasonalContentStrategy($dto),
            readerBehaviorAnalysis: $this->getReaderBehaviorAnalysis($dto),
        );
    }

    /**
     * 取得內容效能評級.
     */
    public function getPerformanceGrade(ContentInsightsDTO $dto): PerformanceGrade
    {
        $engagementRate = $dto->getAverageEngagementRate();
        $completionRate = $dto->getCompletionRate();
        $shareRate = $dto->getShareRate();
        $score = ($engagementRate * 0.4) + ($completionRate * 0.4) + ($shareRate * 0.2);

        return match (true) {
            $score >= 80 => PerformanceGrade::EXCELLENT,
            $score >= 60 => PerformanceGrade::GOOD,
            $score >= 40 => PerformanceGrade::AVERAGE,
            $score >= 20 => PerformanceGrade::POOR,
            default      => PerformanceGrade::CRITICAL,
        };
    }

    /**
     * 取得內容策略建議.
     *
     * @return array<string, mixed>
     */
    public function getContentStrategyRecommendations(ContentInsightsDTO $dto): array
    {
        $recommendations = [];
        if ($dto->getAverageEngagementRate() < 5.0) {
            $recommendations['engagement'] = [
                'priority'    => 'high',
                'action'      => '提升內容互動性',
                'suggestions' => ['增加問答環節', '加入互動元素', '改善內容標題'],
            ];
        }
        if ($dto->getCompletionRate() < 60.0) {
            $recommendations['completion'] = [
                'priority'    => 'high',
                'action'      => '優化內容結構',
                'suggestions' => ['縮短內容長度', '改善排版', '增加視覺元素'],
            ];
        }
        if ($dto->getShareRate() < 2.0) {
            $recommendations['sharing'] = [
                'priority'    => 'medium',
                'action'      => '提升內容分享價值',
                'suggestions' => ['創造更多有價值的內容', '優化分享功能', '增加社群元素'],
            ];
        }
        $topTopic = $dto->getTopTopic();
        if ($topTopic !== null) {
            $recommendations['topics'] = [
                'priority'    => 'medium',
                'action'      => "專注於熱門主題：{$topTopic}",
                'suggestions' => ['創作更多相關內容', '深入探討熱門話題', '建立主題系列'],
            ];
        }

        return $recommendations;
    }

    /**
     * 取得內容優化洞察.
     *
     * @return array<string, mixed>
     */
    public function getOptimizationInsights(ContentInsightsDTO $dto): array
    {
        return [
            'optimal_publish_time' => [
                'hour' => $dto->getPeakEngagementHour(),
                'day'  => $dto->getPeakEngagementDay(),
            ],
            'content_specifications' => [
                'optimal_length'     => $dto->getOptimalContentLength(),
                'target_read_time'   => $dto->getAverageReadTime(),
                'recommended_format' => $dto->getMostPopularFormat(),
            ],
            'engagement_optimization' => [
                'target_engagement_rate' => max(8.0, $dto->getAverageEngagementRate() * 1.2),
                'target_completion_rate' => max(70.0, $dto->getCompletionRate() * 1.1),
                'target_share_rate'      => max(3.0, $dto->getShareRate() * 1.3),
            ],
            'lifecycle_management' => [
                'content_lifespan'        => $dto->getContentLifespanDays(),
                'peak_period'             => $dto->getPeakViewsPeriod(),
                'refresh_recommendations' => $this->getRefreshRecommendations($dto),
            ],
        ];
    }

    /**
     * 取得季節性內容策略.
     *
     * @return array<string, mixed>
     */
    public function getSeasonalContentStrategy(ContentInsightsDTO $dto): array
    {
        $currentSeason = $this->getCurrentSeason();
        $seasonalData = $dto->getSeasonalTrends()[$currentSeason] ?? [];

        return [
            'current_season'               => $currentSeason,
            'seasonal_performance'         => $seasonalData,
            'recommended_topics'           => is_array($seasonalData) && isset($seasonalData['trending_topics']) ? $seasonalData['trending_topics'] : [],
            'optimal_formats'              => is_array($seasonalData) && isset($seasonalData['popular_formats']) ? $seasonalData['popular_formats'] : [],
            'engagement_patterns'          => is_array($seasonalData) && isset($seasonalData['engagement_patterns']) ? $seasonalData['engagement_patterns'] : [],
            'content_calendar_suggestions' => $this->generateContentCalendarSuggestions($currentSeason),
        ];
    }

    /**
     * 取得讀者行為分析.
     *
     * @return array<string, mixed>
     */
    public function getReaderBehaviorAnalysis(ContentInsightsDTO $dto): array
    {
        return [
            'reading_habits' => [
                'avg_scroll_depth' => $dto->getAverageScrollDepth(),
                'completion_rate'  => $dto->getCompletionRate(),
                'return_rate'      => $dto->getReturnReaderRate(),
                'bounce_rate'      => $dto->getBounceRate(),
            ],
            'engagement_preferences' => [
                'preferred_content_length' => $dto->getOptimalContentLength(),
                'optimal_read_time'        => $dto->getAverageReadTime(),
                'most_engaging_format'     => $dto->getMostPopularFormat(),
            ],
            'interaction_patterns' => [
                'peak_activity_time' => $dto->getPeakEngagementHour(),
                'preferred_day'      => $dto->getPeakEngagementDay(),
                'sharing_behavior'   => [
                    'avg_shares'       => $dto->getAverageSharesPerContent(),
                    'most_shared_type' => $dto->getMostSharableContentType(),
                ],
            ],
            'content_discovery' => $dto->getUserEngagementPatterns()['discovery_patterns'] ?? [],
        ];
    }

    private function getCurrentSeason(): string
    {
        $month = (int) date('n');

        return match (true) {
            in_array($month, [12, 1, 2], true) => 'winter',
            in_array($month, [3, 4, 5], true)  => 'spring',
            in_array($month, [6, 7, 8], true)  => 'summer',
            default                            => 'autumn',
        };
    }

    /**
     * @return array<string>
     */
    private function getRefreshRecommendations(ContentInsightsDTO $dto): array
    {
        $lifespan = $dto->getContentLifespanDays();
        if ($lifespan < 30) {
            return ['每週檢查內容效能', '快速更新過時資訊', '持續優化標題和描述'];
        } elseif ($lifespan < 90) {
            return ['每月評估內容相關性', '更新統計數據', '添加新的相關資訊'];
        } else {
            return ['每季度全面檢視', '重新評估內容價值', '考慮重新撰寫或合併內容'];
        }
    }

    /**
     * @return array<string>
     */
    private function generateContentCalendarSuggestions(string $season): array
    {
        return match ($season) {
            'spring' => ['新年目標相關內容', '春季清理技巧', '成長和學習主題'],
            'summer' => ['度假和旅遊內容', '戶外活動指南', '輕鬆娛樂主題'],
            'autumn' => ['回到學校內容', '準備冬季主題', '反思和規劃內容'],
            'winter' => ['年終總結', '新年計劃', '室內活動推薦'],
            default  => ['通用主題內容'],
        };
    }
}
