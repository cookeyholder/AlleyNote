<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\DTOs\StatisticsQueryDTO;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Application\Services\Statistics\StatisticsQueryService;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 統計管理 API 控制器.
 *
 * 提供管理員專用的統計管理功能，包括快取管理、系統健康檢查、統計刷新等操作
 */
#[OA\Tag(name: 'statistics-admin', description: '統計管理 API (管理員專用)')]
class StatisticsAdminController extends BaseController
{
    public function __construct(
        private readonly StatisticsApplicationService $statisticsApplicationService,
        private readonly StatisticsQueryService $statisticsQueryService,
        private readonly StatisticsCacheServiceInterface $cacheService,
    ) {}

    /**
     * 手動刷新統計資料.
     *
     * POST /api/admin/statistics/refresh
     */
    #[OA\Post(
        path: '/api/admin/statistics/refresh',
        summary: '手動刷新統計資料',
        description: '立即重新計算並更新統計資料，包括快取預熱',
        security: [['Bearer' => []]],
        tags: ['statistics-admin'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        'types' => new OA\Property(
                            property: 'types',
                            type: 'array',
                            items: new OA\Items(
                                type: 'string',
                                enum: ['overview', 'posts', 'users', 'popular', 'sources'],
                            ),
                            description: '要刷新的統計類型，不指定則刷新全部',
                            example: ['overview', 'posts'],
                        ),
                        'force_recalculate' => new OA\Property(
                            property: 'force_recalculate',
                            type: 'boolean',
                            description: '是否強制重新計算，否則僅清除快取',
                            example: true,
                        ),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '刷新成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '統計資料刷新成功'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'refreshed_types' => new OA\Property(
                                    property: 'refreshed_types',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                ),
                                'cache_cleared' => new OA\Property(property: 'cache_cleared', type: 'boolean'),
                                'snapshots_created' => new OA\Property(property: 'snapshots_created', type: 'integer'),
                                'execution_time' => new OA\Property(property: 'execution_time', type: 'number'),
                                'timestamp' => new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: '請求參數錯誤'),
            new OA\Response(response: 401, description: '未授權訪問'),
            new OA\Response(response: 403, description: '權限不足'),
            new OA\Response(response: 500, description: '刷新失敗'),
        ],
    )]
    public function refresh(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $startTime = microtime(true);

            // 檢查管理員權限
            $this->checkAdminPermission($request);

            $body = $request->getParsedBody() ?? [];

            // 確保 body 是陣列類型
            if (!is_array($body)) {
                $body = [];
            }

            $types = $body['types'] ?? ['overview', 'posts', 'users', 'popular', 'sources'];
            $forceRecalculate = $body['force_recalculate'] ?? true;

            // 確保 types 是陣列類型
            if (!is_array($types)) {
                $types = ['overview', 'posts', 'users', 'popular', 'sources'];
            }

            // 驗證統計類型
            $validTypes = ['overview', 'posts', 'users', 'popular', 'sources'];
            $invalidTypes = array_diff($types, $validTypes);
            if (!empty($invalidTypes)) {
                throw ValidationException::fromSingleError(
                    'types',
                    '無效的統計類型：' . implode(', ', $invalidTypes),
                );
            }

            $refreshedTypes = [];
            $snapshotsCreated = 0;

            // 清除相關快取
            /** @var array<string> $cacheTags */
            $cacheTags = array_merge(['statistics'], $types);
            $this->cacheService->flushByTags($cacheTags);

            if ($forceRecalculate) {
                // 強制重新計算統計快照
                foreach ($types as $type) {
                    if (!is_string($type)) {
                        continue;
                    }

                    try {
                        $snapshot = match ($type) {
                            'overview' => $this->statisticsApplicationService->createOverviewSnapshot(
                                new StatisticsPeriod(
                                    PeriodType::MONTHLY,
                                    new DateTimeImmutable('-30 days'),
                                    new DateTimeImmutable(),
                                ),
                            ),
                            'posts' => $this->statisticsApplicationService->createPostsSnapshot(
                                new StatisticsPeriod(
                                    PeriodType::MONTHLY,
                                    new DateTimeImmutable('-30 days'),
                                    new DateTimeImmutable(),
                                ),
                            ),
                            'users' => $this->statisticsApplicationService->createUsersSnapshot(
                                new StatisticsPeriod(
                                    PeriodType::MONTHLY,
                                    new DateTimeImmutable('-30 days'),
                                    new DateTimeImmutable(),
                                ),
                            ),
                            'popular' => $this->statisticsApplicationService->createPopularSnapshot(
                                new StatisticsPeriod(
                                    PeriodType::WEEKLY,
                                    new DateTimeImmutable('-7 days'),
                                    new DateTimeImmutable(),
                                ),
                            ),
                            'sources' => null, // 來源統計不需要快照，直接從資料庫計算
                            default => null,
                        };

                        if ($snapshot !== null) {
                            $snapshotsCreated++;
                        }
                        $refreshedTypes[] = $type;
                    } catch (Throwable $e) {
                        // 記錄錯誤但繼續處理其他類型
                        error_log("Failed to refresh statistics type '{$type}': " . $e->getMessage());
                    }
                }
            } else {
                // 僅清除快取，不重新計算
                $refreshedTypes = $types;
            }

            $executionTime = round(microtime(true) - $startTime, 3);

            // 記錄管理操作
            $this->logAdminAction($request, 'statistics_refresh', [
                'types' => $refreshedTypes,
                'force_recalculate' => $forceRecalculate,
                'execution_time' => $executionTime,
            ]);

            return $this->json($response, [
                'success' => true,
                'message' => '統計資料刷新成功',
                'data' => [
                    'refreshed_types' => $refreshedTypes,
                    'cache_cleared' => true,
                    'snapshots_created' => $snapshotsCreated,
                    'execution_time' => $executionTime,
                    'timestamp' => new DateTimeImmutable()->format('c'),
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 400);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => '統計資料刷新失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清除統計快取.
     *
     * DELETE /api/admin/statistics/cache
     */
    #[OA\Delete(
        path: '/api/admin/statistics/cache',
        summary: '清除統計快取',
        description: '清除指定標籤的統計快取資料',
        security: [['Bearer' => []]],
        tags: ['statistics-admin'],
        parameters: [
            new OA\Parameter(
                name: 'tags',
                description: '要清除的快取標籤，多個標籤用逗號分隔',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    example: 'overview,posts,users',
                ),
            ),
            new OA\Parameter(
                name: 'all',
                description: '是否清除所有統計快取',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'boolean',
                    example: false,
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '快取清除成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '快取清除成功'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'cleared_tags' => new OA\Property(
                                    property: 'cleared_tags',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                ),
                                'all_cache_cleared' => new OA\Property(property: 'all_cache_cleared', type: 'boolean'),
                                'timestamp' => new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: '請求參數錯誤'),
            new OA\Response(response: 401, description: '未授權訪問'),
            new OA\Response(response: 403, description: '權限不足'),
            new OA\Response(response: 500, description: '快取清除失敗'),
        ],
    )]
    public function clearCache(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            // 檢查管理員權限
            $this->checkAdminPermission($request);

            $queryParams = $request->getQueryParams();
            $tagsParam = $queryParams['tags'] ?? '';
            $clearAll = ($queryParams['all'] ?? 'false') === 'true';

            // 確保 tagsParam 是字串
            if (!is_string($tagsParam)) {
                $tagsParam = '';
            }

            $clearedTags = [];
            $allCacheCleared = false;

            if ($clearAll) {
                // 清除所有統計快取
                $this->cacheService->flush();
                $allCacheCleared = true;
                $clearedTags = ['*'];
            } elseif (!empty($tagsParam)) {
                // 清除指定標籤的快取
                $tags = array_map('trim', explode(',', $tagsParam));
                $validTags = ['statistics', 'overview', 'posts', 'users', 'popular', 'trends', 'sources', 'prewarmed'];
                $invalidTags = array_diff($tags, $validTags);

                if (!empty($invalidTags)) {
                    throw ValidationException::fromSingleError(
                        'tags',
                        '無效的快取標籤：' . implode(', ', $invalidTags),
                    );
                }

                $this->cacheService->flushByTags($tags);
                $clearedTags = $tags;
            } else {
                // 預設清除一般統計快取
                $defaultTags = ['statistics', 'overview', 'posts', 'users'];
                $this->cacheService->flushByTags($defaultTags);
                $clearedTags = $defaultTags;
            }

            // 記錄管理操作
            $this->logAdminAction($request, 'cache_clear', [
                'tags' => $clearedTags,
                'all_cache_cleared' => $allCacheCleared,
            ]);

            return $this->json($response, [
                'success' => true,
                'message' => '快取清除成功',
                'data' => [
                    'cleared_tags' => $clearedTags,
                    'all_cache_cleared' => $allCacheCleared,
                    'timestamp' => new DateTimeImmutable()->format('c'),
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 400);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => '快取清除失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 統計系統健康檢查.
     *
     * GET /api/admin/statistics/health
     */
    #[OA\Get(
        path: '/api/admin/statistics/health',
        summary: '統計系統健康檢查',
        description: '檢查統計系統的運行狀態，包括快取狀態、資料庫連接、服務可用性等',
        security: [['Bearer' => []]],
        tags: ['statistics-admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: '健康檢查成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '系統狀態正常'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'overall_status' => new OA\Property(
                                    property: 'overall_status',
                                    type: 'string',
                                    enum: ['healthy', 'warning', 'critical'],
                                    example: 'healthy',
                                ),
                                'cache' => new OA\Property(
                                    property: 'cache',
                                    type: 'object',
                                    properties: [
                                        'status' => new OA\Property(property: 'status', type: 'string'),
                                        'hits' => new OA\Property(property: 'hits', type: 'integer'),
                                        'misses' => new OA\Property(property: 'misses', type: 'integer'),
                                        'hit_rate' => new OA\Property(property: 'hit_rate', type: 'number'),
                                    ],
                                ),
                                'database' => new OA\Property(
                                    property: 'database',
                                    type: 'object',
                                    properties: [
                                        'status' => new OA\Property(property: 'status', type: 'string'),
                                        'connection_time' => new OA\Property(property: 'connection_time', type: 'number'),
                                    ],
                                ),
                                'services' => new OA\Property(
                                    property: 'services',
                                    type: 'object',
                                    properties: [
                                        'statistics_application_service' => new OA\Property(property: 'statistics_application_service', type: 'string'),
                                        'statistics_query_service' => new OA\Property(property: 'statistics_query_service', type: 'string'),
                                        'cache_service' => new OA\Property(property: 'cache_service', type: 'string'),
                                    ],
                                ),
                                'timestamp' => new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: '未授權訪問'),
            new OA\Response(response: 403, description: '權限不足'),
            new OA\Response(response: 500, description: '健康檢查失敗'),
        ],
    )]
    public function health(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            // 檢查管理員權限
            $this->checkAdminPermission($request);

            $healthData = [];
            $overallStatus = 'healthy';

            // 檢查快取狀態
            try {
                $cacheStats = $this->cacheService->getStats();
                $hits = is_int($cacheStats['hits']) ? $cacheStats['hits'] : 0;
                $misses = is_int($cacheStats['misses']) ? $cacheStats['misses'] : 0;
                $totalRequests = $hits + $misses;
                $hitRate = $totalRequests > 0 ? round($hits / $totalRequests * 100, 2) : 0;

                $healthData['cache'] = [
                    'status' => 'healthy',
                    'hits' => $hits,
                    'misses' => $misses,
                    'hit_rate' => $hitRate,
                ];

                // 如果命中率過低，標記為警告
                if ($hitRate < 50 && $totalRequests > 100) {
                    $healthData['cache']['status'] = 'warning';
                    $overallStatus = 'warning';
                }
            } catch (Throwable $e) {
                $healthData['cache'] = [
                    'status' => 'critical',
                    'error' => $e->getMessage(),
                ];
                $overallStatus = 'critical';
            }

            // 檢查資料庫連接
            try {
                $startTime = microtime(true);
                // 嘗試執行一個簡單的查詢
                $this->statisticsQueryService->getOverview(
                    new StatisticsQueryDTO(),
                );
                $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

                $healthData['database'] = [
                    'status' => 'healthy',
                    'connection_time' => $connectionTime,
                ];

                // 如果連接時間過長，標記為警告
                if ($connectionTime > 1000) { // 1 秒
                    $healthData['database']['status'] = 'warning';
                    if ($overallStatus === 'healthy') {
                        $overallStatus = 'warning';
                    }
                }
            } catch (Throwable $e) {
                $healthData['database'] = [
                    'status' => 'critical',
                    'error' => $e->getMessage(),
                ];
                $overallStatus = 'critical';
            }

            // 檢查服務可用性
            $healthData['services'] = [
                'statistics_application_service' => 'healthy',
                'statistics_query_service' => 'healthy',
                'cache_service' => 'healthy',
            ];

            // 所有核心服務都被成功注入，所以狀態都是 healthy

            $healthData['overall_status'] = $overallStatus;
            $healthData['timestamp'] = new DateTimeImmutable()->format('c');

            $statusCode = match ($overallStatus) {
                'healthy' => 200,
                'warning' => 200,
                'critical' => 503,
            };

            return $this->json($response, [
                'success' => $overallStatus !== 'critical',
                'message' => match ($overallStatus) {
                    'healthy' => '系統狀態正常',
                    'warning' => '系統運行正常，但有一些警告',
                    'critical' => '系統狀態異常，需要立即處理',
                },
                'data' => $healthData,
            ], $statusCode);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => '健康檢查失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 檢查管理員權限.
     */
    private function checkAdminPermission(ServerRequestInterface $request): void
    {
        // 檢查用戶角色 (從 JWT token 中獲取)
        $userRole = $request->getAttribute('role');

        // 允許的管理員角色
        $adminRoles = ['super_admin', 'admin'];

        if (!in_array($userRole, $adminRoles)) {
            // 備用檢查：檢查權限陣列 (如果存在)
            $userPermissions = $request->getAttribute('permissions', []);

            if (!is_array($userPermissions)) {
                throw ValidationException::fromSingleError('permission', '權限不足，需要管理員權限');
            }

            $hasPermission = in_array('*', $userPermissions)
                            || in_array('admin.*', $userPermissions)
                            || in_array('statistics.*', $userPermissions)
                            || in_array('statistics.admin', $userPermissions);

            if (!$hasPermission) {
                throw ValidationException::fromSingleError('permission', '權限不足，需要管理員權限');
            }
        }
    }

    /**
     * 記錄管理操作.
     */
    private function logAdminAction(ServerRequestInterface $request, string $action, array $details): void
    {
        // 取得使用者資訊
        $userId = $request->getAttribute('user_id') ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent');
        $ipAddress = $this->getClientIpAddress($request);

        // 確保 userId 是字串
        $userIdString = 'unknown';
        if (is_string($userId)) {
            $userIdString = $userId;
        } elseif (is_numeric($userId)) {
            $userIdString = (string) $userId;
        }

        // 記錄管理操作（這裡簡化為 error_log，實際應該使用專門的審計日誌系統）
        error_log(sprintf(
            'ADMIN_ACTION: user_id=%s, action=%s, ip=%s, user_agent=%s, details=%s',
            $userIdString,
            $action,
            $ipAddress,
            $userAgent,
            json_encode($details),
        ));
    }

    /**
     * 取得客戶端 IP 地址.
     */
    private function getClientIpAddress(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // 檢查常見的 IP 標頭
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR',                // Standard
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];

                // 確保 IP 是字串類型
                if (!is_string($ip)) {
                    continue;
                }

                // 如果有多個 IP（以逗號分隔），取第一個
                if (str_contains($ip, ',')) {
                    $ipParts = explode(',', $ip);
                    $ip = trim($ipParts[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return isset($serverParams['REMOTE_ADDR']) && is_string($serverParams['REMOTE_ADDR'])
            ? $serverParams['REMOTE_ADDR']
            : 'unknown';
    }
}
