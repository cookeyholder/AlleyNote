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
use Tests\Support\Statistics\StatisticsTestSeeder;

/**
 * StatisticsRepository 整合測試.
 *
 * 測試統計快照 Repository 的資料庫互動功能，包括：
 * - 基本 CRUD 操作
 * - 複雜查詢功能
 * - JSON 資料處理
 * - 過期資料管理
 * - 分頁和排序
 * - 資料完整性驗證
 */
#[Group('integration')]
#[Group('statistics')]
final class StatisticsRepositoryIntegrationTest extends IntegrationTestCase
{
    private StatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new StatisticsRepository($this->db);

        // 使用統一的測試資料種子
        $seeder = new StatisticsTestSeeder($this->db);
        $seeder->createTables();
    }

    public function testCreateAndSaveSnapshot(): void
    {
        // 建立測試資料
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            ['total_posts' => 150],
            ['version' => '1.0'],
            new DateTimeImmutable('+1 day'),
        );

        // 執行測試
        $result = $this->repository->save($snapshot);

        // 驗證結果
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);

        // 從資料庫重新取得並驗證
        $retrieved = $this->repository->findById($result->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('overview', $retrieved->getSnapshotType());
        $this->assertEquals(['total_posts' => 150], $retrieved->getStatisticsData());
        $this->assertEquals(['version' => '1.0'], $retrieved->getMetadata());
    }

    public function testFindByTypeAndPeriod(): void
    {
        // 建立並儲存測試快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_POSTS,
            $period,
            ['daily_posts' => 25],
            ['source' => 'test'],
            null,
        );

        $this->repository->save($snapshot);

        // 測試查詢
        $found = $this->repository->findByTypeAndPeriod(StatisticsSnapshot::TYPE_POSTS, $period);

        // 驗證結果
        $this->assertNotNull($found);
        $this->assertEquals(StatisticsSnapshot::TYPE_POSTS, $found->getSnapshotType());
        $this->assertEquals(['daily_posts' => 25], $found->getStatisticsData());
    }

    public function testFindLatestByType(): void
    {
        // 建立多個同類型快照，時間不同
        $periods = [
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-01 00:00:00'),
                new DateTimeImmutable('2024-01-01 23:59:59'),
            ),
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-02 00:00:00'),
                new DateTimeImmutable('2024-01-02 23:59:59'),
            ),
            new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable('2024-01-03 00:00:00'),
                new DateTimeImmutable('2024-01-03 23:59:59'),
            ),
        ];

        foreach ($periods as $i => $period) {
            $snapshot = StatisticsSnapshot::create(
                StatisticsSnapshot::TYPE_USERS,
                $period,
                ['active_users' => 100 + $i * 10],
                ['day' => $i + 1],
                null,
            );

            $this->repository->save($snapshot);
            // 添加小延遲以確保時間排序正確
            usleep(1000); // 1 毫秒
        }

        // 查詢最新的快照
        $latest = $this->repository->findLatestByType(StatisticsSnapshot::TYPE_USERS);

        // 驗證結果（應該是最後建立的，即第三個）
        $this->assertNotNull($latest);
        $this->assertEquals(StatisticsSnapshot::TYPE_USERS, $latest->getSnapshotType());
        $this->assertEquals(['active_users' => 120], $latest->getStatisticsData());
        $this->assertEquals(['day' => 3], $latest->getMetadata());
    }

    public function testFindByTypeAndDateRange(): void
    {
        // 建立多個不同日期的快照
        $dates = ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05'];

        foreach ($dates as $i => $date) {
            $period = new StatisticsPeriod(
                PeriodType::DAILY,
                new DateTimeImmutable($date . ' 00:00:00'),
                new DateTimeImmutable($date . ' 23:59:59'),
            );

            $snapshot = StatisticsSnapshot::create(
                StatisticsSnapshot::TYPE_SOURCES,
                $period,
                ['sources_count' => ($i + 1) * 5],
                ['date' => $date],
                null,
            );

            $this->repository->save($snapshot);
        }

        // 查詢特定日期範圍
        $startDate = new DateTimeImmutable('2024-01-02 00:00:00');
        $endDate = new DateTimeImmutable('2024-01-04 23:59:59');

        $snapshots = $this->repository->findByTypeAndDateRange(
            StatisticsSnapshot::TYPE_SOURCES,
            $startDate,
            $endDate,
        );

        // 驗證結果（應該有 3 個快照：01-02, 01-03, 01-04）
        $this->assertCount(3, $snapshots);
        $this->assertEquals(['sources_count' => 10], $snapshots[0]->getStatisticsData());
        $this->assertEquals(['sources_count' => 15], $snapshots[1]->getStatisticsData());
        $this->assertEquals(['sources_count' => 20], $snapshots[2]->getStatisticsData());
    }

    public function testFindNonExistentSnapshot(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::WEEKLY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-07 23:59:59'),
        );

        // 測試不存在的情況
        $result = $this->repository->findByTypeAndPeriod('non_existent', $period);
        $this->assertNull($result);

        // 建立測試快照
        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_POPULAR,
            $period,
            ['popular_posts' => 50],
            [],
            null,
        );

        $this->repository->save($snapshot);

        // 驗證可以找到
        $found = $this->repository->findByTypeAndPeriod(StatisticsSnapshot::TYPE_POPULAR, $period);
        $this->assertNotNull($found);
    }

    public function testUpdateSnapshot(): void
    {
        // 建立原始快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            ['total_posts' => 100],
            ['source' => 'test'],
            null,
        );

        $this->repository->save($snapshot);

        // 更新資料
        $snapshot->updateStatistics(['total_posts' => 150]);
        $snapshot->updateMetadata(['updated' => 'data', 'version' => '2.0']);

        $result = $this->repository->update($snapshot);

        // 驗證更新結果
        $this->assertInstanceOf(StatisticsSnapshot::class, $result);
        $this->assertEquals(150, $result->getStatistic('total_posts'));
        $this->assertEquals('data', $result->getMetadata()['updated']);
        $this->assertEquals('2.0', $result->getMetadata()['version']);

        // 從資料庫重新驗證
        $retrieved = $this->repository->findById($result->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals(150, $retrieved->getStatistic('total_posts'));
        $this->assertEquals('data', $retrieved->getMetadata()['updated']);
    }

    public function testDeleteSnapshot(): void
    {
        // 建立測試快照
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            ['total_posts' => 50],
            [],
            null,
        );

        $this->repository->save($snapshot);
        $snapshotId = $snapshot->getId();

        // 執行刪除
        $deleted = $this->repository->delete($snapshot);
        $this->assertTrue($deleted);

        // 驗證已刪除
        $found = $this->repository->findById($snapshotId);
        $this->assertNull($found);
    }

    public function testExpiredSnapshotsManagement(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');

        // 建立過期的快照
        $expiredPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            $yesterday->modify('-1 day'),
            $yesterday,
        );

        $expiredSnapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $expiredPeriod,
            ['total_posts' => 10],
            [],
            $yesterday, // 設定過期時間為昨天
        );

        // 建立未過期的快照
        $validPeriod = new StatisticsPeriod(
            PeriodType::DAILY,
            $now,
            $now->setTime(23, 59, 59),
        );

        $validSnapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $validPeriod,
            ['total_posts' => 20],
            [],
            $tomorrow, // 設定過期時間為明天
        );

        // 儲存快照
        $this->repository->save($expiredSnapshot);
        $this->repository->save($validSnapshot);

        // 測試查詢過期快照
        $expiredSnapshots = $this->repository->findExpiredSnapshots($now);
        $this->assertCount(1, $expiredSnapshots);
        $this->assertEquals(10, $expiredSnapshots[0]->getStatistic('total_posts'));

        // 測試刪除過期快照
        $deletedCount = $this->repository->deleteExpiredSnapshots($now);
        $this->assertEquals(1, $deletedCount);

        // 驗證只剩下有效快照
        $expiredSnapshotsAfter = $this->repository->findExpiredSnapshots($now);
        $this->assertCount(0, $expiredSnapshotsAfter);
    }

    public function testExists(): void
    {
        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        // 測試不存在的情況
        $exists = $this->repository->exists(StatisticsSnapshot::TYPE_OVERVIEW, $period);
        $this->assertFalse($exists);

        // 建立快照
        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            ['total_posts' => 100],
            [],
            null,
        );

        $this->repository->save($snapshot);

        // 測試存在的情況
        $exists = $this->repository->exists(StatisticsSnapshot::TYPE_OVERVIEW, $period);
        $this->assertTrue($exists);
    }

    public function testComplexJsonDataHandling(): void
    {
        // 建立包含複雜資料結構的測試資料
        $complexMetadata = [
            'calculation' => [
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 10:05:00',
                'duration_ms' => 1500,
            ],
            'quality' => [
                'completeness' => 98.5,
                'accuracy' => 99.2,
            ],
            'source_breakdown' => [
                'api' => 150,
                'web' => 200,
                'mobile' => 75,
            ],
            'trends' => [
                'daily_growth' => 5.2,
                'weekly_growth' => 12.8,
            ],
            'top_categories' => [
                ['name' => 'technology', 'count' => 45],
                ['name' => 'business', 'count' => 38],
                ['name' => 'science', 'count' => 29],
            ],
        ];

        $period = new StatisticsPeriod(
            PeriodType::DAILY,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable('2024-01-01 23:59:59'),
        );

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_OVERVIEW,
            $period,
            [
                'posts' => ['totals' => 425],
                'trends' => ['daily_growth' => 5.2],
            ],
            $complexMetadata,
            null,
        );

        // 儲存並重新取得
        $saved = $this->repository->save($snapshot);
        $retrieved = $this->repository->findById($saved->getId());

        // 驗證複雜資料完整性
        $this->assertNotNull($retrieved);
        $retrievedMeta = $retrieved->getMetadata();
        $retrievedStats = $retrieved->getStatisticsData();

        // 安全存取陣列元素
        $this->assertEquals(425, is_array($retrievedStats['posts']) && isset($retrievedStats['posts']['totals']) ? $retrievedStats['posts']['totals'] : 0);
        $this->assertEquals(5.2, is_array($retrievedStats['trends']) && isset($retrievedStats['trends']['daily_growth']) ? $retrievedStats['trends']['daily_growth'] : 0);

        if (is_array($retrievedMeta['top_categories']) && isset($retrievedMeta['top_categories'][0]) && is_array($retrievedMeta['top_categories'][0])) {
            $this->assertEquals('technology', $retrievedMeta['top_categories'][0]['name'] ?? '');
            $this->assertEquals(45, $retrievedMeta['top_categories'][0]['count'] ?? 0);
        }

        $this->assertEquals(1500, is_array($retrievedMeta['calculation']) && isset($retrievedMeta['calculation']['duration_ms']) ? $retrievedMeta['calculation']['duration_ms'] : 0);
        $this->assertEquals(98.5, is_array($retrievedMeta['quality']) && isset($retrievedMeta['quality']['completeness']) ? $retrievedMeta['quality']['completeness'] : 0);
    }
}
