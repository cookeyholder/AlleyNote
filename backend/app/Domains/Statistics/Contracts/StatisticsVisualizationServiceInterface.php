<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\ChartData;
use DateTimeInterface;

/**
 * 統計可視化服務介面.
 *
 * 提供前端圖表所需的各種統計資料格式化服務
 */
interface StatisticsVisualizationServiceInterface
{
    /**
     * 取得文章發布時間序列統計.
     *
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param string $granularity 時間粒度 (day|week|month|year)
     */
    public function getPostsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData;

    /**
     * 取得使用者活動時間序列統計.
     *
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param string $granularity 時間粒度 (day|week|month|year)
     */
    public function getUserActivityTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData;

    /**
     * 取得文章來源分布統計.
     *
     * @param DateTimeInterface|null $startDate 開始日期（可選）
     * @param DateTimeInterface|null $endDate 結束日期（可選）
     * @param int $limit 限制數量
     */
    public function getPostSourceDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData;

    /**
     * 取得熱門標籤分布統計.
     *
     * @param DateTimeInterface|null $startDate 開始日期（可選）
     * @param DateTimeInterface|null $endDate 結束日期（可選）
     * @param int $limit 限制數量
     */
    public function getPopularTagsDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): ChartData;

    /**
     * 取得使用者註冊趨勢分析.
     *
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param string $granularity 時間粒度 (day|week|month|year)
     */
    public function getUserRegistrationTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData;

    /**
     * 取得內容成長趨勢分析（包含多個指標）.
     *
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param string $granularity 時間粒度 (day|week|month|year)
     */
    public function getContentGrowthTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
    ): ChartData;

    /**
     * 取得熱門內容排行榜.
     *
     * @param DateTimeInterface|null $startDate 開始日期（可選）
     * @param DateTimeInterface|null $endDate 結束日期（可選）
     * @param string $sortBy 排序依據 (views|likes|comments)
     * @param int $limit 限制數量
     */
    public function getPopularContentRankingData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $sortBy = 'views',
        int $limit = 10,
    ): ChartData;

    /**
     * 取得使用者活躍度分布統計.
     *
     * @param DateTimeInterface|null $startDate 開始日期（可選）
     * @param DateTimeInterface|null $endDate 結束日期（可選）
     */
    public function getUserEngagementDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): ChartData;

    /**
     * 取得自訂統計圖表資料.
     *
     * @param string $metricName 指標名稱
     * @param array<string, mixed> $parameters 查詢參數
     * @param array<string, mixed> $chartOptions 圖表選項
     */
    public function getCustomChartData(
        string $metricName,
        array $parameters = [],
        array $chartOptions = [],
    ): ChartData;

    /**
     * 取得多指標組合圖表資料.
     *
     * @param array<string> $metricNames 指標名稱列表
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param string $granularity 時間粒度
     * @param array<string, mixed> $chartOptions 圖表選項
     */
    public function getMultiMetricChartData(
        array $metricNames,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity = 'day',
        array $chartOptions = [],
    ): ChartData;

    /**
     * 取得效能監控圖表資料.
     *
     * @param DateTimeInterface $startDate 開始日期
     * @param DateTimeInterface $endDate 結束日期
     * @param array<string> $metrics 監控指標
     * @param string $granularity 時間粒度
     */
    public function getPerformanceMetricsData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $metrics = ['response_time', 'error_rate', 'throughput'],
        string $granularity = 'hour',
    ): ChartData;
}
