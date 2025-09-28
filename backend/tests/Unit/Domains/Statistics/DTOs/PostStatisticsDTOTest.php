<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\PostStatisticsDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PostStatisticsDTOTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'total_posts' => 250,
            'by_status' => [
                'published' => 200,
                'draft' => 30,
                'pending' => 15,
                'archived' => 5,
            ],
            'by_source' => [
                'web' => 180,
                'mobile' => 50,
                'api' => 20,
            ],
            'views_statistics' => [
                'total_views' => 25000,
                'avg_views_per_post' => 125.5,
                'most_viewed_post' => 1500,
            ],
            'top_posts' => [
                [
                    'id' => 101,
                    'title' => 'Laravel 最佳實踐',
                    'views' => 1500,
                ],
                [
                    'id' => 102,
                    'title' => 'PHP 8.4 新功能',
                    'views' => 1200,
                ],
            ],
            'length_statistics' => [
                'avg_length' => 1200,
                'min_length' => 300,
                'max_length' => 3500,
            ],
            'time_distribution' => [
                '09:00' => 30,
                '14:00' => 60,
                '19:00' => 45,
                '22:00' => 25,
            ],
            'top_authors' => [
                [
                    'author_id' => 1,
                    'name' => 'John Doe',
                    'posts_count' => 45,
                ],
                [
                    'author_id' => 2,
                    'name' => 'Jane Smith',
                    'posts_count' => 38,
                ],
            ],
            'pinned_stats' => [
                'total_pinned' => 12,
                'pinned_views' => 5500,
                'avg_pinned_engagement' => 8.5,
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
            'metadata' => [
                'report_id' => 'post_stats_001',
                'version' => '1.0',
            ],
        ];
    }

    public function testConstructionWithValidData(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');

        $dto = new PostStatisticsDTO(
            totalPosts: 250,
            byStatus: ['published' => 200, 'draft' => 30],
            bySource: ['web' => 180, 'mobile' => 50],
            viewsStatistics: ['total_views' => 25000],
            topPosts: [['id' => 101, 'title' => 'Test', 'views' => 1500]],
            lengthStatistics: ['avg_length' => 1200],
            timeDistribution: ['14:00' => 60],
            topAuthors: [['author_id' => 1, 'name' => 'John', 'posts_count' => 45]],
            pinnedStats: ['total_pinned' => 12],
            generatedAt: $generatedAt,
            metadata: ['report_id' => 'test'],
        );

        $this->assertSame(250, $dto->getTotalPosts());
        $this->assertSame(['published' => 200, 'draft' => 30], $dto->getByStatus());
        $this->assertSame($generatedAt, $dto->getGeneratedAt());
        $this->assertSame(['report_id' => 'test'], $dto->getMetadata());
    }

    public function testFromArray(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame(250, $dto->getTotalPosts());
        $this->assertSame($this->validData['by_status'], $dto->getByStatus());
        $this->assertSame($this->validData['by_source'], $dto->getBySource());
        $this->assertSame($this->validData['views_statistics'], $dto->getViewsStatistics());
        $this->assertSame($this->validData['top_posts'], $dto->getTopPosts());
        $this->assertSame($this->validData['length_statistics'], $dto->getLengthStatistics());
        $this->assertSame($this->validData['time_distribution'], $dto->getTimeDistribution());
        $this->assertSame($this->validData['top_authors'], $dto->getTopAuthors());
        $this->assertSame($this->validData['pinned_stats'], $dto->getPinnedStats());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getGeneratedAt());
        $this->assertSame($this->validData['metadata'], $dto->getMetadata());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $dto = PostStatisticsDTO::fromArray([]);

        $this->assertSame(0, $dto->getTotalPosts());
        $this->assertSame([], $dto->getByStatus());
        $this->assertSame([], $dto->getBySource());
        $this->assertSame([], $dto->getViewsStatistics());
        $this->assertSame([], $dto->getTopPosts());
        $this->assertNull($dto->getGeneratedAt());
        $this->assertSame([], $dto->getMetadata());
    }

    public function testCalculatedStatusMetrics(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame(200, $dto->getPublishedPosts());
        $this->assertSame(30, $dto->getDraftPosts());
        $this->assertSame(15, $dto->getPendingPosts());
        $this->assertSame(5, $dto->getArchivedPosts());
    }

    public function testCalculatedViewsMetrics(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame(25000, $dto->getTotalViews());
        $this->assertSame(125.5, $dto->getAverageViewsPerPost());
        $this->assertSame(1500, $dto->getMostViewedPostViews());
    }

    public function testTopPost(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $topPost = $dto->getTopPost();
        $this->assertNotNull($topPost);
        $this->assertSame(101, $topPost['id']);
        $this->assertSame('Laravel 最佳實踐', $topPost['title']);
        $this->assertSame(1500, $topPost['views']);
    }

    public function testTopPostWhenEmpty(): void
    {
        $data = $this->validData;
        $data['top_posts'] = [];

        $dto = PostStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getTopPost());
    }

    public function testLengthStatistics(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame(1200, $dto->getAverageLength());
        $this->assertSame(300, $dto->getMinLength());
        $this->assertSame(3500, $dto->getMaxLength());
    }

    public function testTopAuthor(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $topAuthor = $dto->getTopAuthor();
        $this->assertNotNull($topAuthor);
        $this->assertSame(1, $topAuthor['author_id']);
        $this->assertSame('John Doe', $topAuthor['name']);
        $this->assertSame(45, $topAuthor['posts_count']);
    }

    public function testTopAuthorWhenEmpty(): void
    {
        $data = $this->validData;
        $data['top_authors'] = [];

        $dto = PostStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getTopAuthor());
    }

    public function testPinnedStatistics(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame(12, $dto->getTotalPinnedPosts());
        $this->assertSame(5500, $dto->getPinnedPostsViews());
        $this->assertSame(8.5, $dto->getAveragePinnedEngagement());
    }

    public function testPublishedPercentage(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        // 80% = 200 / 250 * 100
        $this->assertSame(80.0, $dto->getPublishedPercentage());
    }

    public function testPublishedPercentageWithZeroTotal(): void
    {
        $data = $this->validData;
        $data['total_posts'] = 0;

        $dto = PostStatisticsDTO::fromArray($data);

        $this->assertSame(0.0, $dto->getPublishedPercentage());
    }

    public function testMostActiveHour(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $this->assertSame('14:00', $dto->getMostActiveHour());
    }

    public function testMostActiveHourWhenEmpty(): void
    {
        $data = $this->validData;
        $data['time_distribution'] = [];

        $dto = PostStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getMostActiveHour());
    }

    public function testEngagementMetrics(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $metrics = $dto->getEngagementMetrics();

        $this->assertArrayHasKey('views_per_post_ratio', $metrics);
        $this->assertArrayHasKey('pinned_engagement_rate', $metrics);
        $this->assertArrayHasKey('author_productivity', $metrics);

        $this->assertSame(100.0, $metrics['views_per_post_ratio']); // 25000 / 250
        $this->assertSame(22.0, $metrics['pinned_engagement_rate']); // 5500 / 25000 * 100
        $this->assertEqualsWithDelta(41.5, $metrics['author_productivity'], 0.1); // (45 + 38) / 2
    }

    public function testContentAnalysis(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $analysis = $dto->getContentAnalysis();

        $this->assertArrayHasKey('length_distribution', $analysis);
        $this->assertArrayHasKey('optimal_length_score', $analysis);
        $this->assertArrayHasKey('content_diversity', $analysis);

        $lengthDistribution = $analysis['length_distribution'];
        $this->assertIsArray($lengthDistribution);
        $this->assertSame(1200, $lengthDistribution['average']);
        $this->assertSame(300, $lengthDistribution['minimum']);
        $this->assertSame(3500, $lengthDistribution['maximum']);
    }

    public function testToArray(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $array = $dto->toArray();

        $this->assertArrayHasKey('total_posts', $array);
        $this->assertArrayHasKey('by_status', $array);
        $this->assertArrayHasKey('views_statistics', $array);
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayHasKey('engagement_metrics', $array);
        $this->assertArrayHasKey('content_analysis', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame(250, $array['total_posts']);
        $this->assertSame('2024-01-15T10:30:00Z', $array['generated_at']);
    }

    public function testJsonSerialize(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $json = json_encode($dto, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        $this->assertArrayHasKey('total_posts', $decoded);
        $this->assertArrayHasKey('calculated_metrics', $decoded);
        $this->assertSame(250, $decoded['total_posts']);
    }

    public function testHasData(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);
        $this->assertTrue($dto->hasData());

        $emptyDto = PostStatisticsDTO::fromArray([]);
        $this->assertFalse($emptyDto->hasData());

        $partialDto = PostStatisticsDTO::fromArray(['total_posts' => 1]);
        $this->assertTrue($partialDto->hasData());
    }

    public function testGetSummary(): void
    {
        $dto = PostStatisticsDTO::fromArray($this->validData);

        $summary = $dto->getSummary();

        $this->assertArrayHasKey('total_posts', $summary);
        $this->assertArrayHasKey('published_posts', $summary);
        $this->assertArrayHasKey('total_views', $summary);
        $this->assertArrayHasKey('avg_views_per_post', $summary);
        $this->assertArrayHasKey('top_author', $summary);
        $this->assertArrayHasKey('most_active_hour', $summary);

        $this->assertSame(250, $summary['total_posts']);
        $this->assertSame(200, $summary['published_posts']);
        $this->assertSame(25000, $summary['total_views']);
        $this->assertSame(125.5, $summary['avg_views_per_post']);
        $this->assertSame('John Doe', $summary['top_author']);
        $this->assertSame('14:00', $summary['most_active_hour']);
    }

    public function testValidationFailsWithNegativeTotalPosts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章總數不能為負數');

        new PostStatisticsDTO(
            totalPosts: -1,
            byStatus: [],
            bySource: [],
            viewsStatistics: [],
            topPosts: [],
            lengthStatistics: [],
            timeDistribution: [],
            topAuthors: [],
            pinnedStats: [],
        );
    }

    public function testValidationFailsWithInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('狀態統計資料格式不正確');

        new PostStatisticsDTO(
            totalPosts: 100,
            byStatus: ['published' => -1], // 負數
            bySource: [],
            viewsStatistics: [],
            topPosts: [],
            lengthStatistics: [],
            timeDistribution: [],
            topAuthors: [],
            pinnedStats: [],
        );
    }

    public function testValidationFailsWithInvalidSource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('來源統計資料格式不正確');

        new PostStatisticsDTO(
            totalPosts: 100,
            byStatus: [],
            bySource: ['web' => -1], // 負數
            viewsStatistics: [],
            topPosts: [],
            lengthStatistics: [],
            timeDistribution: [],
            topAuthors: [],
            pinnedStats: [],
        );
    }

    public function testValidationFailsWithInvalidTopPosts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('熱門文章資料結構不正確');

        new PostStatisticsDTO(
            totalPosts: 100,
            byStatus: [],
            bySource: [],
            viewsStatistics: [],
            topPosts: [['invalid' => 'structure']], // 缺少必要的鍵
            lengthStatistics: [],
            timeDistribution: [],
            topAuthors: [],
            pinnedStats: [],
        );
    }

    public function testValidationFailsWithInvalidTimeDistribution(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('時間分布統計資料格式不正確');

        new PostStatisticsDTO(
            totalPosts: 100,
            byStatus: [],
            bySource: [],
            viewsStatistics: [],
            topPosts: [],
            lengthStatistics: [],
            timeDistribution: ['14:00' => -1], // 負數
            topAuthors: [],
            pinnedStats: [],
        );
    }

    public function testValidationFailsWithInvalidTopAuthors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('熱門作者資料結構不正確');

        new PostStatisticsDTO(
            totalPosts: 100,
            byStatus: [],
            bySource: [],
            viewsStatistics: [],
            topPosts: [],
            lengthStatistics: [],
            timeDistribution: [],
            topAuthors: [['invalid' => 'structure']], // 缺少必要的鍵
            pinnedStats: [],
        );
    }

    public function testFromArrayWithInvalidTypes(): void
    {
        // 測試 fromArray 方法對無效型別的處理
        $data = [
            'by_status' => [
                'published' => 'invalid', // 非整數
                123 => 50, // 非字符串鍵
            ],
            'by_source' => [
                'web' => 'invalid', // 非整數
                456 => 30, // 非字符串鍵
            ],
            'time_distribution' => [
                '14:00' => 'invalid', // 非整數
                789 => 25, // 非字符串鍵
            ],
        ];

        $dto = PostStatisticsDTO::fromArray($data);

        // 應該過濾掉無效的項目
        $this->assertSame([], $dto->getByStatus());
        $this->assertSame([], $dto->getBySource());
        $this->assertSame([], $dto->getTimeDistribution());
    }
}
