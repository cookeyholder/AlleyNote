<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Services\AdvancedAnalyticsService;
use App\Domains\Statistics\Services\StatisticsExportService;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * StatisticsExportService 單元測試.
 *
 * 以 SQLite in-memory 資料庫驗證 CSV 匯出，
 * 並以模擬的 AdvancedAnalyticsService 驗證綜合報告匯出。
 */
final class StatisticsExportServiceTest extends UnitTestCase
{
    private PDO $pdo;

    /** @var AdvancedAnalyticsService&MockInterface */
    private $analyticsService;

    private StatisticsExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema();
        $this->seedData();

        $this->analyticsService = Mockery::mock(AdvancedAnalyticsService::class);
        $this->service = new StatisticsExportService($this->pdo, $this->analyticsService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY,
                title TEXT NOT NULL
            )
        ');
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                username TEXT NOT NULL
            )
        ');
        $this->pdo->exec('
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT,
                user_agent TEXT,
                referrer TEXT,
                view_date DATETIME NOT NULL
            )
        ');
    }

    private function seedData(): void
    {
        $this->pdo->exec("INSERT INTO posts (id, title) VALUES (1, '第一篇公告'), (2, '第二篇公告')");
        $this->pdo->exec("INSERT INTO users (id, username) VALUES (10, 'alice')");
        $stmt = $this->pdo->prepare('
            INSERT INTO post_views (post_id, user_id, user_ip, user_agent, referrer, view_date)
            VALUES (:post_id, :user_id, :user_ip, :user_agent, :referrer, :view_date)
        ');
        $rows = [
            [1, 10, '192.168.1.10', 'Chrome/120.0', 'https://google.com', '2025-01-01 08:00:00'],
            [1, null, '192.168.1.11', 'Safari/17.0', null, '2025-01-02 09:30:00'],
            [2, null, '10.0.0.5', 'Firefox/121.0', 'https://example.com', '2025-01-03 22:15:00'],
        ];
        foreach ($rows as $row) {
            $stmt->execute([
                ':post_id'    => $row[0],
                ':user_id'    => $row[1],
                ':user_ip'    => $row[2],
                ':user_agent' => $row[3],
                ':referrer'   => $row[4],
                ':view_date'  => $row[5],
            ]);
        }
    }

    public function testExportViewsToCSVWithoutFilters(): void
    {
        $csv = $this->service->exportViewsToCSV();

        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertCount(4, $lines);
        $this->assertStringContainsString('文章標題', $lines[0]);
        $this->assertStringContainsString('第一篇公告', implode("\n", $lines));
        $this->assertStringContainsString('alice', implode("\n", $lines));
    }

    public function testExportViewsToCSVWithPostFilter(): void
    {
        $csv = $this->service->exportViewsToCSV(postId: 1);

        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertCount(3, $lines);
    }

    public function testExportViewsToCSVWithStartDate(): void
    {
        $csv = $this->service->exportViewsToCSV(startDate: '2025-01-02');

        // 應排除 2025-01-01 的紀錄（保留 2 筆資料加標頭）
        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertCount(3, $lines);
        $this->assertStringNotContainsString('08:00:00', implode("\n", $lines));
    }

    public function testExportViewsToCSVWithAnonymousUserFallback(): void
    {
        $csv = $this->service->exportViewsToCSV(postId: 2);

        // 匿名瀏覽應以「匿名」作為使用者名稱
        $this->assertStringContainsString('匿名', $csv);
    }

    public function testExportComprehensiveReportToCSV(): void
    {
        $this->analyticsService
            ->shouldReceive('getComprehensiveReport')
            ->once()
            ->with(1, '2025-01-01', '2025-01-31')
            ->andReturn([
                'total_views'         => 1000,
                'unique_visitors'     => 250,
                'device_types'        => ['desktop' => 600, 'mobile' => 400],
                'browsers'            => ['Chrome' => 700, 'Safari' => 300],
                'operating_systems'   => ['Windows 10' => 500, 'iOS' => 500],
                'top_referrers'       => [['referrer' => 'google.com', 'count' => 300, 'percentage' => 30]],
                'hourly_distribution' => ['00' => 10, '01' => 20],
            ]);

        $csv = $this->service->exportComprehensiveReportToCSV(1, '2025-01-01', '2025-01-31');

        $this->assertStringContainsString('統計報告', $csv);
        $this->assertStringContainsString('總瀏覽量', $csv);
        $this->assertStringContainsString('裝置類型統計', $csv);
        $this->assertStringContainsString('desktop', $csv);
        $this->assertStringContainsString('瀏覽器統計', $csv);
        $this->assertStringContainsString('操作系統統計', $csv);
        $this->assertStringContainsString('熱門來源', $csv);
        $this->assertStringContainsString('google.com', $csv);
        $this->assertStringContainsString('時段分布', $csv);
    }

    public function testExportToJSON(): void
    {
        $report = [
            'total_views'         => 42,
            'unique_visitors'     => 7,
            'device_types'        => [],
            'browsers'            => [],
            'operating_systems'   => [],
            'top_referrers'       => [],
            'hourly_distribution' => [],
        ];
        $this->analyticsService
            ->shouldReceive('getComprehensiveReport')
            ->once()
            ->andReturn($report);

        $json = $this->service->exportToJSON();

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($decoded));
        $this->assertSame(42, $decoded['total_views'] ?? null);
        $this->assertSame(7, $decoded['unique_visitors'] ?? null);
    }
}
