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
     * @param StatisticsMetric $totalActiveUsers 總活躍使用者數
     * @param StatisticsMetric $newUsers 新使用者數
     * @param StatisticsMetric $returningUsers 回訪使用者數
     * @param array<array> $topActiveUsers 最活躍使用者清單
     * @param array<string, mixed> $activityPatterns 活動模式分析
     * @param array<string, mixed> $engagementMetrics 參與度指標
     * @param DateTimeImmutable $generatedAt 產生時間
     */
    public function __construct(
        public StatisticsPeriod $period,
        public StatisticsMetric $totalActiveUsers,
        public StatisticsMetric $newUsers,
        public StatisticsMetric $returningUsers,
        public array $topActiveUsers,
        public array $activityPatterns,
        public array $engagementMetrics,
        public DateTimeImmutable $generatedAt,
    ) {
        $this->validateTopActiveUsers($topActiveUsers);
        $this->validateActivityPatterns($activityPatterns);
    }

    /**
     * 從統計資料建立 DTO.
     */
    public static function fromStatistics(
        StatisticsPeriod $period,
        array $userStats,
        array $topUsers = [],
        array $patterns = [],
    ): self {
        $totalActive = StatisticsMetric::count(
            $userStats['total_active'] ?? 0,
            '總活躍使用者數',
        );

        $newUsers = StatisticsMetric::count(
            $userStats['new_users'] ?? 0,
            '新使用者數',
        );

        $returningUsers = StatisticsMetric::count(
            $userStats['returning_users'] ?? 0,
            '回訪使用者數',
        );

        // 計算參與度指標
        $engagementMetrics = self::calculateEngagementMetrics($userStats);

        return new self(
            $period,
            $totalActive,
            $newUsers,
            $returningUsers,
            $topUsers,
            $patterns,
            $engagementMetrics,
            new DateTimeImmutable(),
        );
    }

    /**
     * 從陣列資料建立 DTO.
     */
    public static function fromArray(array $data): self
    {
        $period = StatisticsPeriod::create(
            new DateTimeImmutable($data['period']['start_date']),
            new DateTimeImmutable($data['period']['end_date']),
            PeriodType::from($data['period']['type']),
        );

        $totalActive = StatisticsMetric::count(
            $data['total_active_users'],
            '總活躍使用者數',
        );

        $newUsers = StatisticsMetric::count(
            $data['new_users'],
            '新使用者數',
        );

        $returningUsers = StatisticsMetric::count(
            $data['returning_users'] ?? 0,
            '回訪使用者數',
        );

        return new self(
            $period,
            $totalActive,
            $newUsers,
            $returningUsers,
            $data['top_active_users'] ?? [],
            $data['activity_patterns'] ?? [],
            $data['engagement_metrics'] ?? [],
            new DateTimeImmutable($data['generated_at'] ?? 'now'),
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
        return $this->engagementMetrics['growth_rate'] ?? 0.0;
    }

    /**
     * 取得平均會話時長.
     */
    public function getAverageSessionDuration(): float
    {
        return $this->engagementMetrics['avg_session_duration'] ?? 0.0;
    }

    /**
     * 取得平均頁面瀏覽數.
     */
    public function getAveragePageViews(): float
    {
        return $this->engagementMetrics['avg_page_views'] ?? 0.0;
    }

    /**
     * 取得跳出率.
     */
    public function getBounceRate(): float
    {
        return $this->engagementMetrics['bounce_rate'] ?? 0.0;
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
     */
    public function getTopActiveUsersSummary(): array
    {
        if (empty($this->topActiveUsers)) {
            return [];
        }

        return array_map(
            fn(array $user) => [
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
     */
    public function getActivityTimeAnalysis(): array
    {
        return $this->activityPatterns['time_analysis'] ?? [];
    }

    /**
     * 取得最熱門活動時段.
     */
    public function getPeakActivityHours(): array
    {
        $timeAnalysis = $this->getActivityTimeAnalysis();
        if (empty($timeAnalysis)) {
            return [];
        }

        // 排序並取前3個時段
        uasort($timeAnalysis, fn($a, $b) => ($b['activity_count'] ?? 0) <=> ($a['activity_count'] ?? 0));

        return array_slice($timeAnalysis, 0, 3, true);
    }

    /**
     * 取得使用者活動摘要
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
     */
    public function getFormattedActivity(): array
    {
        return [
            'period_info' => [
                'display_name' => $this->period->getDisplayName(),
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
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
     */
    public function toArray(): array
    {
        return [
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
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
            'UserActivity[%s: %d active, %.1f%% retention]',
            $this->period->getDisplayName(),
            $this->totalActiveUsers->value,
            $this->getRetentionRate(),
        );
    }

    /**
     * 計算參與度指標.
     */
    private static function calculateEngagementMetrics(array $userStats): array
    {
        return [
            'growth_rate' => $userStats['growth_rate'] ?? 0.0,
            'avg_session_duration' => $userStats['avg_session_duration'] ?? 0.0,
            'avg_page_views' => $userStats['avg_page_views'] ?? 0.0,
            'bounce_rate' => $userStats['bounce_rate'] ?? 0.0,
            'conversion_rate' => $userStats['conversion_rate'] ?? 0.0,
            'calculated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 驗證最活躍使用者資料.
     */
    private function validateTopActiveUsers(array $topUsers): void
    {
        foreach ($topUsers as $index => $user) {
            if (!is_array($user)) {
                throw new InvalidArgumentException(
                    "最活躍使用者索引 {$index} 必須是陣列",
                );
            }

            if (!isset($user['user_id'])) {
                throw new InvalidArgumentException(
                    "最活躍使用者索引 {$index} 必須包含 user_id",
                );
            }
        }
    }

    /**
     * 驗證活動模式資料.
     */
    private function validateActivityPatterns(array $patterns): void
    {
        // 基本驗證：確保是陣列格式
        if (!is_array($patterns)) {
            throw new InvalidArgumentException('活動模式必須是陣列格式');
        }
    }
}
