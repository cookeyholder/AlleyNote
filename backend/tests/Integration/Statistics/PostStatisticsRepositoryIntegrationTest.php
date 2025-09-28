<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * PostStatisticsRepository 整合測試.
 *
 * 測試文章統計 Repository 的資料庫查詢功能，包括：
 * - 文章數量統計
 * - 狀態別統計
 * - 來源統計
 * - 熱門文章查詢
 * - 成長趨勢分析
 * - 長度統計
 * - 時間分布統計
 */
#[Group('integration')]
#[Group('statistics')]
final class PostStatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private PostStatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostStatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new StatisticsTestSeeder($this->db);
        $seeder->seedAll();
    }

    public function testGetTotalPostsCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $count = $this->repository->getTotalPostsCount($period);

        // 驗證取得的文章數量（基於測試資料）
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetTotalPostsCountWithStatus(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $publishedCount = $this->repository->getTotalPostsCount($period, '1');
        $draftCount = $this->repository->getTotalPostsCount($period, '0');

        // 驗證結果
        $this->assertEquals(3, $publishedCount);
        $this->assertEquals(1, $draftCount);
    }

    public function testGetPostsCountByStatus(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $statusCounts = $this->repository->getPostsCountByStatus($period);

        // 驗證結果結構
        $this->assertArrayHasKey('published', $statusCounts);
        $this->assertArrayHasKey('draft', $statusCounts);
        $this->assertArrayHasKey('archived', $statusCounts);

        // 驗證數值
        $this->assertEquals(3, $statusCounts['published'] ?? 0);
        $this->assertEquals(1, $statusCounts['draft'] ?? 0);
        $this->assertEquals(0, $statusCounts['archived'] ?? 0);
    }

    public function testGetPostsCountBySource(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $sourceCounts = $this->repository->getPostsCountBySource($period);

        // 驗證結果結構
        $this->assertArrayHasKey('web', $sourceCounts);
        $this->assertArrayHasKey('mobile', $sourceCounts);
        $this->assertArrayHasKey('api', $sourceCounts);

        // 驗證數值
        $this->assertGreaterThanOrEqual(0, $sourceCounts['web']);
        $this->assertGreaterThanOrEqual(0, $sourceCounts['mobile']);
        $this->assertGreaterThanOrEqual(0, $sourceCounts['api']);
    }

    public function testGetPostsCountBySourceType(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $webCount = $this->repository->getPostsCountBySourceType(
            $period,
            SourceType::fromCode('web'),
        );

        // 驗證特定來源統計結果
        $this->assertGreaterThanOrEqual(0, $webCount);
    }

    public function testGetPostViewsStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $viewsStats = $this->repository->getPostViewsStatistics($period);

        // 驗證統計結果結構
        $this->assertArrayHasKey('total_views', $viewsStats);
        $this->assertArrayHasKey('unique_views', $viewsStats);
        $this->assertArrayHasKey('avg_views_per_post', $viewsStats);

        // 驗證數值有效性
        $this->assertGreaterThanOrEqual(0, $viewsStats['total_views']);
        $this->assertGreaterThanOrEqual(0, $viewsStats['unique_views']);
        $this->assertGreaterThanOrEqual(0, $viewsStats['avg_views_per_post']);
    }

    public function testGetPopularPosts(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );
        $limit = 5;

        $popularPosts = $this->repository->getPopularPosts($period, $limit);

        // 驗證結果結構
        $this->assertLessThanOrEqual($limit, count($popularPosts));

        // 如果有結果，驗證每個項目的結構
        if (!empty($popularPosts)) {
            foreach ($popularPosts as $post) {
                $this->assertArrayHasKey('post_id', $post);
                $this->assertArrayHasKey('title', $post);
                $this->assertArrayHasKey('metric_value', $post);

                // 驗證數值類型
                $this->assertGreaterThan(0, $post['post_id']);
                $this->assertIsString($post['title']);
                $this->assertGreaterThanOrEqual(0, $post['metric_value']);
            }

            // 驗證排序正確性（按指標值降序）
            if (count($popularPosts) > 1) {
                $this->assertGreaterThanOrEqual($popularPosts[1]['metric_value'], $popularPosts[0]['metric_value']);
            }
        }
    }

    public function testGetPopularPostsByComments(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );
        $limit = 10;

        $topCommentedPosts = $this->repository->getPopularPosts($period, $limit, 'comments');

        // 驗證結果結構
        $this->assertLessThanOrEqual($limit, count($topCommentedPosts));

        // 如果有結果，驗證結構和排序
        if (!empty($topCommentedPosts)) {
            foreach ($topCommentedPosts as $post) {
                $this->assertArrayHasKey('post_id', $post);
                $this->assertArrayHasKey('title', $post);
                $this->assertArrayHasKey('metric_value', $post);

                $this->assertGreaterThan(0, $post['post_id']);
                $this->assertIsString($post['title']);
                $this->assertGreaterThanOrEqual(0, $post['metric_value']);
            }

            // 驗證按評論數降序排列
            if (count($topCommentedPosts) > 1) {
                $this->assertGreaterThanOrEqual(
                    $topCommentedPosts[1]['metric_value'],
                    $topCommentedPosts[0]['metric_value'],
                );
            }
        }
    }

    public function testGetPostsPublishTimeDistribution(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $timeDistribution = $this->repository->getPostsPublishTimeDistribution($period, 'hour');

        // 驗證結果結構

        // 每小時的統計結果檢查
        foreach ($timeDistribution as $hour => $count) {
            $this->assertIsString($hour);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetPostsGrowthTrend(): void
    {
        // 設定當前和前一期間
        $currentPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-02 00:00:00'),
            new DateTimeImmutable('2024-01-02 23:59:59'),
        );

        $previousPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $growthTrend = $this->repository->getPostsGrowthTrend($currentPeriod, $previousPeriod);

        // 驗證結果結構
        $this->assertArrayHasKey('current', $growthTrend);
        $this->assertArrayHasKey('previous', $growthTrend);
        $this->assertArrayHasKey('growth_count', $growthTrend);
        $this->assertArrayHasKey('growth_rate', $growthTrend);

        // 驗證數值類型和邏輯
        $this->assertGreaterThanOrEqual(0, $growthTrend['current']);
        $this->assertGreaterThanOrEqual(0, $growthTrend['previous']);
        $this->assertIsInt($growthTrend['growth_count']);
        $this->assertIsFloat($growthTrend['growth_rate']);

        // 驗證成長數量計算正確性
        $expectedGrowthCount = $growthTrend['current'] - $growthTrend['previous'];
        $this->assertEquals($expectedGrowthCount, $growthTrend['growth_count']);
    }

    public function testGetPostsLengthStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $lengthStats = $this->repository->getPostsLengthStatistics($period);

        // 驗證統計結果結構
        $this->assertArrayHasKey('avg_length', $lengthStats);
        $this->assertArrayHasKey('max_length', $lengthStats);
        $this->assertArrayHasKey('min_length', $lengthStats);
        $this->assertArrayHasKey('total_chars', $lengthStats);

        // 驗證數值有效性
        $this->assertGreaterThanOrEqual(0, $lengthStats['avg_length']);
        $this->assertGreaterThanOrEqual(0, $lengthStats['max_length']);
        $this->assertGreaterThanOrEqual(0, $lengthStats['min_length']);
        $this->assertGreaterThanOrEqual(0, $lengthStats['total_chars']);

        // 邏輯檢查
        $this->assertGreaterThanOrEqual($lengthStats['min_length'], $lengthStats['max_length']);
    }

    public function testGetPostsCountByLengthRange(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $lengthRanges = [
            'short' => ['min' => 0, 'max' => 500],
            'medium' => ['min' => 501, 'max' => 1000],
            'long' => ['min' => 1001, 'max' => 2000],
            'extra_long' => ['min' => 2001, 'max' => 5000], // 使用具體數值而非 null
        ];

        $lengthDistribution = $this->repository->getPostsCountByLengthRange($period, $lengthRanges);

        // 驗證結果結構
        $this->assertCount(4, $lengthDistribution);

        foreach ($lengthDistribution as $rangeData) {
            $this->assertIsArray($rangeData);
            $this->assertArrayHasKey('range', $rangeData);
            $this->assertArrayHasKey('count', $rangeData);
            $this->assertArrayHasKey('percentage', $rangeData);

            $this->assertIsString($rangeData['range']);
            $this->assertGreaterThanOrEqual(0, $rangeData['count']);
            $this->assertGreaterThanOrEqual(0, $rangeData['percentage']);
            $this->assertLessThanOrEqual(100, $rangeData['percentage']);
        }
    }

    public function testGetPinnedPostsStatistics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $pinnedStats = $this->repository->getPinnedPostsStatistics($period);

        // 驗證結果結構
        $this->assertArrayHasKey('pinned_count', $pinnedStats);
        $this->assertArrayHasKey('unpinned_count', $pinnedStats);
        $this->assertArrayHasKey('pinned_views', $pinnedStats);

        // 驗證數值
        $this->assertGreaterThanOrEqual(0, $pinnedStats['pinned_count']);
        $this->assertGreaterThanOrEqual(0, $pinnedStats['unpinned_count']);
        $this->assertGreaterThanOrEqual(0, $pinnedStats['pinned_views']);
    }

    public function testEmptyResultHandling(): void
    {
        // 測試無資料的日期範圍
        $periodWithData = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $periodWithoutData = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-01-01 23:59:59'),
        );

        // 驗證有資料的期間
        $countWithData = $this->repository->getTotalPostsCount($periodWithData);
        $this->assertGreaterThan(0, $countWithData);

        // 驗證無資料的期間
        $countWithoutData = $this->repository->getTotalPostsCount($periodWithoutData);
        $this->assertEquals(0, $countWithoutData);
    }

    public function testGetPostActivitySummary(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $summary = $this->repository->getPostActivitySummary($period);

        // 驗證摘要結構
        $this->assertArrayHasKey('total_posts', $summary);
        $this->assertArrayHasKey('published_posts', $summary);
        $this->assertArrayHasKey('draft_posts', $summary);
        $this->assertArrayHasKey('total_views', $summary);
        $this->assertArrayHasKey('active_authors', $summary);
        $this->assertArrayHasKey('popular_sources', $summary);

        // 驗證數值有效性
        $this->assertGreaterThanOrEqual(0, $summary['total_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['published_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['draft_posts']);
        $this->assertGreaterThanOrEqual(0, $summary['total_views']);
        $this->assertGreaterThanOrEqual(0, $summary['active_authors']);

        // 驗證邏輯一致性
        $this->assertEquals(
            $summary['published_posts'] + $summary['draft_posts'],
            $summary['total_posts'],
        );
        $this->assertEquals(3, $summary['active_authors']);
    }

    public function testInvalidLimitThrowsException(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        // 測試無效的限制數量
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPopularPosts($period, -1);
    }

    public function testInvalidTimeGroupThrowsException(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        // 測試無效的分組參數
        $this->expectException(InvalidArgumentException::class);
        $this->repository->getPostsPublishTimeDistribution($period, 'invalid');
    }
}
