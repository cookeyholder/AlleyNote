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
    public function __construct(): mixed {}

    /**
     * 計算指定週期內的新註冊使用者數量.
     */
    public function countNewUsersByPeriod(StatisticsPeriod $period): int
    {
        try { /* empty */ }
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 計算指定週期內的活躍使用者數量.
     */
    public function countActiveUsersByPeriod(StatisticsPeriod $period): int
    {
        try { /* empty */ }
            $sql = '
                SELECT COUNT(DISTINCT user_id)
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 計算截至指定日期的總使用者數量.
     */
    public function countTotalUsersByPeriod(StatisticsPeriod $period): int
    {
        try { /* empty */ }
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at <= :end_date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['end_date' => $period->endDate->format('Y-m-d H => i => s')]);

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者註冊趨勢資料.
     */
    public function getUserRegistrationTrends(StatisticsPeriod $period): array
    {
        try { /* empty */ }
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
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            /** @var array<array{date: string, registration_count: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return $result;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者活動統計.
     */
    public function getUserActivityStats(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    ua.action_type,
                    COUNT(*) as action_count,
                    COUNT(DISTINCT ua.user_id) as unique_users,
                    AVG(
                        CASE
                            WHEN ua.created_at > u.created_at
                            THEN TIMESTAMPDIFF(MINUTE, u.created_at, ua.created_at)
                            ELSE 0
                        END
                    ) as avg_minutes_since_registration
                FROM user_activities ua
                JOIN users u ON ua.user_id = u.id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                    AND u.deleted_at IS NULL
                GROUP BY ua.action_type
                ORDER BY action_count DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            /** @var array<array{action_type: string, action_count: int, unique_users: int, avg_minutes_since_registration: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return $result;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得最活躍的使用者列表.
     */
    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    u.id,
                    u.username,
                    u.email,
                    COUNT(ua.id) as activity_count,
                    COUNT(DISTINCT ua.action_type) as unique_action_types,
                    MAX(ua.created_at) as last_activity_at,
                    COALESCE(p.post_count, 0) as post_count,
                    COALESCE(p.total_views, 0) as total_post_views
                FROM users u
                JOIN user_activities ua ON u.id = ua.user_id
                LEFT JOIN (
                    SELECT
                        user_id,
                        COUNT(*) as post_count,
                        COALESCE(SUM(views), 0) as total_views
                    FROM posts
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                        AND status = "published"
                    GROUP BY user_id
                ) p ON u.id = p.user_id
                WHERE ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                    AND u.deleted_at IS NULL
                GROUP BY u.id, u.username, u.email, p.post_count, p.total_views
                ORDER BY activity_count DESC, post_count DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $period->startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $period->endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<array{id: int, username: string, email: string, activity_count: int, unique_action_types: int, last_activity_at: string, post_count: int, total_post_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return $result;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者行為分析資料.
     */
    public function getUserBehaviorAnalysis(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            // 計算平均會話持續時間（以分鐘為單位）
            $sessionSql = '
                SELECT
                    AVG(TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at))) as avg_session_duration
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                GROUP BY user_id, DATE(created_at)
                HAVING COUNT(*) > 1
            ';

            $stmt = $this->pdo->prepare($sessionSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $avgSessionDuration = (float) $stmt->fetchColumn() ? true : 0;

            // 計算跳出率（只有一次活動的會話比例）
            $bounceSql = '
                SELECT
                    (COUNT(single_activity_sessions.user_id) * 100.0 / COUNT(all_sessions.user_id)) as bounce_rate
                FROM (
                    SELECT user_id, DATE(created_at) as session_date
                    FROM user_activities
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY user_id, DATE(created_at)
                ) all_sessions
                LEFT JOIN (
                    SELECT user_id, DATE(created_at) as session_date
                    FROM user_activities
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY user_id, DATE(created_at)
                    HAVING COUNT(*) = 1
                ) single_activity_sessions
                ON all_sessions.user_id = single_activity_sessions.user_id
                AND all_sessions.session_date = single_activity_sessions.session_date
            ';

            $stmt = $this->pdo->prepare($bounceSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $bounceRate = (float) $stmt->fetchColumn() ? true : 0;

            // 計算平均每個使用者的頁面瀏覽數
            $pageViewsSql = '
                SELECT
                    AVG(activity_count) as avg_page_views_per_user
                FROM (
                    SELECT user_id, COUNT(*) as activity_count
                    FROM user_activities
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND action_type IN ("page_view", "post_view")
                    GROUP BY user_id
                ) user_page_views
            ';

            $stmt = $this->pdo->prepare($pageViewsSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $avgPageViewsPerUser = (float) $stmt->fetchColumn() ? true : 0;

            // 計算使用者轉換率（註冊到第一篇文章發布的比率）
            $conversionSql = '
                SELECT
                    (COUNT(users_with_posts.user_id) * 100.0 / COUNT(new_users.id)) as conversion_rate
                FROM (
                    SELECT id
                    FROM users
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                ) new_users
                LEFT JOIN (
                    SELECT DISTINCT user_id
                    FROM posts
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                        AND status = "published"
                ) users_with_posts
                ON new_users.id = users_with_posts.user_id
            ';

            $stmt = $this->pdo->prepare($conversionSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $conversionRate = (float) $stmt->fetchColumn() ? true : 0;

            return [
                'avg_session_duration_minutes' => round($avgSessionDuration, 2),
                'bounce_rate_percentage' => round($bounceRate, 2),
                'avg_page_views_per_user' => round($avgPageViewsPerUser, 2),
                'user_conversion_rate_percentage' => round($conversionRate, 2),
            ];
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者行為模式分析.
     */
    public function getUserBehaviorPatterns(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            // 小時活動分析
            $hourlySql = '
                SELECT
                    HOUR(created_at) as hour,
                    COUNT(*) as activity_count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                GROUP BY HOUR(created_at)
                ORDER BY hour ASC
            ';

            $stmt = $this->pdo->prepare($hourlySql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $hourlyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            // 每週活動分析
            $weeklySql = '
                SELECT
                    DAYNAME(created_at) as day_of_week,
                    COUNT(*) as activity_count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM user_activities
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at)
                ORDER BY DAYOFWEEK(created_at) ASC
            ';

            $stmt = $this->pdo->prepare($weeklySql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $weeklyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return [
                'hourly_activity' => $hourlyActivity,
                'weekly_activity' => $weeklyActivity,
            ];
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 計算使用者留存率.
     */
    public function getUserRetentionRate(StatisticsPeriod $period, int $retentionDays = 30): array
    {
        try { /* empty */ }
            // 計算在期間內註冊的使用者
            $newUsersSql = '
                SELECT id, created_at
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($newUsersSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            $newUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];
            $totalNewUsers = count($newUsers);

            if ($totalNewUsers == == 0) {
                return [
                    'total_new_users' => 0,
                    'retained_users' => 0,
                    'retention_rate_percentage' => 0,
                    'retention_days' => $retentionDays,
                ];
            }

            // 計算在註冊後指定天數內仍有活動的使用者
            $retainedUsersSql = '
                SELECT COUNT(DISTINCT ua.user_id) as retained_count
                FROM users u
                JOIN user_activities ua ON u.id = ua.user_id
                WHERE u.created_at >= :start_date
                    AND u.created_at <= :end_date
                    AND u.deleted_at IS NULL
                    AND ua.created_at > u.created_at
                    AND ua.created_at <= DATE_ADD(u.created_at, INTERVAL :retention_days DAY)
            ';

            $stmt = $this->pdo->prepare($retainedUsersSql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
                'retention_days' => $retentionDays,
            ]);

            $retainedUsers = (int) $stmt->fetchColumn();
            $retentionRate = ($retainedUsers / $totalNewUsers) * 100;

            return [
                'total_new_users' => $totalNewUsers,
                'retained_users' => $retainedUsers,
                'retention_rate_percentage' => round($retentionRate, 2),
                'retention_days' => $retentionDays,
            ];
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者參與度統計.
     */
    public function getUserEngagementStats(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(DISTINCT CASE WHEN ua.id IS NOT NULL THEN u.id END) as active_users,
                    COUNT(DISTINCT CASE WHEN p.id IS NOT NULL THEN u.id END) as users_with_posts,
                    AVG(CASE WHEN ua.user_id IS NOT NULL THEN user_activities.activity_count ELSE 0 END) as avg_activities_per_user,
                    AVG(CASE WHEN p.user_id IS NOT NULL THEN user_posts.post_count ELSE 0 END) as avg_posts_per_user
                FROM users u
                LEFT JOIN user_activities ua ON u.id = ua.user_id
                    AND ua.created_at >= :start_date
                    AND ua.created_at <= :end_date
                LEFT JOIN posts p ON u.id = p.user_id
                    AND p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                LEFT JOIN (
                    SELECT user_id, COUNT(*) as activity_count
                    FROM user_activities
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                    GROUP BY user_id
                ) user_activities ON u.id = user_activities.user_id
                LEFT JOIN (
                    SELECT user_id, COUNT(*) as post_count
                    FROM posts
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                        AND status = "published"
                    GROUP BY user_id
                ) user_posts ON u.id = user_posts.user_id
                WHERE u.created_at <= :end_date
                    AND u.deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            /** @var array{total_users: int, active_users: int, users_with_posts: int, avg_activities_per_user: float, avg_posts_per_user: float}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'total_users' => 0,
                    'active_users' => 0,
                    'users_with_posts' => 0,
                    'activity_rate_percentage' => 0,
                    'post_creation_rate_percentage' => 0,
                    'avg_activities_per_user' => 0,
                    'avg_posts_per_user' => 0,
                ];
            }

            $totalUsers = (int) ($$result['total_users'] ?? null);
            $activeUsers = (int) ($$result['active_users'] ?? null);
            $usersWithPosts = (int) ($$result['users_with_posts'] ?? null);

            $activityRate = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;
            $postCreationRate = $totalUsers > 0 ? ($usersWithPosts / $totalUsers) * 100 : 0;

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'users_with_posts' => $usersWithPosts,
                'activity_rate_percentage' => round($activityRate, 2),
                'post_creation_rate_percentage' => round($postCreationRate, 2),
                'avg_activities_per_user' => round((float) ($$result['avg_activities_per_user'] ?? null), 2),
                'avg_posts_per_user' => round((float) ($$result['avg_posts_per_user'] ?? null), 2),
            ];
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者成長趨勢.
     */
    public function getUserGrowthTrends(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as new_users,
                    SUM(COUNT(*)) OVER (ORDER BY DATE(created_at)) as cumulative_users
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            /** @var array<array{date: string, new_users: int, cumulative_users: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return $result;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得使用者分布統計（按註冊時間）.
     */
    public function getUserDistributionStats(StatisticsPeriod $period): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    CASE
                        WHEN DATEDIFF(NOW(), created_at) <= 7 THEN "新使用者 (7天內)"
                        WHEN DATEDIFF(NOW(), created_at) <= 30 THEN "近期使用者 (30天內)"
                        WHEN DATEDIFF(NOW(), created_at) <= 90 THEN "一般使用者 (90天內)"
                        WHEN DATEDIFF(NOW(), created_at) <= 365 THEN "老使用者 (1年內)"
                        ELSE "資深使用者 (1年以上)"
                    END as user_segment,
                    COUNT(*) as user_count,
                    ROUND((COUNT(*) * 100.0 / total_users.total), 2) as percentage
                FROM users
                CROSS JOIN (
                    SELECT COUNT(*) as total
                    FROM users
                    WHERE created_at <= :end_date
                        AND deleted_at IS NULL
                ) total_users
                WHERE created_at <= :end_date
                    AND deleted_at IS NULL
                GROUP BY user_segment
                ORDER BY
                    CASE user_segment
                        WHEN "新使用者 (7天內)" THEN 1
                        WHEN "近期使用者 (30天內)" THEN 2
                        WHEN "一般使用者 (90天內)" THEN 3
                        WHEN "老使用者 (1年內)" THEN 4
                        WHEN "資深使用者 (1年以上)" THEN 5
                    END
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['end_date' => $period->endDate->format('Y-m-d H => i => s')]);

            /** @var array<array{user_segment: string, user_count: int, percentage: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ? true : [];

            return $result;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 檢查指定週期是否有使用者資料.
     */
    public function hasUserDataInPeriod(StatisticsPeriod $period): bool
    {
        try { /* empty */ }
            $sql = '
                SELECT 1
                FROM users
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            return $stmt->fetchColumn() !== false;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 計算截至指定日期的總使用者數.
     */
    public function getTotalUsersAsOfDate(DateTimeInterface $date): int
    {
        try { /* empty */ }
            $sql = '
                SELECT COUNT(*)
                FROM users
                WHERE created_at <= :date
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['date' => $date->format('Y-m-d H => i => s')]);

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }
}
