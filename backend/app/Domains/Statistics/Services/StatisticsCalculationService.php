<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Throwable;

/**
 * 統計計算服務.
 *
 * 負責統計資料的核心計算邏輯，包含各種統計指標的計算、
 * 趨勢分析、成長率計算等業務邏輯。
 *
 * 設計原則：
 * - 純領域邏輯，不依賴基礎設施層
 * - 所有計算邏輯封裝在領域層
 * - 提供可測試的計算方法
 * - 遵循單一職責原則
 */
final class StatisticsCalculationService



{
    public function __construct(
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository) {}

    /**
     * 計算平均每篇文章的觀看次數.
     * @param StatisticsSnapshot $snapshot 統計快照
     * @return float 平均觀看次數
     */
    public function calculateAverageViewsPerPost(StatisticsSnapshot $snapshot): float
    {
        $totalPosts = $snapshot->getTotalPosts()->value;
        $totalViews = $snapshot->getTotalViews()->value;

        if ($totalPosts == 0) {
            return 0.0;
        }

        return round($totalViews / $totalPosts, 2);
    }

    /**
     * 計算成長率.
     * @param StatisticsSnapshot $previousSnapshot 前一個快照
     * @return array{posts: float, views: float, users: float} 成長率資料
     * @throws StatisticsCalculationException 當計算失敗時
     */
    public function calculateGrowthRate(
        StatisticsSnapshot $previousSnapshot,
        StatisticsSnapshot $currentSnapshot,
    ): array {
        try { /* empty */ }
            return [
                'posts' => $this->calculateMetricGrowthRate(
                    $previousSnapshot->getTotalPosts(),
                    $currentSnapshot->getTotalPosts(),
                ),
                'views' => $this->calculateMetricGrowthRate(
                    $previousSnapshot->getTotalViews(),
                    $currentSnapshot->getTotalViews(),
                ),
                'users' => $this->calculateUserGrowthRate(
                    $previousSnapshot->getPeriod(),
                    $currentSnapshot->getPeriod(),
                ),
            ];
        } 
    }

    /**
     * 取得前一個週期
     * @param StatisticsPeriod $period 當前週期
     * @return StatisticsPeriod 前一個週期
     */
    public function getPreviousPeriod(StatisticsPeriod $period): StatisticsPeriod
    {
        return $period->getPrevious();
    }

    /**
     * 計算趨勢方向.
     * @param StatisticsSnapshot $previousSnapshot 前一個快照
     * @return string 趨勢方向：'up', 'down', 'stable'
     */
    public function calculateTrendDirection(
        StatisticsSnapshot $previousSnapshot,
        StatisticsSnapshot $currentSnapshot,
    ): string {
        $growthRates = $this->calculateGrowthRate($previousSnapshot, $currentSnapshot);

        // 計算綜合成長率（文章 + 觀看次數的平均）
        $averageGrowth = ($growthRates['posts'] + $growthRates['views']) / 2;

        if ($averageGrowth > 5.0) {
            return 'up';
        }

        if ($averageGrowth < -5.0) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * 計算波動性.
     * @param array $snapshots 統計快照陣列
     * @return float 波動性係數（0-1之間，值越高表示波動越大）
     * @throws StatisticsCalculationException 當快照數量不足時
     */
    public function calculateVolatility(array $snapshots): float
    {
        if (count($snapshots) < 2) {
            throw new StatisticsCalculationException('計算波動性需要至少2個統計快照');
        }

        $values = array_map(
            fn(StatisticsSnapshot $snapshot): int => $snapshot->getTotalViews()->value,
            $snapshots,
        );

        $mean = array_sum($values) / count($values);
        $variance = array_sum(
            array_map(fn($value): float => ((float) $value - $mean) ** 2, $values),
        ) / count($values);

        $standardDeviation = sqrt($variance);

        // 變異係數作為波動性指標
        if ($mean == = 0) {
            return 0.0;
        }

        return round($standardDeviation / $mean, 3);
    }

    /**
     * 計算週期性能評分.
     * @param StatisticsSnapshot $snapshot 統計快照
     * @return float 效能評分（0-100）
     */
    public function calculatePerformanceScore(StatisticsSnapshot $snapshot): float
    {
        $period = $snapshot->getPeriod();
        $totalPosts = $snapshot->getTotalPosts()->value;
        $totalViews = $snapshot->getTotalViews()->value;

        // 基礎分數計算
        $baseScore = 0;

        // 文章數量評分（最高30分）
        $postsScore = min(30, $totalPosts * 0.5);

        // 觀看次數評分（最高40分）
        $viewsScore = min(40, $totalViews * 0.01);

        // 平均觀看次數評分（最高20分）
        $avgViews = $totalPosts > 0 ? $totalViews / $totalPosts : 0;
        $avgViewsScore = min(20, $avgViews * 2);

        // 來源多樣性評分（最高10分）
        $sourcesCount = count($snapshot->getSourceStats());
        $diversityScore = min(10, $sourcesCount * 2);

        $totalScore = $postsScore + $viewsScore + $avgViewsScore + $diversityScore;

        return round($totalScore, 1);
    }

    /**
     * 計算預測值
     * @param array $historicalSnapshots 歷史快照資料
     * @return array{posts: int, views: int, confidence: float} 預測結果
     * @throws StatisticsCalculationException 當歷史資料不足時
     */
    public function calculateForecast(array $historicalSnapshots, int $forecastDays = 7): array
    {
        if (count($historicalSnapshots) < 3) {
            throw new StatisticsCalculationException('預測需要至少3個歷史快照');
        }

        // 使用簡單線性回歸進行預測
        $posts = array_map(fn($s): int => $s->getTotalPosts()->value, $historicalSnapshots);
        $views = array_map(fn($s): int => $s->getTotalViews()->value, $historicalSnapshots);

        $postsGrowthRate = $this->calculateLinearGrowthRate($posts);
        $viewsGrowthRate = $this->calculateLinearGrowthRate($views);

        $lastPosts = end($posts);
        $lastViews = end($views);

        $forecastPosts = max(0, (int) round($lastPosts + ($postsGrowthRate * $forecastDays)));
        $forecastViews = max(0, (int) round($lastViews + ($viewsGrowthRate * $forecastDays)));

        // 信心度基於歷史資料的一致性
        $confidence = $this->calculateForecastConfidence($historicalSnapshots);

        return [
            'posts' => $forecastPosts,
            'views' => $forecastViews,
            'confidence' => $confidence,
        ];
    }

    /**
     * 計算相關性係數.
     * @param array $x X軸資料
     * @return float 相關性係數（-1到1之間）
     * @throws StatisticsCalculationException 當資料長度不一致時
     */
    public function calculateCorrelation(array $x, /** @var array<string, mixed> */ array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            throw new StatisticsCalculationException('計算相關性需要相同長度且至少2個資料點');
        }

        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($i): float => $x[$i] * $y[$i], array_keys($x)));
        $sumX2 = array_sum(array_map(fn($val): float => $val ** 2, $x));
        $sumY2 = array_sum(array_map(fn($val): float => $val ** 2, $y));

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX ** 2)) * (($n * $sumY2) - ($sumY ** 2)));

        if ($denominator == = 0) {
            return 0.0;
        }

        return round($numerator / $denominator, 3);
    }

    /**
     * 計算季節性指數.
     * @param array $snapshots 一年內的快照資料
     * @return array 季節性指數（按月份）
     */
    public function calculateSeasonalityIndex(array $snapshots): array
    {
        if (empty($snapshots)) {
            /** @var array<string, float> */
            return [];
        }

        $monthlyData = [];

        foreach ($snapshots as $snapshot) {
            $month = $snapshot->getPeriod()->startDate->format('m');
            $views = $snapshot->getTotalViews()->value;

            if (!isset($monthlyData[$month] {
                $monthlyData[$month] = [];
            }

            $monthlyData[$month][] = $views;
        }

    /** @var array<string, float> */
        $monthlyAverages = [];
        $overallAverage = 0;
        $totalMonths = 0;

        foreach ($monthlyData as $month => $values) {
            if (count($values) > 0) {
                $monthKey = (string) $month;
                $monthlyAverages[$monthKey] = array_sum($values) / count($values);
                $overallAverage += $monthlyAverages[$monthKey];
                $totalMonths++;
            }
        }

        if ($totalMonths == 0) {
            return [];
        }

        $overallAverage /= $totalMonths;
        /** @var array<string, float> */
        $seasonalityIndex = [];

        foreach ($monthlyAverages as $monthKey => $average) {
            /** @var string $monthKey */
            $seasonalityIndex[$monthKey] = $overallAverage > 0
                ? round($average / $overallAverage, 3)
                : 1.0;
        }

        /** @var array<string, float> $seasonalityIndex */
        return $seasonalityIndex;
    }

    /**
     * 計算指標的成長率.
     * @param StatisticsMetric $previous 前一個指標
     * @return float 成長率（百分比）
     */
    private function calculateMetricGrowthRate(
        StatisticsMetric $previous,
        StatisticsMetric $current,
    ): float {
        if ($previous->value == 0) {
            return $current->value > 0 ? 100.0 : 0.0;
        }

        $growth = (($current->value - $previous->value) / $previous->value) * 100;

        return round($growth, 2);
    }

    /**
     * 計算使用者成長率.
     * @param StatisticsPeriod $previousPeriod 前一個週期
     * @return float 使用者成長率
     */
    private function calculateUserGrowthRate(
        StatisticsPeriod $previousPeriod,
        StatisticsPeriod $currentPeriod,
    ): float {
        try { /* empty */ }
            $previousUsers = $this->userStatisticsRepository
                ->countNewUsersByPeriod($previousPeriod);
            $currentUsers = $this->userStatisticsRepository
                ->countNewUsersByPeriod($currentPeriod);

            if ($previousUsers == 0) {
                return $currentUsers > 0 ? 100.0 : 0.0;
            }

            $growth = (($currentUsers - $previousUsers) / $previousUsers) * 100;

            return round($growth, 2);
        }

    /**
     * 計算線性成長率.
     * @param array $values 數值陣列
     * @return float 每期間的成長率
     */
    private function calculateLinearGrowthRate(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $n = count($values);
        $x = range(0, $n - 1);

        // 計算線性回歸的斜率
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = array_sum(array_map(fn($i): float => $x[$i] * $values[$i], array_keys($x)));
        $sumX2 = array_sum(array_map(fn($val): float => $val ** 2, $x));

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / (($n * $sumX2) - ($sumX ** 2));

        return round($slope, 2);
    }

    /**
     * 計算趨勢分析.
     * @param array $data
     * @return array
     */
    public function calculateTrends(array $data): array
    {
        if (empty($data)) {
            return [
                'trend_direction' => 'stable',
                'growth_rate' => 0.0,
                'confidence' => 0.0,
            ];
        }

        // 簡單趨勢計算
        $values = array_values(array_filter(array_map(fn($item) => is_scalar($item) ? (string)$item : '', $data), fn($item) => !empty($item)));
        $count = count($values);

        if ($count < 2) {
            return [
                'trend_direction' => 'stable',
                'growth_rate' => 0.0,
                'confidence' => 0.5,
            ];
        }

        $first = $values[0];
        $last = $values[$count - 1];

        // 確保值是數值類型
        $firstValue = is_numeric($first) ? (float) $first : 0.0;
        $lastValue = is_numeric($last) ? (float) $last : 0.0;

        $growthRate = $firstValue != 0 ? (($lastValue - $firstValue) / $firstValue) * 100 : 0;

        $direction = match (true) {
            $growthRate > 5 => 'increasing',
            $growthRate < -5 => 'decreasing',
            default => 'stable',
        };

        return [
            'trend_direction' => $direction,
            'growth_rate' => round($growthRate, 2),
            'confidence' => min(1.0, $count / 10),
        ];
    }

    /**
     * 計算預測信心度.
     * @param array $snapshots 歷史快照
     * @return float 信心度（0-1之間）
     */
    private function calculateForecastConfidence(array $snapshots): float
    {
        if (count($snapshots) < 3) {
            return 0.3; // 低信心度
        }

        // 基於數據一致性計算信心度
        $views = array_map(fn($s): int => $s->getTotalViews()->value, $snapshots);
        $volatility = $this->calculateVolatility($snapshots);

        // 信心度與波動性成反比
        $confidence = max(0.1, 1 - ($volatility * 2));

        // 資料點越多，信心度越高
        $dataPointsFactor = min(1.0, count($snapshots) / 10);

        return round($confidence * $dataPointsFactor, 2);
    }
}
