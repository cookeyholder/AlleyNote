<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Database\DatabaseConnection;
use DateTime;
use PDO;
use RuntimeException;
use Throwable;

/**
 * 使用者統計資料存取實作類別.
 *
 * 專門處理使用者相關的統計資料存取與分析，
 * 使用原生 SQL 提供高效能的使用者統計查詢。
 *
 * 設計原則：
 * - 使用原生 SQL 進行複雜統計查詢
 * - 專注於使用者行為分析
 * - 提供高效能的資料聚合
 * - 完整的錯誤處理和類型安全
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-05
 */
final class UserStatisticsRepository implements UserStatisticsRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    /**
     * 計算指定週期內的新註冊使用者數量.
     */
    public function countNewUsersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException("計算新註冊使用者數量時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算指定週期內的活躍使用者數量.
     */
    public function countActiveUsersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(DISTINCT user_id)
                FROM (
                    SELECT user_id FROM posts
                    WHERE created_at >= ? AND created_at <= ? AND deleted_at IS NULL
                    UNION
                    SELECT created_by as user_id FROM activity_logs
                    WHERE created_at >= ? AND created_at <= ?
                ) as active_users
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException("計算活躍使用者數量時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算指定週期內的總使用者數量.
     */
    public function countTotalUsersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at <= ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException("計算總使用者數量時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者註冊趨勢資料（按日期分組）.
     */
    public function getUserRegistrationTrends(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as new_users
                FROM users
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            throw new RuntimeException("取得使用者註冊趨勢時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者活躍度統計.
     */
    public function getUserActivityStats(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    u.id as user_id,
                    u.email,
                    u.name,
                    COUNT(DISTINCT p.id) as posts_count,
                    COUNT(DISTINCT al.id) as activities_count,
                    MAX(COALESCE(p.created_at, al.created_at)) as last_activity
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                    AND p.created_at >= ?
                    AND p.created_at <= ?
                    AND p.deleted_at IS NULL
                LEFT JOIN activity_logs al ON u.id = al.created_by
                    AND al.created_at >= ?
                    AND al.created_at <= ?
                WHERE u.created_at <= ?
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.email, u.name
                HAVING (posts_count > 0 OR activities_count > 0)
                ORDER BY (posts_count + activities_count) DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();

            // 格式化結果
            foreach ($results as &$result) {
                $result['posts_count'] = (int) $result['posts_count'];
                $result['activities_count'] = (int) $result['activities_count'];
                $result['total_activities'] = $result['posts_count'] + $result['activities_count'];
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("取得使用者活躍度統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得最活躍的使用者列表.
     */
    public function getMostActiveUsers(
        StatisticsPeriod $period,
        int $limit = 10,
    ): array {
        try {
            $sql = '
                SELECT
                    u.id as user_id,
                    u.email,
                    u.name,
                    COUNT(DISTINCT p.id) as posts_count,
                    COUNT(DISTINCT al.id) as activities_count,
                    COALESCE(SUM(p.views), 0) as total_views,
                    MAX(COALESCE(p.created_at, al.created_at)) as last_activity
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                    AND p.created_at >= ?
                    AND p.created_at <= ?
                    AND p.deleted_at IS NULL
                LEFT JOIN activity_logs al ON u.id = al.created_by
                    AND al.created_at >= ?
                    AND al.created_at <= ?
                WHERE u.deleted_at IS NULL
                GROUP BY u.id, u.email, u.name
                HAVING (posts_count > 0 OR activities_count > 0)
                ORDER BY (posts_count + activities_count) DESC, total_views DESC
                LIMIT ?
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $limit,
            ]);

            $results = $stmt->fetchAll();

            // 格式化結果
            foreach ($results as &$result) {
                $result['posts_count'] = (int) $result['posts_count'];
                $result['activities_count'] = (int) $result['activities_count'];
                $result['total_views'] = (int) $result['total_views'];
                $result['total_activities'] = $result['posts_count'] + $result['activities_count'];
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("取得最活躍使用者列表時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者行為模式分析.
     */
    public function getUserBehaviorPatterns(StatisticsPeriod $period): array
    {
        try {
            // 分析使用者活動時間分布
            $hourlyActivitySql = "
                SELECT
                    strftime('%H', created_at) as hour,
                    COUNT(*) as activity_count
                FROM (
                    SELECT created_at FROM posts
                    WHERE created_at >= ? AND created_at <= ? AND deleted_at IS NULL
                    UNION ALL
                    SELECT created_at FROM activity_logs
                    WHERE created_at >= ? AND created_at <= ?
                ) as all_activities
                GROUP BY strftime('%H', created_at)
                ORDER BY hour
            ";

            $stmt = $this->pdo->prepare($hourlyActivitySql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $hourlyActivity = $stmt->fetchAll();

            // 分析使用者活動週期分布
            $weeklyActivitySql = "
                SELECT
                    strftime('%w', created_at) as day_of_week,
                    COUNT(*) as activity_count
                FROM (
                    SELECT created_at FROM posts
                    WHERE created_at >= ? AND created_at <= ? AND deleted_at IS NULL
                    UNION ALL
                    SELECT created_at FROM activity_logs
                    WHERE created_at >= ? AND created_at <= ?
                ) as all_activities
                GROUP BY strftime('%w', created_at)
                ORDER BY day_of_week
            ";

            $stmt = $this->pdo->prepare($weeklyActivitySql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $weeklyActivity = $stmt->fetchAll();

            // 轉換星期數字為中文
            $dayNames = ['日', '一', '二', '三', '四', '五', '六'];
            foreach ($weeklyActivity as &$activity) {
                $activity['day_name'] = $dayNames[(int) $activity['day_of_week']];
                $activity['activity_count'] = (int) $activity['activity_count'];
            }

            // 格式化時間活動資料
            foreach ($hourlyActivity as &$activity) {
                $activity['hour'] = (int) $activity['hour'];
                $activity['activity_count'] = (int) $activity['activity_count'];
            }

            return [
                'hourly_activity' => $hourlyActivity,
                'weekly_activity' => $weeklyActivity,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("分析使用者行為模式時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算使用者留存率.
     */
    public function getUserRetentionRate(
        StatisticsPeriod $period,
        int $retentionDays = 30,
    ): array {
        try {
            // 取得期間內註冊的使用者
            $newUsersSql = '
                SELECT id, created_at
                FROM users
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($newUsersSql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $newUsers = $stmt->fetchAll();
            $totalNewUsers = count($newUsers);

            if ($totalNewUsers === 0) {
                return [
                    'total_new_users' => 0,
                    'retained_users' => 0,
                    'retention_rate' => 0.0,
                    'retention_period_days' => $retentionDays,
                ];
            }

            $retainedCount = 0;

            // 檢查每個使用者是否在留存期間內有活動
            foreach ($newUsers as $user) {
                $userRegistrationDate = new DateTime($user['created_at']);
                $retentionEndDate = $userRegistrationDate->modify("+{$retentionDays} days");

                $activityCheckSql = '
                    SELECT COUNT(*)
                    FROM (
                        SELECT user_id FROM posts
                        WHERE user_id = ?
                            AND created_at > ?
                            AND created_at <= ?
                            AND deleted_at IS NULL
                        UNION
                        SELECT created_by as user_id FROM activity_logs
                        WHERE created_by = ?
                            AND created_at > ?
                            AND created_at <= ?
                    ) as user_activities
                ';

                $activityStmt = $this->pdo->prepare($activityCheckSql);
                $activityStmt->execute([
                    $user['id'],
                    $user['created_at'],
                    $retentionEndDate->format('Y-m-d H:i:s'),
                    $user['id'],
                    $user['created_at'],
                    $retentionEndDate->format('Y-m-d H:i:s'),
                ]);

                $activityCount = (int) $activityStmt->fetchColumn();
                if ($activityCount > 0) {
                    $retainedCount++;
                }
            }

            $retentionRate = round(($retainedCount / $totalNewUsers) * 100, 2);

            return [
                'total_new_users' => $totalNewUsers,
                'retained_users' => $retainedCount,
                'retention_rate' => $retentionRate,
                'retention_period_days' => $retentionDays,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算使用者留存率時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者分群統計.
     */
    public function getUserSegmentationStats(StatisticsPeriod $period): array
    {
        try {
            $sql = "
                SELECT
                    CASE
                        WHEN posts_count = 0 THEN '潛水者'
                        WHEN posts_count BETWEEN 1 AND 5 THEN '輕度使用者'
                        WHEN posts_count BETWEEN 6 AND 20 THEN '中度使用者'
                        WHEN posts_count BETWEEN 21 AND 50 THEN '重度使用者'
                        ELSE '超級使用者'
                    END as user_segment,
                    COUNT(*) as user_count,
                    COALESCE(AVG(posts_count), 0) as avg_posts,
                    COALESCE(SUM(total_views), 0) as segment_total_views
                FROM (
                    SELECT
                        u.id,
                        COUNT(p.id) as posts_count,
                        COALESCE(SUM(p.views), 0) as total_views
                    FROM users u
                    LEFT JOIN posts p ON u.id = p.user_id
                        AND p.created_at >= ?
                        AND p.created_at <= ?
                        AND p.deleted_at IS NULL
                    WHERE u.created_at <= ?
                        AND u.deleted_at IS NULL
                    GROUP BY u.id
                ) as user_stats
                GROUP BY
                    CASE
                        WHEN posts_count = 0 THEN '潛水者'
                        WHEN posts_count BETWEEN 1 AND 5 THEN '輕度使用者'
                        WHEN posts_count BETWEEN 6 AND 20 THEN '中度使用者'
                        WHEN posts_count BETWEEN 21 AND 50 THEN '重度使用者'
                        ELSE '超級使用者'
                    END
                ORDER BY
                    CASE
                        WHEN posts_count = 0 THEN 1
                        WHEN posts_count BETWEEN 1 AND 5 THEN 2
                        WHEN posts_count BETWEEN 6 AND 20 THEN 3
                        WHEN posts_count BETWEEN 21 AND 50 THEN 4
                        ELSE 5
                    END
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();
            $totalUsers = array_sum(array_column($results, 'user_count'));

            // 計算百分比
            foreach ($results as &$result) {
                $result['user_count'] = (int) $result['user_count'];
                $result['avg_posts'] = round((float) $result['avg_posts'], 2);
                $result['segment_total_views'] = (int) $result['segment_total_views'];
                $result['percentage'] = $totalUsers > 0
                    ? round(($result['user_count'] / $totalUsers) * 100, 2)
                    : 0;
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("取得使用者分群統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算使用者流失率.
     */
    public function getUserChurnRate(
        StatisticsPeriod $period,
        int $inactivityDays = 30,
    ): array {
        try {
            $inactivityCutoff = $period->endDate->modify("-{$inactivityDays} days");

            // 計算在此期間前已註冊的使用者總數
            $totalUsersSql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at < ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($totalUsersSql);
            $stmt->execute([$inactivityCutoff->format('Y-m-d H:i:s')]);
            $totalUsers = (int) $stmt->fetchColumn();

            if ($totalUsers === 0) {
                return [
                    'total_users' => 0,
                    'churned_users' => 0,
                    'churn_rate' => 0.0,
                    'inactivity_threshold_days' => $inactivityDays,
                ];
            }

            // 計算在指定時間內沒有任何活動的使用者數量
            $churnedUsersSql = '
                SELECT COUNT(DISTINCT u.id)
                FROM users u
                WHERE u.created_at < ?
                    AND u.deleted_at IS NULL
                    AND u.id NOT IN (
                        SELECT DISTINCT user_id
                        FROM posts
                        WHERE created_at >= ?
                            AND deleted_at IS NULL
                            AND user_id IS NOT NULL
                        UNION
                        SELECT DISTINCT created_by
                        FROM activity_logs
                        WHERE created_at >= ?
                            AND created_by IS NOT NULL
                    )
            ';

            $stmt = $this->pdo->prepare($churnedUsersSql);
            $stmt->execute([
                $inactivityCutoff->format('Y-m-d H:i:s'),
                $inactivityCutoff->format('Y-m-d H:i:s'),
                $inactivityCutoff->format('Y-m-d H:i:s'),
            ]);

            $churnedUsers = (int) $stmt->fetchColumn();
            $churnRate = round(($churnedUsers / $totalUsers) * 100, 2);

            return [
                'total_users' => $totalUsers,
                'churned_users' => $churnedUsers,
                'active_users' => $totalUsers - $churnedUsers,
                'churn_rate' => $churnRate,
                'retention_rate' => 100 - $churnRate,
                'inactivity_threshold_days' => $inactivityDays,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算使用者流失率時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得新使用者首次活動分析.
     */
    public function getNewUserFirstActivityAnalysis(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    u.id as user_id,
                    u.email,
                    u.name,
                    u.created_at as registration_date,
                    MIN(COALESCE(p.created_at, al.created_at)) as first_activity_date,
                    COUNT(DISTINCT p.id) as posts_count,
                    COUNT(DISTINCT al.id) as activities_count
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id AND p.deleted_at IS NULL
                LEFT JOIN activity_logs al ON u.id = al.created_by
                WHERE u.created_at >= ?
                    AND u.created_at <= ?
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.email, u.name, u.created_at
                HAVING first_activity_date IS NOT NULL
                ORDER BY u.created_at DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();

            // 計算首次活動間隔時間
            foreach ($results as &$result) {
                $registrationDate = new DateTime($result['registration_date']);
                $firstActivityDate = new DateTime($result['first_activity_date']);
                $interval = $registrationDate->diff($firstActivityDate);

                $result['days_to_first_activity'] = $interval->days;
                $result['posts_count'] = (int) $result['posts_count'];
                $result['activities_count'] = (int) $result['activities_count'];
            }

            // 計算統計資料
            $totalNewUsers = count($results);
            $avgDaysToFirstActivity = $totalNewUsers > 0
                ? round(array_sum(array_column($results, 'days_to_first_activity')) / $totalNewUsers, 2)
                : 0;

            return [
                'new_users_with_activity' => $results,
                'total_new_users_with_activity' => $totalNewUsers,
                'avg_days_to_first_activity' => $avgDaysToFirstActivity,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("分析新使用者首次活動時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者互動網路分析.
     */
    public function getUserInteractionNetworkStats(StatisticsPeriod $period): array
    {
        // 由於沒有使用者互動系統（如留言、點讚等），暫時回傳空資料
        return [
            'total_interactions' => 0,
            'unique_interacting_users' => 0,
            'avg_interactions_per_user' => 0.0,
            'most_connected_users' => [],
        ];
    }

    /**
     * 計算使用者生命週期價值分析.
     */
    public function getUserLifetimeValueAnalysis(StatisticsPeriod $period): array
    {
        try {
            $sql = "
                SELECT
                    u.id as user_id,
                    u.email,
                    u.name,
                    u.created_at as registration_date,
                    COUNT(DISTINCT p.id) as total_posts,
                    COALESCE(SUM(p.views), 0) as total_views_generated,
                    COUNT(DISTINCT al.id) as total_activities,
                    JULIANDAY('now') - JULIANDAY(u.created_at) as user_age_days
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id AND p.deleted_at IS NULL
                LEFT JOIN activity_logs al ON u.id = al.created_by
                WHERE u.created_at <= ?
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.email, u.name, u.created_at
                HAVING user_age_days >= 0
                ORDER BY total_views_generated DESC, total_posts DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$period->endDate->format('Y-m-d H:i:s')]);

            $results = $stmt->fetchAll();

            // 計算生命週期價值指標
            foreach ($results as &$result) {
                $result['total_posts'] = (int) $result['total_posts'];
                $result['total_views_generated'] = (int) $result['total_views_generated'];
                $result['total_activities'] = (int) $result['total_activities'];
                $result['user_age_days'] = (int) $result['user_age_days'];

                // 計算平均每日價值
                $result['avg_posts_per_day'] = $result['user_age_days'] > 0
                    ? round($result['total_posts'] / $result['user_age_days'], 3)
                    : 0;

                $result['avg_views_per_day'] = $result['user_age_days'] > 0
                    ? round($result['total_views_generated'] / $result['user_age_days'], 2)
                    : 0;

                // 計算價值分數（基於內容產出和觀看數）
                $result['value_score'] = ($result['total_posts'] * 10) + ($result['total_views_generated'] * 0.1);
            }

            // 計算整體統計
            $totalUsers = count($results);
            $avgLifetimeValue = $totalUsers > 0
                ? round(array_sum(array_column($results, 'value_score')) / $totalUsers, 2)
                : 0;

            return [
                'user_lifetime_values' => array_slice($results, 0, 50), // 前50名
                'total_analyzed_users' => $totalUsers,
                'avg_lifetime_value' => $avgLifetimeValue,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("分析使用者生命週期價值時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得使用者地理分布統計.
     */
    public function getUserGeographicDistribution(StatisticsPeriod $period): array
    {
        // 由於沒有地理位置資料，暫時回傳空陣列
        return [];
    }

    /**
     * 計算使用者參與度評分.
     */
    public function getUserEngagementScores(
        StatisticsPeriod $period,
        int $limit = 100,
    ): array {
        try {
            $sql = '
                SELECT
                    u.id as user_id,
                    u.email,
                    u.name,
                    u.created_at as registration_date,
                    COUNT(DISTINCT p.id) as posts_count,
                    COALESCE(SUM(p.views), 0) as total_views,
                    COUNT(DISTINCT al.id) as activities_count,
                    COUNT(DISTINCT DATE(COALESCE(p.created_at, al.created_at))) as active_days
                FROM users u
                LEFT JOIN posts p ON u.id = p.user_id
                    AND p.created_at >= ?
                    AND p.created_at <= ?
                    AND p.deleted_at IS NULL
                LEFT JOIN activity_logs al ON u.id = al.created_by
                    AND al.created_at >= ?
                    AND al.created_at <= ?
                WHERE u.deleted_at IS NULL
                GROUP BY u.id, u.email, u.name, u.created_at
                HAVING (posts_count > 0 OR activities_count > 0)
                ORDER BY (posts_count + activities_count + active_days) DESC
                LIMIT ?
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $limit,
            ]);

            $results = $stmt->fetchAll();

            // 計算參與度評分
            foreach ($results as &$result) {
                $result['posts_count'] = (int) $result['posts_count'];
                $result['total_views'] = (int) $result['total_views'];
                $result['activities_count'] = (int) $result['activities_count'];
                $result['active_days'] = (int) $result['active_days'];

                // 參與度評分計算公式：
                // 文章數 * 20 + 活動數 * 5 + 活躍天數 * 10 + 觀看數 * 0.01
                $result['engagement_score'] = round(
                    ($result['posts_count'] * 20)
                    + ($result['activities_count'] * 5)
                    + ($result['active_days'] * 10)
                    + ($result['total_views'] * 0.01),
                    2,
                );
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("計算使用者參與度評分時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }
}
