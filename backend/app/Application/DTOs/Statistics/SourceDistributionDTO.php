<?php

declare(strict_types=1);

namespace App\Application\DTOs\Statistics;

use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 來源分佈統計資料傳輸物件.
 *
 * 用於傳輸來源分佈統計資料的 DTO 類別。
 * 包含各種來源的統計資訊、分佈分析、趨勢資訊等。
 *
 * 設計原則：
 * - 不可變物件 (Immutable)
 * - 支援 JSON 序列化
 * - 包含資料驗證邏輯
 * - 提供分佈分析方法
 */
final readonly class SourceDistributionDTO implements JsonSerializable
{
    /**
     * @param StatisticsPeriod $period 統計週期
     * @param array $sourceStatistics 來源統計資料
     * @param int $totalCount 總數量
     * @param array $distributionAnalysis 分佈分析
     * @param DateTimeImmutable $generatedAt 產生時間
     */
    public function __construct(
        public StatisticsPeriod $period,
        /** @var array<SourceStatistics> */
        public array $sourceStatistics,
        public int $totalCount,
        /** @var array<string, mixed> */
        public array $distributionAnalysis,
        public DateTimeImmutable $generatedAt,
    ) {
        $this->validateSourceStatistics($this->sourceStatistics);
        $this->validateTotalCount($this->totalCount);
    }

    /**
     * 從來源統計資料建立 DTO.
     *
     * @param array<SourceStatistics> $sourceStatistics
     */
    public static function fromSourceStatistics(
        StatisticsPeriod $period,
        array $sourceStatistics,
        int $totalCount,
    ): self {
        /** @var array<string, mixed> $analysis */
        $analysis = self::calculateDistributionAnalysis($sourceStatistics, $totalCount);

        return new self(
            $period,
            $sourceStatistics,
            $totalCount,
            $analysis,
            new DateTimeImmutable(),
        );
    }

    /**
     * 從陣列資料建立 DTO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // 使用型別安全的方式存取期間資料
        /** @var array<string, mixed> $periodData */
        $periodData = is_array($data['period'] ?? []) ? $data['period'] : [];

        // 型別安全地取得日期字串
        $periodStartDate = $periodData['start_date'] ?? null;
        $startDate = is_string($periodStartDate) ? $periodStartDate : 'now';

        $periodEndDate = $periodData['end_date'] ?? null;
        $endDate = is_string($periodEndDate) ? $periodEndDate : 'now';

        $periodTypeValue = $periodData['type'] ?? null;
        $periodType = is_string($periodTypeValue) || is_int($periodTypeValue) ? $periodTypeValue : 'daily';

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 確保來源統計資料是陣列
        $sourceStatsDataRaw = $data['source_statistics'] ?? [];
        /** @var array<array<string, mixed>> $sourceStatsData */
        $sourceStatsData = is_array($sourceStatsDataRaw) ? array_filter($sourceStatsDataRaw, 'is_array') : [];

        /** @var array<SourceStatistics> $sourceStatistics */
        $sourceStatistics = array_map(
            static function (array $sourceData): SourceStatistics {
                $sourceTypeValue = $sourceData['source_type'] ?? null;
                $sourceType = is_string($sourceTypeValue) || is_int($sourceTypeValue) ? $sourceTypeValue : 'web';

                $countValue = $sourceData['count'] ?? null;
                $count = is_numeric($countValue) ? (int) $countValue : 0;

                $percentageValue = $sourceData['percentage'] ?? null;
                $percentage = is_numeric($percentageValue) ? (float) $percentageValue : 0.0;

                return SourceStatistics::create(
                    SourceType::from($sourceType),
                    $count,
                    $percentage,
                );
            },
            $sourceStatsData,
        );

        $totalCountValue = $data['total_count'] ?? null;
        $totalCount = is_numeric($totalCountValue) ? (int) $totalCountValue : 0;

        /** @var array<string, mixed> $distributionAnalysisRaw */
        $distributionAnalysisRaw = $data['distribution_analysis'] ?? [];
        /** @var array<string, mixed> $distributionAnalysis */
        $distributionAnalysis = $distributionAnalysisRaw;

        $generatedAtValue = $data['generated_at'] ?? null;
        $generatedAt = is_string($generatedAtValue) ? $generatedAtValue : 'now';

        // 型別已經透過 PHPDoc 確保
        return new self(
            $period,
            $sourceStatistics,
            $totalCount,
            $distributionAnalysis,
            new DateTimeImmutable($generatedAt),
        );
    }

    /**
     * 取得主要來源.
     */
    public function getDominantSource(): ?SourceStatistics
    {
        if (empty($this->sourceStatistics)) {
            return null;
        }

        /** @var SourceStatistics|null */
        return array_reduce(
            $this->sourceStatistics,
            fn(?SourceStatistics $carry, SourceStatistics $source): SourceStatistics => $carry === null || $source->count->value > $carry->count->value ? $source : $carry,
        );
    }

    /**
     * 取得最少來源.
     */
    public function getMinorSource(): ?SourceStatistics
    {
        if (empty($this->sourceStatistics)) {
            return null;
        }

        /** @var SourceStatistics|null */
        return array_reduce(
            $this->sourceStatistics,
            fn(?SourceStatistics $carry, SourceStatistics $source): SourceStatistics => $carry === null || $source->count->value < $carry->count->value ? $source : $carry,
        );
    }

    /**
     * 取得多樣性指數 (Diversity Index).
     */
    public function getDiversityIndex(): float
    {
        if (empty($this->sourceStatistics) || $this->totalCount === 0) {
            return 0.0;
        }

        // 使用 Shannon 多樣性指數
        $shannon = 0.0;
        foreach ($this->sourceStatistics as $source) {
            if ($source->count->value > 0) {
                $proportion = $source->count->value / $this->totalCount;
                $shannon -= $proportion * log($proportion);
            }
        }

        return round($shannon, 4);
    }

    /**
     * 取得集中度指數 (Concentration Index).
     */
    public function getConcentrationIndex(): float
    {
        if (empty($this->sourceStatistics) || $this->totalCount === 0) {
            return 0.0;
        }

        // 使用 Herfindahl-Hirschman 指數
        $hhi = 0.0;
        foreach ($this->sourceStatistics as $source) {
            $proportion = $source->count->value / $this->totalCount;
            $hhi += $proportion * $proportion;
        }

        return round($hhi, 4);
    }

    /**
     * 檢查分佈是否平均.
     */
    public function isBalancedDistribution(float $threshold = 0.5): bool
    {
        return $this->getConcentrationIndex() < $threshold;
    }

    /**
     * 檢查是否有主導來源.
     */
    public function hasDominantSource(float $threshold = 0.5): bool
    {
        $dominant = $this->getDominantSource();

        return $dominant !== null && ($dominant->count->value / $this->totalCount) > $threshold;
    }

    /**
     * 取得前 N 個來源.
     *
     * @return array<array<string, mixed>>
     */
    public function getTopSources(int $limit = 5): array
    {
        $sorted = $this->sourceStatistics;
        usort($sorted, fn(SourceStatistics $a, SourceStatistics $b): int => $b->count->value <=> $a->count->value);

        return array_slice(array_map(fn(SourceStatistics $source): array => [
            'source' => $source->sourceType->value,
            'count' => $source->count->value,
            'percentage' => $this->totalCount > 0 ? round(($source->count->value / $this->totalCount) * 100, 2) : 0,
        ], $sorted), 0, $limit);
    }

    /**
     * 取得來源排名.
     *
     * @return array<array<string, mixed>>
     */
    public function getSourceRanking(): array
    {
        $sorted = $this->sourceStatistics;
        usort($sorted, fn(SourceStatistics $a, SourceStatistics $b): int => $b->count->value <=> $a->count->value);

        return array_map(
            fn(SourceStatistics $source, int $index): array => [
                'rank' => $index + 1,
                'source_type' => $source->sourceType->value,
                'count' => $source->count->value,
                'percentage' => $source->percentage->value,
                'is_top_3' => $index < 3,
            ],
            $sorted,
            array_keys($sorted),
        );
    }

    /**
     * 取得分佈摘要
     *
     * @return array<string, mixed>
     */
    public function getDistributionSummary(): array
    {
        $dominant = $this->getDominantSource();
        $minor = $this->getMinorSource();

        return [
            'total_sources' => count($this->sourceStatistics),
            'total_count' => $this->totalCount,
            'dominant_source' => $dominant ? [
                'type' => $dominant->sourceType->value,
                'count' => $dominant->count->value,
                'percentage' => $dominant->percentage->value,
            ] : null,
            'minor_source' => $minor ? [
                'type' => $minor->sourceType->value,
                'count' => $minor->count->value,
                'percentage' => $minor->percentage->value,
            ] : null,
            'diversity_index' => $this->getDiversityIndex(),
            'concentration_index' => $this->getConcentrationIndex(),
            'is_balanced' => $this->isBalancedDistribution(),
            'has_dominant' => $this->hasDominantSource(),
        ];
    }

    /**
     * 取得格式化的分佈資訊.
     *
     * @return array<string, mixed>
     */
    public function getFormattedDistribution(): array
    {
        return [
            'period_info' => [
                'display_name' => $this->period->getDisplayName(),
                'start_date' => $this->period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
            ],
            'overview' => [
                'total_count' => $this->totalCount,
                'source_types_count' => count($this->sourceStatistics),
                'diversity_index' => $this->getDiversityIndex(),
                'concentration_index' => $this->getConcentrationIndex(),
            ],
            'sources' => array_map(
                fn(SourceStatistics $source): array => [
                    'source_type' => $source->sourceType->value,
                    'count' => [
                        'value' => $source->count->value,
                        'formatted' => $source->count->getFormattedValueWithUnit(),
                    ],
                    'percentage' => [
                        'value' => $source->percentage->value,
                        'formatted' => $source->percentage->getFormattedValueWithUnit(),
                    ],
                ],
                $this->sourceStatistics,
            ),
            'ranking' => $this->getSourceRanking(),
            'analysis' => $this->distributionAnalysis,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 比較與另一個分佈的差異.
     *
     * @return array<string, mixed>
     */
    public function compareWith(SourceDistributionDTO $other): array
    {
        $changes = [];

        // 建立來源類型對應表
        $thisSourceMap = [];
        foreach ($this->sourceStatistics as $source) {
            $thisSourceMap[$source->sourceType->value] = $source;
        }

        $otherSourceMap = [];
        foreach ($other->sourceStatistics as $source) {
            $otherSourceMap[$source->sourceType->value] = $source;
        }

        // 比較各來源的變化
        $allSourceTypes = array_unique(array_merge(
            array_keys($thisSourceMap),
            array_keys($otherSourceMap),
        ));

        foreach ($allSourceTypes as $sourceType) {
            $thisCount = $thisSourceMap[$sourceType]->count->value ?? 0;
            $otherCount = $otherSourceMap[$sourceType]->count->value ?? 0;

            $changes[$sourceType] = [
                'current_count' => $thisCount,
                'previous_count' => $otherCount,
                'absolute_change' => $thisCount - $otherCount,
                'percentage_change' => $otherCount > 0
                    ? round((($thisCount - $otherCount) / $otherCount) * 100, 2)
                    : ($thisCount > 0 ? 100 : 0),
            ];
        }

        return [
            'period_comparison' => [
                'current' => $this->period->__toString(),
                'previous' => $other->period->__toString(),
            ],
            'total_change' => [
                'current' => $this->totalCount,
                'previous' => $other->totalCount,
                'absolute_change' => $this->totalCount - $other->totalCount,
                'percentage_change' => $other->totalCount > 0
                    ? round((($this->totalCount - $other->totalCount) / $other->totalCount) * 100, 2)
                    : ($this->totalCount > 0 ? 100 : 0),
            ],
            'diversity_change' => round($this->getDiversityIndex() - $other->getDiversityIndex(), 4),
            'source_changes' => $changes,
            'significant_changes' => array_filter(
                $changes,
                fn(array $change): bool => abs($change['percentage_change']) >= 10,
            ),
        ];
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $this->period->endDate->format('Y-m-d H => i => s'),
                'type' => $this->period->type->value,
                'display_name' => $this->period->getDisplayName(),
            ],
            'total_count' => $this->totalCount,
            'source_statistics' => array_map(
                fn(SourceStatistics $source): array => [
                    'source_type' => $source->sourceType->value,
                    'count' => $source->count->value,
                    'percentage' => $source->percentage->value,
                ],
                $this->sourceStatistics,
            ),
            'analysis' => $this->getDistributionSummary(),
            'ranking' => $this->getSourceRanking(),
            'distribution_analysis' => $this->distributionAnalysis,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * JSON 序列化.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        $dominant = $this->getDominantSource();

        return sprintf(
            'SourceDistribution[%s => %d total, dominant => %s]',
            $this->period->getDisplayName(),
            $this->totalCount,
            $dominant ? $dominant->sourceType->value : 'none',
        );
    }

    /**
     * 計算分佈分析.
     *
     * @param array<SourceStatistics> $sourceStatistics
     * @return array<string, mixed>
     */
    private static function calculateDistributionAnalysis(array $sourceStatistics, int $totalCount): array
    {
        if (empty($sourceStatistics) || $totalCount === 0) {
            return [];
        }

        // 計算多樣性指數
        $shannon = 0.0;
        $hhi = 0.0;

        foreach ($sourceStatistics as $source) {
            // 型別已透過 PHPDoc 保證為 SourceStatistics
            if ($source->count->value > 0) {
                $sourceCount = $source->count->value;
                $proportion = $sourceCount / $totalCount;
                $shannon -= $proportion * log($proportion);
                $hhi += $proportion * $proportion;
            }
        }

        return [
            'diversity_index' => round($shannon, 4),
            'concentration_index' => round($hhi, 4),
            'source_count' => count($sourceStatistics),
            'analysis_type' => 'basic_distribution',
            'calculated_at' => new DateTimeImmutable()->format('Y-m-d H => i => s'),
        ];
    }

    /**
     * 驗證來源統計資料.
     *
     * @param array<SourceStatistics> $sourceStatistics
     */
    private function validateSourceStatistics(array $sourceStatistics): void
    {
        foreach ($sourceStatistics as $index => $source) {
            // 基本型別檢查已通過 PHPDoc，這裡只做業務邏輯驗證

            // 基本驗證：確保有有效的統計值
            if ($source->count->value < 0) {
                throw new InvalidArgumentException(
                    "來源統計索引 {$index} 的計數不能為負數",
                );
            }
        }
    }

    /**
     * 驗證總數量.
     */
    private function validateTotalCount(int $totalCount): void
    {
        if ($totalCount < 0) {
            throw new InvalidArgumentException('總數量不能為負數');
        }
    }
}
