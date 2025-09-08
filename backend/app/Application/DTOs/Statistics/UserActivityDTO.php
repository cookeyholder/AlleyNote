<?php

declare(strict_types=1);

namespace App\Application\DTOs\Statistics;

use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 使用者活動統計資料傳輸物件.
 *
 * 用於傳輸使用者活動統計資料的 DTO 類別。
 * 包含活躍使用者資訊、活動模式分析、參與度統計等。
 *
 * 設計原則：
 * - 不可變物件 (Immutable)
 * - 支援 JSON 序列化
 * - 包含資料驗證邏輯
 * - 提供活動分析方法
 */
final readonly class UserActivityDTO implements JsonSerializable
{
    /**
     * @param StatisticsPeriod $period 統計週期
     * @param StatisticsMetric $newUsers 新使用者數
     * @param array> $topActiveUsers
     * @param array $engagementMetrics 參與度指標
     */



    public function __construct(
        public StatisticsPeriod $period,
        public StatisticsMetric $totalActiveUsers,
        public StatisticsMetric $newUsers,
        public StatisticsMetric $returningUsers,
        /** @var array<array<string, mixed> */
        public array $topActiveUsers,
        /** @var array<string, mixed> */
        public array $activityPatterns,
        /** @var array<string, mixed> */
        public array $engagementMetrics,
        public DateTimeImmutable $generatedAt,
    ) {
        $this->validateTopActiveUsers($this->topActiveUsers);
        $this->validateActivityPatterns($this->activityPatterns);
    }

    /**
     * 從統計資料建立 DTO.
     */
    /**
     * 從統計資料建立 DTO.
     * @param array $userStats
     * @param array $patterns
     */
    public static function fromStatistics(
        StatisticsPeriod $period,
        /** @var array<string, mixed> */
        array $userStats,
        /** @var array<array<string, mixed> */
        array $topUsers = [],
        /** @var array<string, mixed> */
        array $patterns = [],
    ): self {
        $totalActiveValue = $userStats['total_active'] ?? null;
        $totalActive = StatisticsMetric::count(
            is_numeric($totalActiveValue) ? (int) $totalActiveValue : 0,
            '總活躍使用者數',
        );

        $newUsersValue = $userStats['new_users'] ?? null;
        $newUsers = StatisticsMetric::count(
            is_numeric($newUsersValue) ? (int) $newUsersValue : 0,
            '新使用者數',
        );

        $returningUsersValue = $userStats['returning_users'] ?? null;
        $returningUsers = StatisticsMetric::count(
            is_numeric($returningUsersValue) ? (int) $returningUsersValue : 0,
            '回訪使用者數',
        );

        // 計算參與度指標
        $engagementMetrics = self::calculateEngagementMetrics($userStats);

        return new self(
            $period,
            $totalActive,
            $newUsers,
            $returningUsers,
            $topUsers, // 移除不必要的 null coalesce
            $patterns,
            $engagementMetrics,
            new DateTimeImmutable(),
        );
    }

    /**
     * 從陣列資料建立 DTO.
     * @param array $data
     */
    public static function fromArray(array $data): self
    {
        // 使用型別安全的方式存取期間資料
        /** @var array<string, mixed> $periodData */
        $periodData = is_array($data['period'] ?? []) ? $data['period'] : [];

        $periodStartDate = $periodData['start_date'] ?? null;
        $startDate = is_string($periodStartDate) ? $periodStartDate : 'now';

        $periodEndDate = $periodData['end_date'] ?? null;
        $endDate = is_string($periodEndDate) ? $periodEndDate : 'now';

        $periodTypeValue = $periodData['type'] ?? null;
        $periodType = is_string($periodTypeValue) || is_int($periodTypeValue) ? $periodTypeValue : 'daily';

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 安全地提取統計指標
        $activeUsersValue = $data['active_users'] ?? null;
        $activeUsers = StatisticsMetric::count(
            is_numeric($activeUsersValue) ? (int) $activeUsersValue : 0,
            '活躍用戶數',
        );

        $newUsersValue = $data['new_users'] ?? null;
        $newUsers = StatisticsMetric::count(
            is_numeric($newUsersValue) ? (int) $newUsersValue : 0,
            '新用戶數',
        );

        $returningUsersValue = $data['returning_users'] ?? null;
        $returningUsers = StatisticsMetric::count(
            is_numeric($returningUsersValue) ? (int) $returningUsersValue : 0,
            '回訪用戶數',
        );

        $topActiveUsersRaw = $data['top_active_users'] ?? [];
        /** @var array<array<string, mixed> $topActiveUsers */
        $topActiveUsers = is_array($topActiveUsersRaw) ? array_filter($topActiveUsersRaw, 'is_array') : [];

        $activityPatternsRaw = $data['activity_patterns'] ?? [];
        /** @var array<string, mixed> $activityPatterns */
        $activityPatterns = is_array($activityPatternsRaw) ? $activityPatternsRaw : [];

        $engagementMetricsRaw = $data['engagement_metrics'] ?? [];
        /** @var array<string, mixed> $engagementMetrics */
        $engagementMetrics = is_array($engagementMetricsRaw) ? $engagementMetricsRaw : [];

        $generatedAtValue = $data['generated_at'] ?? null;
        $generatedAt = is_string($generatedAtValue) ? $generatedAtValue : 'now';

        return new self(
            $period,
            $activeUsers,
            $newUsers,
            $returningUsers,
            $topActiveUsers,
            $activityPatterns,
            $engagementMetrics,
            new DateTimeImmutable($generatedAt),
        );
    }

    /**
     * 取得新使用者比率.
     */
    public function getNewUserRatio(): float
    {
        if ($this->totalActiveUsers->value === 0) {
            return 0.0;
        }

        return round(($this->newUsers->value / $this->totalActiveUsers->value) * 100, 2);
    }

    /**
     * 取得回訪使用者比率.
     */
    public function getReturningUserRatio(): float
    {
        if ($this->totalActiveUsers->value === 0) {
            return 0.0;
        }

        return round(($this->returningUsers->value / $this->totalActiveUsers->value) * 100, 2);
    }

    /**
     * 取得使用者留存率.
     */
    public function getRetentionRate(): float
    {
        return $this->getReturningUserRatio();
    }

    /**
     * 取得使用者成長率.
     */
    public function getGrowthRate(): float
    {
        $value = $this->engagementMetrics['growth_rate'] ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 取得平均會話時長.
     */
    public function getAverageSessionDuration(): float
    {
        $value = $this->engagementMetrics['avg_session_duration'] ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 取得平均頁面瀏覽數.
     */
    public function getAveragePageViews(): float
    {
        $value = $this->engagementMetrics['avg_page_views'] ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 取得跳出率.
     */
    public function getBounceRate(): float
    {
        $value = $this->engagementMetrics['bounce_rate'] ?? null;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 檢查是否有健康的使用者成長.
     */
    public function hasHealthyGrowth(): bool
    {
        return $this->getGrowthRate() > 0 && $this->getNewUserRatio() > 20;
    }

    /**
     * 檢查是否有良好的使用者參與度.
     */
    public function hasGoodEngagement(): bool
    {
        return $this->getBounceRate() < 60 && $this->getAverageSessionDuration() > 120; // 2分鐘
    }

    /**
     * 取得最活躍使用者資訊.
     * @return array>
     */
    public function getTopActiveUsersSummary(): array
    {
        if (empty($this->topActiveUsers)) {
            return [];
        }

        return array_map(
            /** @param array $user */
            fn(array $user): array => [
                'user_id' => $user['user_id'],
                'username' => $user['username'] ?? 'Unknown',
                'activity_score' => $user['activity_score'] ?? 0,
                'sessions_count' => $user['sessions_count'] ?? 0,
                'total_time' => $user['total_time'] ?? 0,
                'pages_viewed' => $user['pages_viewed'] ?? 0,
            ],
            array_slice($this->topActiveUsers, 0, 10),
        );
    }

    /**
     * 取得活動時段分析.
     * @return array
     */
    public function getActivityTimeAnalysis(): array
    {
        $value = $this->activityPatterns['time_analysis'] ?? null;

        /** @var array<string, mixed> */
        return is_array($value) ? $value : [];
    }

    /**
     * 取得最熱門活動時段.
     * @return array>
     */
    public function getPeakActivityHours(): array
    {
        $timeAnalysis = $this->getActivityTimeAnalysis();
        if (empty($timeAnalysis)) {
            return [];
        }

        // 排序並取前3個時段
        uasort($timeAnalysis, static function ($a, $b): int {
            $countA = is_array($a) ? ($a['activity_count'] ?? 0) : 0;
            $countB = is_array($b) ? ($b['activity_count'] ?? 0) : 0;

            return is_numeric($countB) && is_numeric($countA)
                ? ((int) $countB <=> (int) $countA) : 0;
        });

        /** @var array<int, array<string, mixed> $timeAnalysis */
        return array_slice($timeAnalysis, 0, 3, true);
    }

    /**
     * 取得使用者活動摘要
     * @return array
     */
    public function getActivitySummary(): array
    {
        return [
            'user_metrics' => [
                'total_active' => $this->totalActiveUsers->value,
                'new_users' => $this->newUsers->value,
                'returning_users' => $this->returningUsers->value,
                'new_user_ratio' => $this->getNewUserRatio(),
                'retention_rate' => $this->getRetentionRate(),
            ],
            'engagement_metrics' => [
                'growth_rate' => $this->getGrowthRate(),
                'avg_session_duration' => $this->getAverageSessionDuration(),
                'avg_page_views' => $this->getAveragePageViews(),
                'bounce_rate' => $this->getBounceRate(),
            ],
            'quality_indicators' => [
                'has_healthy_growth' => $this->hasHealthyGrowth(),
                'has_good_engagement' => $this->hasGoodEngagement(),
            ],
            'top_users_count' => count($this->topActiveUsers),
            'peak_activity_hours' => array_keys($this->getPeakActivityHours()),
        ];
    }

    /**
     * 取得格式化的活動資訊.
     * @return array
     */
    public function getFormattedActivity(): array
    {
        return [
            'period_info' => [
                'display_name' => $this->period->getDisplayName(),
                'start_date' => $this->period->startDate->format('Y-m-d H => i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
            ],
            'user_statistics' => [
                'total_active_users' => [
                    'value' => $this->totalActiveUsers->value,
                    'formatted' => $this->totalActiveUsers->getFormattedValueWithUnit(),
                ],
                'new_users' => [
                    'value' => $this->newUsers->value,
                    'formatted' => $this->newUsers->getFormattedValueWithUnit(),
                    'ratio' => $this->getNewUserRatio(),
                ],
                'returning_users' => [
                    'value' => $this->returningUsers->value,
                    'formatted' => $this->returningUsers->getFormattedValueWithUnit(),
                    'ratio' => $this->getReturningUserRatio(),
                ],
            ],
            'engagement_analysis' => $this->engagementMetrics,
            'top_active_users' => $this->getTopActiveUsersSummary(),
            'activity_patterns' => $this->activityPatterns,
            'peak_hours' => $this->getPeakActivityHours(),
            'summary' => $this->getActivitySummary(),
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 比較與另一個週期的活動差異.
     * @return array
     */
    public function compareWith(UserActivityDTO $other): array
    {
        return [
            'period_comparison' => [
                'current' => $this->period->__toString(),
                'previous' => $other->period->__toString(),
            ],
            'user_changes' => [
                'total_active' => [
                    'current' => $this->totalActiveUsers->value,
                    'previous' => $other->totalActiveUsers->value,
                    'change' => $this->totalActiveUsers->value - $other->totalActiveUsers->value,
                    'change_percentage' => $other->totalActiveUsers->value > 0
                        ? round((($this->totalActiveUsers->value - $other->totalActiveUsers->value) / $other->totalActiveUsers->value) * 100, 2) : 0,
                ],
                'new_users' => [
                    'current' => $this->newUsers->value,
                    'previous' => $other->newUsers->value,
                    'change' => $this->newUsers->value - $other->newUsers->value,
                    'change_percentage' => $other->newUsers->value > 0
                        ? round((($this->newUsers->value - $other->newUsers->value) / $other->newUsers->value) * 100, 2) : 0,
                ],
            ],
            'engagement_changes' => [
                'retention_rate' => [
                    'current' => $this->getRetentionRate(),
                    'previous' => $other->getRetentionRate(),
                    'change' => round($this->getRetentionRate() - $other->getRetentionRate(), 2),
                ],
                'growth_rate' => [
                    'current' => $this->getGrowthRate(),
                    'previous' => $other->getGrowthRate(),
                    'change' => round($this->getGrowthRate() - $other->getGrowthRate(), 2),
                ],
            ],
            'improvement_indicators' => [
                'user_growth_improved' => $this->totalActiveUsers->value > $other->totalActiveUsers->value,
                'retention_improved' => $this->getRetentionRate() > $other->getRetentionRate(),
                'engagement_improved' => $this->hasGoodEngagement() && !$other->hasGoodEngagement(),
            ],
        ];
    }

    /**
     * 轉換為陣列.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H => i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
                'display_name' => $this->period->getDisplayName(),
            ],
            'user_metrics' => [
                'total_active_users' => $this->totalActiveUsers->value,
                'new_users' => $this->newUsers->value,
                'returning_users' => $this->returningUsers->value,
            ],
            'calculated_metrics' => [
                'new_user_ratio' => $this->getNewUserRatio(),
                'retention_rate' => $this->getRetentionRate(),
                'growth_rate' => $this->getGrowthRate(),
            ],
            'engagement_metrics' => $this->engagementMetrics,
            'top_active_users' => $this->topActiveUsers,
            'activity_patterns' => $this->activityPatterns,
            'quality_indicators' => [
                'has_healthy_growth' => $this->hasHealthyGrowth(),
                'has_good_engagement' => $this->hasGoodEngagement(),
            ],
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * JSON 序列化.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return sprintf(
            'UserActivity[%s => %d active, %.1f%% retention]',
            $this->period->getDisplayName(),
            $this->totalActiveUsers->value,
            $this->getRetentionRate(),
        );
    }

    /**
     * 計算參與度指標.
     */
    /**
     * 計算參與度指標.
     * @param array $userStats
     * @return array
     */
    private static function calculateEngagementMetrics(array $userStats): array
    {
        $growthRateValue = $userStats['growth_rate'] ?? null;
        $avgSessionDurationValue = $userStats['avg_session_duration'] ?? null;
        $avgPageViewsValue = $userStats['avg_page_views'] ?? null;
        $bounceRateValue = $userStats['bounce_rate'] ?? null;
        $conversionRateValue = $userStats['conversion_rate'] ?? null;

        return [
            'growth_rate' => is_numeric($growthRateValue) ? (float) $growthRateValue : 0.0,
            'avg_session_duration' => is_numeric($avgSessionDurationValue) ? (float) $avgSessionDurationValue : 0.0,
            'avg_page_views' => is_numeric($avgPageViewsValue) ? (float) $avgPageViewsValue : 0.0,
            'bounce_rate' => is_numeric($bounceRateValue) ? (float) $bounceRateValue : 0.0,
            'conversion_rate' => is_numeric($conversionRateValue) ? (float) $conversionRateValue : 0.0,
            'calculated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 驗證最活躍使用者資料.
     * @param array> $topUsers
     */
    private function validateTopActiveUsers(array $topUsers): void
    {
        foreach ($topUsers as $index => $user) {
            if (!isset($user['user_id'])) {
                throw new InvalidArgumentException(
                    "最活躍使用者索引 {$index} 必須包含 user_id");
            }
        }
    }

    /**
     * 驗證活動模式資料.
     * @param array $patterns
     */
    private function validateActivityPatterns(array $patterns): void
    {
        // 活動模式陣列格式已通過型別檢查確認
    }
}
