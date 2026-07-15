<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Enums\PerformanceGrade;
use JsonSerializable;

/**
 * 內容洞察分析結果.
 */
readonly class ContentInsightsResult implements JsonSerializable
{
    /**
     * @param PerformanceGrade $performanceGrade 效能評級
     * @param array<string, mixed> $contentStrategyRecommendations 內容策略建議
     * @param array<string, mixed> $optimizationInsights 優化洞察
     * @param array<string, mixed> $seasonalContentStrategy 季節性內容策略
     * @param array<string, mixed> $readerBehaviorAnalysis 讀者行為分析
     */
    public function __construct(
        private PerformanceGrade $performanceGrade,
        private array $contentStrategyRecommendations,
        private array $optimizationInsights,
        private array $seasonalContentStrategy,
        private array $readerBehaviorAnalysis,
    ) {}

    public function getPerformanceGrade(): PerformanceGrade
    {
        return $this->performanceGrade;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentStrategyRecommendations(): array
    {
        return $this->contentStrategyRecommendations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptimizationInsights(): array
    {
        return $this->optimizationInsights;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSeasonalContentStrategy(): array
    {
        return $this->seasonalContentStrategy;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReaderBehaviorAnalysis(): array
    {
        return $this->readerBehaviorAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'calculated_metrics' => [
                'performance_grade' => $this->performanceGrade->value,
            ],
            'strategy_recommendations'  => $this->contentStrategyRecommendations,
            'optimization_insights'     => $this->optimizationInsights,
            'seasonal_content_strategy' => $this->seasonalContentStrategy,
            'reader_behavior_analysis'  => $this->readerBehaviorAnalysis,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
