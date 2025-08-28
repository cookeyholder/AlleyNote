<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Http\ApiResponse;
use DateTime;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ValueError;

/**
 * ActivityLogController.
 *
 * 處理系統活動記錄的 API 請求
 */
class ActivityLogController extends BaseController
{
    public function __construct(
        private readonly ActivityLoggingServiceInterface $loggingService,
        private readonly ActivityLogRepositoryInterface $repository,
    ) {
        // No parent constructor to call
    }

    #[OA\Post(
        path: '/api/v1/activity-logs',
        summary: 'Create new activity log',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['action_type', 'user_id'],
                properties: [
                    new OA\Property(property: 'action_type', type: 'string', example: 'auth.login.success'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'resource_type', type: 'string', nullable: true, example: 'post'),
                    new OA\Property(property: 'resource_id', type: 'integer', nullable: true, example: 123),
                    new OA\Property(property: 'ip_address', type: 'string', nullable: true, example: '192.168.1.1'),
                    new OA\Property(property: 'user_agent', type: 'string', nullable: true, example: 'Mozilla/5.0'),
                    new OA\Property(property: 'additional_data', type: 'object', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Activity log created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Activity log created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
        tags: ['Activity Logs'],
    )]
    public function store(ServerRequestInterface $request, ResponseInterface $response): string
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->errorResponse('Invalid request body', 400);
            }

            // 簡單驗證：檢查必填欄位
            if (!isset($data['action_type']) || !isset($data['user_id'])) {
                return $this->errorResponse('Missing required fields: action_type and user_id', 422);
            }

            // 驗證枚舉值
            try {
                ActivityType::from($data['action_type']);
            } catch (ValueError $e) {
                return $this->errorResponse('Validation failed', 422, [
                    'action_type' => ['Invalid action type'],
                ]);
            }

            // 建立 DTO
            $dto = new CreateActivityLogDTO(
                actionType: ActivityType::from($data['action_type']),
                userId: $data['user_id'],
                targetType: $data['resource_type'] ?? null,
                targetId: $data['resource_id'] ?? null,
                ipAddress: $data['ip_address'] ?? null,
                userAgent: $data['user_agent'] ?? null,
                metadata: $data['additional_data'] ?? null,
            );

            // 建立活動記錄
            $result = $this->loggingService->log($dto);

            return $this->jsonResponse(
                ApiResponse::success($result, 'Activity logged successfully'),
                201,
            );
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/activity-logs/batch',
        summary: 'Create multiple activity logs',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['logs'],
                properties: [
                    new OA\Property(
                        property: 'logs',
                        type: 'array',
                        items: new OA\Items(
                            required: ['action_type', 'user_id'],
                            properties: [
                                new OA\Property(property: 'action_type', type: 'string'),
                                new OA\Property(property: 'user_id', type: 'integer'),
                                new OA\Property(property: 'resource_type', type: 'string', nullable: true),
                                new OA\Property(property: 'resource_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'ip_address', type: 'string', nullable: true),
                                new OA\Property(property: 'user_agent', type: 'string', nullable: true),
                                new OA\Property(property: 'additional_data', type: 'object', nullable: true),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Activity logs created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Activity logs created successfully'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'created_count', type: 'integer', example: 5),
                        ]),
                    ],
                ),
            ),
        ],
        tags: ['Activity Logs'],
    )]
    public function storeBatch(ServerRequestInterface $request, ResponseInterface $response): string
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->errorResponse('Invalid request body', 400);
            }

            if (!isset($data['logs']) || !is_array($data['logs'])) {
                return $this->errorResponse('Logs array is required', 400);
            }

            // 驗證每個記錄
            $dtos = [];
            foreach ($data['logs'] as $index => $logData) {
                // 簡單驗證：檢查必填欄位
                if (!isset($logData['action_type']) || !isset($logData['user_id'])) {
                    return $this->errorResponse("Missing required fields for log #{$index}: action_type and user_id", 422);
                }

                $dtos[] = new CreateActivityLogDTO(
                    actionType: ActivityType::from($logData['action_type']),
                    userId: $logData['user_id'],
                    targetType: $logData['resource_type'] ?? null,
                    targetId: $logData['resource_id'] ?? null,
                    ipAddress: $logData['ip_address'] ?? null,
                    userAgent: $logData['user_agent'] ?? null,
                    metadata: $logData['additional_data'] ?? null,
                );
            }

            // 批次建立活動記錄
            $count = $this->loggingService->logBatch($dtos);

            return $this->successResponse(
                ['logged_count' => $count],
                "{$count} activities logged successfully",
            );
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs',
        summary: 'Get activity logs with pagination and filters',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'category', in: 'query', description: 'Activity category', schema: new OA\Schema(type: 'string', enum: ['authentication', 'content', 'admin', 'system', 'security'])),
            new OA\Parameter(name: 'type', in: 'query', description: 'Activity type', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', description: 'User ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_time', in: 'query', description: 'Start time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end_time', in: 'query', description: 'End time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Activity logs retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                        new OA\Property(
                            property: 'pagination',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'limit', type: 'integer', example: 20),
                                new OA\Property(property: 'pages', type: 'integer', example: 5),
                            ],
                        ),
                    ],
                ),
            ),
        ],
        tags: ['Activity Logs'],
    )]
    public function index(ServerRequestInterface $request, ResponseInterface $response): string
    {
        try {
            $params = $request->getQueryParams();

            $page = (int) ($params['page'] ?? 1);
            $limit = (int) ($params['limit'] ?? 20);
            $offset = ($page - 1) * $limit;

            // 準備搜尋參數
            $category = isset($params['category']) ? ActivityCategory::from($params['category']) : null;
            $actionType = isset($params['type']) ? ActivityType::from($params['type']) : null;
            $userId = isset($params['user_id']) ? (int) $params['user_id'] : null;
            $startTime = isset($params['start_time']) ? new DateTime($params['start_time']) : null;
            $endTime = isset($params['end_time']) ? new DateTime($params['end_time']) : null;

            $logs = $this->repository->search(
                null, // searchTerm
                $userId,
                $category,
                $actionType,
                $startTime,
                $endTime,
                $limit,
                $offset,
            );
            $total = $this->repository->getSearchCount(
                null, // searchTerm
                $userId,
                $category,
                $actionType,
                $startTime,
                $endTime,
            );

            $pagination = [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
            ];

            return $this->paginatedResponse($logs, $total, $page, $limit);
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    #[OA\Get(
        path: '/api/v1/activity-logs/users/{id}',
        summary: 'Get activity logs for specific user',
        tags: ['Activity Logs'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', description: 'Offset', schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'category', in: 'query', description: 'Activity category', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'action_type', in: 'query', description: 'Activity type', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User activity logs retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items()),
                    ],
                ),
            ),
        ],
    )]
    public function getUserActivities(ServerRequestInterface $request, ResponseInterface $response, array $args): string
    {
        try {
            $userId = (int) $args['id'];
            $params = $request->getQueryParams();

            $limit = (int) ($params['limit'] ?? 20);
            $offset = (int) ($params['offset'] ?? 0);
            $category = isset($params['category']) ? ActivityCategory::from($params['category']) : null;
            $actionType = isset($params['action_type']) ? ActivityType::from($params['action_type']) : null;

            $logs = $this->repository->findByUser($userId, $limit, $offset, $category, $actionType);

            return $this->successResponse($logs, 'User activity logs retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs/search',
        summary: 'Search activity logs',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'offset', in: 'query', description: 'Offset', schema: new OA\Schema(type: 'integer', default: 0)),
            new OA\Parameter(name: 'start_time', in: 'query', description: 'Start time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end_time', in: 'query', description: 'End time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'logs', type: 'array', items: new OA\Items()),
                            new OA\Property(property: 'total', type: 'integer', example: 42),
                        ]),
                    ],
                ),
            ),
        ],
        tags: ['Activity Logs'],
    )]
    public function search(ServerRequestInterface $request, ResponseInterface $response): string
    {
        try {
            $params = $request->getQueryParams();
            $query = $params['q'] ?? '';

            if (empty($query)) {
                return $this->errorResponse('Search query is required', 400);
            }

            $limit = (int) ($params['limit'] ?? 50);
            $offset = (int) ($params['offset'] ?? 0);

            // 準備搜尋參數
            $startTime = isset($params['start_time']) ? new DateTime($params['start_time']) : null;
            $endTime = isset($params['end_time']) ? new DateTime($params['end_time']) : null;

            $logs = $this->repository->search(
                $query, // searchTerm
                null, // userId
                null, // category
                null, // actionType
                $startTime,
                $endTime,
                $limit,
                $offset,
            );
            $total = $this->repository->getSearchCount(
                $query, // searchTerm
                null, // userId
                null, // category
                null, // actionType
                $startTime,
                $endTime,
            );

            $data = [
                'logs' => $logs,
                'total' => $total,
            ];

            return $this->successResponse($data, 'Search results retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs/statistics',
        summary: 'Get activity statistics',
        parameters: [
            new OA\Parameter(name: 'start_time', in: 'query', description: 'Start time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end_time', in: 'query', description: 'End time (ISO 8601)', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Activity statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'total_activities', type: 'integer', example: 1250),
                            new OA\Property(property: 'by_category', type: 'object'),
                            new OA\Property(property: 'by_type', type: 'object'),
                            new OA\Property(property: 'popular_types', type: 'array', items: new OA\Items()),
                        ]),
                    ],
                ),
            ),
        ],
        tags: ['Activity Logs'],
    )]
    public function statistics(ServerRequestInterface $request, ResponseInterface $response): string
    {
        try {
            $params = $request->getQueryParams();

            $startTime = isset($params['start_time'])
                ? new DateTime($params['start_time'])
                : new DateTime('-30 days');
            $endTime = isset($params['end_time'])
                ? new DateTime($params['end_time'])
                : new DateTime();

            $statistics = $this->repository->getActivityStatistics($startTime, $endTime);

            $data = [
                'statistics' => $statistics,
            ];

            return $this->successResponse($data, 'Activity statistics retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500);
        }
    }
}
