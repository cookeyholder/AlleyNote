<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Processors;

use App\Domains\Statistics\ValueObjects\ChartData;
use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;
use InvalidArgumentException;

/**
 * 趨勢分析資料處理器.
 *
 * 提供趨勢分析所需的進階數學計算和資料處理功能
 * 包括成長率、移動平均、線性回歸、預測等功能
 */
class TrendAnalysisProcessor
{
    /**
     * 為圖表資料添加趨勢分析線.
     *
     * @param ChartData $baseChart 基礎圖表資料
     * @param string $analysisType 分析類型：'trend', 'moving_average', 'seasonal'
     * @return ChartData 包含趨勢分析的圖表資料
     */
    public function addTrendAnalysis(ChartData $baseChart, string $analysisType): ChartData
    {
        $datasets = $baseChart->datasets;

        // 取得基礎資料
        if (empty($datasets)) {
            return $baseChart;
        }

        $baseDataset = $datasets[0];
        $datasetData = $baseDataset->data;
        if (!is_array($datasetData)) {
            throw new InvalidArgumentException('Dataset data must be an array');
        }
        /** @var array<float> $dataFloat */
        $dataFloat = array_map(fn($value): float => is_numeric($value) ? (float) $value : 0.0, $datasetData);

        $additionalDatasets = match ($analysisType) {
            'trend' => [$this->calculateLinearTrend($dataFloat, $baseChart->labels)],
            'moving_average' => [$this->calculateMovingAverage($dataFloat, $baseChart->labels)],
            'seasonal' => [$this->calculateSeasonalTrend($dataFloat, $baseChart->labels)],
            'growth' => [$this->calculateGrowthRate($dataFloat, $baseChart->labels)],
            'registration' => [
                $this->calculateLinearTrend($dataFloat, $baseChart->labels),
                $this->calculateGrowthRate($dataFloat, $baseChart->labels),
            ],
            default => [$this->calculateLinearTrend($dataFloat, $baseChart->labels)],
        };

        $datasets = array_merge($datasets, $additionalDatasets);

        return new ChartData(
            $baseChart->labels,
            $datasets,
            $baseChart->options,
        );
    }

    /**
     * 計算線性趨勢線.
     *
     * @param array<float> $data
     * @param array<string> $labels
     */
    private function calculateLinearTrend(array $data, array $labels): ChartDataset
    {
        $n = count($data);
        if ($n < 2) {
            return new ChartDataset('趨勢線', $data, ChartType::Line, '#ff6384', ['rgba(255, 99, 132, 1)'], 2);
        }

        // 計算線性回歸
        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        // 計算斜率和截距
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // 生成趨勢線資料
        $trendData = [];
        for ($i = 0; $i < $n; $i++) {
            $trendData[] = $slope * ($i + 1) + $intercept;
        }

        return new ChartDataset(
            '趨勢線',
            $trendData,
            ChartType::Line,
            '#ff6384',
            '#ff6384',
            2,
        );
    }

    /**
     * 計算移動平均線.
     *
     * @param array<float> $data
     * @param array<string> $labels
     * @param int $period 移動平均週期
     */
    private function calculateMovingAverage(array $data, array $labels, int $period = 7): ChartDataset
    {
        $n = count($data);
        $movingAvg = [];

        for ($i = 0; $i < $n; $i++) {
            if ($i < $period - 1) {
                // 前面不足週期的點使用累計平均
                $sum = array_sum(array_slice($data, 0, $i + 1));
                $movingAvg[] = $sum / ($i + 1);
            } else {
                // 移動平均
                $sum = array_sum(array_slice($data, $i - $period + 1, $period));
                $movingAvg[] = $sum / $period;
            }
        }

        return new ChartDataset(
            "{$period}日移動平均",
            $movingAvg,
            ChartType::Line,
            '#36a2eb',
            '#36a2eb',
            2,
            false,
        );
    }

    /**
     * 計算季節性趨勢.
     *
     * @param array<float> $data
     * @param array<string> $labels
     */
    private function calculateSeasonalTrend(array $data, array $labels): ChartDataset
    {
        $n = count($data);
        if ($n < 12) {
            // 資料不足，返回移動平均
            return $this->calculateMovingAverage($data, $labels, min(7, $n));
        }

        // 簡化的季節性調整（假設月度週期）
        $seasonalData = [];
        $seasonalPattern = $this->calculateSeasonalPattern($data, 12);

        for ($i = 0; $i < $n; $i++) {
            $seasonalIndex = $i % 12;
            $seasonalAdjustment = $seasonalPattern[$seasonalIndex] ?? 1.0;
            $seasonalData[] = $data[$i] * $seasonalAdjustment;
        }

        return new ChartDataset(
            '季節性調整',
            $seasonalData,
            ChartType::Line,
            '#4bc0c0',
            '#4bc0c0',
            2,
            false,
        );
    }

