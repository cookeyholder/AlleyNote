<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Statistics\Contracts\StatisticsVisualizationServiceInterface;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 統計可視化 API 控制器.
 *
 * 提供前端圖表所需的各種統計資料 API 端點
 */
class StatisticsChartController extends BaseController
{
    public function __construct(
        private StatisticsVisualizationServiceInterface $visualizationService,
        private PDO $db,
    ) {}

    /**
     * 取得文章發布時間序列統計.
     *
     * GET /api/v1/statistics/charts/posts/timeseries
     */
    public function getPostsTimeSeries(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            [$startDate, $endDate] = $this->parseDateRange($params);
            /** @var string $granularity */
            $granularity = $params['granularity'] ?? 'day';

            $this->validateGranularity($granularity);

            $chartData = $this->visualizationService->getPostsTimeSeriesData(
                $startDate,
                $endDate,
                $granularity,
            );

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'granularity' => $granularity,
                    'data_points' => $chartData->getDataPointCount(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得統計資料失敗');
        }
    }

    /**
     * 取得使用者活動時間序列統計.
     *
     * GET /api/v1/statistics/charts/users/timeseries
     */
    public function getUserActivityTimeSeries(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            [$startDate, $endDate] = $this->parseDateRange($params);
            /** @var string $granularity */
            $granularity = $params['granularity'] ?? 'day';

            $this->validateGranularity($granularity);

            $chartData = $this->visualizationService->getUserActivityTimeSeriesData(
                $startDate,
                $endDate,
                $granularity,
            );

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'granularity' => $granularity,
                    'data_points' => $chartData->getDataPointCount(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得統計資料失敗');
        }
    }

