<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\InvalidStatisticsPeriodException;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * 統計計算核心領域服務.
 *
 * 負責統計資料的計算、聚合與快照生成的核心業務邏輯。
 * 封裝複雜的統計計算演算法，確保統計資料的一致性與準確性。
 *
 * 主要功能：
 * - 統計快照的計算與產生
 * - 多週期統計資料聚合
 * - 成長率與趨勢分析
 * - 來源分布統計計算
 * - 快照比較與差異分析
 *
 * 設計原則：
 * - 純領域邏輯，不依賴基礎設施層
 * - 高內聚、低耦合的設計
 * - 可測試性優先
 * - 遵循 DDD 領域服務模式
 */
class StatisticsCalculatorService
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
    ) {}

    /**
     * 產生指定週期的統計快照.
     */
    public function generateSnapshot(StatisticsPeriod $period): StatisticsSnapshot
    {
        // 檢查是否已存在該週期的快照
        if ($this->statisticsRepository->existsForPeriod($period)) {
            throw new StatisticsCalculationException(
                sprintf('統計快照已存在於週期 %s', $period),
            );
        }

        // 計算基礎統計資料
        $totalCount = $this->postStatisticsRepository->countViewsByPeriod($period);
        $uniqueCount = $this->postStatisticsRepository->countUniqueViewersByPeriod($period);

        // 計算來源統計
        $sourceStats = $this->calculateSourceStatistics($period);

        // 建立統計快照
        $snapshot = StatisticsSnapshot::create(
            Uuid::generate(),
            $period,
            0, // 總文章數，暫時設為0，後續可擴展
            $totalCount, // 總觀看次數
            $sourceStats,
        );

        // 儲存快照
        $savedSnapshot = $this->statisticsRepository->saveSnapshot($snapshot);

        return $savedSnapshot;
    }

    /**
     * 更新現有統計快照.
     */
    public function updateSnapshot(string $snapshotId): StatisticsSnapshot
    {
        $snapshot = $this->statisticsRepository->findByUuid($snapshotId);
        if (!$snapshot) {
            throw new StatisticsCalculationException(
                "找不到 ID 為 {$snapshotId} 的統計快照",
            );
        }

        // 重新計算統計資料
        $period = $snapshot->getPeriod();
        $totalCount = $this->postStatisticsRepository->countViewsByPeriod($period);
        $uniqueCount = $this->postStatisticsRepository->countUniqueViewersByPeriod($period);
        $sourceStats = $this->calculateSourceStatistics($period);

        // 更新快照
        $snapshot->updateViewCount($totalCount);

        // 更新來源統計
        $snapshot->updateSourceStats($sourceStats);

        // 儲存更新
        $updatedSnapshot = $this->statisticsRepository->saveSnapshot($snapshot);

        return $updatedSnapshot;
    }

    /**
     * 計算指定週期的來源統計.
     *
     * @return SourceStatistics[]
     */
    public function calculateSourceStatistics(StatisticsPeriod $period): array
    {
        $sourceStats = [];
        $totalViews = $this->postStatisticsRepository->countViewsByPeriod($period);

        // 如果沒有觀看記錄，返回空統計
        if ($totalViews === 0) {
            foreach (SourceType::cases() as $sourceType) {
                $sourceStats[] = SourceStatistics::empty($sourceType);
            }

            return $sourceStats;
        }

        // 計算各來源的統計資料
        foreach (SourceType::cases() as $sourceType) {
            $sourceViewStats = $this->postStatisticsRepository->getViewStatisticsBySource(
                $period,
                $sourceType,
            );

            $count = 0;
            $uniqueViewers = 0;

            if (!empty($sourceViewStats)) {
                $stats = $sourceViewStats[0];
                $count = $stats['view_count'];
                $uniqueViewers = $stats['unique_viewers'];
            }

            $percentage = $totalViews > 0 ? ($count / $totalViews) * 100 : 0.0;

            $additionalMetrics = [
                'unique_viewers' => StatisticsMetric::count($uniqueViewers, '不重複觀看者'),
            ];

            $sourceStats[] = SourceStatistics::create(
                $sourceType,
                $count,
                $percentage,
                $additionalMetrics,
            );
        }

        return $sourceStats;
    }

    /**
     * 聚合子週期統計為上層週期統計.
     */
    public function aggregateSubPeriods(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $sourcePeriodType,
        PeriodType $targetPeriodType,
    ): StatisticsSnapshot {
        // 驗證週期類型的合理性
        $this->validatePeriodAggregation($sourcePeriodType, $targetPeriodType);

        // 建立目標週期
        $targetPeriod = StatisticsPeriod::create($startDate, $endDate, $targetPeriodType);

        // 取得子週期的快照
        $subSnapshots = $this->statisticsRepository->findByDateRange(
            $startDate,
            $endDate,
            $sourcePeriodType,
        );

        if (empty($subSnapshots)) {
            throw new StatisticsCalculationException(
                '找不到可聚合的子週期統計快照',
            );
        }

        // 聚合計算
        $aggregatedData = $this->performAggregation($subSnapshots);

        // 建立聚合快照
        $snapshot = StatisticsSnapshot::create(
            Uuid::generate(),
            $targetPeriod,
            0, // 總文章數，暫時設為0
            (int) $aggregatedData['total_count'], // 總觀看次數，確保為整數
            $aggregatedData['source_stats'],
        );

        return $snapshot;
    }

    /**
     * 計算成長率統計.
     *
     * @return array{
     *     current_total: int,
     *     previous_total: int,
     *     growth_count: int,
     *     growth_rate: float,
     *     growth_percentage: float
     * }
     */
    public function calculateGrowthRate(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
    ): array {
        $currentSnapshot = $this->statisticsRepository->findByPeriod($currentPeriod);
        $previousSnapshot = $this->statisticsRepository->findByPeriod($previousPeriod);

        if (!$currentSnapshot) {
            throw new StatisticsCalculationException(
                '找不到當前週期的統計快照',
            );
        }

        $currentTotal = $currentSnapshot->getTotalViews()->value;
        $previousTotal = $previousSnapshot ? $previousSnapshot->getTotalViews()->value : 0;

        $growthCount = $currentTotal - $previousTotal;
        $growthRate = $previousTotal > 0 ? $growthCount / $previousTotal : 0.0;
        $growthPercentage = $growthRate * 100;

        return [
            'current_total' => (int) $currentTotal,
            'previous_total' => (int) $previousTotal,
            'growth_count' => (int) $growthCount,
            'growth_rate' => $growthRate,
            'growth_percentage' => $growthPercentage,
        ];
    }

    /**
     * 比較兩個統計快照的差異.
     */
    public function compareSnapshots(
        StatisticsSnapshot $snapshot1,
        StatisticsSnapshot $snapshot2,
    ): array {
        $comparison = [
            'period_1' => $snapshot1->getPeriod()->toArray(),
            'period_2' => $snapshot2->getPeriod()->toArray(),
            'total_count_diff' => $snapshot1->getTotalViews()->value - $snapshot2->getTotalViews()->value,
            'unique_count_diff' => 0, // 當前實體設計沒有獨立的唯一計數，暫時設為0
            'source_comparisons' => [],
        ];

        // 比較來源統計
        $sources1 = $snapshot1->getSourceStats();
        $sources2 = $snapshot2->getSourceStats();

        foreach ($sources1 as $source1) {
            $sourceType = $source1->sourceType;
            $source2 = $this->findSourceStatInArray($sources2, $sourceType);

            $comparison['source_comparisons'][] = [
                'source_type' => $sourceType->value,
                'count_diff' => $source1->getCountValue() - ($source2 ? $source2->getCountValue() : 0),
                'percentage_diff' => $source1->getPercentageValue() - ($source2 ? $source2->getPercentageValue() : 0),
            ];
        }

        return $comparison;
    }

    /**
     * 計算統計趨勢分析.
     *
     * @param StatisticsSnapshot[] $snapshots
     */
    public function calculateTrend(array $snapshots): array
    {
        if (count($snapshots) < 2) {
            throw new StatisticsCalculationException(
                '至少需要兩個統計快照才能計算趨勢',
            );
        }

        // 按時間排序
        usort(
            $snapshots,
            fn($a, $b) => $a->getPeriod()->startDate <=> $b->getPeriod()->startDate,
        );

        $trendData = [];
        for ($i = 1; $i < count($snapshots); $i++) {
            $current = $snapshots[$i];
            $previous = $snapshots[$i - 1];

            $growthData = $this->calculateGrowthRate(
                $current->getPeriod(),
                $previous->getPeriod(),
            );

            $trendData[] = [
                'period' => $current->getPeriod()->toArray(),
                'growth_data' => $growthData,
                'is_growing' => $growthData['growth_rate'] > 0,
                'growth_magnitude' => abs($growthData['growth_rate']),
            ];
        }

        return [
            'trend_points' => $trendData,
            'overall_direction' => $this->determineOverallTrend($trendData),
            'average_growth_rate' => $this->calculateAverageGrowthRate($trendData),
        ];
    }

    /**
     * 產生週期性快照批次.
     *
     * @return StatisticsSnapshot[]
     */
    public function generatePeriodicSnapshots(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $periodType,
    ): array {
        $snapshots = [];
        $currentDate = DateTimeImmutable::createFromInterface($startDate);
        $endDateTime = DateTimeImmutable::createFromInterface($endDate);

        while ($currentDate <= $endDateTime) {
            $periodEnd = $this->calculatePeriodEnd($currentDate, $periodType);

            if ($periodEnd > $endDateTime) {
                break;
            }

            $period = StatisticsPeriod::create($currentDate, $periodEnd, $periodType);

            // 如果該週期已有快照則跳過
            if (!$this->statisticsRepository->existsForPeriod($period)) {
                $snapshot = $this->generateSnapshot($period);
                $snapshots[] = $snapshot;
            }

            $currentDate = $this->getNextPeriodStart($currentDate, $periodType);
        }

        return $snapshots;
    }

    /**
     * 執行聚合計算.
     *
     * @param StatisticsSnapshot[] $snapshots
     */
    private function performAggregation(array $snapshots): array
    {
        $totalCount = 0;
        $uniqueCount = 0;
        $sourceAggregates = [];

        foreach ($snapshots as $snapshot) {
            $totalCount += (int) $snapshot->getTotalViews()->value;
            // 暫時用總觀看數代替唯一計數，實際實作時需要擴展實體設計
            $uniqueCount += (int) $snapshot->getTotalViews()->value;

            foreach ($snapshot->getSourceStats() as $sourceStat) {
                $sourceType = $sourceStat->sourceType->value;

                if (!isset($sourceAggregates[$sourceType])) {
                    $sourceAggregates[$sourceType] = [
                        'count' => 0,
                        'unique_viewers' => 0,
                    ];
                }

                $sourceAggregates[$sourceType]['count'] += $sourceStat->getCountValue();

                if ($sourceStat->hasAdditionalMetric('unique_viewers')) {
                    $sourceAggregates[$sourceType]['unique_viewers']
                        += (int) $sourceStat->getAdditionalMetric('unique_viewers')->value;
                }
            }
        }

        // 轉換為 SourceStatistics 陣列
        $sourceStats = [];
        foreach ($sourceAggregates as $sourceTypeValue => $data) {
            $sourceType = SourceType::fromValue($sourceTypeValue);
            if ($sourceType) {
                $percentage = $totalCount > 0 ? ($data['count'] / $totalCount) * 100 : 0.0;

                $additionalMetrics = [
                    'unique_viewers' => StatisticsMetric::count(
                        $data['unique_viewers'],
                        '不重複觀看者',
                    ),
                ];

                $sourceStats[] = SourceStatistics::create(
                    $sourceType,
                    $data['count'],
                    $percentage,
                    $additionalMetrics,
                );
            }
        }

        return [
            'total_count' => $totalCount,
            'unique_count' => $uniqueCount,
            'source_stats' => $sourceStats,
        ];
    }

    /**
     * 驗證週期聚合的合理性.
     */
    private function validatePeriodAggregation(
        PeriodType $sourcePeriodType,
        PeriodType $targetPeriodType,
    ): void {
        $validAggregations = [
            PeriodType::DAILY->value => [PeriodType::WEEKLY, PeriodType::MONTHLY, PeriodType::YEARLY],
            PeriodType::WEEKLY->value => [PeriodType::MONTHLY, PeriodType::YEARLY],
            PeriodType::MONTHLY->value => [PeriodType::YEARLY],
        ];

        if (!isset($validAggregations[$sourcePeriodType->value])
            || !in_array($targetPeriodType, $validAggregations[$sourcePeriodType->value], true)) {
            throw new InvalidStatisticsPeriodException(
                sprintf(
                    '無法將 %s 聚合為 %s',
                    $sourcePeriodType->getDisplayName(),
                    $targetPeriodType->getDisplayName(),
                ),
            );
        }
    }

    /**
     * 計算週期結束時間.
     */
    private function calculatePeriodEnd(DateTimeImmutable $start, PeriodType $periodType): DateTimeImmutable
    {
        return match ($periodType) {
            PeriodType::DAILY => $start->setTime(23, 59, 59),
            PeriodType::WEEKLY => $start->modify('next sunday')->setTime(23, 59, 59),
            PeriodType::MONTHLY => $start->modify('last day of this month')->setTime(23, 59, 59),
            PeriodType::YEARLY => $start->setDate((int) $start->format('Y'), 12, 31)->setTime(23, 59, 59),
        };
    }

    /**
     * 取得下一個週期的開始時間.
     */
    private function getNextPeriodStart(DateTimeImmutable $current, PeriodType $periodType): DateTimeImmutable
    {
        return match ($periodType) {
            PeriodType::DAILY => $current->modify('+1 day')->setTime(0, 0, 0),
            PeriodType::WEEKLY => $current->modify('next monday')->setTime(0, 0, 0),
            PeriodType::MONTHLY => $current->modify('first day of next month')->setTime(0, 0, 0),
            PeriodType::YEARLY => $current->modify('+1 year')->setDate((int) $current->format('Y') + 1, 1, 1)->setTime(0, 0, 0),
        };
    }

    /**
     * 在陣列中尋找指定來源類型的統計.
     *
     * @param SourceStatistics[] $sourceStats
     */
    private function findSourceStatInArray(array $sourceStats, SourceType $sourceType): ?SourceStatistics
    {
        foreach ($sourceStats as $sourceStat) {
            if ($sourceStat->sourceType === $sourceType) {
                return $sourceStat;
            }
        }

        return null;
    }

    /**
     * 判斷整體趨勢方向.
     */
    private function determineOverallTrend(array $trendData): string
    {
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($trendData as $trend) {
            if ($trend['growth_data']['growth_rate'] > 0) {
                $positiveCount++;
            } elseif ($trend['growth_data']['growth_rate'] < 0) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'growing';
        } elseif ($negativeCount > $positiveCount) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * 計算平均成長率.
     */
    private function calculateAverageGrowthRate(array $trendData): float
    {
        if (empty($trendData)) {
            return 0.0;
        }

        $totalGrowthRate = 0.0;
        foreach ($trendData as $trend) {
            $totalGrowthRate += $trend['growth_data']['growth_rate'];
        }

        return $totalGrowthRate / count($trendData);
    }
}
