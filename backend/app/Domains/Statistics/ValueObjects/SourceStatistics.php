<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\InvalidSourceStatisticsException;

/**
 * 來源統計值物件
 * 表示特定來源的統計資料.
 */
readonly class SourceStatistics
{
    /**
     * @param SourceType $sourceType 來源類型
     * @param StatisticsMetric $percentage 百分比
     */
    private function __construct(
        public SourceType $sourceType,
        public StatisticsMetric $count,
        public StatisticsMetric $percentage,
        /** @var array<string, StatisticsMetric> */
        public array $additionalMetrics = [],
    ) {}

    /**
     * 建立來源統計.
     * @param array<string, StatisticsMetric> $additionalMetrics
     */
    public static function create(
        SourceType $sourceType,
        int $count,
        float $percentage,
        array $additionalMetrics = [],
    ): self {
        if ($count < 0) {
            throw new InvalidSourceStatisticsException(
                '計數不能為負數',
            );
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidSourceStatisticsException(
                '百分比必須在 0-100 之間',
            );
        }

        $countMetric = StatisticsMetric::count($count);
        $percentageMetric = StatisticsMetric::percentage($percentage);

        // 驗證額外指標
        foreach ($additionalMetrics as $key => $metric) {
            // instanceof 檢查由於 @var 註解的型別保證而總是為 true，
            // 但我們仍保留它以確保執行時的型別安全
            if (!$metric instanceof StatisticsMetric) {
                throw new InvalidSourceStatisticsException(
                    "額外指標 '{$key}' 必須是 StatisticsMetric 實例",
                );
            }
        }

        return new self($sourceType, $countMetric, $percentageMetric, $additionalMetrics);
    }

    /**
     * 建立空的來源統計.
     */
    public static function empty(SourceType $sourceType): self
    {
        return new self(
            $sourceType,
            StatisticsMetric::count(0),
            StatisticsMetric::percentage(0.0),
        );
    }

    /**
     * 取得來源名稱.
     */
    public function getSourceName(): string
    {
        return $this->sourceType->getDisplayName();
    }

    /**
     * 取得來源描述.
     */
    public function getSourceDescription(): string
    {
        return $this->sourceType->getDescription();
    }

    /**
     * 取得計數值.
     */
    public function getCountValue(): int
    {
        return (int) $this->count->value;
    }

    /**
     * 取得百分比值.
     */
    public function getPercentageValue(): float
    {
        return (float) $this->percentage->value;
    }

    /**
     * 判斷是否有資料.
     */
    public function hasData(): bool
    {
        return !$this->count->isZero();
    }

    /**
     * 判斷是否為主要來源.
     */
    public function isPrimarySource(): bool
    {
        return $this->sourceType->isPrimarySource();
    }

    /**
     * 判斷是否為程式化存取.
     */
    public function isProgrammaticAccess(): bool
    {
        return $this->sourceType->isProgrammaticAccess();
    }

    /**
     * 判斷是否為外部來源.
     */
    public function isExternalSource(): bool
    {
        return $this->sourceType->isExternalSource();
    }

    /**
     * 取得額外指標.
     */
    public function getAdditionalMetric(string $key): ?StatisticsMetric
    {
        return $this->additionalMetrics[$key] ?? null;
    }

    /**
     * 判斷是否有額外指標.
     */
    public function hasAdditionalMetric(string $key): bool
    {
        return isset($this->additionalMetrics[$key]);
    }

    /**
     * 取得所有額外指標的鍵.
     * @return array<string>
     */
    public function getAdditionalMetricKeys(): array
    {
        return array_keys($this->additionalMetrics);
    }

    /**
     * 新增額外指標.
     */
    public function withAdditionalMetric(string $key, StatisticsMetric $metric): self
    {
        $newMetrics = $this->additionalMetrics;
        $newMetrics[$key] = $metric;

        return new self(
            $this->sourceType,
            $this->count,
            $this->percentage,
            $newMetrics,
        );
    }

    /**
     * 移除額外指標.
     */
    public function withoutAdditionalMetric(string $key): self
    {
        $newMetrics = $this->additionalMetrics;
        unset($newMetrics[$key]);

        return new self(
            $this->sourceType,
            $this->count,
            $this->percentage,
            $newMetrics,
        );
    }

    /**
     * 更新計數和百分比.
     */
    public function updateCountAndPercentage(int $newCount, float $newPercentage): self
    {
        return self::create(
            $this->sourceType,
            $newCount,
            $newPercentage,
            $this->additionalMetrics,
        );
    }

    /**
     * 合併來源統計.
     */
    public function merge(SourceStatistics $other): self
    {
        if ($this->sourceType !== $other->sourceType) {
            throw new InvalidSourceStatisticsException(
                '只能合併相同來源類型的統計資料',
            );
        }

        $newCount = $this->getCountValue() + $other->getCountValue();

        // 百分比需要重新計算，這裡暫時相加（調用方應該重新計算）
        $newPercentage = $this->getPercentageValue() + $other->getPercentageValue();

        // 合併額外指標
        $mergedMetrics = $this->additionalMetrics;
        foreach ($other->additionalMetrics as $key => $metric) {
            if (isset($mergedMetrics[$key])) {
                $mergedMetrics[$key] = $mergedMetrics[$key]->add($metric);
            } else {
                $mergedMetrics[$key] = $metric;
            }
        }

        return self::create(
            $this->sourceType,
            $newCount,
            min($newPercentage, 100.0), // 確保不超過100%
            $mergedMetrics,
        );
    }

    /**
     * 比較兩個來源統計（按計數排序）.
     */
    public function compareTo(SourceStatistics $other): int
    {
        return $other->getCountValue() <=> $this->getCountValue(); // 降序
    }

    /**
     * 判斷是否相等.
     */
    public function equals(SourceStatistics $other): bool
    {
        if ($this->sourceType !== $other->sourceType) {
            return false;
        }

        if (!$this->count->equals($other->count) || !$this->percentage->equals($other->percentage)) {
            return false;
        }

        if (count($this->additionalMetrics) !== count($other->additionalMetrics)) {
            return false;
        }

        foreach ($this->additionalMetrics as $key => $metric) {
            if (!isset($other->additionalMetrics[$key]) || !$metric->equals($other->additionalMetrics[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得排序權重（用於排序）.
     */
    public function getSortWeight(): int
    {
        // 結合優先順序和計數來計算權重
        $priorityWeight = (11 - $this->sourceType->getPriority()) * 1000000; // 優先順序越高權重越大
        $countWeight = $this->getCountValue();

        return $priorityWeight + $countWeight;
    }

    /**
     * 轉換為陣列.
     * @return array
     *               source_type: string,
     *               source_name: string,
     *               source_description: string,
     *               count: array,
     *               percentage: array,
     *               additional_metrics: array,
     *               has_data: bool,
     *               is_primary_source: bool,
     *               is_programmatic_access: bool,
     *               is_external_source: bool
     *               }
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $additionalMetricsArray = [];
        foreach ($this->additionalMetrics as $key => $metric) {
            $additionalMetricsArray[$key] = $metric->toArray();
        }

        return [
            'source_type' => $this->sourceType->value,
            'source_name' => $this->getSourceName(),
            'source_description' => $this->getSourceDescription(),
            'count' => $this->count->toArray(),
            'percentage' => $this->percentage->toArray(),
            'additional_metrics' => $additionalMetricsArray,
            'has_data' => $this->hasData(),
            'is_primary_source' => $this->isPrimarySource(),
            'is_programmatic_access' => $this->isProgrammaticAccess(),
            'is_external_source' => $this->isExternalSource(),
        ];
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s: %s (%s)',
            $this->getSourceName(),
            $this->count->getFormattedValueWithUnit(),
            $this->percentage->getFormattedValueWithUnit(),
        );
    }
}
