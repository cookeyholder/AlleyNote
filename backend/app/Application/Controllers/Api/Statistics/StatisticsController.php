<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Application\Services\Statistics\StatisticsQueryService;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

#[OA\Info(
    version: '1.0.0',
    title: 'AlleyNote Statistics API',
    description: 'AlleyNote 統計系統 API 文件',
)]
#[OA\Server(
    url: '/api',
    description: 'API 基礎路徑',
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'object',
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'INVALID_PARAMETER'),
                new OA\Property(property: 'message', type: 'string', example: '錯誤訊息'),
            ],
        ),
    ],
)]
/**
 * 統計資料查詢 API 控制器。
 *
 * 提供統計資料的 REST API 端點，包含概覽、文章、來源、使用者和熱門內容統計
 */
#[OA\Tag(
    name: 'Statistics',
    description: '統計資料相關 API',
)]
class StatisticsController extends BaseController
{
    private StatisticsApplicationService $applicationService;

    private StatisticsQueryService $queryService;

    private LoggerInterface $logger;

    public function __construct(
        StatisticsApplicationService $applicationService,
        StatisticsQueryService $queryService,
        LoggerInterface $logger,
    ) {
        $this->applicationService = $applicationService;
        $this->queryService = $queryService;
        $this->logger = $logger;
    }

