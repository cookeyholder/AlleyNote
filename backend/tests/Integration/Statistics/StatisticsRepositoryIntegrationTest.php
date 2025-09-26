<?php

declare(strict_types=1);

namespace Tests\Integration\Statistics;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\StatisticsRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

/**
 * StatisticsRepository 整合測試.
 *
 * 測試統計快照 Repository 的資料庫互動和複雜查詢功能。
 * 使用實際的資料庫連線進行完整的整合測試。
 */
#[Group('statistics')]
#[Group('repository')]
#[Group('integration')]
final class StatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private StatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new StatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new \Tests\Support\Statistics\StatisticsTestSeeder($this->db);
        $seeder->createTables();
    }

    public function testCreateStatisticsSnapshot(): void
    {
        // 建立測試資料
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $snapshot = StatisticsSnapshot::create(
            'overview',
            $period,
            ['total_posts' => 100, 'total_users' => 50],
            ['version' => '1.0'],
            new DateTimeImmutable('+1 day'),
            1000,
            800
        );

        // 執行測試
        $result = $this->repository->save($snapshot);

        // 驗證結果
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertNotNull($result->getId());

        // 從資料庫重新取得並驗證
        $retrieved = $this->repository->findById($result->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('overview', $retrieved->getSnapshotType());
        $this->assertEquals(['total_posts' => 100, 'total_users' => 50], $retrieved->getStatisticsData());
        $this->assertEquals(['version' => '1.0'], $retrieved->getMetadata());
    }

    public function testFindByTypeAndPeriod(): void
    {
        // 建立並儲存測試快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $snapshot = StatisticsSnapshot::create(
            'posts',
            $period,
            ['daily_posts' => 25],
            ['source' => 'test'],
            null,
            500,
            300
        );

        $this->repository->save($snapshot);

        // 測試查詢
        $found = $this->repository->findByTypeAndPeriod('posts', $period);

        // 驗證結果
        $this->assertNotNull($found);
        $this->assertEquals('posts', $found->getSnapshotType());
        $this->assertEquals(['daily_posts' => 25], $found->getStatisticsData());
        $this->assertEquals(500, $found->getTotalViews());
        $this->assertEquals(300, $found->getTotalUniqueViewers());
    }

    public function testFindLatestByType(): void
    {
        // 建立多個同類型快照，時間不同
        $periods = [
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-01 00:00:00'),
                new DateTimeImmutable('2024-01-01 23:59:59')
            ),
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-02 00:00:00'),
                new DateTimeImmutable('2024-01-02 23:59:59')
            ),
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-03 00:00:00'),
                new DateTimeImmutable('2024-01-03 23:59:59')
            ),
        ];

        foreach ($periods as $i => $period) {
            $snapshot = StatisticsSnapshot::create(
                'users',
                $period,
                ['active_users' => 100 + $i * 10],
                ['day' => $i + 1],
                null,
                1000 + $i * 100,
                800 + $i * 50
            );

            $this->repository->save($snapshot);

            // 確保建立時間有差異
            usleep(1000);
        }

        // 測試取得最新的快照
        $latest = $this->repository->findLatestByType('users');

        // 驗證結果 - 應該是最後建立的（第三個）
        $this->assertNotNull($latest);
        $this->assertEquals(['active_users' => 120], $latest->getStatisticsData());
        $this->assertEquals(['day' => 3], $latest->getMetadata());
        $this->assertEquals(1200, $latest->getTotalViews());
        $this->assertEquals(900, $latest->getTotalUniqueViewers());
    }

    public function testFindByTypeAndDateRange(): void
    {
        // 建立多天的快照
        $dates = ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05'];

        foreach ($dates as $date) {
            $period = new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable($date . ' 00:00:00'),
                new DateTimeImmutable($date . ' 23:59:59')
            );

            $snapshot = StatisticsSnapshot::create(
                'popular',
                $period,
                ['top_posts' => [1, 2, 3]],
                ['date' => $date],
                null,
                500,
                300
            );

            $this->repository->save($snapshot);
        }

        // 查詢特定範圍的快照
        $startDate = new DateTimeImmutable('2024-01-02 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-04 23:59:59');

        $snapshots = $this->repository->findByTypeAndDateRange('popular', $startDate, $endDate);

        // 驗證結果
        $this->assertCount(3, $snapshots); // 應該有 3 天的資料

        // 驗證日期順序
        $expectedDates = ['2024-01-02', '2024-01-03', '2024-01-04'];
        foreach ($snapshots as $i => $snapshot) {
            $metadata = $snapshot->getMetadata();
            $this->assertEquals($expectedDates[$i], $metadata['date']);
            $this->assertEquals(['top_posts' => [1, 2, 3]], $snapshot->getStatisticsData());
        }
    }

    public function testExistsMethod(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-07 23:59:59')
        );

        // 測試不存在的情況
        $this->assertFalse($this->repository->exists('weekly_overview', $period));

        // 建立快照
        $snapshot = StatisticsSnapshot::create(
            'weekly_overview',
            $period,
            ['week_stats' => 'data'],
            [],
            null,
            2000,
            1500
        );

        $this->repository->save($snapshot);

        // 測試存在的情況
        $this->assertTrue($this->repository->exists('weekly_overview', $period));
    }

    public function testUpdateStatisticsSnapshot(): void
    {
        // 建立並儲存初始快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $snapshot = StatisticsSnapshot::create(
            'posts',
            $period,
            ['daily_posts' => 25],
            ['source' => 'test'],
            null,
            500,
            300
        );

        $this->repository->save($snapshot);
        $originalId = $snapshot->getId();

        // 更新快照資料
        $updatedSnapshot = StatisticsSnapshot::createFromExisting(
            $snapshot,
            ['updated' => 'data'],
            ['version' => '2.0'],
            200,
            150
        );

        $result = $this->repository->save($updatedSnapshot);

        // 驗證更新結果
        $this->assertTrue($result);
        $this->assertEquals($originalId, $updatedSnapshot->getId());

        // 從資料庫重新取得並驗證
        $retrieved = $this->repository->findById($originalId);
        $this->assertNotNull($retrieved);
        $this->assertEquals(['updated' => 'data'], $retrieved->getStatisticsData());
        $this->assertEquals(['version' => '2.0'], $retrieved->getMetadata());
        $this->assertEquals(200, $retrieved->getTotalViews());
        $this->assertEquals(150, $retrieved->getTotalUniqueViewers());
    }

    public function testDeleteStatisticsSnapshot(): void
    {
        // 建立並儲存快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $snapshot = StatisticsSnapshot::create(
            'posts',
            $period,
            ['temp' => 'data'],
            [],
            null,
            50,
            30
        );

        $this->repository->save($snapshot);
        $snapshotId = $snapshot->getId();

        // 驗證快照存在
        $this->assertNotNull($this->repository->findById($snapshotId));

        // 刪除快照
        $result = $this->repository->delete($snapshot);

        // 驗證刪除結果
        $this->assertTrue($result);
        $this->assertNull($this->repository->findById($snapshotId));
    }

    public function testFindAndDeleteExpiredSnapshots(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        // 建立已過期的快照
        $expiredPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            $yesterday->modify('-1 day'),
            $yesterday
        );

        $expiredSnapshot = StatisticsSnapshot::create(
            'posts',
            $expiredPeriod,
            ['expired' => true],
            [],
            $yesterday, // 設定過期時間為昨天
            10,
            5
        );

        // 建立未過期的快照
        $validPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            $now,
            $now->setTime(23, 59, 59)
        );

        $validSnapshot = StatisticsSnapshot::create(
            'posts',
            $validPeriod,
            ['valid' => true],
            [],
            $tomorrow, // 設定過期時間為明天
            20,
            15
        );

        // 儲存快照
        $this->repository->save($expiredSnapshot);
        $this->repository->save($validSnapshot);

        // 查詢過期的快照
        $expiredSnapshots = $this->repository->findExpiredBefore($now);
        $this->assertCount(1, $expiredSnapshots);
        $this->assertEquals(['expired' => true], $expiredSnapshots[0]->getStatisticsData());

        // 刪除過期的快照
        $deletedCount = $this->repository->deleteExpiredBefore($now);
        $this->assertEquals(1, $deletedCount);

        // 驗證過期快照已被刪除，有效快照仍存在
        $remainingExpired = $this->repository->findExpiredBefore($now);
        $this->assertCount(0, $remainingExpired);

        $validStillExists = $this->repository->findById($validSnapshot->getId());
        $this->assertNotNull($validStillExists);
    }

    public function testComplexJSONDataHandling(): void
    {
        // 測試複雜的 JSON 資料處理
        $complexData = [
            'overview' => [
                'totals' => [
                    'posts' => 1000,
                    'users' => 500,
                    'views' => 50000,
                ],
                'trends' => [
                    'daily_growth' => 2.5,
                    'weekly_growth' => 15.2,
                ],
                'top_categories' => [
                    ['name' => '技術', 'count' => 300],
                    ['name' => '生活', 'count' => 200],
                    ['name' => '旅遊', 'count' => 150],
                ],
            ],
            'meta' => [
                'calculation_time' => '2024-01-01T12:00:00Z',
                'version' => '2.1.0',
                'source' => 'automated',
            ],
        ];

        $complexMetadata = [
            'calculation' => [
                'duration_ms' => 1500,
                'memory_peak_mb' => 128,
                'queries_executed' => 25,
            ],
            'quality' => [
                'completeness' => 98.5,
                'accuracy_score' => 95.2,
            ],
        ];

        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59')
        );

        $snapshot = StatisticsSnapshot::create(
            'posts',
            $period,
            $complexData,
            $complexMetadata,
            null,
            75000,
            45000
        );

        // 儲存並重新取得
        $this->repository->save($snapshot);
        $retrieved = $this->repository->findById($snapshot->getId());

        // 驗證複雜資料完整性
        $this->assertNotNull($retrieved);
        $this->assertEquals($complexData, $retrieved->getStatisticsData());
        $this->assertEquals($complexMetadata, $retrieved->getMetadata());

        // 驗證特定巢狀資料
        $retrievedData = $retrieved->getStatisticsData();
        $this->assertEquals(1000, $retrievedData['overview']['totals']['posts']);
        $this->assertEquals(2.5, $retrievedData['overview']['trends']['daily_growth']);
        $this->assertEquals('技術', $retrievedData['overview']['top_categories'][0]['name']);

        $retrievedMeta = $retrieved->getMetadata();
        $this->assertEquals(1500, $retrievedMeta['calculation']['duration_ms']);
        $this->assertEquals(98.5, $retrievedMeta['quality']['completeness']);
    }

}
