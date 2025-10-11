<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\Services\PermissionManagementService;
use App\Shared\Exceptions\NotFoundException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 權限管理 Controller.
 */
#[OA\Tag(
    name: 'Permissions',
    description: 'Permission management endpoints',
)]
class PermissionController
{
    public function __construct(
        private readonly PermissionManagementService $permissionManagementService,
    ) {}

    /**
     * 取得權限列表.
     *
     * GET /api/permissions
     */
    #[OA\Get(
        path: '/api/permissions',
        operationId: 'getPermissions',
        summary: '取得權限列表',
        description: '取得系統中所有可用的權限',
        tags: ['Permissions'],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得權限列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'posts.create'),
                                    new OA\Property(property: 'display_name', type: 'string', example: '建立文章'),
                                    new OA\Property(property: 'resource', type: 'string', example: 'posts'),
                                    new OA\Property(property: 'action', type: 'string', example: 'create'),
                                    new OA\Property(property: 'description', type: 'string', example: '允許建立新文章'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        $permissions = $this->permissionManagementService->listPermissions();

        $responseData = json_encode([
            'success' => true,
            'data' => array_map(fn($permission) => $permission->toArray(), $permissions),
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得單一權限.
     *
     * GET /api/permissions/{id}
     */
    #[OA\Get(
        path: '/api/permissions/{id}',
        operationId: 'getPermissionById',
        summary: '取得單一權限',
        description: '根據權限 ID 取得權限詳細資訊',
        tags: ['Permissions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '權限 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得權限資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'posts.create'),
                                new OA\Property(property: 'display_name', type: 'string', example: '建立文章'),
                                new OA\Property(property: 'resource', type: 'string', example: 'posts'),
                                new OA\Property(property: 'action', type: 'string', example: 'create'),
                                new OA\Property(property: 'description', type: 'string', example: '允許建立新文章'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '權限不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '權限不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function show(Request $request, Response $response): Response
    {
        try {
            $idAttr = $request->getAttribute('id');
            if (!is_numeric($idAttr)) {
                throw new InvalidArgumentException('Invalid permission ID');
            }
            $id = (int) $idAttr;
            $permission = $this->permissionManagementService->getPermission($id);

            $responseData = json_encode([
                'success' => true,
                'data' => $permission->toArray(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
}
