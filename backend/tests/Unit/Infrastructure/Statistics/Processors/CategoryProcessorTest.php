<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Processors;

use App\Domains\Statistics\ValueObjects\ChartType;
use App\Infrastructure\Statistics\Processors\CategoryProcessor;
use Tests\Support\UnitTestCase;

/**
 * 分類統計處理器測試.
 */
final class CategoryProcessorTest extends UnitTestCase
{
    private CategoryProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CategoryProcessor();
    }

    public function testProcessPieChartData(): void
    {
        // 空資料
        $emptyChart = $this->processor->processPieChartData([]);
        $this->assertTrue($emptyChart->isEmpty());

        // 有資料
        $rawData = [
            ['category' => 'Chrome', 'value' => 60.0],
            ['category' => 'Safari', 'value' => 30.0],
            ['category' => 'Firefox', 'value' => 10.0],
        ];

        $chart = $this->processor->processPieChartData($rawData, 'Browser Share');
        $this->assertSame(['Chrome', 'Safari', 'Firefox'], $chart->labels);
        $this->assertSame(ChartType::Pie, $chart->datasets[0]->type);
        $this->assertSame([60.0, 30.0, 10.0], $chart->datasets[0]->data);
    }

    public function testProcessDoughnutChartData(): void
    {
        $rawData = [
            ['category' => 'Desktop', 'value' => 70.0],
            ['category' => 'Mobile', 'value' => 30.0],
        ];

        $chart = $this->processor->processDoughnutChartData($rawData, 'Device Share');
        $this->assertSame(ChartType::Doughnut, $chart->datasets[0]->type);
        $this->assertSame(['Desktop', 'Mobile'], $chart->labels);
    }

    public function testProcessBarChartData(): void
    {
        // 空資料
        $emptyChart = $this->processor->processBarChartData([]);
        $this->assertTrue($emptyChart->isEmpty());

        // 有資料
        $rawData = [
            ['category' => 'Cat A', 'value' => 15.0],
            ['category' => 'Cat B', 'value' => 25.0],
        ];

        $chart = $this->processor->processBarChartData($rawData, 'Bar Stats');
        $this->assertSame(['Cat B', 'Cat A'], $chart->labels); // 自動降序排序
        $this->assertSame(ChartType::Bar, $chart->datasets[0]->type);
        $this->assertSame([25.0, 15.0], $chart->datasets[0]->data);
    }

    public function testProcessPercentageDistributionData(): void
    {
        // 空資料或 total <= 0
        $this->assertTrue($this->processor->processPercentageDistributionData([])->isEmpty());
        $this->assertTrue($this->processor->processPercentageDistributionData([['category' => 'A', 'value' => 0.0]])->isEmpty());

        $rawData = [
            ['category' => 'A', 'value' => 25.0],
            ['category' => 'B', 'value' => 75.0],
        ];

        $chart = $this->processor->processPercentageDistributionData($rawData, 'Percentage');
        $this->assertSame(['B', 'A'], $chart->labels);
        $this->assertSame([75.0, 25.0], $chart->datasets[0]->data);
    }

    public function testProcessTopNData(): void
    {
        // 空資料或 topN <= 0
        $this->assertTrue($this->processor->processTopNData([], 5)->isEmpty());
        $this->assertTrue($this->processor->processTopNData([['category' => 'A', 'value' => 1]], 0)->isEmpty());

        $rawData = [
            ['category' => 'A', 'value' => 50],
            ['category' => 'B', 'value' => 40],
            ['category' => 'C', 'value' => 30],
            ['category' => 'D', 'value' => 20],
            ['category' => 'E', 'value' => 10],
        ];

        // Top 2 且包含其他
        $chart = $this->processor->processTopNData($rawData, 2, 'Top 2', ChartType::Bar, null, true);
        $this->assertSame(['A', 'B', '其他'], $chart->labels);
        $this->assertSame([50.0, 40.0, 60.0], $chart->datasets[0]->data);

        // Top 2 不包含其他
        $chartNoOthers = $this->processor->processTopNData($rawData, 2, 'Top 2', ChartType::Bar, null, false);
        $this->assertSame(['A', 'B'], $chartNoOthers->labels);
        $this->assertSame([50.0, 40.0], $chartNoOthers->datasets[0]->data);
    }

    public function testProcessComparisonDataAndStackedBarData(): void
    {
        // 空資料
        $this->assertTrue($this->processor->processComparisonData([])->isEmpty());

        $multiSeries = [
            '2024' => [
                ['category' => 'Q1', 'value' => 100.0],
                ['category' => 'Q2', 'value' => 120.0],
            ],
            '2025' => [
                ['category' => 'Q1', 'value' => 110.0],
                ['category' => 'Q2', 'value' => 140.0],
            ],
        ];

        $chart = $this->processor->processComparisonData($multiSeries, 'Year Comparison');
        $this->assertSame(['Q1', 'Q2'], $chart->labels);
        $this->assertCount(2, $chart->datasets);
        $this->assertSame('2024', $chart->datasets[0]->label);
        $this->assertSame([100.0, 120.0], $chart->datasets[0]->data);

        // 堆疊長條圖
        $stackedChart = $this->processor->processStackedBarData($multiSeries, 'Stacked');
        $this->assertArrayHasKey('scales', $stackedChart->options);
        $this->assertIsArray($stackedChart->options['scales']);
        $this->assertIsArray($stackedChart->options['scales']['x']);
        $this->assertTrue($stackedChart->options['scales']['x']['stacked']);
        $this->assertIsArray($stackedChart->options['scales']['y']);
        $this->assertTrue($stackedChart->options['scales']['y']['stacked']);
    }

    public function testGetColorScheme(): void
    {
        $this->assertNotEmpty($this->processor->getColorScheme('pastel'));
        $this->assertNotEmpty($this->processor->getColorScheme('vibrant'));
        $this->assertNotEmpty($this->processor->getColorScheme('monochrome'));
        $this->assertNotEmpty($this->processor->getColorScheme('business'));
        $this->assertNotEmpty($this->processor->getColorScheme('default'));
    }

    public function testProcessHorizontalBarChartData(): void
    {
        $rawData = [
            ['category' => 'Zebra', 'value' => 10.0],
            ['category' => 'Apple', 'value' => 50.0],
        ];

        // 依 value 排序
        $chartVal = $this->processor->processHorizontalBarChartData($rawData, 'By Value', 'value');
        $this->assertSame(['Apple', 'Zebra'], $chartVal->labels);
        $this->assertSame('y', $chartVal->options['indexAxis']);

        // 依 category 排序
        $chartCat = $this->processor->processHorizontalBarChartData($rawData, 'By Cat', 'category');
        $this->assertSame(['Apple', 'Zebra'], $chartCat->labels);
    }

    public function testProcessCustomChartData(): void
    {
        $rawData = [['category' => 'A', 'value' => 10.0]];

        $pie = $this->processor->processCustomChartData($rawData, 'M', ['type' => 'pie']);
        $this->assertSame(ChartType::Pie, $pie->datasets[0]->type);

        $bar = $this->processor->processCustomChartData($rawData, 'M', ['type' => 'bar']);
        $this->assertSame(ChartType::Bar, $bar->datasets[0]->type);

        $hBar = $this->processor->processCustomChartData($rawData, 'M', ['type' => 'horizontal-bar']);
        $this->assertSame('y', $hBar->options['indexAxis']);
    }

    public function testProcessCategoryDataAndRankingData(): void
    {
        $rawData = [
            ['name' => 'Tech', 'count' => 42],
        ];
        $catChart = $this->processor->processCategoryData($rawData, 'Tech Categories');
        $this->assertSame(['Tech'], $catChart->labels);
        $this->assertSame([42.0], $catChart->datasets[0]->data);

        $rankData = [
            ['title' => 'Top Post', 'views' => 1000],
        ];
        $rankChart = $this->processor->processRankingData($rankData, 'Top Posts');
        $this->assertSame(['Top Post'], $rankChart->labels);
        $this->assertSame([1000.0], $rankChart->datasets[0]->data);
    }

    public function testProcessHorizontalBarChartDataSkipsInvalidItems(): void
    {
        /** @phpstan-ignore-next-line argument.type */
        $chart = $this->processor->processHorizontalBarChartData([
            ['category' => 'Valid', 'value' => 10.0],
            ['invalid' => true],
        ], 'Mixed');

        $this->assertSame(['Valid'], $chart->labels);
        $this->assertSame([10.0], $chart->datasets[0]->data);
    }

    public function testProcessCategoryDataHandlesNonArrayItems(): void
    {
        $chart = $this->processor->processCategoryData([123], 'Fallback');

        $this->assertSame(['Unknown'], $chart->labels);
        $this->assertSame([0.0], $chart->datasets[0]->data);
    }

    public function testProcessRankingDataHandlesNonArrayItems(): void
    {
        $chart = $this->processor->processRankingData(['not-an-array'], 'Ranking');

        $this->assertSame(['Unknown'], $chart->labels);
        $this->assertSame([0.0], $chart->datasets[0]->data);
    }
}
