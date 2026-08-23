<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\ChartType;
use Tests\Support\UnitTestCase;

/**
 * 圖表類型列舉測試.
 */
final class ChartTypeTest extends UnitTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('line', ChartType::Line->value);
        $this->assertSame('bar', ChartType::Bar->value);
        $this->assertSame('pie', ChartType::Pie->value);
        $this->assertSame('doughnut', ChartType::Doughnut->value);
    }

    public function testDefaultOptionsForLine(): void
    {
        $options = ChartType::Line->getDefaultOptions();
        $plugins = $options['plugins'];
        $this->assertIsArray($plugins);

        $this->assertTrue($options['responsive']);
        $this->assertFalse($options['maintainAspectRatio']);
        $this->assertSame(['mode' => 'index', 'intersect' => false], $options['interaction']);
        $this->assertSame(['display' => true, 'position' => 'top'], $plugins['legend']);
    }

    public function testDefaultOptionsForBar(): void
    {
        $options = ChartType::Bar->getDefaultOptions();
        $plugins = $options['plugins'];
        $this->assertIsArray($plugins);

        $this->assertTrue($options['responsive']);
        $this->assertFalse($options['maintainAspectRatio']);
        $this->assertSame(['y' => ['beginAtZero' => true]], $options['scales']);
        $this->assertSame(['display' => true, 'position' => 'top'], $plugins['legend']);
    }

    public function testDefaultOptionsForPieAndDoughnut(): void
    {
        $pieOptions = ChartType::Pie->getDefaultOptions();
        $piePlugins = $pieOptions['plugins'];
        $this->assertIsArray($piePlugins);
        $this->assertSame(['display' => true, 'position' => 'right'], $piePlugins['legend']);

        $doughnutOptions = ChartType::Doughnut->getDefaultOptions();
        $doughnutPlugins = $doughnutOptions['plugins'];
        $this->assertIsArray($doughnutPlugins);
        $this->assertSame(['display' => true, 'position' => 'right'], $doughnutPlugins['legend']);
    }
}
