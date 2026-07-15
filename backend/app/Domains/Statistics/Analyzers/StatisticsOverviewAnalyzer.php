<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;

/**
 * 統計概覽分析器.
 *
 * 負責分析活動等級與活動分數
 */
class StatisticsOverviewAnalyzer
{
    /**
     * 執行統計概覽分析.
     */
    public function analyze(StatisticsOverviewDTO $dto): StatisticsOverviewResult
    {
        $activityScore = $this->calculateActivityScore($dto);

        return new StatisticsOverviewResult(
            activityLevel: $this->getActivityLevelFromScore($activityScore),
            activityScore: $activityScore,
        );
    }

    /**
     * 取得活動等級.
     */
    public function getActivityLevel(StatisticsOverviewDTO $dto): string
    {
        $activityScore = $this->calculateActivityScore($dto);

        return $this->getActivityLevelFromScore($activityScore);
    }

    private function getActivityLevelFromScore(float $activityScore): string
    {
        return match (true) {
            $activityScore >= 80 => 'high',
            $activityScore >= 50 => 'medium',
            $activityScore >= 20 => 'low',
            default              => 'inactive',
        };
    }

    /**
     * 計算活動分數.
     */
    public function calculateActivityScore(StatisticsOverviewDTO $dto): float
    {
        $postScore = min(($dto->getTotalPosts() / 100) * 40, 40);
        $userScore = min(($dto->getActiveUsers() / 50) * 30, 30);
        $growthScore = min($dto->getGrowthRate() / 10 * 30, 30);

        return round($postScore + $userScore + $growthScore, 2);
    }
}
