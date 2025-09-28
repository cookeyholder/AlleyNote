<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Processors;

use App\Domains\Statistics\ValueObjects\CategoryDataPoint;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Domains\Statistics\ValueObjects\ChartDataset;
use App\Domains\Statistics\ValueObjects\ChartType;

/**
 * 分類統計資料處理器.
 *
 * 處理圓餅圖、甜甜圈圖、長條圖等分類統計所需的資料格式化功能
 */
class CategoryProcessor
{
    /**
     * 預設顏色配色方案.
     */
    private const DEFAULT_COLORS = [
        '#3B82F6', // 藍色
        '#EF4444', // 紅色
        '#10B981', // 綠色
        '#F59E0B', // 黃色
        '#8B5CF6', // 紫色
        '#EC4899', // 粉色
        '#14B8A6', // 青色
        '#F97316', // 橘色
        '#84CC16', // 萊姆色
        '#06B6D4', // 天藍色
        '#8B5A2B', // 棕色
        '#6B7280', // 灰色
    ];

    /**
     * 處理分類統計資料為圓餅圖.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string>|null $customColors
     * @param array<string, mixed> $options
     */
    public function processPieChartData(
        array $rawData,
        string $label = '分類統計',
        ?array $customColors = null,
        array $options = [],
    ): ChartData {
        if (empty($rawData)) {
            return new ChartData([], []);
        }

        // 排序並處理資料
        $processedData = $this->sortAndProcessData($rawData, 'desc');
        $colors = $customColors ?? array_slice(self::DEFAULT_COLORS, 0, count($processedData));

        // 建立分類資料點
        $dataPoints = [];
        foreach ($processedData as $index => $item) {
            $color = $colors[$index % count($colors)];
            $dataPoints[] = new CategoryDataPoint(
                category: $item['category'],
                value: $item['value'],
                color: $color,
            );
        }

        return ChartData::forCategory(
            $dataPoints,
            $label,
            ChartType::Pie,
            array_merge(ChartType::Pie->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理分類統計資料為甜甜圈圖.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string>|null $customColors
     * @param array<string, mixed> $options
     */
    public function processDoughnutChartData(
        array $rawData,
        string $label = '分類統計',
        ?array $customColors = null,
        array $options = [],
    ): ChartData {
        $pieChart = $this->processPieChartData($rawData, $label, $customColors, $options);

        // 轉換為甜甜圈圖
        $datasets = [];
        foreach ($pieChart->datasets as $dataset) {
            $datasets[] = $dataset->withType(ChartType::Doughnut);
        }

        return ChartData::forMultiDataset(
            $pieChart->labels,
            $datasets,
            array_merge(ChartType::Doughnut->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理分類統計資料為長條圖.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string>|null $customColors
     * @param array<string, mixed> $options
     */
    public function processBarChartData(
        array $rawData,
        string $label = '分類統計',
        ?array $customColors = null,
        array $options = [],
    ): ChartData {
        if (empty($rawData)) {
            return new ChartData([], []);
        }

        // 排序並處理資料
        $processedData = $this->sortAndProcessData($rawData, 'desc');
        $colors = $customColors ?? array_slice(self::DEFAULT_COLORS, 0, count($processedData));

        // 建立分類資料點
        $dataPoints = [];
        foreach ($processedData as $index => $item) {
            $color = $colors[$index % count($colors)];
            $dataPoints[] = new CategoryDataPoint(
                category: $item['category'],
                value: $item['value'],
                color: $color,
            );
        }

        return ChartData::forCategory(
            $dataPoints,
            $label,
            ChartType::Bar,
            array_merge(ChartType::Bar->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理百分比分布圖.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string>|null $customColors
     * @param array<string, mixed> $options
     */
    public function processPercentageDistributionData(
        array $rawData,
        string $label = '百分比分布',
        ChartType $chartType = ChartType::Pie,
        ?array $customColors = null,
        array $options = [],
    ): ChartData {
        if (empty($rawData)) {
            return new ChartData([], []);
        }

        // 計算總和
        $total = array_sum(array_column($rawData, 'value'));

        if ($total <= 0) {
            return new ChartData([], []);
        }

        // 轉換為百分比並排序
        $percentageData = [];
        foreach ($rawData as $item) {
            $percentage = ($item['value'] / $total) * 100;
            $percentageData[] = [
                'category' => $item['category'],
                'value' => $percentage,
            ];
        }

        $processedData = $this->sortAndProcessData($percentageData, 'desc');
        $colors = $customColors ?? array_slice(self::DEFAULT_COLORS, 0, count($processedData));

        // 建立分類資料點
        $dataPoints = [];
        foreach ($processedData as $index => $item) {
            $color = $colors[$index % count($colors)];
            $dataPoints[] = CategoryDataPoint::withPercentage(
                category: $item['category'],
                value: $rawData[$index]['value'], // 使用原始值計算百分比
                total: $total,
                color: $color,
            );
        }

        return ChartData::forCategory(
            $dataPoints,
            $label,
            $chartType,
            array_merge($chartType->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理排名前 N 的資料.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string>|null $customColors
     * @param array<string, mixed> $options
     */
    public function processTopNData(
        array $rawData,
        int $topN = 10,
        string $label = '排行榜',
        ChartType $chartType = ChartType::Bar,
        ?array $customColors = null,
        bool $includeOthers = true,
        array $options = [],
    ): ChartData {
        if (empty($rawData) || $topN <= 0) {
            return new ChartData([], []);
        }

        // 排序資料
        $sortedData = $this->sortAndProcessData($rawData, 'desc');

        // 取前 N 名
        $topData = array_slice($sortedData, 0, $topN);
        $remainingData = array_slice($sortedData, $topN);

        // 如果需要包含「其他」項目且有剩餘資料
        if ($includeOthers && !empty($remainingData)) {
            $othersValue = array_sum(array_column($remainingData, 'value'));
            if ($othersValue > 0) {
                $topData[] = [
                    'category' => '其他',
                    'value' => $othersValue,
                ];
            }
        }

        $colors = $customColors ?? array_slice(self::DEFAULT_COLORS, 0, count($topData));

        // 建立分類資料點
        $dataPoints = [];
        foreach ($topData as $index => $item) {
            $color = $colors[$index % count($colors)];
            $dataPoints[] = new CategoryDataPoint(
                category: $item['category'],
                value: $item['value'],
                color: $color,
            );
        }

        return ChartData::forCategory(
            $dataPoints,
            $label,
            $chartType,
            array_merge($chartType->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理比較分析資料（多個分類系列）.
     *
     * @param array<string, array<array{category: string, value: float}>> $multiSeriesData
     * @param array<string, mixed> $options
     */
    public function processComparisonData(
        array $multiSeriesData,
        string $label = '比較分析',
        array $options = [],
    ): ChartData {
        if (empty($multiSeriesData)) {
            return new ChartData([], []);
        }

        // 收集所有分類
        $allCategories = [];
        foreach ($multiSeriesData as $seriesData) {
            foreach ($seriesData as $item) {
                if (!in_array($item['category'], $allCategories)) {
                    $allCategories[] = $item['category'];
                }
            }
        }

        // 為每個系列建立資料集
        $datasets = [];
        $colors = self::DEFAULT_COLORS;
        $colorIndex = 0;

        foreach ($multiSeriesData as $seriesLabel => $seriesData) {
            // 建立該系列的完整資料（包含所有分類）
            $seriesValues = [];
            foreach ($allCategories as $category) {
                $value = 0.0;
                foreach ($seriesData as $item) {
                    if ($item['category'] === $category) {
                        $value = $item['value'];
                        break;
                    }
                }
                $seriesValues[] = $value;
            }

            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = ChartDataset::forBarChart(
                $seriesLabel,
                $seriesValues,
                [$color],
            );
            $colorIndex++;
        }

        return ChartData::forMultiDataset(
            $allCategories,
            $datasets,
            array_merge(ChartType::Bar->getDefaultOptions(), $options),
        );
    }

    /**
     * 處理分組堆疊長條圖資料.
     *
     * @param array<string, array<array{category: string, value: float}>> $stackedData
     * @param array<string, mixed> $options
     */
    public function processStackedBarData(
        array $stackedData,
        string $label = '堆疊分析',
        array $options = [],
    ): ChartData {
        $comparisonChart = $this->processComparisonData($stackedData, $label, $options);

        // 加入堆疊選項
        $existingScales = $comparisonChart->options['scales'] ?? [];
        $stackedOptions = array_merge($comparisonChart->options, [
            'scales' => array_merge(
                is_array($existingScales) ? $existingScales : [],
                [
                    'x' => ['stacked' => true],
                    'y' => ['stacked' => true],
                ],
            ),
        ]);

        return $comparisonChart->withOptions($stackedOptions);
    }

    /**
     * 排序並處理資料.
     *
     * @param array<array{category: string, value: float}> $data
     * @return array<array{category: string, value: float}>
     */
    private function sortAndProcessData(array $data, string $order = 'desc'): array
    {
        // 移除空值和無效資料 - 由於參數已經是強型別，只需檢查數值是否為負
        $filteredData = array_filter($data, function ($item) {
            return $item['value'] >= 0;
        });

        // 排序
        usort($filteredData, function ($a, $b) use ($order) {
            if ($order === 'asc') {
                return $a['value'] <=> $b['value'];
            }

            return $b['value'] <=> $a['value'];
        });

        return $filteredData;
    }

    /**
     * 取得顏色配色方案.
     *
     * @param string $scheme 配色方案名稱
     * @return array<string>
     */
    public function getColorScheme(string $scheme = 'default'): array
    {
        return match ($scheme) {
            'pastel' => [
                '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9',
                '#BAE1FF', '#E1BAFF', '#FFBAE1', '#C9BAFF',
            ],
            'vibrant' => [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4',
                '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F',
            ],
            'monochrome' => [
                '#2C3E50', '#34495E', '#5D6D7E', '#85929E',
                '#AEB6BF', '#D5DBDB', '#E8DAEF', '#F4F6F6',
            ],
            'business' => [
                '#1f4e79', '#2f5f8f', '#4472c4', '#5b86db',
                '#7199e2', '#8fb3ea', '#a6c8f1', '#bdd7f7',
            ],
            default => self::DEFAULT_COLORS,
        };
    }

    /**
     * 處理水平長條圖資料.
     *
     * @param array<array{category: string, value: float}> $rawData
     */
    public function processHorizontalBarChartData(
        array $rawData,
        string $label,
        string $sortBy = 'value',
    ): ChartData {
        $labels = [];
        $values = [];

        // 依據排序欄位排序
        if ($sortBy === 'category') {
            usort($rawData, fn($a, $b) => strcmp($a['category'], $b['category']));
        } else {
            usort($rawData, fn($a, $b) => $b['value'] <=> $a['value']);
        }

        foreach ($rawData as $item) {
            if (!is_array($item) || !isset($item['category'], $item['value'])) {
                continue;
            }
            $labels[] = $item['category'];
            $values[] = $item['value'];
        }

        $dataset = new ChartDataset(
            $label,
            $values,
            ChartType::Bar,
            $this->getColorScheme('business'),
            $this->getColorScheme('business'),
            1,
            false,
        );

        return new ChartData(
            $labels,
            [$dataset],
            [
                'indexAxis' => 'y', // 水平長條圖
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        );
    }

    /**
     * 處理自訂圖表資料.
     *
     * @param array<array{category: string, value: float}> $rawData
     * @param array<string, mixed> $chartOptions
     */
    public function processCustomChartData(
        array $rawData,
        string $metricName,
        array $chartOptions = [],
    ): ChartData {
        $chartType = $chartOptions['type'] ?? 'bar';

        return match ($chartType) {
            'pie', 'doughnut' => $this->processPieChartData($rawData, $metricName),
            'bar' => $this->processBarChartData($rawData, $metricName),
            'horizontal-bar' => $this->processHorizontalBarChartData($rawData, $metricName),
            default => $this->processBarChartData($rawData, $metricName),
        };
    }

    /**
     * 處理分類資料.
     */
    public function processCategoryData(
        array $rawData,
        string $categoryType,
    ): ChartData {
        // 確保資料格式正確
        $formattedData = array_map(function ($item) {
            if (is_array($item)) {
                $categoryValue = $item['category'] ?? $item['name'] ?? 'Unknown';
                $value = $item['value'] ?? $item['count'] ?? 0;

                return [
                    'category' => is_string($categoryValue) ? $categoryValue : 'Unknown',
                    'value' => is_numeric($value) ? (float) $value : 0.0,
                ];
            }

            return ['category' => 'Unknown', 'value' => 0.0];
        }, $rawData);

        return $this->processPieChartData($formattedData, $categoryType);
    }

    /**
     * 處理排名資料.
     */
    public function processRankingData(
        array $rawData,
        string $title,
    ): ChartData {
        // 確保資料格式正確
        $formattedData = array_map(function ($item) {
            if (is_array($item)) {
                $titleValue = $item['title'] ?? $item['name'] ?? $item['category'] ?? 'Unknown';
                $value = $item['views'] ?? $item['value'] ?? $item['count'] ?? 0;

                return [
                    'category' => is_string($titleValue) ? $titleValue : 'Unknown',
                    'value' => is_numeric($value) ? (float) $value : 0.0,
                ];
            }

            return ['category' => 'Unknown', 'value' => 0.0];
        }, $rawData);

        return $this->processBarChartData($formattedData, $title);
    }
}
