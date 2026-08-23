<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Processors;

use App\Domains\Statistics\ValueObjects\ChartData;
use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;
use App\Infrastructure\Statistics\Processors\TrendAnalysisProcessor;
use Tests\Support\UnitTestCase;

/**
 * 趨勢分析處理器測試.
 */
final class TrendAnalysisProcessorTest extends UnitTestCase
{
    private TrendAnalysisProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new TrendAnalysisProcessor();
    }

    public function testAddTrendAnalysisWithEmptyDatasets(): void
    {
        $baseChart = new ChartData([], []);
        $result = $this->processor->addTrendAnalysis($baseChart, 'trend');
        $this->assertSame($baseChart, $result);
    }

    public function testAddTrendAnalysisLinearTrend(): void
    {
        $labels = ['D1', 'D2', 'D3', 'D4', 'D5'];
        $dataset = new ChartDataset('Original', [10.0, 20.0, 30.0, 40.0, 50.0]);
        $baseChart = new ChartData($labels, [$dataset]);

        $result = $this->processor->addTrendAnalysis($baseChart, 'trend');

        $this->assertCount(2, $result->datasets);
        $this->assertSame('趨勢線', $result->datasets[1]->label);
        $this->assertSame(ChartType::Line, $result->datasets[1]->type);
        $this->assertEquals([10.0, 20.0, 30.0, 40.0, 50.0], $result->datasets[1]->data);
    }

    public function testAddTrendAnalysisWithSingleDataPoint(): void
    {
        $dataset = new ChartDataset('Original', [10.0]);
        $baseChart = new ChartData(['D1'], [$dataset]);

        $result = $this->processor->addTrendAnalysis($baseChart, 'trend');
        $this->assertCount(2, $result->datasets);
        $this->assertSame([10.0], $result->datasets[1]->data);
    }

    public function testAddTrendAnalysisMovingAverage(): void
    {
        $data = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
        $labels = array_map(fn($i) => "D{$i}", range(1, 10));
        $dataset = new ChartDataset('Original', $data);
        $baseChart = new ChartData($labels, [$dataset]);

        $result = $this->processor->addTrendAnalysis($baseChart, 'moving_average');

        $this->assertCount(2, $result->datasets);
        $this->assertSame('7日移動平均', $result->datasets[1]->label);
        $this->assertCount(10, $result->datasets[1]->data);
    }

    public function testAddTrendAnalysisSeasonal(): void
    {
        // 資料 < 12 筆會回退至移動平均
        $dataSmall = [10, 20, 30, 40, 50];
        $labelsSmall = ['D1', 'D2', 'D3', 'D4', 'D5'];
        $baseChartSmall = new ChartData($labelsSmall, [new ChartDataset('Original', $dataSmall)]);
        $resultSmall = $this->processor->addTrendAnalysis($baseChartSmall, 'seasonal');
        $this->assertCount(2, $resultSmall->datasets);

        // 資料 >= 12 筆
        $dataLarge = array_fill(0, 24, 100.0);
        $labelsLarge = array_map(fn($i) => "M{$i}", range(1, 24));
        $baseChartLarge = new ChartData($labelsLarge, [new ChartDataset('Original', $dataLarge)]);
        $resultLarge = $this->processor->addTrendAnalysis($baseChartLarge, 'seasonal');
        $this->assertSame('季節性調整', $resultLarge->datasets[1]->label);
        $this->assertCount(24, $resultLarge->datasets[1]->data);
    }

    public function testAddTrendAnalysisGrowthAndRegistration(): void
    {
        $data = [0.0, 50.0, 100.0, 100.0];
        $labels = ['D1', 'D2', 'D3', 'D4'];
        $baseChart = new ChartData($labels, [new ChartDataset('Original', $data)]);

        $growthResult = $this->processor->addTrendAnalysis($baseChart, 'growth');
        $this->assertCount(2, $growthResult->datasets);
        $this->assertSame('成長率 (%)', $growthResult->datasets[1]->label);
        $this->assertSame([0.0, 0.0, 100.0, 0.0], $growthResult->datasets[1]->data);

        $regResult = $this->processor->addTrendAnalysis($baseChart, 'registration');
        $this->assertCount(3, $regResult->datasets); // Original + Linear Trend + Growth
    }

    public function testPredictFutureValues(): void
    {
        // 資料筆數 < 3
        $predSmall = $this->processor->predictFutureValues([10.0, 20.0], 3);
        $this->assertSame([20.0, 20.0, 20.0], $predSmall);

        // 資料筆數 >= 3
        $pred = $this->processor->predictFutureValues([10.0, 20.0, 30.0], 2);
        $this->assertCount(2, $pred);
        $this->assertEquals(40.0, $pred[0]);
        $this->assertEquals(50.0, $pred[1]);
    }

    public function testCalculateStatisticalSummary(): void
    {
        // 空陣列
        $this->assertSame([], $this->processor->calculateStatisticalSummary([]));

        // 奇數筆資料
        $oddSummary = $this->processor->calculateStatisticalSummary([10.0, 20.0, 30.0]);
        $this->assertSame(3, $oddSummary['count']);
        $this->assertSame(60.0, $oddSummary['sum']);
        $this->assertSame(20.0, $oddSummary['mean']);
        $this->assertSame(20.0, $oddSummary['median']);
        $this->assertSame(10.0, $oddSummary['min']);
        $this->assertSame(30.0, $oddSummary['max']);
        $this->assertSame(20.0, $oddSummary['range']);

        // 偶數筆資料
        $evenSummary = $this->processor->calculateStatisticalSummary([10.0, 20.0, 30.0, 40.0]);
        $this->assertSame(25.0, $evenSummary['median']);
    }

    public function testProcessTrendAnalysis(): void
    {
        $baseChart = new ChartData(['D1', 'D2'], [new ChartDataset('Views', [10, 20])]);
        $result = $this->processor->processTrendAnalysis($baseChart, 'trend');
        $this->assertCount(2, $result->datasets);
    }

    public function testAddTrendAnalysisWithUnknownTypeFallsBackToLinearTrend(): void
    {
        $dataset = new ChartDataset('Original', [10.0, 20.0, 30.0]);
        $baseChart = new ChartData(['D1', 'D2', 'D3'], [$dataset]);

        $result = $this->processor->addTrendAnalysis($baseChart, 'unknown-type');

        // 未知分析類型退回線性趨性趨勢，僅附加一條資料集
        $this->assertCount(2, $result->datasets);
    }

    public function testAddTrendAnalysisSeasonalWithAllZeroData(): void
    {
        // 全為零的資料會讓整體平均為 0，季節調整係數應設為 1.0（資料 × 1.0 仍為 0）
        $dataset = new ChartDataset('Zeros', array_fill(0, 12, 0.0));
        $baseChart = new ChartData(
            ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7', 'D8', 'D9', 'D10', 'D11', 'D12'],
            [$dataset],
        );

        $result = $this->processor->addTrendAnalysis($baseChart, 'seasonal');

        $this->assertCount(2, $result->datasets);
        $this->assertSame('季節性調整', $result->datasets[1]->label);
        foreach ($result->datasets[1]->data as $value) {
            $this->assertSame(0.0, $value);
        }
    }
}
