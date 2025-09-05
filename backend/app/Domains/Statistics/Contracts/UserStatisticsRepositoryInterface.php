<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;

/**
 * 使用者統計資料存取介面
 * 
 * 定義使用者相關統計資料的查詢方法。
 * 專注於使用者註冊、活躍度、行為模式等統計資料的存取。
 */
interface UserStatisticsRepositoryInterface
{
    /**
     * 計算指定週期內的新註冊使用者數量
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return int 新註冊使用者數量
     */
    public function countNewUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的活躍使用者數量
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return int 活躍使用者數量
     */
    public function countActiveUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的總使用者數量（截至週期結束）
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return int 總使用者數量
     */
    public function countTotalUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 取得使用者註冊趨勢資料（按日期分組）
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, new_users: int}> 註冊趨勢資料
     */
    public function getUserRegistrationTrends(StatisticsPeriod $period): array;

    /**
     * 取得使用者活躍度統計
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{user_id: int, email: string, name: string, posts_count: int, activities_count: int, last_activity: string}> 活躍度統計
     */
    public function getUserActivityStats(StatisticsPeriod $period): array;

    /**
     * 取得最活躍的使用者列表
     * 
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 限制回傳數量，預設為 10
     * @return array<array{user_id: int, email: string, name: string, posts_count: int, activities_count: int, total_views: int, last_activity: string}> 最活躍使用者
     */
    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 取得使用者行為模式分析
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array{hourly_activity: array, weekly_activity: array} 行為模式資料
     */
    public function getUserBehaviorPatterns(StatisticsPeriod $period): array;

    /**
     * 計算使用者留存率
     * 
     * @param StatisticsPeriod $period 統計週期（註冊期間）
     * @param int $retentionDays 留存期間天數，預設為 30 天
     * @return array{total_new_users: int, retained_users: int, retention_rate: float, retention_period_days: int} 留存率資料
     */
    public function getUserRetentionRate(StatisticsPeriod $period, int $retentionDays = 30): array;

    /**
     * 取得使用者分群統計
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{user_segment: string, user_count: int, avg_posts: float, segment_total_views: int, percentage: float}> 分群統計
     */
    public function getUserSegmentationStats(StatisticsPeriod $period): array;

    /**
     * 計算使用者流失率
     * 
     * @param StatisticsPeriod $period 統計週期
     * @param int $inactivityDays 無活動天數閾值，預設為 30 天
     * @return array{total_users: int, churned_users: int, active_users: int, churn_rate: float, retention_rate: float, inactivity_threshold_days: int} 流失率資料
     */
    public function getUserChurnRate(StatisticsPeriod $period, int $inactivityDays = 30): array;

    /**
     * 取得新使用者首次活動分析
     * 
     * @param StatisticsPeriod $period 統計週期（註冊期間）
     * @return array{new_users_with_activity: array, total_new_users_with_activity: int, avg_days_to_first_activity: float} 首次活動分析
     */
    public function getNewUserFirstActivityAnalysis(StatisticsPeriod $period): array;

    /**
     * 取得使用者互動網路分析
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_interactions: int, unique_interacting_users: int, avg_interactions_per_user: float, most_connected_users: array} 互動網路分析
     */
    public function getUserInteractionNetworkStats(StatisticsPeriod $period): array;

    /**
     * 計算使用者生命週期價值分析
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array{user_lifetime_values: array, total_analyzed_users: int, avg_lifetime_value: float} 生命週期價值分析
     */
    public function getUserLifetimeValueAnalysis(StatisticsPeriod $period): array;

    /**
     * 取得使用者地理分布統計
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{region: string, user_count: int, percentage: float}> 地理分布統計
     */
    public function getUserGeographicDistribution(StatisticsPeriod $period): array;

    /**
     * 計算使用者參與度評分
     * 
     * @param StatisticsPeriod $period 統計週期
     * @param int $limit 限制回傳數量，預設為 100
     * @return array<array{user_id: int, email: string, name: string, posts_count: int, total_views: int, activities_count: int, active_days: int, engagement_score: float}> 參與度評分
     */
    public function getUserEngagementScores(StatisticsPeriod $period, int $limit = 100): array;

    /**
     * 取得使用者活動時間分布
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{hour: int, activity_count: int, user_count: int}> 活動時間分布
     */
    public function getUserActivityTimeDistribution(StatisticsPeriod $period): array;

    /**
     * 計算使用者活躍度變化趨勢
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{date: string, active_users: int, new_users: int, returning_users: int}> 活躍度趨勢
     */
    public function getUserActivityTrends(StatisticsPeriod $period): array;

    /**
     * 取得使用者裝置類型統計
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return array<array{device_type: string, user_count: int, session_count: int, percentage: float}> 裝置類型統計
     */
    public function getUserDeviceTypeStats(StatisticsPeriod $period): array;

    /**
     * 檢查指定週期是否有使用者活動資料
     * 
     * @param StatisticsPeriod $period 統計週期
     * @return bool 有資料時回傳 true，否則回傳 false
     */
    public function hasUserActivityInPeriod(StatisticsPeriod $period): bool;

    /**
     * 計算總使用者數（截至指定日期）
     * 
     * @param DateTimeInterface $date 截止日期
     * @return int 總使用者數
     */
    public function getTotalUsersAsOfDate(DateTimeInterface $date): int;
}
