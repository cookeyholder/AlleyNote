<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\ContentInsightsDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ContentInsightsDTOTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'top_performing_content' => [
                [
                    'id' => 101,
                    'title' => '如何學習程式設計',
                    'metric_value' => 1500,
                    'type' => 'views',
                ],
                [
                    'id' => 102,
                    'title' => 'PHP 最佳實踐',
                    'metric_value' => 1200,
                    'type' => 'views',
                ],
            ],
            'content_performance_metrics' => [
                'avg_views_per_content' => 850.5,
                'avg_engagement_rate' => 6.5,
                'avg_read_time' => 320, // seconds
                'bounce_rate' => 35.2,
                'completion_rate' => 68.7,
                'share_rate' => 3.8,
            ],
            'popular_topics' => [
                'programming' => 450,
                'web_development' => 380,
                'database' => 280,
                'api_design' => 200,
            ],
            'content_formats' => [
                'article' => 650,
                'tutorial' => 420,
                'video' => 300,
                'infographic' => 150,
            ],
            'user_engagement_patterns' => [
                'peak_hour' => '14:00',
                'peak_day' => 'Tuesday',
                'avg_session_duration' => 480, // seconds
                'discovery_patterns' => [
                    'search' => 45.2,
                    'direct' => 28.7,
                    'social' => 16.1,
                    'referral' => 10.0,
                ],
            ],
            'content_lifecycle_analysis' => [
                'avg_lifespan_days' => 45,
                'peak_views_period' => 'first_week',
                'decay_rate' => 15.5, // percent per week
            ],
            'reading_patterns' => [
                'optimal_length_words' => 1200,
                'avg_scroll_depth' => 72.5,
                'return_reader_rate' => 28.3,
                'reading_speed_wpm' => 250,
            ],
            'shareability' => [
                'avg_shares_per_content' => 4.2,
                'most_sharable_type' => 'infographic',
                'share_platforms' => [
                    'Facebook' => 35.5,
                    'Twitter' => 28.2,
                    'LinkedIn' => 22.1,
                    'Others' => 14.2,
                ],
            ],
            'seasonal_trends' => [
                'spring' => [
                    'trending_topics' => ['新年目標', '學習計劃'],
                    'popular_formats' => ['article', 'tutorial'],
                    'engagement_patterns' => ['higher_morning_activity'],
                ],
                'summer' => [
                    'trending_topics' => ['度假技巧', '輕鬆閱讀'],
                    'popular_formats' => ['infographic', 'video'],
                    'engagement_patterns' => ['lower_overall_engagement'],
                ],
            ],
            'content_optimization' => [
                'title_optimization' => [
                    'optimal_length' => 60,
                    'high_performing_keywords' => ['完整指南', '最佳實踐', '深入解析'],
                ],
                'seo_recommendations' => [
                    'use_long_tail_keywords',
                    'improve_meta_descriptions',
                    'add_internal_links',
                ],
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
            'metadata' => [
                'report_id' => 'content_insights_001',
                'version' => '1.0',
                'analysis_period' => '2024-01-01_to_2024-01-15',
            ],
        ];
    }

    public function testConstructionWithValidData(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');

        $dto = new ContentInsightsDTO(
            topPerformingContent: [['id' => 1, 'title' => 'Test', 'metric_value' => 100]],
            contentPerformanceMetrics: ['avg_views_per_content' => 500.0, 'avg_engagement_rate' => 5.0, 'avg_read_time' => 300, 'bounce_rate' => 30.0, 'completion_rate' => 70.0, 'share_rate' => 2.5],
            popularTopics: ['tech' => 100],
            contentFormats: ['article' => 50],
            userEngagementPatterns: ['peak_hour' => '14:00'],
            contentLifecycleAnalysis: ['avg_lifespan_days' => 30],
            readingPatterns: ['optimal_length_words' => 1000],
            shareability: ['avg_shares_per_content' => 3.0],
            seasonalTrends: ['spring' => []],
            contentOptimization: ['title_optimization' => []],
            generatedAt: $generatedAt,
            metadata: ['report_id' => 'test'],
        );

        $this->assertSame([['id' => 1, 'title' => 'Test', 'metric_value' => 100]], $dto->getTopPerformingContent());
        $this->assertSame(['tech' => 100], $dto->getPopularTopics());
        $this->assertSame($generatedAt, $dto->getGeneratedAt());
        $this->assertSame(['report_id' => 'test'], $dto->getMetadata());
    }

    public function testFromArray(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame($this->validData['top_performing_content'], $dto->getTopPerformingContent());
        $this->assertSame($this->validData['content_performance_metrics'], $dto->getContentPerformanceMetrics());
        $this->assertSame($this->validData['popular_topics'], $dto->getPopularTopics());
        $this->assertSame($this->validData['content_formats'], $dto->getContentFormats());
        $this->assertSame($this->validData['user_engagement_patterns'], $dto->getUserEngagementPatterns());
        $this->assertSame($this->validData['content_lifecycle_analysis'], $dto->getContentLifecycleAnalysis());
        $this->assertSame($this->validData['reading_patterns'], $dto->getReadingPatterns());
        $this->assertSame($this->validData['shareability'], $dto->getShareability());
        $this->assertSame($this->validData['seasonal_trends'], $dto->getSeasonalTrends());
        $this->assertSame($this->validData['content_optimization'], $dto->getContentOptimization());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getGeneratedAt());
        $this->assertSame($this->validData['metadata'], $dto->getMetadata());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $dto = ContentInsightsDTO::fromArray([]);

        $this->assertSame([], $dto->getTopPerformingContent());
        $this->assertSame([], $dto->getContentPerformanceMetrics());
        $this->assertSame([], $dto->getPopularTopics());
        $this->assertSame([], $dto->getContentFormats());
        $this->assertNull($dto->getGeneratedAt());
        $this->assertSame([], $dto->getMetadata());
    }

    public function testCalculatedPerformanceMetrics(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame(850.5, $dto->getAverageViewsPerContent());
        $this->assertSame(6.5, $dto->getAverageEngagementRate());
        $this->assertSame(320, $dto->getAverageReadTime());
        $this->assertSame(35.2, $dto->getBounceRate());
        $this->assertSame(68.7, $dto->getCompletionRate());
        $this->assertSame(3.8, $dto->getShareRate());
    }

    public function testTopContentAndFormats(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame('programming', $dto->getTopTopic());
        $this->assertSame('article', $dto->getMostPopularFormat());

        $bestContent = $dto->getBestPerformingContent();
        $this->assertNotNull($bestContent);
        $this->assertSame(101, $bestContent['id']);
        $this->assertSame('如何學習程式設計', $bestContent['title']);
        $this->assertSame(1500, $bestContent['metric_value']);
    }

    public function testTopContentWhenEmpty(): void
    {
        $data = $this->validData;
        $data['top_performing_content'] = [];

        $dto = ContentInsightsDTO::fromArray($data);

        $this->assertNull($dto->getBestPerformingContent());
    }

    public function testTopTopicWhenEmpty(): void
    {
        $data = $this->validData;
        $data['popular_topics'] = [];

        $dto = ContentInsightsDTO::fromArray($data);

        $this->assertNull($dto->getTopTopic());
    }

    public function testShareabilityMetrics(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame(4.2, $dto->getAverageSharesPerContent());
        $this->assertSame('infographic', $dto->getMostSharableContentType());
    }

    public function testEngagementTimingMetrics(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame('14:00', $dto->getPeakEngagementHour());
        $this->assertSame('Tuesday', $dto->getPeakEngagementDay());
    }

    public function testContentLifecycleMetrics(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame(45, $dto->getContentLifespanDays());
        $this->assertSame('first_week', $dto->getPeakViewsPeriod());
    }

    public function testReadingPatternMetrics(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $this->assertSame(1200, $dto->getOptimalContentLength());
        $this->assertSame(72.5, $dto->getAverageScrollDepth());
        $this->assertSame(28.3, $dto->getReturnReaderRate());
    }

    public function testPerformanceGrade(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        // 評分計算: (6.5 * 0.4) + (68.7 * 0.4) + (3.8 * 0.2) = 2.6 + 27.48 + 0.76 = 30.84
        $this->assertSame('C', $dto->getPerformanceGrade());
    }

    public function testPerformanceGradeExcellent(): void
    {
        $data = $this->validData;
        $data['content_performance_metrics'] = [
            'avg_views_per_content' => 1000.0,
            'avg_engagement_rate' => 90.0, // 高參與率
            'avg_read_time' => 400,
            'bounce_rate' => 10.0,
            'completion_rate' => 95.0, // 高完成率
            'share_rate' => 15.0, // 高分享率
        ];

        $dto = ContentInsightsDTO::fromArray($data);

        // 評分: (90 * 0.4) + (95 * 0.4) + (15 * 0.2) = 36 + 38 + 3 = 77
        $this->assertSame('A', $dto->getPerformanceGrade());
    }

    public function testContentStrategyRecommendations(): void
    {
        // 創建低效能的內容資料
        $data = $this->validData;
        $data['content_performance_metrics'] = [
            'avg_views_per_content' => 100.0,
            'avg_engagement_rate' => 2.0, // 低參與率
            'avg_read_time' => 120,
            'bounce_rate' => 70.0,
            'completion_rate' => 40.0, // 低完成率
            'share_rate' => 1.0, // 低分享率
        ];

        $dto = ContentInsightsDTO::fromArray($data);
        $recommendations = $dto->getContentStrategyRecommendations();

        $this->assertArrayHasKey('engagement', $recommendations);
        $this->assertArrayHasKey('completion', $recommendations);
        $this->assertArrayHasKey('sharing', $recommendations);
        $this->assertArrayHasKey('topics', $recommendations);

        $engagementRec = $recommendations['engagement'];
        $this->assertIsArray($engagementRec);
        $this->assertSame('high', $engagementRec['priority']);
        $this->assertSame('提升內容互動性', $engagementRec['action']);

        $completionRec = $recommendations['completion'];
        $this->assertIsArray($completionRec);
        $this->assertSame('high', $completionRec['priority']);
        $this->assertSame('優化內容結構', $completionRec['action']);

        $sharingRec = $recommendations['sharing'];
        $this->assertIsArray($sharingRec);
        $this->assertSame('medium', $sharingRec['priority']);
        $this->assertSame('提升內容分享價值', $sharingRec['action']);
    }

    public function testOptimizationInsights(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $insights = $dto->getOptimizationInsights();

        $this->assertArrayHasKey('optimal_publish_time', $insights);
        $this->assertArrayHasKey('content_specifications', $insights);
        $this->assertArrayHasKey('engagement_optimization', $insights);
        $this->assertArrayHasKey('lifecycle_management', $insights);

        $publishTime = $insights['optimal_publish_time'];
        $this->assertIsArray($publishTime);
        $this->assertSame('14:00', $publishTime['hour']);
        $this->assertSame('Tuesday', $publishTime['day']);

        $specs = $insights['content_specifications'];
        $this->assertIsArray($specs);
        $this->assertSame(1200, $specs['optimal_length']);
        $this->assertSame(320, $specs['target_read_time']);
        $this->assertSame('article', $specs['recommended_format']);

        $engagement = $insights['engagement_optimization'];
        $this->assertIsArray($engagement);
        $this->assertSame(8.0, $engagement['target_engagement_rate']); // max(8.0, 6.5 * 1.2)
        $this->assertEqualsWithDelta(75.57, $engagement['target_completion_rate'], 0.01); // 68.7 * 1.1
        $this->assertEqualsWithDelta(4.94, $engagement['target_share_rate'], 0.01); // 3.8 * 1.3
    }

    public function testSeasonalContentStrategy(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $strategy = $dto->getSeasonalContentStrategy();

        $this->assertArrayHasKey('current_season', $strategy);
        $this->assertArrayHasKey('seasonal_performance', $strategy);
        $this->assertArrayHasKey('recommended_topics', $strategy);
        $this->assertArrayHasKey('optimal_formats', $strategy);
        $this->assertArrayHasKey('engagement_patterns', $strategy);
        $this->assertArrayHasKey('content_calendar_suggestions', $strategy);

        $currentSeason = $strategy['current_season'];
        $this->assertContains($currentSeason, ['spring', 'summer', 'autumn', 'winter']);

        $suggestions = $strategy['content_calendar_suggestions'];
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);
    }

    public function testReaderBehaviorAnalysis(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $analysis = $dto->getReaderBehaviorAnalysis();

        $this->assertArrayHasKey('reading_habits', $analysis);
        $this->assertArrayHasKey('engagement_preferences', $analysis);
        $this->assertArrayHasKey('interaction_patterns', $analysis);
        $this->assertArrayHasKey('content_discovery', $analysis);

        $habits = $analysis['reading_habits'];
        $this->assertIsArray($habits);
        $this->assertSame(72.5, $habits['avg_scroll_depth']);
        $this->assertSame(68.7, $habits['completion_rate']);
        $this->assertSame(28.3, $habits['return_rate']);
        $this->assertSame(35.2, $habits['bounce_rate']);

        $preferences = $analysis['engagement_preferences'];
        $this->assertIsArray($preferences);
        $this->assertSame(1200, $preferences['preferred_content_length']);
        $this->assertSame(320, $preferences['optimal_read_time']);
        $this->assertSame('article', $preferences['most_engaging_format']);

        $patterns = $analysis['interaction_patterns'];
        $this->assertIsArray($patterns);
        $this->assertSame('14:00', $patterns['peak_activity_time']);
        $this->assertSame('Tuesday', $patterns['preferred_day']);
        $this->assertArrayHasKey('sharing_behavior', $patterns);
        $sharingBehavior = $patterns['sharing_behavior'];
        $this->assertIsArray($sharingBehavior);
        $this->assertSame(4.2, $sharingBehavior['avg_shares']);
    }

    public function testToArray(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $array = $dto->toArray();

        $this->assertArrayHasKey('top_performing_content', $array);
        $this->assertArrayHasKey('content_performance_metrics', $array);
        $this->assertArrayHasKey('popular_topics', $array);
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayHasKey('strategy_recommendations', $array);
        $this->assertArrayHasKey('optimization_insights', $array);
        $this->assertArrayHasKey('seasonal_content_strategy', $array);
        $this->assertArrayHasKey('reader_behavior_analysis', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('2024-01-15T10:30:00Z', $array['generated_at']);

        $metrics = $array['calculated_metrics'];
        $this->assertIsArray($metrics);
        $this->assertSame(850.5, $metrics['avg_views_per_content']);
        $this->assertSame('C', $metrics['performance_grade']);
    }

    public function testJsonSerialize(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $json = json_encode($dto, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        $this->assertArrayHasKey('top_performing_content', $decoded);
        $this->assertArrayHasKey('calculated_metrics', $decoded);
        $calculatedMetrics = $decoded['calculated_metrics'];
        $this->assertIsArray($calculatedMetrics);
        $this->assertSame(850.5, $calculatedMetrics['avg_views_per_content']);
    }

    public function testHasData(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);
        $this->assertTrue($dto->hasData());

        $emptyDto = ContentInsightsDTO::fromArray([]);
        $this->assertFalse($emptyDto->hasData());

        $partialDto = ContentInsightsDTO::fromArray(['top_performing_content' => [['id' => 1, 'title' => 'test', 'metric_value' => 1]]]);
        $this->assertTrue($partialDto->hasData());

        $metricsOnlyDto = ContentInsightsDTO::fromArray([
            'content_performance_metrics' => [
                'avg_views_per_content' => 100.0,
                'avg_engagement_rate' => 5.0,
                'avg_read_time' => 300,
                'bounce_rate' => 30.0,
                'completion_rate' => 70.0,
                'share_rate' => 2.5,
            ],
        ]);
        $this->assertTrue($metricsOnlyDto->hasData());
    }

    public function testGetSummary(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $summary = $dto->getSummary();

        $this->assertArrayHasKey('performance_grade', $summary);
        $this->assertArrayHasKey('avg_engagement_rate', $summary);
        $this->assertArrayHasKey('completion_rate', $summary);
        $this->assertArrayHasKey('top_topic', $summary);
        $this->assertArrayHasKey('most_popular_format', $summary);
        $this->assertArrayHasKey('optimal_content_length', $summary);

        $this->assertSame('C', $summary['performance_grade']);
        $this->assertSame(6.5, $summary['avg_engagement_rate']);
        $this->assertSame(68.7, $summary['completion_rate']);
        $this->assertSame('programming', $summary['top_topic']);
        $this->assertSame('article', $summary['most_popular_format']);
        $this->assertSame(1200, $summary['optimal_content_length']);
    }

    public function testValidationFailsWithInvalidTopPerformingContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('表現最佳內容資料結構不正確');

        new ContentInsightsDTO(
            topPerformingContent: [['invalid' => 'structure']], // 缺少必要的鍵
            contentPerformanceMetrics: [],
            popularTopics: [],
            contentFormats: [],
            userEngagementPatterns: [],
            contentLifecycleAnalysis: [],
            readingPatterns: [],
            shareability: [],
            seasonalTrends: [],
            contentOptimization: [],
        );
    }

    public function testValidationFailsWithMissingPerformanceMetrics(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('內容效能指標缺少必要的鍵');

        new ContentInsightsDTO(
            topPerformingContent: [],
            contentPerformanceMetrics: ['avg_views_per_content' => 100.0], // 缺少其他必要的鍵
            popularTopics: [],
            contentFormats: [],
            userEngagementPatterns: [],
            contentLifecycleAnalysis: [],
            readingPatterns: [],
            shareability: [],
            seasonalTrends: [],
            contentOptimization: [],
        );
    }

    public function testValidationFailsWithInvalidPopularTopics(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('熱門主題統計資料格式不正確');

        new ContentInsightsDTO(
            topPerformingContent: [],
            contentPerformanceMetrics: [],
            popularTopics: ['tech' => -1], // 負數
            contentFormats: [],
            userEngagementPatterns: [],
            contentLifecycleAnalysis: [],
            readingPatterns: [],
            shareability: [],
            seasonalTrends: [],
            contentOptimization: [],
        );
    }

    public function testValidationFailsWithInvalidContentFormats(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('內容格式統計資料格式不正確');

        new ContentInsightsDTO(
            topPerformingContent: [],
            contentPerformanceMetrics: [],
            popularTopics: [],
            contentFormats: ['article' => -1], // 負數
            userEngagementPatterns: [],
            contentLifecycleAnalysis: [],
            readingPatterns: [],
            shareability: [],
            seasonalTrends: [],
            contentOptimization: [],
        );
    }

    public function testValidationFailsWithInvalidReadingPatterns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('閱讀模式統計鍵必須是字符串');

        /** @var array<string, int|float> $invalidReadingPatterns */
        $invalidReadingPatterns = [123 => 100, 'valid_key' => 72.5]; // 數字鍵會被過濾掉導致鍵不是字符串

        /** @phpstan-ignore-next-line argument.type */
        new ContentInsightsDTO(
            topPerformingContent: [],
            contentPerformanceMetrics: [],
            popularTopics: [],
            contentFormats: [],
            userEngagementPatterns: [],
            contentLifecycleAnalysis: [],
            readingPatterns: $invalidReadingPatterns,
            shareability: [],
            seasonalTrends: [],
            contentOptimization: [],
        );
    }

    public function testSeasonSpecificStrategy(): void
    {
        // 測試不同季節的內容策略建議
        $seasons = ['spring', 'summer', 'autumn', 'winter'];

        foreach ($seasons as $season) {
            // 模擬當前季節（這裡僅做概念驗證，實際實現可能需要 mock）
            $dto = ContentInsightsDTO::fromArray($this->validData);
            $strategy = $dto->getSeasonalContentStrategy();

            $this->assertContains($strategy['current_season'], $seasons);
            $this->assertIsArray($strategy['content_calendar_suggestions']);
        }
    }

    public function testLifespanBasedRefreshRecommendations(): void
    {
        // 測試短生命週期內容
        /** @var array<string, mixed> $data */
        $data = $this->validData;
        if (!isset($data['content_lifecycle_analysis'])) {
            $data['content_lifecycle_analysis'] = [];
        }
        /** @var array<string, mixed> $lifecycleAnalysis */
        $lifecycleAnalysis = $data['content_lifecycle_analysis'];
        $lifecycleAnalysis['avg_lifespan_days'] = 20; // 短生命週期
        $data['content_lifecycle_analysis'] = $lifecycleAnalysis;

        $dto = ContentInsightsDTO::fromArray($data);
        $insights = $dto->getOptimizationInsights();
        $lifecycleManagement = $insights['lifecycle_management'];
        $this->assertIsArray($lifecycleManagement);

        $recommendations = $lifecycleManagement['refresh_recommendations'];
        $this->assertIsArray($recommendations);
        $this->assertContains('每週檢查內容效能', $recommendations);

        // 測試長生命週期內容
        if (!isset($data['content_lifecycle_analysis'])) {
            $data['content_lifecycle_analysis'] = [];
        }
        /** @var array<string, mixed> $lifecycleAnalysis2 */
        $lifecycleAnalysis2 = $data['content_lifecycle_analysis'];
        $lifecycleAnalysis2['avg_lifespan_days'] = 120; // 長生命週期
        $data['content_lifecycle_analysis'] = $lifecycleAnalysis2;

        $dto2 = ContentInsightsDTO::fromArray($data);
        $insights2 = $dto2->getOptimizationInsights();
        $lifecycleManagement2 = $insights2['lifecycle_management'];
        $this->assertIsArray($lifecycleManagement2);

        $recommendations2 = $lifecycleManagement2['refresh_recommendations'];
        $this->assertIsArray($recommendations2);
        $this->assertContains('每季度全面檢視', $recommendations2);
    }
}
