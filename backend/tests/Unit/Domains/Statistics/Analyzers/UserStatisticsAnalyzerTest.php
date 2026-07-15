<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\UserStatisticsAnalyzer;
use App\Domains\Statistics\Analyzers\UserStatisticsResult;
use App\Domains\Statistics\DTOs\UserStatisticsDTO;
use Tests\Support\UnitTestCase;

/**
 * UserStatisticsAnalyzer 單元測試.
 */
class UserStatisticsAnalyzerTest extends UnitTestCase
{
    private UserStatisticsAnalyzer $analyzer;

    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->analyzer = new UserStatisticsAnalyzer();
        $this->validData = [
            'active_users'     => 150,
            'by_activity_type' => ['posts' => 50, 'comments' => 80, 'likes' => 200, 'shares' => 30],
            'login_activity'   => ['total_logins' => 500, 'unique_users' => 120, 'avg_logins_per_user' => 4.17, 'peak_hour' => 14],
            'most_active'      => [
                ['user_id' => 123, 'username' => 'john_doe', 'metric_value' => 25],
                ['user_id' => 456, 'username' => 'jane_smith', 'metric_value' => 22],
            ],
            'engagement_stats' => [
                'high_engagement' => 30, 'medium_engagement' => 60,
                'low_engagement'  => 40, 'inactive' => 20, 'avg_engagement_score' => 7.5,
            ],
            'registration_sources'      => ['website' => 80, 'social_media' => 50, 'referral' => 20],
            'geographical_distribution' => [
                ['location' => 'Taiwan', 'users_count' => 100],
                ['location' => 'Japan', 'users_count' => 30],
            ],
            'by_role'                    => ['user' => 120, 'admin' => 20, 'moderator' => 10],
            'activity_time_distribution' => ['09:00' => 20, '14:00' => 45, '19:00' => 35, '22:00' => 25],
            'generated_at'               => '2024-01-15T10:30:00Z',
        ];
    }

    private function createDTO(): UserStatisticsDTO
    {
        return UserStatisticsDTO::fromArray($this->validData);
    }

    public function testEngagementAnalysis(): void
    {
        $dto = $this->createDTO();
        $analysis = $this->analyzer->getEngagementAnalysis($dto);

        $this->assertArrayHasKey('total_users', $analysis);
        $this->assertArrayHasKey('engagement_rate', $analysis);
        $this->assertArrayHasKey('average_engagement_score', $analysis);
        $this->assertArrayHasKey('engagement_distribution', $analysis);

        $this->assertSame(150, $analysis['total_users']);
        $this->assertSame(60.0, $analysis['engagement_rate']);
        $this->assertSame(7.5, $analysis['average_engagement_score']);

        $distribution = $analysis['engagement_distribution'];
        $this->assertSame(30, $distribution['high']['count']);
        $this->assertSame(20.0, $distribution['high']['percentage']);
    }

    public function testActivityInsights(): void
    {
        $dto = $this->createDTO();
        $insights = $this->analyzer->getActivityInsights($dto);

        $this->assertArrayHasKey('peak_login_hour', $insights);
        $this->assertArrayHasKey('peak_activity_hour', $insights);
        $this->assertArrayHasKey('activity_pattern', $insights);
        $this->assertArrayHasKey('weekend_vs_weekday', $insights);

        $this->assertSame(14, $insights['peak_login_hour']);
        $this->assertSame('14:00', $insights['peak_activity_hour']);
    }

    public function testAnalyzeReturnsResult(): void
    {
        $dto = $this->createDTO();
        $result = $this->analyzer->analyze($dto);

        $this->assertInstanceOf(UserStatisticsResult::class, $result);
        $this->assertArrayHasKey('engagement_analysis', $result->toArray());
        $this->assertArrayHasKey('activity_insights', $result->toArray());
    }
}
