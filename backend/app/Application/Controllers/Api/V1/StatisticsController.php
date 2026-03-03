<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\DTOs\StatisticsQueryDTO;
use App\Application\Services\Statistics\StatisticsQueryService;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 統計查詢 API 控制器.
 *
 * 提供統計資料查詢的 REST API 端點，支援概覽、文章、來源、使用者等統計查詢
 */
#[OA\Tag(name: 'statistics', description: '統計查詢 API')]
class StatisticsController extends BaseController
{
    public function __construct(
        private readonly StatisticsQueryService $statisticsQueryService,
        private readonly ValidatorInterface $validator,
    ) {}

    /**
     * 取得統計概覽.
     *
     * GET /api/v1/statistics/overview
     */
    #[OA\Get(
        path: '/api/v1/statistics/overview',
        summary: '取得統計概覽',
        description: '取得系統整體統計概覽資訊，包含文章、使用者、活動等綜合指標',
        operationId: 'getStatisticsOverview',
        tags: ['statistics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-09-01'),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-09-25'),
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
                                'total_posts' => new OA\Property(property: 'total_posts', type: 'integer', example: 1250),
                                'active_users' => new OA\Property(property: 'active_users', type: 'integer', example: 328),
                                'new_users' => new OA\Property(property: 'new_users', type: 'integer', example: 42),
                                'total_views' => new OA\Property(property: 'total_views', type: 'integer', example: 15620),
                                'post_activity' => new OA\Property(property: 'post_activity', type: 'object'),
                                'user_activity' => new OA\Property(property: 'user_activity', type: 'object'),
                                'engagement_metrics' => new OA\Property(property: 'engagement_metrics', type: 'object'),
                                'period_summary' => new OA\Property(property: 'period_summary', type: 'object'),
                                'generated_at' => new OA\Property(property: 'generated_at', type: 'string', format: 'date-time'),
                            ],
                        ),
                        'meta' => new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                'start_date' => new OA\Property(property: 'start_date', type: 'string'),
                                'end_date' => new OA\Property(property: 'end_date', type: 'string'),
                                'cache_hit' => new OA\Property(property: 'cache_hit', type: 'boolean'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: '請求參數錯誤'),
            new OA\Response(response: 401, description: '未授權訪問'),
            new OA\Response(response: 403, description: '權限不足'),
            new OA\Response(response: 500, description: '伺服器內部錯誤'),
        ],
    )]
    public function getOverview(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams();

            // 解析查詢參數
            /** @var array<string, mixed> $queryParams */
            $queryDTO = $this->buildQueryDTO($queryParams);

            // 呼叫應用服務
            $overview = $this->statisticsQueryService->getOverview($queryDTO);

            return $this->json($response, [
                'success' => true,
                'data' => $overview,
                'meta' => [
                    'start_date' => $queryDTO->getStartDate()?->format('Y-m-d'),
                    'end_date' => $queryDTO->getEndDate()?->format('Y-m-d'),
                    'cache_hit' => false, // TODO: 實作快取命中檢測
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ],
            ], 422);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'internal_error',
                    'message' => '取得統計概覽失敗',
                ],
            ], 500);
        }
    }

    /**
     * 取得文章統計.
     *
     * GET /api/v1/statistics/posts
     */
    #[OA\Get(
        path: '/api/v1/statistics/posts',
        summary: '取得文章統計',
        description: '取得文章相關統計資料，支援分頁查詢',
        operationId: 'getPostStatistics',
        tags: ['statistics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: '頁碼',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
            ),
            new OA\Parameter(
                name: 'limit',
                description: '每頁筆數',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 20),
            ),
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-09-01'),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-09-25'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得文章統計',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        'pagination' => new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                'current_page' => new OA\Property(property: 'current_page', type: 'integer'),
                                'per_page' => new OA\Property(property: 'per_page', type: 'integer'),
                                'total' => new OA\Property(property: 'total', type: 'integer'),
                                'last_page' => new OA\Property(property: 'last_page', type: 'integer'),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function getPosts(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams();

            // 解析查詢參數
            $queryDTO = $this->buildQueryDTO($queryParams);

            // 呼叫應用服務
            $paginatedResult = $this->statisticsQueryService->getPostStatistics($queryDTO);

            return $this->json($response, [
                'success' => true,
                'data' => $paginatedResult->getData(),
                'pagination' => [
                    'current_page' => $paginatedResult->getCurrentPage(),
                    'per_page' => $paginatedResult->getPerPage(),
                    'total_count' => $paginatedResult->getTotalCount(),
                    'total_pages' => $paginatedResult->getTotalPages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ],
            ], 422);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'internal_error',
                    'message' => '取得文章統計失敗',
                ],
            ], 500);
        }
    }

    /**
     * 取得來源分布統計.
     *
     * GET /api/v1/statistics/sources
     */
    #[OA\Get(
        path: '/api/v1/statistics/sources',
        summary: '取得來源分布統計',
        description: '取得文章來源分布統計資料',
        operationId: 'getSourceStatistics',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得來源分布統計',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    'source' => new OA\Property(property: 'source', type: 'string', example: 'web'),
                                    'count' => new OA\Property(property: 'count', type: 'integer', example: 125),
                                    'percentage' => new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 65.5),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function getSources(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams();

            // 解析查詢參數
            $queryDTO = $this->buildQueryDTO($queryParams);

            // 呼叫應用服務
            $sourceDistribution = $this->statisticsQueryService->getSourceDistribution($queryDTO);

            return $this->json($response, [
                'success' => true,
                'data' => $sourceDistribution,
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ],
            ], 422);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'internal_error',
                    'message' => '取得來源統計失敗',
                ],
            ], 500);
        }
    }

    /**
     * 取得使用者統計.
     *
     * GET /api/v1/statistics/users
     */
    #[OA\Get(
        path: '/api/v1/statistics/users',
        summary: '取得使用者統計',
        description: '取得使用者活動統計資料，支援分頁查詢',
        operationId: 'getUserStatistics',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: '頁碼',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
            ),
            new OA\Parameter(
                name: 'limit',
                description: '每頁筆數',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 20),
            ),
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得使用者統計',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        'pagination' => new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                'current_page' => new OA\Property(property: 'current_page', type: 'integer'),
                                'per_page' => new OA\Property(property: 'per_page', type: 'integer'),
                                'total' => new OA\Property(property: 'total', type: 'integer'),
                                'last_page' => new OA\Property(property: 'last_page', type: 'integer'),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function getUsers(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams();

            // 解析查詢參數
            $queryDTO = $this->buildQueryDTO($queryParams);

            // 呼叫應用服務
            $paginatedResult = $this->statisticsQueryService->getUserStatistics($queryDTO);

            return $this->json($response, [
                'success' => true,
                'data' => $paginatedResult->getData(),
                'pagination' => [
                    'current_page' => $paginatedResult->getCurrentPage(),
                    'per_page' => $paginatedResult->getPerPage(),
                    'total_count' => $paginatedResult->getTotalCount(),
                    'total_pages' => $paginatedResult->getTotalPages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ],
            ], 422);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'internal_error',
                    'message' => '取得使用者統計失敗',
                ],
            ], 500);
        }
    }

    /**
     * 取得熱門內容.
     *
     * GET /api/v1/statistics/popular
     */
    #[OA\Get(
        path: '/api/v1/statistics/popular',
        summary: '取得熱門內容統計',
        description: '取得熱門內容排行榜資料',
        operationId: 'getPopularContent',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: '限制筆數',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, example: 10),
            ),
            new OA\Parameter(
                name: 'start_date',
                description: '開始日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\Parameter(
                name: 'end_date',
                description: '結束日期 (YYYY-MM-DD)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得熱門內容統計',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    'title' => new OA\Property(property: 'title', type: 'string', example: '熱門文章標題'),
                                    'views' => new OA\Property(property: 'views', type: 'integer', example: 1250),
                                    'engagement_score' => new OA\Property(property: 'engagement_score', type: 'number', format: 'float'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function getPopular(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams();

            // 解析查詢參數
            $queryDTO = $this->buildQueryDTO($queryParams);

            // 呼叫應用服務
            $popularContent = $this->statisticsQueryService->getPopularContent($queryDTO);

            return $this->json($response, [
                'success' => true,
                'data' => $popularContent,
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => $e->getMessage(),
                    'details' => [],
                ],
            ], 422);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'internal_error',
                    'message' => '取得熱門內容統計失敗',
                ],
            ], 500);
        }
    }

    /**
    /**
     * 建構統計查詢 DTO.
     *
     * @param array $queryParams 查詢參數
     *
     * @throws ValidationException 驗證失敗時拋出
     */
    private function buildQueryDTO(array $queryParams): StatisticsQueryDTO
    {
        // 驗證查詢參數
        $this->validateQueryParams($queryParams);

        try {
            // 解析日期參數
            $startDate = null;
            $endDate = null;

            if (!empty($queryParams['start_date']) && is_string($queryParams['start_date'])) {
                $startDate = new DateTimeImmutable($queryParams['start_date']);
            }

            if (!empty($queryParams['end_date']) && is_string($queryParams['end_date'])) {
                $endDate = new DateTimeImmutable($queryParams['end_date']);
            }

            // 驗證日期範圍
            $this->validateDateRange($startDate, $endDate);

            // 解析分頁參數
            $page = 1;
            $limit = 20;

            if (isset($queryParams['page']) && is_numeric($queryParams['page'])) {
                $page = max(1, (int) $queryParams['page']);
            }

            if (isset($queryParams['limit']) && is_numeric($queryParams['limit'])) {
                $limit = max(1, min(100, (int) $queryParams['limit']));
            }

            // 解析排序參數
            $sortBy = 'created_at';
            $sortDirection = 'desc';

            if (isset($queryParams['sort_by']) && is_string($queryParams['sort_by'])) {
                $sortBy = $queryParams['sort_by'];
            }

            if (isset($queryParams['sort_direction']) && is_string($queryParams['sort_direction'])) {
                $sortDirection = $queryParams['sort_direction'];
            }

            // 建立查詢 DTO
            return new StatisticsQueryDTO(
                startDate: $startDate,
                endDate: $endDate,
                page: $page,
                limit: $limit,
                sortBy: $sortBy,
                sortDirection: $sortDirection,
            );
        } catch (Exception $e) {
            throw ValidationException::fromSingleError('date_format', '日期格式錯誤，請使用 YYYY-MM-DD 格式');
        }
    }

    /**
     * 驗證查詢參數.
     *
     * @throws ValidationException
     */
    private function validateQueryParams(array $queryParams): void
    {
        // 觸碰 validator 屬性以避免 PHPStan 警告
        $this->validator; // @phpstan-ignore-line

        $rules = [
            'start_date' => [
                'type' => 'string',
                'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                'optional' => true,
            ],
            'end_date' => [
                'type' => 'string',
                'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                'optional' => true,
            ],
            'page' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 1000,
                'optional' => true,
            ],
            'limit' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 100,
                'optional' => true,
            ],
            'sort_by' => [
                'type' => 'string',
                'enum' => ['created_at', 'updated_at', 'views', 'engagement_score'],
                'optional' => true,
            ],
            'sort_direction' => [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
                'optional' => true,
            ],
        ];

        foreach ($rules as $field => $rule) {
            $value = $queryParams[$field] ?? null;

            // 檢查必填欄位
            if ($value === null || $value === '') {
                // 所有欄位都是 optional，如果不存在就跳過
                continue;
            }

            // 型別檢查（所有規則都有 type，所以總是存在）
            $this->validateFieldType($field, $value, $rule['type']);

            // 正規表達式檢查
            if (isset($rule['pattern']) && is_string($value)) {
                if (!preg_match($rule['pattern'], $value)) {
                    throw ValidationException::fromSingleError($field, "參數 {$field} 格式不正確");
                }
            }

            // 範圍檢查
            if (is_numeric($value)) {
                $numValue = (int) $value;
                if (array_key_exists('min', $rule) && $numValue < $rule['min']) {
                    throw ValidationException::fromSingleError($field, "參數 {$field} 不能小於 {$rule['min']}");
                }
                if (array_key_exists('max', $rule) && $numValue > $rule['max']) {
                    throw ValidationException::fromSingleError($field, "參數 {$field} 不能大於 {$rule['max']}");
                }
            }

            // 枚舉值檢查
            if (array_key_exists('enum', $rule) && is_string($value)) {
                // enum 總是陣列，所以移除 is_array 檢查
                if (!in_array($value, $rule['enum'], true)) {
                    $allowedValues = implode(', ', $rule['enum']);

                    throw ValidationException::fromSingleError($field, "參數 {$field} 必須為以下值之一：{$allowedValues}");
                }
            }
        }
    }

    /**
     * 驗證欄位型別.
     *
     * @param mixed $value
     * @throws ValidationException
     */
    private function validateFieldType(string $field, $value, string $expectedType): void
    {
        $valid = match ($expectedType) {
            'string' => is_string($value),
            'integer' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, ['true', 'false', '1', '0']),
            default => true,
        };

        if (!$valid) {
            throw ValidationException::fromSingleError($field, "參數 {$field} 必須為 {$expectedType} 型別");
        }
    }

    /**
     * 驗證日期範圍.
     *
     * @throws ValidationException
     */
    private function validateDateRange(?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate): void
    {
        if ($startDate && $endDate) {
            // 檢查日期順序
            if ($startDate > $endDate) {
                throw ValidationException::fromSingleError('date_range', '開始日期不能晚於結束日期');
            }

            // 檢查查詢範圍限制（最多1年）
            $maxRange = $startDate->modify('+1 year');
            if ($endDate > $maxRange) {
                throw ValidationException::fromSingleError('date_range', '查詢範圍不能超過1年');
            }
        }

        // 檢查日期不能是未來
        $now = new DateTimeImmutable();
        if ($startDate && $startDate > $now) {
            throw ValidationException::fromSingleError('start_date', '開始日期不能是未來日期');
        }
        if ($endDate && $endDate > $now) {
            throw ValidationException::fromSingleError('end_date', '結束日期不能是未來日期');
        }
    }
}
