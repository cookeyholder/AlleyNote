<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;
use Tests\Support\UnitTestCase;

/**
 * 圖表資料集值物件測試.
 */
final class ChartDatasetTest extends UnitTestCase
{
    public function testConstructAndDefaultValues(): void
    {
        $dataset = new ChartDataset();

        $this->assertSame('', $dataset->label);
        $this->assertSame([], $dataset->data);
        $this->assertSame(ChartType::Line, $dataset->type);
        $this->assertNull($dataset->backgroundColor);
        $this->assertNull($dataset->borderColor);
        $this->assertSame(1, $dataset->borderWidth);
        $this->assertFalse($dataset->fill);
        $this->assertSame([], $dataset->options);
        $this->assertTrue($dataset->isEmpty());
        $this->assertSame(0, $dataset->getDataPointCount());
    }

    public function testForTimeSeries(): void
    {
        $dataset = ChartDataset::forTimeSeries('Views', [10, 20, 30], '#10B981', ['tension' => 0.4]);

        $this->assertSame('Views', $dataset->label);
        $this->assertSame([10, 20, 30], $dataset->data);
        $this->assertSame(ChartType::Line, $dataset->type);
        $this->assertSame('#10B98120', $dataset->backgroundColor);
        $this->assertSame('#10B981', $dataset->borderColor);
        $this->assertSame(2, $dataset->borderWidth);
        $this->assertTrue($dataset->fill);
        $this->assertSame(['tension' => 0.4], $dataset->options);
        $this->assertFalse($dataset->isEmpty());
        $this->assertSame(3, $dataset->getDataPointCount());
    }

    public function testForBarChart(): void
    {
        $dataset = ChartDataset::forBarChart('Posts', [5, 15]);

        $this->assertSame('Posts', $dataset->label);
        $this->assertSame([5, 15], $dataset->data);
        $this->assertSame(ChartType::Bar, $dataset->type);
        $this->assertSame(['#3B82F6', '#EF4444'], $dataset->backgroundColor);
        $this->assertSame(['#3B82F6', '#EF4444'], $dataset->borderColor);
        $this->assertSame(1, $dataset->borderWidth);
        $this->assertFalse($dataset->fill);

        // 指定自訂顏色
        $customDataset = ChartDataset::forBarChart('Posts', [5], ['#000000']);
        $this->assertSame(['#000000'], $customDataset->backgroundColor);
    }

    public function testForPieChartAndDoughnutChart(): void
    {
        $pie = ChartDataset::forPieChart([10, 20, 30]);
        $this->assertSame(ChartType::Pie, $pie->type);
        $this->assertSame('#FFFFFF', $pie->borderColor);
        $this->assertSame(2, $pie->borderWidth);

        $doughnut = ChartDataset::forDoughnutChart([10, 20]);
        $this->assertSame(ChartType::Doughnut, $doughnut->type);
    }

    public function testImmutableWithMethods(): void
    {
        $original = new ChartDataset('Original', [1, 2], ChartType::Line);

        $withType = $original->withType(ChartType::Bar);
        $this->assertSame(ChartType::Bar, $withType->type);
        $this->assertSame(ChartType::Line, $original->type);

        $withBg = $original->withBackgroundColor('#FF0000');
        $this->assertSame('#FF0000', $withBg->backgroundColor);

        $withBorder = $original->withBorderColor('#00FF00');
        $this->assertSame('#00FF00', $withBorder->borderColor);

        $withOptions = $original->withOptions(['responsive' => true]);
        $this->assertSame(['responsive' => true], $withOptions->options);
    }

    public function testJsonSerialization(): void
    {
        $dataset = new ChartDataset(
            label: 'Sales',
            data: [100, 200],
            type: ChartType::Bar,
            backgroundColor: '#3B82F6',
            borderColor: '#1D4ED8',
            borderWidth: 2,
            fill: true,
            options: ['customOption' => 123],
        );

        $json = $dataset->jsonSerialize();

        $this->assertSame([
            'label'           => 'Sales',
            'data'            => [100, 200],
            'type'            => 'bar',
            'borderWidth'     => 2,
            'fill'            => true,
            'backgroundColor' => '#3B82F6',
            'borderColor'     => '#1D4ED8',
            'customOption'    => 123,
        ], $json);
    }
}
