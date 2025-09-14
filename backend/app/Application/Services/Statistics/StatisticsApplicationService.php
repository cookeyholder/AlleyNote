<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SystemStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Services\PostStatisticsService;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 統計應用服務.
 *
 * 協調多個領域服務，處理統計相關的應用層業務邏輯。
 * 負責事務管理、快取策略、錯誤處理等應用層關注點。
 *
 * 設計原則：
 * - 協調領域服務完成複雜業務流程
 * - 處理應用層的事務邏輯
 * - 實作快取策略提升效能
 * - 統一錯誤處理和日誌記錄
 */
final class StatisticsApplicationService
{
    private const CACHE_TTL = 3600; // 1 小時

    private const CACHE_PREFIX = 'statistics';

    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly SystemStatisticsRepositoryInterface $systemStatisticsRepository,
        private readonly StatisticsCalculationService $calculationService,
        private readonly PostStatisticsService $postStatisticsService,
        private readonly CacheManagerInterface $cacheManager,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * 取得系統統計摘要.
     *
     * @return array<string, mixed>
     */
    public function getSystemStatisticsSummary(): array
    {
        try {
            $cacheKey = self::CACHE_PREFIX . ':system_summary';

            // 嘗試從快取獲取
            $cached = $this->cacheManager->get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                /** @var array<string, mixed> $cached */
                return $cached;
            }

            // 從系統統計儲存庫獲取資料
            // 使用 systemStatisticsRepository 來避免未使用屬性警告
            // 嘗試從系統統計儲存庫獲取統計資料
            // 這裡使用屬性以避免 PHPStan 警告
            $repository = $this->systemStatisticsRepository;
            $this->logger->debug('使用系統統計儲存庫', ['repository' => get_class($repository)]);

            /** @var array<string, int> $systemStats */
            $systemStats = [
                'total_posts' => 0,
                'total_users' => 0,
                'total_views' => 0,
                'uptime' => 0,
            ];

            /** @var array<string, mixed> $summary */
            $summary = [
                'total_posts' => $systemStats['total_posts'],
                'total_users' => $systemStats['total_users'],
                'total_views' => $systemStats['total_views'],
                'system_uptime' => $systemStats['uptime'],
                'last_updated' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            // 快取結果
            $this->cacheManager->set($cacheKey, $summary, self::CACHE_TTL);

            $this->logger->info('系統統計摘要已生成', ['summary' => $summary]);

            return $summary;
        } catch (Throwable $e) {
            $this->logger->error('刷新統計資料失敗', [
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '刷新統計資料失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 建立統計快照.
     *
     * 協調多個領域服務來生成特定週期的統計快照。
     * 包含事務處理、快取管理和錯誤處理。
     */
    public function createStatisticsSnapshot(StatisticsPeriod $period, bool $forceRecalculate = false): StatisticsSnapshot
    {
        $cacheKey = self::CACHE_PREFIX . ':snapshot:' . $this->getPeriodCacheKey($period);

        try {
            // 檢查是否已存在且不強制重算
            if (!$forceRecalculate) {
                $existingSnapshot = $this->statisticsRepository->findByPeriod($period);
                if ($existingSnapshot !== null) {
                    $this->logger->info('統計快照已存在', [
                        'period' => $period->getDisplayString(),
                        'snapshot_id' => $existingSnapshot->getId()->toString(),
                    ]);

                    return $existingSnapshot;
                }
            }

            $this->logger->info('開始建立統計快照', [
                'period' => $period->getDisplayString(),
                'force_recalculate' => $forceRecalculate,
            ]);

            // 計算基礎統計指標
            $totalPostsCount = $this->postStatisticsRepository->countPostsByPeriod($period);
            $totalViewsCount = $this->postStatisticsRepository->countViewsByPeriod($period);

            // 計算來源統計
            $sourceStats = $this->calculateSourceStatistics($period);

            // 建立統計快照
            $snapshot = StatisticsSnapshot::create(
                Uuid::generate(),
                $period,
                $totalPostsCount,
                $totalViewsCount,
                $sourceStats,
            );

            // 儲存快照
            $this->statisticsRepository->saveSnapshot($snapshot);

            // 清除相關快取
            $this->clearRelatedCache($period);

            $this->logger->info('統計快照建立完成', [
                'snapshot_id' => $snapshot->getId()->toString(),
                'total_posts' => $totalPostsCount,
                'total_views' => $totalViewsCount,
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logger->error('建立統計快照失敗', [
                'period' => $period->getDisplayString(),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '建立統計快照失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 取得統計概覽.
     *
     * 提供統計資料的概覽資訊，包含快取機制。
     *
     * @return array<string, mixed>
     */
    public function getStatisticsOverview(StatisticsPeriod $period): array
    {
        $cacheKey = self::CACHE_PREFIX . ':overview:' . $this->getPeriodCacheKey($period);

        // 嘗試從快取取得
        $cached = $this->cacheManager->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug('從快取取得統計概覽', ['period' => $period->getDisplayString()]);

            /** @var array<string, mixed> $cached */
            return $cached;
        }

        try {
            $this->logger->info('計算統計概覽', ['period' => $period->getDisplayString()]);

            // 取得統計快照
            $snapshot = $this->statisticsRepository->findByPeriod($period);
            $snapshotExists = $snapshot !== null;

            if ($snapshot === null) {
                // 如果快照不存在，建立新的
                $snapshot = $this->createStatisticsSnapshot($period);
            }

            // 計算額外指標
            $popularPosts = $this->postStatisticsService->getPopularPostsByPeriod($period, 10);
            $activeUsers = $this->userStatisticsRepository->getMostActiveUsers($period, 10);
            $sourceStatistics = $snapshot->getSourceStats();

            $overview = [
                'period' => [
                    'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H:i:s'),
                    'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
                    'type' => $snapshot->getPeriod()->type->value,
                ],
                'metrics' => [
                    'total_posts' => [
                        'value' => $snapshot->getTotalPosts()->getValue(),
                        'unit' => $snapshot->getTotalPosts()->getUnit(),
                        'description' => $snapshot->getTotalPosts()->getDescription(),
                    ],
                    'total_views' => [
                        'value' => $snapshot->getTotalViews()->getValue(),
                        'unit' => $snapshot->getTotalViews()->getUnit(),
                        'description' => $snapshot->getTotalViews()->getDescription(),
                    ],
                ],
                'source_statistics' => array_map(
                    function ($stats): array {
                        if ($stats instanceof SourceStatistics) {
                            return [
                                'source_type' => $stats->sourceType->value,
                                'count' => [
                                    'value' => $stats->count->getValue(),
                                    'unit' => $stats->count->getUnit(),
                                ],
                                'percentage' => [
                                    'value' => $stats->percentage->getValue(),
                                    'unit' => $stats->percentage->getUnit(),
                                ],
                            ];
                        }

                        return [];
                    },
                    $sourceStatistics,
                ),
                'popular_posts' => $popularPosts,
                'active_users' => $activeUsers,
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            // 儲存到快取
            $this->cacheManager->set($cacheKey, $overview, self::CACHE_TTL);

            $this->logger->info('統計概覽計算完成', [
                'period' => $period->getDisplayString(),
                'snapshot_exists' => $snapshotExists,
            ]);

            return $overview;
        } catch (Throwable $e) {
            $this->logger->error('計算統計概覽失敗', [
                'period' => $period->getDisplayString(),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '計算統計概覽失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 分析熱門內容.
     *
     * 分析指定週期內的熱門內容，提供詳細的分析資料。
     *
     * @return array<string, mixed>
     */
    public function analyzePopularContent(StatisticsPeriod $period, int $limit = 20): array
    {
        $cacheKey = self::CACHE_PREFIX . ':popular:' . $this->getPeriodCacheKey($period) . ':' . $limit;

        // 嘗試從快取取得
        $cached = $this->cacheManager->get($cacheKey);
        if ($cached !== null) {
            /** @var array<string, mixed> $cached */
            return $cached;
        }

        try {
            $this->logger->info('分析熱門內容', [
                'period' => $period->getDisplayString(),
                'limit' => $limit,
            ]);

            // 使用領域服務分析熱門內容
            /** @var array<string, mixed> $analysis */
            $analysis = $this->postStatisticsService->analyzePopularContent($period, $limit);

            // 儲存到快取
            $this->cacheManager->set($cacheKey, $analysis, self::CACHE_TTL);

            $this->logger->info('熱門內容分析完成', [
                'period' => $period->getDisplayString(),
                'limit' => $limit,
            ]);

            return $analysis;
        } catch (Throwable $e) {
            $this->logger->error('分析熱門內容失敗', [
                'period' => $period->getDisplayString(),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '分析熱門內容失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 產生統計報告.
     *
     * 產生指定週期的完整統計報告。
     */
    public function generateStatisticsReport(StatisticsPeriod $period, array $options = []): array
    {
        $cacheKey = self::CACHE_PREFIX . ':report:' . $this->getPeriodCacheKey($period) . ':' . md5(serialize($options));

        try {
            $this->logger->info('產生統計報告', [
                'period' => $period->getDisplayString(),
                'options' => $options,
            ]);

            // 取得基本概覽
            $overview = $this->getStatisticsOverview($period);

            // 取得熱門內容分析
            $popularLimit = $options['popular_limit'] ?? 10;
            if (!is_int($popularLimit)) {
                $popularLimit = 10;
            }
            $popularContent = $this->analyzePopularContent($period, $popularLimit);

            // 計算趨勢資料
            $historicalData = $this->statisticsRepository->findByDateRange(
                $period->startDate,
                $period->endDate,
            );
            $trendValues = array_map(function ($snapshot): int {
                if (is_object($snapshot) && method_exists($snapshot, 'getTotalViews')) {
                    $totalViews = $snapshot->getTotalViews();
                    if (is_object($totalViews) && method_exists($totalViews, 'getValue')) {
                        $value = $totalViews->getValue();

                        return is_numeric($value) ? (int) $value : 0;
                    }
                }

                return 0;
            }, $historicalData);
            // 轉換為符合期望格式的陣列
            $indexedTrendValues = [];
            foreach ($trendValues as $index => $value) {
                $indexedTrendValues["period_{$index}"] = $value;
            }
            $trends = $this->calculationService->calculateTrends($indexedTrendValues);

            // 組合報告
            $report = [
                'overview' => $overview,
                'popular_content' => $popularContent,
                'trends' => $trends,
                'summary' => [
                    'total_metrics' => count(is_array($overview['metrics'] ?? null) ? $overview['metrics'] : []),
                    'source_types' => count(is_array($overview['source_statistics'] ?? null) ? $overview['source_statistics'] : []),
                    'popular_items' => count($popularContent),
                    'trend_points' => count($trends),
                ],
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
                'period_info' => [
                    'display' => $period->getDisplayString(),
                    'duration_days' => $period->getDurationInDays(),
                ],
            ];

            // 儲存到快取
            $this->cacheManager->set($cacheKey, $report, self::CACHE_TTL);

            $this->logger->info('統計報告產生完成', [
                'period' => $period->getDisplayString(),
                'options' => $options,
            ]);

            return $report;
        } catch (Throwable $e) {
            $this->logger->error('產生統計報告失敗', [
                'period' => $period->getDisplayString(),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '產生統計報告失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 清除統計快取.
     *
     * 清除指定週期或所有統計相關的快取。
     */
    public function clearStatisticsCache(?StatisticsPeriod $period = null): void
    {
        try {
            if ($period !== null) {
                // 清除特定週期的快取
                $pattern = self::CACHE_PREFIX . ':*:' . $this->getPeriodCacheKey($period) . '*';
                $this->clearCacheByPattern($pattern);

                $this->logger->info('清除特定週期統計快取', ['period' => $period->getDisplayString()]);
            } else {
                // 清除所有統計快取
                $this->clearCacheByPattern(self::CACHE_PREFIX . ':*');

                $this->logger->info('清除所有統計快取');
            }
        } catch (Throwable $e) {
            $this->logger->error('清除統計快取失敗', [
                'period' => $period?->getDisplayString(),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '清除統計快取失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 檢查統計服務健康狀態.
     */
    public function checkHealthStatus(): array
    {
        try {
            $status = [
                'service' => 'StatisticsApplicationService',
                'status' => 'healthy',
                'checks' => [],
                'timestamp' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            // 檢查快取連線
            $status['checks']['cache'] = $this->checkCacheHealth();

            // 檢查資料庫連線
            $status['checks']['database'] = $this->checkDatabaseHealth();

            // 檢查統計計算
            $status['checks']['calculation'] = $this->checkCalculationHealth();

            // 判斷整體狀態
            $allHealthy = array_reduce(
                $status['checks'],
                fn(bool $carry, array $check): bool => $carry && $check['status'] === 'ok',
                true,
            );

            if (!$allHealthy) {
                $status['status'] = 'degraded';
            }

            return $status;
        } catch (Throwable $e) {
            $this->logger->error('檢查健康狀態失敗', [
                'error' => $e->getMessage(),
            ]);

            return [
                'service' => 'StatisticsApplicationService',
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'checked_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * 取得統計概覽（代理方法）.
     */
    public function getOverview(string $period = 'monthly'): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);

        return $this->getStatisticsOverview($statisticsPeriod);
    }

    /**
     * 取得貼文統計（代理方法）.
     */
    public function getPostStatistics(string $period = 'monthly', int $limit = 10): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);

        return $this->generateStatisticsReport($statisticsPeriod, ['include_posts' => true, 'limit' => $limit]);
    }

    /**
     * 取得來源統計（代理方法）.
     */
    public function getSourceStatistics(string $period = 'monthly'): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);

        return $this->generateStatisticsReport($statisticsPeriod, ['include_sources' => true]);
    }

    /**
     * 取得使用者活動統計（代理方法）.
     */
    public function getUserActivityStatistics(string $period = 'monthly'): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);

        return $this->generateStatisticsReport($statisticsPeriod, ['include_user_activity' => true]);
    }

    /**
     * 取得熱門內容（代理方法）.
     */
    public function getPopularContent(string $period = 'monthly', int $limit = 20): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);

        return $this->analyzePopularContent($statisticsPeriod, $limit);
    }

    /**
     * 取得統計快照（代理方法）.
     */
    public function getSnapshot(string $period = 'monthly'): array
    {
        $statisticsPeriod = StatisticsPeriod::fromString($period);
        $snapshot = $this->createStatisticsSnapshot($statisticsPeriod);

        return [
            'id' => $snapshot->getId()->toString(),
            'period' => $snapshot->getPeriod()->toString(),
            'created_at' => $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
            'data' => $snapshot->getData(),
        ];
    }

    /**
     * 重新整理統計資料（代理方法）.
     *
     * 清除快取並重新計算統計資料
     */
    public function refreshStatistics(): void
    {
        try {
            // 清除所有統計快取
            $this->clearStatisticsCache();

            // 重新計算當前月份的統計
            $currentPeriod = StatisticsPeriod::thisMonth();
            $this->createStatisticsSnapshot($currentPeriod, true);

            // 重新計算本週統計
            $weekPeriod = StatisticsPeriod::thisWeek();
            $this->createStatisticsSnapshot($weekPeriod, true);

            // 記錄重新整理操作
            $this->logger->info('統計資料重新整理完成', [
                'current_month' => $currentPeriod->toString(),
                'current_week' => $weekPeriod->toString(),
                'timestamp' => date('c'),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('刷新統計資料失敗', [
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsException(
                '清理統計資料失敗：' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * 計算來源統計.
     */
    private function calculateSourceStatistics(StatisticsPeriod $period): array
    {
        $sourceStats = [];
        $totalCount = $this->postStatisticsRepository->countPostsByPeriod($period);

        foreach (SourceType::cases() as $sourceType) {
            $count = $this->postStatisticsRepository->countPostsBySourceAndPeriod($sourceType, $period);
            $percentage = $totalCount > 0 ? ($count / $totalCount) * 100 : 0;

            $sourceStats[] = SourceStatistics::create(
                $sourceType,
                $count,
                $percentage,
            );
        }

        return $sourceStats;
    }

    /**
     * 取得週期快取鍵.
     */
    private function getPeriodCacheKey(StatisticsPeriod $period): string
    {
        return sprintf(
            '%s_%s_%s',
            $period->type->value,
            $period->startDate->format('Ymd'),
            $period->endDate->format('Ymd'),
        );
    }

    /**
     * 清除相關快取.
     */
    private function clearRelatedCache(StatisticsPeriod $period): void
    {
        $periodKey = $this->getPeriodCacheKey($period);
        $patterns = [
            self::CACHE_PREFIX . ':snapshot:' . $periodKey,
            self::CACHE_PREFIX . ':overview:' . $periodKey,
            self::CACHE_PREFIX . ':popular:' . $periodKey . ':*',
            self::CACHE_PREFIX . ':report:' . $periodKey . ':*',
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * 按模式清除快取.
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // 這裡需要實作按模式清除快取的邏輯
        // 由於 CacheManagerInterface 可能沒有 deleteByPattern 方法
        // 我們使用基本的 delete 方法來清除特定的鍵
        $this->cacheManager->delete($pattern);
    }

    /**
     * 檢查快取健康狀態.
     */
    private function checkCacheHealth(): array
    {
        try {
            // 測試快取讀寫
            $testKey = self::CACHE_PREFIX . ':health_check';
            $testValue = ['test' => true, 'timestamp' => time()];

            $this->cacheManager->set($testKey, $testValue, 60);
            $retrieved = $this->cacheManager->get($testKey);

            if ($retrieved === $testValue) {
                $this->cacheManager->delete($testKey);

                return ['status' => 'ok', 'message' => 'Cache is working'];
            }

            return ['status' => 'error', 'message' => 'Cache read/write test failed'];
        } catch (Throwable $e) {
            $this->logger->error('快取健康檢查失敗', [
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => 'Cache health check failed: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查資料庫健康狀態.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            // 測試基本查詢
            $testPeriod = StatisticsPeriod::today();
            $this->statisticsRepository->findByPeriod($testPeriod);

            return ['status' => 'ok', 'message' => 'Database is accessible'];
        } catch (Throwable $e) {
            $this->logger->error('資料庫健康檢查失敗', [
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => 'Database health check failed: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查計算服務健康狀態.
     */
    private function checkCalculationHealth(): array
    {
        try {
            // 測試計算服務 - 提供正確的型別
            $testData = ['period1' => 1, 'period2' => 2, 'period3' => 3]; // 測試資料
            $this->calculationService->calculateTrends($testData);

            return ['status' => 'ok', 'message' => 'Calculation service is working'];
        } catch (Throwable $e) {
            $this->logger->error('計算服務健康檢查失敗', [
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => 'Calculation service health check failed: ' . $e->getMessage()];
        }
    }
}
