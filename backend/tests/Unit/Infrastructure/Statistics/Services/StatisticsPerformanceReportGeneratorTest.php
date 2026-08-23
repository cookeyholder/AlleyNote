<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Infrastructure\Statistics\Services\SlowQueryMonitoringService;
use App\Infrastructure\Statistics\Services\StatisticsPerformanceReportGenerator;
use InvalidArgumentException;
use PDO;
use ReflectionMethod;
use RuntimeException;
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

    public function testGradeAndSummaryBranchesViaReflection(): void
    {
        $gradeMethod = new ReflectionMethod(StatisticsPerformanceReportGenerator::class, 'calculatePerformanceGrade');
        $gradeMethod->setAccessible(true);

        // 各種執行時間對應的等級（以陣列配對避免浮點鍵被轉整數）
        $expectedGrades = [
            [0.001, 'A+'],
            [0.02, 'A'],
            [0.07, 'B'],
            [0.3, 'C'],
            [0.7, 'D'],
            [2.0, 'F'],
        ];
        foreach ($expectedGrades as [$time, $grade]) {
            $this->assertSame($grade, $gradeMethod->invoke($this->generator, (float) $time));
        }

        $summaryMethod = new ReflectionMethod(StatisticsPerformanceReportGenerator::class, 'generatePerformanceSummary');
        $summaryMethod->setAccessible(true);
        /** @var string $good */
        $good = $summaryMethod->invoke($this->generator, 0.04, 1);
        $this->assertStringContainsString('良好', $good);
        /** @var string $fair */
        $fair = $summaryMethod->invoke($this->generator, 0.07, 1);
        $this->assertStringContainsString('普通', $fair);
        /** @var string $needsWork */
        $needsWork = $summaryMethod->invoke($this->generator, 0.5, 1);
        $this->assertStringContainsString('需要改善', $needsWork);
        /** @var string $critical */
        $critical = $summaryMethod->invoke($this->generator, 5.0, 1);
        $this->assertStringContainsString('嚴重', $critical);
    }

    public function testIndexAssessmentReflectsIndexCount(): void
    {
        // 建立足夠多的索引以觸發 well_optimized 與 moderately_optimized 分支
        for ($i = 1; $i <= 9; $i++) {
            $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->pdo->exec("CREATE INDEX idx_posts_extra_{$number} ON posts(created_at, views)");
        }

        $report = $this->generator->generateCompleteReport(10, true);
        /** @var array<string, mixed> $indexAnalysis */
        $indexAnalysis = $report['index_effectiveness'];
        $this->assertSame('well_optimized', $indexAnalysis['optimization_status']);

        // 移除部分索引後剩餘 6 個，應判定為 moderately_optimized
        for ($i = 3; $i <= 9; $i++) {
            $number = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->pdo->exec("DROP INDEX idx_posts_extra_{$number}");
        }

        $moderateReport = $this->generator->generateCompleteReport(10, true);
        /** @var array<string, mixed> $moderateIndexAnalysis */
        $moderateIndexAnalysis = $moderateReport['index_effectiveness'];
        $this->assertSame('moderately_optimized', $moderateIndexAnalysis['optimization_status']);
    }

    public function testAllQueryTestsFailWhenTableMissing(): void
    {
        // 移除 posts 資料表，所有測試查詢應回報失敗且整體等級為 N/A
        $this->pdo->exec('DROP TABLE posts');

        $report = $this->generator->generateCompleteReport(100, true);

        /** @var array<string, mixed> $queryPerformance */
        $queryPerformance = $report['query_performance'];
        /** @var array<int, array<string, mixed>> $queryTests */
        $queryTests = $queryPerformance['query_tests'];

        $failedResults = array_filter(
            $queryTests,
            static fn(array $result): bool => ($result['status'] ?? '') === 'failed',
        );
        $this->assertNotEmpty($failedResults);
        /** @var array<string, mixed> $overallPerformance */
        $overallPerformance = $queryPerformance['overall_performance'];
        $this->assertSame('N/A', $overallPerformance['overall_grade']);
    }

    public function testReportDirectoryIsCreatedWhenMissing(): void
    {
        $reportDir = dirname(__DIR__, 5) . '/storage/reports';

        // 記錄目錄原始狀態
        $existed = is_dir($reportDir);
        if ($existed) {
            @rename($reportDir, $reportDir . '_backup_' . uniqid());
        }

        try {
            // 目錄不存在時應自動建立（涵蓋 mkdir 分支）
            $this->generator->generateCompleteReport(10, false);

            $this->assertDirectoryExists($reportDir);
            $files = glob($reportDir . '/statistics_performance_report_*.json');
            $this->assertNotEmpty($files);
        } finally {
            // 清理並還原
            foreach (glob($reportDir . '/statistics_performance_report_*.json') ?: [] as $file) {
                @unlink($file);
            }
            if (is_dir($reportDir)) {
                @rmdir($reportDir);
            }
            $backups = glob(dirname($reportDir) . '/reports_backup_*');
            if ($backups !== false && $backups !== []) {
                $backup = $backups[0];
                rename($backup, $reportDir);
            }
        }
    }

    public function testSaveReportFailureIsSwallowedWhenDirectoryIsFile(): void
    {
        $reportDir = dirname(__DIR__, 5) . '/storage/reports';

        // 將報告目錄位置換成檔案，使寫入失敗但不中斷流程
        $existed = is_dir($reportDir);
        if ($existed) {
            @rename($reportDir, $reportDir . '_backup_' . uniqid());
        }
        file_put_contents($reportDir, 'not-a-directory');

        try {
            // mkdir 失敗會被服務捕捉並輸出警告訊息
            set_error_handler(static fn(): bool => throw new RuntimeException('mkdir failed'));

            try {
                $this->generator->generateCompleteReport(10, false);
                $this->addToAssertionCount(1);
            } finally {
                restore_error_handler();
            }
        } finally {
            if (is_file($reportDir)) {
                unlink($reportDir);
            }
            if (!$existed) {
                @mkdir(dirname($reportDir), 0o755, true);
            }
            $backups = glob(dirname($reportDir) . '/reports_backup_*');
            if ($backups !== false && $backups !== []) {
                rename($backups[0], $reportDir);
            }
        }
    }
}
