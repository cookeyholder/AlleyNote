<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsQueryServiceInterface as BaseQueryServiceInterface;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * 統計查詢適配器.
 *
 * 將現有的統計查詢服務介面適配為可視化服務所需的格式
 */
class StatisticsQueryAdapter
{
    public function __construct(
        private BaseQueryServiceInterface $baseQueryService,
    ) {}

    /**
     * 取得文章時間序列資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getPostsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        $options = [
            'period_start' => DateTime::createFromInterface($startDate),
            'period_end' => DateTime::createFromInterface($endDate),
        ];

        $data = $this->baseQueryService->getPostStatistics($options);

        // 模擬時間序列資料生成
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'posts');
    }

    /**
     * 取得使用者活動時間序列資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getUserActivityTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        $options = [
            'period_start' => DateTime::createFromInterface($startDate),
            'period_end' => DateTime::createFromInterface($endDate),
        ];

        $data = $this->baseQueryService->getUserStatistics($options);

        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'users');
    }

    /**
     * 取得文章來源分布資料.
     *
     * @return array<array{category: string, value: float}>
     */
    public function getPostSourceDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): array {
        $options = [];
        if ($startDate) {
            $options['period_start'] = DateTime::createFromInterface($startDate);
        }
        if ($endDate) {
            $options['period_end'] = DateTime::createFromInterface($endDate);
        }

        $data = $this->baseQueryService->getSourceDistribution($options);

        return $this->generateMockCategoryData('source', $limit);
    }

    /**
     * 取得熱門標籤分布資料.
     *
     * @return array<array{category: string, value: float}>
     */
    public function getPopularTagsDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): array {
        return $this->generateMockCategoryData('tags', $limit);
    }

    /**
     * 取得使用者註冊趨勢資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getUserRegistrationTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'registrations');
    }

    /**
     * 取得留言時間序列資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getCommentsTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'comments');
    }

    /**
     * 取得熱門內容排行資料.
     *
     * @return array<array{category: string, value: float}>
     */
    public function getPopularContentRankingData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $sortBy = 'views',
        int $limit = 10,
    ): array {
        $options = [];
        if ($startDate) {
            $options['period_start'] = DateTime::createFromInterface($startDate);
        }
        if ($endDate) {
            $options['period_end'] = DateTime::createFromInterface($endDate);
        }

        $data = $this->baseQueryService->getPopularContent($options);

        return $this->generateMockCategoryData('content', $limit);
    }

    /**
     * 取得使用者活躍度分布資料.
     *
     * @return array<array{category: string, value: float}>
     */
    public function getUserEngagementDistributionData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): array {
        return [
            ['category' => '高活躍', 'value' => 25.5],
            ['category' => '中活躍', 'value' => 45.8],
            ['category' => '低活躍', 'value' => 28.7],
        ];
    }

    /**
     * 取得自訂指標資料.
     *
     * @param array<string, mixed> $parameters
     * @return array<array{timestamp?: string, category?: string, value: float}>
     */
    public function getCustomMetricData(string $metricName, array $parameters): array
    {
        // 簡單實作，返回模擬資料
        if (isset($parameters['start_date']) && isset($parameters['end_date'])) {
            $startDateParam = $parameters['start_date'];
            $endDateParam = $parameters['end_date'];
            $granularityParam = $parameters['granularity'] ?? 'day';

            if (!is_string($startDateParam) || !is_string($endDateParam) || !is_string($granularityParam)) {
                $limitParam = $parameters['limit'] ?? 10;
                if (!is_int($limitParam) && !is_numeric($limitParam)) {
                    $limitParam = 10;
                }

                return $this->generateMockCategoryData($metricName, (int) $limitParam);
            }

            $startDate = new DateTimeImmutable($startDateParam);
            $endDate = new DateTimeImmutable($endDateParam);
            $granularity = $granularityParam;

            return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metricName);
        }

        return $this->generateMockCategoryData($metricName, 5);
    }

    /**
     * 取得指標時間序列資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getMetricTimeSeriesData(
        string $metricName,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
    ): array {
        $startDate ??= new DateTimeImmutable('-30 days');
        $endDate ??= new DateTimeImmutable();

        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metricName);
    }

    /**
     * 取得效能指標資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getPerformanceMetricData(
        string $metric,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metric);
    }

    /**
     * 生成模擬時間序列資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    private function generateMockTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
        string $type,
    ): array {
        $data = [];
        $current = DateTimeImmutable::createFromInterface($startDate);
        $end = DateTimeImmutable::createFromInterface($endDate);

        $interval = match ($granularity) {
            'hour' => new DateInterval('PT1H'),
            'day' => new DateInterval('P1D'),
            'week' => new DateInterval('P1W'),
            'month' => new DateInterval('P1M'),
            'year' => new DateInterval('P1Y'),
            default => new DateInterval('P1D'),
        };

        $baseValue = match ($type) {
            'posts' => 50,
            'users' => 100,
            'comments' => 200,
            'registrations' => 10,
            'response_time' => 150,
            'error_rate' => 2,
            'throughput' => 500,
            default => 100,
        };

        while ($current <= $end) {
            // 加入一些隨機波動和趨勢
            $randomFactor = 0.7 + (mt_rand() / mt_getrandmax()) * 0.6; // 0.7-1.3
            $trendFactor = 1 + sin($current->getTimestamp() / 86400) * 0.2; // 週期性趨勢

            $value = $baseValue * $randomFactor * $trendFactor;

            // 確保某些指標的合理範圍
            if ($type === 'error_rate') {
                $value = max(0, min(10, $value)); // 0-10%
            } elseif ($type === 'response_time') {
                $value = max(50, $value); // 最少 50ms
            }

            $data[] = [
                'timestamp' => $current->format('Y-m-d H:i:s'),
                'value' => round($value, 2),
            ];

            $current = $current->add($interval);
        }

        return $data;
    }

    /**
     * 生成模擬分類資料.
     *
     * @return array<array{category: string, value: float}>
     */
    private function generateMockCategoryData(string $type, int $limit): array
    {
        $categories = match ($type) {
            'source' => ['官方網站', 'RSS 訂閱', '手動輸入', 'API 匯入', '社群媒體', '新聞聚合'],
            'tags' => ['技術', '生活', '科技', '教育', '娛樂', '健康', '財經', '體育', '旅遊', '美食'],
            'content' => ['React 入門指南', 'PHP 最佳實踐', 'Docker 容器化', 'API 設計原則', 'MySQL 優化'],
            default => ['項目1', '項目2', '項目3', '項目4', '項目5'],
        };

        $data = [];
        $totalValue = 1000;
        $remainingValue = $totalValue;

        for ($i = 0; $i < min($limit, count($categories)); $i++) {
            $maxValue = $i === $limit - 1 ? $remainingValue : $remainingValue * 0.6;
            $value = max(10, mt_rand(10, (int) $maxValue));

            $data[] = [
                'category' => $categories[$i],
                'value' => (float) $value,
            ];

            $remainingValue -= $value;
            if ($remainingValue <= 0) {
                break;
            }
        }

        return $data;
    }

    /**
     * 取得內容成長趨勢資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getContentGrowthTrendData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'content_growth');
    }

    /**
     * 取得時間序列指標資料.
     *
     * @return array<array{timestamp: string, value: float}>
     */
    public function getTimeSeriesMetricData(
        string $metricName,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metricName);
    }

    /**
     * 取得多個效能指標資料.
     *
     * @param array<string> $metrics
     * @return array<string, array<array{timestamp: string, value: float}>>
     */
    public function getPerformanceMetricsData(
        array $metrics,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $granularity,
    ): array {
        $result = [];
        foreach ($metrics as $metric) {
            $result[$metric] = $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metric);
        }

        return $result;
    }

    /**
     * 取得通用時間序列資料.
     */
    public function getTimeSeriesData(
        string $metric,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
    ): array {
        $startDate ??= new DateTimeImmutable('-30 days');
        $endDate ??= new DateTimeImmutable();

        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, $metric);
    }

    /**
     * 取得分類分佈資料.
     */
    public function getCategoryDistributionData(
        string $categoryType,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 10,
    ): array {
        // 模擬分類資料
        $categories = ['Tech', 'Business', 'Health', 'Education', 'Entertainment'];
        $result = [];

        foreach (array_slice($categories, 0, $limit) as $index => $category) {
            $result[] = [
                'category' => $category,
                'value' => rand(10, 100),
                'percentage' => rand(5, 30),
            ];
        }

        return $result;
    }

    /**
     * 取得熱門內容資料.
     */
    public function getTopContentData(int $limit, array $timeRange, string $sortBy): array
    {
        $result = [];

        for ($i = 1; $i <= $limit; $i++) {
            $result[] = [
                'title' => "Popular Article {$i}",
                'views' => rand(100, 1000),
                'likes' => rand(10, 100),
                'shares' => rand(5, 50),
            ];
        }

        return $result;
    }

    /**
     * 取得使用者參與度資料.
     */
    public function getUserEngagementData(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
    ): array {
        $startDate ??= new DateTimeImmutable('-30 days');
        $endDate ??= new DateTimeImmutable();

        return $this->generateMockTimeSeriesData($startDate, $endDate, $granularity, 'engagement');
    }
}
