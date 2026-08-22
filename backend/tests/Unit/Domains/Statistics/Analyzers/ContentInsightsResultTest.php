<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\ContentInsightsResult;
use App\Domains\Statistics\Enums\PerformanceGrade;
use Tests\Support\UnitTestCase;

/**
 * 內容洞察分析結果測試.
 */
final class ContentInsightsResultTest extends UnitTestCase
{
    public function testGettersAndSerialization(): void
    {
        $grade = PerformanceGrade::EXCELLENT;
        $recommendations = ['publish_time' => 'morning'];
        $optimizationInsights = ['readability' => 'good'];
        $seasonalStrategy = ['summer' => 'increase_visuals'];
        $readerBehavior = ['avg_scroll_depth' => 0.8];

        $result = new ContentInsightsResult(
            performanceGrade: $grade,
            contentStrategyRecommendations: $recommendations,
            optimizationInsights: $optimizationInsights,
            seasonalContentStrategy: $seasonalStrategy,
            readerBehaviorAnalysis: $readerBehavior,
        );

        $this->assertSame($grade, $result->getPerformanceGrade());
        $this->assertSame($recommendations, $result->getContentStrategyRecommendations());
        $this->assertSame($optimizationInsights, $result->getOptimizationInsights());
        $this->assertSame($seasonalStrategy, $result->getSeasonalContentStrategy());
        $this->assertSame($readerBehavior, $result->getReaderBehaviorAnalysis());

        $expectedArray = [
            'calculated_metrics' => [
                'performance_grade' => $grade->value,
            ],
            'strategy_recommendations'  => $recommendations,
            'optimization_insights'     => $optimizationInsights,
            'seasonal_content_strategy' => $seasonalStrategy,
            'reader_behavior_analysis'  => $readerBehavior,
        ];

        $this->assertSame($expectedArray, $result->toArray());
        $this->assertSame($expectedArray, $result->jsonSerialize());
    }
}
