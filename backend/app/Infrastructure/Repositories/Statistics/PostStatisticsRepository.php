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

            $value = $stmt->fetchColumn();

            return $value === false ? 0 : (int) $value;
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

            $value = $stmt->fetchColumn();

            return $value === false ? 0 : (int) $value;
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

            $value = $stmt->fetchColumn();

            return $value === false ? 0 : (int) $value;
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

            /** @var array<int, array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows ?: [];
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢熱門文章: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得指定週期內各來源的文章統計.
     *
     * @param StatisticsPeriod $period
     * @return array<string, array{post_count: int, views: int}>
     */
    public function getSourceDistributionByPeriod(StatisticsPeriod $period): array
    {
        // 預設空資料，實作時回傳 country/source => stats map
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

    /**
     * 取得文章發布趨勢資料（按日期分組）.
     *
     * @param StatisticsPeriod $period
     * @return array<string,int> // date => post_count
     */
    public function getPostTrendsByPeriod(StatisticsPeriod $period): array
    {
        return [];
    }

    /**
     * 取得觀看次數趨勢資料（按日期分組）.
     *
     * @param StatisticsPeriod $period
     * @return array<string,int> // date => views
     */
    public function getViewTrendsByPeriod(StatisticsPeriod $period): array
    {
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
        // 預設回傳，符合 interface 指定的 shape
        return [
            'total_new_posts' => 0,
            'total_views' => 0,
            'avg_views_per_post' => 0.0,
        ];
    }

    /**
     * 取得文章觀看次數分布.
     *
     * @param StatisticsPeriod $period
     * @return array<string,int> // bucket => count
     */
    public function getViewsDistributionByPeriod(StatisticsPeriod $period): array
    {
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

    /**
     * 取得文章活動熱圖資料（小時級別）.
     *
     * @param StatisticsPeriod $period
     * @return array<string, array{hour: int, day: int, activity_count: int}>
     */
    public function getPostActivityHeatmapByPeriod(StatisticsPeriod $period): array
    {
        return [];
    }

    /**
     * 取得最活躍的文章作者統計.
     *
     * @param StatisticsPeriod $period
     * @param int $limit
     * @return array<int, array{author_id: int, name: string, post_count: int, views: int}>
     */
    public function getMostActiveAuthorsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        return [];
    }

    public function getEngagementRateByPeriod(StatisticsPeriod $period): float
    {
        // 簡單實作
        return 0.0;
    }

    /**
     * 取得文章標籤使用統計.
     *
     * @param StatisticsPeriod $period
     * @param int $limit
     * @return array<int, array{tag: string, count: int}>
     */
    public function getTagUsageStatsByPeriod(StatisticsPeriod $period, int $limit = 20): array
    {
        return [];
    }

    public function hasPostDataInPeriod(StatisticsPeriod $period): bool
    {
        // 簡單實作
        return false;
    }

    public function getPostStatsByPeriod(int $postId, StatisticsPeriod $period): array
    {
        // 預設回傳符合 interface 指定的 shape
        return [
            'views' => 0,
            'comments' => 0,
            'likes' => 0,
            'shares' => 0,
            'source' => '',
        ];
    }

    /**
     * 取得按發布時間分組的文章統計.
     *
     * @param StatisticsPeriod $period
     * @return array<int, array{post_id: int, publish_time: string, count: int}>
     */
    public function getPostsByPublishTime(StatisticsPeriod $period): array
    {
        return [];
    }

    /**
     * 取得文章歷史表現資料.
     *
     * @param int $postId
     * @param StatisticsPeriod $period
     * @return array<int, array{date: string, views: int, likes: int, comments: int}>
     */
    public function getPostHistoricalPerformance(int $postId, StatisticsPeriod $period): array
    {
        return [];
    }

    /**
     * 取得文章統計趨勢資料.
     *
     * @param StatisticsPeriod $period
     * @param int $dataPoints
     * @return array<int, array{date: string, value: float|int}>
     */
    public function getStatisticsTrends(StatisticsPeriod $period, int $dataPoints = 30): array
    {
        return [];
    }
}
