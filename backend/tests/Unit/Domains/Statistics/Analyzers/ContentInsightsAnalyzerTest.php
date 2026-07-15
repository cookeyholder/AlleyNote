<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\ContentInsightsAnalyzer;
use App\Domains\Statistics\Analyzers\ContentInsightsResult;
use App\Domains\Statistics\DTOs\ContentInsightsDTO;
use App\Domains\Statistics\Enums\PerformanceGrade;
use Tests\Support\UnitTestCase;

/**
 * ContentInsightsAnalyzer 單元測試.
 */
class ContentInsightsAnalyzerTest extends UnitTestCase
{
    private ContentInsightsAnalyzer $analyzer;

    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->analyzer = new ContentInsightsAnalyzer();
        $this->validData = [
            'top_performing_content' => [
                ['id' => 101, 'title' => '如何學習程式設計', 'metric_value' => 1500, 'type' => 'views'],
                ['id' => 102, 'title' => 'PHP 最佳實踐', 'metric_value' => 1200, 'type' => 'views'],
            ],
            'content_performance_metrics' => [
                'avg_views_per_content' => 850.5, 'avg_engagement_rate' => 6.5,
                'avg_read_time'         => 320, 'bounce_rate' => 35.2,
                'completion_rate'       => 68.7, 'share_rate' => 3.8,
            ],
            'popular_topics'           => ['programming' => 450, 'web_development' => 380],
            'content_formats'          => ['article' => 650, 'tutorial' => 420],
            'user_engagement_patterns' => [
                'peak_hour'          => '14:00', 'peak_day' => 'Tuesday',
                'discovery_patterns' => ['search' => 45.2],
            ],
            'content_lifecycle_analysis' => ['avg_lifespan_days' => 45, 'peak_views_period' => 'first_week'],
            'reading_patterns'           => ['optimal_length_words' => 1200, 'avg_scroll_depth' => 72.5, 'return_reader_rate' => 28.3],
            'shareability'               => ['avg_shares_per_content' => 4.2, 'most_sharable_type' => 'infographic'],
            'seasonal_trends'            => ['spring' => ['trending_topics' => ['新年目標'], 'popular_formats' => ['article']]],
            'content_optimization'       => [],
            'generated_at'               => '2024-01-15T10:30:00Z',
        ];
    }

    private function createDTO(): ContentInsightsDTO
    {
        return ContentInsightsDTO::fromArray($this->validData);
    }

    public function testPerformanceGrade(): void
    {
        $dto = $this->createDTO();
        $grade = $this->analyzer->getPerformanceGrade($dto);

        $this->assertInstanceOf(PerformanceGrade::class, $grade);
        $this->assertSame(PerformanceGrade::POOR, $grade);
    }

    public function testPerformanceGradeExcellent(): void
    {
        $this->validData['content_performance_metrics'] = [
            'avg_views_per_content' => 1000.0, 'avg_engagement_rate' => 90.0,
            'avg_read_time'         => 400, 'bounce_rate' => 10.0,
            'completion_rate'       => 95.0, 'share_rate' => 15.0,
        ];
        $dto = $this->createDTO();
        $grade = $this->analyzer->getPerformanceGrade($dto);

        $this->assertSame(PerformanceGrade::GOOD, $grade);
    }

    public function testContentStrategyRecommendations(): void
    {
        $this->validData['content_performance_metrics'] = [
            'avg_views_per_content' => 100.0, 'avg_engagement_rate' => 2.0,
            'avg_read_time'         => 120, 'bounce_rate' => 70.0,
            'completion_rate'       => 40.0, 'share_rate' => 1.0,
        ];
        $dto = $this->createDTO();
        $recommendations = $this->analyzer->getContentStrategyRecommendations($dto);

        $this->assertArrayHasKey('engagement', $recommendations);
        $this->assertArrayHasKey('completion', $recommendations);
        $this->assertArrayHasKey('sharing', $recommendations);
        $this->assertArrayHasKey('topics', $recommendations);

        $this->assertSame('high', $recommendations['engagement']['priority']);
        $this->assertSame('提升內容互動性', $recommendations['engagement']['action']);
    }

    public function testOptimizationInsights(): void
    {
        $dto = $this->createDTO();
        $insights = $this->analyzer->getOptimizationInsights($dto);

        $this->assertArrayHasKey('optimal_publish_time', $insights);
        $this->assertArrayHasKey('content_specifications', $insights);
        $this->assertArrayHasKey('engagement_optimization', $insights);
        $this->assertArrayHasKey('lifecycle_management', $insights);

        $this->assertSame('14:00', $insights['optimal_publish_time']['hour']);
        $this->assertSame(1200, $insights['content_specifications']['optimal_length']);
        $this->assertSame(8.0, $insights['engagement_optimization']['target_engagement_rate']);
    }

    public function testSeasonalContentStrategy(): void
    {
        $dto = $this->createDTO();
        $strategy = $this->analyzer->getSeasonalContentStrategy($dto);

        $this->assertArrayHasKey('current_season', $strategy);
        $this->assertArrayHasKey('seasonal_performance', $strategy);
        $this->assertArrayHasKey('content_calendar_suggestions', $strategy);

        $this->assertContains($strategy['current_season'], ['spring', 'summer', 'autumn', 'winter']);
        $this->assertNotEmpty($strategy['content_calendar_suggestions']);
    }

    public function testReaderBehaviorAnalysis(): void
    {
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getReaderBehaviorAnalysis($dto);

        $this->assertArrayHasKey('reading_habits', $analysis);
        $this->assertArrayHasKey('engagement_preferences', $analysis);
        $this->assertArrayHasKey('interaction_patterns', $analysis);

        $this->assertSame(72.5, $analysis['reading_habits']['avg_scroll_depth']);
        $this->assertSame(68.7, $analysis['reading_habits']['completion_rate']);
        $this->assertSame('14:00', $analysis['interaction_patterns']['peak_activity_time']);
    }

    public function testLifespanBasedRefreshRecommendations(): void
    {
        $this->validData['content_lifecycle_analysis']['avg_lifespan_days'] = 20;
        $dto = $this->createDTO();
        $insights = $this->analyzer->getOptimizationInsights($dto);
        $recommendations = $insights['lifecycle_management']['refresh_recommendations'];
        $this->assertContains('每週檢查內容效能', $recommendations);

        $this->validData['content_lifecycle_analysis']['avg_lifespan_days'] = 120;
        $dto2 = $this->createDTO();
        $insights2 = $this->analyzer->getOptimizationInsights($dto2);
        $recommendations2 = $insights2['lifecycle_management']['refresh_recommendations'];
        $this->assertContains('每季度全面檢視', $recommendations2);
    }

    public function testAnalyzeReturnsResult(): void
    {
        $dto = $this->createDTO();
        $result = $this->analyzer->analyze($dto);

        $this->assertInstanceOf(ContentInsightsResult::class, $result);
        $this->assertInstanceOf(PerformanceGrade::class, $result->getPerformanceGrade());
    }
}
