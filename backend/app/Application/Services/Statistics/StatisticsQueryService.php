<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Application\Services\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Application\Services\Statistics\DTOs\StatisticsQueryDTO;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * 統計查詢服務.
 *
 * 專門處理統計資料的查詢操作，遵循 CQRS 原則，與寫操作分離
 * 提供高效能的統計資料讀取，支援分頁、過濾、快取等功能
 */
final class StatisticsQueryService
{
    private const CACHE_TTL = 3600; // 1 小時

    private const MAX_QUERY_DAYS = 365; // 最大查詢範圍：1 年

    public function __construct(
        /** @phpstan-ignore-next-line property.onlyWritten */
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly StatisticsCacheServiceInterface $cacheService,
        private readonly LoggerInterface $logger,
        private readonly PDO $db,
    ) {}

    /**
     * 取得統計概覽資料.
     *
     * @param StatisticsQueryDTO $query 查詢參數
     * @throws InvalidArgumentException
     */
    public function getOverview(StatisticsQueryDTO $query): StatisticsOverviewDTO
    {
        $this->validateQuery($query);

        $cacheKey = $this->generateCacheKey('overview', $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached instanceof StatisticsOverviewDTO) {
                $this->logger->debug('統計概覽快取命中', ['cache_key' => $cacheKey]);

                return $cached;
            }

            $this->logger->debug('開始查詢統計概覽', [
                'query' => [
                    'start_date' => $query->getStartDate()?->format('Y-m-d'),
                    'end_date' => $query->getEndDate()?->format('Y-m-d'),
                    'filters' => $query->getFilters(),
                ],
            ]);

            $overview = $this->buildOverviewFromRepository($query);

            $this->cacheService->put($cacheKey, $overview, self::CACHE_TTL, ['statistics', 'overview']);
            $this->logger->debug('統計概覽已快取', ['cache_key' => $cacheKey]);

            return $overview;
        } catch (Exception $e) {
            $this->logger->error('統計概覽查詢失敗', [
                'error' => $e->getMessage(),
                'query' => [
                    'start_date' => $query->getStartDate()?->format('Y-m-d'),
                    'end_date' => $query->getEndDate()?->format('Y-m-d'),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * 取得文章統計資料（分頁）.
     */
    public function getPostStatistics(StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        $this->validateQuery($query);

        $cacheKey = $this->generateCacheKey('posts', $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached instanceof PaginatedStatisticsDTO) {
                $this->logger->debug('文章統計快取命中', ['cache_key' => $cacheKey]);

                return $cached;
            }

            $this->logger->debug('開始查詢文章統計', [
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
                'sort' => $query->getSortBy() . ' ' . $query->getSortDirection(),
            ]);

            $result = $this->buildPostStatisticsFromRepository($query);

            $this->cacheService->put($cacheKey, $result, self::CACHE_TTL, ['statistics', 'posts']);
            $this->logger->debug('文章統計已快取', ['cache_key' => $cacheKey]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('文章統計查詢失敗', [
                'error' => $e->getMessage(),
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
            ]);

            throw $e;
        }
    }

    /**
     * 取得來源分佈統計.
     */
    public function getSourceDistribution(StatisticsQueryDTO $query): array
    {
        $this->validateQuery($query);

        $cacheKey = $this->generateCacheKey('sources', $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                $this->logger->debug('來源分佈快取命中', ['cache_key' => $cacheKey]);

                return $cached;
            }

            $this->logger->debug('開始查詢來源分佈');

            $distribution = $this->buildSourceDistributionFromRepository($query);

            $this->cacheService->put($cacheKey, $distribution, self::CACHE_TTL, ['statistics', 'sources']);
            $this->logger->debug('來源分佈已快取', ['cache_key' => $cacheKey]);

            return $distribution;
        } catch (Exception $e) {
            $this->logger->error('來源分佈查詢失敗', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 取得使用者統計資料（分頁）.
     */
    public function getUserStatistics(StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        $this->validateQuery($query);

        $cacheKey = $this->generateCacheKey('users', $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached instanceof PaginatedStatisticsDTO) {
                $this->logger->debug('使用者統計快取命中', ['cache_key' => $cacheKey]);

                return $cached;
            }

            $this->logger->debug('開始查詢使用者統計');

            $result = $this->buildUserStatisticsFromRepository($query);

            $this->cacheService->put($cacheKey, $result, self::CACHE_TTL, ['statistics', 'users']);
            $this->logger->debug('使用者統計已快取', ['cache_key' => $cacheKey]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('使用者統計查詢失敗', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 取得熱門內容統計.
     */
    public function getPopularContent(StatisticsQueryDTO $query): array
    {
        $this->validateQuery($query);

        $cacheKey = $this->generateCacheKey('popular', $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                $this->logger->debug('熱門內容快取命中', ['cache_key' => $cacheKey]);

                return $cached;
            }

            $this->logger->debug('開始查詢熱門內容');

            $popular = $this->buildPopularContentFromRepository($query);

            $this->cacheService->put($cacheKey, $popular, self::CACHE_TTL, ['statistics', 'popular']);
            $this->logger->debug('熱門內容已快取', ['cache_key' => $cacheKey]);

            return $popular;
        } catch (Exception $e) {
            $this->logger->error('熱門內容查詢失敗', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 搜尋統計資料.
     */
    public function search(string $keyword, StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        if (trim($keyword) === '') {
            throw new InvalidArgumentException('搜尋關鍵字不能為空');
        }

        $this->validateQuery($query);

        $cacheKey = $this->generateSearchCacheKey($keyword, $query);

        try {
            $cached = $this->cacheService->get($cacheKey);
            if ($cached instanceof PaginatedStatisticsDTO) {
                $this->logger->debug('統計搜尋快取命中', ['cache_key' => $cacheKey, 'keyword' => $keyword]);

                return $cached;
            }

            $this->logger->debug('開始搜尋統計資料', ['keyword' => $keyword]);

            $result = $this->buildSearchResultsFromRepository($keyword, $query);

            $this->cacheService->put($cacheKey, $result, self::CACHE_TTL, ['statistics', 'search']);
            $this->logger->debug('統計搜尋已快取', ['cache_key' => $cacheKey, 'keyword' => $keyword]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('統計搜尋失敗', [
                'error' => $e->getMessage(),
                'keyword' => $keyword,
            ]);

            throw $e;
        }
    }

    /**
     * 驗證查詢參數.
     */
    private function validateQuery(StatisticsQueryDTO $query): void
    {
        if ($query->hasDateRange() && $query->getDateRangeInDays() > self::MAX_QUERY_DAYS) {
            throw new InvalidArgumentException(
                sprintf('查詢時間範圍不能超過 %d 天', self::MAX_QUERY_DAYS),
            );
        }

        if ($query->getLimit() > 100) {
            throw new InvalidArgumentException('每頁筆數不能超過 100');
        }
    }

    /**
     * 生成快取鍵.
     */
    private function generateCacheKey(string $type, StatisticsQueryDTO $query): string
    {
        $parts = [
            'stats',
            $type,
            md5(serialize([
                'start' => $query->getStartDate()?->format('Y-m-d'),
                'end' => $query->getEndDate()?->format('Y-m-d'),
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
                'sort' => $query->getSortBy() . ':' . $query->getSortDirection(),
                'filters' => $query->getFilters(),
            ])),
        ];

        return implode(':', $parts);
    }

    /**
     * 生成搜尋快取鍵.
     */
    private function generateSearchCacheKey(string $keyword, StatisticsQueryDTO $query): string
    {
        $parts = [
            'stats',
            'search',
            md5($keyword),
            md5(serialize([
                'start' => $query->getStartDate()?->format('Y-m-d'),
                'end' => $query->getEndDate()?->format('Y-m-d'),
                'page' => $query->getPage(),
                'limit' => $query->getLimit(),
                'sort' => $query->getSortBy() . ':' . $query->getSortDirection(),
                'filters' => $query->getFilters(),
            ])),
        ];

        return implode(':', $parts);
    }

    /**
     * 從 Repository 建構概覽資料.
     */
    private function buildOverviewFromRepository(StatisticsQueryDTO $query): StatisticsOverviewDTO
    {
        // 準備日期範圍
        $startDate = $query->getStartDate()?->format('Y-m-d H:i:s');
        $endDate = $query->getEndDate()?->format('Y-m-d H:i:s');

        // 如果沒有指定日期範圍，使用最近30天
        if (!$startDate || !$endDate) {
            $endDate = new DateTimeImmutable()->format('Y-m-d 23:59:59');
            $startDate = new DateTimeImmutable('-30 days')->format('Y-m-d 00:00:00');
        }

        // 查詢總文章數
        $totalPosts = $this->queryTotalPosts($startDate, $endDate);

        // 查詢活躍使用者數
        $activeUsers = $this->queryActiveUsers($startDate, $endDate);

        // 查詢新使用者數
        $newUsers = $this->queryNewUsers($startDate, $endDate);

        // 查詢總瀏覽量
        $totalViews = $this->queryTotalViews($startDate, $endDate);

        return new StatisticsOverviewDTO(
            totalPosts: $totalPosts,
            activeUsers: $activeUsers,
            newUsers: $newUsers,
            postActivity: [
                'total_posts' => $totalPosts,
                'published_posts' => $this->queryPublishedPosts($startDate, $endDate),
                'draft_posts' => $this->queryDraftPosts($startDate, $endDate),
            ],
            userActivity: [
                'total_users' => $this->queryTotalUsers(),
                'active_users' => $activeUsers,
                'new_users' => $newUsers,
            ],
            engagementMetrics: [
                'posts_per_active_user' => $activeUsers > 0 ? round($totalPosts / $activeUsers, 2) : 0.0,
                'user_growth_rate' => $this->calculateUserGrowthRate($startDate, $endDate),
            ],
            periodSummary: [
                'type' => $this->determinePeriodType($startDate, $endDate),
                'duration_days' => $this->calculateDurationDays($startDate, $endDate),
            ],
        );
    }

    /**
     * 查詢指定時間範圍內的總文章數.
     */
    private function queryTotalPosts(?string $startDate, ?string $endDate): int
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL';
        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND created_at BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢已發布文章數.
     */
    private function queryPublishedPosts(?string $startDate, ?string $endDate): int
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = \'published\'';
        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND publish_date BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢草稿文章數.
     */
    private function queryDraftPosts(?string $startDate, ?string $endDate): int
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL AND status = \'draft\'';
        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND created_at BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢活躍使用者數（在時間範圍內有登入或發文行為）.
     */
    private function queryActiveUsers(?string $startDate, ?string $endDate): int
    {
        if (!$startDate || !$endDate) {
            // 如果沒有時間範圍，返回所有使用者
            return $this->queryTotalUsers();
        }

        $sql = '
            SELECT COUNT(DISTINCT user_id) 
            FROM (
                SELECT user_id FROM user_activity_logs 
                WHERE occurred_at BETWEEN :start_date AND :end_date
                    AND user_id IS NOT NULL
                UNION
                SELECT user_id FROM posts 
                WHERE created_at BETWEEN :start_date AND :end_date
                    AND user_id IS NOT NULL
            ) AS active_users
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢新使用者數.
     */
    private function queryNewUsers(?string $startDate, ?string $endDate): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE 1=1';
        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND created_at BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢總使用者數.
     */
    private function queryTotalUsers(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');

        if ($stmt === false) {
            throw new RuntimeException('查詢總使用者數失敗');
        }

        return (int) $stmt->fetchColumn();
    }

    /**
     * 查詢總瀏覽量.
     */
    private function queryTotalViews(?string $startDate, ?string $endDate): int
    {
        $sql = 'SELECT COALESCE(SUM(views), 0) FROM posts WHERE deleted_at IS NULL';
        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND publish_date BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 計算使用者成長率.
     */
    private function calculateUserGrowthRate(?string $startDate, ?string $endDate): float
    {
        if (!$startDate || !$endDate) {
            return 0.0;
        }

        // 計算前一個週期的使用者數
        $duration = new DateTimeImmutable($endDate)->diff(new DateTimeImmutable($startDate))->days;
        $previousStart = new DateTimeImmutable($startDate)->modify("-{$duration} days")->format('Y-m-d H:i:s');
        $previousEnd = $startDate;

        $currentUsers = $this->queryNewUsers($startDate, $endDate);
        $previousUsers = $this->queryNewUsers($previousStart, $previousEnd);

        if ($previousUsers === 0) {
            return $currentUsers > 0 ? 100.0 : 0.0;
        }

        return round((($currentUsers - $previousUsers) / $previousUsers) * 100, 2);
    }

    /**
     * 決定週期類型.
     */
    private function determinePeriodType(?string $startDate, ?string $endDate): string
    {
        if (!$startDate || !$endDate) {
            return 'custom';
        }

        $days = $this->calculateDurationDays($startDate, $endDate);

        if ($days <= 1) {
            return 'daily';
        } elseif ($days <= 7) {
            return 'weekly';
        } elseif ($days <= 31) {
            return 'monthly';
        } else {
            return 'custom';
        }
    }

    /**
     * 計算持續天數.
     */
    private function calculateDurationDays(?string $startDate, ?string $endDate): int
    {
        if (!$startDate || !$endDate) {
            return 0;
        }

        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        return $start->diff($end)->days + 1;
    }

    /**
     * 從 Repository 建構文章統計資料.
     */
    private function buildPostStatisticsFromRepository(StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        // 模擬資料，實際實作會從 Repository 查詢
        $posts = [];
        for ($i = 1; $i <= $query->getLimit(); $i++) {
            $posts[] = [
                'id' => 'post-' . $i,
                'title' => 'Sample Post ' . $i,
                'view_count' => rand(100, 1000),
                'like_count' => rand(10, 100),
                'comment_count' => rand(0, 50),
                'category' => 'Technology',
                'creation_source' => 'web',
                'created_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];
        }

        return new PaginatedStatisticsDTO(
            data: $posts,
            totalCount: 1000,
            currentPage: $query->getPage(),
            perPage: $query->getLimit(),
        );
    }

    /**
     * 從 Repository 建構來源分佈資料.
     */
    private function buildSourceDistributionFromRepository(StatisticsQueryDTO $query): array
    {
        // 模擬資料 - 建立簡化版本的來源分佈
        return [
            [
                'source' => 'web',
                'count' => 800,
                'percentage' => 80.0,
            ],
            [
                'source' => 'api',
                'count' => 150,
                'percentage' => 15.0,
            ],
            [
                'source' => 'mobile',
                'count' => 50,
                'percentage' => 5.0,
            ],
        ];
    }

    /**
     * 從 Repository 建構使用者統計資料.
     */
    private function buildUserStatisticsFromRepository(StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        // 模擬資料
        $users = [];
        for ($i = 1; $i <= $query->getLimit(); $i++) {
            $users[] = [
                'id' => 'user-' . $i,
                'username' => 'user' . $i,
                'post_count' => rand(1, 20),
                'view_count' => rand(100, 5000),
                'like_count' => rand(10, 500),
                'last_active_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];
        }

        return new PaginatedStatisticsDTO(
            data: $users,
            totalCount: 200,
            currentPage: $query->getPage(),
            perPage: $query->getLimit(),
        );
    }

    /**
     * 從 Repository 建構熱門內容資料.
     */
    private function buildPopularContentFromRepository(StatisticsQueryDTO $query): array
    {
        $startDate = $query->getStartDate()?->format('Y-m-d H:i:s');
        $endDate = $query->getEndDate()?->format('Y-m-d H:i:s');
        $limit = min($query->getLimit(), 50); // 最多50筆

        // 查詢最熱門的文章 (SQLite 使用 strftime 而非 DATE_FORMAT)
        $sql = '
            SELECT 
                p.id,
                p.title,
                p.views,
                strftime(\'%Y-%m-%d\', p.publish_date) as publish_date
            FROM posts p
            WHERE p.deleted_at IS NULL 
                AND p.status = \'published\'
        ';

        $params = [];

        if ($startDate && $endDate) {
            $sql .= ' AND p.publish_date BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $sql .= ' ORDER BY p.views DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 從 Repository 建構搜尋結果.
     */
    private function buildSearchResultsFromRepository(string $keyword, StatisticsQueryDTO $query): PaginatedStatisticsDTO
    {
        // 模擬搜尋結果，實際會根據關鍵字搜尋統計資料
        $results = [];
        for ($i = 1; $i <= min(10, $query->getLimit()); $i++) {
            $results[] = [
                'type' => 'post',
                'id' => 'post-' . $i,
                'title' => sprintf('Post containing "%s" %d', $keyword, $i),
                'relevance_score' => 0.9 - ($i * 0.1),
                'statistics' => [
                    'views' => rand(100, 1000),
                    'likes' => rand(10, 100),
                ],
            ];
        }

        return new PaginatedStatisticsDTO(
            data: $results,
            totalCount: 50,
            currentPage: $query->getPage(),
            perPage: $query->getLimit(),
            metadata: ['search_keyword' => $keyword],
        );
    }
}
