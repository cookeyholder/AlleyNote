<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Application\Services\Statistics\StatisticsQueryService;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * 統計資料查詢 API 控制器。
 *
 * 提供統計資料的 REST API 端點，包含概覽、文章、來源、使用者和熱門內容統計
 */
class StatisticsController extends BaseController
{
    private StatisticsApplicationService $applicationService;
    private StatisticsQueryService $queryService;
    private StatisticsCacheServiceInterface $cacheService;
    private LoggerInterface $logger;

    public function __construct(
        StatisticsApplicationService $applicationService,
        StatisticsQueryService $queryService,
        StatisticsCacheServiceInterface $cacheService,
        LoggerInterface $logger
    ) {
        $this->applicationService = $applicationService;
        $this->queryService = $queryService;
        $this->cacheService = $cacheService;
        $this->logger = $logger;
    }

    /**
     * 取得統計概覽。
     * 
     * GET /api/statistics/overview
     */
    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計概覽 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validateOverviewParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            $overview = $this->applicationService->getStatisticsOverview($period);

            $this->logger->info('統計概覽 API 成功回應', [
                'period_type' => $period->type->value,
                'data_size' => count($overview),
            ]);

            $response->getBody()->write($this->successResponse($overview, '統計概覽取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('統計概覽 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('統計概覽 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('統計概覽取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得文章統計。
     * 
     * GET /api/statistics/posts
     */
    public function posts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('文章統計 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validatePostsParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            $sourceType = isset($params['source']) ? 
                SourceType::from($params['source']) : null;
            
            $statistics = $this->queryService->getPostStatisticsTrends(
                $period, 
                $sourceType,
                $params['data_points'] ?? 30
            );

            $this->logger->info('文章統計 API 成功回應', [
                'period_type' => $period->type->value,
                'data_size' => count($statistics),
            ]);

            $response->getBody()->write($this->successResponse($statistics, '文章統計取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('文章統計 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('文章統計 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('文章統計取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得來源分佈統計。
     * 
     * GET /api/statistics/sources
     */
    public function sources(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('來源分佈統計 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validateSourcesParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            // 使用統計快照來取得來源分佈
            $snapshots = $this->queryService->getStatisticsSnapshots(
                $period->startDate,
                $period->endDate,
                $period->type,
                1,
                100
            );
            
            // 從快照中提取來源分佈資料
            $distribution = [];
            foreach ($snapshots['data'] as $snapshot) {
                if (isset($snapshot['source_distribution'])) {
                    $distribution = array_merge($distribution, $snapshot['source_distribution']);
                }
            }

            $this->logger->info('來源分佈統計 API 成功回應', [
                'period_type' => $period->type->value,
                'sources_count' => count($distribution),
            ]);

            $response->getBody()->write($this->successResponse($distribution, '來源分佈統計取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('來源分佈統計 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('來源分佈統計 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('來源分佈統計取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得使用者統計。
     * 
     * GET /api/statistics/users
     */
    public function users(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('使用者統計 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validateUsersParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            $statistics = $this->queryService->getUserActivityStatistics($period, $params['page'] ?? 1, $params['per_page'] ?? 20);

            $this->logger->info('使用者統計 API 成功回應', [
                'period_type' => $period->type->value,
                'data_size' => count($statistics),
            ]);

            $response->getBody()->write($this->successResponse($statistics, '使用者統計取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('使用者統計 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('使用者統計 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('使用者統計取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得熱門內容。
     * 
     * GET /api/statistics/popular
     */
    public function popular(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('熱門內容 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validatePopularParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            $popularContent = $this->applicationService->analyzePopularContent(
                $period,
                $params['limit'] ?? 20
            );

            $this->logger->info('熱門內容 API 成功回應', [
                'period_type' => $period->type->value,
                'content_count' => count($popularContent),
                'limit' => $params['limit'] ?? 20,
            ]);

            $response->getBody()->write($this->successResponse($popularContent, '熱門內容取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('熱門內容 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('熱門內容 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('熱門內容取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得統計趨勢。
     * 
     * GET /api/statistics/trends
     */
    public function trends(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計趨勢 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $params = $this->validateTrendsParams($request->getQueryParams());
            $period = $this->createPeriodFromParams($params);

            $trends = $this->queryService->getPostStatisticsTrends($period, null, 30);

            $this->logger->info('統計趨勢 API 成功回應', [
                'period_type' => $period->type->value,
                'trends_count' => count($trends),
            ]);

            $response->getBody()->write($this->successResponse($trends, '統計趨勢取得成功'));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (InvalidArgumentException $e) {
            $this->logger->warning('統計趨勢 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

        } catch (Exception $e) {
            $this->logger->error('統計趨勢 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('統計趨勢取得失敗', 500));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 驗證概覽參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateOverviewParams(array $queryParams): array
    {
        $params = [];

        // 驗證週期類型
        $params['period_type'] = $this->validatePeriodType($queryParams['period_type'] ?? 'daily');

        // 驗證日期範圍
        if (isset($queryParams['start_date']) || isset($queryParams['end_date'])) {
            $params = array_merge($params, $this->validateDateRange($queryParams));
        }

        return $params;
    }

    /**
     * 驗證文章統計參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validatePostsParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證分頁參數
        $params['page'] = max(1, (int) ($queryParams['page'] ?? 1));
        $params['per_page'] = min(100, max(1, (int) ($queryParams['per_page'] ?? 20)));

        // 驗證排序參數
        $allowedSortFields = ['created_at', 'views', 'title', 'author'];
        $params['sort_by'] = in_array($queryParams['sort_by'] ?? 'created_at', $allowedSortFields)
            ? $queryParams['sort_by']
            : 'created_at';

        $params['sort_order'] = in_array($queryParams['sort_order'] ?? 'desc', ['asc', 'desc'])
            ? $queryParams['sort_order']
            : 'desc';

        // 驗證來源篩選
        if (isset($queryParams['source'])) {
            $params['source'] = $this->validateSource($queryParams['source']);
        }

        return $params;
    }

    /**
     * 驗證來源分佈參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateSourcesParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證分組方式
        $allowedGroupBy = ['source', 'date', 'hour'];
        $params['group_by'] = in_array($queryParams['group_by'] ?? 'source', $allowedGroupBy)
            ? $queryParams['group_by']
            : 'source';

        return $params;
    }

    /**
     * 驗證使用者統計參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateUsersParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證統計類型
        $allowedTypes = ['registration', 'activity', 'retention', 'engagement'];
        $params['type'] = in_array($queryParams['type'] ?? 'activity', $allowedTypes)
            ? $queryParams['type']
            : 'activity';

        // 驗證分頁參數
        $params['page'] = max(1, (int) ($queryParams['page'] ?? 1));
        $params['per_page'] = min(100, max(1, (int) ($queryParams['per_page'] ?? 20)));

        return $params;
    }

    /**
     * 驗證熱門內容參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validatePopularParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證限制數量
        $params['limit'] = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));

        // 驗證內容類型
        $allowedTypes = ['posts', 'authors', 'sources', 'tags'];
        $params['type'] = in_array($queryParams['type'] ?? 'posts', $allowedTypes)
            ? $queryParams['type']
            : 'posts';

        return $params;
    }

    /**
     * 驗證趨勢參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateTrendsParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證指標類型
        $allowedMetrics = ['posts', 'views', 'users', 'sources'];
        $params['metrics'] = isset($queryParams['metrics'])
            ? array_intersect(explode(',', $queryParams['metrics']), $allowedMetrics)
            : ['posts', 'views'];

        if (empty($params['metrics'])) {
            $params['metrics'] = ['posts'];
        }

        // 驗證時間粒度
        $allowedGranularity = ['hour', 'day', 'week', 'month'];
        $params['granularity'] = in_array($queryParams['granularity'] ?? 'day', $allowedGranularity)
            ? $queryParams['granularity']
            : 'day';

        return $params;
    }

    /**
     * 驗證週期類型。
     */
    private function validatePeriodType(string $periodType): string
    {
        $validPeriods = ['daily', 'weekly', 'monthly', 'yearly'];
        
        if (!in_array($periodType, $validPeriods)) {
            throw new InvalidArgumentException("無效的週期類型: {$periodType}。支援的類型: " . implode(', ', $validPeriods));
        }

        return $periodType;
    }

    /**
     * 驗證日期範圍。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateDateRange(array $queryParams): array
    {
        $params = [];

        if (isset($queryParams['start_date'])) {
            try {
                $params['start_date'] = new DateTimeImmutable($queryParams['start_date']);
            } catch (Exception $e) {
                throw new InvalidArgumentException("無效的開始日期格式: {$queryParams['start_date']}");
            }
        }

        if (isset($queryParams['end_date'])) {
            try {
                $params['end_date'] = new DateTimeImmutable($queryParams['end_date']);
            } catch (Exception $e) {
                throw new InvalidArgumentException("無效的結束日期格式: {$queryParams['end_date']}");
            }
        }

        // 驗證日期範圍邏輯
        if (isset($params['start_date']) && isset($params['end_date'])) {
            if ($params['start_date'] > $params['end_date']) {
                throw new InvalidArgumentException('開始日期不能晚於結束日期');
            }
        }

        return $params;
    }

    /**
     * 驗證來源類型。
     */
    private function validateSource(string $source): string
    {
        $validSources = ['web', 'mobile', 'api', 'import'];
        
        if (!in_array($source, $validSources)) {
            throw new InvalidArgumentException("無效的來源類型: {$source}。支援的類型: " . implode(', ', $validSources));
        }

        return $source;
    }

    /**
     * 從參數建立統計週期。
     *
     * @param array<string, mixed> $params
     */
    private function createPeriodFromParams(array $params): StatisticsPeriod
    {
        $periodType = match ($params['period_type']) {
            'daily' => PeriodType::DAILY,
            'weekly' => PeriodType::WEEKLY,
            'monthly' => PeriodType::MONTHLY,
            'yearly' => PeriodType::YEARLY,
            default => PeriodType::DAILY,
        };

        // 如果有指定日期範圍，使用自訂範圍
        if (isset($params['start_date']) && isset($params['end_date'])) {
            return StatisticsPeriod::create(
                $params['start_date'],
                $params['end_date'],
                $periodType
            );
        }

        // 否則使用預設的週期範圍
        $now = new DateTimeImmutable();

        return match ($periodType) {
            PeriodType::DAILY => StatisticsPeriod::create(
                $now->modify('today midnight'),
                $now->modify('tomorrow midnight -1 second'),
                PeriodType::DAILY
            ),
            PeriodType::WEEKLY => StatisticsPeriod::create(
                $now->modify('monday this week midnight'),
                $now->modify('sunday this week 23:59:59'),
                PeriodType::WEEKLY
            ),
            PeriodType::MONTHLY => StatisticsPeriod::create(
                $now->modify('first day of this month midnight'),
                $now->modify('last day of this month 23:59:59'),
                PeriodType::MONTHLY
            ),
            PeriodType::YEARLY => StatisticsPeriod::create(
                $now->modify('first day of january this year midnight'),
                $now->modify('last day of december this year 23:59:59'),
                PeriodType::YEARLY
            ),
        };
    }
}
