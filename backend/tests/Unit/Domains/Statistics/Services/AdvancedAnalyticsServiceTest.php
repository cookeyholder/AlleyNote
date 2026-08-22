<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Services\AdvancedAnalyticsService;
use App\Domains\Statistics\Services\UserAgentParserService;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 進階分析服務測試.
 */
final class AdvancedAnalyticsServiceTest extends UnitTestCase
{
    private PDO $pdo;

    private UserAgentParserService $userAgentParser;

    private AdvancedAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                user_agent TEXT,
                referrer TEXT,
                view_date DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->userAgentParser = new UserAgentParserService();
        $this->service = new AdvancedAnalyticsService($this->pdo, $this->userAgentParser);
    }

    public function testGetDeviceTypeStatsWithFilters(): void
    {
        $this->seedViews();

        // 無條件過濾
        $statsAll = $this->service->getDeviceTypeStats();
        $this->assertSame(2, $statsAll['Desktop']);
        $this->assertSame(1, $statsAll['Mobile']);
        $this->assertSame(1, $statsAll['Tablet']);
        $this->assertSame(0, $statsAll['Unknown']);

        // 指定 post_id
        $statsPost1 = $this->service->getDeviceTypeStats(1);
        $this->assertSame(1, $statsPost1['Desktop']);
        $this->assertSame(1, $statsPost1['Mobile']);
        $this->assertSame(0, $statsPost1['Tablet']);

        // 指定日期範圍
        $statsDate = $this->service->getDeviceTypeStats(null, '2025-01-01', '2025-01-02');
        $this->assertSame(2, $statsDate['Desktop']);
        $this->assertSame(1, $statsDate['Mobile']);
        $this->assertSame(0, $statsDate['Tablet']);
    }

    public function testGetBrowserStats(): void
    {
        $this->seedViews();

        $stats = $this->service->getBrowserStats();
        $this->assertArrayHasKey('Chrome', $stats);
        $this->assertArrayHasKey('Safari', $stats);
        $this->assertArrayHasKey('Firefox', $stats);

        // 指定條件
        $statsFiltered = $this->service->getBrowserStats(1, '2025-01-01', '2025-01-02');
        $this->assertIsArray($statsFiltered);
    }

    public function testGetOSStats(): void
    {
        $this->seedViews();

        $stats = $this->service->getOSStats();
        $this->assertArrayHasKey('Windows 10', $stats);
        $this->assertArrayHasKey('iOS', $stats);

        // 指定條件
        $statsFiltered = $this->service->getOSStats(2, '2025-01-01', '2025-01-05');
        $this->assertIsArray($statsFiltered);
    }

    public function testGetReferrerStats(): void
    {
        $this->seedViews();

        $stats = $this->service->getReferrerStats();
        $this->assertNotEmpty($stats);
        $this->assertSame('https://google.com', $stats[0]['referrer']);
        $this->assertSame(2, $stats[0]['count']);
        $this->assertEquals(66.67, $stats[0]['percentage']);

        // 包含 post_id 與日期條件
        $statsFiltered = $this->service->getReferrerStats(1, '2025-01-01', '2025-01-02', 5);
        $this->assertCount(2, $statsFiltered);

        // 測試無資料時的百分比
        $this->pdo->exec('DELETE FROM post_views');
        $emptyStats = $this->service->getReferrerStats();
        $this->assertEmpty($emptyStats);
    }

    public function testGetHourlyDistribution(): void
    {
        $this->seedViews();

        $hourly = $this->service->getHourlyDistribution();
        $this->assertCount(24, $hourly);
        $this->assertSame(2, $hourly[10]); // 10:00 與 10:30
        $this->assertSame(1, $hourly[14]);
        $this->assertSame(1, $hourly[20]);
        $this->assertSame(0, $hourly[0]);

        // 測試條件過濾
        $hourlyFiltered = $this->service->getHourlyDistribution(1, '2025-01-01', '2025-01-01');
        $this->assertSame(1, $hourlyFiltered[10]);
        $this->assertSame(1, $hourlyFiltered[14]);
        $this->assertSame(0, $hourlyFiltered[20]);
    }

    public function testGetComprehensiveReport(): void
    {
        $this->seedViews();

        $report = $this->service->getComprehensiveReport();

        $this->assertArrayHasKey('device_types', $report);
        $this->assertArrayHasKey('browsers', $report);
        $this->assertArrayHasKey('operating_systems', $report);
        $this->assertArrayHasKey('top_referrers', $report);
        $this->assertArrayHasKey('hourly_distribution', $report);
        $this->assertSame(4, $report['total_views']);
        $this->assertSame(3, $report['unique_visitors']);

        // 帶過濾條件
        $reportFiltered = $this->service->getComprehensiveReport(1, '2025-01-01', '2025-01-02');
        $this->assertSame(2, $reportFiltered['total_views']);
        $this->assertSame(2, $reportFiltered['unique_visitors']);
    }

    private function seedViews(): void
    {
        $records = [
            [
                'uuid'       => 'u1',
                'post_id'    => 1,
                'user_id'    => 1,
                'user_ip'    => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'referrer'   => 'https://google.com',
                'view_date'  => '2025-01-01 10:00:00',
            ],
            [
                'uuid'       => 'u2',
                'post_id'    => 1,
                'user_id'    => null,
                'user_ip'    => '192.168.1.2',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                'referrer'   => 'https://facebook.com',
                'view_date'  => '2025-01-01 14:00:00',
            ],
            [
                'uuid'       => 'u3',
                'post_id'    => 2,
                'user_id'    => 2,
                'user_ip'    => '192.168.1.1', // 同 IP
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0',
                'referrer'   => 'https://google.com',
                'view_date'  => '2025-01-02 10:30:00',
            ],
            [
                'uuid'       => 'u4',
                'post_id'    => 2,
                'user_id'    => null,
                'user_ip'    => '192.168.1.3',
                'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'referrer'   => '',
                'view_date'  => '2025-01-03 20:00:00',
            ],
        ];

        $stmt = $this->pdo->prepare('
            INSERT INTO post_views (uuid, post_id, user_id, user_ip, user_agent, referrer, view_date)
            VALUES (:uuid, :post_id, :user_id, :user_ip, :user_agent, :referrer, :view_date)
        ');

        foreach ($records as $r) {
            $stmt->execute($r);
        }
    }
}
