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
     * @return array<int, array<string, mixed>>
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

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $distribution = [];

            foreach ($results as $result) {
                $distribution[$result['activity_level']] = (int) $result['user_count'];
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

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dailyActive = [];

            foreach ($results as $result) {
                $dailyActive[$result['activity_date']] = (int) $result['active_users'];
            }

            return $dailyActive;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢每日活躍使用者: ' . $e->getMessage(), 0, $e);
        }
    }
}
