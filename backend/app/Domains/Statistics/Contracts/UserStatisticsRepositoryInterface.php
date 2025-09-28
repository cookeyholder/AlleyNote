<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 使用者統計查詢 Repository 介面.
 *
 * 專門處理使用者相關的統計查詢，提供使用者活躍度與行為分析。
 * 此介面遵循 ISP（Interface Segregation Principle），專注於使用者統計查詢。
 */
interface UserStatisticsRepositoryInterface
{
    /**
     * 取得活躍使用者總數.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param string $activityType 活動類型（login, post, view, comment）
     * @return int 活躍使用者數量
     */
    public function getActiveUsersCount(StatisticsPeriod $period, string $activityType = 'login'): int;

    /**
     * 取得新註冊使用者數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return int 新註冊使用者數量
     */
    public function getNewUsersCount(StatisticsPeriod $period): int;

    /**
     * 取得使用者總數統計.
     *
     * @param StatisticsPeriod $period 統計週期（以此為基準點）
     * @return int 截至該週期結束的使用者總數
     */
    public function getTotalUsersCount(StatisticsPeriod $period): int;

    /**
     * 根據活動類型分組統計活躍使用者.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, int> 活動類型為鍵，使用者數為值的陣列
     */
    public function getActiveUsersByActivityType(StatisticsPeriod $period): array;

    /**
     * 取得最活躍使用者排行榜.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 取得數量
     * @param string $metric 排序指標（posts, views, logins, activity_score）
     * @return array<int, array{user_id: int, username: string, metric_value: int, rank: int}> 活躍使用者陣列
     */
    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10, string $metric = 'posts'): array;

    /**
     * 取得使用者登入活動分析.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{
     *   total_logins: int,
     *   unique_users: int,
     *   avg_logins_per_user: float,
     *   peak_hour: int,
     *   login_frequency_distribution: array<string, int>
     * }
     */
    public function getUserLoginActivity(StatisticsPeriod $period): array;

    /**
     * 取得使用者註冊趨勢.
     *
     * @param StatisticsPeriod $currentPeriod 當前週期
     * @param StatisticsPeriod $previousPeriod 上一週期
     * @return array{current: int, previous: int, growth_rate: float, growth_count: int}
     */
    public function getUserRegistrationTrend(StatisticsPeriod $currentPeriod, StatisticsPeriod $previousPeriod): array;

    /**
     * 取得使用者活動時間分布.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param string $groupBy 分組方式（hour, day, week）
     * @return array<string, int> 時間段為鍵，活動使用者數為值的陣列
     */
    public function getUserActivityTimeDistribution(StatisticsPeriod $period, string $groupBy = 'hour'): array;

    /**
     * 取得使用者留存率分析.
     *
     * @param StatisticsPeriod $cohortPeriod 世代週期（註冊時間）
     * @param int $daysAfterRegistration 註冊後天數
     * @return array{
     *   cohort_size: int,
     *   retained_users: int,
     *   retention_rate: float,
     *   churn_rate: float
     * }
     */
    public function getUserRetentionAnalysis(StatisticsPeriod $cohortPeriod, int $daysAfterRegistration): array;

    /**
     * 根據使用者角色統計數量.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array<string, int> 角色為鍵，數量為值的陣列
     */
    public function getUsersCountByRole(StatisticsPeriod $period): array;

    /**
     * 取得使用者參與度統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{
     *   high_engagement: int,
     *   medium_engagement: int,
     *   low_engagement: int,
     *   inactive: int,
     *   avg_engagement_score: float
     * }
     */
    public function getUserEngagementStatistics(StatisticsPeriod $period): array;

    /**
     * 取得使用者來源分析.
     *
     * @param StatisticsPeriod $period 統計週期（註冊時間範圍）
     * @return array<string, int> 來源管道為鍵，註冊數為值的陣列
     */
    public function getUserRegistrationSources(StatisticsPeriod $period): array;

    /**
     * 取得使用者地理分布統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 取得前 N 個地區
     * @return array<int, array{location: string, users_count: int, percentage: float}> 地理分布陣列
     */
    public function getUserGeographicalDistribution(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 檢查指定週期是否有使用者統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return bool 是否有資料
     */
    public function hasDataForPeriod(StatisticsPeriod $period): bool;

    /**
     * 取得指定週期的使用者活動摘要
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{
     *   total_users: int,
     *   active_users: int,
     *   new_users: int,
     *   returning_users: int,
     *   user_activity_rate: float,
     *   top_active_hours: array<int>
     * }
     */
    public function getUserActivitySummary(StatisticsPeriod $period): array;
}
