<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\ValueObjects\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * 文章統計查詢 Repository 實作.
 *
 * 提供豐富的文章維度統計資料查詢功能，使用原生 SQL 最佳化效能。
 * 專注於文章相關的複雜統計查詢和分析。
 */
final class PostStatisticsRepository implements PostStatisticsRepositoryInterface
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    public function getTotalPostsCount(StatisticsPeriod $period, ?string $status = null): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM posts WHERE created_at >= :start_date AND created_at <= :end_date';
            $params = [
                'start_date' => $period->startTime->format('Y-m-d H:i:s'),
                'end_date' => $period->endTime->format('Y-m-d H:i:s'),
            ];

            if ($status !== null) {
                $sql .= ' AND status = :status';
                $params['status'] = $status;
            }

            $stmt = $this->db->prepare($sql);
            $this->bindParams($stmt, $params);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章總數統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsCountByStatus(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT status, COUNT(*) as count 
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    GROUP BY status
                    ORDER BY count DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.string, cast.int */
                $result[(string) $row['status']] = (int) $row['count'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章狀態統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsCountBySource(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT creation_source, COUNT(*) as count 
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    GROUP BY creation_source
                    ORDER BY count DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.string, cast.int */
                $source = $row['creation_source'] ?? 'unknown';
                $result[(string) $source] = (int) $row['count'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章來源統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsCountBySourceType(
        StatisticsPeriod $period,
        SourceType $sourceType,
        ?string $status = null,
    ): int {
        try {
            $sql = 'SELECT COUNT(*) FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    AND creation_source = :source';
            $params = [
                'start_date' => $period->startTime->format('Y-m-d H:i:s'),
                'end_date' => $period->endTime->format('Y-m-d H:i:s'),
                'source' => $sourceType->code,
            ];

            if ($status !== null) {
                $sql .= ' AND status = :status';
                $params['status'] = $status;
            }

            $stmt = $this->db->prepare($sql);
            $this->bindParams($stmt, $params);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('取得指定來源文章數量失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostViewsStatistics(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT 
                        COALESCE(SUM(views_count), 0) as total_views,
                        COUNT(DISTINCT CASE WHEN views_count > 0 THEN id END) as posts_with_views,
                        COUNT(*) as total_posts,
                        ROUND(AVG(COALESCE(views_count, 0)), 2) as avg_views_per_post
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return ['total_views' => 0, 'unique_views' => 0, 'avg_views_per_post' => 0.0];
            }

            /** @phpstan-ignore cast.int, cast.double */
            return [
                'total_views' => (int) $row['total_views'],
                'unique_views' => (int) $row['posts_with_views'], // 使用有瀏覽量的文章數作為 unique_views 的近似值
                'avg_views_per_post' => (float) $row['avg_views_per_post'],
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章瀏覽量統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPopularPosts(StatisticsPeriod $period, int $limit = 10, string $metric = 'views'): array
    {
        if ($limit <= 0 || $limit > 100) {
            throw new InvalidArgumentException('查詢數量必須在 1-100 之間');
        }

        $allowedMetrics = ['views', 'comments', 'likes'];
        if (!in_array($metric, $allowedMetrics, true)) {
            throw new InvalidArgumentException('不支援的排序指標: ' . $metric);
        }

        try {
            // 根據指標選擇排序欄位
            $orderField = match ($metric) {
                'views' => 'views_count',
                'comments' => 'COALESCE(comments_count, 0)',
                'likes' => 'COALESCE(likes_count, 0)',
            };

            $sql = "SELECT id as post_id, title, {$orderField} as metric_value
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    AND status = 'published'
                    ORDER BY {$orderField} DESC, created_at DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.int, cast.string */
                $result[] = [
                    'post_id' => (int) $row['post_id'],
                    'title' => (string) $row['title'],
                    'metric_value' => (int) $row['metric_value'],
                ];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得熱門文章失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsCountByUser(StatisticsPeriod $period, int $limit = 10): array
    {
        if ($limit <= 0 || $limit > 50) {
            throw new InvalidArgumentException('查詢數量必須在 1-50 之間');
        }

        try {
            $sql = 'SELECT 
                        user_id,
                        COUNT(*) as posts_count,
                        COALESCE(SUM(views_count), 0) as total_views
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    GROUP BY user_id
                    ORDER BY posts_count DESC, total_views DESC
                    LIMIT :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @phpstan-ignore offsetAccess.nonOffsetAccessible, cast.int */
                $result[] = [
                    'user_id' => (int) $row['user_id'],
                    'posts_count' => (int) $row['posts_count'],
                    'total_views' => (int) $row['total_views'],
                ];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得使用者文章統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsPublishTimeDistribution(StatisticsPeriod $period, string $groupBy = 'day'): array
    {
        $allowedGroupBy = ['hour', 'day', 'week', 'month'];
        if (!in_array($groupBy, $allowedGroupBy, true)) {
            throw new InvalidArgumentException('不支援的分組方式: ' . $groupBy);
        }

        try {
            $groupByClause = match ($groupBy) {
                'hour' => 'HOUR(created_at)',
                'day' => 'DATE(created_at)',
                'week' => 'YEARWEEK(created_at)',
                'month' => 'DATE_FORMAT(created_at, "%Y-%m")',
            };

            $sql = "SELECT {$groupByClause} as time_period, COUNT(*) as count
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    GROUP BY {$groupByClause}
                    ORDER BY time_period";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[(string) $row['time_period']] = (int) $row['count'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章發布時間分布統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsGrowthTrend(StatisticsPeriod $currentPeriod, StatisticsPeriod $previousPeriod): array
    {
        try {
            $currentCount = $this->getTotalPostsCount($currentPeriod);
            $previousCount = $this->getTotalPostsCount($previousPeriod);

            $growthCount = $currentCount - $previousCount;
            $growthRate = $previousCount > 0 ? ($growthCount / $previousCount) * 100 : 0.0;

            return [
                'current' => $currentCount,
                'previous' => $previousCount,
                'growth_rate' => round($growthRate, 2),
                'growth_count' => $growthCount,
            ];
        } catch (Exception $e) {
            throw new RuntimeException('取得文章成長趨勢失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsLengthStatistics(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT 
                        ROUND(AVG(CHAR_LENGTH(content)), 2) as avg_length,
                        MIN(CHAR_LENGTH(content)) as min_length,
                        MAX(CHAR_LENGTH(content)) as max_length,
                        SUM(CHAR_LENGTH(content)) as total_chars
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date 
                    AND content IS NOT NULL';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return ['avg_length' => 0.0, 'min_length' => 0, 'max_length' => 0, 'total_chars' => 0];
            }

            return [
                'avg_length' => (float) ($row['avg_length'] ?? 0.0),
                'min_length' => (int) ($row['min_length'] ?? 0),
                'max_length' => (int) ($row['max_length'] ?? 0),
                'total_chars' => (int) ($row['total_chars'] ?? 0),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章長度統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostsCountByLengthRange(StatisticsPeriod $period, array $lengthRanges): array
    {
        if (empty($lengthRanges)) {
            throw new InvalidArgumentException('字數範圍定義不能為空');
        }

        try {
            $result = [];
            foreach ($lengthRanges as $rangeName => $range) {
                if (!isset($range['min'], $range['max'])) {
                    throw new InvalidArgumentException("字數範圍 '{$rangeName}' 必須包含 min 和 max 值");
                }

                $sql = 'SELECT COUNT(*) FROM posts 
                        WHERE created_at >= :start_date AND created_at <= :end_date 
                        AND CHAR_LENGTH(content) >= :min_length 
                        AND CHAR_LENGTH(content) <= :max_length';

                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
                $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
                $stmt->bindValue(':min_length', (int) $range['min'], PDO::PARAM_INT);
                $stmt->bindValue(':max_length', (int) $range['max'], PDO::PARAM_INT);
                $stmt->execute();

                $result[(string) $rangeName] = (int) $stmt->fetchColumn();
            }

            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章字數範圍統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPinnedPostsStatistics(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT 
                        SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) as pinned_count,
                        SUM(CASE WHEN is_pinned = 0 OR is_pinned IS NULL THEN 1 ELSE 0 END) as unpinned_count,
                        SUM(CASE WHEN is_pinned = 1 THEN COALESCE(views_count, 0) ELSE 0 END) as pinned_views
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return ['pinned_count' => 0, 'unpinned_count' => 0, 'pinned_views' => 0];
            }

            return [
                'pinned_count' => (int) ($row['pinned_count'] ?? 0),
                'unpinned_count' => (int) ($row['unpinned_count'] ?? 0),
                'pinned_views' => (int) ($row['pinned_views'] ?? 0),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('取得置頂文章統計失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hasDataForPeriod(StatisticsPeriod $period): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM posts WHERE created_at >= :start_date AND created_at <= :end_date LIMIT 1';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('檢查資料存在性失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPostActivitySummary(StatisticsPeriod $period): array
    {
        try {
            $sql = 'SELECT 
                        COUNT(*) as total_posts,
                        SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published_posts,
                        SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_posts,
                        SUM(COALESCE(views_count, 0)) as total_views,
                        COUNT(DISTINCT user_id) as active_authors
                    FROM posts 
                    WHERE created_at >= :start_date AND created_at <= :end_date';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':start_date', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                $basicStats = [
                    'total_posts' => 0,
                    'published_posts' => 0,
                    'draft_posts' => 0,
                    'total_views' => 0,
                    'active_authors' => 0,
                ];
            } else {
                $basicStats = [
                    'total_posts' => (int) $row['total_posts'],
                    'published_posts' => (int) $row['published_posts'],
                    'draft_posts' => (int) $row['draft_posts'],
                    'total_views' => (int) $row['total_views'],
                    'active_authors' => (int) $row['active_authors'],
                ];
            }

            // 取得熱門來源
            $popularSources = $this->getPostsCountBySource($period);

            return array_merge($basicStats, [
                'popular_sources' => $popularSources,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('取得文章活動摘要失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 綁定查詢參數.
     *
     * @param array<string, mixed> $params
     */
    private function bindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = match (gettype($value)) {
                'integer' => PDO::PARAM_INT,
                'boolean' => PDO::PARAM_BOOL,
                'NULL' => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }
}
