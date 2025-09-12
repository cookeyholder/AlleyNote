<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * 文章統計資料存取實作類別.
 *
 * 實作文章相關統計資料的查詢功能，提供高效能的原生 SQL 查詢。
 * 支援文章數量、觀看次數、來源分布等複雜統計查詢。
 */
final readonly class PostStatisticsRepository implements PostStatisticsRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {}

    /**
     * 計算指定週期內的文章總數.
     */
    public function countPostsByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            if (!$executed) {
                throw new RuntimeException('查詢執行失敗');
            }

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢文章數量: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算指定週期內的總觀看次數.
     */
    public function countViewsByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COALESCE(SUM(p.views), 0)
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢觀看次數: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算指定週期內的不重複觀看者數量.
     */
    public function countUniqueViewersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(DISTINCT user_ip)
                FROM post_views
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
            throw new RuntimeException('無法查詢不重複觀看者數量: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得指定週期內最受歡迎的文章.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPopularPostsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        try {
            $sql = '
                SELECT
                    p.id,
                    p.title,
                    p.views,
                    p.created_at
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                ORDER BY p.views DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $period->startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $period->endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢熱門文章: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據來源類型計算文章數量.
     *
     * @return array<string, int>
     */
    public function countPostsBySource(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    COALESCE(p.source_type, "direct") as source_type,
                    COUNT(*) as count
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY p.source_type
                ORDER BY count DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sourceCounts = [];

            foreach ($results as $result) {
                $sourceCounts[$result['source_type']] = (int) $result['count'];
            }

            return $sourceCounts;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢來源分布: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算平均文章長度.
     */
    public function getAveragePostLength(StatisticsPeriod $period): float
    {
        try {
            $sql = '
                SELECT AVG(CHAR_LENGTH(p.content)) as avg_length
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetchColumn();
            return $result ? (float) $result : 0.0;
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算平均文章長度: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得每日文章發布數量統計.
     *
     * @return array<string, int>
     */
    public function getDailyPostCounts(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(p.created_at) as post_date,
                    COUNT(*) as count
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY DATE(p.created_at)
                ORDER BY post_date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dailyCounts = [];

            foreach ($results as $result) {
                $dailyCounts[$result['post_date']] = (int) $result['count'];
            }

            return $dailyCounts;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢每日文章統計: ' . $e->getMessage(), 0, $e);
        }
    }
}
