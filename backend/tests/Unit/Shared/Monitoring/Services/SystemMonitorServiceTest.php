<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring\Services;

use App\Shared\Config\EnvironmentConfig;
use App\Shared\Monitoring\Services\SystemMonitorService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * SystemMonitorService 單元測試.
 */
final class SystemMonitorServiceTest extends UnitTestCase
{
    private string $tempDir;

    private SystemMonitorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/alleynote_sysmon_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
        file_put_contents(
            $this->tempDir . '/.env.testing',
            "APP_NAME=AlleyNote\n" .
            "APP_ENV=testing\n" .
            "DB_CONNECTION=sqlite\n" .
            "DB_DATABASE=:memory:\n",
        );

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE sample (id INTEGER PRIMARY KEY)');
        $config = new EnvironmentConfig('testing', $this->tempDir);
        $this->service = new SystemMonitorService(new NullLogger(), $pdo, $config);
    }

    protected function tearDown(): void
    {
        $envFile = $this->tempDir . '/.env.testing';
        if (is_file($envFile)) {
            unlink($envFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function getSystemInfoReturnsEnvironmentDetails(): void
    {
        $info = $this->service->getSystemInfo();

        $this->assertSame(PHP_VERSION, $info['php_version']);
        $this->assertSame(PHP_OS_FAMILY, $info['system']);
        $this->assertSame('testing', $info['environment']);
        $this->assertArrayHasKey('timezone', $info);
        $this->assertArrayHasKey('memory_limit', $info);
        $extensions = $info['extensions'];
        $this->assertIsArray($extensions);
        $this->assertNotEmpty($extensions);
    }

    #[Test]
    public function getMemoryUsageReportsUsageAndLimit(): void
    {
        $usage = $this->service->getMemoryUsage();

        $this->assertGreaterThan(0, $usage['current_usage_bytes']);
        $this->assertGreaterThan(0, $usage['peak_usage_bytes']);
        // 測試環境通常無記憶體上限（-1），此時百分比為 0
        $limitBytes = $usage['limit_bytes'];
        $this->assertIsInt($limitBytes);
        if ($limitBytes > 0) {
            $this->assertGreaterThan(0, $usage['available_bytes']);
        } else {
            $this->assertSame(0, $usage['usage_percentage']);
        }
    }

    #[Test]
    public function getCpuUsageReturnsLoadAverages(): void
    {
        $cpu = $this->service->getCpuUsage();

        $this->assertArrayHasKey('load_average_1min', $cpu);
        $this->assertArrayHasKey('load_average_5min', $cpu);
        $this->assertArrayHasKey('load_average_15min', $cpu);
        $this->assertGreaterThanOrEqual(1, $cpu['cpu_count']);
        $this->assertNotFalse($cpu['process_id']);
    }

    #[Test]
    public function getDiskUsageCalculatesPercentages(): void
    {
        $usage = $this->service->getDiskUsage(sys_get_temp_dir());

        $this->assertSame(sys_get_temp_dir(), $usage['path']);
        $this->assertGreaterThan(0, $usage['total_bytes']);
        $totalBytes = $usage['total_bytes'];
        $this->assertIsFloat($totalBytes);
        $freeBytes = $usage['free_bytes'];
        $this->assertIsFloat($freeBytes);
        $usedPercentage = $usage['usage_percentage'];
        $this->assertIsFloat($usedPercentage);
        $this->assertLessThanOrEqual(100.0, $usedPercentage);

        // 不存在的路徑回退到專案根目錄
        $fallback = $this->service->getDiskUsage('/nonexistent/path/xyz');
        $this->assertNotSame('/nonexistent/path/xyz', $fallback['path']);
    }

    #[Test]
    public function getDatabaseStatusConfirmsSqliteConnection(): void
    {
        $status = $this->service->getDatabaseStatus();

        $this->assertTrue($status['connected']);
        $this->assertSame('sqlite', $status['driver']);
        $this->assertGreaterThan(0, $status['connection_time_ms'] ?? 0);
        // SQLite 特定統計：資料表數量
        $tableCount = $status['table_count'];
        $this->assertIsInt($tableCount);
        $this->assertGreaterThanOrEqual(1, $tableCount);
    }

    #[Test]
    public function getHealthCheckAggregatesAllChecks(): void
    {
        $health = $this->service->getHealthCheck();

        $checks = $health['checks'];
        $this->assertIsArray($checks);
        foreach (['database', 'memory', 'disk', 'environment', 'logs'] as $name) {
            $check = $checks[$name] ?? null;
            $this->assertIsArray($check);
            $this->assertArrayHasKey('status', $check);
        }
        $databaseCheck = $checks['database'];
        $this->assertIsArray($databaseCheck);
        // SQLite :memory: 連線迅速，應判定為健康
        $this->assertSame('healthy', $databaseCheck['status']);
        $this->assertSame('testing', $health['environment']);
        $this->assertGreaterThan(0, $health['health_score']);
    }

    #[Test]
    public function isSystemHealthyMatchesOverallStatus(): void
    {
        $health = $this->service->getHealthCheck();
        $overallStatus = $health['overall_status'];
        $healthScore = $health['health_score'];

        $expected = $overallStatus === 'healthy'
            && (is_float($healthScore) || is_int($healthScore))
            && $healthScore >= 80;
        $this->assertSame($expected, $this->service->isSystemHealthy());
    }

    #[Test]
    public function getAllMetricsCombinesEverySection(): void
    {
        $metrics = $this->service->getAllMetrics();

        foreach (['system', 'memory', 'cpu', 'disk', 'database', 'health'] as $section) {
            $data = $metrics[$section] ?? null;
            $this->assertIsArray($data);
        }
        // logSystemMetrics 不應拋出例外
        $this->service->logSystemMetrics();
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function databaseFailureIsReportedGracefully(): void
    {
        // 以反射注入會拋例外的 PDO 子類別模擬連線失敗
        $failingPdo = new class('sqlite::memory:') extends PDO {
            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
            {
                throw new RuntimeException('connection refused');
            }
        };
        $config = new EnvironmentConfig('testing', $this->tempDir);
        $service = new SystemMonitorService(new NullLogger(), $failingPdo, $config);

        $status = $service->getDatabaseStatus();
        $this->assertFalse($status['connected']);
        $this->assertSame('connection refused', $status['error']);

        $health = $service->getHealthCheck();
        $checks = $health['checks'];
        $this->assertIsArray($checks);
        $databaseCheck = $checks['database'];
        $this->assertIsArray($databaseCheck);
        $this->assertSame('critical', $databaseCheck['status']);
        $this->assertFalse($service->isSystemHealthy());
    }
}