    /**
     * 取得瀏覽量時間序列統計.
     *
     * GET /api/statistics/charts/views/timeseries
     */
    public function getViewsTimeSeries(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            [$startDate, $endDate] = $this->parseDateRange($params);
            /** @var string $granularity */
            $granularity = $params['granularity'] ?? 'day';

            $this->validateGranularity($granularity);

            // 直接查詢資料庫
            $chartData = $this->getViewsTimeSeriesData($startDate, $endDate, $granularity);

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'granularity' => $granularity,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得瀏覽量統計失敗: ' . $e->getMessage());
        }
    }

    /**
     * 查詢瀏覽量時間序列資料.
     *
     * @return array<int, array{date: string, views: int, visitors: int}>
     */
    private function getViewsTimeSeriesData(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $granularity,
    ): array {
        // SQLite 使用 strftime 函數
        $dateFormat = match ($granularity) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%W',  // SQLite 使用 %W 表示週數
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $sql = '
            SELECT 
                strftime(:date_format, view_date) as date,
                COUNT(*) as views,
                COUNT(DISTINCT user_ip) as visitors
            FROM post_views
            WHERE view_date BETWEEN :start_date AND :end_date
            GROUP BY strftime(:date_format, view_date)
            ORDER BY date ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'date_format' => $dateFormat,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d 23:59:59'),
        ]);

        /** @var array<int, array{date: string, views: string, visitors: string}> */
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 轉換為整數
        return array_map(function ($row) {
            return [
                'date' => $row['date'],
                'views' => (int) $row['views'],
                'visitors' => (int) $row['visitors'],
            ];
        }, $result);
    }

    /**
     * 取得分類統計圖表（圓餅圖/長條圖等）.
     *
     * GET /api/v1/statistics/charts/categories/{type}
     */
    public function getCategoryChart(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        try {
            $type = $args['type'] ?? 'sources';
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            /** @var string|null $startDateStr */
            $startDateStr = $params['start_date'] ?? null;
            /** @var string|null $endDateStr */
            $endDateStr = $params['end_date'] ?? null;

            $startDate = $this->parseOptionalDate($startDateStr);
            $endDate = $this->parseOptionalDate($endDateStr);
            $limit = $this->parseIntParam($params, 'limit', 10, 1, 50);

            $chartData = match ($type) {
                'sources' => $this->visualizationService->getPostSourceDistributionData(
                    $startDate,
                    $endDate,
                    $limit,
                ),
                'tags' => $this->visualizationService->getPopularTagsDistributionData(
                    $startDate,
                    $endDate,
                    $limit,
                ),
                'engagement' => $this->visualizationService->getUserEngagementDistributionData(
                    $startDate,
                    $endDate,
                ),
                default => throw ValidationException::fromSingleError('type', '不支援的分類類型'),
            };

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'type' => $type,
                    'start_date' => $startDate?->format('Y-m-d'),
                    'end_date' => $endDate?->format('Y-m-d'),
                    'limit' => $limit,
                    'data_points' => $chartData->getDataPointCount(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得統計資料失敗');
        }
    }

    /**
     * 取得趨勢分析圖表.
     *
     * GET /api/v1/statistics/charts/trends/{type}
     */
    public function getTrendChart(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        try {
            $type = $args['type'] ?? 'registration';
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            [$startDate, $endDate] = $this->parseDateRange($params);
            /** @var string $granularity */
            $granularity = $params['granularity'] ?? 'day';

            $this->validateGranularity($granularity);

            $chartData = match ($type) {
                'registration' => $this->visualizationService->getUserRegistrationTrendData(
                    $startDate,
                    $endDate,
                    $granularity,
                ),
                'content-growth' => $this->visualizationService->getContentGrowthTrendData(
                    $startDate,
                    $endDate,
                    $granularity,
                ),
                default => throw ValidationException::fromSingleError('type', '不支援的趨勢類型'),
            };

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'type' => $type,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'granularity' => $granularity,
                    'data_points' => $chartData->getDataPointCount(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得統計資料失敗');
        }
    }

    /**
     * 取得熱門內容排行榜.
     *
     * GET /api/v1/statistics/charts/content/ranking
     */
    public function getContentRanking(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            /** @var array<string, mixed> $params */
            $params = $request->getQueryParams();

            /** @var string|null $startDateStr */
            $startDateStr = $params['start_date'] ?? null;
            /** @var string|null $endDateStr */
            $endDateStr = $params['end_date'] ?? null;

            $startDate = $this->parseOptionalDate($startDateStr);
            $endDate = $this->parseOptionalDate($endDateStr);
            /** @var string $sortBy */
            $sortBy = $params['sort_by'] ?? 'views';
            $limit = $this->parseIntParam($params, 'limit', 10, 1, 50);

            $this->validateSortBy($sortBy);

            $chartData = $this->visualizationService->getPopularContentRankingData(
                $startDate,
                $endDate,
                $sortBy,
                $limit,
            );

            return $this->json($response, [
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'start_date' => $startDate?->format('Y-m-d'),
                    'end_date' => $endDate?->format('Y-m-d'),
                    'sort_by' => $sortBy,
                    'limit' => $limit,
                    'data_points' => $chartData->getDataPointCount(),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception('取得統計資料失敗');
        }
    }

    /**
     * 解析日期範圍參數.
     *
     * @param array<string, mixed> $params
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     * @throws ValidationException
     */
    private function parseDateRange(array $params): array
    {
        /** @var string|null $startDateStr */
        $startDateStr = $params['start_date'] ?? null;
        /** @var string|null $endDateStr */
        $endDateStr = $params['end_date'] ?? null;

        if (!$startDateStr || !$endDateStr) {
            // 預設為最近 30 天
            $endDate = new DateTimeImmutable();
            $startDate = $endDate->modify('-30 days');
        } else {
            try {
                $startDate = new DateTimeImmutable($startDateStr);
                $endDate = new DateTimeImmutable($endDateStr);
            } catch (Exception $e) {
                throw ValidationException::fromSingleError('date_format', '日期格式無效，請使用 YYYY-MM-DD 格式');
            }
        }

        if ($startDate > $endDate) {
            throw ValidationException::fromSingleError('date_range', '開始日期不能晚於結束日期');
        }

        return [$startDate, $endDate];
    }

    /**
     * 解析可選日期參數.
     */
    private function parseOptionalDate(?string $dateStr): ?DateTimeImmutable
    {
        if (!$dateStr) {
            return null;
        }

        try {
            return new DateTimeImmutable($dateStr);
        } catch (Exception $e) {
            throw ValidationException::fromSingleError('date_format', '日期格式無效，請使用 YYYY-MM-DD 格式');
        }
    }

    /**
     * 解析整數參數.
     *
     * @param array<string, mixed> $params
     */
    private function parseIntParam(
        array $params,
        string $key,
        int $default,
        int $min = 1,
        int $max = 100,
    ): int {
        $value = $params[$key] ?? $default;

        if (!is_numeric($value)) {
            throw ValidationException::fromSingleError($key, "參數 {$key} 必須為整數");
        }

        $intValue = (int) $value;

        if ($intValue < $min || $intValue > $max) {
            throw ValidationException::fromSingleError($key, "參數 {$key} 必須在 {$min} 到 {$max} 之間");
        }

        return $intValue;
    }

    /**
     * 驗證時間粒度參數.
     */
    private function validateGranularity(string $granularity): void
    {
        $validGranularities = ['hour', 'day', 'week', 'month', 'year'];

        if (!in_array($granularity, $validGranularities)) {
            throw ValidationException::fromSingleError('granularity', '時間粒度無效，支援的粒度: ' . implode(', ', $validGranularities));
        }
    }

    /**
     * 驗證排序參數.
     */
    private function validateSortBy(string $sortBy): void
    {
        $validSortOptions = ['views', 'likes', 'comments'];

        if (!in_array($sortBy, $validSortOptions)) {
            throw ValidationException::fromSingleError('sort_by', '排序選項無效，支援的選項: ' . implode(', ', $validSortOptions));
        }
    }
}