    /**
     * 取得統計概覽。
     *
     * GET /api/statistics/overview
     */
    #[OA\Get(
        path: '/api/statistics/overview',
        summary: '取得統計概覽',
        description: '取得指定期間的統計概覽資料，包含文章、使用者、瀏覽量等基本統計',
        tags: ['Statistics'],
        parameters: [
            new OA\Parameter(
                name: 'period_type',
                description: '統計期間類型',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['daily', 'weekly', 'monthly'],
                    default: 'daily',
                ),
            ),
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (格式: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'date',
                    example: '2024-01-01',
                ),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (格式: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'date',
                    example: '2024-01-31',
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得統計概覽',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'period' => new OA\Property(
                                    property: 'period',
                                    type: 'object',
                                    properties: [
                                        'type' => new OA\Property(property: 'type', type: 'string', example: 'daily'),
                                        'start_date' => new OA\Property(property: 'start_date', type: 'string', example: '2024-01-01'),
                                        'end_date' => new OA\Property(property: 'end_date', type: 'string', example: '2024-01-01'),
                                    ],
                                ),
                                'posts' => new OA\Property(
                                    property: 'posts',
                                    type: 'object',
                                    properties: [
                                        'total_count' => new OA\Property(property: 'total_count', type: 'integer', example: 150),
                                        'published_count' => new OA\Property(property: 'published_count', type: 'integer', example: 120),
                                        'draft_count' => new OA\Property(property: 'draft_count', type: 'integer', example: 30),
                                    ],
                                ),
                                'users' => new OA\Property(
                                    property: 'users',
                                    type: 'object',
                                    properties: [
                                        'total_count' => new OA\Property(property: 'total_count', type: 'integer', example: 500),
                                        'active_users' => new OA\Property(property: 'active_users', type: 'integer', example: 80),
                                        'new_registrations' => new OA\Property(property: 'new_registrations', type: 'integer', example: 15),
                                    ],
                                ),
                                'views' => new OA\Property(
                                    property: 'views',
                                    type: 'object',
                                    properties: [
                                        'total_views' => new OA\Property(property: 'total_views', type: 'integer', example: 12500),
                                        'unique_visitors' => new OA\Property(property: 'unique_visitors', type: 'integer', example: 3200),
                                        'average_views_per_post' => new OA\Property(property: 'average_views_per_post', type: 'number', format: 'float', example: 83.33),
                                    ],
                                ),
                            ],
                        ),
                        'meta' => new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                'generated_at' => new OA\Property(property: 'generated_at', type: 'string', format: 'datetime'),
                                'cached' => new OA\Property(property: 'cached', type: 'boolean', example: true),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '請求參數錯誤',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(
                            property: 'error',
                            type: 'object',
                            properties: [
                                'code' => new OA\Property(property: 'code', type: 'string', example: 'INVALID_PARAMETER'),
                                'message' => new OA\Property(property: 'message', type: 'string', example: '無效的期間類型'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 500,
                description: '伺服器內部錯誤',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(
                            property: 'error',
                            type: 'object',
                            properties: [
                                'code' => new OA\Property(property: 'code', type: 'string', example: 'INTERNAL_ERROR'),
                                'message' => new OA\Property(property: 'message', type: 'string', example: '統計資料處理發生錯誤'),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計概覽 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validateOverviewParams($stringKeyParams);
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
    #[OA\Get(
        path: '/api/statistics/posts',
        summary: '取得文章統計',
        description: '取得指定期間的文章統計資料，包含文章數量、狀態分布、來源分析等',
        tags: ['Statistics'],
        parameters: [
            new OA\Parameter(
                name: 'period_type',
                description: '統計期間類型',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['daily', 'weekly', 'monthly'],
                    default: 'daily',
                ),
            ),
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (格式: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'date',
                    example: '2024-01-01',
                ),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (格式: Y-m-d)',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    format: 'date',
                    example: '2024-01-31',
                ),
            ),
            new OA\Parameter(
                name: 'source',
                description: '來源類型篩選',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['web', 'mobile', 'api'],
                    example: 'web',
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得文章統計',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'period' => new OA\Property(
                                    property: 'period',
                                    type: 'object',
                                    properties: [
                                        'type' => new OA\Property(property: 'type', type: 'string', example: 'daily'),
                                        'start_date' => new OA\Property(property: 'start_date', type: 'string', example: '2024-01-01'),
                                        'end_date' => new OA\Property(property: 'end_date', type: 'string', example: '2024-01-01'),
                                    ],
                                ),
                                'total_count' => new OA\Property(property: 'total_count', type: 'integer', example: 150),
                                'status_distribution' => new OA\Property(
                                    property: 'status_distribution',
                                    type: 'object',
                                    properties: [
                                        'published' => new OA\Property(property: 'published', type: 'integer', example: 120),
                                        'draft' => new OA\Property(property: 'draft', type: 'integer', example: 25),
                                        'archived' => new OA\Property(property: 'archived', type: 'integer', example: 5),
                                    ],
                                ),
                                'source_analysis' => new OA\Property(
                                    property: 'source_analysis',
                                    type: 'object',
                                    properties: [
                                        'web' => new OA\Property(property: 'web', type: 'integer', example: 100),
                                        'mobile' => new OA\Property(property: 'mobile', type: 'integer', example: 40),
                                        'api' => new OA\Property(property: 'api', type: 'integer', example: 10),
                                    ],
                                ),
                                'trends' => new OA\Property(
                                    property: 'trends',
                                    type: 'object',
                                    properties: [
                                        'growth_rate' => new OA\Property(property: 'growth_rate', type: 'number', format: 'float', example: 15.5),
                                        'average_daily' => new OA\Property(property: 'average_daily', type: 'number', format: 'float', example: 4.8),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '請求參數錯誤',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 500,
                description: '伺服器內部錯誤',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function posts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('文章統計 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validatePostsParams($stringKeyParams);
            $period = $this->createPeriodFromParams($params);

            $sourceType = is_string($params['source'])
                ? SourceType::from($params['source']) : null;

            $statistics = $this->queryService->getPostStatisticsTrends(
                $period,
                $sourceType,
                is_numeric($params['limit'] ?? null) ? (int) $params['limit'] : 50,
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

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validateSourcesParams($stringKeyParams);
            $period = $this->createPeriodFromParams($params);

            // 使用統計快照來取得來源分佈
            $snapshots = $this->queryService->getStatisticsSnapshots(
                $period->startDate,
                $period->endDate,
                $period->type,
                1,
                100,
            );

            // 從快照中提取來源分佈資料
            $distribution = [];
            $snapshotsData = is_array($snapshots['data'] ?? null) ? $snapshots['data'] : [];
            foreach ($snapshotsData as $snapshot) {
                if (is_array($snapshot) && is_array($snapshot['source_distribution'])) {
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

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validateUsersParams($stringKeyParams);
            $period = $this->createPeriodFromParams($params);

            $statistics = $this->queryService->getUserActivityStatistics(
                $period,
                is_numeric($params['page'] ?? null) ? (int) $params['page'] : 1,
                is_numeric($params['per_page'] ?? null) ? (int) $params['per_page'] : 20,
            );

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
    #[OA\Get(
        path: '/api/statistics/popular',
        summary: '取得熱門內容',
        description: '取得指定期間內最受歡迎的內容清單',
        tags: ['Statistics'],
        parameters: [
            new OA\Parameter(
                name: 'period_type',
                description: '統計期間類型',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['daily', 'weekly', 'monthly'], default: 'daily'),
            ),
            new OA\Parameter(
                name: 'limit',
                description: '回傳項目數量限制',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得熱門內容',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    'id' => new OA\Property(property: 'id', type: 'integer', example: 123),
                                    'title' => new OA\Property(property: 'title', type: 'string', example: '熱門文章標題'),
                                    'views' => new OA\Property(property: 'views', type: 'integer', example: 1250),
                                    'rank' => new OA\Property(property: 'rank', type: 'integer', example: 1),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: '請求參數錯誤', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: '伺服器內部錯誤', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function popular(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('熱門內容 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validatePopularParams($stringKeyParams);
            $period = $this->createPeriodFromParams($params);

            $popularContent = $this->applicationService->analyzePopularContent(
                $period,
                is_numeric($params['limit'] ?? null) ? (int) $params['limit'] : 10,
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

            $queryParams = $request->getQueryParams();
            $stringKeyParams = $this->ensureStringKeys($queryParams);
            $params = $this->validateTrendsParams($stringKeyParams);
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
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
     */
    private function validateOverviewParams(array $queryParams): array
    {
        $params = [];

        // 驗證週期類型
        $periodType = is_string($queryParams['period_type'] ?? null) ? $queryParams['period_type'] : 'daily';
        $params['period_type'] = $this->validatePeriodType($periodType);

        // 驗證日期範圍
        if (isset($queryParams['start_date']) || isset($queryParams['end_date'])) {
            $params = array_merge($params, $this->validateDateRange($queryParams));
        }

        return $params;
    }

    /**
     * 驗證文章統計參數。
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
     */
    private function validatePostsParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證分頁參數
        $pageValue = is_numeric($queryParams['page'] ?? null) ? (int) $queryParams['page'] : 1;
        $perPageValue = is_numeric($queryParams['per_page'] ?? null) ? (int) $queryParams['per_page'] : 20;
        $params['page'] = max(1, $pageValue);
        $params['per_page'] = min(100, max(1, $perPageValue));

        // 驗證排序參數
        $allowedSortFields = ['created_at', 'views', 'title', 'author'];
        $sortBy = is_string($queryParams['sort_by'] ?? null) ? $queryParams['sort_by'] : 'created_at';
        $params['sort_by'] = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';

        $sortOrder = is_string($queryParams['sort_order'] ?? null) ? $queryParams['sort_order'] : 'desc';
        $params['sort_order'] = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        // 驗證來源篩選
        if (isset($queryParams['source'])) {
            $source = is_string($queryParams['source']) ? $queryParams['source'] : '';
            if ($source !== '') {
                $params['source'] = $this->validateSource($source);
            }
        }

        return $params;
    }

    /**
     * 驗證來源分佈參數。
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
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
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
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
        $pageValue = is_numeric($queryParams['page'] ?? null) ? (int) $queryParams['page'] : 1;
        $perPageValue = is_numeric($queryParams['per_page'] ?? null) ? (int) $queryParams['per_page'] : 20;
        $params['page'] = max(1, $pageValue);
        $params['per_page'] = min(100, max(1, $perPageValue));

        return $params;
    }

    /**
     * 驗證熱門內容參數。
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
     */
    private function validatePopularParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證限制數量
        $limitValue = is_numeric($queryParams['limit'] ?? null) ? (int) $queryParams['limit'] : 20;
        $params['limit'] = min(100, max(1, $limitValue));

        // 驗證內容類型
        $allowedTypes = ['posts', 'authors', 'sources', 'tags'];
        $params['type'] = in_array($queryParams['type'] ?? 'posts', $allowedTypes)
            ? $queryParams['type']
            : 'posts';

        return $params;
    }

    /**
     * 驗證趨勢參數。
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
     */
    private function validateTrendsParams(array $queryParams): array
    {
        $params = $this->validateOverviewParams($queryParams);

        // 驗證指標類型
        $allowedMetrics = ['posts', 'views', 'users', 'sources'];
        $metricsString = is_string($queryParams['metrics'] ?? null) ? $queryParams['metrics'] : '';
        $params['metrics'] = $metricsString !== ''
            ? array_intersect(explode(',', $metricsString), $allowedMetrics)
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
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed><string, mixed>
     */
    private function validateDateRange(array $queryParams): array
    {
        $params = [];

        if (isset($queryParams['start_date'])) {
            try {
                $startDate = is_string($queryParams['start_date']) ? $queryParams['start_date'] : '';
                if ($startDate === '') {
                    throw new InvalidArgumentException('開始日期不能為空');
                }
                $params['start_date'] = new DateTimeImmutable($startDate);
            } catch (Exception $e) {
                $dateValue = is_string($queryParams['start_date']) ? $queryParams['start_date'] : '(invalid)';

                throw new InvalidArgumentException("無效的開始日期格式: {$dateValue}");
            }
        }

        if (isset($queryParams['end_date'])) {
            try {
                $endDate = is_string($queryParams['end_date']) ? $queryParams['end_date'] : '';
                if ($endDate === '') {
                    throw new InvalidArgumentException('結束日期不能為空');
                }
                $params['end_date'] = new DateTimeImmutable($endDate);
            } catch (Exception $e) {
                $dateValue = is_string($queryParams['end_date']) ? $queryParams['end_date'] : '(invalid)';

                throw new InvalidArgumentException("無效的結束日期格式: {$dateValue}");
            }
        }

        // 驗證日期範圍邏輯
        if (isset($params['end_date']) && isset($params['start_date'])) {
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
        if (isset($params['end_date'])) {
            $startDate = $params['start_date'];
            $endDate = $params['end_date'];
            if ($startDate instanceof DateTimeInterface && $endDate instanceof DateTimeInterface) {
                return StatisticsPeriod::create(
                    $startDate,
                    $endDate,
                    $periodType,
                );
            }
        }

        // 否則使用預設的週期範圍
        $now = new DateTimeImmutable();

        return match ($periodType) {
            PeriodType::DAILY => StatisticsPeriod::create(
                $now->modify('today midnight'),
                $now->modify('tomorrow midnight -1 second'),
                PeriodType::DAILY,
            ),
            PeriodType::WEEKLY => StatisticsPeriod::create(
                $now->modify('monday this week midnight'),
                $now->modify('sunday this week 23:59:59'),
                PeriodType::WEEKLY,
            ),
            PeriodType::MONTHLY => StatisticsPeriod::create(
                $now->modify('first day of this month midnight'),
                $now->modify('last day of this month 23:59:59'),
                PeriodType::MONTHLY,
            ),
            PeriodType::YEARLY => StatisticsPeriod::create(
                $now->modify('first day of january this year midnight'),
                $now->modify('last day of december this year 23:59:59'),
                PeriodType::YEARLY,
            ),
        };
    }

    /**
     * 確保所有陣列鍵都是字串型態.
     * @param array<mixed, mixed> $params
     * @return array<string, mixed><string, mixed>
     */
    private function ensureStringKeys(array $params): array
    {
        $stringKeyParams = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $stringKeyParams[$key] = $value;
            }
        }

        return $stringKeyParams;
    }
}
