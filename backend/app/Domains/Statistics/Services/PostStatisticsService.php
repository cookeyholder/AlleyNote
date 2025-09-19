<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Exceptions\StatisticsCalculationException;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Exception;
use Psr\Log\LoggerInterface;

final class PostStatisticsService
{
    public function __construct(
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * 分析熱門文章.
     * @return array{posts: list<array<mixed>>, summary: array<string, mixed>}
     * @throws StatisticsCalculationException
     */
    public function analyzePopularPosts(StatisticsPeriod $period, int $limit = 10): array
    {
        try {
            $popularPosts = $this->postStatisticsRepository->getPopularPostsByPeriod($period, $limit);
            $popularPosts = array_values(array_map(fn($p) => (array) $p, (array) $popularPosts));

            if (count($popularPosts) === 0) {
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

            $totalViews = (int) array_sum(array_column($popularPosts, 'views'));
            $count = count($popularPosts);
            $averageViews = round((float) $totalViews / max(1, $count), 2);

            $sources = array_column($popularPosts, 'source');
            $sourceCounts = array_count_values($sources);
            arsort($sourceCounts);
            $topSource = array_key_first($sourceCounts);

            return [
                'posts' => $popularPosts,
                'summary' => [
                    'total_posts' => $count,
                    'total_views' => $totalViews,
                    'average_views' => $averageViews,
                    'top_source' => $topSource,
                ],
            ];
        } catch (Exception $e) {
            $this->logger?->error('熱門內容分析失敗', [
                'period' => $period->type->value ?? null,
                'error' => $e->getMessage(),
            ]);

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
    }

    /**
     * @return array{distribution: array<string,int>, insights: array<string, mixed>}
     */
    public function analyzeSourceDistribution(StatisticsPeriod $period): array
    {
        $sourceData = $this->postStatisticsRepository->getSourceDistributionByPeriod($period);

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

        /** @var array<string,int> $distribution */
        $distribution = [];
        foreach ($sourceData as $item) {
            $key = isset($item['source_type']) ? (string) $item['source_type'] : 'unknown';
            $distribution[$key] = isset($item['post_count']) ? (int) $item['post_count'] : 0;
        }

        $total = array_sum($distribution);
        $sourceCount = count($distribution);

        $diversityScore = $this->calculateShannonEntropy($distribution);

        arsort($distribution);
        $dominantSource = array_key_first($distribution);
        $dominantPercentage = $total > 0 ? ($distribution[$dominantSource] / $total) * 100.0 : 0.0;

        $isBalanced = $dominantPercentage <= 50.0;

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
     * @return array{score: float, factors: array<string, float|int>, grade: string}
     */
    public function calculatePostQualityScore(int $postId, StatisticsPeriod $period): array
    {
        $postStats = $this->postStatisticsRepository->getPostStatsByPeriod($postId, $period);

        $views = (int) $postStats['views'];
        $comments = (int) $postStats['comments'];
        $likes = (int) $postStats['likes'];
        $shares = (int) $postStats['shares'];
        $source = (string) $postStats['source'];

        if ($views === 0 && $comments === 0 && $likes === 0 && $shares === 0) {
            return [
                'score' => 0.0,
                'factors' => [],
                'grade' => 'N/A',
            ];
        }

        $factors = [];
        $totalScore = 0.0;

        $viewsScore = min(30.0, ((float) $views / 100.0));
        $factors['views'] = round($viewsScore, 1);
        $totalScore += $viewsScore;

        $engagementRate = $views > 0 ? (($comments + $likes) / $views) : 0.0;
        $engagementScore = min(25.0, $engagementRate * 2500.0);
        $factors['engagement'] = round($engagementScore, 1);
        $totalScore += $engagementScore;

        $shareScore = min(20.0, ((float) $shares / 5.0));
        $factors['shares'] = round($shareScore, 1);
        $totalScore += $shareScore;

        $sourceScore = $this->calculateSourceQualityScore($source);
        $factors['source_quality'] = $sourceScore;
        $totalScore += $sourceScore;

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
     * @return array{trending_up: list<array<int|string,mixed>>, trending_down: list<array<int|string,mixed>>, stable: list<array<int|string,mixed>>}
     */
    public function analyzeTrends(StatisticsPeriod $period): array
    {
        $trendData = $this->postStatisticsRepository->getPostTrendsByPeriod($period);
        $trendData = array_values(array_map(fn($t) => (array) $t, (array) $trendData));

        $trendingUp = [];
        $trendingDown = [];
        $stable = [];

        foreach ($trendData as $post) {
            $post = (array) $post;
            $viewCount = isset($post['view_count']) && is_numeric($post['view_count']) ? (float) $post['view_count'] : 0.0;
            $postCount = (int) ($post['post_count'] ?? 0);

            $growthRate = 0.0;
            if ($postCount > 0) {
                $growthRate = ($viewCount / $postCount) - 100.0;
            }

            $postWithGrowth = $post;
            $postWithGrowth['growth_rate'] = $growthRate;

            if ($growthRate > 10.0) {
                $trendingUp[] = $postWithGrowth;
            } elseif ($growthRate < -10.0) {
                $trendingDown[] = $postWithGrowth;
            } else {
                $stable[] = $postWithGrowth;
            }
        }

        usort($trendingUp, fn(array $a, array $b): int => $b['growth_rate'] <=> $a['growth_rate']);
        usort($trendingDown, fn(array $a, array $b): int => $a['growth_rate'] <=> $b['growth_rate']);

        return [
            'trending_up' => array_slice($trendingUp, 0, 10),
            'trending_down' => array_slice($trendingDown, 0, 10),
            'stable' => array_slice($stable, 0, 10),
        ];
    }

    /**
     * @return array{roi: float, revenue: float, cost: float, profit: float}
     */
    public function calculatePostROI(int $postId, StatisticsPeriod $period, float $contentCost): array
    {
        $postStats = $this->postStatisticsRepository->getPostStatsByPeriod($postId, $period);
        /** @var array{views: int, comments: int, likes: int, shares: int, source: string} $postStats */
        // 介面已宣告回傳包含預期欄位的陣列，直接使用索引存取以符合 PHPStan 的型別推論
        $views = (int) $postStats['views'];
        $revenuePerView = 0.01;
        $estimatedRevenue = ((float) $views) * $revenuePerView;

        $profit = $estimatedRevenue - $contentCost;
        $roi = $contentCost > 0.0 ? ($profit / $contentCost) * 100.0 : 0.0;

        return [
            'roi' => round($roi, 2),
            'revenue' => round($estimatedRevenue, 2),
            'cost' => $contentCost,
            'profit' => round($profit, 2),
        ];
    }

    /**
     * @return array{best_hours: array<int,float>, best_days: array<string,float>, insights: array<string, mixed>}
     */
    public function getBestPublishingTimes(StatisticsPeriod $period): array
    {
        $timeData = $this->postStatisticsRepository->getPostsByPublishTime($period);

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

        $hourlyStats = [];
        $dailyStats = [];

        foreach ($timeData as $post) {
            $hour = isset($post['publish_hour']) ? (int) $post['publish_hour'] : 0;
            $day = isset($post['publish_day']) ? (string) $post['publish_day'] : '';
            $performance = isset($post['avg_views']) && is_numeric($post['avg_views']) ? (float) $post['avg_views'] : 0.0;

            $hourlyStats[$hour][] = $performance;
            $dailyStats[$day][] = $performance;
        }

        $hourlyAverage = [];
        foreach ($hourlyStats as $hour => $performances) {
            $hourlyAverage[$hour] = array_sum($performances) / max(1, count($performances));
        }

        $dailyAverage = [];
        foreach ($dailyStats as $day => $performances) {
            $dailyAverage[$day] = array_sum($performances) / max(1, count($performances));
        }

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
                'recommendation' => "建議在 {$peakDay} 的 {$peakHour} => 00 發布文章以獲得最佳表現",
            ],
        ];
    }

    /**
     * 分析熱門內容.
     *
     * @return array{posts: list<array<int|string,mixed>>, summary: array<string,mixed>}
     * @throws StatisticsCalculationException 當分析失敗時
     */
    public function analyzePopularContent(StatisticsPeriod $period, int $limit = 10): array
    {
        $result = $this->analyzePopularPosts($period, $limit);

        return $result;
    }

    /**
     * 計算香農熵（多樣性指標）.
     *
     * @param array<string,int> $distribution 分佈資料
     */
    private function calculateShannonEntropy(array $distribution): float
    {
        $total = array_sum($distribution);

        if ($total == 0) {
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
     *
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
     *
     * @return float 一致性評分 (0-10)
     */
    private function calculateConsistencyScore(int $postId, StatisticsPeriod $period): float
    {
        $historicalData = $this->postStatisticsRepository->getPostHistoricalPerformance($postId, $period);

        if (count($historicalData) < 3) {
            return 5.0; // 預設中等評分
        }

        $performances = array_column($historicalData, 'daily_views');
        $mean = array_sum($performances) / count($performances);

        if ($mean == 0) {
            return 0.0;
        }

        $variance = array_sum(
            array_map(fn($value): float => (is_numeric($value) ? (($value + 0.0 - $mean) ** 2) : 0.0), $performances),
        ) / count($performances);

        $coefficientOfVariation = sqrt($variance) / $mean;

        $consistencyScore = max(0, 10 - ($coefficientOfVariation * 10));

        return round($consistencyScore, 1);
    }

    /**
     * 取得熱門文章（代理方法）。
     *
     * @return list<array<int|string,mixed>>
     */
    public function getPopularPostsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        $result = $this->analyzePopularPosts($period, $limit);

        return $result['posts'];
    }

    /**
     * 取得品質等級.
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
            $score >= 30 => 'D+',
            $score >= 20 => 'D',
            default => 'F',
        };
    }
}
