<?php

declare(strict_types=1);

namespace App\Application\DTOs\Statistics;

use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 統計概覽資料傳輸物件.
 *
 * 用於傳輸統計概覽資料的 DTO 類別。
 * 包含統計週期、基本指標、來源統計等資訊。
 *
 * 設計原則：
 * - 不可變物件 (Immutable)
 * - 支援 JSON 序列化
 * - 包含資料驗證邏輯
 * - 提供格式化方法
 */
final readonly class StatisticsOverviewDTO implements JsonSerializable
{
    /**
     * @param StatisticsPeriod $period 統計週期
     * @param StatisticsMetric $totalPosts 總文章數
     * @param StatisticsMetric $totalViews 總瀏覽數
     * @param array<SourceStatistics> $sourceStatistics 來源統計
     * @param array<string, mixed> $additionalMetrics 額外指標
     * @param DateTimeImmutable $generatedAt 產生時間
     */
    public function __construct(
        public StatisticsPeriod $period,
        public StatisticsMetric $totalPosts,
        public StatisticsMetric $totalViews,
        public array $sourceStatistics,
        public array $additionalMetrics,
        public DateTimeImmutable $generatedAt,
    ) {
        $this->validateSourceStatistics($sourceStatistics);
    }

    /**
     * 從統計快照建立 DTO.
     */
    public static function fromSnapshot(
        StatisticsPeriod $period,
        StatisticsMetric $totalPosts,
        StatisticsMetric $totalViews,
        array $sourceStatistics,
        array $additionalMetrics = [],
    ): self {
        return new self(
            $period,
            $totalPosts,
            $totalViews,
            $sourceStatistics,
            $additionalMetrics,
            new DateTimeImmutable(),
        );
    }

    /**
     * 從陣列資料建立 DTO.
     */
    public static function fromArray(array $data): self
    {
        $period = StatisticsPeriod::create(
            new DateTimeImmutable($data['period']['start_date']),
            new DateTimeImmutable($data['period']['end_date']),
            PeriodType::from($data['period']['type']),
        );

        $totalPosts = StatisticsMetric::count(
            $data['total_posts']['value'],
            $data['total_posts']['description'] ?? '總文章數',
        );

        $totalViews = StatisticsMetric::count(
            $data['total_views']['value'],
            $data['total_views']['description'] ?? '總瀏覽數',
        );

        $sourceStatistics = array_map(
            fn(array $sourceData) => SourceStatistics::create(
                SourceType::from($sourceData['source_type']),
                $sourceData['count'],
                $sourceData['percentage'],
            ),
            $data['source_statistics'] ?? [],
        );

        return new self(
            $period,
            $totalPosts,
            $totalViews,
            $sourceStatistics,
            $data['additional_metrics'] ?? [],
            new DateTimeImmutable($data['generated_at'] ?? 'now'),
        );
    }

    /**
     * 取得格式化的統計概覽.
     */
    public function getFormattedOverview(): array
    {
        return [
            'period_info' => [
                'display_name' => $this->period->getDisplayName(),
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'duration_days' => $this->period->getDaysCount(),
                'type' => $this->period->type->value,
            ],
            'key_metrics' => [
                'total_posts' => [
                    'value' => $this->totalPosts->value,
                    'formatted' => $this->totalPosts->getFormattedValueWithUnit(),
                    'description' => $this->totalPosts->description,
                ],
                'total_views' => [
                    'value' => $this->totalViews->value,
                    'formatted' => $this->totalViews->getFormattedValueWithUnit(),
                    'description' => $this->totalViews->description,
                ],
                'avg_views_per_post' => $this->calculateAverageViewsPerPost(),
            ],
            'source_distribution' => array_map(
                fn(SourceStatistics $source) => [
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
            'additional_metrics' => $this->additionalMetrics,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得摘要資訊.
     */
    public function getSummary(): array
    {
        return [
            'period' => $this->period->getDisplayName(),
            'total_posts' => $this->totalPosts->value,
            'total_views' => $this->totalViews->value,
            'avg_views_per_post' => $this->calculateAverageViewsPerPost(),
            'source_types_count' => count($this->sourceStatistics),
            'top_source' => $this->getTopSource(),
        ];
    }

    /**
     * 取得主要來源.
     */
    public function getTopSource(): ?array
    {
        if (empty($this->sourceStatistics)) {
            return null;
        }

        $topSource = array_reduce(
            $this->sourceStatistics,
            fn(?SourceStatistics $carry, SourceStatistics $source) => $carry === null || $source->count->value > $carry->count->value ? $source : $carry,
        );

        return $topSource ? [
            'source_type' => $topSource->sourceType->value,
            'count' => $topSource->count->value,
            'percentage' => $topSource->percentage->value,
        ] : null;
    }

    /**
     * 計算平均每篇文章瀏覽數.
     */
    public function calculateAverageViewsPerPost(): float
    {
        if ($this->totalPosts->value === 0) {
            return 0.0;
        }

        return round($this->totalViews->value / $this->totalPosts->value, 2);
    }

    /**
     * 檢查是否有成長.
     */
    public function hasGrowth(): bool
    {
        // 如果有額外指標包含成長率資訊
        if (isset($this->additionalMetrics['growth_rate'])) {
            return $this->additionalMetrics['growth_rate'] > 0;
        }

        // 基本判斷：有文章和瀏覽數就算有成長
        return $this->totalPosts->value > 0 && $this->totalViews->value > 0;
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
                'display_name' => $this->period->getDisplayName(),
                'duration_days' => $this->period->getDaysCount(),
            ],
            'total_posts' => [
                'value' => $this->totalPosts->value,
                'unit' => $this->totalPosts->unit,
                'description' => $this->totalPosts->description,
                'formatted' => $this->totalPosts->getFormattedValueWithUnit(),
            ],
            'total_views' => [
                'value' => $this->totalViews->value,
                'unit' => $this->totalViews->unit,
                'description' => $this->totalViews->description,
                'formatted' => $this->totalViews->getFormattedValueWithUnit(),
            ],
            'source_statistics' => array_map(
                fn(SourceStatistics $source) => [
                    'source_type' => $source->sourceType->value,
                    'count' => [
                        'value' => $source->count->value,
                        'unit' => $source->count->unit,
                        'formatted' => $source->count->getFormattedValueWithUnit(),
                    ],
                    'percentage' => [
                        'value' => $source->percentage->value,
                        'unit' => $source->percentage->unit,
                        'formatted' => $source->percentage->getFormattedValueWithUnit(),
                    ],
                ],
                $this->sourceStatistics,
            ),
            'additional_metrics' => $this->additionalMetrics,
            'calculated_metrics' => [
                'avg_views_per_post' => $this->calculateAverageViewsPerPost(),
                'has_growth' => $this->hasGrowth(),
                'top_source' => $this->getTopSource(),
            ],
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * JSON 序列化.
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
        return sprintf(
            'StatisticsOverview[%s: %d posts, %d views]',
            $this->period->getDisplayName(),
            $this->totalPosts->value,
            $this->totalViews->value,
        );
    }

    /**
     * 驗證來源統計資料.
     */
    private function validateSourceStatistics(array $sourceStatistics): void
    {
        foreach ($sourceStatistics as $index => $source) {
            if (!$source instanceof SourceStatistics) {
                throw new InvalidArgumentException(
                    "來源統計索引 {$index} 必須是 SourceStatistics 實例",
                );
            }
        }
    }
}
