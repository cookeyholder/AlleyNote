<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\DTOs\PostStatisticsDTO;

/**
 * 文章統計分析器.
 *
 * 負責分析內容品質、互動指標與內容分析
 */
class PostStatisticsAnalyzer
{
    /**
     * 執行完整文章統計分析.
     */
    public function analyze(PostStatisticsDTO $dto): PostStatisticsResult
    {
        return new PostStatisticsResult(
            contentQualityMetrics: $this->getContentQualityMetrics($dto),
            engagementMetrics: $this->getEngagementMetrics($dto),
            contentAnalysis: $this->getContentAnalysis($dto),
        );
    }

    /**
     * 取得內容品質指標.
     *
     * @return array{average_length: float, quality_score: float, engagement_ratio: float, publish_rate: float}
     */
    public function getContentQualityMetrics(PostStatisticsDTO $dto): array
    {
        $lengthStatistics = $dto->getLengthStatistics();
        $avgLength = $lengthStatistics['avg_length'] ?? 0;
        $avgLengthFloat = is_numeric($avgLength) ? (float) $avgLength : 0.0;
        $totalViews = $dto->getTotalViews();
        $totalPosts = $dto->getTotalPosts();

        return [
            'average_length'   => $avgLengthFloat,
            'quality_score'    => $this->calculateQualityScore($dto),
            'engagement_ratio' => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0.0,
            'publish_rate'     => $dto->getPublishRate(),
        ];
    }

    /**
     * 取得互動指標.
     *
     * @return array{views_per_post_ratio: float, pinned_engagement_rate: float, author_productivity: float}
     */
    public function getEngagementMetrics(PostStatisticsDTO $dto): array
    {
        $totalViews = $dto->getTotalViews();
        $totalPosts = $dto->getTotalPosts();

        return [
            'views_per_post_ratio'   => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0.0,
            'pinned_engagement_rate' => $totalViews > 0 ? round(($dto->getPinnedPostsViews() / $totalViews) * 100, 2) : 0.0,
            'author_productivity'    => $this->calculateAuthorProductivity($dto),
        ];
    }

    /**
     * 取得內容分析.
     *
     * @return array{length_distribution: array{average: int, minimum: int, maximum: int}, optimal_length_score: float, content_diversity: float}
     */
    public function getContentAnalysis(PostStatisticsDTO $dto): array
    {
        return [
            'length_distribution' => [
                'average' => $dto->getAverageLength(),
                'minimum' => $dto->getMinLength(),
                'maximum' => $dto->getMaxLength(),
            ],
            'optimal_length_score' => $this->calculateOptimalLengthScore($dto),
            'content_diversity'    => $this->calculateContentDiversity($dto),
        ];
    }

    /**
     * 計算內容品質分數.
     */
    private function calculateQualityScore(PostStatisticsDTO $dto): float
    {
        $lengthStatistics = $dto->getLengthStatistics();
        $avgLength = $lengthStatistics['avg_length'] ?? 0;
        $avgLengthFloat = is_numeric($avgLength) ? (float) $avgLength : 0.0;
        $publishRate = $dto->getPublishRate();
        $avgViews = $dto->getAverageViews();
        $lengthScore = min(($avgLengthFloat / 500) * 40, 40);
        $publishScore = ($publishRate / 100) * 30;
        $viewsScore = min(($avgViews / 100) * 30, 30);

        return round($lengthScore + $publishScore + $viewsScore, 2);
    }

    private function calculateAuthorProductivity(PostStatisticsDTO $dto): float
    {
        $topAuthors = $dto->getTopAuthors();
        if (!empty($topAuthors)) {
            $totalPostCount = 0;
            foreach ($topAuthors as $author) {
                if (is_array($author) && isset($author['posts_count']) && is_numeric($author['posts_count'])) {
                    $totalPostCount += (int) $author['posts_count'];
                }
            }

            return $totalPostCount / count($topAuthors);
        }

        return 0.0;
    }

    /**
     * 計算最佳長度分數.
     */
    private function calculateOptimalLengthScore(PostStatisticsDTO $dto): float
    {
        $avgLength = $dto->getAverageLength();
        $optimalRange = [500, 2000];
        if ($avgLength >= $optimalRange[0] && $avgLength <= $optimalRange[1]) {
            return 1.0;
        }
        if ($avgLength < $optimalRange[0]) {
            return $avgLength / $optimalRange[0];
        }
        $excess = $avgLength - $optimalRange[1];
        $penalty = min($excess / $optimalRange[1], 0.5);

        return max(0.5, 1.0 - $penalty);
    }

    /**
     * 計算內容多樣性.
     */
    private function calculateContentDiversity(PostStatisticsDTO $dto): float
    {
        $maxLength = $dto->getMaxLength();
        $minLength = $dto->getMinLength();
        $avgLength = $dto->getAverageLength();
        if ($maxLength === $minLength || $avgLength === 0) {
            return 0.0;
        }
        $lengthRange = $maxLength - $minLength;
        $diversityScore = min($lengthRange / $avgLength, 2.0);

        return round($diversityScore, 2);
    }
}
