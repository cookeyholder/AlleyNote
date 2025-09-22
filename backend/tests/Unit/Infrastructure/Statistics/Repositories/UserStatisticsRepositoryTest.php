<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\UserStatisticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \App\Infrastructure\Statistics\Repositories\UserStatisticsRepository
 * @phpstan-ignore-next-line
 */
class UserStatisticsRepositoryTest extends TestCase
{
    private PDO&MockObject $mockDb;

    private UserStatisticsRepository $repository;

    private StatisticsPeriod $testPeriod;

    protected function setUp(): void
    {
        $this->mockDb = $this->createMock(PDO::class);
        $this->repository = new UserStatisticsRepository($this->mockDb);

        $this->testPeriod = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2023-01-01'),
            new DateTimeImmutable('2023-01-31'),
        );
    }

    public function testGetActiveUsersCountWithValidActivityType(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->exactly(2))
            ->method('bindValue')
            ->with(
                $this->logicalOr(
                    $this->equalTo(':start_date'),
                    $this->equalTo(':end_date'),
                ),
                $this->logicalOr(
                    $this->equalTo('2023-01-01 00:00:00'),
                    $this->equalTo('2023-01-31 00:00:00'),
                ),
                PDO::PARAM_STR,
            );

        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('150');

        $result = $this->repository->getActiveUsersCount($this->testPeriod, 'login');

        $this->assertSame(150, $result);
    }

    public function testGetActiveUsersCountWithInvalidActivityType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的活動類型: invalid_type');

        $this->repository->getActiveUsersCount($this->testPeriod, 'invalid_type');
    }

    public function testGetActiveUsersCountWithDatabaseError(): void
    {
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('資料庫連接失敗'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('取得活躍使用者數量失敗');

        $this->repository->getActiveUsersCount($this->testPeriod, 'login');
    }

    public function testGetNewUsersCount(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->exactly(2))
            ->method('bindValue');

        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('25');

        $result = $this->repository->getNewUsersCount($this->testPeriod);

        $this->assertSame(25, $result);
    }

    public function testGetTotalUsersCount(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->once())
            ->method('bindValue')
            ->with(':end_date', '2023-01-31 00:00:00', PDO::PARAM_STR);

        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1000');

        $result = $this->repository->getTotalUsersCount($this->testPeriod);

        $this->assertSame(1000, $result);
    }

    public function testGetActiveUsersByActivityType(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->exactly(8))
            ->method('bindValue');

        $mockStmt->expects($this->exactly(4))
            ->method('execute');

        $mockStmt->expects($this->exactly(4))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls('100', '50', '200', '30');

        $result = $this->repository->getActiveUsersByActivityType($this->testPeriod);

        $expected = [
            'login' => 100,
            'post' => 50,
            'view' => 200,
            'comment' => 30,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetMostActiveUsersWithValidInput(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->exactly(3))
            ->method('bindValue');

        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'user_id' => '1',
                    'username' => 'john_doe',
                    'metric_value' => '15',
                    'rank' => '1',
                ],
                [
                    'user_id' => '2',
                    'username' => 'jane_smith',
                    'metric_value' => '10',
                    'rank' => '2',
                ],
                false,
            );

        $result = $this->repository->getMostActiveUsers($this->testPeriod, 5, 'posts');

        $expected = [
            [
                'user_id' => 1,
                'username' => 'john_doe',
                'metric_value' => 15,
                'rank' => 1,
            ],
            [
                'user_id' => 2,
                'username' => 'jane_smith',
                'metric_value' => 10,
                'rank' => 2,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetMostActiveUsersWithInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('查詢數量必須在 1-50 之間');

        $this->repository->getMostActiveUsers($this->testPeriod, 0, 'posts');
    }

    public function testGetMostActiveUsersWithInvalidMetric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的排序指標: invalid_metric');

        $this->repository->getMostActiveUsers($this->testPeriod, 5, 'invalid_metric');
    }

    public function testGetUserLoginActivity(): void
    {
        $mockStmt1 = $this->createMock(PDOStatement::class);
        $mockStmt2 = $this->createMock(PDOStatement::class);
        $mockStmt3 = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($mockStmt1, $mockStmt2, $mockStmt3);

        // 第一個查詢：基本統計
        $mockStmt1->method('bindValue');
        $mockStmt1->method('execute');
        $mockStmt1->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'total_logins' => '500',
                'unique_users' => '150',
                'avg_logins_per_user' => '3.33',
            ]);

        // 第二個查詢：高峰時間
        $mockStmt2->method('bindValue');
        $mockStmt2->method('execute');
        $mockStmt2->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['hour' => '14', 'count' => '50']);

        // 第三個查詢：頻率分布
        $mockStmt3->method('bindValue');
        $mockStmt3->method('execute');
        $mockStmt3->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                ['frequency_range' => '1次', 'users_count' => '50'],
                false,
            );

        $result = $this->repository->getUserLoginActivity($this->testPeriod);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_logins', $result);
        $this->assertArrayHasKey('unique_users', $result);
        $this->assertArrayHasKey('avg_logins_per_user', $result);
        $this->assertArrayHasKey('peak_hour', $result);
        $this->assertArrayHasKey('login_frequency_distribution', $result);
        $this->assertEquals(500, $result['total_logins']);
        $this->assertEquals(150, $result['unique_users']);
        $this->assertEquals(3.33, $result['avg_logins_per_user']);
        $this->assertEquals(14, $result['peak_hour']);
        $this->assertEquals(['1次' => 50], $result['login_frequency_distribution']);
    }

    public function testGetUserRegistrationTrend(): void
    {
        $previousPeriod = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2022-12-01'),
            new DateTimeImmutable('2022-12-31'),
        );

        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(2))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls('25', '20');

        $result = $this->repository->getUserRegistrationTrend($this->testPeriod, $previousPeriod);

        $expected = [
            'current' => 25,
            'previous' => 20,
            'growth_rate' => 25.0,
            'growth_count' => 5,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserActivityTimeDistributionWithValidGroupBy(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['time_period' => '10', 'active_users' => '50'],
                ['time_period' => '14', 'active_users' => '80'],
                false,
            );

        $result = $this->repository->getUserActivityTimeDistribution($this->testPeriod, 'hour');

        $expected = [
            '10' => 50,
            '14' => 80,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserActivityTimeDistributionWithInvalidGroupBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的分組方式: invalid_group');

        $this->repository->getUserActivityTimeDistribution($this->testPeriod, 'invalid_group');
    }

    public function testGetUserRetentionAnalysisWithValidInput(): void
    {
        $cohortPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2023-01-01'),
            new DateTimeImmutable('2023-01-01 23:59:59'),
        );

        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(2))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls('100', '75');

        $result = $this->repository->getUserRetentionAnalysis($cohortPeriod, 7);

        $expected = [
            'cohort_size' => 100,
            'retained_users' => 75,
            'retention_rate' => 75.0,
            'churn_rate' => 25.0,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserRetentionAnalysisWithInvalidDays(): void
    {
        $cohortPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2023-01-01'),
            new DateTimeImmutable('2023-01-01 23:59:59'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('註冊後天數必須大於 0');

        $this->repository->getUserRetentionAnalysis($cohortPeriod, 0);
    }

    public function testGetUsersCountByRole(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(4))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['role' => 'admin', 'count' => '5'],
                ['role' => 'user', 'count' => '100'],
                ['role' => null, 'count' => '10'],
                false,
            );

        $result = $this->repository->getUsersCountByRole($this->testPeriod);

        $expected = [
            'admin' => 5,
            'user' => 100,
            'unknown' => 10,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserEngagementStatistics(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(6))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => '1', 'engagement_score' => '15'],
                ['id' => '2', 'engagement_score' => '8'],
                ['id' => '3', 'engagement_score' => '3'],
                ['id' => '4', 'engagement_score' => '0'],
                ['id' => '5', 'engagement_score' => '12'],
                false,
            );

        $result = $this->repository->getUserEngagementStatistics($this->testPeriod);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('high_engagement', $result);
        $this->assertArrayHasKey('medium_engagement', $result);
        $this->assertArrayHasKey('low_engagement', $result);
        $this->assertArrayHasKey('inactive', $result);
        $this->assertArrayHasKey('avg_engagement_score', $result);
    }

    public function testGetUserRegistrationSources(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(4))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['registration_source' => 'website', 'count' => '50'],
                ['registration_source' => 'mobile_app', 'count' => '30'],
                ['registration_source' => null, 'count' => '20'],
                false,
            );

        $result = $this->repository->getUserRegistrationSources($this->testPeriod);

        $expected = [
            'website' => 50,
            'mobile_app' => 30,
            'direct' => 20,
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserGeographicalDistributionWithValidLimit(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->expects($this->exactly(3))
            ->method('bindValue');

        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['location' => 'Taiwan', 'users_count' => '50', 'percentage' => '25.0'],
                ['location' => 'Japan', 'users_count' => '30', 'percentage' => '15.0'],
                false,
            );

        $result = $this->repository->getUserGeographicalDistribution($this->testPeriod, 5);

        $expected = [
            [
                'location' => 'Taiwan',
                'users_count' => 50,
                'percentage' => 25.0,
            ],
            [
                'location' => 'Japan',
                'users_count' => 30,
                'percentage' => 15.0,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserGeographicalDistributionWithInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('查詢數量必須在 1-50 之間');

        $this->repository->getUserGeographicalDistribution($this->testPeriod, 0);
    }

    public function testHasDataForPeriodWithData(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('5');

        $result = $this->repository->hasDataForPeriod($this->testPeriod);

        $this->assertTrue($result);
    }

    public function testHasDataForPeriodWithoutData(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('0');

        $result = $this->repository->hasDataForPeriod($this->testPeriod);

        $this->assertFalse($result);
    }

    public function testGetUserActivitySummary(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);

        $this->mockDb->expects($this->exactly(5))
            ->method('prepare')
            ->willReturn($mockStmt);

        $mockStmt->method('bindValue');
        $mockStmt->method('execute');

        $mockStmt->expects($this->exactly(4))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls('1000', '25', '150', '75');

        $mockStmt->expects($this->exactly(4))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['hour' => '10', 'active_users' => '50'],
                ['hour' => '14', 'active_users' => '80'],
                ['hour' => '20', 'active_users' => '45'],
                false,
            );

        $result = $this->repository->getUserActivitySummary($this->testPeriod);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_users', $result);
        $this->assertArrayHasKey('active_users', $result);
        $this->assertArrayHasKey('new_users', $result);
        $this->assertArrayHasKey('returning_users', $result);
        $this->assertArrayHasKey('user_activity_rate', $result);
        $this->assertArrayHasKey('top_active_hours', $result);
    }
}
