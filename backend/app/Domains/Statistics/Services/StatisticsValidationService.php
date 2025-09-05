<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\Exceptions\InvalidStatisticsPeriodException;
use App\Domains\Statistics\Exceptions\InvalidStatisticsSnapshotException;
use DateTimeImmutable;
use DateInterval;

/**
 * 統計驗證服務
 * 負責驗證統計相關資料的有效性.
 */
class StatisticsValidationService
{
    /**
     * 驗證統計期間.
     */
    public function validatePeriod(StatisticsPeriod $period): bool
    {
        $now = new DateTimeImmutable();

        // 檢查開始時間不能晚於結束時間
        if ($period->startDate > $period->endDate) {
            throw new InvalidStatisticsPeriodException(
                '開始時間不能晚於結束時間',
            );
        }

        // 檢查結束時間不能晚於當前時間
        if ($period->endDate > $now) {
            throw new InvalidStatisticsPeriodException(
                '結束時間不能晚於當前時間',
            );
        }

        // 檢查期間長度是否合理
        $interval = $period->startDate->diff($period->endDate);
        $days = $interval->days;

        if ($days > 365) {
            throw new InvalidStatisticsPeriodException(
                '統計期間不能超過一年',
            );
        }

        return true;
    }

    /**
     * 驗證統計指標.
     */
    public function validateMetric(StatisticsMetric $metric): bool
    {
        // 檢查數值的合理性
        if (is_float($metric->value) && !is_finite($metric->value)) {
            throw new InvalidStatisticsSnapshotException(
                '統計指標數值必須是有限數值',
            );
        }

        // 檢查精確度
        if ($metric->precision < 0 || $metric->precision > 10) {
            throw new InvalidStatisticsSnapshotException(
                '統計指標精確度必須在 0-10 之間',
            );
        }

        return true;
    }

    /**
     * 驗證來源統計列表.
     *
     * @param array<SourceStatistics> $sourceStats
     */
    public function validateSourceStats(array $sourceStats): bool
    {
        $totalPercentage = 0.0;

        foreach ($sourceStats as $sourceStat) {
            if (!$sourceStat instanceof SourceStatistics) {
                throw new InvalidStatisticsSnapshotException(
                    '來源統計必須是 SourceStatistics 實例',
                );
            }

            $totalPercentage += $sourceStat->percentage->value;
        }

        // 檢查總百分比是否合理（允許一定誤差）
        if (abs($totalPercentage - 100.0) > 0.1 && $totalPercentage > 0) {
            throw new InvalidStatisticsSnapshotException(
                "來源統計總百分比應該為 100%，實際為 {$totalPercentage}%",
            );
        }

        return true;
    }

    /**
     * 驗證統計資料的一致性.
     */
    public function validateDataConsistency(
        StatisticsMetric $totalPosts,
        StatisticsMetric $totalViews,
        array $sourceStats,
    ): bool {
        // 檢查總數的合理性
        if ($totalPosts->value < 0 || $totalViews->value < 0) {
            throw new InvalidStatisticsSnapshotException(
                '統計總數不能為負值',
            );
        }

        // 檢查瀏覽數與文章數的比例是否合理
        if ($totalPosts->value > 0 && $totalViews->value / $totalPosts->value > 10000) {
            throw new InvalidStatisticsSnapshotException(
                '平均每篇文章瀏覽數過高，可能資料有誤',
            );
        }

        // 驗證來源統計
        $this->validateSourceStats($sourceStats);

        return true;
    }

    /**
     * 驗證期間類型與時間範圍的匹配性.
     */
    public function validatePeriodTypeConsistency(StatisticsPeriod $period): bool
    {
        $interval = $period->startDate->diff($period->endDate);
        $days = $interval->days;

        $expectedDays = match ($period->type) {
            \App\Domains\Statistics\Enums\PeriodType::DAILY => 1,
            \App\Domains\Statistics\Enums\PeriodType::WEEKLY => 7,
            \App\Domains\Statistics\Enums\PeriodType::MONTHLY => 30, // 簡化處理
            \App\Domains\Statistics\Enums\PeriodType::YEARLY => 365,
        };

        // 允許一定的誤差範圍
        if (abs($days - $expectedDays) > 2) {
            throw new InvalidStatisticsPeriodException(
                "期間類型 {$period->type->value} 與實際天數 {$days} 不匹配",
            );
        }

        return true;
    }

    /**
     * 驗證批量生成參數.
     */
    public function validateBatchParameters(array $periods): bool
    {
        if (empty($periods)) {
            throw new InvalidStatisticsPeriodException(
                '批量生成參數不能為空',
            );
        }

        if (count($periods) > 100) {
            throw new InvalidStatisticsPeriodException(
                '批量生成數量不能超過 100 個',
            );
        }

        foreach ($periods as $period) {
            if (!$period instanceof StatisticsPeriod) {
                throw new InvalidStatisticsPeriodException(
                    '批量生成參數必須是 StatisticsPeriod 實例',
                );
            }

            $this->validatePeriod($period);
        }

        return true;
    }

    /**
     * 驗證統計快照更新資料.
     */
    public function validateSnapshotUpdate(
        StatisticsMetric $newTotalPosts,
        StatisticsMetric $newTotalViews,
        array $newSourceStats,
    ): bool {
        // 驗證新的統計指標
        $this->validateMetric($newTotalPosts);
        $this->validateMetric($newTotalViews);

        // 驗證資料一致性
        $this->validateDataConsistency(
            $newTotalPosts,
            $newTotalViews,
            $newSourceStats,
        );

        return true;
    }

    /**
     * 驗證清理參數.
     */
    public function validateCleanupParameters(int $retentionDays): bool
    {
        if ($retentionDays < 1) {
            throw new InvalidStatisticsPeriodException(
                '保留天數必須大於 0',
            );
        }

        if ($retentionDays < 30) {
            throw new InvalidStatisticsPeriodException(
                '保留天數不能少於 30 天',
            );
        }

        if ($retentionDays > 3650) { // 10 年
            throw new InvalidStatisticsPeriodException(
                '保留天數不能超過 10 年',
            );
        }

        return true;
    }
}
