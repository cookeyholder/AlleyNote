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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\Support\UnitTestCase;

#[CoversClass(UserStatisticsRepository::class)]
class UserStatisticsRepositoryTest extends UnitTestCase
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
            'login'   => 100,
            'post'    => 50,
            'view'    => 200,
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
                    'user_id'      => '1',
                    'username'     => 'john_doe',
                    'metric_value' => '15',
                    'rank'         => '1',
                ],
                [
                    'user_id'      => '2',
                    'username'     => 'jane_smith',
                    'metric_value' => '10',
                    'rank'         => '2',
                ],
                false,
            );

        $result = $this->repository->getMostActiveUsers($this->testPeriod, 5, 'posts');

        $expected = [
            [
                'user_id'      => 1,
                'username'     => 'john_doe',
                'metric_value' => 15,
                'rank'         => 1,
            ],
            [
                'user_id'      => 2,
                'username'     => 'jane_smith',
                'metric_value' => 10,
                'rank'         => 2,
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
                'total_logins'        => '500',
                'unique_users'        => '150',
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
            'current'      => 25,
            'previous'     => 20,
            'growth_rate'  => 25.0,
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
            'cohort_size'    => 100,
            'retained_users' => 75,
            'retention_rate' => 75.0,
            'churn_rate'     => 25.0,
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
            'admin'   => 5,
            'user'    => 100,
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
            'website'    => 50,
            'mobile_app' => 30,
            'direct'     => 20,
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
                'location'    => 'Taiwan',
                'users_count' => 50,
                'percentage'  => 25.0,
            ],
            [
                'location'    => 'Japan',
                'users_count' => 30,
                'percentage'  => 15.0,
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

    // ========== 以下使用真實 SQLite 資料庫驗證完整查詢與錯誤處理 ==========

    private PDO $realDb;

    /**
     * 建立真實 SQLite 連線與使用者統計相關資料表.
     */
    private function createRealRepository(): UserStatisticsRepository
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                role TEXT DEFAULT "user",
                registration_source TEXT DEFAULT "website",
                location TEXT,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL DEFAULT "",
                user_id INTEGER,
                status TEXT DEFAULT "1",
                creation_source TEXT DEFAULT "web",
                views INTEGER DEFAULT 0,
                is_pinned INTEGER DEFAULT 0,
                content TEXT,
                comments_count INTEGER DEFAULT 0,
                likes_count INTEGER DEFAULT 0,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE user_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action_type TEXT,
                action TEXT,
                occurred_at DATETIME,
                created_at DATETIME NOT NULL
            );
        ');
        $this->realDb = $pdo;

        return new UserStatisticsRepository($pdo);
    }

    private function seedRealDatabase(): void
    {
        $this->realDb->exec("
            INSERT INTO users (username, role, registration_source, location, created_at) VALUES
            ('alice', 'admin', 'website', 'Taipei', '2023-01-01 08:00:00'),
            ('bob',   'user',  'referral', 'Kaohsiung', '2023-01-15 12:00:00')
        ");
        $this->realDb->exec("
            INSERT INTO posts (title, user_id, status, views, created_at) VALUES
            ('Post A', 1, '1', 100, '2023-01-10 09:00:00'),
            ('Post B', 1, '1', 50, '2023-01-20 10:00:00'),
            ('Post C', 2, '0', 30, '2023-01-21 11:00:00')
        ");
        $this->realDb->exec("
            INSERT INTO comments (user_id, created_at) VALUES
            (2, '2023-01-22 13:00:00'),
            (2, '2023-01-22 14:00:00')
        ");
        $this->realDb->exec("
            INSERT INTO user_activity_logs (user_id, action_type, action, created_at) VALUES
            (1, 'login', 'login', '2023-01-16 07:00:00'),
            (1, 'view',  'view',  '2023-01-16 07:05:00'),
            (1, 'login', 'login', '2023-01-17 08:00:00'),
            (2, 'login', 'login', '2023-01-18 08:30:00'),
            (2, 'view',  'view',  '2023-01-18 21:00:00')
        ");
    }

    public function testRealDatabaseReturnsAggregatedUserStatistics(): void
    {
        $repository = $this->createRealRepository();
        $this->seedRealDatabase();

        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2023-01-01 00:00:00'),
            new DateTimeImmutable('2023-01-31 23:59:59'),
        );

        $this->assertSame(2, $repository->getNewUsersCount($period));
        $this->assertSame(2, $repository->getTotalUsersCount($period));
        $activity = $repository->getActiveUsersByActivityType($period);
        $this->assertSame(2, $activity['login']);
        $this->assertSame(2, $activity['post']);

        $mostActive = $repository->getMostActiveUsers($period, 10);
        $this->assertSame('alice', $mostActive[0]['username']);
        $byLogins = $repository->getMostActiveUsers($period, 5, 'logins');
        $this->assertNotEmpty($byLogins);
        $byViews = $repository->getMostActiveUsers($period, 5, 'views');
        $this->assertNotEmpty($byViews);
        $byScore = $repository->getMostActiveUsers($period, 5, 'activity_score');
        $this->assertSame(1, $byScore[0]['rank']);

        $engagement = $repository->getUserEngagementStatistics($period);
        $this->assertSame(0, $engagement['inactive']);
        $this->assertGreaterThan(0.0, $engagement['avg_engagement_score']);

        $roles = $repository->getUsersCountByRole($period);
        $this->assertSame(1, $roles['admin']);

        $sources = $repository->getUserRegistrationSources($period);
        $this->assertSame(1, $sources['referral']);

        $geo = $repository->getUserGeographicalDistribution($period);
        $this->assertCount(2, $geo);

        $retention = $repository->getUserRetentionAnalysis($period, 7);
        $this->assertSame(2, $retention['cohort_size']);
        $this->assertSame(100.0, $retention['churn_rate']);

        $trend = $repository->getUserRegistrationTrend(
            new StatisticsPeriod(PeriodType::MONTHLY, new DateTimeImmutable('2023-02-01'), new DateTimeImmutable('2023-02-28 23:59:59')),
            $period,
        );
        $this->assertSame(-2, $trend['growth_count']);

        $this->assertTrue($repository->hasDataForPeriod($period));
    }

    public function testGetUserLoginActivityWithRealDatabase(): void
    {
        $repository = $this->createRealRepository();
        $this->seedRealDatabase();

        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2023-01-01 00:00:00'),
            new DateTimeImmutable('2023-01-31 23:59:59'),
        );

        $loginStats = $repository->getUserLoginActivity($period);

        $this->assertSame(3, $loginStats['total_logins']);
        $this->assertSame(2, $loginStats['unique_users']);
        $this->assertSame(1.5, $loginStats['avg_logins_per_user']);
        $this->assertSame(8, $loginStats['peak_hour']);
        $this->assertArrayHasKey('2-5次', $loginStats['login_frequency_distribution']);
        $this->assertArrayHasKey('1次', $loginStats['login_frequency_distribution']);
    }

    public function testAllMethodsWrapUnexpectedPdoErrors(): void
    {
        $repository = $this->createRealRepository();
        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2023-01-01 00:00:00'),
            new DateTimeImmutable('2023-01-31 23:59:59'),
        );

        // 移除所有資料表讓查詢失敗
        foreach (['user_activity_logs', 'comments', 'posts', 'users'] as $table) {
            $this->realDb->exec("DROP TABLE {$table}");
        }

        $cases = [
            'getActiveUsersCountLogin'   => static fn() => $repository->getActiveUsersCount($period, 'login'),
            'getActiveUsersCountComment' => static fn() => $repository->getActiveUsersCount($period, 'comment'),
            'getNewUsersCount'           => static fn() => $repository->getNewUsersCount($period),
            'getTotalUsersCount'         => static fn() => $repository->getTotalUsersCount($period),
            'getActiveUsersByType'       => static fn() => $repository->getActiveUsersByActivityType($period),
            'getMostActivePosts'         => static fn() => $repository->getMostActiveUsers($period, 10, 'posts'),
            'getMostActiveLogins'        => static fn() => $repository->getMostActiveUsers($period, 10, 'logins'),
            'getMostActiveScore'         => static fn() => $repository->getMostActiveUsers($period, 10, 'activity_score'),
            'getLoginActivity'           => static fn() => $repository->getUserLoginActivity($period),
            'getRegistrationTrend'       => static fn() => $repository->getUserRegistrationTrend($period, $period),
            'getTimeDistributionHour'    => static fn() => $repository->getUserActivityTimeDistribution($period, 'hour'),
            'getTimeDistributionDay'     => static fn() => $repository->getUserActivityTimeDistribution($period, 'day'),
            'getRetentionAnalysis'       => static fn() => $repository->getUserRetentionAnalysis($period, 7),
            'getUsersByRole'             => static fn() => $repository->getUsersCountByRole($period),
            'getEngagement'              => static fn() => $repository->getUserEngagementStatistics($period),
            'getRegistrationSources'     => static fn() => $repository->getUserRegistrationSources($period),
            'getGeographical'            => static fn() => $repository->getUserGeographicalDistribution($period),
            'hasDataForPeriod'           => static fn() => $repository->hasDataForPeriod($period),
            'getActivitySummary'         => static fn() => $repository->getUserActivitySummary($period),
        ];

        foreach ($cases as $name => $invoke) {
            try {
                $invoke();
                $this->fail("{$name} 在資料表不存在時應拋出 RuntimeException");
            } catch (RuntimeException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testEdgeCasesWithEmptyAndPartialData(): void
    {
        // 空資料庫：留存分析與參與度統計應回傳全零結果
        $repository = $this->createRealRepository();

        $emptyPeriod = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2030-01-01 00:00:00'),
            new DateTimeImmutable('2030-01-31 23:59:59'),
        );

        $retention = $repository->getUserRetentionAnalysis($emptyPeriod, 7);
        $this->assertSame(0, $retention['cohort_size']);
        $this->assertSame(0.0, $retention['retention_rate']);

        $engagement = $repository->getUserEngagementStatistics($emptyPeriod);
        $this->assertSame(['high_engagement' => 0, 'medium_engagement' => 0, 'low_engagement' => 0, 'inactive' => 0, 'avg_engagement_score' => 0.0], $engagement);

        // week 分組會組出 YEARWEEK 查詢，在 SQLite 上拋出 PDOException
        $period = new StatisticsPeriod(
            PeriodType::MONTHLY,
            new DateTimeImmutable('2023-01-01 00:00:00'),
            new DateTimeImmutable('2023-01-31 23:59:59'),
        );

        try {
            $repository->getUserActivityTimeDistribution($period, 'week');
            $this->fail('YEARWEEK 在 SQLite 應導致查詢失敗');
        } catch (RuntimeException) {
            $this->addToAssertionCount(1);
        }

        // 摘要中的「最活躍時段」查詢使用 HOUR()，在 SQLite 上必然失敗並被包裝
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('取得使用者活動摘要失敗');
        $repository->getUserActivitySummary($period);
    }
}
