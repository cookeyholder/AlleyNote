<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * 使用者統計查詢 Repository 實作.
 *
 * 提供使用者活躍度與行為分析的統計查詢功能，使用原生 SQL 最佳化效能。
 * 專注於使用者相關的複雜統計查詢和活動分析。
 */
final class UserStatisticsRepository implements UserStatisticsRepositoryInterface
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    public function getActiveUsersCount(StatisticsPeriod $period, string $activityType = 'login'): int
    {
        $allowedTypes = ['login', 'post', 'view', 'comment'];
        if (!in_array($activityType, $allowedTypes, true)) {
            throw new InvalidArgumentException('不支援的活動類型: ' . $activityType);
        }

        try {
            $sql = match ($activityType) {
                'login' => 'SELECT COUNT(DISTINCT user_id) FROM user_activity_logs 
                           WHERE action = "login" AND created_at >= :start_date AND created_at <= :end_date',
                'post' => 'SELECT COUNT(DISTINCT user_id) FROM posts 
                          WHERE created_at >= :start_date AND created_at <= :end_date',
                'view' => 'SELECT COUNT(DISTINCT user_id) FROM user_activity_logs 
                          WHERE action = "view" AND created_at >= :start_date AND created_at <= :end_date',
                'comment' => 'SELECT COUNT(DISTINCT user_id) FROM comments 
                             WHERE created_at >= :start_date AND created_at <= :end_date',
            };

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('取得活躍使用者數量失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getNewUsersCount(StatisticsPeriod $period): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM users 
                    WHERE created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('取得新註冊使用者數量失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getTotalUsersCount(StatisticsPeriod $period): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM users WHERE created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者總數統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getActiveUsersByActivityType(StatisticsPeriod $period): array
    {
        try {
            $activityTypes = ['login', 'post', 'view', 'comment'];
            $result = [];

            foreach ($activityTypes as $type) {
                $result[$type] = $this->getActiveUsersCount($period, $type);
            }

            return $result;
        } catch (Exception $e) {
            throw new RuntimeException('取得活動類型統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10, string $metric = 'posts'): array
    {
        if ($limit <= 0 || $limit > 50) {
            throw new InvalidArgumentException('查詢數量必須在 1-50 之間');
        }

        $allowedMetrics = ['posts', 'views', 'logins', 'activity_score'];
        if (!in_array($metric, $allowedMetrics, true)) {
            throw new InvalidArgumentException('不支援的排序指標: ' . $metric);
        }

        try {
            $sql = match ($metric) {
                'posts' => 'SELECT u.id as user_id, u.username, COUNT(p.id) as metric_value, 
                                  ROW_NUMBER() OVER (ORDER BY COUNT(p.id) DESC) as rank
                           FROM users u 
                           LEFT JOIN posts p ON u.id = p.user_id 
                                              AND p.created_at >= :start_date 
                                              AND p.created_at <= :end_date
                           WHERE u.created_at <= :end_date
                           GROUP BY u.id, u.username 
                           ORDER BY metric_value DESC 
                           LIMIT :limit',
                'logins' => 'SELECT u.id as user_id, u.username, COUNT(al.id) as metric_value,
                                   ROW_NUMBER() OVER (ORDER BY COUNT(al.id) DESC) as rank
                            FROM users u 
                            LEFT JOIN user_activity_logs al ON u.id = al.user_id 
                                                             AND al.action = "login"
                                                             AND al.created_at >= :start_date 
                                                             AND al.created_at <= :end_date
                            WHERE u.created_at <= :end_date
                            GROUP BY u.id, u.username 
                            ORDER BY metric_value DESC 
                            LIMIT :limit',
                'views' => 'SELECT u.id as user_id, u.username, COUNT(al.id) as metric_value,
                                  ROW_NUMBER() OVER (ORDER BY COUNT(al.id) DESC) as rank
                           FROM users u 
                           LEFT JOIN user_activity_logs al ON u.id = al.user_id 
                                                            AND al.action = "view"
                                                            AND al.created_at >= :start_date 
                                                            AND al.created_at <= :end_date
                           WHERE u.created_at <= :end_date
                           GROUP BY u.id, u.username 
                           ORDER BY metric_value DESC 
                           LIMIT :limit',
                'activity_score' => 'SELECT u.id as user_id, u.username, 
                                           (COUNT(DISTINCT p.id) * 3 + 
                                            COUNT(DISTINCT c.id) * 2 + 
                                            COUNT(DISTINCT al.id)) as metric_value,
                                           ROW_NUMBER() OVER (ORDER BY (COUNT(DISTINCT p.id) * 3 + 
                                                                       COUNT(DISTINCT c.id) * 2 + 
                                                                       COUNT(DISTINCT al.id)) DESC) as rank
                                    FROM users u 
                                    LEFT JOIN posts p ON u.id = p.user_id 
                                                       AND p.created_at >= :start_date 
                                                       AND p.created_at <= :end_date
                                    LEFT JOIN comments c ON u.id = c.user_id 
                                                          AND c.created_at >= :start_date 
                                                          AND c.created_at <= :end_date
                                    LEFT JOIN user_activity_logs al ON u.id = al.user_id 
                                                                     AND al.action = "login"
                                                                     AND al.created_at >= :start_date 
                                                                     AND al.created_at <= :end_date
                                    WHERE u.created_at <= :end_date
                                    GROUP BY u.id, u.username 
                                    ORDER BY metric_value DESC 
                                    LIMIT :limit',
            };

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'user_id' => (int) $row['user_id'],
                    'username' => (string) $row['username'],
                    'metric_value' => (int) $row['metric_value'],
                    'rank' => (int) $row['rank'],
                ];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得最活躍使用者失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserLoginActivity(StatisticsPeriod $period): array
    {
        try {
            // 基本登入統計
            $sql = 'SELECT 
                        COUNT(*) as total_logins,
                        COUNT(DISTINCT user_id) as unique_users,
                        ROUND(COUNT(*) / NULLIF(COUNT(DISTINCT user_id), 0), 2) as avg_logins_per_user
                    FROM user_activity_logs 
                    WHERE action = "login" 
                    AND created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $basicStats = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($basicStats)) {
                return [
                    'total_logins' => 0,
                    'unique_users' => 0,
                    'avg_logins_per_user' => 0.0,
                    'peak_hour' => 0,
                    'login_frequency_distribution' => [],
                ];
            }

            // 取得登入高峰時間
            $sql = 'SELECT HOUR(created_at) as hour, COUNT(*) as count
                    FROM user_activity_logs 
                    WHERE action = "login" 
                    AND created_at >= :start_date AND created_at <= :end_date
                    GROUP BY HOUR(created_at)
                    ORDER BY count DESC
                    LIMIT 1';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $peakHourData = $stmt->fetch(PDO::FETCH_ASSOC);
            $peakHour = is_array($peakHourData) ? (int) $peakHourData['hour'] : 0;

            // 取得登入頻率分布
            $sql = 'SELECT 
                        CASE 
                            WHEN login_count = 1 THEN "1次"
                            WHEN login_count BETWEEN 2 AND 5 THEN "2-5次"
                            WHEN login_count BETWEEN 6 AND 10 THEN "6-10次"
                            ELSE "10次以上"
                        END as frequency_range,
                        COUNT(*) as users_count
                    FROM (
                        SELECT user_id, COUNT(*) as login_count
                        FROM user_activity_logs 
                        WHERE action = "login" 
                        AND created_at >= :start_date AND created_at <= :end_date
                        GROUP BY user_id
                    ) as user_logins
                    GROUP BY frequency_range';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $frequencyDistribution = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $frequencyDistribution[(string) $row['frequency_range']] = (int) $row['users_count'];
            }

            return [
                'total_logins' => (int) $basicStats['total_logins'],
                'unique_users' => (int) $basicStats['unique_users'],
                'avg_logins_per_user' => (float) $basicStats['avg_logins_per_user'],
                'peak_hour' => $peakHour,
                'login_frequency_distribution' => $frequencyDistribution,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者登入活動分析失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserRegistrationTrend(StatisticsPeriod $currentPeriod, StatisticsPeriod $previousPeriod): array
    {
        try {
            $currentCount = $this->getNewUsersCount($currentPeriod);
            $previousCount = $this->getNewUsersCount($previousPeriod);

            $growthCount = $currentCount - $previousCount;
            $growthRate = $previousCount > 0 ? ($growthCount / $previousCount) * 100 : 0.0;

            return [
                'current' => $currentCount,
                'previous' => $previousCount,
                'growth_rate' => round($growthRate, 2),
                'growth_count' => $growthCount,
            ];
        } catch (Exception $e) {
            throw new RuntimeException('取得使用者註冊趨勢失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserActivityTimeDistribution(StatisticsPeriod $period, string $groupBy = 'hour'): array
    {
        $allowedGroupBy = ['hour', 'day', 'week'];
        if (!in_array($groupBy, $allowedGroupBy, true)) {
            throw new InvalidArgumentException('不支援的分組方式: ' . $groupBy);
        }

        try {
            $groupByClause = match ($groupBy) {
                'hour' => 'HOUR(created_at)',
                'day' => 'DATE(created_at)',
                'week' => 'YEARWEEK(created_at)',
            };

            $sql = "SELECT {$groupByClause} as time_period, COUNT(DISTINCT user_id) as active_users
                    FROM user_activity_logs 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    GROUP BY {$groupByClause}
                    ORDER BY time_period";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[(string) $row['time_period']] = (int) $row['active_users'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者活動時間分布失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserRetentionAnalysis(StatisticsPeriod $cohortPeriod, int $daysAfterRegistration): array
    {
        if ($daysAfterRegistration < 1) {
            throw new InvalidArgumentException('註冊後天數必須大於 0');
        }

        try {
            // 取得世代使用者數
            $sql = 'SELECT COUNT(*) as cohort_size
                    FROM users 
                    WHERE created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $cohortPeriod->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $cohortPeriod->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $cohortSize = (int) $stmt->fetchColumn();

            if ($cohortSize === 0) {
                return [
                    'cohort_size' => 0,
                    'retained_users' => 0,
                    'retention_rate' => 0.0,
                    'churn_rate' => 0.0,
                ];
            }

            // 計算保留使用者數
            $retentionDate = $cohortPeriod->endTime->modify("+{$daysAfterRegistration} days");

            $sql = 'SELECT COUNT(DISTINCT u.id) as retained_users
                    FROM users u
                    INNER JOIN user_activity_logs al ON u.id = al.user_id
                    WHERE u.created_at >= :start_date AND u.created_at <= :end_date
                    AND al.created_at >= :retention_date
                    AND al.created_at <= :retention_end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $cohortPeriod->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $cohortPeriod->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':retention_date', $retentionDate->format('Y-m-d 00:00:00'), PDO::PARAM_STR);
            $stmt->bindValue(':retention_end_date', $retentionDate->format('Y-m-d 23:59:59'), PDO::PARAM_STR);
            $stmt->execute();

            $retainedUsers = (int) $stmt->fetchColumn();
            $retentionRate = ($retainedUsers / $cohortSize) * 100;
            $churnRate = 100 - $retentionRate;

            return [
                'cohort_size' => $cohortSize,
                'retained_users' => $retainedUsers,
                'retention_rate' => round($retentionRate, 2),
                'churn_rate' => round($churnRate, 2),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者留存率分析失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUsersCountByRole(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT role, COUNT(*) as count 
                    FROM users 
                    WHERE created_at <= :end_date
                    GROUP BY role
                    ORDER BY count DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[(string) ($row['role'] ?? 'unknown')] = (int) $row['count'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者角色統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserEngagementStatistics(StatisticsPeriod $period): array
    {
        try {
            // 計算使用者參與度評分（基於文章數、留言數、登入次數）
            $sql = 'SELECT 
                        u.id,
                        (COUNT(DISTINCT p.id) * 3 + 
                         COUNT(DISTINCT c.id) * 2 + 
                         COUNT(DISTINCT al.id)) as engagement_score
                    FROM users u
                    LEFT JOIN posts p ON u.id = p.user_id 
                                       AND p.created_at >= :start_date 
                                       AND p.created_at <= :end_date
                    LEFT JOIN comments c ON u.id = c.user_id 
                                          AND c.created_at >= :start_date 
                                          AND c.created_at <= :end_date
                    LEFT JOIN user_activity_logs al ON u.id = al.user_id 
                                                     AND al.action = "login"
                                                     AND al.created_at >= :start_date 
                                                     AND al.created_at <= :end_date
                    WHERE u.created_at <= :end_date
                    GROUP BY u.id';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $scores = [];
            $totalScore = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $score = (int) $row['engagement_score'];
                $scores[] = $score;
                $totalScore += $score;
            }

            $totalUsers = count($scores);
            if ($totalUsers === 0) {
                return [
                    'high_engagement' => 0,
                    'medium_engagement' => 0,
                    'low_engagement' => 0,
                    'inactive' => 0,
                    'avg_engagement_score' => 0.0,
                ];
            }

            $avgScore = $totalScore / $totalUsers;

            // 分類參與度
            $highEngagement = 0;
            $mediumEngagement = 0;
            $lowEngagement = 0;
            $inactive = 0;

            foreach ($scores as $score) {
                if ($score >= 10) {
                    $highEngagement++;
                } elseif ($score >= 5) {
                    $mediumEngagement++;
                } elseif ($score > 0) {
                    $lowEngagement++;
                } else {
                    $inactive++;
                }
            }

            return [
                'high_engagement' => $highEngagement,
                'medium_engagement' => $mediumEngagement,
                'low_engagement' => $lowEngagement,
                'inactive' => $inactive,
                'avg_engagement_score' => round($avgScore, 2),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者參與度統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserRegistrationSources(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT registration_source, COUNT(*) as count 
                    FROM users 
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY registration_source
                    ORDER BY count DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $source = $row['registration_source'] ?? 'direct';
                $result[(string) $source] = (int) $row['count'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者註冊來源分析失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserGeographicalDistribution(StatisticsPeriod $period, int $limit = 10): array
    {
        if ($limit <= 0 || $limit > 50) {
            throw new InvalidArgumentException('查詢數量必須在 1-50 之間');
        }

        try {
            $sql = 'SELECT 
                        COALESCE(location, "未知") as location,
                        COUNT(*) as users_count,
                        ROUND((COUNT(*) * 100.0) / (SELECT COUNT(*) FROM users WHERE created_at <= :total_end_date), 2) as percentage
                    FROM users 
                    WHERE created_at <= :end_date
                    GROUP BY location
                    ORDER BY users_count DESC
                    LIMIT :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':total_end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[] = [
                    'location' => (string) $row['location'],
                    'users_count' => (int) $row['users_count'],
                    'percentage' => (float) $row['percentage'],
                ];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者地理分布統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hasDataForPeriod(StatisticsPeriod $period): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM users WHERE created_at >= :start_date AND created_at <= :end_date LIMIT 1';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('檢查資料存在性失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserActivitySummary(StatisticsPeriod $period): array
    {
        try {
            $totalUsers = $this->getTotalUsersCount($period);
            $newUsers = $this->getNewUsersCount($period);
            $activeUsers = $this->getActiveUsersCount($period, 'login');

            // 計算回訪使用者（有活動但非新註冊的使用者）
            $sql = 'SELECT COUNT(DISTINCT al.user_id) as returning_users
                    FROM user_activity_logs al
                    INNER JOIN users u ON al.user_id = u.id
                    WHERE al.created_at >= :start_date AND al.created_at <= :end_date
                    AND u.created_at < :start_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $returningUsers = (int) $stmt->fetchColumn();

            // 計算活動率
            $activityRate = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0.0;

            // 取得最活躍時段
            $sql = 'SELECT HOUR(created_at) as hour, COUNT(DISTINCT user_id) as active_users
                    FROM user_activity_logs 
                    WHERE created_at >= :start_date AND created_at <= :end_date
                    GROUP BY HOUR(created_at)
                    ORDER BY active_users DESC
                    LIMIT 3';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $topActiveHours = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $topActiveHours[] = (int) $row['hour'];
            }

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'new_users' => $newUsers,
                'returning_users' => $returningUsers,
                'user_activity_rate' => round($activityRate, 2),
                'top_active_hours' => $topActiveHours,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者活動摘要失敗: ' . $e->getMessage(), 0, $e);
        }
    }
}
