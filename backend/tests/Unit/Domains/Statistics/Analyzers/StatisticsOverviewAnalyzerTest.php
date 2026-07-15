<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\StatisticsOverviewAnalyzer;
use App\Domains\Statistics\Analyzers\StatisticsOverviewResult;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use Tests\Support\UnitTestCase;

/**
 * StatisticsOverviewAnalyzer 單元測試.
 */
class StatisticsOverviewAnalyzerTest extends UnitTestCase
{
    private StatisticsOverviewAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new StatisticsOverviewAnalyzer();
    }

    public function testGetActivityLevelHigh(): void
    {
        $dto = new StatisticsOverviewDTO(
            totalPosts: 200,
            activeUsers: 100,
            newUsers: 50,
            postActivity: ['total_posts' => 200, 'published_posts' => 150, 'draft_posts' => 50],
            userActivity: ['total_users' => 500, 'active_users' => 100, 'new_users' => 50],
            engagementMetrics: ['posts_per_active_user' => 2.0, 'user_growth_rate' => 10.0],
            periodSummary: ['type' => 'monthly', 'duration_days' => 30],
        );

        $this->assertSame('high', $this->analyzer->getActivityLevel($dto));
    }

    public function testGetActivityLevelLow(): void
    {
        $dto = new StatisticsOverviewDTO(
            totalPosts: 5,
            activeUsers: 3,
            newUsers: 1,
            postActivity: ['total_posts' => 5, 'published_posts' => 3, 'draft_posts' => 2],
            userActivity: ['total_users' => 20, 'active_users' => 3, 'new_users' => 1],
            engagementMetrics: ['posts_per_active_user' => 1.0, 'user_growth_rate' => 0.0],
            periodSummary: ['type' => 'monthly', 'duration_days' => 30],
        );

        $this->assertSame('low', $this->analyzer->getActivityLevel($dto));
    }

    public function testGetActivityLevelInactive(): void
    {
        $dto = new StatisticsOverviewDTO(
            totalPosts: 0,
            activeUsers: 0,
            newUsers: 0,
            postActivity: ['total_posts' => 0, 'published_posts' => 0, 'draft_posts' => 0],
            userActivity: ['total_users' => 10, 'active_users' => 0, 'new_users' => 0],
            engagementMetrics: ['posts_per_active_user' => 0.0, 'user_growth_rate' => 0.0],
            periodSummary: ['type' => 'monthly', 'duration_days' => 30],
        );

        $this->assertSame('inactive', $this->analyzer->getActivityLevel($dto));
    }

    public function testAnalyzeReturnsResult(): void
    {
        $dto = new StatisticsOverviewDTO(
            totalPosts: 100,
            activeUsers: 50,
            newUsers: 20,
            postActivity: ['total_posts' => 100, 'published_posts' => 80, 'draft_posts' => 20],
            userActivity: ['total_users' => 200, 'active_users' => 50, 'new_users' => 20],
            engagementMetrics: ['posts_per_active_user' => 2.0, 'user_growth_rate' => 10.0],
            periodSummary: ['type' => 'monthly', 'duration_days' => 30],
        );

        $result = $this->analyzer->analyze($dto);

        $this->assertInstanceOf(StatisticsOverviewResult::class, $result);
        $this->assertSame('high', $result->getActivityLevel());
        $this->assertSame(100.0, $result->getActivityScore());
    }
}
