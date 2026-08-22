<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Infrastructure\Statistics\Services\SlowQueryMonitoringService;
use App\Infrastructure\Statistics\Services\StatisticsPerformanceReportGenerator;
use InvalidArgumentException;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 統計效能報告產生器測試.
 */
final class StatisticsPerformanceReportGeneratorTest extends UnitTestCase
{
    private PDO $pdo;

    private SlowQueryMonitoringService $monitoringService;

    private StatisticsPerformanceReportGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                content TEXT,
                views INTEGER DEFAULT 0,
                status TEXT DEFAULT 'published',
                creation_source TEXT DEFAULT 'web',
                user_id INTEGER DEFAULT 1,
                is_pinned INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_posts_source ON posts(creation_source);
            CREATE INDEX idx_posts_status ON posts(status);
            CREATE INDEX idx_posts_user_id ON posts(user_id);
            CREATE INDEX idx_posts_created_at ON posts(created_at);

            CREATE TABLE statistics_slow_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                query_sql TEXT,
                execution_time REAL NOT NULL,
                result_count INTEGER DEFAULT 0,
                query_params TEXT,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE statistics_query_performance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                execution_time REAL NOT NULL,
                result_count INTEGER NOT NULL,
                created_at DATETIME NOT NULL
            );
            CREATE TABLE statistics_failed_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                query_hash TEXT NOT NULL,
                query_type TEXT NOT NULL,
                query_sql TEXT,
                execution_time REAL NOT NULL,
                error_message TEXT,
                created_at DATETIME NOT NULL
            );
        ");

        // 插入測試文章資料
        $stmt = $this->pdo->prepare('
            INSERT INTO posts (title, content, views, status, creation_source, user_id, is_pinned, created_at)
            VALUES (:title, :content, :views, :status, :source, :user_id, :is_pinned, :created_at)
        ');

        for ($i = 1; $i <= 10; $i++) {
            $stmt->execute([
                'title'      => "Post {$i}",
                'content'    => "Content {$i}",
                'views'      => $i * 10,
                'status'     => $i % 2 === 0 ? 'published' : 'draft',
                'source'     => 'web',
                'user_id'    => ($i % 3) + 1,
                'is_pinned'  => $i % 5 === 0 ? 1 : 0,
                'created_at' => '2025-06-15 10:00:00',
            ]);
        }

        $this->monitoringService = new SlowQueryMonitoringService($this->pdo);
        $this->generator = new StatisticsPerformanceReportGenerator($this->pdo, $this->monitoringService);
    }

    public function testGenerateCompleteReportSuccess(): void
    {
        ob_start();
        $report = $this->generator->generateCompleteReport(10, true);
        ob_end_clean();

        $this->assertArrayHasKey('test_metadata', $report);
        $this->assertArrayHasKey('query_performance', $report);
        $this->assertArrayHasKey('index_effectiveness', $report);
        $this->assertArrayHasKey('optimization_summary', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('generated_at', $report);

        $this->assertSame(10, $report['test_metadata']['test_data_count']);
        $this->assertNotEmpty($report['query_performance']['query_tests']);
        $this->assertArrayHasKey('posts_count_by_source', $report['query_performance']['query_tests']);
        $this->assertArrayHasKey('overall_performance', $report['query_performance']);
    }

    public function testGenerateCompleteReportWithoutIndexAnalysis(): void
    {
        ob_start();
        $report = $this->generator->generateCompleteReport(5, false);
        ob_end_clean();

        $this->assertNull($report['index_effectiveness']);
    }

    public function testGenerateCompleteReportThrowsOnZeroDataCount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('測試資料數量必須大於 0');

        $this->generator->generateCompleteReport(0);
    }
}
