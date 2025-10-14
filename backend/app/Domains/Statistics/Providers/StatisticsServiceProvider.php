<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Providers;

use App\Application\Services\Statistics\StatisticsQueryService;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SlowQueryMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsMonitoringServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsQueryServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Domains\Statistics\Services\StatisticsConfigService;
use App\Infrastructure\Services\CacheService;
use App\Infrastructure\Statistics\Repositories\PostStatisticsRepository;
use App\Infrastructure\Statistics\Repositories\StatisticsRepository;
use App\Infrastructure\Statistics\Repositories\UserStatisticsRepository;
use App\Infrastructure\Statistics\Services\SlowQueryMonitoringService;
use App\Infrastructure\Statistics\Services\StatisticsCacheService;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use App\Infrastructure\Statistics\Services\StatisticsVisualizationService;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use App\Shared\Events\SimpleEventDispatcher;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * 統計服務提供者.
 *
 * 負責註冊統計系統的所有組件到 DI 容器中
 */
class StatisticsServiceProvider
{
    /**
     * 取得統計服務的 DI 定義.
     *
     * @return array<string, mixed>
     */
    public static function getDefinitions(): array
    {
        return [
            // Repository 介面綁定
            StatisticsRepositoryInterface::class => \DI\factory(function (ContainerInterface $container): StatisticsRepositoryInterface {
                /** @var PDO $pdo */
                $pdo = $container->get(PDO::class);

                return new StatisticsRepository($pdo);
            }),

            PostStatisticsRepositoryInterface::class => \DI\factory(function (ContainerInterface $container): PostStatisticsRepositoryInterface {
                /** @var PDO $pdo */
                $pdo = $container->get(PDO::class);

                return new PostStatisticsRepository($pdo);
            }),

            UserStatisticsRepositoryInterface::class => \DI\factory(function (ContainerInterface $container): UserStatisticsRepositoryInterface {
                /** @var PDO $pdo */
                $pdo = $container->get(PDO::class);

                return new UserStatisticsRepository($pdo);
            }),

            // 快取服務
            StatisticsCacheServiceInterface::class => \DI\factory(function (ContainerInterface $container): StatisticsCacheServiceInterface {
                /** @var CacheServiceInterface $cacheService */
                $cacheService = $container->get(CacheService::class);
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);

                return new StatisticsCacheService($cacheService, $logger);
            }),

            // SlowQueryMonitoringService
            SlowQueryMonitoringServiceInterface::class => \DI\factory(function (ContainerInterface $container): SlowQueryMonitoringServiceInterface {
                /** @var PDO $pdo */
                $pdo = $container->get(PDO::class);

                return new SlowQueryMonitoringService($pdo);
            }),

            // 監控服務
            StatisticsMonitoringServiceInterface::class => \DI\factory(function (ContainerInterface $container): StatisticsMonitoringServiceInterface {
                /** @var SlowQueryMonitoringServiceInterface $slowQueryService */
                $slowQueryService = $container->get(SlowQueryMonitoringServiceInterface::class);
                /** @var PDO|null $pdo */
                $pdo = $container->has(PDO::class) ? $container->get(PDO::class) : null;
                /** @var LoggerInterface|null $logger */
                $logger = $container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : null;

                return new StatisticsMonitoringService($slowQueryService, $pdo, $logger);
            }),

            // 事件分派器
            EventDispatcherInterface::class => \DI\factory(function (ContainerInterface $container): EventDispatcherInterface {
                /** @var LoggerInterface|null $logger */
                $logger = $container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : null;

                return new SimpleEventDispatcher($logger);
            }),

            // 領域服務
            StatisticsAggregationService::class => \DI\factory(function (ContainerInterface $container): StatisticsAggregationService {
                /** @var StatisticsRepositoryInterface $statisticsRepository */
                $statisticsRepository = $container->get(StatisticsRepositoryInterface::class);
                /** @var PostStatisticsRepositoryInterface $postStatisticsRepository */
                $postStatisticsRepository = $container->get(PostStatisticsRepositoryInterface::class);
                /** @var UserStatisticsRepositoryInterface $userStatisticsRepository */
                $userStatisticsRepository = $container->get(UserStatisticsRepositoryInterface::class);
                /** @var EventDispatcherInterface|null $eventDispatcher */
                $eventDispatcher = $container->has(EventDispatcherInterface::class) ? $container->get(EventDispatcherInterface::class) : null;

                return new StatisticsAggregationService(
                    $statisticsRepository,
                    $postStatisticsRepository,
                    $userStatisticsRepository,
                    $eventDispatcher,
                );
            }),

            // 綁定介面到實作
            StatisticsAggregationServiceInterface::class => \DI\get(StatisticsAggregationService::class),

            // 應用服務
            StatisticsQueryService::class => \DI\factory(function (ContainerInterface $container): StatisticsQueryService {
                /** @var StatisticsRepositoryInterface $statisticsRepository */
                $statisticsRepository = $container->get(StatisticsRepositoryInterface::class);
                /** @var StatisticsCacheServiceInterface $cacheService */
                $cacheService = $container->get(StatisticsCacheServiceInterface::class);
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);
                /** @var PDO $db */
                $db = $container->get(PDO::class);

                return new StatisticsQueryService($statisticsRepository, $cacheService, $logger, $db);
            }),

            // 綁定介面到實作
            StatisticsQueryServiceInterface::class => \DI\get(StatisticsQueryService::class),

            // 配置服務
            StatisticsConfigService::class => \DI\factory(function (): StatisticsConfigService {
                return new StatisticsConfigService();
            }),

            // 視覺化服務
            StatisticsVisualizationServiceInterface::class => \DI\autowire(StatisticsVisualizationService::class),
        ];
    }
}
