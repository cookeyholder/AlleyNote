<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use JsonSerializable;

/**
 * 圖表資料值物件.
 *
 * 用於統一前端圖表庫（如 Chart.js）的資料格式
 * 支援多種圖表類型的資料結構
 */
readonly class ChartData implements JsonSerializable
{
    /**
     * @param array<string> $labels 圖表標籤（X軸或分類）
     * @param array<ChartDataset> $datasets 資料集合
     * @param array<string, mixed> $options 圖表選項
     */
    public function __construct(
        public array $labels,
        public array $datasets,
        public array $options = [],
    ) {}

    /**
     * 建立時間序列圖表資料.
     *
     * @param array<TimeSeriesDataPoint> $dataPoints
     * @param array<string, mixed> $options
     */
    public static function forTimeSeries(
        array $dataPoints,
        string $label = '',
        array $options = [],
    ): self {
        $labels = [];
        $data = [];

        foreach ($dataPoints as $point) {
            $labels[] = $point->timestamp;
            $data[] = $point->value;
        }

        $dataset = new ChartDataset(
            label: $label,
            data: $data,
            type: ChartType::Line,
        );

        return new self(
            labels: $labels,
            datasets: [$dataset],
            options: $options,
        );
    }

    /**
     * 建立分類統計圖表資料.
     *
     * @param array<CategoryDataPoint> $dataPoints
     * @param array<string, mixed> $options
     */
    public static function forCategory(
        array $dataPoints,
        string $label = '',
        ChartType $type = ChartType::Bar,
        array $options = [],
    ): self {
        $labels = [];
        $data = [];
        $backgroundColors = [];

        foreach ($dataPoints as $point) {
            $labels[] = $point->category;
            $data[] = $point->value;
            $backgroundColors[] = $point->color;
        }

        $dataset = new ChartDataset(
            label: $label,
            data: $data,
            type: $type,
            backgroundColor: $backgroundColors,
        );

        return new self(
            labels: $labels,
            datasets: [$dataset],
            options: $options,
        );
    }

    /**
     * 建立多資料集圖表資料.
     *
     * @param array<string> $labels
     * @param array<ChartDataset> $datasets
     * @param array<string, mixed> $options
     */
    public static function forMultiDataset(
        array $labels,
        array $datasets,
        array $options = [],
    ): self {
        return new self(
            labels: $labels,
            datasets: $datasets,
            options: $options,
        );
    }

    /**
     * 加入額外的資料集.
     */
    public function withDataset(ChartDataset $dataset): self
    {
        $datasets = [...$this->datasets, $dataset];

        return new self(
            labels: $this->labels,
            datasets: $datasets,
            options: $this->options,
        );
    }

    /**
     * 設定圖表選項.
     *
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self
    {
        $mergedOptions = array_merge($this->options, $options);

        return new self(
            labels: $this->labels,
            datasets: $this->datasets,
            options: $mergedOptions,
        );
    }

    /**
     * 檢查是否為空資料.
     */
    public function isEmpty(): bool
    {
        return empty($this->datasets)
               || (count($this->datasets) === 1 && empty($this->datasets[0]->data));
    }

    /**
     * 取得資料點總數.
     */
    public function getDataPointCount(): int
    {
        $totalCount = 0;
        foreach ($this->datasets as $dataset) {
            $totalCount += count($dataset->data);
        }

        return $totalCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'labels' => $this->labels,
            'datasets' => array_map(
                fn(ChartDataset $dataset) => $dataset->jsonSerialize(),
                $this->datasets,
            ),
            'options' => $this->options,
        ];
    }
}
