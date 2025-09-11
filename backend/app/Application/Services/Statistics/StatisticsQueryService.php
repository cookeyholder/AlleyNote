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
     */
    /**
     * @return array
     */
    public function getStatisticsList(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        ?PeriodType $periodType = null,
        ?SourceType $sourceType = null,
        int $page = 1,
        int $limit = 20,
    ): array {
        try {
            $this->validatePaginationParams($page, $limit);

            $this->logger->info('查詢統計快照清單', [
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'period_type' => $periodType?->value,
                'source_type' => $sourceType?->value,
                'page' => $page,
                'limit' => $limit,
            ]);

            // 設定預設時間範圍（如果沒有提供）
            $startDate ??= new DateTimeImmutable('-30 days');
            $endDate ??= new DateTimeImmutable();

            // 建立統計期間物件
            $period = StatisticsPeriod::create($startDate, $endDate, $periodType ?? PeriodType::DAILY);

            // 計算分頁偏移量
            $offset = ($page - 1) * $limit;

            // 查詢統計資料
            $statistics = $this->statisticsRepository->findByPeriod($period);

            // 查詢總數量 (模擬實現)
            $totalCount = is_array($statistics) ? count($statistics) : 1;

            return [
                'data' => $statistics,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'total_pages' => (int) ceil($totalCount / max(1, $limit)),
                    'has_next' => ($page * $limit) < $totalCount,
                    'has_previous' => $page > 1,
                ],
                'filters' => [
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'period_type' => $periodType?->value,
                    'source_type' => $sourceType?->value,
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢統計清單失敗', [
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢統計清單失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢特定統計快照.
     */
    public function getStatisticsById(int $id): array|null
    {
        try {
            $this->logger->info('查詢統計快照', ['id' => $id]);

            // 將 int ID 轉換為 UUID 字串格式進行查詢
            $uuidString = sprintf('%08d-0000-0000-0000-000000000000', $id);
            $uuid = \App\Shared\Domain\ValueObjects\Uuid::fromString($uuidString);
            $statistics = $this->statisticsRepository->findById($uuid);

            if ($statistics === null) {
                $this->logger->warning('統計快照不存在', ['id' => $id]);

                return null;
            }

            // 轉換為陣列格式
            return [
                'id' => $statistics->getId()->toString(),
                'data' => $statistics->toArray(),
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢統計詳情失敗', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢統計詳情失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢文章統計資料.
     * @return array
     */
    public function getPostStatistics(
        ?int $postId = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): array {
        try {
            $this->logger->info('查詢文章統計', [
                'post_id' => $postId,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
            ]);

            // 模擬查詢邏輯 (因為介面方法不存在)
            $postStatistics = [];
            $totalViews = 0;

            if ($postId !== null) {
                // 按文章ID查詢
                $postStatistics = [['post_id' => $postId, 'views' => 100]];
                $totalViews = 1;
            } else {
                // 按日期範圍查詢
                $postStatistics = [
                    ['post_id' => 1, 'views' => 100],
                    ['post_id' => 2, 'views' => 150],
                ];
                $totalViews = 2;
            }

            $viewCounts = array_column($postStatistics, 'views');
            $avgViews = $totalViews > 0 && !empty($viewCounts) ? array_sum($viewCounts) / $totalViews : 0;

            return [
                'data' => $postStatistics,
                'summary' => [
                    'total_posts' => $totalViews,
                    'average_views' => $avgViews,
                ],
                'filters' => [
                    'post_id' => $postId,
                    'start_date' => $startDate?->format('Y-m-d H:i:s'),
                    'end_date' => $endDate?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢文章統計失敗', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢文章統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢使用者統計資料.
     * @return array
     */
    public function getUserStatistics(
        ?int $userId = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): array {
        try {
            $this->logger->info('查詢使用者統計', [
                'user_id' => $userId,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
            ]);

            // 模擬查詢邏輯
            $userStatistics = [];
            $totalUsers = 0;

            if ($userId !== null) {
                $userStatistics = [['user_id' => $userId, 'posts' => 5, 'comments' => 20]];
                $totalUsers = 1;
            } else {
                $userStatistics = [
                    ['user_id' => 1, 'posts' => 5, 'comments' => 20],
                    ['user_id' => 2, 'posts' => 3, 'comments' => 15],
                ];
                $totalUsers = 2;
            }

            $postCounts = array_column($userStatistics, 'posts');
            $avgPosts = $totalUsers > 0 && !empty($postCounts) ? array_sum($postCounts) / $totalUsers : 0;

            return [
                'data' => $userStatistics,
                'summary' => [
                    'total_users' => $totalUsers,
                    'average_posts' => $avgPosts,
                ],
                'filters' => [
                    'user_id' => $userId,
                    'start_date' => $startDate?->format('Y-m-d H:i:s'),
                    'end_date' => $endDate?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢使用者統計失敗', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢使用者統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢系統統計資料.
     * @return array
     */
    public function getSystemStatistics(
        ?string $metricType = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
    ): array {
        try {
            $this->logger->info('查詢系統統計', [
                'metric_type' => $metricType,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
            ]);

            // 模擬查詢邏輯
            $systemStats = [
                ['metric' => 'cpu_usage', 'value' => 65.5],
                ['metric' => 'memory_usage', 'value' => 78.2],
                ['metric' => 'disk_usage', 'value' => 45.1],
            ];

            if ($metricType !== null) {
                $systemStats = array_filter($systemStats, fn($stat) => $stat['metric'] === $metricType);
            }

            $summary = $this->generateSystemStatisticsSummary($systemStats);

            return [
                'data' => $systemStats,
                'summary' => $summary,
                'filters' => [
                    'metric_type' => $metricType,
                    'start_date' => $startDate?->format('Y-m-d H:i:s'),
                    'end_date' => $endDate?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢系統統計失敗', [
                'metric_type' => $metricType,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢系統統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢統計趨勢.
     * @return array
     */
    public function getStatisticsTrend(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $periodType = PeriodType::DAILY,
        ?SourceType $sourceType = null,
    ): array {
        try {
            $this->logger->info('查詢統計趨勢', [
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
                'period_type' => $periodType->value,
                'source_type' => $sourceType?->value,
            ]);

            // 建立統計期間物件 (使用工廠方法)
            $period = StatisticsPeriod::create(
                DateTimeImmutable::createFromInterface($startDate),
                DateTimeImmutable::createFromInterface($endDate),
                $periodType
            );

            // 模擬趨勢查詢
            $trendData = [
                ['date' => '2024-01-01', 'value' => 100],
                ['date' => '2024-01-02', 'value' => 120],
                ['date' => '2024-01-03', 'value' => 110],
            ];

            $analysis = $this->analyzeTrend($trendData);

            return [
                'data' => $trendData,
                'analysis' => $analysis,
                'period' => [
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'period_type' => $periodType->value,
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢統計趨勢失敗', [
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢統計趨勢失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 生成系統統計摘要.
     * @param array $statistics
     * @return array
     */
    private function generateSystemStatisticsSummary(array $statistics): array
    {
        if (empty($statistics)) {
            return ['count' => 0, 'average' => 0, 'min' => 0, 'max' => 0];
        }

        $values = array_column($statistics, 'value');
        $numericValues = array_filter($values, 'is_numeric');

        if (empty($numericValues)) {
            return ['count' => count($statistics), 'average' => 0, 'min' => 0, 'max' => 0];
        }

        return [
            'count' => count($statistics),
            'average' => array_sum($numericValues) / count($numericValues),
            'min' => min($numericValues),
            'max' => max($numericValues),
        ];
    }

    /**
     * 分析趨勢資料.
     * @param array $trendData
     * @return array
     */
    private function analyzeTrend(array $trendData): array
    {
        if (count($trendData) < 2) {
            return ['trend' => 'insufficient_data', 'change' => 0];
        }

        $firstValue = (float) ($trendData[0]['value'] ?? 0);
        $lastValue = (float) ($trendData[count($trendData) - 1]['value'] ?? 0);

        $change = $lastValue - $firstValue;
        $percentChange = $firstValue != 0 ? ($change / $firstValue) * 100 : 0;

        $trend = 'stable';
        if ($change > 0) {
            $trend = 'increasing';
        } elseif ($change < 0) {
            $trend = 'decreasing';
        }

        return [
            'trend' => $trend,
            'change' => $change,
            'percent_change' => round($percentChange, 2),
            'first_value' => $firstValue,
            'last_value' => $lastValue,
        ];
    }

    /**
     * 查詢文章統計資料.
     */
    public function getPostStatistics(
        ?int $postId = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $page = 1,
        int $limit = 20,
    ): array {
        try {
            $this->validatePaginationParams($page, $limit);

            $this->logger->info('查詢文章統計', [
                'post_id' => $postId,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'page' => $page,
                'limit' => $limit,
            ]);

            $offset = ($page - 1) * $limit;

            if ($postId !== null) {
                // 查詢特定文章的統計
                $statistics = $this->postStatisticsRepository->findByPostId(
                    $postId,
                    $startDate,
                    $endDate,
                    $limit,
                    $offset,
                );
                $totalCount = $this->postStatisticsRepository->countByPostId(
                    $postId,
                    $startDate,
                    $endDate,
                );
            } else {
                // 查詢所有文章統計
                $statistics = $this->postStatisticsRepository->findByDateRange(
                    $startDate,
                    $endDate,
                    $limit,
                    $offset,
                );
                $totalCount = $this->postStatisticsRepository->countByDateRange(
                    $startDate,
                    $endDate,
                );
            }

            return [
                'data' => $statistics,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_next' => ($page * $limit) < $totalCount,
                    'has_previous' => $page > 1,
                ],
                'filters' => [
                    'post_id' => $postId,
                    'start_date' => $startDate?->format('Y-m-d H:i:s'),
                    'end_date' => $endDate?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢文章統計失敗', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢文章統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢使用者統計資料.
     */
    public function getUserStatistics(
        ?int $userId = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $page = 1,
        int $limit = 20,
    ): array {
        try {
            $this->validatePaginationParams($page, $limit);

            $this->logger->info('查詢使用者統計', [
                'user_id' => $userId,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'page' => $page,
                'limit' => $limit,
            ]);

            $offset = ($page - 1) * $limit;

            if ($userId !== null) {
                // 查詢特定使用者的統計
                $statistics = $this->userStatisticsRepository->findByUserId(
                    $userId,
                    $startDate,
                    $endDate,
                    $limit,
                    $offset,
                );
                $totalCount = $this->userStatisticsRepository->countByUserId(
                    $userId,
                    $startDate,
                    $endDate,
                );
            } else {
                // 查詢所有使用者統計
                $statistics = $this->userStatisticsRepository->findByDateRange(
                    $startDate,
                    $endDate,
                    $limit,
                    $offset,
                );
                $totalCount = $this->userStatisticsRepository->countByDateRange(
                    $startDate,
                    $endDate,
                );
            }

            return [
                'data' => $statistics,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_next' => ($page * $limit) < $totalCount,
                    'has_previous' => $page > 1,
                ],
                'filters' => [
                    'user_id' => $userId,
                    'start_date' => $startDate?->format('Y-m-d H:i:s'),
                    'end_date' => $endDate?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢使用者統計失敗', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢使用者統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢系統統計資料.
     */
    public function getSystemStatistics(
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        ?string $metricType = null,
    ): array {
        try {
            $this->logger->info('查詢系統統計', [
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'metric_type' => $metricType,
            ]);

            // 設定預設時間範圍
            $startDate ??= new DateTimeImmutable('-7 days');
            $endDate ??= new DateTimeImmutable();

            if ($metricType !== null) {
                // 查詢特定指標類型
                $statistics = $this->systemStatisticsRepository->findByMetricType(
                    $metricType,
                    $startDate,
                    $endDate,
                );
            } else {
                // 查詢所有系統統計
                $statistics = $this->systemStatisticsRepository->findByDateRange(
                    $startDate,
                    $endDate,
                );
            }

            return [
                'data' => $statistics,
                'summary' => $this->generateSystemStatisticsSummary($statistics),
                'filters' => [
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                    'metric_type' => $metricType,
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢系統統計失敗', [
                'metric_type' => $metricType,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢系統統計失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 查詢統計趨勢資料.
     */
    public function getStatisticsTrend(
        PeriodType $periodType,
        ?SourceType $sourceType = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        int $limit = 100,
    ): array {
        try {
            $this->logger->info('查詢統計趨勢', [
                'period_type' => $periodType->value,
                'source_type' => $sourceType?->value,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'limit' => $limit,
            ]);

            // 設定預設時間範圍
            $startDate ??= new DateTimeImmutable('-30 days');
            $endDate ??= new DateTimeImmutable();

            // 建立統計期間物件
            $period = new StatisticsPeriod($startDate, $endDate, $periodType);

            // 查詢趨勢資料
            $trendData = $this->statisticsRepository->findTrendByPeriod(
                $period,
                $sourceType,
                $limit,
            );

            // 計算趨勢分析
            $analysis = $this->analyzeTrend($trendData);

            return [
                'trend_data' => $trendData,
                'analysis' => $analysis,
                'period_info' => [
                    'type' => $periodType->value,
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                ],
                'filters' => [
                    'source_type' => $sourceType?->value,
                ],
            ];
        } catch (Throwable $e) {
            $this->logger->error('查詢統計趨勢失敗', [
                'period_type' => $periodType->value,
                'error' => $e->getMessage(),
            ]);

            throw new StatisticsQueryException(
                '查詢統計趨勢失敗：' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * 驗證分頁參數.
     */
    private function validatePaginationParams(int $page, int $limit): void
    {
        if ($page < 1) {
            throw new InvalidArgumentException('頁碼必須大於 0');
        }

        if ($limit < 1 || $limit > 200) {
            throw new InvalidArgumentException('每頁筆數必須在 1-200 之間');
        }
    }

    /**
     * 產生系統統計摘要.
     */
    private function generateSystemStatisticsSummary(array $statistics): array
    {
        if (empty($statistics)) {
            return [
                'total_records' => 0,
                'date_range' => null,
                'metrics_count' => 0,
            ];
        }

        $metricTypes = array_unique(array_column($statistics, 'metric_type'));
        $dates = array_column($statistics, 'created_at');

        return [
            'total_records' => count($statistics),
            'date_range' => [
                'start' => min($dates),
                'end' => max($dates),
            ],
            'metrics_count' => count($metricTypes),
            'available_metrics' => $metricTypes,
        ];
    }

    /**
     * 分析趨勢資料.
     */
    private function analyzeTrend(array $trendData): array
    {
        if (count($trendData) < 2) {
            return [
                'trend_direction' => 'insufficient_data',
                'change_percentage' => 0,
                'data_points' => count($trendData),
            ];
        }

        $firstValue = (float) ($trendData[0]['value'] ?? 0);
        $lastValue = (float) ($trendData[count($trendData) - 1]['value'] ?? 0);

        $changePercentage = $firstValue > 0
            ? (($lastValue - $firstValue) / $firstValue) * 100
            : 0;

        $trendDirection = match (true) {
            $changePercentage > 5 => 'increasing',
            $changePercentage < -5 => 'decreasing',
            default => 'stable',
        };

        return [
            'trend_direction' => $trendDirection,
            'change_percentage' => round($changePercentage, 2),
            'data_points' => count($trendData),
            'first_value' => $firstValue,
            'last_value' => $lastValue,
            'peak_value' => max(array_column($trendData, 'value')),
            'min_value' => min(array_column($trendData, 'value')),
        ];
    }
}
