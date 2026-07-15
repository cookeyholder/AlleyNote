<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\ContentInsightsDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

class ContentInsightsDTOTest extends UnitTestCase
{
    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'top_performing_content' => [
                [
                    'id'           => 101,
                    'title'        => '如何學習程式設計',
                    'metric_value' => 1500,
                    'type'         => 'views',
                ],
                [
                    'id'           => 102,
                    'title'        => 'PHP 最佳實踐',
                    'metric_value' => 1200,
                    'type'         => 'views',
                ],
            ],
            'content_performance_metrics' => [
                'avg_views_per_content' => 850.5,
                'avg_engagement_rate'   => 6.5,
                'avg_read_time'         => 320,
                'bounce_rate'           => 35.2,
                'completion_rate'       => 68.7,
                'share_rate'            => 3.8,
            ],
            'popular_topics' => [
                'programming'     => 450,
                'web_development' => 380,
                'database'        => 280,
                'api_design'      => 200,
            ],
            'content_formats' => [
                'article'     => 650,
                'tutorial'    => 420,
                'video'       => 300,
                'infographic' => 150,
            ],
            'user_engagement_patterns' => [
                'peak_hour'            => '14:00',
                'peak_day'             => 'Tuesday',
                'avg_session_duration' => 480,
                'discovery_patterns'   => [
                    'search'   => 45.2,
                    'direct'   => 28.7,
                    'social'   => 16.1,
                    'referral' => 10.0,
                ],
            ],
            'content_lifecycle_analysis' => [
                'avg_lifespan_days' => 45,
                'peak_views_period' => 'first_week',
                'decay_rate'        => 15.5,
            ],
            'reading_patterns' => [
                'optimal_length_words' => 1200,
                'avg_scroll_depth'     => 72.5,
                'return_reader_rate'   => 28.3,
                'reading_speed_wpm'    => 250,
            ],
            'shareability' => [
                'avg_shares_per_content' => 4.2,
                'most_sharable_type'     => 'infographic',
                'share_platforms'        => [
                    'Facebook' => 35.5,
                    'Twitter'  => 28.2,
                    'LinkedIn' => 22.1,
                    'Others'   => 14.2,
                ],
            ],
            'seasonal_trends' => [
                'spring' => [
                    'trending_topics'     => ['新年目標', '學習計劃'],
                    'popular_formats'     => ['article', 'tutorial'],
                    'engagement_patterns' => ['higher_morning_activity'],
                ],
                'summer' => [
                    'trending_topics'     => ['度假技巧', '輕鬆閱讀'],
                    'popular_formats'     => ['infographic', 'video'],
                    'engagement_patterns' => ['lower_overall_engagement'],
                ],
            ],
            'content_optimization' => [
                'title_optimization' => [
                    'optimal_length'           => 60,
                    'high_performing_keywords' => ['完整指南', '最佳實踐', '深入解析'],
                ],
                'seo_recommendations' => [
                    'use_long_tail_keywords',
                    'improve_meta_descriptions',
                    'add_internal_links',
                ],
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
            'metadata'     => [
                'report_id'       => 'content_insights_001',
                'version'         => '1.0',
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

    public function testToArray(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $array = $dto->toArray();

        $this->assertArrayHasKey('top_performing_content', $array);
        $this->assertArrayHasKey('content_performance_metrics', $array);
        $this->assertArrayHasKey('popular_topics', $array);
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayNotHasKey('strategy_recommendations', $array);
        $this->assertArrayNotHasKey('optimization_insights', $array);
        $this->assertArrayNotHasKey('seasonal_content_strategy', $array);
        $this->assertArrayNotHasKey('reader_behavior_analysis', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('2024-01-15T10:30:00Z', $array['generated_at']);

        $metrics = $array['calculated_metrics'];
        $this->assertIsArray($metrics);
        $this->assertSame(850.5, $metrics['avg_views_per_content']);
        $this->assertArrayNotHasKey('performance_grade', $metrics);
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
                'avg_engagement_rate'   => 5.0,
                'avg_read_time'         => 300,
                'bounce_rate'           => 30.0,
                'completion_rate'       => 70.0,
                'share_rate'            => 2.5,
            ],
        ]);
        $this->assertTrue($metricsOnlyDto->hasData());
    }

    public function testGetSummary(): void
    {
        $dto = ContentInsightsDTO::fromArray($this->validData);

        $summary = $dto->getSummary();

        $this->assertArrayHasKey('avg_engagement_rate', $summary);
        $this->assertArrayHasKey('completion_rate', $summary);
        $this->assertArrayHasKey('top_topic', $summary);
        $this->assertArrayHasKey('most_popular_format', $summary);
        $this->assertArrayHasKey('optimal_content_length', $summary);
        $this->assertArrayNotHasKey('performance_grade', $summary);

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
            topPerformingContent: [['invalid' => 'structure']],
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
            contentPerformanceMetrics: ['avg_views_per_content' => 100.0],
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
            popularTopics: ['tech' => -1],
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
            contentFormats: ['article' => -1],
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
        $invalidReadingPatterns = [123 => 100, 'valid_key' => 72.5];

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
}
