<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Domains\Statistics\ValueObjects\ChartData;
use App\Infrastructure\Statistics\Adapters\StatisticsQueryAdapter;
use App\Infrastructure\Statistics\Processors\CategoryProcessor;
use App\Infrastructure\Statistics\Processors\TimeSeriesProcessor;
use DateTimeInterface;

final readonly class StatisticsVisualizationService implements StatisticsVisualizationServiceInterface
{
    public function __construct(
        private StatisticsQueryAdapter $queryAdapter,
        private CategoryProcessor $categoryProcessor,
        private TimeSeriesProcessor $timeSeriesProcessor,
        private StatisticsCacheServiceInterface $cacheService,
    ) {}

    public function getPostsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        return $this->getTimeSeriesData('posts', $startDate, $endDate, $granularity);
    }

    public function getUserActivityTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        return $this->getTimeSeriesData('user_activity', $startDate, $endDate, $granularity);
    }

    public function getPostSourceDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData {
        return $this->getCategoryDistributionData('source', $startDate, $endDate, $limit);
    }

    public function getPopularTagsDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData {
        return $this->getCategoryDistributionData('tags', $startDate, $endDate, $limit);
    }

    public function getUserRegistrationTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        return $this->getTimeSeriesData('user_registration', $startDate, $endDate, $granularity);
    }

    public function getContentGrowthTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData {
        return $this->getTimeSeriesData('content_growth', $startDate, $endDate, $granularity);
    }

    public function getPopularContentRankingData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $sortBy = 'views',
        int $limit = 10,
    ): ChartData {
        $timeRange = [];
        if ($startDate !== null) {
            $timeRange['start'] = $startDate;
        }
        if ($endDate !== null) {
            $timeRange['end'] = $endDate;
        }

        return $this->getTopContentData($limit, $timeRange, $sortBy);
    }

    public function getUserEngagementDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): ChartData {
        return $this->getUserEngagementData($startDate, $endDate);
    }

    public function getCustomChartData(
        string $metricName,
        array $parameters = [],
        array $chartOptions = [],
    ): ChartData {
        $startDate = $parameters['start_date'] ?? null;
        $endDate = $parameters['end_date'] ?? null;
        $granularity = $parameters['granularity'] ?? 'day';

        $validStartDate = ($startDate instanceof DateTimeInterface) ? $startDate : null;
        $validEndDate = ($endDate instanceof DateTimeInterface) ? $endDate : null;
        $validGranularity = is_string($granularity) ? $granularity : 'day';

        return $this->getTimeSeriesData($metricName, $validStartDate, $validEndDate, $validGranularity);
    }

    public function getMultiMetricChartData(
        array $metricNames,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
        array $chartOptions = [],
    ): ChartData {
        return $this->getMultiMetricDashboardData(
            $metricNames,
            $startDate,
            $endDate,
            $granularity,
            $chartOptions,
        );
    }

    public function getPerformanceMetricsData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $metrics = ['response_time', 'throughput', 'error_rate'],
        string $granularity = 'hour',
    ): ChartData {
        $cacheKey = 'performance_metrics_' . implode('_', $metrics) . '_'
            . $startDate->format('Y-m-d') . '_'
            . $endDate->format('Y-m-d') . '_'
            . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($metrics, $startDate, $endDate, $granularity): ChartData {
                $allData = [];

                foreach ($metrics as $metric) {
                    $rawData = $this->queryAdapter->getMetricTimeSeriesData(
                        $metric,
                        $startDate,
                        $endDate,
                        $granularity,
                    );
                    $allData[$metric] = $rawData;
                }

                return $this->timeSeriesProcessor->processMultiSeriesData(
                    $allData,
                    'Performance Metrics',
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    // 輔助方法
    private function getTimeSeriesData(
        string $metric,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = "timeseries_{$metric}_"
            . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_'
            . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_'
            . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($metric, $startDate, $endDate, $granularity): ChartData {
                $rawData = $this->queryAdapter->getTimeSeriesData(
                    $metric,
                    $startDate,
                    $endDate,
                    $granularity,
                );

                return $this->timeSeriesProcessor->processTimeSeriesData(
                    $rawData,
                    $metric,
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    private function getCategoryDistributionData(
        string $categoryType,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData {
        $cacheKey = "category_{$categoryType}_"
            . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_'
            . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_'
            . $limit;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($categoryType, $startDate, $endDate, $limit): ChartData {
                $rawData = $this->queryAdapter->getCategoryDistributionData(
                    $categoryType,
                    $startDate,
                    $endDate,
                    $limit,
                );

                return $this->categoryProcessor->processCategoryData(
                    $rawData,
                    $categoryType,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    private function getTopContentData(
        int $limit = 10,
        array $timeRange = [],
        string $sortBy = 'views',
    ): ChartData {
        $cacheKey = "top_content_{$limit}_{$sortBy}_"
            . md5(serialize($timeRange));

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($limit, $timeRange, $sortBy): ChartData {
                $rawData = $this->queryAdapter->getTopContentData($limit, $timeRange, $sortBy);

                return $this->categoryProcessor->processRankingData(
                    $rawData,
                    'Top Content',
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    private function getUserEngagementData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
    ): ChartData {
        $cacheKey = 'user_engagement_'
            . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_'
            . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_'
            . $granularity;

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($startDate, $endDate, $granularity): ChartData {
                $rawData = $this->queryAdapter->getUserEngagementData(
                    $startDate,
                    $endDate,
                    $granularity,
                );

                return $this->timeSeriesProcessor->processEngagementData(
                    $rawData,
                    $granularity,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }

    private function getMultiMetricDashboardData(
        array $metrics,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
        array $chartOptions = [],
    ): ChartData {
        $cacheKey = 'multi_metric_' . implode('_', $metrics) . '_'
            . ($startDate ? $startDate->format('Y-m-d') : 'all') . '_'
            . ($endDate ? $endDate->format('Y-m-d') : 'all') . '_'
            . $granularity . '_' . md5(serialize($chartOptions));

        $result = $this->cacheService->remember(
            $cacheKey,
            function () use ($metrics, $startDate, $endDate, $granularity, $chartOptions): ChartData {
                $allData = [];

                foreach ($metrics as $metric) {
                    if (!is_string($metric)) {
                        continue;
                    }

                    $rawData = $this->queryAdapter->getTimeSeriesData(
                        $metric,
                        $startDate,
                        $endDate,
                        $granularity,
                    );
                    $allData[$metric] = $rawData;
                }

                return $this->timeSeriesProcessor->processMultiMetricData(
                    $allData,
                    'Multi-Metric Chart',
                    $granularity,
                    $chartOptions,
                );
            },
            3600,
        );

        assert($result instanceof ChartData);

        return $result;
    }
}
