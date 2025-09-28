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
use Psr\Log\LoggerInterface;

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
        // 這裡實際會調用 Repository 查詢資料庫
        // 為了演示，先返回模擬資料
        return new StatisticsOverviewDTO(
            totalPosts: 1000,
            activeUsers: 200,
            newUsers: 50,
            postActivity: [
                'total_posts' => 1000,
                'published_posts' => 800,
                'draft_posts' => 200,
            ],
            userActivity: [
                'total_users' => 200,
                'active_users' => 150,
                'new_users' => 50,
            ],
            engagementMetrics: [
                'posts_per_active_user' => 5.0,
                'user_growth_rate' => 25.0,
            ],
            periodSummary: [
                'type' => 'monthly',
                'duration_days' => 31,
            ],
        );
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
        // 模擬資料，實際會根據瀏覽數、讚數等排序
        return [
            'most_viewed_posts' => [
                ['id' => 'post-1', 'title' => 'Popular Post 1', 'views' => 5000],
                ['id' => 'post-2', 'title' => 'Popular Post 2', 'views' => 4500],
                ['id' => 'post-3', 'title' => 'Popular Post 3', 'views' => 4000],
            ],
            'trending_categories' => [
                ['name' => 'Technology', 'posts' => 150],
                ['name' => 'Science', 'posts' => 120],
                ['name' => 'Programming', 'posts' => 100],
            ],
            'active_users' => [
                ['id' => 'user-1', 'name' => 'Active User 1', 'posts' => 25],
                ['id' => 'user-2', 'name' => 'Active User 2', 'posts' => 20],
                ['id' => 'user-3', 'name' => 'Active User 3', 'posts' => 18],
            ],
        ];
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
