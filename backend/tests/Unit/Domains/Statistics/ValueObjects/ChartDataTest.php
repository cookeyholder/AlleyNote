<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\CategoryDataPoint;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;
use App\Domains\Statistics\ValueObjects\TimeSeriesDataPoint;
use Tests\Support\UnitTestCase;

/**
 * 圖表資料值物件測試.
 */
final class ChartDataTest extends UnitTestCase
{
    public function testForTimeSeries(): void
    {
        $points = [
            new TimeSeriesDataPoint('2025-01-01', 10.0),
            new TimeSeriesDataPoint('2025-01-02', 20.0),
        ];

        $chart = ChartData::forTimeSeries($points, 'Traffic', ['responsive' => true]);

        $this->assertSame(['2025-01-01', '2025-01-02'], $chart->labels);
        $this->assertCount(1, $chart->datasets);
        $this->assertSame('Traffic', $chart->datasets[0]->label);
        $this->assertSame([10.0, 20.0], $chart->datasets[0]->data);
        $this->assertSame(['responsive' => true], $chart->options);
        $this->assertFalse($chart->isEmpty());
        $this->assertSame(2, $chart->getDataPointCount());
    }

    public function testForCategory(): void
    {
        $points = [
            new CategoryDataPoint('Mobile', 60.0, '#3B82F6'),
            new CategoryDataPoint('Desktop', 40.0, '#EF4444'),
        ];

        $chart = ChartData::forCategory($points, 'Devices', ChartType::Pie);

        $this->assertSame(['Mobile', 'Desktop'], $chart->labels);
        $this->assertCount(1, $chart->datasets);
        $this->assertSame(ChartType::Pie, $chart->datasets[0]->type);
        $this->assertSame([60.0, 40.0], $chart->datasets[0]->data);
        $this->assertSame(['#3B82F6', '#EF4444'], $chart->datasets[0]->backgroundColor);
    }

    public function testForMultiDataset(): void
    {
        $dataset1 = new ChartDataset('D1', [1, 2]);
        $dataset2 = new ChartDataset('D2', [3, 4]);

        $chart = ChartData::forMultiDataset(['A', 'B'], [$dataset1, $dataset2]);

        $this->assertSame(['A', 'B'], $chart->labels);
        $this->assertCount(2, $chart->datasets);
        $this->assertSame(4, $chart->getDataPointCount());
    }

    public function testWithDatasetAndWithOptions(): void
    {
        $chart = new ChartData(['Jan'], [new ChartDataset('2024', [10])]);

        $newDataset = new ChartDataset('2025', [15]);
        $updatedChart = $chart->withDataset($newDataset);

        $this->assertCount(2, $updatedChart->datasets);
        $this->assertCount(1, $chart->datasets); // 不可變性

        $withOptions = $updatedChart->withOptions(['animation' => false]);
        $this->assertSame(['animation' => false], $withOptions->options);
    }

    public function testIsEmpty(): void
    {
        $empty1 = new ChartData([], []);
        $this->assertTrue($empty1->isEmpty());

        $empty2 = new ChartData(['Jan'], [new ChartDataset('D', [])]);
        $this->assertTrue($empty2->isEmpty());

        $notEmpty = new ChartData(['Jan'], [new ChartDataset('D', [10])]);
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testJsonSerialization(): void
    {
        $dataset = new ChartDataset('Line', [1, 2], ChartType::Line);
        $chart = new ChartData(['L1', 'L2'], [$dataset], ['opt' => 1]);

        $json = $chart->jsonSerialize();

        $this->assertArrayHasKey('labels', $json);
        $this->assertArrayHasKey('datasets', $json);
        $this->assertArrayHasKey('options', $json);
        $this->assertSame(['L1', 'L2'], $json['labels']);
        $datasets = $json['datasets'];
        $this->assertIsArray($datasets);
        $this->assertCount(1, $datasets);
        $this->assertSame(['opt' => 1], $json['options']);
    }
}
