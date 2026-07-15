<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\PostStatisticsAnalyzer;
use App\Domains\Statistics\DTOs\PostStatisticsDTO;
use Tests\Support\UnitTestCase;

/**
 * PostStatisticsAnalyzer 單元測試.
 */
class PostStatisticsAnalyzerTest extends UnitTestCase
{
    private PostStatisticsAnalyzer $analyzer;

    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->analyzer = new PostStatisticsAnalyzer();
        $this->validData = [
            'total_posts' => 250,
            'by_status' => ['published' => 200, 'draft' => 30, 'pending' => 15, 'archived' => 5],
            'by_source' => ['web' => 180, 'mobile' => 50, 'api' => 20],
            'views_statistics' => ['total_views' => 25000, 'avg_views_per_post' => 125.5, 'most_viewed_post' => 1500],
            'top_posts' => [
                ['id' => 101, 'title' => 'Laravel 最佳實踐', 'views' => 1500],
                ['id' => 102, 'title' => 'PHP 8.4 新功能', 'views' => 1200],
            ],
            'length_statistics' => ['avg_length' => 1200, 'min_length' => 300, 'max_length' => 3500],
            'time_distribution' => ['09:00' => 30, '14:00' => 60, '19:00' => 45, '22:00' => 25],
            'top_authors' => [
                ['author_id' => 1, 'name' => 'John Doe', 'posts_count' => 45],
                ['author_id' => 2, 'name' => 'Jane Smith', 'posts_count' => 38],
            ],
            'pinned_stats' => ['total_pinned' => 12, 'pinned_views' => 5500, 'avg_pinned_engagement' => 8.5],
            'generated_at' => '2024-01-15T10:30:00Z',
        ];
    }

    private function createDTO(): PostStatisticsDTO
    {
        return PostStatisticsDTO::fromArray($this->validData);
    }

    public function testContentQualityMetrics(): void
    {
        $dto = $this->createDTO();
        $metrics = $this->analyzer->getContentQualityMetrics($dto);

        $this->assertArrayHasKey('average_length', $metrics);
        $this->assertArrayHasKey('quality_score', $metrics);
        $this->assertArrayHasKey('engagement_ratio', $metrics);
        $this->assertArrayHasKey('publish_rate', $metrics);

        $this->assertSame(1200.0, $metrics['average_length']);
        $this->assertSame(80.0, $metrics['publish_rate']);
        $this->assertSame(100.0, $metrics['engagement_ratio']);
    }

    public function testEngagementMetrics(): void
    {
        $dto = $this->createDTO();
        $metrics = $this->analyzer->getEngagementMetrics($dto);

        $this->assertArrayHasKey('views_per_post_ratio', $metrics);
        $this->assertArrayHasKey('pinned_engagement_rate', $metrics);
        $this->assertArrayHasKey('author_productivity', $metrics);

        $this->assertSame(100.0, $metrics['views_per_post_ratio']);
        $this->assertSame(22.0, $metrics['pinned_engagement_rate']);
    }

    public function testContentAnalysis(): void
    {
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getContentAnalysis($dto);

        $this->assertArrayHasKey('length_distribution', $analysis);
        $this->assertArrayHasKey('optimal_length_score', $analysis);
        $this->assertArrayHasKey('content_diversity', $analysis);

        $this->assertSame(1200, $analysis['length_distribution']['average']);
        $this->assertSame(300, $analysis['length_distribution']['minimum']);
        $this->assertSame(3500, $analysis['length_distribution']['maximum']);
    }

    public function testAnalyzeReturnsResult(): void
    {
        $dto = $this->createDTO();
        $result = $this->analyzer->analyze($dto);

        $this->assertInstanceOf(\App\Domains\Statistics\Analyzers\PostStatisticsResult::class, $result);
        $this->assertArrayHasKey('engagement_metrics', $result->toArray());
        $this->assertArrayHasKey('content_analysis', $result->toArray());
        $this->assertArrayHasKey('content_quality', $result->toArray());
    }
}
