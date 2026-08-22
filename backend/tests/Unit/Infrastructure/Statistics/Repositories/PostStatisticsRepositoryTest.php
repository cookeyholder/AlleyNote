<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 文章統計儲存庫單元測試.
 */
final class PostStatisticsRepositoryTest extends UnitTestCase
{
    private PDO $pdo;

    private PostStatisticsRepository $repository;

    private StatisticsPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT,
                views INTEGER DEFAULT 0,
                comments_count INTEGER DEFAULT 0,
                likes_count INTEGER DEFAULT 0,
                status TEXT DEFAULT '1',
                creation_source TEXT DEFAULT 'web',
                user_id INTEGER DEFAULT 1,
                is_pinned INTEGER DEFAULT 0,
                created_at DATETIME NOT NULL
            );

            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date DATETIME NOT NULL
            );
        ");

        $this->repository = new PostStatisticsRepository($this->pdo);

        $this->period = new StatisticsPeriod(
            type: PeriodType::MONTHLY,
            startTime: new DateTimeImmutable('2025-01-01 00:00:00'),
            endTime: new DateTimeImmutable('2025-01-31 23:59:59'),
        );

        $this->seedDatabase();
    }

    private function seedDatabase(): void
    {
        $posts = [
            [
                'title'           => 'Post 1',
                'content'         => 'Short content',
                'views'           => 100,
                'comments_count'  => 5,
                'likes_count'     => 10,
                'status'          => '1', // published
                'creation_source' => 'web',
                'user_id'         => 1,
                'is_pinned'       => 1,
                'created_at'      => '2025-01-05 10:00:00',
            ],
            [
                'title'           => 'Post 2',
                'content'         => 'Medium length content that is longer',
                'views'           => 200,
                'comments_count'  => 20,
                'likes_count'     => 50,
                'status'          => '0', // draft
                'creation_source' => 'api',
                'user_id'         => 1,
                'is_pinned'       => 0,
                'created_at'      => '2025-01-10 14:00:00',
            ],
            [
                'title'           => 'Post 3',
                'content'         => 'Very very long content with many characters for testing length ranges in repository',
                'views'           => 0,
                'comments_count'  => 0,
                'likes_count'     => 0,
                'status'          => '1', // published
                'creation_source' => 'mobile',
                'user_id'         => 2,
                'is_pinned'       => 0,
                'created_at'      => '2025-01-15 20:00:00',
            ],
        ];

        $stmt = $this->pdo->prepare('
            INSERT INTO posts (title, content, views, comments_count, likes_count, status, creation_source, user_id, is_pinned, created_at)
            VALUES (:title, :content, :views, :comments_count, :likes_count, :status, :creation_source, :user_id, :is_pinned, :created_at)
        ');

        foreach ($posts as $p) {
            $stmt->execute($p);
        }

        $views = [
            ['post_id' => 1, 'user_id' => 1, 'user_ip' => '1.1.1.1', 'view_date' => '2025-01-05 10:05:00'],
            ['post_id' => 1, 'user_id' => null, 'user_ip' => '1.1.1.2', 'view_date' => '2025-01-05 11:00:00'],
            ['post_id' => 2, 'user_id' => null, 'user_ip' => '1.1.1.1', 'view_date' => '2025-01-10 15:00:00'],
        ];

        $stmtView = $this->pdo->prepare('
            INSERT INTO post_views (post_id, user_id, user_ip, view_date)
            VALUES (:post_id, :user_id, :user_ip, :view_date)
        ');

        foreach ($views as $v) {
            $stmtView->execute($v);
        }
    }

    public function testGetTotalPostsCount(): void
    {
        $all = $this->repository->getTotalPostsCount($this->period);
        $this->assertSame(3, $all);

        $published = $this->repository->getTotalPostsCount($this->period, '1');
        $this->assertSame(2, $published);

        $draft = $this->repository->getTotalPostsCount($this->period, '0');
        $this->assertSame(1, $draft);
    }

    public function testGetPostsCountByStatus(): void
    {
        $statusCounts = $this->repository->getPostsCountByStatus($this->period);
        $this->assertSame(2, $statusCounts['published']);
        $this->assertSame(1, $statusCounts['draft']);
        $this->assertSame(0, $statusCounts['archived']);
    }

    public function testGetPostsCountBySource(): void
    {
        $sourceCounts = $this->repository->getPostsCountBySource($this->period);
        $this->assertSame(1, $sourceCounts['web']);
        $this->assertSame(1, $sourceCounts['api']);
        $this->assertSame(1, $sourceCounts['mobile']);
    }

    public function testGetPostsCountBySourceType(): void
    {
        $webType = SourceType::createWeb();
        $webCount = $this->repository->getPostsCountBySourceType($this->period, $webType);
        $this->assertSame(1, $webCount);

        $webPublished = $this->repository->getPostsCountBySourceType($this->period, $webType, '1');
        $this->assertSame(1, $webPublished);

        $webDraft = $this->repository->getPostsCountBySourceType($this->period, $webType, '0');
        $this->assertSame(0, $webDraft);
    }

    public function testGetPostViewsStatistics(): void
    {
        $stats = $this->repository->getPostViewsStatistics($this->period);
        $this->assertSame(300, $stats['total_views']);
        $this->assertSame(2, $stats['unique_views']); // 2 篇文章有瀏覽量
        $this->assertEquals(100.0, $stats['avg_views_per_post']);
    }

    public function testGetPopularPosts(): void
    {
        // 依 views 排序
        $popular = $this->repository->getPopularPosts($this->period, 5, 'views');
        $this->assertCount(2, $popular); // 只有 published 狀態
        $this->assertSame('Post 1', $popular[0]['title']);
        $this->assertSame(100, $popular[0]['metric_value']);

        // 依 comments 排序
        $popularComments = $this->repository->getPopularPosts($this->period, 5, 'comments');
        $this->assertCount(2, $popularComments);

        // 依 likes 排序
        $popularLikes = $this->repository->getPopularPosts($this->period, 5, 'likes');
        $this->assertCount(2, $popularLikes);
    }

    public function testGetPopularPostsValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPopularPosts($this->period, 0);
    }

    public function testGetPopularPostsInvalidMetricValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPopularPosts($this->period, 5, 'invalid');
    }

    public function testGetPostsCountByUser(): void
    {
        $byUser = $this->repository->getPostsCountByUser($this->period, 10);
        $this->assertCount(2, $byUser);
        $this->assertSame(1, $byUser[0]['user_id']);
        $this->assertSame(2, $byUser[0]['posts_count']);
        $this->assertSame(300, $byUser[0]['total_views']);
    }

    public function testGetPostsCountByUserValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPostsCountByUser($this->period, 0);
    }

    public function testGetPostsPublishTimeDistribution(): void
    {
        $distDay = $this->repository->getPostsPublishTimeDistribution($this->period, 'day');
        $this->assertArrayHasKey('2025-01-05', $distDay);
        $this->assertSame(1, $distDay['2025-01-05']);

        $distHour = $this->repository->getPostsPublishTimeDistribution($this->period, 'hour');
        $this->assertArrayHasKey('10:00', $distHour);

        $distWeek = $this->repository->getPostsPublishTimeDistribution($this->period, 'week');
        $this->assertNotEmpty($distWeek);

        $distMonth = $this->repository->getPostsPublishTimeDistribution($this->period, 'month');
        $this->assertArrayHasKey('2025-01', $distMonth);
    }

    public function testGetPostsPublishTimeDistributionInvalidGroupBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPostsPublishTimeDistribution($this->period, 'year');
    }

    public function testGetPostsGrowthTrend(): void
    {
        $prevPeriod = new StatisticsPeriod(
            type: PeriodType::MONTHLY,
            startTime: new DateTimeImmutable('2024-12-01 00:00:00'),
            endTime: new DateTimeImmutable('2024-12-31 23:59:59'),
        );

        $trend = $this->repository->getPostsGrowthTrend($this->period, $prevPeriod);
        $this->assertSame(3, $trend['current']);
        $this->assertSame(0, $trend['previous']);
        $this->assertEquals(0.0, $trend['growth_rate']);
        $this->assertSame(3, $trend['growth_count']);
    }

    public function testGetPostsLengthStatistics(): void
    {
        $lengthStats = $this->repository->getPostsLengthStatistics($this->period);
        $this->assertGreaterThan(0, $lengthStats['avg_length']);
        $this->assertGreaterThan(0, $lengthStats['min_length']);
        $this->assertGreaterThan(0, $lengthStats['max_length']);
        $this->assertGreaterThan(0, $lengthStats['total_chars']);
    }

    public function testGetPostsCountByLengthRange(): void
    {
        $ranges = [
            'short'  => ['min' => 0, 'max' => 20],
            'medium' => ['min' => 21, 'max' => 50],
            'long'   => ['min' => 51, 'max' => 200],
        ];

        $results = $this->repository->getPostsCountByLengthRange($this->period, $ranges);
        $this->assertArrayHasKey('short', $results);
        $this->assertArrayHasKey('medium', $results);
        $this->assertArrayHasKey('long', $results);

        $this->assertSame(1, $results['short']['count']);
        $this->assertSame(1, $results['medium']['count']);
        $this->assertSame(1, $results['long']['count']);
    }

    public function testGetPostsCountByLengthRangeValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPostsCountByLengthRange($this->period, []);
    }

    public function testGetPinnedPostsStatistics(): void
    {
        $pinned = $this->repository->getPinnedPostsStatistics($this->period);
        $this->assertSame(1, $pinned['pinned_count']);
        $this->assertSame(2, $pinned['unpinned_count']);
        $this->assertSame(100, $pinned['pinned_views']);
    }

    public function testHasDataForPeriod(): void
    {
        $this->assertTrue($this->repository->hasDataForPeriod($this->period));

        $emptyPeriod = new StatisticsPeriod(
            type: PeriodType::MONTHLY,
            startTime: new DateTimeImmutable('2020-01-01 00:00:00'),
            endTime: new DateTimeImmutable('2020-01-31 23:59:59'),
        );
        $this->assertFalse($this->repository->hasDataForPeriod($emptyPeriod));
    }

    public function testGetPostActivitySummary(): void
    {
        $summary = $this->repository->getPostActivitySummary($this->period);
        $this->assertSame(3, $summary['total_posts']);
        $this->assertSame(2, $summary['published_posts']);
        $this->assertSame(1, $summary['draft_posts']);
        $this->assertSame(300, $summary['total_views']);
        $this->assertSame(2, $summary['active_authors']);
        $this->assertArrayHasKey('popular_sources', $summary);
    }

    public function testGetViewTimeSeriesData(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-31');

        $daily = $this->repository->getViewTimeSeriesData($start, $end, 'day');
        $this->assertNotEmpty($daily);

        $hourly = $this->repository->getViewTimeSeriesData($start, $end, 'hour');
        $this->assertNotEmpty($hourly);

        $weekly = $this->repository->getViewTimeSeriesData($start, $end, 'week');
        $this->assertNotEmpty($weekly);

        $monthly = $this->repository->getViewTimeSeriesData($start, $end, 'month');
        $this->assertNotEmpty($monthly);
    }
}
