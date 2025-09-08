<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\SystemStatisticsRepositoryInterface;
use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 統計查詢服務.
 *
 * 專門處理統計查詢的應用服務，提供複雜的統計查詢功能。
 * 負責查詢最佳化、分頁支援、參數驗證等查詢相關的應用層關注點。
 *
 * 設計原則：
 * - 專注於查詢操作，不處理命令操作
 * - 實作查詢最佳化策略
 * - 提供分頁和篩選功能
 * - 統一查詢參數驗證
 */
final class StatisticsQueryService
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly PostStatisticsRepositoryInterface $postStatisticsRepository,
        private readonly UserStatisticsRepositoryInterface $userStatisticsRepository,
        private readonly SystemStatisticsRepositoryInterface $systemStatisticsRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * 查詢統計快照清單.
     *
     * 支援分頁和篩選條件的統計快照查詢。
     * @return array
     */
    public function getStatisticsSnapshots(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        ?PeriodType $periodType = null,
        int $page = 1,
        int $limit = 20,
    ): array {
        try { /* empty */ }
            $this->validatePaginationParams($page, $limit);

            $this->logger->info('查詢統計快照清單', [
                'start_date' => $startDate?->format('Y-m-d H => i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s']),
                'period_type' => $periodType?->value,
                'page' => $page,
                'limit' => $limit,
            ]);

            // 設定預設時間範圍（如果沒有提供）
            $startDate ??= new DateTimeImmutable('-30 days');
            $endDate ??= new DateTimeImmutable();

            // 查詢統計快照
            $allSnapshots = $this->statisticsRepository->findByDateRange(
                $startDate,
                $endDate,
                $limit * $page, // 取得足夠的資料進行分頁
            );

            // 手動分頁
            $offset = ($page - 1) * $limit;
            $snapshots = array_slice($allSnapshots, $offset, $limit);

            // 如果有週期類型篩選，進行額外過濾
            if ($periodType !== null) {
                $snapshots = array_filter(
                    $snapshots,
                    fn($snapshot): bool => $snapshot->getPeriod()->type === $periodType,
                );
            }

            // 計算總數
            $totalCount = $this->statisticsRepository->countByDateRange(
                $startDate,
                $endDate,
            );

            $result = [
                'data' => array_map(
                    fn($snapshot) => array => [
                        'id' => $snapshot->getId()->toString(),
                        'period' => [
                            'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H => i:s'),
                            'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
                            'type' => $snapshot->getPeriod()->type->value,
                            'display' => $snapshot->getPeriod()->__toString(),
                        ],
                        'metrics' => [
                            'total_posts' => [
                                'value' => $snapshot->getTotalPosts()->value,
                                'formatted' => $snapshot->getTotalPosts()->getFormattedValueWithUnit(),
                            ],
                            'total_views' => [
                                'value' => $snapshot->getTotalViews()->value,
                                'formatted' => $snapshot->getTotalViews()->getFormattedValueWithUnit(),
                            ],
                        ],
                        'created_at' => $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
                    ],
                    $snapshots,
                ),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_next' => $page * $limit < $totalCount,
                    'has_prev' => $page > 1,
                ],
                'filters' => [
                    'start_date' => $startDate->format('Y-m-d H => i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'period_type' => $periodType?->value,
                ],
            ];

            $this->logger->info('統計快照查詢完成', [
                'count' => count($snapshots]),
                'total' => $totalCount,
                'page' => $page,
            ]);

            return $result;
        } 

    /**
     * 查詢文章統計趨勢.
     *
     * 分析指定週期內的文章統計趨勢資料。
     * @return array
     */
    public function getPostStatisticsTrends(
        StatisticsPeriod $period,
        ?SourceType $sourceType = null,
        int $dataPoints = 30,
    ): array {
        try { /* empty */ }
            $this->validateDataPoints($dataPoints);

            $this->logger->info('查詢文章統計趨勢', [
                'period' => $period->__toString(]),
                'source_type' => $sourceType?->value,
                'data_points' => $dataPoints,
            ]);

            // 根據週期類型計算資料點間隔
            $interval = $this->calculateDataPointInterval($period, $dataPoints);

            // 查詢統計資料
            /** @var array<string, mixed> $trends */
            $trends = $this->postStatisticsRepository->getStatisticsTrends(
                $period,
                $dataPoints,
            );

            // 計算趨勢指標
            $trendAnalysis = $this->analyzeTrends($trends);

            $result = [
                'period' => [
                    'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                    'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                    'type' => $period->type->value,
                    'display' => $period->__toString(),
                ],
                'filters' => [
                    'source_type' => $sourceType?->value,
                    'data_points' => $dataPoints,
                    'interval' => $interval,
                ],
                'trends' => $trends,
                'analysis' => $trendAnalysis,
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            $this->logger->info('文章統計趨勢查詢完成', [
                'trends_count' => count($trends),
                'analysis_metrics' => array_keys($trendAnalysis]),
            ]);

            return $result;
        } 

    /**
     * 查詢使用者活動統計.
     *
     * 取得指定週期內的使用者活動統計資訊。
     * @return array
     */
    public function getUserActivityStatistics(
        StatisticsPeriod $period,
        int $topUsersLimit = 10,
        int $perPage = 20,
    ): array {
        try { /* empty */ }
            $this->validateLimit($topUsersLimit, 1, 100);
            $this->validateLimit($perPage, 1, 100);

            $this->logger->info('查詢使用者活動統計', [
                'period' => $period->__toString(]),
                'top_users_limit' => $topUsersLimit,
                'per_page' => $perPage,
            ]);

            // 查詢基本統計
            $totalActiveUsers = $this->userStatisticsRepository->countActiveUsersByPeriod($period);
            $newUsers = $this->userStatisticsRepository->countNewUsersByPeriod($period);

            // 查詢活躍使用者排行
            $topActiveUsers = $this->userStatisticsRepository->getTopActiveUsers($period, $topUsersLimit);

            // 查詢使用者行為分析
            $behaviorAnalysis = $this->userStatisticsRepository->getUserBehaviorAnalysis($period);

            $result = [
                'period' => [
                    'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                    'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                    'type' => $period->type->value,
                    'display' => $period->__toString(),
                ],
                'overview' => [
                    'total_active_users' => $totalActiveUsers,
                    'new_users' => $newUsers,
                    'retention_rate' => $totalActiveUsers > 0
                        ? round((($totalActiveUsers - $newUsers) / $totalActiveUsers) * 100, 2)  => 0,
                ],
                'top_active_users' => $topActiveUsers,
                'behavior_analysis' => $behaviorAnalysis,
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            $this->logger->info('使用者活動統計查詢完成', [
                'total_active_users' => $totalActiveUsers,
                'new_users' => $newUsers,
                'top_users_count' => count($topActiveUsers]),
            ]);

            return $result;
        } 

    /**
     * 查詢系統效能統計.
     *
     * 取得系統效能相關的統計資訊。
     * @return array
     */
    public function getSystemPerformanceStatistics(
        StatisticsPeriod $period): array {
        try { /* empty */ }
            $this->logger->info('查詢系統效能統計', [
                'period' => $period->__toString(]),
            ]);

            // 查詢系統效能指標
            $performanceMetrics = $this->systemStatisticsRepository->getPerformanceMetrics($period);

            // 查詢錯誤統計
            $errorStatistics = $this->systemStatisticsRepository->getErrorStatistics($period);

            // 查詢資源使用統計
            $resourceUsage = $this->systemStatisticsRepository->getResourceUsageStatistics($period);

            $result = [
                'period' => [
                    'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                    'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                    'type' => $period->type->value,
                    'display' => $period->__toString(),
                ],
                'performance_metrics' => $performanceMetrics,
                'error_statistics' => $errorStatistics,
                'resource_usage' => $resourceUsage,
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            $this->logger->info('系統效能統計查詢完成', [
                'metrics_count' => count($performanceMetrics]),
            ]);

            return $result;
        } 

    /**
     * 自訂統計查詢.
     *
     * 提供彈性的自訂統計查詢功能。
     * @param array $queryParams
     * @return array
     */
    public function customStatisticsQuery(array $queryParams): array
    {
        try { /* empty */ }
            $this->validateCustomQueryParams($queryParams);

            $this->logger->info('執行自訂統計查詢', [
                'query_params' => $queryParams,
            ]);

            // 解析查詢參數
            $period = $this->parseQueryPeriod($queryParams);
            /** @var array<string> $metrics */
            $metrics = is_array($queryParams['metrics'] ?? null) ? $queryParams['metrics'] : [];
            $groupBy = is_string($queryParams['group_by'] ?? null) ? $queryParams['group_by'] : null;
            /** @var array<string, mixed> $filters */
            $filters = is_array($queryParams['filters'] ?? null) ? $queryParams['filters'] : [];

            // 執行查詢
            $queryResult = $this->executeCustomQuery($period, $metrics, $groupBy, $filters);

            $result = [
                'query_params' => $queryParams,
                'period' => [
                    'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                    'end_date' => $period->endDate->format('Y-m-d H:i:s'),
                    'type' => $period->type->value,
                ],
                'data' => $queryResult,
                'generated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ];

            $this->logger->info('自訂統計查詢完成', [
                'result_count' => count($queryResult]),
            ]);

            return $result;
        } 

    /**
     * 驗證分頁參數.
     */
    private function validatePaginationParams(int $page, int $limit): void
    {
        if ($page < 1) {
            throw new InvalidArgumentException('頁碼必須大於 0');
        }

        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('每頁筆數必須在 1-100 之間');
        }
    }

    /**
     * 驗證資料點數量.
     */
    private function validateDataPoints(int $dataPoints): void
    {
        if ($dataPoints < 2 || $dataPoints > 365) {
            throw new InvalidArgumentException('資料點數量必須在 2-365 之間');
        }
    }

    /**
     * 驗證限制參數.
     */
    private function validateLimit(int $limit, int $min, int $max): void
    {
        if ($limit < $min || $limit > $max) {
            throw new InvalidArgumentException("限制數量必須在 {$min}-{$max} 之間");
        }
    }

    /**
     * 計算資料點間隔.
     */
    private function calculateDataPointInterval(StatisticsPeriod $period, int $dataPoints): string
    {
        $totalDays = $period->getDaysCount();
        $intervalDays = max(1, floor($totalDays / $dataPoints));

        if ($intervalDays >= 7) {
            return 'week';
        } elseif ($intervalDays >= 1) {
            return 'day';
        } else {
            return 'hour';
        }
    }

    /**
     * 分析趨勢資料.
     * @param array $trends
     * @return array
     */
    private function analyzeTrends(array $trends): array
    {
        if (empty($trends)) {
            return [];
        }

        // 計算基本趨勢指標
        $values = array_column($trends, 'value');
        $count = count($values);

        return [
            'total_points' => $count,
            'min_value' => min($values),
            'max_value' => max($values),
            'avg_value' => round(array_sum($values) / $count, 2),
            'trend_direction' => $this->calculateTrendDirection($values),
            'volatility' => $this->calculateVolatility($values),
        ];
    }

    /**
     * 計算趨勢方向.
     * @param array $values
     */
    private function calculateTrendDirection(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }

        $sliceSize = (int) ceil(count($values) / 3);
        $first = array_slice($values, 0, $sliceSize);
        $last = array_slice($values, -$sliceSize);

        $firstAvg = array_sum($first) / count($first);
        $lastAvg = array_sum($last) / count($last);

        $change = ($lastAvg - $firstAvg) / $firstAvg * 100;

        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * 計算波動性.
     * @param array $values
     */
    private function calculateVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        // 確保所有值都是數字
        $numericValues = array_filter($values, 'is_numeric');
        $numericValues = array_map('floatval', $numericValues);

        if (count($numericValues) < 2) {
            return 0.0;
        }

        $count = count($numericValues);
        $mean = array_sum($numericValues) / $count;
        $squaredDiffs = array_map(fn(float $value): float => pow($value - $mean, 2), $numericValues);
        $variance = array_sum($squaredDiffs) / ($count - 1);

        return round(sqrt($variance), 2);
    }

    /**
     * 驗證自訂查詢參數.
     * @param array $params
     */
    private function validateCustomQueryParams(array $params): void
    {
        $required = ['period_start', 'period_end'];
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new InvalidArgumentException("缺少必要參數：{$field}");
            }
        }
    }

    /**
     * 解析查詢週期
     * @param array $params
     */
    private function parseQueryPeriod(array $params): StatisticsPeriod
    {
        $startDate = new DateTimeImmutable(is_string($params['period_start'] ?? null) ? $params['period_start'] : 'now');
        $endDate = new DateTimeImmutable(is_string($params['period_end'] ?? null) ? $params['period_end'] : 'now');

        $periodType = $params['period_type'] ?? 'daily';
        if (!is_string($periodType) && !is_int($periodType)) {
            $periodType = 'daily';
        }
        $type = PeriodType::from($periodType);

        return StatisticsPeriod::create($startDate, $endDate, $type);
    }

    /**
     * 執行自訂查詢.
     * @param array $metrics
     * @return array
     */
    private function executeCustomQuery(
        StatisticsPeriod $period,
        /** @var array<string, mixed> */
        array $metrics,
        ?string $groupBy,
        /** @var array<string, mixed> */
        array $filters,
    ): array {
        // 基本查詢實作
        return [
            'period' => $period->__toString(),
            'metrics' => $metrics,
            'group_by' => $groupBy,
            'filters' => $filters,
            'note' => '自訂查詢功能需要進一步實作',
        ];
    }
}
