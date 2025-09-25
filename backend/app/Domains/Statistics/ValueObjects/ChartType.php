<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

/**
 * 圖表類型列舉.
 */
enum ChartType: string
{
    case Line = 'line';
    case Bar = 'bar';
    case Pie = 'pie';
    case Doughnut = 'doughnut';
    case Radar = 'radar';
    case PolarArea = 'polarArea';
    case Scatter = 'scatter';
    case Bubble = 'bubble';

    /**
     * 取得圖表類型的顯示名稱.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::Line => '線圖',
            self::Bar => '長條圖',
            self::Pie => '圓餅圖',
            self::Doughnut => '甜甜圈圖',
            self::Radar => '雷達圖',
            self::PolarArea => '極區圖',
            self::Scatter => '散點圖',
            self::Bubble => '泡泡圖',
        };
    }

    /**
     * 檢查是否為時間序列圖表類型.
     */
    public function isTimeSeries(): bool
    {
        return match ($this) {
            self::Line, self::Bar, self::Scatter => true,
            default => false,
        };
    }

    /**
     * 檢查是否為分類統計圖表類型.
     */
    public function isCategory(): bool
    {
        return match ($this) {
            self::Pie, self::Doughnut, self::Bar, self::PolarArea => true,
            default => false,
        };
    }

    /**
     * 取得推薦的預設選項.
     *
     * @return array<string, mixed>
     */
    public function getDefaultOptions(): array
    {
        return match ($this) {
            self::Line => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'type' => 'time',
                        'time' => [
                            'unit' => 'day',
                        ],
                    ],
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
                'elements' => [
                    'point' => [
                        'radius' => 3,
                    ],
                ],
            ],
            self::Bar => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
            self::Pie, self::Doughnut => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                    ],
                ],
            ],
            self::Radar => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'r' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
            self::PolarArea => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                    ],
                ],
            ],
            self::Scatter => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'type' => 'linear',
                        'position' => 'bottom',
                    ],
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
            self::Bubble => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'x' => [
                        'type' => 'linear',
                        'position' => 'bottom',
                    ],
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        };
    }
}
