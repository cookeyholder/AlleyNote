<?php

declare(strict_types=1);

namespace Tests\Integration\Performance;

use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use DateTimeImmutable;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Tests\Support\Traits\DatabaseTestTrait;

/**
 * 統計索引最佳化效能測試.
 *
 * 測試索引建立前後的查詢效能差異，確保索引最佳化有效。
 */
final class StatisticsIndexOptimizationTest extends TestCase
{
    use DatabaseTestTrait;

    private PDO $connection;

    private PostStatisticsRepository $postStatisticsRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->connection = $this->db;
        $this->postStatisticsRepository = new PostStatisticsRepository($this->connection);
        $this->createPostsTableWithData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownDatabase();
    }

    public function test統計查詢在無索引情況下的基準效能(): void
    {
        // 建立測試資料（不包含最佳化索引）
        $this->createBasicPostsTable();
        $this->insertTestData(1000); // 插入 1000 筆測試資料

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );        // 測量查詢時間
        $startTime = microtime(true);

        $result = $this->postStatisticsRepository->getPostsCountBySource($period);

        $executionTime = microtime(true) - $startTime;

        // 記錄基準效能
        echo "\n無索引基準效能 - 來源統計查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));

        // 基準效能應該存在（>0ms），為後續比較提供基準
        $this->assertGreaterThan(0, $executionTime, '基準查詢時間應該 > 0ms');
    }

    public function test統計查詢在最佳化索引後的效能提升(): void
    {
        // 建立包含最佳化索引的表
        $this->createOptimizedPostsTable();
        $this->insertTestData(1000); // 插入相同數量的測試資料

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );        // 測量查詢時間
        $startTime = microtime(true);

        $result = $this->postStatisticsRepository->getPostsCountBySource($period);

        $executionTime = microtime(true) - $startTime;

        // 記錄最佳化後效能
        echo "\n最佳化索引效能 - 來源統計查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));

        // 最佳化後的效能應該顯著提升（<5ms）
        $this->assertLessThan(0.005, $executionTime, '最佳化後查詢時間應該 < 5ms');
    }

    public function test狀態統計查詢的索引效能(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(2000);

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );

        $startTime = microtime(true);
        $result = $this->postStatisticsRepository->getPostsCountByStatus($period);
        $executionTime = microtime(true) - $startTime;

        echo "\n狀態統計查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertLessThan(0.01, $executionTime, '狀態統計查詢應該 < 10ms');
    }

    public function test熱門文章查詢的索引效能(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(5000); // 更多資料來測試效能

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );

        $startTime = microtime(true);
        $result = $this->postStatisticsRepository->getPopularPosts($period, 10, 'views');
        $executionTime = microtime(true) - $startTime;

        echo "\n熱門文章查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
        $this->assertLessThan(0.02, $executionTime, '熱門文章查詢應該 < 20ms');
    }

    public function test使用者文章統計的索引效能(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(3000);

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );

        $startTime = microtime(true);
        $result = $this->postStatisticsRepository->getPostsCountByUser($period, 10);
        $executionTime = microtime(true) - $startTime;

        echo "\n使用者文章統計查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));
        $this->assertLessThan(0.015, $executionTime, '使用者統計查詢應該 < 15ms');
    }

    public function test時間分佈統計的索引效能(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(4000);

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );

        $startTime = microtime(true);
        $result = $this->postStatisticsRepository->getPostsPublishTimeDistribution($period, 'month');
        $executionTime = microtime(true) - $startTime;

        echo "\n時間分佈統計查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertLessThan(0.02, $executionTime, '時間分佈統計查詢應該 < 20ms');
    }

    public function test複雜活動摘要查詢的綜合效能(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(5000);

        $period = new StatisticsPeriod(
            PeriodType::YEARLY,
            new DateTimeImmutable('2025-01-01 00:00:00'),
            new DateTimeImmutable('2025-12-31 23:59:59'),
        );

        $startTime = microtime(true);
        $result = $this->postStatisticsRepository->getPostActivitySummary($period);
        $executionTime = microtime(true) - $startTime;

        echo "\n活動摘要查詢時間: " . ($executionTime * 1000) . " ms\n";

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_posts', $result);
        $this->assertArrayHasKey('popular_sources', $result);
        $this->assertLessThan(0.05, $executionTime, '複雜摘要查詢應該 < 50ms');
    }

    public function test查詢執行計劃分析(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(1000);

        // 分析最常用的統計查詢的執行計劃
        $queries = [
            'posts_by_source' => "SELECT creation_source, COUNT(*) as count FROM posts WHERE created_at >= '2025-01-01' AND created_at <= '2025-12-31' GROUP BY creation_source",
            'posts_by_status' => "SELECT status, COUNT(*) as count FROM posts WHERE created_at >= '2025-01-01' AND created_at <= '2025-12-31' GROUP BY status",
            'popular_posts' => "SELECT id, title, views FROM posts WHERE created_at >= '2025-01-01' AND created_at <= '2025-12-31' AND status = 'published' ORDER BY views DESC LIMIT 10",
        ];

        foreach ($queries as $queryName => $sql) {
            $explainSql = 'EXPLAIN QUERY PLAN ' . $sql;

            try {
                $stmt = $this->connection->prepare($explainSql);
                $stmt->execute();
                $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "\n=== {$queryName} 執行計劃 ===\n";
                foreach ($plan as $step) {
                    echo sprintf(
                        "ID: %s, Parent: %s, Detail: %s\n",
                        $step['id'],
                        $step['parent'],
                        $step['detail'],
                    );
                }

                // 檢查是否使用了索引（SQLite 特有）
                $planText = implode(' ', array_column($plan, 'detail'));
                $this->assertStringContainsString('INDEX', $planText, "{$queryName} 應該使用索引");
            } catch (PDOException $e) {
                $this->markTestSkipped('無法分析查詢計劃: ' . $e->getMessage());
            }
        }
    }

    private function createBasicPostsTable(): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS posts');

        $sql = "CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT NOT NULL UNIQUE,
            seq_number INTEGER NOT NULL UNIQUE,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            user_ip TEXT,
            views INTEGER DEFAULT 0 NOT NULL,
            is_pinned BOOLEAN DEFAULT 0 NOT NULL,
            status TEXT DEFAULT 'draft' NOT NULL,
            creation_source TEXT DEFAULT 'web' NOT NULL,
            creation_source_detail TEXT,
            publish_date DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            deleted_at DATETIME
        )";

        $this->connection->exec($sql);

        // 只建立基本的唯一索引，不包含效能最佳化索引
        $this->connection->exec('CREATE UNIQUE INDEX idx_posts_uuid ON posts(uuid)');
        $this->connection->exec('CREATE UNIQUE INDEX idx_posts_seq_number ON posts(seq_number)');
    }

    private function createOptimizedPostsTable(): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS posts');

        $sql = "CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT NOT NULL UNIQUE,
            seq_number INTEGER NOT NULL UNIQUE,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            user_ip TEXT,
            views INTEGER DEFAULT 0 NOT NULL,
            is_pinned BOOLEAN DEFAULT 0 NOT NULL,
            status TEXT DEFAULT 'draft' NOT NULL,
            creation_source TEXT DEFAULT 'web' NOT NULL,
            creation_source_detail TEXT,
            publish_date DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            deleted_at DATETIME
        )";

        $this->connection->exec($sql);

        // 建立最佳化的複合索引
        $optimizedIndexes = [
            // 基本唯一索引
            'CREATE UNIQUE INDEX idx_posts_uuid ON posts(uuid)',
            'CREATE UNIQUE INDEX idx_posts_seq_number ON posts(seq_number)',

            // 統計查詢最佳化索引
            'CREATE INDEX idx_posts_created_status ON posts(created_at, status)',
            'CREATE INDEX idx_posts_created_source ON posts(created_at, creation_source)',
            'CREATE INDEX idx_posts_created_user ON posts(created_at, user_id)',
            'CREATE INDEX idx_posts_status_views ON posts(status, views DESC)',
            'CREATE INDEX idx_posts_created_pinned ON posts(created_at, is_pinned)',
            'CREATE INDEX idx_posts_source_status ON posts(creation_source, status)',

            // 瀏覽量和熱門內容查詢索引
            'CREATE INDEX idx_posts_views_created ON posts(views DESC, created_at DESC)',
            'CREATE INDEX idx_posts_status_created ON posts(status, created_at)',

            // 使用者相關查詢索引
            'CREATE INDEX idx_posts_user_created ON posts(user_id, created_at)',

            // 時間範圍查詢索引
            'CREATE INDEX idx_posts_created_at ON posts(created_at)',

            // 發佈狀態相關索引
            'CREATE INDEX idx_posts_publish_date ON posts(publish_date)',
        ];

        foreach ($optimizedIndexes as $indexSql) {
            $this->connection->exec($indexSql);
        }
    }

    private function insertTestData(int $count): void
    {
        $statuses = ['draft', 'published', 'archived'];
        $sources = ['web', 'api', 'mobile', 'admin', 'import'];

        $stmt = $this->connection->prepare('
            INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, views,
                              is_pinned, status, creation_source, created_at, publish_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        for ($i = 1; $i <= $count; $i++) {
            $createdAt = $this->generateRandomDateTime();
            $publishDate = rand(0, 1) ? $createdAt : null;

            $stmt->execute([
                'uuid-' . $i, // uuid
                $i, // seq_number
                "測試文章 {$i}", // title
                '這是測試文章內容 ' . str_repeat('內容 ', rand(10, 100)), // content
                rand(1, 50), // user_id
                $this->generateRandomIp(), // user_ip
                rand(0, 1000), // views
                rand(0, 10) === 0 ? 1 : 0, // is_pinned (10% 機率置頂)
                $statuses[array_rand($statuses)], // status
                $sources[array_rand($sources)], // creation_source
                $createdAt, // created_at
                $publishDate, // publish_date
            ]);
        }
    }

    private function generateRandomDateTime(): string
    {
        $start = strtotime('2025-01-01');
        $end = strtotime('2025-12-31');
        $randomTime = rand($start, $end);

        return date('Y-m-d H:i:s', $randomTime);
    }

    private function generateRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    private function createPostsTableWithData(): void
    {
        $this->createOptimizedPostsTable();
        $this->insertTestData(100); // 建立基本測試資料
    }

    /**
     * 產生測試 UUID.
     */
    private function generateTestUuid(): string
    {
        return 'test-uuid-' . uniqid();
    }

    /**
     * 產生隨機字串.
     */
    private function generateRandomString(int $length = 10): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
    }

    /**
     * 產生測試電子郵件.
     */
    private function generateTestEmail(): string
    {
        return 'test' . $this->generateRandomString(6) . '@example.com';
    }
}
