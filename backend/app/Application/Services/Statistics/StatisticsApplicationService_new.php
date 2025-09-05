<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SystemStatisticsRepositoryInterface;
use App\Domains\Statistics\Services\StatisticsCalculationService;
use App\Domains\Statistics\Services\PostStatisticsService;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\Enums\SourceType;
use App\Shared\Domain\ValueObjects\Uuid;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Throwable;

/**
 * 統計應用服務
 *
 * 協調多個領域服務，處理統計相關的應用層業務邏輯。
 * 負責事務管理、錯誤處理等應用層關注點。
 *
 * 設計原則：
 * - 協調領域服務完成複雜業務流程
 * - 處理應用層的事務邏輯
 * - 統一錯誤處理和日誌記錄
 */
final class StatisticsApplicationService
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly SystemStatisticsRepositoryInterface $systemStatisticsRepository,
        private readonly StatisticsCalculationService $calculationService,
        private readonly PostStatisticsService $postStatisticsService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 建立統計快照
     *
     * 協調多個領域服務來生成特定週期的統計快照。
     * 包含事務處理、錯誤處理。
     */
    public function createStatisticsSnapshot(StatisticsPeriod $period, bool $forceRecalculate = false): StatisticsSnapshot
    {
        try {
            // 檢查是否已存在且不強制重算
            if (!$forceRecalculate) {
                $existingSnapshot = $this->statisticsRepository->findByPeriod($period);
                if ($existingSnapshot !== null) {
                    $this->logger->info('統計快照已存在', [
                        'period' => $period->__toString(),
                        'snapshot_id' => $existingSnapshot->getId()->toString()
                    ]);
                    return $existingSnapshot;
                }
            }

            $this->logger->info('開始建立統計快照', [
                'period' => $period->__toString(),
                'force_recalculate' => $forceRecalculate
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
                $sourceStats
            );

            // 儲存快照
            $this->statisticsRepository->saveSnapshot($snapshot);

            $this->logger->info('統計快照建立完成', [
                'snapshot_id' => $snapshot->getId()->toString(),
                'total_posts' => $totalPostsCount,
                'total_views' => $totalViewsCount
            ]);

            return $snapshot;

        } catch (Throwable $e) {
            $this->logger->error('建立統計快照失敗', [
                'period' => $period->__toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 取得統計概覽
     *
     * 提供統計資料的概覽資訊。
     */
    public function getStatisticsOverview(StatisticsPeriod $period): array
    {
        try {
            $this->logger->info('計算統計概覽', ['period' => $period->__toString()]);

            // 取得統計快照
            $snapshot = $this->statisticsRepository->findByPeriod($period);

            if ($snapshot === null) {
                // 如果快照不存在，建立新的
                $snapshot = $this->createStatisticsSnapshot($period);
            }

            $overview = [
                'period' => [
                    'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H:i:s'),
                    'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
                    'type' => $snapshot->getPeriod()->type->value
                ],
                'metrics' => [
                    'total_posts' => [
                        'value' => $snapshot->getTotalPosts()->value,
                        'unit' => $snapshot->getTotalPosts()->unit,
                        'description' => $snapshot->getTotalPosts()->description
                    ],
                    'total_views' => [
                        'value' => $snapshot->getTotalViews()->value,
                        'unit' => $snapshot->getTotalViews()->unit,
                        'description' => $snapshot->getTotalViews()->description
                    ]
                ],
                'source_statistics' => array_map(
                    fn(SourceStatistics $stats) => [
                        'source_type' => $stats->sourceType->value,
                        'count' => [
                            'value' => $stats->count->value,
                            'unit' => $stats->count->unit
                        ],
                        'percentage' => [
                            'value' => $stats->percentage->value,
                            'unit' => $stats->percentage->unit
                        ]
                    ],
                    $snapshot->getSourceStats()
                ),
                'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ];

            $this->logger->info('統計概覽計算完成', [
                'period' => $period->__toString(),
                'metrics_count' => count($overview['metrics'])
            ]);

            return $overview;

        } catch (Throwable $e) {
            $this->logger->error('取得統計概覽失敗', [
                'period' => $period->__toString(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 分析熱門內容
     *
     * 分析指定週期內的熱門內容，提供詳細的分析資料。
     */
    public function analyzePopularContent(StatisticsPeriod $period, int $limit = 20): array
    {
        try {
            $this->logger->info('分析熱門內容', [
                'period' => $period->__toString(),
                'limit' => $limit
            ]);

            // 使用領域服務分析熱門內容
            $analysis = $this->postStatisticsService->analyzePopularContent($period, $limit);

            return $analysis;

        } catch (Throwable $e) {
            $this->logger->error('分析熱門內容失敗', [
                'period' => $period->__toString(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 產生統計報告
     *
     * 產生指定週期的完整統計報告。
     */
    public function generateStatisticsReport(StatisticsPeriod $period, array $options = []): array
    {
        try {
            $this->logger->info('產生統計報告', [
                'period' => $period->__toString(),
                'options' => $options
            ]);

            // 取得基本概覽
            $overview = $this->getStatisticsOverview($period);

            // 取得熱門內容分析
            $popularContent = $this->analyzePopularContent($period, $options['popular_limit'] ?? 10);

            // 計算趨勢資料
            $trends = $this->calculationService->calculateTrends($period);

            // 組合報告
            $report = [
                'overview' => $overview,
                'popular_content' => $popularContent,
                'trends' => $trends,
                'summary' => [
                    'total_metrics' => count($overview['metrics']),
                    'source_types' => count($overview['source_statistics']),
                    'popular_items' => count($popularContent),
                    'trend_points' => count($trends)
                ],
                'generated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'period_info' => [
                    'display' => $period->__toString(),
                    'duration_days' => $period->getDaysCount()
                ]
            ];

            $this->logger->info('統計報告產生完成', [
                'period' => $period->__toString(),
                'sections' => array_keys($report)
            ]);

            return $report;

        } catch (Throwable $e) {
            $this->logger->error('產生統計報告失敗', [
                'period' => $period->__toString(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 檢查統計服務健康狀態
     */
    public function checkHealthStatus(): array
    {
        try {
            $status = [
                'service' => 'StatisticsApplicationService',
                'status' => 'healthy',
                'checks' => [],
                'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ];

            // 檢查資料庫連線
            $status['checks']['database'] = $this->checkDatabaseHealth();

            // 檢查統計計算
            $status['checks']['calculation'] = $this->checkCalculationHealth();

            // 判斷整體狀態
            $allHealthy = array_reduce(
                $status['checks'],
                fn(bool $carry, array $check) => $carry && $check['status'] === 'ok',
                true
            );

            if (!$allHealthy) {
                $status['status'] = 'degraded';
            }

            return $status;

        } catch (Throwable $e) {
            $this->logger->error('統計服務健康檢查失敗', [
                'error' => $e->getMessage()
            ]);

            return [
                'service' => 'StatisticsApplicationService',
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * 計算來源統計
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
                $percentage
            );
        }

        return $sourceStats;
    }

    /**
     * 檢查資料庫健康狀態
     */
    private function checkDatabaseHealth(): array
    {
        try {
            // 測試基本查詢
            $testPeriod = StatisticsPeriod::today();
            $this->statisticsRepository->findByPeriod($testPeriod);

            return ['status' => 'ok', 'message' => 'Database is accessible'];

        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * 檢查計算服務健康狀態
     */
    private function checkCalculationHealth(): array
    {
        try {
            // 測試計算服務
            $testPeriod = StatisticsPeriod::today();
            $this->calculationService->calculateTrends($testPeriod);

            return ['status' => 'ok', 'message' => 'Calculation service is working'];

        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => 'Calculation error: ' . $e->getMessage()];
        }
    }
}
