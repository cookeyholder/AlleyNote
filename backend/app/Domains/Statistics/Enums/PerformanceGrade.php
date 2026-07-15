<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Enums;

/**
 * 內容效能評級列舉.
 */
enum PerformanceGrade: string
{
    case EXCELLENT = 'EXCELLENT';
    case GOOD = 'GOOD';
    case AVERAGE = 'AVERAGE';
    case POOR = 'POOR';
    case CRITICAL = 'CRITICAL';

    /**
     * 從分數建立評級.
     *
     * @param float $score 0-100 的分數
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 80 => self::EXCELLENT,
            $score >= 60 => self::GOOD,
            $score >= 40 => self::AVERAGE,
            $score >= 20 => self::POOR,
            default      => self::CRITICAL,
        };
    }
}
