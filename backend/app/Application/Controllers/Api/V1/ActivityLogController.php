<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use Exception;
use ValueError;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

#[OA\Tag(
    name: 'Activity Log',
    description: 'Activity logging and retrieval endpoints',
)]
class ActivityLogController extends BaseController
{
    public function __construct(
        private readonly ActivityLoggingServiceInterface $loggingService,
        private readonly ActivityLogRepositoryInterface $repository,
    ) {}

    #[OA\Post(
        path: '/api/v1/activity-logs',
        operationId: 'storeActivityLog',
        summary: 'Log a new activity',
        tags: ['Activity Log'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'action_type', type: 'string'),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'metadata', type: 'object'),
                ],
                example: [
                    'action_type' => 'USER_LOGIN',
                    'user_id' => 1,
                    'metadata' => ['ip' => '127.0.0.1'],
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Activity logged successfully'),
            new OA\Response(response: 400, description: 'Invalid request'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                $errorResponse = json_encode([
                    'success' => false,
                    'message' => 'Invalid request data',
                    'error_code' => 400,
                ]);
                $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $actionTypeValue = $data['action_type'] ?? null;
            if (!is_string($actionTypeValue) && !is_int($actionTypeValue)) {
                return $this->validationError($response, 'action_type must be a valid string or integer value.');
            }

            $userIdValue = $data['user_id'] ?? null;
            if (!is_int($userIdValue) && !(is_string($userIdValue) && ctype_digit($userIdValue))) {
                return $this->validationError($response, 'user_id must be a numeric value.');
            }

            $metadataValue = $data['metadata'] ?? null;
            if ($metadataValue !== null && !is_array($metadataValue)) {
                return $this->validationError($response, 'metadata must be an object or null.');
            }

            if (is_array($metadataValue)) {
                foreach (array_keys($metadataValue) as $key) {
                    if (!is_string($key)) {
                        return $this->validationError($response, 'metadata keys must be strings.');
                    }
                }
                /** @var array<string, mixed> $metadataValue */
            }

            try {
                $dto = new CreateActivityLogDTO(
                    actionType: ActivityType::from($actionTypeValue),
                    userId: (int) $userIdValue,
                    metadata: $metadataValue,
                );
            } catch (ValueError $exception) {
                return $this->validationError($response, $exception->getMessage());
            }

            $result = $this->loggingService->log($dto);

            $successResponse = json_encode([
                'success' => true,
                'data' => $result,
                'message' => 'Activity logged successfully',
            ]);
            $response->getBody()->write($successResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error_code' => 500,
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function validationError(Response $response, string $message): Response
    {
        $errorResponse = json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => 422,
        ]);
        $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(422);
    }

    #[OA\Get(
        path: '/api/v1/activity-logs',
        operationId: 'getActivityLogs',
        summary: 'Get activity logs',
        tags: ['Activity Log'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity logs retrieved successfully'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limitParam = $params['limit'] ?? 20;
            $offsetParam = $params['offset'] ?? 0;

            $limit = is_numeric($limitParam) ? (int) $limitParam : 20;
            $offset = is_numeric($offsetParam) ? (int) $offsetParam : 0;

            $logs = $this->repository->findAll($limit, $offset);

            $successResponse = json_encode([
                'success' => true,
                'data' => $logs,
                'message' => 'Activity logs retrieved successfully',
            ]);
            $response->getBody()->write($successResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error_code' => 500,
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs/stats',
        operationId: 'getActivityLogStats',
        summary: 'Get activity log statistics',
        tags: ['Activity Log'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity log statistics retrieved successfully'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ],
    )]
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $startDate = isset($params['start_date']) && is_string($params['start_date'])
                ? new DateTimeImmutable($params['start_date'])
                : new DateTimeImmutable('-30 days');
            $endDate = isset($params['end_date']) && is_string($params['end_date'])
                ? new DateTimeImmutable($params['end_date'])
                : new DateTimeImmutable();

            $stats = $this->repository->getActivityStatistics($startDate, $endDate);

            $successResponse = json_encode([
                'success' => true,
                'data' => $stats,
                'message' => 'Activity log statistics retrieved successfully',
            ]);
            $response->getBody()->write($successResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error_code' => 500,
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs/me',
        operationId: 'getCurrentUserActivityLogs',
        summary: 'Get current user activity logs',
        tags: ['Activity Log'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Current user activity logs retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ],
    )]
    public function getCurrentUserLogs(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            if (!is_numeric($userId)) {
                $errorResponse = json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'error_code' => 401,
                ]);
                $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $params = $request->getQueryParams();
            $limitParam = $params['limit'] ?? 20;
            $offsetParam = $params['offset'] ?? 0;

            $limit = is_numeric($limitParam) ? (int) $limitParam : 20;
            $offset = is_numeric($offsetParam) ? (int) $offsetParam : 0;

            $logs = $this->repository->findByUser((int) $userId, $limit, $offset);

            $successResponse = json_encode([
                'success' => true,
                'data' => $logs,
                'message' => 'Current user activity logs retrieved successfully',
            ]);
            $response->getBody()->write($successResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error_code' => 500,
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Get(
        path: '/api/v1/activity-logs/login-failures',
        operationId: 'getLoginFailureStats',
        summary: 'Get login failure statistics',
        tags: ['Activity Log'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Login failure statistics retrieved successfully'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ],
    )]
    public function getLoginFailureStats(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $startDate = isset($params['start_date']) && is_string($params['start_date'])
                ? new DateTimeImmutable($params['start_date'])
                : new DateTimeImmutable('-30 days');
            $endDate = isset($params['end_date']) && is_string($params['end_date'])
                ? new DateTimeImmutable($params['end_date'])
                : new DateTimeImmutable();
            $limitParam = $params['limit'] ?? 10;
            $limit = is_numeric($limitParam) ? (int) $limitParam : 10;

            // 使用 repository 的方法取得登入失敗統計
            $stats = $this->repository->getLoginFailureStatistics($startDate, $endDate, $limit);

            $successResponse = json_encode([
                'success' => true,
                'data' => $stats,
                'message' => 'Login failure statistics retrieved successfully',
            ]);
            $response->getBody()->write($successResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'error_code' => 500,
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
