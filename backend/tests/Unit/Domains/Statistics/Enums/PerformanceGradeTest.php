<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Enums;

use App\Domains\Statistics\Enums\PerformanceGrade;
use PHPUnit\Framework\TestCase;

/**
 * PerformanceGrade 列舉單元測試.
 */
final class PerformanceGradeTest extends TestCase
{
    public function testFromScoreExcellent(): void
    {
        $this->assertSame(PerformanceGrade::EXCELLENT, PerformanceGrade::fromScore(85.0));
        $this->assertSame(PerformanceGrade::EXCELLENT, PerformanceGrade::fromScore(80.0));
        $this->assertSame(PerformanceGrade::EXCELLENT, PerformanceGrade::fromScore(100.0));
    }

    public function testFromScoreGood(): void
    {
        $this->assertSame(PerformanceGrade::GOOD, PerformanceGrade::fromScore(75.0));
        $this->assertSame(PerformanceGrade::GOOD, PerformanceGrade::fromScore(60.0));
        $this->assertSame(PerformanceGrade::GOOD, PerformanceGrade::fromScore(79.99));
    }

    public function testFromScoreAverage(): void
    {
        $this->assertSame(PerformanceGrade::AVERAGE, PerformanceGrade::fromScore(55.0));
        $this->assertSame(PerformanceGrade::AVERAGE, PerformanceGrade::fromScore(40.0));
        $this->assertSame(PerformanceGrade::AVERAGE, PerformanceGrade::fromScore(59.99));
    }

    public function testFromScorePoor(): void
    {
        $this->assertSame(PerformanceGrade::POOR, PerformanceGrade::fromScore(35.0));
        $this->assertSame(PerformanceGrade::POOR, PerformanceGrade::fromScore(20.0));
        $this->assertSame(PerformanceGrade::POOR, PerformanceGrade::fromScore(39.99));
    }

    public function testFromScoreCritical(): void
    {
        $this->assertSame(PerformanceGrade::CRITICAL, PerformanceGrade::fromScore(15.0));
        $this->assertSame(PerformanceGrade::CRITICAL, PerformanceGrade::fromScore(0.0));
        $this->assertSame(PerformanceGrade::CRITICAL, PerformanceGrade::fromScore(19.99));
    }

    public function testEnumValues(): void
    {
        $this->assertSame('EXCELLENT', PerformanceGrade::EXCELLENT->value);
        $this->assertSame('GOOD', PerformanceGrade::GOOD->value);
        $this->assertSame('AVERAGE', PerformanceGrade::AVERAGE->value);
        $this->assertSame('POOR', PerformanceGrade::POOR->value);
        $this->assertSame('CRITICAL', PerformanceGrade::CRITICAL->value);
    }
}
