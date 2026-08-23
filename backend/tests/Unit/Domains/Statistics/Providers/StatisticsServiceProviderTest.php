<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Providers;

use App\Domains\Statistics\Analyzers\ContentInsightsAnalyzer;
use App\Domains\Statistics\Analyzers\PostStatisticsAnalyzer;
use App\Domains\Statistics\Analyzers\SourceDistributionAnalyzer;
use App\Domains\Statistics\Analyzers\StatisticsOverviewAnalyzer;
use App\Domains\Statistics\Analyzers\UserStatisticsAnalyzer;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SlowQueryMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Domains\Statistics\Contracts\SystemMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Providers\StatisticsServiceProvider;
use App\Domains\Statistics\Services\AdvancedAnalyticsService;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Domains\Statistics\Services\StatisticsConfigService;
use App\Domains\Statistics\Services\StatisticsExportService;
use App\Domains\Statistics\Services\StatisticsQueryService;
use App\Domains\Statistics\Services\UserAgentParserService;
use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use DI\ContainerBuilder;
use Mockery;
use PDO;
use Psr\Log\LoggerInterface;
use Tests\Support\UnitTestCase;

/**
 * 統計服務提供者測試.
 */
final class StatisticsServiceProviderTest extends UnitTestCase
{
    public function testGetDefinitionsReturnsValidArray(): void
    {
        $definitions = StatisticsServiceProvider::getDefinitions();
        $this->assertArrayHasKey(StatisticsRepositoryInterface::class, $definitions);
        $this->assertArrayHasKey(PostStatisticsRepositoryInterface::class, $definitions);
        $this->assertArrayHasKey(UserStatisticsRepositoryInterface::class, $definitions);
    }

    public function testContainerResolvesAllDefinitions(): void
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);

        // 模擬外部必要服務
        $pdo = new PDO('sqlite::memory:');
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info', 'warning', 'error', 'debug')->byDefault();
        $cacheService = Mockery::mock(CacheService::class);

        $builder->addDefinitions([
            PDO::class             => $pdo,
            LoggerInterface::class => $logger,
            CacheService::class    => $cacheService,
        ]);

        $builder->addDefinitions(StatisticsServiceProvider::getDefinitions());
        $container = $builder->build();

        // 測試解析 Repository
        $this->assertInstanceOf(StatisticsRepositoryInterface::class, $container->get(StatisticsRepositoryInterface::class));
        $this->assertInstanceOf(PostStatisticsRepositoryInterface::class, $container->get(PostStatisticsRepositoryInterface::class));
        $this->assertInstanceOf(UserStatisticsRepositoryInterface::class, $container->get(UserStatisticsRepositoryInterface::class));

        // 測試解析 快取 & 監控
        $this->assertInstanceOf(StatisticsCacheServiceInterface::class, $container->get(StatisticsCacheServiceInterface::class));
        $this->assertInstanceOf(SlowQueryMonitoringServiceInterface::class, $container->get(SlowQueryMonitoringServiceInterface::class));
        $this->assertInstanceOf(StatisticsMonitoringServiceInterface::class, $container->get(StatisticsMonitoringServiceInterface::class));
        $this->assertInstanceOf(StatisticsMonitoringService::class, $container->get(StatisticsMonitoringService::class));

        // 測試解析 事件分派器
        $this->assertInstanceOf(EventDispatcherInterface::class, $container->get(EventDispatcherInterface::class));

        // 測試解析 領域服務
        $this->assertInstanceOf(StatisticsAggregationService::class, $container->get(StatisticsAggregationService::class));
        $this->assertInstanceOf(StatisticsAggregationServiceInterface::class, $container->get(StatisticsAggregationServiceInterface::class));
        $this->assertInstanceOf(StatisticsQueryService::class, $container->get(StatisticsQueryService::class));
        $this->assertInstanceOf(StatisticsConfigService::class, $container->get(StatisticsConfigService::class));
        $this->assertInstanceOf(StatisticsVisualizationServiceInterface::class, $container->get(StatisticsVisualizationServiceInterface::class));
        $this->assertInstanceOf(SystemMonitoringServiceInterface::class, $container->get(SystemMonitoringServiceInterface::class));
        $this->assertInstanceOf(PostViewStatisticsService::class, $container->get(PostViewStatisticsService::class));
        $this->assertInstanceOf(UserAgentParserService::class, $container->get(UserAgentParserService::class));
        $this->assertInstanceOf(AdvancedAnalyticsService::class, $container->get(AdvancedAnalyticsService::class));
        $this->assertInstanceOf(StatisticsExportService::class, $container->get(StatisticsExportService::class));

        // 測試解析 分析器
        $this->assertInstanceOf(SourceDistributionAnalyzer::class, $container->get(SourceDistributionAnalyzer::class));
        $this->assertInstanceOf(ContentInsightsAnalyzer::class, $container->get(ContentInsightsAnalyzer::class));
        $this->assertInstanceOf(PostStatisticsAnalyzer::class, $container->get(PostStatisticsAnalyzer::class));
        $this->assertInstanceOf(UserStatisticsAnalyzer::class, $container->get(UserStatisticsAnalyzer::class));
        $this->assertInstanceOf(StatisticsOverviewAnalyzer::class, $container->get(StatisticsOverviewAnalyzer::class));
    }
}
