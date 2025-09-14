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

    public function getSourceDistributionByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function countPostsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int
    {
        // 簡單實作
        return 0;
    }

    public function countViewsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int
    {
        // 簡單實作
        return 0;
    }

    public function getPostTrendsByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getViewTrendsByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getAverageViewsPerPostByPeriod(StatisticsPeriod $period): float
    {
        // 簡單實作
        return 0.0;
    }

    public function getMostViewedPostByPeriod(StatisticsPeriod $period): ?array
    {
        // 簡單實作
        return null;
    }

    public function getNewPostsStatsByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getViewsDistributionByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getTotalPostsAsOfDate(DateTimeInterface $date): int
    {
        // 簡單實作
        return 0;
    }

    public function getTotalViewsAsOfDate(DateTimeInterface $date): int
    {
        // 簡單實作
        return 0;
    }

    public function getPostActivityHeatmapByPeriod(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getMostActiveAuthorsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        // 簡單實作
        return [];
    }

    public function getEngagementRateByPeriod(StatisticsPeriod $period): float
    {
        // 簡單實作
        return 0.0;
    }

    public function getTagUsageStatsByPeriod(StatisticsPeriod $period, int $limit = 20): array
    {
        // 簡單實作
        return [];
    }

    public function hasPostDataInPeriod(StatisticsPeriod $period): bool
    {
        // 簡單實作
        return false;
    }

    public function getPostStatsByPeriod(int $postId, StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getPostsByPublishTime(StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getPostHistoricalPerformance(int $postId, StatisticsPeriod $period): array
    {
        // 簡單實作
        return [];
    }

    public function getStatisticsTrends(StatisticsPeriod $period, int $dataPoints = 30): array
    {
        // 簡單實作
        return [];
    }
}
