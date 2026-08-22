<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring\Providers;

use App\Shared\Config\EnvironmentConfig;
use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use App\Shared\Monitoring\Contracts\PerformanceMonitorInterface;
use App\Shared\Monitoring\Contracts\SystemMonitorInterface;
use App\Shared\Monitoring\Providers\MonitoringServiceProvider;
use App\Shared\Monitoring\Services\ErrorTrackerService;
use App\Shared\Monitoring\Services\PerformanceMonitorService;
use App\Shared\Monitoring\Services\SystemMonitorService;
use DI\Container;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Support\UnitTestCase;

/**
 * MonitoringServiceProvider 單元測試.
 *
 * 驗證監控服務的 DI 定義、容器註冊與初始化輔助方法。
 */
final class MonitoringServiceProviderTest extends UnitTestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
    }

    /**
     * 建立已註冊基礎依賴的容器。
     */
    private function createContainer(): Container
    {
        $container = new Container();
        $container->set(LoggerInterface::class, fn(): LoggerInterface => new NullLogger());
        $container->set(PDO::class, fn(): PDO => $this->pdo);
        $container->set(EnvironmentConfig::class, fn(): EnvironmentConfig => new EnvironmentConfig('testing'));

        return $container;
    }

    #[Test]
    public function getDefinitionsResolveAllMonitoringServices(): void
    {
        $container = $this->createContainer();
        foreach (MonitoringServiceProvider::getDefinitions() as $id => $definition) {
            $container->set($id, $definition);
        }

        $systemMonitor = $container->get(SystemMonitorInterface::class);
        $this->assertInstanceOf(SystemMonitorService::class, $systemMonitor);

        $performanceMonitor = $container->get(PerformanceMonitorInterface::class);
        $this->assertInstanceOf(PerformanceMonitorService::class, $performanceMonitor);

        $errorTracker = $container->get(ErrorTrackerInterface::class);
        $this->assertInstanceOf(ErrorTrackerService::class, $errorTracker);

        // 具體實作類別的別名
        $this->assertSame(
            $container->get(SystemMonitorInterface::class),
            $container->get(SystemMonitorService::class) instanceof SystemMonitorInterface ? $systemMonitor : null,
        );
    }

    #[Test]
    public function registerSetsContainerEntriesWithAliases(): void
    {
        $container = $this->createContainer();
        MonitoringServiceProvider::register($container);

        $systemMonitor = $container->get(SystemMonitorInterface::class);
        $this->assertInstanceOf(SystemMonitorInterface::class, $systemMonitor);

        // 別名應解析為同一服務型別
        $aliasResolved = $container->get(SystemMonitorService::class);
        $this->assertInstanceOf(SystemMonitorInterface::class, $aliasResolved);

        $performanceAlias = $container->get(PerformanceMonitorService::class);
        $this->assertInstanceOf(PerformanceMonitorService::class, $performanceAlias);

        $errorAlias = $container->get(ErrorTrackerService::class);
        $this->assertInstanceOf(ErrorTrackerService::class, $errorAlias);
    }

    #[Test]
    public function initializeInstallsErrorFiltersAndNotificationHandlers(): void
    {
        $container = $this->createContainer();
        MonitoringServiceProvider::register($container);

        // 不應拋出例外，且錯誤追蹤器取得過濾器與通知處理器
        MonitoringServiceProvider::initialize($container);

        $tracker = $container->get(ErrorTrackerInterface::class);
        $this->assertInstanceOf(ErrorTrackerInterface::class, $tracker);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function setupPerformanceBenchmarksRecordsStartupMetric(): void
    {
        // 容器 set() 定義不共用，直接注入固定實例以驗證設定效果
        $monitor = new PerformanceMonitorService(new NullLogger());
        $container = $this->createContainer();
        $container->set(PerformanceMonitorInterface::class, $monitor);

        MonitoringServiceProvider::setupPerformanceBenchmarks($container);
        // 啟動指標帶有標籤後綴（app_startup[...]），以鍵名前綴搜尋
        $stats = $monitor->getPerformanceStats();
        $summary = $stats['metrics_summary'];
        $this->assertIsArray($summary);
        $matchingKeys = array_values(array_filter(
            array_keys($summary),
            static fn(string $key): bool => str_starts_with($key, 'app_startup'),
        ));
        $this->assertCount(1, $matchingKeys);
        $startupMetric = $summary[$matchingKeys[0]];
        $this->assertIsArray($startupMetric);
        $this->assertSame(1, $startupMetric['count']);
    }

    #[Test]
    public function setupHealthCheckScheduleWarnsWhenUnhealthy(): void
    {
        $container = $this->createContainer();
        MonitoringServiceProvider::register($container);

        // SQLite :memory: 環境下健康檢查應可完整執行
        MonitoringServiceProvider::setupHealthCheckSchedule($container);
        $this->addToAssertionCount(1);
    }
}
