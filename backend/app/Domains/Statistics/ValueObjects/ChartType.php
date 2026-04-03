<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

enum ChartType: string
{
    case Line = 'line';
    case Bar = 'bar';
    case Pie = 'pie';
    case Doughnut = 'doughnut';

    /**
     * @return array<string, mixed>
     */
    public function getDefaultOptions(): array
    {
        return match ($this) {
            self::Line => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'top'],
                ],
                'interaction' => ['mode' => 'index', 'intersect' => false],
            ],
            self::Bar => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'top'],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
            self::Pie,
            self::Doughnut => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'right'],
                ],
            ],
        };
    }
}
