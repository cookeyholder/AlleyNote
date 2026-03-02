<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Application;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use Tests\Support\DatabaseTestCase;

class ApiPerformanceTest extends DatabaseTestCase
{
    private PostRepository $postRepository;

    private PostStatisticsRepository $statsRepository;

    private function getApp(): Application
    {
        return new Application();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getApp()->getContainer();
        $this->postRepository = $container->get(PostRepository::class);
        $this->statsRepository = $container->get(PostStatisticsRepository::class);

        // 使用內建方法 Seed 測試資料
        $this->seedTestData(100);
    }

    private function seedTestData(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->insertTestPost([
                'uuid' => "uuid-$i",
                'seq_number' => $i,
                'title' => "Title $i",
                'content' => "Content for post $i",
            ]);
        }
    }

    public function test_post_listing_performance_is_within_limit(): void
    {
        $startTime = microtime(true);

        $this->postRepository->paginate(1, 20);

        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $durationMs, "Post listing took too long: {$durationMs}ms (Limit: 500ms)");
    }

    public function test_popular_posts_statistics_performance_is_within_limit(): void
    {
        $startTime = microtime(true);

        $startTimeObj = new DateTimeImmutable('today');
        $endTimeObj = $startTimeObj->modify('+1 day');

        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            $startTimeObj,
            $endTimeObj,
            'UTC',
        );

        $this->statsRepository->getPopularPosts($period, 10);

        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $durationMs, "Popular posts statistics took too long: {$durationMs}ms (Limit: 500ms)");
    }
}
