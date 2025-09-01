<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use Exception;
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

            $dto = new CreateActivityLogDTO(
                actionType: ActivityType::from($data['action_type'] ?? ''),
                userId: (int) ($data['user_id'] ?? 0),
                metadata: $data['metadata'] ?? [],
            );

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
            $limit = (int) ($params['limit'] ?? 20);
            $offset = (int) ($params['offset'] ?? 0);

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
}
