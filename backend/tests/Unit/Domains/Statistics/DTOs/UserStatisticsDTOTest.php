<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\UserStatisticsDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserStatisticsDTOTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'active_users' => 150,
            'by_activity_type' => [
                'posts' => 50,
                'comments' => 80,
                'likes' => 200,
                'shares' => 30,
            ],
            'login_activity' => [
                'total_logins' => 500,
                'unique_users' => 120,
                'avg_logins_per_user' => 4.17,
                'peak_hour' => 14,
            ],
            'most_active' => [
                [
                    'user_id' => 123,
                    'username' => 'john_doe',
                    'metric_value' => 25,
                ],
                [
                    'user_id' => 456,
                    'username' => 'jane_smith',
                    'metric_value' => 22,
                ],
            ],
            'engagement_stats' => [
                'high_engagement' => 30,
                'medium_engagement' => 60,
                'low_engagement' => 40,
                'inactive' => 20,
                'avg_engagement_score' => 7.5,
            ],
            'registration_sources' => [
                'website' => 80,
                'social_media' => 50,
                'referral' => 20,
            ],
            'geographical_distribution' => [
                [
                    'location' => 'Taiwan',
                    'users_count' => 100,
                ],
                [
                    'location' => 'Japan',
                    'users_count' => 30,
                ],
            ],
            'by_role' => [
                'user' => 120,
                'admin' => 20,
                'moderator' => 10,
            ],
            'activity_time_distribution' => [
                '09:00' => 20,
                '14:00' => 45,
                '19:00' => 35,
                '22:00' => 25,
            ],
            'generated_at' => '2024-01-15T10:30:00Z',
            'metadata' => [
                'report_id' => 'user_stats_001',
                'version' => '1.0',
            ],
        ];
    }

    public function testConstructionWithValidData(): void
    {
        $generatedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');

        $dto = new UserStatisticsDTO(
            activeUsers: 150,
            byActivityType: ['posts' => 50, 'comments' => 80],
            loginActivity: ['total_logins' => 500, 'unique_users' => 120, 'avg_logins_per_user' => 4.17],
            mostActive: [['user_id' => 123, 'username' => 'john_doe', 'metric_value' => 25]],
            engagementStats: ['high_engagement' => 30, 'medium_engagement' => 60, 'low_engagement' => 40, 'inactive' => 20],
            registrationSources: ['website' => 80],
            geographicalDistribution: [['location' => 'Taiwan', 'users_count' => 100]],
            byRole: ['user' => 120],
            activityTimeDistribution: ['14:00' => 45],
            generatedAt: $generatedAt,
            metadata: ['report_id' => 'test'],
        );

        $this->assertSame(150, $dto->getActiveUsers());
        $this->assertSame(['posts' => 50, 'comments' => 80], $dto->getByActivityType());
        $this->assertSame($generatedAt, $dto->getGeneratedAt());
        $this->assertSame(['report_id' => 'test'], $dto->getMetadata());
    }

    public function testFromArray(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $this->assertSame(150, $dto->getActiveUsers());
        $this->assertSame($this->validData['by_activity_type'], $dto->getByActivityType());
        $this->assertSame($this->validData['login_activity'], $dto->getLoginActivity());
        $this->assertSame($this->validData['most_active'], $dto->getMostActive());
        $this->assertSame($this->validData['engagement_stats'], $dto->getEngagementStats());
        $this->assertSame($this->validData['registration_sources'], $dto->getRegistrationSources());
        $this->assertSame($this->validData['geographical_distribution'], $dto->getGeographicalDistribution());
        $this->assertSame($this->validData['by_role'], $dto->getByRole());
        $this->assertSame($this->validData['activity_time_distribution'], $dto->getActivityTimeDistribution());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getGeneratedAt());
        $this->assertSame($this->validData['metadata'], $dto->getMetadata());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $dto = UserStatisticsDTO::fromArray([]);

        $this->assertSame(0, $dto->getActiveUsers());
        $this->assertSame([], $dto->getByActivityType());
        $this->assertSame([], $dto->getLoginActivity());
        $this->assertSame([], $dto->getMostActive());
        $this->assertSame([], $dto->getEngagementStats());
        $this->assertNull($dto->getGeneratedAt());
        $this->assertSame([], $dto->getMetadata());
    }

    public function testCalculatedLoginMetrics(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $this->assertSame(500, $dto->getTotalLogins());
        $this->assertSame(120, $dto->getUniqueLoggedInUsers());
        $this->assertSame(4.17, $dto->getAverageLoginsPerUser());
        $this->assertSame(14, $dto->getPeakHour());
    }

    public function testCalculatedEngagementMetrics(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $this->assertSame(30, $dto->getHighEngagementUsers());
        $this->assertSame(60, $dto->getMediumEngagementUsers());
        $this->assertSame(40, $dto->getLowEngagementUsers());
        $this->assertSame(20, $dto->getInactiveUsers());
        $this->assertSame(7.5, $dto->getAverageEngagementScore());

        // 計算參與率：(高 + 中) / 總活躍使用者 * 100
        $this->assertSame(60.0, $dto->getEngagementRate()); // (30 + 60) / 150 * 100
    }

    public function testEngagementRateWithZeroActiveUsers(): void
    {
        $data = $this->validData;
        $data['active_users'] = 0;

        $dto = UserStatisticsDTO::fromArray($data);

        $this->assertSame(0.0, $dto->getEngagementRate());
    }

    public function testMostActiveUser(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $mostActive = $dto->getMostActiveUser();

        $this->assertNotNull($mostActive);
        $this->assertSame(123, $mostActive['user_id']);
        $this->assertSame('john_doe', $mostActive['username']);
        $this->assertSame(25, $mostActive['metric_value']);
    }

    public function testMostActiveUserWhenEmpty(): void
    {
        $data = $this->validData;
        $data['most_active'] = [];

        $dto = UserStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getMostActiveUser());
    }

    public function testTopRegistrationSource(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $this->assertSame('website', $dto->getTopRegistrationSource());
    }

    public function testTopRegistrationSourceWhenEmpty(): void
    {
        $data = $this->validData;
        $data['registration_sources'] = [];

        $dto = UserStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getTopRegistrationSource());
    }

    public function testTopLocation(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $this->assertSame('Taiwan', $dto->getTopLocation());
    }

    public function testTopLocationWhenEmpty(): void
    {
        $data = $this->validData;
        $data['geographical_distribution'] = [];

        $dto = UserStatisticsDTO::fromArray($data);

        $this->assertNull($dto->getTopLocation());
    }

    public function testEngagementAnalysis(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $analysis = $dto->getEngagementAnalysis();

        $this->assertArrayHasKey('total_users', $analysis);
        $this->assertArrayHasKey('engagement_rate', $analysis);
        $this->assertArrayHasKey('average_engagement_score', $analysis);
        $this->assertArrayHasKey('engagement_distribution', $analysis);

        $this->assertSame(150, $analysis['total_users']);
        $this->assertSame(60.0, $analysis['engagement_rate']);
        $this->assertSame(7.5, $analysis['average_engagement_score']);

        $distribution = $analysis['engagement_distribution'];
        $this->assertIsArray($distribution);

        $highEngagement = $distribution['high'];
        $this->assertIsArray($highEngagement);
        $this->assertSame(30, $highEngagement['count']);
        $this->assertSame(20.0, $highEngagement['percentage']);

        $mediumEngagement = $distribution['medium'];
        $this->assertIsArray($mediumEngagement);
        $this->assertSame(60, $mediumEngagement['count']);
        $this->assertSame(40.0, $mediumEngagement['percentage']);
    }

    public function testActivityInsights(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $insights = $dto->getActivityInsights();

        $this->assertArrayHasKey('peak_login_hour', $insights);
        $this->assertArrayHasKey('peak_activity_hour', $insights);
        $this->assertArrayHasKey('activity_pattern', $insights);
        $this->assertArrayHasKey('weekend_vs_weekday', $insights);

        $this->assertSame(14, $insights['peak_login_hour']);
        $this->assertSame('14:00', $insights['peak_activity_hour']);
    }

    public function testToArray(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $array = $dto->toArray();

        $this->assertArrayHasKey('active_users', $array);
        $this->assertArrayHasKey('by_activity_type', $array);
        $this->assertArrayHasKey('login_activity', $array);
        $this->assertArrayHasKey('calculated_metrics', $array);
        $this->assertArrayHasKey('engagement_analysis', $array);
        $this->assertArrayHasKey('activity_insights', $array);
        $this->assertArrayHasKey('generated_at', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame(150, $array['active_users']);
        $this->assertSame('2024-01-15T10:30:00Z', $array['generated_at']);
    }

    public function testJsonSerialize(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $json = json_encode($dto, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        $this->assertArrayHasKey('active_users', $decoded);
        $this->assertArrayHasKey('calculated_metrics', $decoded);
        $this->assertSame(150, $decoded['active_users']);
    }

    public function testHasData(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);
        $this->assertTrue($dto->hasData());

        $emptyDto = UserStatisticsDTO::fromArray([]);
        $this->assertFalse($emptyDto->hasData());

        $partialDto = UserStatisticsDTO::fromArray(['by_activity_type' => ['posts' => 1]]);
        $this->assertTrue($partialDto->hasData());
    }

    public function testGetSummary(): void
    {
        $dto = UserStatisticsDTO::fromArray($this->validData);

        $summary = $dto->getSummary();

        $this->assertArrayHasKey('active_users', $summary);
        $this->assertArrayHasKey('total_logins', $summary);
        $this->assertArrayHasKey('engagement_rate', $summary);
        $this->assertArrayHasKey('average_engagement_score', $summary);
        $this->assertArrayHasKey('top_location', $summary);

        $this->assertSame(150, $summary['active_users']);
        $this->assertSame(500, $summary['total_logins']);
        $this->assertSame(60.0, $summary['engagement_rate']);
        $this->assertSame('Taiwan', $summary['top_location']);
    }

    public function testValidationFailsWithNegativeActiveUsers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活躍使用者數不能為負數');

        new UserStatisticsDTO(
            activeUsers: -1,
            byActivityType: [],
            loginActivity: [],
            mostActive: [],
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithInvalidActivityType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('活動類型統計資料格式不正確');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: ['posts' => -1], // 負數
            loginActivity: [],
            mostActive: [],
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithMissingLoginActivityKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('登入活動統計缺少必要的鍵');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: ['total_logins' => 100], // 缺少其他必要的鍵
            mostActive: [],
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithInvalidMostActiveStructure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('最活躍使用者資料結構不正確');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: [],
            mostActive: [['invalid' => 'structure']], // 缺少必要的鍵
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithMissingEngagementStatsKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('參與度統計缺少必要的鍵');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: [],
            mostActive: [],
            engagementStats: ['high_engagement' => 10], // 缺少其他必要的鍵
            registrationSources: [],
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithInvalidRegistrationSources(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('註冊來源統計資料格式不正確');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: [],
            mostActive: [],
            engagementStats: [],
            registrationSources: ['website' => -1], // 負數
            geographicalDistribution: [],
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithInvalidGeographicalDistribution(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('地理分布資料結構不正確');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: [],
            mostActive: [],
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [['invalid' => 'structure']], // 缺少必要的鍵
            byRole: [],
            activityTimeDistribution: [],
        );
    }

    public function testValidationFailsWithInvalidRoleStats(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('角色統計資料格式不正確');

        new UserStatisticsDTO(
            activeUsers: 100,
            byActivityType: [],
            loginActivity: [],
            mostActive: [],
            engagementStats: [],
            registrationSources: [],
            geographicalDistribution: [],
            byRole: ['admin' => -1], // 負數
            activityTimeDistribution: [],
        );
    }
}
