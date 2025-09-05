<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Database\DatabaseConnection;
use PDO;
use RuntimeException;
use Throwable;

/**
 * 文章統計資料存取實作類別.
 *
 * 專門處理文章相關的統計資料存取與計算，
 * 使用原生 SQL 提供高效能的統計查詢。
 *
 * 設計原則：
 * - 使用原生 SQL 進行複雜統計查詢
 * - 專注於文章相關的統計分析
 * - 提供高效能的資料聚合
 * - 完整的錯誤處理和類型安全
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-05
 */
final class PostStatisticsRepository implements PostStatisticsRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    /**
     * 計算指定週期內的文章總數.
     */
    public function countPostsByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COUNT(*)
                FROM posts
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
            throw new RuntimeException("計算文章總數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算指定週期內的文章觀看總次數.
     */
    public function countViewsByPeriod(StatisticsPeriod $period): int
    {
        try {
            $sql = '
                SELECT COALESCE(SUM(views), 0)
                FROM posts
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
            throw new RuntimeException("計算觀看總次數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算指定週期內的不重複觀看人數.
     */
    public function countUniqueViewersByPeriod(StatisticsPeriod $period): int
    {
        try {
            // 由於目前沒有觀看記錄表，暫時基於文章數和觀看數進行估算
            $sql = '
                SELECT COUNT(DISTINCT user_ip)
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                    AND user_ip IS NOT NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException("計算不重複觀看人數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 依來源類型統計文章觀看次數.
     */
    public function getViewStatisticsBySource(
        StatisticsPeriod $period,
        ?SourceType $sourceType = null,
    ): array {
        try {
            $sql = '
                SELECT
                    source_type,
                    COUNT(*) as post_count,
                    COALESCE(SUM(views), 0) as view_count,
                    COUNT(DISTINCT user_ip) as unique_viewers
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
            ';

            $params = [
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ];

            if ($sourceType !== null) {
                $sql .= ' AND source_type = ?';
                $params[] = $sourceType->value;
            }

            $sql .= ' GROUP BY source_type ORDER BY view_count DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = [];
            $totalViews = 0;

            // 先計算總觀看數
            $allResults = $stmt->fetchAll();
            foreach ($allResults as $row) {
                $totalViews += (int) $row['view_count'];
            }

            // 再計算百分比
            foreach ($allResults as $row) {
                $viewCount = (int) $row['view_count'];
                $percentage = $totalViews > 0 ? round(($viewCount / $totalViews) * 100, 2) : 0;

                $results[] = [
                    'source_type' => $row['source_type'],
                    'view_count' => $viewCount,
                    'unique_viewers' => (int) $row['unique_viewers'],
                    'percentage' => $percentage,
                ];
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("統計來源觀看次數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得指定週期內最受歡迎的文章列表.
     */
    public function getPopularPosts(
        StatisticsPeriod $period,
        int $limit = 10,
    ): array {
        try {
            $sql = "
                SELECT
                    id as post_id,
                    title as post_title,
                    views as view_count,
                    1 as unique_viewers, -- 暫時固定值
                    'unknown' as author,
                    created_at
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                ORDER BY views DESC, created_at DESC
                LIMIT ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $limit,
            ]);

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            throw new RuntimeException("查詢熱門文章時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得文章發布統計（按日期分組）.
     */
    public function getPostPublishingStats(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as post_count,
                    COALESCE(SUM(views), 0) as total_views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            throw new RuntimeException("統計文章發布資料時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算文章平均觀看次數.
     */
    public function getAverageViewsPerPost(StatisticsPeriod $period): float
    {
        try {
            $sql = '
                SELECT
                    COUNT(*) as total_posts,
                    COALESCE(SUM(views), 0) as total_views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch();
            $totalPosts = (int) $result['total_posts'];
            $totalViews = (int) $result['total_views'];

            return $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0.0;
        } catch (Throwable $e) {
            throw new RuntimeException("計算平均觀看次數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得文章觀看次數分布.
     */
    public function getViewCountDistribution(StatisticsPeriod $period): array
    {
        try {
            $sql = "
                SELECT
                    CASE
                        WHEN views = 0 THEN '0'
                        WHEN views BETWEEN 1 AND 10 THEN '1-10'
                        WHEN views BETWEEN 11 AND 50 THEN '11-50'
                        WHEN views BETWEEN 51 AND 100 THEN '51-100'
                        WHEN views BETWEEN 101 AND 500 THEN '101-500'
                        WHEN views BETWEEN 501 AND 1000 THEN '501-1000'
                        ELSE '1000+'
                    END as view_range,
                    COUNT(*) as post_count
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY
                    CASE
                        WHEN views = 0 THEN '0'
                        WHEN views BETWEEN 1 AND 10 THEN '1-10'
                        WHEN views BETWEEN 11 AND 50 THEN '11-50'
                        WHEN views BETWEEN 51 AND 100 THEN '51-100'
                        WHEN views BETWEEN 101 AND 500 THEN '101-500'
                        WHEN views BETWEEN 501 AND 1000 THEN '501-1000'
                        ELSE '1000+'
                    END
                ORDER BY
                    CASE
                        WHEN views = 0 THEN 1
                        WHEN views BETWEEN 1 AND 10 THEN 2
                        WHEN views BETWEEN 11 AND 50 THEN 3
                        WHEN views BETWEEN 51 AND 100 THEN 4
                        WHEN views BETWEEN 101 AND 500 THEN 5
                        WHEN views BETWEEN 501 AND 1000 THEN 6
                        ELSE 7
                    END
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();
            $totalPosts = array_sum(array_column($results, 'post_count'));

            // 計算百分比
            foreach ($results as &$result) {
                $result['percentage'] = $totalPosts > 0
                    ? round(($result['post_count'] / $totalPosts) * 100, 2)
                    : 0;
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("統計觀看次數分布時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算文章互動率相關統計.
     */
    public function getEngagementStats(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(*) as total_posts,
                    COALESCE(SUM(views), 0) as total_views,
                    COALESCE(AVG(views), 0) as avg_views_per_post,
                    COALESCE(MAX(views), 0) as max_views,
                    COALESCE(MIN(views), 0) as min_views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch();

            // 計算中位數
            $medianSql = '
                SELECT views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                ORDER BY views
            ';

            $medianStmt = $this->pdo->prepare($medianSql);
            $medianStmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $viewCounts = $medianStmt->fetchAll(PDO::FETCH_COLUMN);
            $medianViews = $this->calculateMedian($viewCounts);

            return [
                'total_posts' => (int) $result['total_posts'],
                'total_views' => (int) $result['total_views'],
                'avg_views_per_post' => round((float) $result['avg_views_per_post'], 2),
                'median_views' => (int) $medianViews,
                'engagement_rate' => 0.0, // 暫時固定值，需要額外的互動資料
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算互動統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得文章標籤使用統計.
     */
    public function getTagUsageStats(
        StatisticsPeriod $period,
        int $limit = 20,
    ): array {
        // 由於沒有標籤系統，暫時回傳空陣列
        return [];
    }

    /**
     * 取得文章長度與觀看次數關聯統計.
     */
    public function getContentLengthStats(StatisticsPeriod $period): array
    {
        try {
            $sql = "
                SELECT
                    CASE
                        WHEN LENGTH(content) < 500 THEN '短文 (<500字)'
                        WHEN LENGTH(content) BETWEEN 500 AND 1500 THEN '中文 (500-1500字)'
                        WHEN LENGTH(content) BETWEEN 1501 AND 3000 THEN '長文 (1501-3000字)'
                        ELSE '超長文 (>3000字)'
                    END as length_range,
                    COUNT(*) as post_count,
                    COALESCE(AVG(views), 0) as avg_views,
                    COALESCE(SUM(views), 0) as total_views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY
                    CASE
                        WHEN LENGTH(content) < 500 THEN '短文 (<500字)'
                        WHEN LENGTH(content) BETWEEN 500 AND 1500 THEN '中文 (500-1500字)'
                        WHEN LENGTH(content) BETWEEN 1501 AND 3000 THEN '長文 (1501-3000字)'
                        ELSE '超長文 (>3000字)'
                    END
                ORDER BY avg_views DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();

            // 格式化結果
            foreach ($results as &$result) {
                $result['avg_views'] = round((float) $result['avg_views'], 2);
                $result['post_count'] = (int) $result['post_count'];
                $result['total_views'] = (int) $result['total_views'];
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("統計文章長度時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算新舊文章觀看比例.
     */
    public function getNewVsOldPostsRatio(
        StatisticsPeriod $period,
        int $newPostDays = 30,
    ): array {
        try {
            $cutoffDate = $period->endDate->modify("-{$newPostDays} days");

            $sql = "
                SELECT
                    CASE
                        WHEN created_at >= ? THEN 'new'
                        ELSE 'old'
                    END as post_age,
                    COALESCE(SUM(views), 0) as total_views
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY
                    CASE
                        WHEN created_at >= ? THEN 'new'
                        ELSE 'old'
                    END
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $cutoffDate->format('Y-m-d H:i:s'),
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
                $cutoffDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();

            $newPostsViews = 0;
            $oldPostsViews = 0;

            foreach ($results as $result) {
                if ($result['post_age'] === 'new') {
                    $newPostsViews = (int) $result['total_views'];
                } else {
                    $oldPostsViews = (int) $result['total_views'];
                }
            }

            $totalViews = $newPostsViews + $oldPostsViews;

            return [
                'new_posts_views' => $newPostsViews,
                'old_posts_views' => $oldPostsViews,
                'new_posts_percentage' => $totalViews > 0
                    ? round(($newPostsViews / $totalViews) * 100, 2)
                    : 0,
                'old_posts_percentage' => $totalViews > 0
                    ? round(($oldPostsViews / $totalViews) * 100, 2)
                    : 0,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算新舊文章比例時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得文章觀看時間趨勢分析.
     */
    public function getViewingTimeTrends(StatisticsPeriod $period): array
    {
        try {
            $sql = "
                SELECT
                    strftime('%H', created_at) as hour,
                    COUNT(*) as post_count,
                    COALESCE(SUM(views), 0) as view_count,
                    COUNT(DISTINCT user_ip) as unique_viewers,
                    0.0 as avg_session_duration
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                GROUP BY strftime('%H', created_at)
                ORDER BY hour
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $results = $stmt->fetchAll();

            // 格式化結果
            foreach ($results as &$result) {
                $result['hour'] = (int) $result['hour'];
                $result['view_count'] = (int) $result['view_count'];
                $result['unique_viewers'] = (int) $result['unique_viewers'];
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("分析觀看時間趨勢時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算文章搜尋關鍵字統計.
     */
    public function getSearchKeywordStats(
        StatisticsPeriod $period,
        int $limit = 50,
    ): array {
        // 由於沒有搜尋記錄系統，暫時回傳空陣列
        return [];
    }

    /**
     * 取得文章分類瀏覽統計.
     */
    public function getCategoryViewStats(StatisticsPeriod $period): array
    {
        // 由於沒有分類系統，暫時回傳空陣列
        return [];
    }

    /**
     * 計算回訪讀者統計.
     */
    public function getReturningReaderStats(StatisticsPeriod $period): array
    {
        try {
            $sql = '
                SELECT
                    COUNT(DISTINCT user_ip) as total_unique_viewers,
                    COUNT(user_ip) as total_interactions,
                    0 as returning_viewers,
                    0 as new_viewers,
                    0.0 as return_rate
                FROM posts
                WHERE created_at >= ?
                    AND created_at <= ?
                    AND deleted_at IS NULL
                    AND user_ip IS NOT NULL
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch();

            return [
                'total_unique_viewers' => (int) $result['total_unique_viewers'],
                'returning_viewers' => 0, // 需要更複雜的查詢邏輯
                'new_viewers' => (int) $result['total_unique_viewers'],
                'return_rate' => 0.0,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算回訪讀者統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得文章社交媒體分享統計.
     */
    public function getSocialSharingStats(
        StatisticsPeriod $period,
        int $limit = 10,
    ): array {
        // 由於沒有分享記錄系統，暫時回傳空陣列
        return [];
    }

    /**
     * 計算跳出率統計.
     */
    public function getBounceRateStats(StatisticsPeriod $period): array
    {
        // 由於沒有會話追蹤系統，暫時回傳預設值
        return [
            'total_sessions' => 0,
            'bounce_sessions' => 0,
            'bounce_rate' => 0.0,
            'avg_pages_per_session' => 0.0,
        ];
    }

    /**
     * 取得文章載入效能統計.
     */
    public function getLoadPerformanceStats(StatisticsPeriod $period): array
    {
        // 由於沒有效能追蹤系統，暫時回傳預設值
        return [
            'avg_load_time' => 0.0,
            'median_load_time' => 0.0,
            'slow_loads_count' => 0,
            'slow_loads_percentage' => 0.0,
        ];
    }

    /**
     * 計算文章完成閱讀率.
     */
    public function getReadCompletionStats(
        StatisticsPeriod $period,
        int $limit = 20,
    ): array {
        // 由於沒有閱讀追蹤系統，暫時回傳空陣列
        return [];
    }

    /**
     * 取得行動裝置瀏覽統計.
     */
    public function getMobileViewStats(StatisticsPeriod $period): array
    {
        // 由於沒有裝置類型追蹤，暫時回傳預設值
        return [
            'desktop_views' => 0,
            'mobile_views' => 0,
            'tablet_views' => 0,
            'mobile_percentage' => 0.0,
            'desktop_percentage' => 0.0,
            'tablet_percentage' => 0.0,
        ];
    }

    /**
     * 計算中位數.
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);

        if ($count % 2 === 0) {
            $mid1 = $values[($count / 2) - 1];
            $mid2 = $values[$count / 2];

            return ($mid1 + $mid2) / 2;
        } else {
            return (float) $values[intval($count / 2)];
        }
    }
}
