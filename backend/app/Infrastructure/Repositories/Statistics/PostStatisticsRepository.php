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
            throw new RuntimeException(
                "計算週期內文章總數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
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
            throw new RuntimeException(
                "計算週期內總觀看次數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算指定週期內的不重複觀看者數量.
     */
    public function countUniqueViewersByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(DISTINCT pv.user_ip)
                FROM post_views pv
                JOIN posts p ON pv.post_id = p.id
                WHERE pv.view_date >= :start_date
                    AND pv.view_date <= :end_date
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
            throw new RuntimeException(
                "計算週期內不重複觀看者數量失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得指定週期內最受歡迎的文章.
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

            /** @var array<array{id: int, title: string, views: int, created_at: string}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得熱門文章失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得指定週期內各來源的文章統計.
     */
    public function getSourceDistributionByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    p.source_type,
                    COUNT(*) as post_count,
                    COALESCE(SUM(p.views), 0) as view_count,
                    ROUND((COUNT(*) * 100.0 / total_posts.total), 2) as percentage
                FROM posts p
                CROSS JOIN (
                    SELECT COUNT(*) as total
                    FROM posts
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                        AND status = "published"
                ) total_posts
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY p.source_type
                ORDER BY post_count DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{source_type: string, post_count: int, view_count: int, percentage: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得來源分布統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算指定來源類型在指定週期內的文章數量.
     */
    public function countPostsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM posts
                WHERE source_type = :source_type
                    AND created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'source_type' => $sourceType->value,
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算來源類型文章數量失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算指定來源類型在指定週期內的觀看次數.
     */
    public function countViewsBySourceAndPeriod(SourceType $sourceType, StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COALESCE(SUM(views), 0)
                FROM posts
                WHERE source_type = :source_type
                    AND created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'source_type' => $sourceType->value,
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算來源類型觀看次數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得文章發布趨勢資料（按日期分組）.
     */
    public function getPostTrendsByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as post_count,
                    COALESCE(SUM(views), 0) as view_count
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{date: string, post_count: int, view_count: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得文章發布趨勢失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得觀看次數趨勢資料（按日期分組）.
     */
    public function getViewTrendsByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(pv.view_date) as date,
                    COUNT(*) as view_count,
                    COUNT(DISTINCT pv.user_ip) as unique_views
                FROM post_views pv
                JOIN posts p ON pv.post_id = p.id
                WHERE pv.view_date >= :start_date
                    AND pv.view_date <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY DATE(pv.view_date)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{date: string, view_count: int, unique_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得觀看趨勢失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算指定週期內的平均每篇文章觀看次數.
     */
    public function getAverageViewsPerPostByPeriod(StatisticsPeriod $period): float
    {
        try {
            $sql = '
                SELECT
                    CASE
                        WHEN COUNT(*) = 0 THEN 0
                        ELSE ROUND(COALESCE(SUM(views), 0) / COUNT(*), 2)
                    END as avg_views
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算平均觀看次數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得指定週期內觀看次數最高的文章.
     */
    public function getMostViewedPostByPeriod(StatisticsPeriod $period): ?array
    {
        try {
            $sql = '
                SELECT
                    id,
                    title,
                    views,
                    created_at
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                ORDER BY views DESC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array{id: int, title: string, views: int, created_at: string}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得最高觀看文章失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得指定週期內新發布的文章統計.
     */
    public function getNewPostsStatsByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(*) as total_new_posts,
                    COALESCE(SUM(views), 0) as total_views,
                    CASE
                        WHEN COUNT(*) = 0 THEN 0
                        ELSE ROUND(COALESCE(SUM(views), 0) / COUNT(*), 2)
                    END as avg_views_per_post
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array{total_new_posts: int, total_views: int, avg_views_per_post: float}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [
                'total_new_posts' => 0,
                'total_views' => 0,
                'avg_views_per_post' => 0.0,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得新文章統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得文章觀看次數分布.
     */
    public function getViewsDistributionByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    CASE
                        WHEN views = 0 THEN "0"
                        WHEN views <= 10 THEN "1-10"
                        WHEN views <= 50 THEN "11-50"
                        WHEN views <= 100 THEN "51-100"
                        WHEN views <= 500 THEN "101-500"
                        WHEN views <= 1000 THEN "501-1000"
                        ELSE "1000+"
                    END as range,
                    COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / total_posts.total), 2) as percentage
                FROM posts
                CROSS JOIN (
                    SELECT COUNT(*) as total
                    FROM posts
                    WHERE created_at >= :start_date
                        AND created_at <= :end_date
                        AND deleted_at IS NULL
                        AND status = "published"
                ) total_posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                GROUP BY range
                ORDER BY
                    CASE range
                        WHEN "0" THEN 1
                        WHEN "1-10" THEN 2
                        WHEN "11-50" THEN 3
                        WHEN "51-100" THEN 4
                        WHEN "101-500" THEN 5
                        WHEN "501-1000" THEN 6
                        WHEN "1000+" THEN 7
                    END
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{range: string, count: int, percentage: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得觀看次數分布失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算文章總數（截至指定日期）.
     */
    public function getTotalPostsAsOfDate(DateTimeInterface $date): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM posts
                WHERE created_at <= :date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['date' => $date->format('Y-m-d H:i:s')]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算截至日期的文章總數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算總觀看次數（截至指定日期）.
     */
    public function getTotalViewsAsOfDate(DateTimeInterface $date): int
    {
        try {
            $sql = '
                SELECT COALESCE(SUM(views), 0)
                FROM posts
                WHERE created_at <= :date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['date' => $date->format('Y-m-d H:i:s')]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算截至日期的總觀看次數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * StatisticsQueryService 需要的趨勢分析方法.
     */
    public function getStatisticsTrends(
        StatisticsPeriod $period,
        int $limit = 30,
    ): array {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as post_count,
                    COALESCE(SUM(views), 0) as view_count,
                    COALESCE(AVG(views), 0) as avg_views
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $period->startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $period->endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<array{date: string, post_count: int, view_count: int, unique_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得統計趨勢失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    // 實作其餘的介面方法...

    /**
     * 取得文章活動熱圖資料（小時級別）.
     */
    public function getPostActivityHeatmapByPeriod(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    HOUR(created_at) as hour,
                    COUNT(*) as activity_count
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                GROUP BY DATE(created_at), HOUR(created_at)
                ORDER BY date, hour
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{date: string, hour: int, activity_count: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得活動熱圖資料失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得最活躍的文章作者統計.
     */
    public function getMostActiveAuthorsByPeriod(StatisticsPeriod $period, int $limit = 10): array
    {
        try {
            $sql = '
                SELECT
                    p.user_id,
                    u.username,
                    COUNT(*) as post_count,
                    COALESCE(SUM(p.views), 0) as total_views
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY p.user_id, u.username
                ORDER BY post_count DESC, total_views DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $period->startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $period->endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<array{user_id: int, username: string, post_count: int, total_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得活躍作者統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算文章互動率.
     */
    public function getEngagementRateByPeriod(StatisticsPeriod $period): float
    {
        try {
            // 簡化的互動率計算：基於觀看次數與文章數的比率
            $sql = '
                SELECT
                    CASE
                        WHEN COUNT(*) = 0 THEN 0
                        ELSE ROUND((COALESCE(SUM(views), 0) / COUNT(*)) / 10, 2)
                    END as engagement_rate
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算互動率失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得文章標籤使用統計.
     */
    public function getTagUsageStatsByPeriod(StatisticsPeriod $period, int $limit = 20): array
    {
        try {
            $sql = '
                SELECT
                    t.name as tag,
                    COUNT(pt.post_id) as usage_count,
                    COUNT(DISTINCT pt.post_id) as post_count
                FROM tags t
                JOIN post_tags pt ON t.id = pt.tag_id
                JOIN posts p ON pt.post_id = p.id
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY t.id, t.name
                ORDER BY usage_count DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $period->startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $period->endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<array{tag: string, usage_count: int, post_count: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得標籤使用統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 檢查指定週期是否有文章資料.
     */
    public function hasPostDataInPeriod(StatisticsPeriod $period): bool
    {
        try {
            $sql = '
                SELECT 1
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "檢查文章資料存在性失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得指定文章在特定週期的統計資料.
     */
    public function getPostStatsByPeriod(int $postId, StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    p.views,
                    0 as comments, -- 需要評論系統時再實作
                    0 as likes,    -- 需要按讚系統時再實作
                    0 as shares,   -- 需要分享系統時再實作
                    p.source_type as source
                FROM posts p
                WHERE p.id = :post_id
                    AND p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'post_id' => $postId,
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array{views: int, comments: int, likes: int, shares: int, source: string}|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [
                'views' => 0,
                'comments' => 0,
                'likes' => 0,
                'shares' => 0,
                'source' => 'unknown',
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得文章統計資料失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得按發布時間分組的文章統計.
     */
    public function getPostsByPublishTime(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    HOUR(created_at) as publish_hour,
                    DAYNAME(created_at) as publish_day,
                    COALESCE(AVG(views), 0) as avg_views
                FROM posts
                WHERE created_at >= :start_date
                    AND created_at <= :end_date
                    AND deleted_at IS NULL
                    AND status = "published"
                GROUP BY HOUR(created_at), DAYNAME(created_at)
                ORDER BY avg_views DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{publish_hour: string, publish_day: string, avg_views: float}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得發布時間統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得文章歷史表現資料.
     */
    public function getPostHistoricalPerformance(int $postId, StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(pv.view_date) as date,
                    COUNT(*) as daily_views
                FROM post_views pv
                WHERE pv.post_id = :post_id
                    AND pv.view_date >= :start_date
                    AND pv.view_date <= :end_date
                GROUP BY DATE(pv.view_date)
                ORDER BY date ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'post_id' => $postId,
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<array{date: string, daily_views: int}> $result */
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得文章歷史表現失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 根據來源類型取得文章數據.
     * @return array<string, mixed>
     */
    public function getPostsBySourceType(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    CASE
                        WHEN p.user_id = 1 THEN "admin"
                        WHEN p.user_id IS NULL THEN "anonymous"
                        ELSE "user"
                    END as source_type,
                    COUNT(*) as count
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY source_type
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得來源類型文章數據失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得每小時分布統計.
     * @return array<string, mixed>
     */
    public function getHourlyDistribution(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    EXTRACT(HOUR FROM p.created_at) as hour,
                    COUNT(*) as count
                FROM posts p
                WHERE p.created_at >= :start_date
                    AND p.created_at <= :end_date
                    AND p.deleted_at IS NULL
                    AND p.status = "published"
                GROUP BY hour
                ORDER BY hour
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得每小時分布統計失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }
}
