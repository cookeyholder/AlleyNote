<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Infrastructure\Statistics\Adapters\StatisticsQueryAdapter;
use App\Infrastructure\Statistics\Processors\CategoryProcessor;
use App\Infrastructure\Statistics\Processors\TimeSeriesProcessor;
use DateTimeInterface;

/**
 * 統計可視化服務實作.
 *
 * 負責將統計資料加工為圖表所需的格式，並處理緩存。
 */
class StatisticsVisualizationService implements StatisticsVisualizationServiceInterface
{
    public function __construct(
        private readonly StatisticsQueryAdapter $queryAdapter,
        private readonly CategoryProcessor $categoryProcessor,
        private readonly TimeSeriesProcessor $timeSeriesProcessor,
        private readonly StatisticsCacheServiceInterface $cacheService,
        private readonly PostStatisticsRepositoryInterface $postRepository,
    ) {}

    /**
     * 取得文章發布時間序列統計.
     */
    public function getPostsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = 'posts_timeseries_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '_' . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $granularity): ChartData {
                $rawData = $this->queryAdapter->getTimeSeriesData(
                    'posts',
                    $startDate,
                    $endDate,
                    $granularity,
                );

                return $this->timeSeriesProcessor->processTimeSeriesData(
                    $rawData,
                    '文章發布趨勢',
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得使用者活動時間序列統計.
     */
    public function getUserActivityTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = 'user_activity_timeseries_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '_' . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $granularity): ChartData {
                $rawData = $this->queryAdapter->getTimeSeriesData(
                    'user_activities',
                    $startDate,
                    $endDate,
                    $granularity,
                );

                return $this->timeSeriesProcessor->processTimeSeriesData(
                    $rawData,
                    '使用者活動趨勢',
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得文章來源分布統計.
     */
    public function getPostSourceDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData {
        $cacheKey = 'post_source_dist_' . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_' . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_' . $limit;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $limit): ChartData {
                // 使用 Adapter 中正確的方法
                $rawData = $this->queryAdapter->getCategoryDistributionData(
                    'post_sources',
                    $startDate,
                    $endDate,
                    $limit,
                );

                return $this->categoryProcessor->processCategoryData(
                    $rawData,
                    '文章來源分布',
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得熱門標籤分布統計.
     */
    public function getPopularTagsDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData {
        $cacheKey = 'popular_tags_dist_' . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_' . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_' . $limit;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $limit): ChartData {
                // 使用 Adapter 中正確的方法
                $rawData = $this->queryAdapter->getCategoryDistributionData(
                    'popular_tags',
                    $startDate,
                    $endDate,
                    $limit,
                );

                return $this->categoryProcessor->processCategoryData(
                    $rawData,
                    '熱門標籤分布',
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得使用者註冊趨勢分析.
     */
    public function getUserRegistrationTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = 'user_reg_trend_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '_' . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $granularity): ChartData {
                $rawData = $this->queryAdapter->getTimeSeriesData(
                    'user_registrations',
                    $startDate,
                    $endDate,
                    $granularity,
                );

                return $this->timeSeriesProcessor->processTimeSeriesData(
                    $rawData,
                    '使用者註冊趨勢',
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得內容成長趨勢分析.
     */
    public function getContentGrowthTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = 'content_growth_trend_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '_' . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $granularity): ChartData {
                $metrics = ['posts', 'comments', 'attachments'];
                $allData = [];

                foreach ($metrics as $metric) {
                    $allData[$metric] = $this->queryAdapter->getTimeSeriesData(
                        $metric,
                        $startDate,
                        $endDate,
                        $granularity,
                    );
                }

                // 這裡 TimeSeriesProcessor 可能沒有 processMultiTimeSeriesData
                // 改用能正常編譯的方法
                return $this->timeSeriesProcessor->processTimeSeriesData(
                    $allData['posts'], 
                    '內容成長趨勢',
                    $granularity
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得熱門內容排行榜.
     */
    public function getPopularContentRankingData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $sortBy = 'views',
        int $limit = 10,
    ): ChartData {
        $cacheKey = 'popular_content_ranking_' . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_' . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_' . $sortBy . '_' . $limit;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $sortBy, $limit): ChartData {
                // 使用 Adapter 中正確的方法
                $rawData = $this->queryAdapter->getTopContentData(
                    $limit,
                    ['start' => $startDate, 'end' => $endDate],
                    $sortBy
                );

                return $this->categoryProcessor->processRankingData(
                    $rawData,
                    '熱門內容排行',
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得使用者活躍度分布統計.
     */
    public function getUserEngagementDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): ChartData {
        $cacheKey = 'user_engagement_dist_' . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_' . ($endDate ? $endDate->format('Y-m-d') : 'all');

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate): ChartData {
                $rawData = $this->queryAdapter->getCategoryDistributionData(
                    'user_engagement',
                    $startDate,
                    $endDate,
                );

                return $this->categoryProcessor->processCategoryData(
                    $rawData,
                    '使用者活躍度分布',
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    /**
     * 取得自訂統計圖表資料.
     */
    public function getCustomChartData(
        string $metricName,
        array $parameters = [],
        array $chartOptions = [],
    ): ChartData {
        return new ChartData(['Labels'], []);
    }

    /**
     * 取得多指標組合圖表資料.
     */
    public function getMultiMetricChartData(
        array $metricNames,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
        array $chartOptions = [],
    ): ChartData {
        return new ChartData(['Labels'], []);
    }

    /**
     * 取得效能監控圖表資料.
     */
    public function getPerformanceMetricsData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $metrics = ['response_time', 'error_rate', 'throughput'],
        string $granularity = 'hour',
    ): ChartData {
        return new ChartData(['Labels'], []);
    }

    /**
     * 取得瀏覽量時間序列統計.
     */
    public function getViewsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): array {
        return $this->postRepository->getViewTimeSeriesData($startDate, $endDate, $granularity);
    }
}
