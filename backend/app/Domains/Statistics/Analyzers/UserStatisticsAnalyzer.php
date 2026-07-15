<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use App\Domains\Statistics\DTOs\UserStatisticsDTO;

/**
 * 使用者統計分析器.
 *
 * 負責分析使用者參與度、活動時間洞察
 */
class UserStatisticsAnalyzer
{
    /**
     * 執行完整使用者統計分析.
     */
    public function analyze(UserStatisticsDTO $dto): UserStatisticsResult
    {
        return new UserStatisticsResult(
            engagementAnalysis: $this->getEngagementAnalysis($dto),
            activityInsights: $this->getActivityInsights($dto),
        );
    }

    /**
     * 取得使用者參與度分析.
     *
     * @return array<string, mixed>
     */
    public function getEngagementAnalysis(UserStatisticsDTO $dto): array
    {
        $totalUsers = $dto->getHighEngagementUsers()
                     + $dto->getMediumEngagementUsers()
                     + $dto->getLowEngagementUsers()
                     + $dto->getInactiveUsers();

        return [
            'total_users'              => $totalUsers,
            'engagement_rate'          => $dto->getEngagementRate(),
            'average_engagement_score' => $dto->getAverageEngagementScore(),
            'engagement_distribution'  => [
                'high'     => ['count' => $dto->getHighEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($dto->getHighEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'medium'   => ['count' => $dto->getMediumEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($dto->getMediumEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'low'      => ['count' => $dto->getLowEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($dto->getLowEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'inactive' => ['count' => $dto->getInactiveUsers(), 'percentage' => $totalUsers > 0 ? round(($dto->getInactiveUsers() / $totalUsers) * 100, 1) : 0],
            ],
        ];
    }

    /**
     * 取得活動時間洞察.
     *
     * @return array<string, mixed>
     */
    public function getActivityInsights(UserStatisticsDTO $dto): array
    {
        $peakHour = $this->getPeakActiveHour($dto);

        return [
            'peak_login_hour'    => $dto->getPeakHour(),
            'peak_activity_hour' => $peakHour,
            'activity_pattern'   => $this->getActivityPattern($dto),
            'weekend_vs_weekday' => $this->getWeekendVsWeekdayActivity(),
        ];
    }

    /**
     * 取得最活躍的時間.
     */
    private function getPeakActiveHour(UserStatisticsDTO $dto): ?string
    {
        $activityTimeDistribution = $dto->getActivityTimeDistribution();
        if (empty($activityTimeDistribution)) {
            return null;
        }
        $maxCount = max($activityTimeDistribution);
        $peakHours = array_keys($activityTimeDistribution, $maxCount);

        return $peakHours[0] ?? null;
    }

    /**
     * 取得活動模式.
     */
    private function getActivityPattern(UserStatisticsDTO $dto): string
    {
        $activityTimeDistribution = $dto->getActivityTimeDistribution();
        $totalActivity = array_sum($activityTimeDistribution);
        if ($totalActivity === 0 || empty($activityTimeDistribution)) {
            return 'inactive';
        }
        $peakCount = max($activityTimeDistribution);
        $concentration = ($peakCount / $totalActivity) * 100;

        return match (true) {
            $concentration >= 50 => 'concentrated',
            $concentration >= 30 => 'moderate',
            default              => 'distributed',
        };
    }

    /**
     * 取得週末與工作日活動比較.
     *
     * @return array<string, mixed>
     */
    private function getWeekendVsWeekdayActivity(): array
    {
        return [
            'weekend_percentage'     => 30,
            'weekday_percentage'     => 70,
            'weekend_activity_score' => 0.3,
            'weekday_activity_score' => 0.7,
        ];
    }
}
