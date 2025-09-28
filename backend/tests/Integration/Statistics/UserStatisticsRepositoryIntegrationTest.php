<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\UserStatisticsRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * UserStatisticsRepository 整合測試.
 */
#[Group('integration')]
#[Group('statistics')]
final class UserStatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private UserStatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserStatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new StatisticsTestSeeder($this->db);
        $seeder->seedAll();
    }

    public function testGetActiveUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $loginCount = $this->repository->getActiveUsersCount($period, 'login');
        $postCount = $this->repository->getActiveUsersCount($period, 'post');
        $commentCount = $this->repository->getActiveUsersCount($period, 'comment');
        $allCount = $this->repository->getActiveUsersCount($period);

        $this->assertGreaterThanOrEqual(0, $loginCount);
        $this->assertGreaterThanOrEqual(0, $postCount);
        $this->assertGreaterThanOrEqual(0, $commentCount);
        $this->assertGreaterThanOrEqual(0, $allCount);
    }

    public function testGetActiveUsersCountInvalidType(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->repository->getActiveUsersCount($period, 'invalid_type');
    }

    public function testGetNewUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $newUsersCount = $this->repository->getNewUsersCount($period);
        $this->assertGreaterThanOrEqual(0, $newUsersCount);
    }

    public function testGetTotalUsersCount(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $totalUsersCount = $this->repository->getTotalUsersCount($period);
        $this->assertGreaterThan(0, $totalUsersCount);
    }
}