    /**
     * 計算成長率.
     *
     * @param array<float> $data
     * @param array<string> $labels
     */
    private function calculateGrowthRate(array $data, array $labels): ChartDataset
    {
        $n = count($data);
        $growthRates = [];

        for ($i = 0; $i < $n; $i++) {
            if ($i === 0) {
                $growthRates[] = 0; // 第一個點沒有成長率
            } else {
                $prevValue = $data[$i - 1];
                if ($prevValue != 0) {
                    $growthRate = (($data[$i] - $prevValue) / $prevValue) * 100;
                    $growthRates[] = $growthRate;
                } else {
                    $growthRates[] = 0;
                }
            }
        }

        return new ChartDataset(
            '成長率 (%)',
            $growthRates,
            ChartType::Bar,
            '#ff9f40',
            '#ff9f40',
            1,
            true,
        );
    }

    /**
     * 計算季節性模式.
     *
     * @param array<float> $data
     * @return array<float>
     */
    private function calculateSeasonalPattern(array $data, int $seasonLength): array
    {
        $n = count($data);
        $pattern = array_fill(0, $seasonLength, 0.0);
        $counts = array_fill(0, $seasonLength, 0);

        // 計算每個季節位置的平均值
        for ($i = 0; $i < $n; $i++) {
            $seasonIndex = $i % $seasonLength;
            $pattern[$seasonIndex] += $data[$i];
            $counts[$seasonIndex]++;
        }

        // 計算平均值
        for ($i = 0; $i < $seasonLength; $i++) {
            if ($counts[$i] > 0) {
                $pattern[$i] = $pattern[$i] / $counts[$i];
            }
        }

        // 計算整體平均
        $overallMean = array_sum($pattern) / $seasonLength;

        // 轉換為調整係數
        for ($i = 0; $i < $seasonLength; $i++) {
            if ($overallMean != 0) {
                $pattern[$i] = $pattern[$i] / $overallMean;
            } else {
                $pattern[$i] = 1.0;
            }
        }

        return $pattern;
    }

    /**
     * 預測未來資料點.
     *
     * @param array<float> $data 歷史資料
     * @param int $periods 要預測的週期數
     * @return array<float> 預測值
     */
    public function predictFutureValues(array $data, int $periods): array
    {
        $n = count($data);
        if ($n < 3) {
            // 資料不足，返回最後一個值
            $lastValue = (float) end($data);

            return array_fill(0, $periods, $lastValue);
        }

        // 使用簡單線性回歸預測
        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $data[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // 預測未來值
        $predictions = [];
        for ($i = 1; $i <= $periods; $i++) {
            $nextX = $n + $i;
            $prediction = $slope * $nextX + $intercept;
            $predictions[] = max(0, $prediction); // 確保預測值不為負
        }

        return $predictions;
    }

    /**
     * 計算資料的統計摘要
     *
     * @param array<float> $data
     * @return array<string, float>
     */
    public function calculateStatisticalSummary(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $n = count($data);
        $sum = array_sum($data);
        $mean = $sum / $n;

        // 計算變異數和標準差
        $variance = 0;
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance = $variance / $n;
        $stdDev = sqrt($variance);

        // 排序以計算中位數
        $sortedData = $data;
        sort($sortedData);

        if ($n % 2 === 0) {
            $median = ($sortedData[$n / 2 - 1] + $sortedData[$n / 2]) / 2;
        } else {
            $median = $sortedData[(int) floor($n / 2)];
        }

        return [
            'count' => $n,
            'sum' => $sum,
            'mean' => $mean,
            'median' => $median,
            'min' => min($data),
            'max' => max($data),
            'variance' => $variance,
            'std_dev' => $stdDev,
            'range' => max($data) - min($data),
        ];
    }

    /**
     * 處理趨勢分析資料.
     */
    public function processTrendAnalysis(
        ChartData $baseChart,
        string $analysisType = 'trend',
        array $options = [],
    ): ChartData {
        return $this->addTrendAnalysis($baseChart, $analysisType);
    }
}
