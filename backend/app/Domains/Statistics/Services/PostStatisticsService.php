<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Throwable;

/**
 * 文章統計服務.
 *
 * 負責文章相關的統計分析業務邏輯，包含：
 * - 文章熱門度分析
 * - 來源分析
 * - 文章表現評估
 * - 內容品質評分
 *
 * 設計原則：
 * - 專注於文章統計的業務邏輯
 * - 不依賴基礎設施層
 * - 提供可測試的分析方法
 * - 遵循單一職責原則
 */
final class PostStatisticsService
{
    public function __construct(
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
    ) {}

    /**
     * 分析熱門文章.
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed>
     * @throws StatisticsCalculationException 當分析失敗時
     */
    public function analyzePopularPosts(StatisticsPeriod $period, int $limit = 10): array
    {
        try {
            $popularPosts = $this->postStatisticsRepository
                ->getPopularPostsByPeriod($period, $limit);

            if (empty($popularPosts)) {
                return [
                    'posts' => [],
                    'summary' => [
                        'total_posts' => 0,
                        'total_views' => 0,
                        'average_views' => 0.0,
                        'top_source' => null,
                    ],
                ];
            }

            $totalViews = array_sum(array_column($popularPosts, 'views'));
            $averageViews = round($totalViews / count($popularPosts), 2);

            // 分析主要來源
            $sources = array_column($popularPosts, 'source');
            $sourceCounts = array_count_values($sources);
            arsort($sourceCounts);
            $topSource = array_key_first($sourceCounts);

            return [
                'posts' => $popularPosts,
                'summary' => [
                    'total_posts' => count($popularPosts),
                    'total_views' => $totalViews,
                    'average_views' => $averageViews,
                    'top_source' => $topSource,
                ],
            ];
        } catch (Throwable $e) {
            throw new StatisticsCalculationException(
                "分析熱門文章失敗: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * 分析文章來源分佈.
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed>
     */
    public function analyzeSourceDistribution(StatisticsPeriod $period): array
    {
        $sourceData = $this->postStatisticsRepository
            ->getSourceDistributionByPeriod($period);

        if (empty($sourceData)) {
            return [
                'distribution' => [],
                'insights' => [
                    'diversity_score' => 0.0,
                    'dominant_source' => null,
                    'balanced' => false,
                ],
            ];
        }

        // 轉換為簡單的來源 => 數量對應關係
        $distribution = [];
        foreach ($sourceData as $item) {
            $distribution[$item['source_type']] = $item['post_count'];
        }

        $total = array_sum($distribution);
        $sourceCount = count($distribution);

        // 計算多樣性評分（香農熵）
        $diversityScore = $this->calculateShannonEntropy($distribution);

        // 找出主導來源
        arsort($distribution);
        $dominantSource = array_key_first($distribution);
        $dominantPercentage = $total > 0 ? ($distribution[$dominantSource] / $total) * 100 : 0;

        // 判斷是否平衡（沒有任何來源超過50%）
        $isBalanced = $dominantPercentage <= 50;

        return [
            'distribution' => $distribution,
            'insights' => [
                'diversity_score' => $diversityScore,
                'dominant_source' => $dominantSource,
                'dominant_percentage' => round($dominantPercentage, 1),
                'balanced' => $isBalanced,
                'source_count' => $sourceCount,
            ],
        ];
    }

    /**
     * 計算文章品質評分.
     * @param int $postId 文章ID
     * @return array<string, mixed>
     */
    public function calculatePostQualityScore(int $postId, StatisticsPeriod $period): array
    {
        $postStats = $this->postStatisticsRepository
            ->getPostStatsByPeriod($postId, $period);

        if (empty($postStats)) {
            return [
                'score' => 0.0,
                'factors' => [],
                'grade' => 'N/A',
            ];
        }

        $factors = [];
        $totalScore = 0.0;
        $maxScore = 100.0;

        // 觀看次數評分 (30%)
        $viewsScore = min(30, $postStats['views'] / 100);
        $factors['views'] = round($viewsScore, 1);
        $totalScore += $viewsScore;

        // 互動率評分 (25%)
        $engagementRate = $postStats['views'] > 0
            ? ($postStats['comments'] + $postStats['likes']) / $postStats['views']
            : 0;
        $engagementScore = min(25, $engagementRate * 2500);
        $factors['engagement'] = round($engagementScore, 1);
        $totalScore += $engagementScore;

        // 分享數評分 (20%)
        $shareScore = min(20, $postStats['shares'] / 5);
        $factors['shares'] = round($shareScore, 1);
        $totalScore += $shareScore;

        // 來源品質評分 (15%)
        $sourceScore = $this->calculateSourceQualityScore($postStats['source']);
        $factors['source_quality'] = $sourceScore;
        $totalScore += $sourceScore;

        // 持續性評分 (10%)
        $consistencyScore = $this->calculateConsistencyScore($postId, $period);
        $factors['consistency'] = $consistencyScore;
        $totalScore += $consistencyScore;

        $finalScore = round($totalScore, 1);
        $grade = $this->getQualityGrade($finalScore);

        return [
            'score' => $finalScore,
            'factors' => $factors,
            'grade' => $grade,
        ];
    }

    /**
     * 分析文章趨勢.
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed>
     */
    public function analyzeTrends(StatisticsPeriod $period): array
    {
        $trendData = $this->postStatisticsRepository
            ->getPostTrendsByPeriod($period);

        $trendingUp = [];
        $trendingDown = [];
        $stable = [];

        foreach ($trendData as $post) {
            // 確保 post 有必要的欄位
            if (!is_array($post)) {
                continue;
            }

            // 計算成長率（這裡簡化為基於 view_count 的成長）
            $viewCount = $post['view_count'] ?? 0;
            $postCount = $post['post_count'] ?? 0;

            // 簡單的成長率計算：如果是新文章 (post_count 為 1) 且有瀏覽量，則視為上升趨勢
            $growthRate = 0.0;
            if ($postCount > 0) {
                $growthRate = ($viewCount / $postCount) - 100; // 假設基準是每篇文章100瀏覽
            }

            // 將計算的成長率添加到陣列中
            $postWithGrowth = $post;
            $postWithGrowth['growth_rate'] = $growthRate;

            if ($growthRate > 10) {
                $trendingUp[] = $postWithGrowth;
            } elseif ($growthRate < -10) {
                $trendingDown[] = $postWithGrowth;
            } else {
                $stable[] = $postWithGrowth;
            }
        }

        // 按成長率排序
        usort($trendingUp, fn($a, $b): array => $b['growth_rate'] <=> $a['growth_rate']);
        usort($trendingDown, fn($a, $b): array => $a['growth_rate'] <=> $b['growth_rate']);

        return [
            'trending_up' => array_slice($trendingUp, 0, 10),
            'trending_down' => array_slice($trendingDown, 0, 10),
            'stable' => array_slice($stable, 0, 10),
        ];
    }

    /**
     * 計算文章投資報酬率 (ROI).
     * @param int $postId 文章ID
     * @param float $contentCost 內容製作成本
     * @return array<string, mixed>{roi: float, revenue: float, cost: float, profit: float} ROI分析結果
     */
    public function calculatePostROI(int $postId, StatisticsPeriod $period, float $contentCost): array
    {
        $postStats = $this->postStatisticsRepository
            ->getPostStatsByPeriod($postId, $period);

        if (empty($postStats)) {
            return [
                'roi' => 0.0,
                'revenue' => 0.0,
                'cost' => $contentCost,
                'profit' => -$contentCost,
            ];
        }

        // 假設每次觀看產生的收益（可配置）
        $revenuePerView = 0.01; // $0.01 per view
        $estimatedRevenue = $postStats['views'] * $revenuePerView;

        $profit = $estimatedRevenue - $contentCost;
        $roi = $contentCost > 0 ? ($profit / $contentCost) * 100 : 0.0;

        return [
            'roi' => round($roi, 2),
            'revenue' => round($estimatedRevenue, 2),
            'cost' => $contentCost,
            'profit' => round($profit, 2),
        ];
    }

    /**
     * 取得最佳發布時間建議.
     * @param StatisticsPeriod $period 分析週期
     * @return array<string, mixed>
     */
    public function getBestPublishingTimes(StatisticsPeriod $period): array
    {
        $timeData = $this->postStatisticsRepository
            ->getPostsByPublishTime($period);

        if (empty($timeData)) {
            return [
                'best_hours' => [],
                'best_days' => [],
                'insights' => [
                    'peak_hour' => null,
                    'peak_day' => null,
                    'recommendation' => '資料不足，無法提供建議',
                ],
            ];
        }

        // 分析最佳小時
        $hourlyStats = [];
        $dailyStats = [];

        foreach ($timeData as $post) {
            $hour = (int) $post['publish_hour'];
            $day = $post['publish_day'];
            $performance = $post['avg_views'];

            if (!isset($hourlyStats[$hour])) {
                $hourlyStats[$hour] = [];
            }
            if (!isset($dailyStats[$day])) {
                $dailyStats[$day] = [];
            }

            $hourlyStats[$hour][] = $performance;
            $dailyStats[$day][] = $performance;
        }

        // 計算平均表現
        $hourlyAverage = [];
        foreach ($hourlyStats as $hour => $performances) {
            $hourlyAverage[$hour] = array_sum($performances) / count($performances);
        }

        $dailyAverage = [];
        foreach ($dailyStats as $day => $performances) {
            $dailyAverage[$day] = array_sum($performances) / count($performances);
        }

        // 排序並取前3名
        arsort($hourlyAverage);
        arsort($dailyAverage);

        $bestHours = array_slice($hourlyAverage, 0, 3, true);
        $bestDays = array_slice($dailyAverage, 0, 3, true);

        $peakHour = array_key_first($hourlyAverage);
        $peakDay = array_key_first($dailyAverage);

        return [
            'best_hours' => $bestHours,
            'best_days' => $bestDays,
            'insights' => [
                'peak_hour' => $peakHour,
                'peak_day' => $peakDay,
                'recommendation' => "建議在 {$peakDay} 的 {$peakHour}:00 發布文章以獲得最佳表現",
            ],
        ];
    }

    /**
     * 計算香農熵（多樣性指標）.
     * @param array<string, int> $distribution 分佈資料
     * @return float 香農熵值
     */
    private function calculateShannonEntropy(array $distribution): float
    {
        $total = array_sum($distribution);

        if ($total === 0) {
            return 0.0;
        }

        $entropy = 0.0;

        foreach ($distribution as $count) {
            if ($count > 0) {
                $probability = $count / $total;
                $entropy += $probability * log($probability, 2);
            }
        }

        return round(-$entropy, 3);
    }

    /**
     * 計算來源品質評分.
     * @param string $source 來源類型
     * @return float 品質評分 (0-15)
     */
    private function calculateSourceQualityScore(string $source): float
    {
        return match ($source) {
            SourceType::WEB->value => 15.0,
            SourceType::MOBILE_APP->value => 14.0,
            SourceType::SEARCH_ENGINE->value => 12.0,
            SourceType::SOCIAL_MEDIA->value => 10.0,
            SourceType::REFERRAL->value => 8.0,
            SourceType::RSS_FEED->value => 7.0,
            SourceType::EMAIL_NEWSLETTER->value => 6.0,
            SourceType::API->value => 5.0,
            SourceType::DIRECT->value => 4.0,
            SourceType::UNKNOWN->value => 2.0,
            default => 3.0,
        };
    }

    /**
     * 計算一致性評分.
     * @param int $postId 文章ID
     * @return float 一致性評分 (0-10)
     */
    private function calculateConsistencyScore(int $postId, StatisticsPeriod $period): float
    {
        // 獲取歷史表現資料
        $historicalData = $this->postStatisticsRepository
            ->getPostHistoricalPerformance($postId, $period);

        if (count($historicalData) < 3) {
            return 5.0; // 預設中等評分
        }

        $performances = array_column($historicalData, 'daily_views');
        $mean = array_sum($performances) / count($performances);

        if ($mean === 0) {
            return 0.0;
        }

        $variance = array_sum(
            array_map(fn($value): array => ((float) $value - $mean) ** 2, $performances),
        ) / count($performances);

        $coefficientOfVariation = sqrt($variance) / $mean;

        // 變異係數越小，一致性越高
        $consistencyScore = max(0, 10 - ($coefficientOfVariation * 10));

        return round($consistencyScore, 1);
    }

    /**
     * 分析熱門內容.
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed>
     * @throws StatisticsCalculationException 當分析失敗時
     */
    public function analyzePopularContent(StatisticsPeriod $period, int $limit = 10): array
    {
        return $this->analyzePopularPosts($period, $limit);
    }

    /**
     * 取得週期內的熱門文章.
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, mixed>
     * @throws StatisticsCalculationException 當分析失敗時
     */
    public function getPopularPostsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        return $this->analyzePopularPosts($period, $limit);
    }

    /**
     * 取得品質等級.
     * @param float $score 評分
     * @return string 品質等級
     */
    private function getQualityGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C+',
            $score >= 40 => 'C',
            $score >= 30 => 'D',
            default => 'F',
        };
    }
}
