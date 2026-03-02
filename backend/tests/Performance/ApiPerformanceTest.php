<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Application;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use Tests\Support\IntegrationTestCase;

class ApiPerformanceTest extends IntegrationTestCase
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

        // Seed some data for performance testing
        $this->seedTestData(100);
    }

    private function seedTestData(int $count): void
    {
        $this->db->beginTransaction();
        for ($i = 1; $i <= $count; $i++) {
            $this->db->exec("INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, created_at, updated_at) 
                            VALUES ('uuid-$i', $i, 'Title $i', 'Content for post $i', 1, '127.0.0.1', 0, 'published', datetime('now'), datetime('now'), datetime('now'))");
        }
        $this->db->commit();
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
