<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\UserStatisticsRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

/**
 * UserStatisticsRepository 整合測試.
 *
 * 測試使用者統計 Repository 的資料庫互動和複雜查詢功能。
 * 專注於使用者活躍度與行為分析的統計查詢。
 */
#[Group('statistics')]
#[Group('repository')]
#[Group('integration')]
#[Group('users')]
final class UserStatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private UserStatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserStatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new \Tests\Support\Statistics\StatisticsTestSeeder($this->db);
        $seeder->seedAll();
    }

    public function testGetActiveUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試不同活動類型
        $loginCount = $this->repository->getActiveUsersCount($period, 'login');
        $postCount = $this->repository->getActiveUsersCount($period, 'post');
        $viewCount = $this->repository->getActiveUsersCount($period, 'view');
        $commentCount = $this->repository->getActiveUsersCount($period, 'comment');

        // 驗證結果
        $this->assertIsInt($loginCount);
        $this->assertIsInt($postCount);
        $this->assertIsInt($viewCount);
        $this->assertIsInt($commentCount);

        $this->assertGreaterThanOrEqual(0, $loginCount);
        $this->assertGreaterThanOrEqual(0, $postCount);
        $this->assertGreaterThanOrEqual(0, $viewCount);
        $this->assertGreaterThanOrEqual(0, $commentCount);
    }

    public function testGetActiveUsersCountWithInvalidType(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的活動類型
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getActiveUsersCount($period, 'invalid_type');
    }

    public function testGetNewUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $newUsersCount = $this->repository->getNewUsersCount($period);

        // 驗證結果
        $this->assertIsInt($newUsersCount);
        $this->assertGreaterThanOrEqual(0, $newUsersCount);
        $this->assertEquals(5, $newUsersCount); // 基於 StatisticsTestSeeder 的測試資料
    }

    public function testGetTotalUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 23:59:59'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $totalUsersCount = $this->repository->getTotalUsersCount($period);

        // 驗證結果
        $this->assertIsInt($totalUsersCount);
        $this->assertGreaterThanOrEqual(0, $totalUsersCount);
        $this->assertEquals(5, $totalUsersCount); // 基於 StatisticsTestSeeder 的測試資料
    }

    public function testGetActiveUsersByActivityType(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $activityStats = $this->repository->getActiveUsersByActivityType($period);

        // 驗證結果結構
        $this->assertIsArray($activityStats);
        $this->assertArrayHasKey('login', $activityStats);
        $this->assertArrayHasKey('post', $activityStats);
        $this->assertArrayHasKey('view', $activityStats);
        $this->assertArrayHasKey('comment', $activityStats);

        // 驗證每個活動類型的值
        foreach ($activityStats as $type => $count) {
            $this->assertIsString($type);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetMostActiveUsers(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );
        $limit = 5;

        // 測試不同的排序指標
        $metrics = ['posts', 'views', 'logins', 'activity_score'];

        foreach ($metrics as $metric) {
            $activeUsers = $this->repository->getMostActiveUsers($period, $limit, $metric);

            // 驗證結果
            $this->assertIsArray($activeUsers);
            $this->assertLessThanOrEqual($limit, count($activeUsers));

            $previousValue = PHP_INT_MAX;
            foreach ($activeUsers as $user) {
                $this->assertIsArray($user);
                $this->assertArrayHasKey('user_id', $user);
                $this->assertArrayHasKey('username', $user);
                $this->assertArrayHasKey('metric_value', $user);
                $this->assertArrayHasKey('rank', $user);

                $this->assertIsInt($user['user_id']);
                $this->assertIsString($user['username']);
                $this->assertIsInt($user['metric_value']);
                $this->assertIsInt($user['rank']);

                // 驗證排序正確性（遞減）
                $this->assertLessThanOrEqual($previousValue, $user['metric_value']);
                $previousValue = $user['metric_value'];
            }
        }
    }

    public function testGetMostActiveUsersWithInvalidParameters(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的限制數量
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getMostActiveUsers($period, -1);
    }

    public function testGetMostActiveUsersWithInvalidMetric(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的排序指標
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getMostActiveUsers($period, 5, 'invalid_metric');
    }

    public function testGetUserLoginActivity(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $loginActivity = $this->repository->getUserLoginActivity($period);

        // 驗證結果結構
        $this->assertIsArray($loginActivity);
        $this->assertArrayHasKey('total_logins', $loginActivity);
        $this->assertArrayHasKey('unique_users', $loginActivity);
        $this->assertArrayHasKey('avg_logins_per_user', $loginActivity);
        $this->assertArrayHasKey('peak_hour', $loginActivity);
        $this->assertArrayHasKey('login_frequency_distribution', $loginActivity);

        // 驗證資料型態
        $this->assertIsInt($loginActivity['total_logins']);
        $this->assertIsInt($loginActivity['unique_users']);
        $this->assertIsFloat($loginActivity['avg_logins_per_user']);
        $this->assertIsInt($loginActivity['peak_hour']);
        $this->assertIsArray($loginActivity['login_frequency_distribution']);

        // 驗證數值合理性
        $this->assertGreaterThanOrEqual(0, $loginActivity['total_logins']);
        $this->assertGreaterThanOrEqual(0, $loginActivity['unique_users']);
        $this->assertGreaterThanOrEqual(0, $loginActivity['avg_logins_per_user']);
        $this->assertGreaterThanOrEqual(0, $loginActivity['peak_hour']);
        $this->assertLessThan(24, $loginActivity['peak_hour']);

        // 驗證頻率分布
        foreach ($loginActivity['login_frequency_distribution'] as $range => $count) {
            $this->assertIsString($range);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetUserRegistrationTrend(): void
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

        $registrationTrend = $this->repository->getUserRegistrationTrend($currentPeriod, $previousPeriod);

        // 驗證結果結構
        $this->assertIsArray($registrationTrend);
        $this->assertArrayHasKey('current', $registrationTrend);
        $this->assertArrayHasKey('previous', $registrationTrend);
        $this->assertArrayHasKey('growth_rate', $registrationTrend);
        $this->assertArrayHasKey('growth_count', $registrationTrend);

        // 驗證資料型態
        $this->assertIsInt($registrationTrend['current']);
        $this->assertIsInt($registrationTrend['previous']);
        $this->assertIsFloat($registrationTrend['growth_rate']);
        $this->assertIsInt($registrationTrend['growth_count']);

        // 驗證計算正確性
        $expectedGrowthCount = $registrationTrend['current'] - $registrationTrend['previous'];
        $this->assertEquals($expectedGrowthCount, $registrationTrend['growth_count']);

        // 當前期間沒有新註冊用戶，成長率應該是負數或0
        $this->assertLessThanOrEqual(0, $registrationTrend['growth_rate']);
    }

    public function testGetUserEngagementMetrics(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $engagementStats = $this->repository->getUserEngagementStatistics($period);

        // 驗證結果結構
        $this->assertIsArray($engagementStats);
        $this->assertArrayHasKey('high_engagement', $engagementStats);
        $this->assertArrayHasKey('medium_engagement', $engagementStats);
        $this->assertArrayHasKey('low_engagement', $engagementStats);
        $this->assertArrayHasKey('inactive', $engagementStats);
        $this->assertArrayHasKey('avg_engagement_score', $engagementStats);

        // 驗證資料型態
        $this->assertIsInt($engagementStats['high_engagement']);
        $this->assertIsInt($engagementStats['medium_engagement']);
        $this->assertIsInt($engagementStats['low_engagement']);
        $this->assertIsInt($engagementStats['inactive']);
        $this->assertIsFloat($engagementStats['avg_engagement_score']);

        // 驗證數值合理性
        $this->assertGreaterThanOrEqual(0, $engagementStats['high_engagement']);
        $this->assertGreaterThanOrEqual(0, $engagementStats['medium_engagement']);
        $this->assertGreaterThanOrEqual(0, $engagementStats['low_engagement']);
        $this->assertGreaterThanOrEqual(0, $engagementStats['inactive']);
        $this->assertGreaterThanOrEqual(0, $engagementStats['avg_engagement_score']);
    }

    public function testGetUserRetentionAnalysis(): void
    {
        $cohortPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $retentionAnalysis = $this->repository->getUserRetentionAnalysis($cohortPeriod, 1);

        // 驗證結果結構
        $this->assertIsArray($retentionAnalysis);
        $this->assertArrayHasKey('cohort_size', $retentionAnalysis);
        $this->assertArrayHasKey('retained_users', $retentionAnalysis);
        $this->assertArrayHasKey('retention_rate', $retentionAnalysis);
        $this->assertArrayHasKey('churn_rate', $retentionAnalysis);

        // 驗證資料型態
        $this->assertIsInt($retentionAnalysis['cohort_size']);
        $this->assertIsInt($retentionAnalysis['retained_users']);
        $this->assertIsFloat($retentionAnalysis['retention_rate']);
        $this->assertIsFloat($retentionAnalysis['churn_rate']);

        // 驗證數值合理性
        $this->assertGreaterThanOrEqual(0, $retentionAnalysis['cohort_size']);
        $this->assertGreaterThanOrEqual(0, $retentionAnalysis['retained_users']);
        $this->assertGreaterThanOrEqual(0, $retentionAnalysis['retention_rate']);
        $this->assertLessThanOrEqual(100, $retentionAnalysis['retention_rate']);
        $this->assertGreaterThanOrEqual(0, $retentionAnalysis['churn_rate']);
        $this->assertLessThanOrEqual(100, $retentionAnalysis['churn_rate']);

        // 保留率 + 流失率 = 100%
        $this->assertEquals(100.0, $retentionAnalysis['retention_rate'] + $retentionAnalysis['churn_rate']);
    }

    public function testGetUserActivityTimeDistribution(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $timeDistribution = $this->repository->getUserActivityTimeDistribution($period, 'hour');

        // 驗證結果結構
        $this->assertIsArray($timeDistribution);

        foreach ($timeDistribution as $hour => $count) {
            $this->assertIsString($hour);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);

            // 驗證小時格式
            $hourInt = (int) $hour;
            $this->assertGreaterThanOrEqual(0, $hourInt);
            $this->assertLessThan(24, $hourInt);
        }
    }

    public function testGetUsersCountByRole(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $roleCounts = $this->repository->getUsersCountByRole($period);

        // 驗證結果結構
        $this->assertIsArray($roleCounts);

        foreach ($roleCounts as $role => $count) {
            $this->assertIsString($role);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function testGetUserRegistrationSources(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $sources = $this->repository->getUserRegistrationSources($period);

        // 驗證結果結構
        $this->assertIsArray($sources);

        foreach ($sources as $source => $count) {
            $this->assertIsString($source);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }

        // 驗證測試資料中的來源
        $this->assertArrayHasKey('web', $sources);
        $this->assertArrayHasKey('mobile', $sources);
        $this->assertArrayHasKey('social', $sources);
    }

    public function testGetUserGeographicalDistribution(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );
        $limit = 5;

        $distribution = $this->repository->getUserGeographicalDistribution($period, $limit);

        // 驗證結果結構
        $this->assertIsArray($distribution);
        $this->assertLessThanOrEqual($limit, count($distribution));

        foreach ($distribution as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('location', $item);
            $this->assertArrayHasKey('users_count', $item);
            $this->assertArrayHasKey('percentage', $item);

            $this->assertIsString($item['location']);
            $this->assertIsInt($item['users_count']);
            $this->assertIsFloat($item['percentage']);
            $this->assertGreaterThanOrEqual(0, $item['users_count']);
            $this->assertGreaterThanOrEqual(0, $item['percentage']);
            $this->assertLessThanOrEqual(100, $item['percentage']);
        }
    }

    public function testGetUserActivitySummary(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $summary = $this->repository->getUserActivitySummary($period);

        // 驗證結果結構
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_users', $summary);
        $this->assertArrayHasKey('active_users', $summary);
        $this->assertArrayHasKey('new_users', $summary);
        $this->assertArrayHasKey('returning_users', $summary);
        $this->assertArrayHasKey('user_activity_rate', $summary);
        $this->assertArrayHasKey('top_active_hours', $summary);

        // 驗證資料型態
        $this->assertIsInt($summary['total_users']);
        $this->assertIsInt($summary['active_users']);
        $this->assertIsInt($summary['new_users']);
        $this->assertIsInt($summary['returning_users']);
        $this->assertIsFloat($summary['user_activity_rate']);
        $this->assertIsArray($summary['top_active_hours']);

        // 驗證數值合理性
        $this->assertGreaterThanOrEqual(0, $summary['total_users']);
        $this->assertGreaterThanOrEqual(0, $summary['active_users']);
        $this->assertGreaterThanOrEqual(0, $summary['new_users']);
        $this->assertGreaterThanOrEqual(0, $summary['returning_users']);
        $this->assertGreaterThanOrEqual(0, $summary['user_activity_rate']);
        $this->assertLessThanOrEqual(100, $summary['user_activity_rate']);

        // 驗證最活躍時段
        foreach ($summary['top_active_hours'] as $hour) {
            $this->assertIsInt($hour);
            $this->assertGreaterThanOrEqual(0, $hour);
            $this->assertLessThan(24, $hour);
        }
    }

    public function testInvalidParametersInRetentionAnalysis(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        // 測試無效的天數
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getUserRetentionAnalysis($period, 0);
    }

}
