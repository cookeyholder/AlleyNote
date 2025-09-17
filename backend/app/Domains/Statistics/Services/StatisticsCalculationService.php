<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Exception;
use Psr\Log\LoggerInterface;

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
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * 計算平均每篇文章的觀看次數.
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
     *
     * @return array{posts: float, views: float, users: float} 成長率資料
     * @throws StatisticsCalculationException 當計算失敗時
     */
    public function calculateGrowthRate(
        StatisticsSnapshot $previousSnapshot,
        StatisticsSnapshot $currentSnapshot,
    ): array {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error('計算成長率失敗', [
                'error' => $e->getMessage(),
                'previous_period' => $previousSnapshot->getPeriod()->type->value,
                'current_period' => $currentSnapshot->getPeriod()->type->value,
            ]);

            throw $e;
        }
    }

    /**
     * 取得前一個週期
     */
    public function getPreviousPeriod(StatisticsPeriod $period): StatisticsPeriod
    {
        return $period->getPrevious();
    }

    /**
     * 計算趨勢方向.
     *
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
     *
     * @param array<StatisticsSnapshot> $snapshots 統計快照陣列
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
        if ($mean == 0) {
            return 0.0;
        }

        return round($standardDeviation / $mean, 3);
    }

    /**
     * 計算週期性能評分.
     *
     * @return float 效能評分（0-100）
     */
    public function calculatePerformanceScore(StatisticsSnapshot $snapshot): float
    {
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
     *
     * @param array<StatisticsSnapshot> $historicalSnapshots 歷史快照資料
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
     *
     * @param array<float|int> $x X軸資料
     * @param array<float|int> $y Y軸資料
     * @return float 相關性係數（-1到1之間）
     * @throws StatisticsCalculationException 當資料長度不一致時
     */
    public function calculateCorrelation(array $x, array $y): float
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

        if ($denominator == 0) {
            return 0.0;
        }

        return round($numerator / $denominator, 3);
    }

    /**
     * 計算季節性指數.
     *
     * @param array<StatisticsSnapshot> $snapshots 一年內的快照資料
     * @return array<string, float> 季節性指數（按月份）
     */
    public function calculateSeasonalityIndex(array $snapshots): array
    {
        if (empty($snapshots)) {
            return [];
        }

        $monthlyData = [];

        foreach ($snapshots as $snapshot) {
            $month = $snapshot->getPeriod()->startDate->format('m');
            $views = $snapshot->getTotalViews()->value;

            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }

            $monthlyData[$month][] = $views;
        }

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
        $seasonalityIndex = [];

        foreach ($monthlyAverages as $monthKey => $average) {
            $seasonalityIndex[$monthKey] = $overallAverage > 0
                ? round($average / $overallAverage, 3)
                : 1.0;
        }

        return $seasonalityIndex;
    }

    /**
     * 計算趨勢分析.
     * @param array<mixed> $data 資料陣列
     * @return array{trend_direction: string, growth_rate: float, confidence: float}
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
        $values = array_values(array_filter(
            array_map(fn($item) => is_scalar($item) ? (string) $item : '', $data),
            fn($item) => !empty($item),
        ));
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

        $growthRate = $firstValue !== 0.0
            ? (($lastValue - $firstValue) / $firstValue) * 100
            : 0.0;

        $trendDirection = 'stable';
        if ($growthRate > 5.0) {
            $trendDirection = 'up';
        } elseif ($growthRate < -5.0) {
            $trendDirection = 'down';
        }

        return [
            'trend_direction' => $trendDirection,
            'growth_rate' => round($growthRate, 2),
            'confidence' => min(1.0, $count / 10.0), // 簡單信心度計算
        ];
    }

    /**
     * 計算指標的成長率.
     */
    private function calculateMetricGrowthRate(
        StatisticsMetric $previous,
        StatisticsMetric $current,
    ): float {
        if ($previous->value === 0) {
            return $current->value > 0 ? 100.0 : 0.0;
        }

        $growth = (($current->value - $previous->value) / $previous->value) * 100;

        return round($growth, 2);
    }

    /**
     * 計算使用者成長率.
     */
    private function calculateUserGrowthRate(
        StatisticsPeriod $previousPeriod,
        StatisticsPeriod $currentPeriod,
    ): float {
        try {
            $previousUsers = $this->userStatisticsRepository
                ->countNewUsersByPeriod($previousPeriod);
            $currentUsers = $this->userStatisticsRepository
                ->countNewUsersByPeriod($currentPeriod);

            if ($previousUsers == 0) {
                return $currentUsers > 0 ? 100.0 : 0.0;
            }

            $growth = (($currentUsers - $previousUsers) / $previousUsers) * 100;

            return round($growth, 2);
        } catch (\Exception $e) {
            $this->logger->error('計算使用者成長率失敗', [
                'error' => $e->getMessage(),
                'previous_period' => $previousPeriod->type->value,
                'current_period' => $currentPeriod->type->value,
            ]);

            throw $e;
        }
    }

    /**
     * 計算線性成長率.
     *
     * @param array<float|int> $values 數值陣列
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

        $denominator = ($n * $sumX2) - ($sumX ** 2);
        if ($denominator == 0) {
            return 0.0;
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;

        return round($slope, 2);
    }

    /**
     * 計算預測信心度.
     * @param array<StatisticsSnapshot> $snapshots
     */
    private function calculateForecastConfidence(array $snapshots): float
    {
        if (count($snapshots) < 3) {
            return 0.0;
        }

        // 基於資料一致性的簡單信心度計算
        $values = array_map(fn($s) => $s->getTotalViews()->value, $snapshots);
        $volatility = $this->calculateSimpleVolatility($values);

        // 波動性越低，信心度越高
        $confidence = max(0.1, 1.0 - $volatility);

        return round($confidence, 2);
    }

    /**
     * 計算簡單波動性.
     * @param array<float|int> $values
     */
    private function calculateSimpleVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        if ($mean == 0) {
            return 0.0;
        }

        $variance = array_sum(
            array_map(fn($v) => (($v - $mean) ** 2), $values),
        ) / count($values);

        return sqrt($variance) / $mean;
    }
}
