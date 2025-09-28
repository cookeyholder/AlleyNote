<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use JsonSerializable;

/**
 * 圖表資料集值物件.
 *
 * 表示圖表中的一組資料，包含標籤、資料、顏色等屬性
 */
readonly class ChartDataset implements JsonSerializable
{
    /**
     * @param string $label 資料集標籤
     * @param array<mixed> $data 資料點陣列
     * @param ChartType $type 圖表類型
     * @param array<string>|string|null $backgroundColor 背景色
     * @param array<string>|string|null $borderColor 邊框色
     * @param int $borderWidth 邊框寬度
     * @param bool $fill 是否填充
     * @param array<string, mixed> $options 額外選項
     */
    public function __construct(
        public string $label = '',
        public array $data = [],
        public ChartType $type = ChartType::Line,
        public array|string|null $backgroundColor = null,
        public array|string|null $borderColor = null,
        public int $borderWidth = 1,
        public bool $fill = false,
        public array $options = [],
    ) {}

    /**
     * 建立時間序列資料集.
     *
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     */
    public static function forTimeSeries(
        string $label,
        array $data,
        string $color = '#3B82F6',
        array $options = [],
    ): self {
        return new self(
            label: $label,
            data: $data,
            type: ChartType::Line,
            backgroundColor: $color . '20', // 20% 透明度
            borderColor: $color,
            borderWidth: 2,
            fill: true,
            options: $options,
        );
    }

    /**
     * 建立長條圖資料集.
     *
     * @param array<mixed> $data
     * @param array<string>|null $colors
     * @param array<string, mixed> $options
     */
    public static function forBarChart(
        string $label,
        array $data,
        ?array $colors = null,
        array $options = [],
    ): self {
        $defaultColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
            '#8B5CF6', '#EC4899', '#6B7280', '#14B8A6',
        ];

        $backgroundColor = $colors ?? array_slice($defaultColors, 0, count($data));

        return new self(
            label: $label,
            data: $data,
            type: ChartType::Bar,
            backgroundColor: $backgroundColor,
            borderColor: $backgroundColor,
            borderWidth: 1,
            fill: false,
            options: $options,
        );
    }

    /**
     * 建立圓餅圖資料集.
     *
     * @param array<mixed> $data
     * @param array<string>|null $colors
     * @param array<string, mixed> $options
     */
    public static function forPieChart(
        array $data,
        ?array $colors = null,
        array $options = [],
    ): self {
        $defaultColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
            '#8B5CF6', '#EC4899', '#6B7280', '#14B8A6',
            '#F97316', '#84CC16', '#06B6D4', '#8B5A2B',
        ];

        $backgroundColor = $colors ?? array_slice($defaultColors, 0, count($data));

        return new self(
            label: '',
            data: $data,
            type: ChartType::Pie,
            backgroundColor: $backgroundColor,
            borderColor: '#FFFFFF',
            borderWidth: 2,
            fill: false,
            options: $options,
        );
    }

    /**
     * 建立甜甜圈圖資料集.
     *
     * @param array<mixed> $data
     * @param array<string>|null $colors
     * @param array<string, mixed> $options
     */
    public static function forDoughnutChart(
        array $data,
        ?array $colors = null,
        array $options = [],
    ): self {
        return self::forPieChart($data, $colors, $options)
            ->withType(ChartType::Doughnut);
    }

    /**
     * 設定圖表類型.
     */
    public function withType(ChartType $type): self
    {
        return new self(
            label: $this->label,
            data: $this->data,
            type: $type,
            backgroundColor: $this->backgroundColor,
            borderColor: $this->borderColor,
            borderWidth: $this->borderWidth,
            fill: $this->fill,
            options: $this->options,
        );
    }

    /**
     * 設定背景色.
     *
     * @param array<string>|string $backgroundColor
     */
    public function withBackgroundColor(array|string $backgroundColor): self
    {
        return new self(
            label: $this->label,
            data: $this->data,
            type: $this->type,
            backgroundColor: $backgroundColor,
            borderColor: $this->borderColor,
            borderWidth: $this->borderWidth,
            fill: $this->fill,
            options: $this->options,
        );
    }

    /**
     * 設定邊框色.
     *
     * @param array<string>|string $borderColor
     */
    public function withBorderColor(array|string $borderColor): self
    {
        return new self(
            label: $this->label,
            data: $this->data,
            type: $this->type,
            backgroundColor: $this->backgroundColor,
            borderColor: $borderColor,
            borderWidth: $this->borderWidth,
            fill: $this->fill,
            options: $this->options,
        );
    }

    /**
     * 設定額外選項.
     *
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): self
    {
        $mergedOptions = array_merge($this->options, $options);

        return new self(
            label: $this->label,
            data: $this->data,
            type: $this->type,
            backgroundColor: $this->backgroundColor,
            borderColor: $this->borderColor,
            borderWidth: $this->borderWidth,
            fill: $this->fill,
            options: $mergedOptions,
        );
    }

    /**
     * 檢查資料集是否為空.
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * 取得資料點數量.
     */
    public function getDataPointCount(): int
    {
        return count($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'label' => $this->label,
            'data' => $this->data,
            'type' => $this->type->value,
            'borderWidth' => $this->borderWidth,
            'fill' => $this->fill,
        ];

        if ($this->backgroundColor !== null) {
            $result['backgroundColor'] = $this->backgroundColor;
        }

        if ($this->borderColor !== null) {
            $result['borderColor'] = $this->borderColor;
        }

        // 合併額外選項
        return array_merge($result, $this->options);
    }
}
