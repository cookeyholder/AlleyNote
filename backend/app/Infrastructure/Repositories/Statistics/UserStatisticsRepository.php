<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * 使用者統計資料存取實作類別.
 *
 * 實作使用者相關統計資料的查詢功能，提供高效能的原生 SQL 查詢。
 * 支援使用者註冊、活躍度、行為模式等複雜統計分析。
 */
final readonly class UserStatisticsRepository implements UserStatisticsRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * 計算指定週期內的新註冊使用者數量.
     */
    public function countNewUsersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢新註冊使用者數量: ' . $e->getMessage(), 0, $e);
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
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢活躍使用者數量: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算截至指定日期的總使用者數量.
     */
    public function countTotalUsersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at <= :end_date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['end_date' => $period->endDate->format('Y-m-d H:i:s')]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢總使用者數量: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者註冊趨勢資料.
     *
     * @return array<string,int>
     */
    public function getUserRegistrationTrends(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as registration_count
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{date: string, registration_count: int}> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $date = (string) $r['date'];
                $map[$date] = (int) $r['registration_count'];
            }

            return $map;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者註冊趨勢: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算使用者活躍度分布.
     *
     * @return array<string, int>
     */
    public function getUserActivityDistribution(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    CASE
                        WHEN activity_count = 0 THEN "inactive"
                        WHEN activity_count <= 5 THEN "low"
                        WHEN activity_count <= 20 THEN "medium"
                        ELSE "high"
                    END as activity_level,
                    COUNT(*) as user_count
                FROM (
                    SELECT
                        u.id,
                        COUNT(ua.id) as activity_count
                    FROM users u
                    LEFT JOIN user_activities ua ON u.id = ua.user_id
                        AND ua.created_at >= :start_date
                        AND ua.created_at <= :end_date
                    WHERE u.created_at <= :end_date
                        AND u.deleted_at IS NULL
                    GROUP BY u.id
                ) user_activity_summary
                GROUP BY activity_level
                ORDER BY
                    CASE activity_level
                        WHEN "inactive" THEN 1
                        WHEN "low" THEN 2
                        WHEN "medium" THEN 3
                        WHEN "high" THEN 4
                    END
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{activity_level: string, user_count: int}> $results */
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $distribution = [];

            foreach ($results as $result) {
                $level = (string) $result['activity_level'];
                $distribution[$level] = (int) $result['user_count'];
            }

            return $distribution;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者活躍度分布: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得每日活躍使用者統計.
     *
     * @return array<string, int>
     */
    public function getDailyActiveUsers(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(ua.created_at) as activity_date,
                    COUNT(DISTINCT ua.user_id) as active_users
                FROM user_activities ua
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                GROUP BY DATE(ua.created_at)
                ORDER BY activity_date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{activity_date: string, active_users: int}> $results */
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dailyActive = [];

            foreach ($results as $result) {
                $date = (string) $result['activity_date'];
                $dailyActive[$date] = (int) $result['active_users'];
            }

            return $dailyActive;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢每日活躍使用者: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者活躍度統計.
     */
    public function getUserActivityStats(StatisticsPeriod $period): array
    {
        return [
            'active_users' => $this->countActiveUsersByPeriod($period),
            'activity_distribution' => $this->getUserActivityDistribution($period),
            'daily_active_users' => $this->getDailyActiveUsers($period),
        ];
    }

    /**
     * 取得最活躍的使用者列表.
     */
    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10): array
    {
        try {
            $sql = '
                SELECT
                    u.id,
                    u.username,
                    u.email,
                    COUNT(ua.id) as activity_count,
                    MAX(ua.created_at) as last_activity
                FROM users u
                INNER JOIN user_activities ua ON u.id = ua.user_id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.username, u.email
                ORDER BY activity_count DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                'limit' => $limit,
            ]);

            /** @var array<int, array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 對最活躍使用者回傳清單（保留數字索引以符合介面宣告）
            $processed = [];
            foreach ($rows as $r) {
                $processed[] = [
                    'id' => isset($r['id']) ? (int) $r['id'] : 0,
                    'username' => isset($r['username']) ? (string) $r['username'] : '',
                    'email' => isset($r['email']) ? (string) $r['email'] : '',
                    'activity_count' => isset($r['activity_count']) ? (int) $r['activity_count'] : 0,
                    'last_activity' => isset($r['last_activity']) ? (string) $r['last_activity'] : '',
                ];
            }

            return $processed;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最活躍使用者: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者行為模式分析.
     */
    public function getUserBehaviorPatterns(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    HOUR(ua.created_at) as hour_of_day,
                    DAYOFWEEK(ua.created_at) as day_of_week,
                    COUNT(*) as activity_count,
                    COUNT(DISTINCT ua.user_id) as unique_users
                FROM user_activities ua
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                GROUP BY HOUR(ua.created_at), DAYOFWEEK(ua.created_at)
                ORDER BY day_of_week, hour_of_day
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{hour_of_day: int, day_of_week: int, activity_count: int, unique_users: int}> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $key = (string) $r['day_of_week'] . ':' . (string) $r['hour_of_day'];
                $map[$key] = [
                    'hour_of_day' => (int) $r['hour_of_day'],
                    'day_of_week' => (int) $r['day_of_week'],
                    'activity_count' => (int) $r['activity_count'],
                    'unique_users' => (int) $r['unique_users'],
                ];
            }

            return $map;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者行為模式: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算使用者留存率.
     */
    public function getUserRetentionRate(StatisticsPeriod $period, int $retentionDays = 30): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(DISTINCT u.id) as total_new_users,
                    COUNT(DISTINCT CASE WHEN ua.user_id IS NOT NULL THEN u.id END) as retained_users
                FROM users u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                    AND ua.created_at >= DATE_ADD(u.created_at, INTERVAL 1 DAY)
                    AND ua.created_at <= DATE_ADD(u.created_at, INTERVAL :retention_days DAY)
                WHERE u.created_at >= :start_date
                    AND u.created_at <= :end_date
                    AND u.deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                'retention_days' => $retentionDays,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $totalNewUsers = (int) ($result['total_new_users'] ?? 0);
            $retainedUsers = (int) ($result['retained_users'] ?? 0);
            $retentionRate = $totalNewUsers > 0 ? ($retainedUsers / $totalNewUsers) * 100.0 : 0.0;

            return [
                'total_new_users' => $totalNewUsers,
                'retained_users' => $retainedUsers,
                'retention_rate' => round($retentionRate, 2),
                'retention_period_days' => $retentionDays,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算使用者留存率: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者分群統計.
     */
    public function getUserSegmentationStats(StatisticsPeriod $period): array
    {
        try {
            $activityDistribution = $this->getUserActivityDistribution($period);
            $totalUsers = array_sum($activityDistribution);

            return [
                'total_users' => $totalUsers,
                'activity_segments' => $activityDistribution,
                'activity_percentages' => array_map(
                    fn($count) => $totalUsers > 0 ? round(($count / $totalUsers) * 100, 2) : 0.0,
                    $activityDistribution,
                ),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者分群統計: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算使用者流失率.
     */
    public function getUserChurnRate(StatisticsPeriod $period, int $inactivityDays = 30): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(DISTINCT CASE WHEN ua.user_id IS NULL THEN u.id END) as churned_users,
                    COUNT(DISTINCT CASE WHEN ua.user_id IS NOT NULL THEN u.id END) as active_users
                FROM users u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                    AND ua.created_at >= DATE_SUB(:end_date, INTERVAL :inactivity_days DAY)
                    AND ua.created_at <= :end_date
                WHERE u.created_at <= DATE_SUB(:end_date, INTERVAL :inactivity_days DAY)
                    AND u.deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                'inactivity_days' => $inactivityDays,
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalUsers = (int) $result['total_users'];
            $churnedUsers = (int) $result['churned_users'];
            $activeUsers = (int) $result['active_users'];
            $churnRate = $totalUsers > 0 ? ($churnedUsers / $totalUsers) * 100 : 0.0;
            $retentionRate = 100.0 - $churnRate;

            return [
                'total_users' => $totalUsers,
                'churned_users' => $churnedUsers,
                'active_users' => $activeUsers,
                'churn_rate' => round($churnRate, 2),
                'retention_rate' => round($retentionRate, 2),
                'inactivity_threshold_days' => $inactivityDays,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算使用者流失率: ' . $e->getMessage(), 0, $e);
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
                    AVG(TIMESTAMPDIFF(HOUR, u.created_at, ua.first_activity)) as avg_hours_to_first_activity,
                    COUNT(CASE WHEN ua.first_activity <= DATE_ADD(u.created_at, INTERVAL 1 DAY) THEN 1 END) as activated_within_24h,
                    COUNT(CASE WHEN ua.first_activity <= DATE_ADD(u.created_at, INTERVAL 7 DAY) THEN 1 END) as activated_within_7d,
                    COUNT(u.id) as total_new_users,
                    COUNT(ua.first_activity) as users_with_activity
                FROM users u
                LEFT JOIN (
                    SELECT
                        user_id,
                        MIN(created_at) as first_activity
                    FROM user_activities
                    GROUP BY user_id
                ) ua ON u.id = ua.user_id
                WHERE u.created_at >= :start_date
                    AND u.created_at <= :end_date
                    AND u.deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $totalNewUsers = isset($result['total_new_users']) ? (int) $result['total_new_users'] : 0;
            $usersWithActivity = isset($result['users_with_activity']) ? (int) $result['users_with_activity'] : 0;
            $activationRate = $totalNewUsers > 0 ? ($usersWithActivity / $totalNewUsers) * 100.0 : 0.0;
            $avgHours = isset($result['avg_hours_to_first_activity']) && is_numeric($result['avg_hours_to_first_activity']) ? (float) $result['avg_hours_to_first_activity'] : null;
            $activated24 = isset($result['activated_within_24h']) ? (int) $result['activated_within_24h'] : 0;
            $activated7d = isset($result['activated_within_7d']) ? (int) $result['activated_within_7d'] : 0;

            return [
                'total_new_users' => $totalNewUsers,
                'users_with_activity' => $usersWithActivity,
                'activation_rate' => round($activationRate, 2),
                'avg_hours_to_first_activity' => $avgHours !== null ? round($avgHours, 2) : null,
                'activated_within_24h' => $activated24,
                'activated_within_7d' => $activated7d,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法分析新使用者首次活動: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者互動網路分析.
     */
    public function getUserInteractionNetworkStats(StatisticsPeriod $period): array
    {
        try {
            // 由於缺少具體的互動表結構，這裡提供一個基本實現
            $sql = '
                SELECT
                    COUNT(DISTINCT ua.user_id) as total_active_users,
                    COUNT(ua.id) as total_activities,
                    AVG(daily_activities.activity_count) as avg_activities_per_user
                FROM user_activities ua
                INNER JOIN (
                    SELECT
                        user_id,
                        COUNT(*) as activity_count
                    FROM user_activities
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY user_id
                ) daily_activities ON ua.user_id = daily_activities.user_id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $totalActiveUsers = isset($result['total_active_users']) ? (int) $result['total_active_users'] : 0;
            $totalActivities = isset($result['total_activities']) ? (int) $result['total_activities'] : 0;
            $avgActivities = is_numeric($result['avg_activities_per_user'] ?? null) ? (float) $result['avg_activities_per_user'] : 0.0;

            return [
                'total_active_users' => $totalActiveUsers,
                'total_activities' => $totalActivities,
                'avg_activities_per_user' => round($avgActivities, 2),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法分析使用者互動網路: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算使用者生命週期價值分析.
     */
    public function getUserLifetimeValueAnalysis(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(DISTINCT u.id) as total_users,
                    AVG(user_metrics.activity_count) as avg_lifetime_activities,
                    AVG(user_metrics.days_active) as avg_days_active,
                    MAX(user_metrics.activity_count) as max_lifetime_activities
                FROM users u
                INNER JOIN (
                    SELECT
                        ua.user_id,
                        COUNT(ua.id) as activity_count,
                        DATEDIFF(MAX(ua.created_at), MIN(ua.created_at)) + 1 as days_active
                    FROM user_activities ua
                    GROUP BY ua.user_id
                ) user_metrics ON u.id = user_metrics.user_id
                WHERE u.created_at >= :start_date
                    AND u.created_at <= :end_date
                    AND u.deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $totalUsers = isset($result['total_users']) ? (int) $result['total_users'] : 0;
            $avgLifetimeActivities = is_numeric($result['avg_lifetime_activities'] ?? null) ? (float) $result['avg_lifetime_activities'] : 0.0;
            $avgDaysActive = is_numeric($result['avg_days_active'] ?? null) ? (float) $result['avg_days_active'] : 0.0;
            $maxLifetimeActivities = isset($result['max_lifetime_activities']) ? (int) $result['max_lifetime_activities'] : 0;

            return [
                'total_users' => $totalUsers,
                'avg_lifetime_activities' => round($avgLifetimeActivities, 2),
                'avg_days_active' => round($avgDaysActive, 2),
                'max_lifetime_activities' => $maxLifetimeActivities,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法分析使用者生命週期價值: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者地理分布統計.
     */
    public function getUserGeographicDistribution(StatisticsPeriod $period): array
    {
        try {
            // 假設用戶表有 country 和 timezone 欄位
            $sql = '
                SELECT
                    COALESCE(u.country, "Unknown") as country,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(ua.id) as total_activities
                FROM users u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                    AND ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                WHERE u.created_at <= :end_date
                    AND u.deleted_at IS NULL
                GROUP BY COALESCE(u.country, "Unknown")
                ORDER BY user_count DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{country: string, user_count: int, total_activities: int}> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $country = (string) $r['country'];
                $map[$country] = [
                    'user_count' => (int) $r['user_count'],
                    'total_activities' => (int) $r['total_activities'],
                ];
            }

            return $map;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者地理分布: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算使用者參與度評分.
     */
    public function getUserEngagementScores(StatisticsPeriod $period, int $limit = 100): array
    {
        try {
            $sql = '
                SELECT
                    u.id,
                    u.username,
                    COUNT(ua.id) as activity_count,
                    DATEDIFF(MAX(ua.created_at), MIN(ua.created_at)) + 1 as active_days,
                    COUNT(DISTINCT DATE(ua.created_at)) as unique_active_days,
                    (COUNT(ua.id) * 0.6 + COUNT(DISTINCT DATE(ua.created_at)) * 0.4) as engagement_score
                FROM users u
                INNER JOIN user_activities ua ON u.id = ua.user_id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.username
                ORDER BY engagement_score DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                'limit' => $limit,
            ]);

            /** @var array<int, array{ id: int, username: string, activity_count: int, active_days: int, unique_active_days: int, engagement_score: float }> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processed = [];
            foreach ($rows as $r) {
                $processed[] = [
                    'id' => (int) $r['id'],
                    'username' => (string) $r['username'],
                    'activity_count' => (int) $r['activity_count'],
                    'active_days' => (int) $r['active_days'],
                    'unique_active_days' => (int) $r['unique_active_days'],
                    'engagement_score' => (float) $r['engagement_score'],
                ];
            }

            return $processed;
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算使用者參與度評分: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者活動時間分布.
     */
    public function getUserActivityTimeDistribution(StatisticsPeriod $period): array
    {
        return $this->getUserBehaviorPatterns($period);
    }

    /**
     * 計算使用者活躍度變化趨勢.
     */
    public function getUserActivityTrends(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(ua.created_at) as activity_date,
                    COUNT(ua.id) as total_activities,
                    COUNT(DISTINCT ua.user_id) as unique_active_users,
                    AVG(daily_user_activities.activity_count) as avg_activities_per_user
                FROM user_activities ua
                INNER JOIN (
                    SELECT
                        DATE(created_at) as date,
                        user_id,
                        COUNT(*) as activity_count
                    FROM user_activities
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY DATE(created_at), user_id
                ) daily_user_activities ON DATE(ua.created_at) = daily_user_activities.date
                    AND ua.user_id = daily_user_activities.user_id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                GROUP BY DATE(ua.created_at)
                ORDER BY activity_date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{activity_date: string, total_activities: int, unique_active_users: int, avg_activities_per_user: float}> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $date = (string) $r['activity_date'];
                $map[$date] = [
                    'total_activities' => (int) $r['total_activities'],
                    'unique_active_users' => (int) $r['unique_active_users'],
                    'avg_activities_per_user' => (float) $r['avg_activities_per_user'],
                ];
            }

            return $map;
        } catch (PDOException $e) {
            throw new RuntimeException('無法分析使用者活躍度趨勢: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者裝置類型統計.
     */
    public function getUserDeviceTypeStats(StatisticsPeriod $period): array
    {
        try {
            // 假設有裝置資訊表或在活動表中有裝置資訊
            $sql = '
                SELECT
                    "Unknown" as device_type,
                    COUNT(DISTINCT ua.user_id) as user_count,
                    COUNT(ua.id) as activity_count
                FROM user_activities ua
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                GROUP BY device_type
                ORDER BY user_count DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<int, array{device_type: string, user_count: int, activity_count: int}> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $r) {
                $device = (string) $r['device_type'];
                $map[$device] = [
                    'user_count' => (int) $r['user_count'],
                    'activity_count' => (int) $r['activity_count'],
                ];
            }

            return $map;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢使用者裝置類型統計: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查指定週期是否有使用者活動資料.
     */
    public function hasUserActivityInPeriod(StatisticsPeriod $period): bool
    {
        try {
            $sql = '
                SELECT COUNT(*) > 0 as has_activity
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法檢查使用者活動資料: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算總使用者數（截至指定日期）.
     */
    public function getTotalUsersAsOfDate(DateTimeInterface $date): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at <= :date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['date' => $date->format('Y-m-d H:i:s')]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢總使用者數: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得最活躍的使用者清單.
     */
    public function getTopActiveUsers(StatisticsPeriod $period, int $limit = 10): array
    {
        return $this->getMostActiveUsers($period, $limit);
    }

    /**
     * 取得使用者行為分析資料.
     */
    public function getUserBehaviorAnalysis(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    AVG(session_duration) as average_session_duration,
                    (COUNT(CASE WHEN activity_count = 1 THEN 1 END) * 100.0 / COUNT(*)) as bounce_rate,
                    AVG(activity_count) as page_views_per_session,
                    10.0 as conversion_rate -- 暫時固定值，需要根據實際轉換定義調整
                FROM (
                    SELECT
                        ua.user_id,
                        DATE(ua.created_at) as session_date,
                        COUNT(ua.id) as activity_count,
                        TIMESTAMPDIFF(MINUTE, MIN(ua.created_at), MAX(ua.created_at)) as session_duration
                    FROM user_activities ua
                    WHERE ua.created_at >= :start_date
                        AND ua.created_at <= :end_date
                    GROUP BY ua.user_id, DATE(ua.created_at)
                ) user_sessions
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $avgSession = is_numeric($result['average_session_duration'] ?? null) ? (float) $result['average_session_duration'] : 0.0;
            $bounce = is_numeric($result['bounce_rate'] ?? null) ? (float) $result['bounce_rate'] : 0.0;
            $pageViews = is_numeric($result['page_views_per_session'] ?? null) ? (float) $result['page_views_per_session'] : 0.0;
            $conversion = is_numeric($result['conversion_rate'] ?? null) ? (float) $result['conversion_rate'] : 0.0;

            return [
                'average_session_duration' => round($avgSession, 2),
                'bounce_rate' => round($bounce, 2),
                'page_views_per_session' => round($pageViews, 2),
                'conversion_rate' => round($conversion, 2),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法分析使用者行為: ' . $e->getMessage(), 0, $e);
        }
    }
}
