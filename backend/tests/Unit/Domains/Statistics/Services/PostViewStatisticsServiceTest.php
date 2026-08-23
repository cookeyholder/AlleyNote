<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Services;

use App\Domains\Statistics\Services\PostViewStatisticsService;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 文章瀏覽統計服務測試.
 */
final class PostViewStatisticsServiceTest extends UnitTestCase
{
    private PDO $pdo;

    private PostViewStatisticsService $service;

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

        $this->service = new PostViewStatisticsService($this->pdo);
    }

    public function testGetPostViewStatsReturnsZeroWhenNoViews(): void
    {
        $stats = $this->service->getPostViewStats(999);
        $this->assertSame(0, $stats['views']);
        $this->assertSame(0, $stats['unique_visitors']);
    }

    public function testGetPostViewStatsReturnsCorrectCount(): void
    {
        $this->service->recordView(1, 10, '192.168.1.1', 'UA1', 'ref1');
        $this->service->recordView(1, 20, '192.168.1.1', 'UA1', 'ref1'); // 同 IP
        $this->service->recordView(1, null, '192.168.1.2', 'UA2', 'ref2');

        $stats = $this->service->getPostViewStats(1);
        $this->assertSame(3, $stats['views']);
        $this->assertSame(2, $stats['unique_visitors']);
    }

    public function testGetBatchPostViewStatsWithEmptyArray(): void
    {
        $stats = $this->service->getBatchPostViewStats([]);
        $this->assertEmpty($stats);
    }

    public function testGetBatchPostViewStatsWithMultiplePosts(): void
    {
        $this->service->recordView(1, 10, '192.168.1.1', 'UA1');
        $this->service->recordView(1, null, '192.168.1.2', 'UA2');
        $this->service->recordView(2, 20, '192.168.1.1', 'UA1');

        $stats = $this->service->getBatchPostViewStats([1, 2, 3]);

        $this->assertArrayHasKey(1, $stats);
        $this->assertSame(2, $stats[1]['views']);
        $this->assertSame(2, $stats[1]['unique_visitors']);

        $this->assertArrayHasKey(2, $stats);
        $this->assertSame(1, $stats[2]['views']);
        $this->assertSame(1, $stats[2]['unique_visitors']);

        // 未有瀏覽記錄的文章補零
        $this->assertArrayHasKey(3, $stats);
        $this->assertSame(0, $stats[3]['views']);
        $this->assertSame(0, $stats[3]['unique_visitors']);
    }

    public function testRecordViewGeneratesValidRecord(): void
    {
        $success = $this->service->recordView(
            postId: 42,
            userId: 5,
            userIp: '10.0.0.1',
            userAgent: 'CustomAgent/1.0',
            referrer: 'https://example.com',
        );

        $this->assertTrue($success);

        $stmt = $this->pdo->query('SELECT * FROM post_views WHERE post_id = 42');
        $this->assertNotFalse($stmt);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertIsInt($row['post_id']);
        $this->assertIsInt($row['user_id']);
        $this->assertSame(42, $row['post_id']);
        $this->assertSame(5, $row['user_id']);
        $this->assertSame('10.0.0.1', $row['user_ip']);
        $this->assertSame('CustomAgent/1.0', $row['user_agent']);
        $this->assertSame('https://example.com', $row['referrer']);
        $this->assertNotEmpty($row['uuid']);
        $this->assertIsString($row['uuid']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $row['uuid'],
        );
    }
}
