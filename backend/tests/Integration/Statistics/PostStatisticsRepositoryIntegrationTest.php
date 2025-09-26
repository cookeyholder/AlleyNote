<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

/**
 * PostStatisticsRepository 整合測試.
 *
 * 測試文章統計 Repository 的資料庫互動和複雜查詢功能。
 * 專注於文章相關的統計數據收集和聚合查詢。
 */
#[Group('statistics')]
#[Group('repository')]
#[Group('integration')]
#[Group('posts')]
final class PostStatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private PostStatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostStatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new \Tests\Support\Statistics\StatisticsTestSeeder($this->db);
        $seeder->seedAll();
    }

    public function testGetTotalPostsCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $count = $this->repository->getTotalPostsCount($period);

        // 驗證取得的文章數量（基於測試資料）
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetTotalPostsCountWithStatus(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $publishedCount = $this->repository->getTotalPostsCount($period, 'published');
        $draftCount = $this->repository->getTotalPostsCount($period, 'draft');

        // 驗證按狀態統計的文章數量
        $this->assertIsInt($publishedCount);
        $this->assertIsInt($draftCount);
        $this->assertGreaterThanOrEqual(0, $publishedCount);
        $this->assertGreaterThanOrEqual(0, $draftCount);
    }

    public function testGetPostsCountByStatus(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $statusCounts = $this->repository->getPostsCountByStatus($period);

        // 驗證狀態統計結果
        $this->assertIsArray($statusCounts);

        foreach ($statusCounts as $status => $count) {
            $this->assertIsString($status);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetPostsCountBySource(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $sourceCounts = $this->repository->getPostsCountBySource($period);

        // 驗證來源統計結果
        $this->assertIsArray($sourceCounts);

        foreach ($sourceCounts as $source => $count) {
            $this->assertIsString($source);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetPostsCountBySourceType(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $webCount = $this->repository->getPostsCountBySourceType(
            $period,
            SourceType::fromCode('web')
        );

        // 驗證特定來源統計結果
        $this->assertIsInt($webCount);
        $this->assertGreaterThanOrEqual(0, $webCount);
    }

    public function testGetPostViewsStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $viewsStats = $this->repository->getPostViewsStatistics($period);

        // 驗證瀏覽統計結果
        $this->assertIsArray($viewsStats);
        $this->assertArrayHasKey('total_views', $viewsStats);
        $this->assertArrayHasKey('unique_views', $viewsStats);
        $this->assertArrayHasKey('avg_views_per_post', $viewsStats);

        $this->assertIsInt($viewsStats['total_views']);
        $this->assertIsInt($viewsStats['unique_views']);
        $this->assertIsFloat($viewsStats['avg_views_per_post']);
        $this->assertGreaterThanOrEqual(0, $viewsStats['total_views']);
        $this->assertGreaterThanOrEqual(0, $viewsStats['unique_views']);
        $this->assertGreaterThanOrEqual(0, $viewsStats['avg_views_per_post']);
    }

    public function testGetPopularPosts(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );
        $limit = 5;

        $popularPosts = $this->repository->getPopularPosts($period, $limit, 'views');

        // 驗證熱門文章結果
        $this->assertIsArray($popularPosts);
        $this->assertLessThanOrEqual($limit, count($popularPosts));

        $previousViews = PHP_INT_MAX;
        foreach ($popularPosts as $post) {
            $this->assertIsArray($post);
            $this->assertArrayHasKey('post_id', $post);
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayHasKey('metric_value', $post);

            $this->assertIsInt($post['post_id']);
            $this->assertIsString($post['title']);
            $this->assertIsInt($post['metric_value']);

            // 驗證排序正確性（瀏覽數遞減）
            $this->assertLessThanOrEqual($previousViews, $post['metric_value']);
            $previousViews = $post['metric_value'];
        }
    }

    public function testGetPostsCountByUser(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );
        $limit = 10;

        $userStats = $this->repository->getPostsCountByUser($period, $limit);

        // 驗證使用者文章統計結果
        $this->assertIsArray($userStats);
        $this->assertLessThanOrEqual($limit, count($userStats));

        foreach ($userStats as $userStat) {
            $this->assertIsArray($userStat);
            $this->assertArrayHasKey('user_id', $userStat);
            $this->assertArrayHasKey('posts_count', $userStat);
            $this->assertArrayHasKey('total_views', $userStat);

            $this->assertIsInt($userStat['user_id']);
            $this->assertIsInt($userStat['posts_count']);
            $this->assertIsInt($userStat['total_views']);
            $this->assertGreaterThanOrEqual(0, $userStat['posts_count']);
            $this->assertGreaterThanOrEqual(0, $userStat['total_views']);
        }
    }

    public function testGetPostsPublishTimeDistribution(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $timeDistribution = $this->repository->getPostsPublishTimeDistribution($period, 'hour');

        // 驗證時間分布資料
        $this->assertIsArray($timeDistribution);

        foreach ($timeDistribution as $hour => $count) {
            $this->assertIsString($hour);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);

            // 驗證小時格式（0-23）
            $hourInt = (int) $hour;
            $this->assertGreaterThanOrEqual(0, $hourInt);
            $this->assertLessThan(24, $hourInt);
        }
    }

    public function testGetPostsGrowthTrend(): void
    {
        $currentPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-02 00:00:00'),
            new DateTimeImmutable('2024-01-02 23:59:59')
        );

        $previousPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $growthTrend = $this->repository->getPostsGrowthTrend($currentPeriod, $previousPeriod);

        // 驗證成長趨勢資料
        $this->assertIsArray($growthTrend);
        $this->assertArrayHasKey('current', $growthTrend);
        $this->assertArrayHasKey('previous', $growthTrend);
        $this->assertArrayHasKey('growth_rate', $growthTrend);
        $this->assertArrayHasKey('growth_count', $growthTrend);

        $this->assertIsInt($growthTrend['current']);
        $this->assertIsInt($growthTrend['previous']);
        $this->assertIsFloat($growthTrend['growth_rate']);
        $this->assertIsInt($growthTrend['growth_count']);

        // 驗證計算正確性
        $expectedGrowthCount = $growthTrend['current'] - $growthTrend['previous'];
        $this->assertEquals($expectedGrowthCount, $growthTrend['growth_count']);
    }

    public function testGetPostsLengthStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $lengthStats = $this->repository->getPostsLengthStatistics($period);

        // 驗證文章長度統計
        $this->assertIsArray($lengthStats);
        $this->assertArrayHasKey('avg_length', $lengthStats);
        $this->assertArrayHasKey('min_length', $lengthStats);
        $this->assertArrayHasKey('max_length', $lengthStats);
        $this->assertArrayHasKey('total_chars', $lengthStats);

        $this->assertIsFloat($lengthStats['avg_length']);
        $this->assertIsInt($lengthStats['min_length']);
        $this->assertIsInt($lengthStats['max_length']);
        $this->assertIsInt($lengthStats['total_chars']);

        $this->assertGreaterThanOrEqual(0, $lengthStats['avg_length']);
        $this->assertGreaterThanOrEqual(0, $lengthStats['min_length']);
        $this->assertLessThanOrEqual($lengthStats['max_length'], $lengthStats['max_length']);
        $this->assertGreaterThanOrEqual(0, $lengthStats['total_chars']);
    }

    public function testGetPostsCountByLengthRange(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $lengthRanges = [
            'short' => ['min' => 0, 'max' => 100],
            'medium' => ['min' => 101, 'max' => 500],
            'long' => ['min' => 501, 'max' => 1000],
            'very_long' => ['min' => 1001, 'max' => 999999],
        ];

        $rangeCounts = $this->repository->getPostsCountByLengthRange($period, $lengthRanges);

        // 驗證字數範圍統計
        $this->assertIsArray($rangeCounts);
        $this->assertCount(4, $rangeCounts);

        foreach ($lengthRanges as $rangeName => $range) {
            $this->assertArrayHasKey($rangeName, $rangeCounts);
            $this->assertIsInt($rangeCounts[$rangeName]);
            $this->assertGreaterThanOrEqual(0, $rangeCounts[$rangeName]);
        }
    }

    public function testGetPinnedPostsStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $pinnedStats = $this->repository->getPinnedPostsStatistics($period);

        // 驗證置頂文章統計
        $this->assertIsArray($pinnedStats);
        $this->assertArrayHasKey('pinned_count', $pinnedStats);
        $this->assertArrayHasKey('unpinned_count', $pinnedStats);
        $this->assertArrayHasKey('pinned_views', $pinnedStats);

        $this->assertIsInt($pinnedStats['pinned_count']);
        $this->assertIsInt($pinnedStats['unpinned_count']);
        $this->assertIsInt($pinnedStats['pinned_views']);
        $this->assertGreaterThanOrEqual(0, $pinnedStats['pinned_count']);
        $this->assertGreaterThanOrEqual(0, $pinnedStats['unpinned_count']);
        $this->assertGreaterThanOrEqual(0, $pinnedStats['pinned_views']);
    }

    public function testHasDataForPeriod(): void
    {
        $periodWithData = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $periodWithoutData = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-01-01 23:59:59')
        );

        // 驗證有資料的期間
        $this->assertTrue($this->repository->hasDataForPeriod($periodWithData));

        // 驗證沒有資料的期間
        $this->assertFalse($this->repository->hasDataForPeriod($periodWithoutData));
    }

    public function testGetPostActivitySummary(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $summary = $this->repository->getPostActivitySummary($period);

        // 驗證活動摘要結果
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_posts', $summary);
        $this->assertArrayHasKey('published_posts', $summary);
        $this->assertArrayHasKey('draft_posts', $summary);
        $this->assertArrayHasKey('total_views', $summary);
        $this->assertArrayHasKey('active_authors', $summary);
        $this->assertArrayHasKey('popular_sources', $summary);

        $this->assertIsInt($summary['total_posts']);
        $this->assertIsInt($summary['published_posts']);
        $this->assertIsInt($summary['draft_posts']);
        $this->assertIsInt($summary['total_views']);
        $this->assertIsInt($summary['active_authors']);
        $this->assertIsArray($summary['popular_sources']);

        $this->assertGreaterThanOrEqual(0, $summary['total_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['published_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['draft_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['total_views']);
        $this->assertGreaterThanOrEqual(0, $summary['active_authors']);
    }

    public function testInvalidParameters(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的限制數量
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getPopularPosts($period, -1);
    }

    public function testInvalidGroupByParameter(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的分組參數
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getPostsPublishTimeDistribution($period, 'invalid');
    }

}
