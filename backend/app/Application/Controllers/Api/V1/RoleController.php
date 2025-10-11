<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\Services\RoleManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 角色管理 Controller.
 */
#[OA\Tag(
    name: 'Roles',
    description: 'Role management endpoints',
)]
class RoleController
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
    ) {}

    /**
     * 取得角色列表.
     *
     * GET /api/roles
     */
    #[OA\Get(
        path: '/api/roles',
        operationId: 'listRoles',
        summary: '取得角色列表',
        description: '取得系統中所有角色的列表',
        tags: ['Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得角色列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'admin'),
                                    new OA\Property(property: 'display_name', type: 'string', example: '管理員'),
                                    new OA\Property(property: 'description', type: 'string', example: '系統管理員角色'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
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
        $roles = $this->roleManagementService->listRoles();

        $responseData = json_encode([
            'success' => true,
            'data' => array_map(fn($role) => $role->toArray(), $roles),
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得單一角色（包含權限）.
     *
     * GET /api/roles/{id}
     */
    #[OA\Get(
        path: '/api/roles/{id}',
        operationId: 'getRoleById',
        summary: '取得單一角色',
        description: '根據角色 ID 取得角色詳細資訊，包含該角色擁有的權限列表',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '角色 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得角色資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'admin'),
                                new OA\Property(property: 'display_name', type: 'string', example: '管理員'),
                                new OA\Property(property: 'description', type: 'string', example: '系統管理員角色'),
                                new OA\Property(
                                    property: 'permissions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'display_name', type: 'string'),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '角色不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function show(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $roleData = $this->roleManagementService->getRole($id);

            $responseData = json_encode([
                'success' => true,
                'data' => $roleData,
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

    /**
     * 建立角色.
     *
     * POST /api/roles
     */
    #[OA\Post(
        path: '/api/roles',
        operationId: 'createRole',
        summary: '建立角色',
        description: '建立新的角色，可同時指定該角色的權限',
        tags: ['Roles'],
        requestBody: new OA\RequestBody(
            description: '角色資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '角色名稱（唯一識別碼）', example: 'editor'),
                    new OA\Property(property: 'display_name', type: 'string', description: '顯示名稱', example: '編輯者'),
                    new OA\Property(property: 'description', type: 'string', description: '角色描述', example: '可以編輯內容的使用者', nullable: true),
                    new OA\Property(
                        property: 'permission_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: '權限 ID 陣列',
                        example: [1, 2, 3],
                    ),
                ],
                required: ['name', 'display_name'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '角色建立成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '角色建立成功'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '資料驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = json_decode((string) $request->getBody(), true) ?? [];

            $name = $data['name'] ?? '';
            $displayName = $data['display_name'] ?? '';
            $description = $data['description'] ?? null;
            $permissionIds = $data['permission_ids'] ?? [];

            $role = $this->roleManagementService->createRole($name, $displayName, $description, $permissionIds);

            $responseData = json_encode([
                'success' => true,
                'message' => '角色建立成功',
                'data' => $role->toArray(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 更新角色.
     *
     * PUT /api/roles/{id}
     */
    #[OA\Put(
        path: '/api/roles/{id}',
        operationId: 'updateRole',
        summary: '更新角色',
        description: '更新角色的顯示名稱和描述',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '角色 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '更新資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'display_name', type: 'string', description: '顯示名稱', example: '編輯者'),
                    new OA\Property(property: 'description', type: 'string', description: '角色描述', example: '可以編輯內容的使用者', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '角色更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '角色更新成功'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '角色不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function update(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $data = json_decode((string) $request->getBody(), true) ?? [];

            $displayName = $data['display_name'] ?? null;
            $description = $data['description'] ?? null;

            $role = $this->roleManagementService->updateRole($id, $displayName, $description);

            $responseData = json_encode([
                'success' => true,
                'message' => '角色更新成功',
                'data' => $role->toArray(),
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

    /**
     * 刪除角色.
     *
     * DELETE /api/roles/{id}
     */
    #[OA\Delete(
        path: '/api/roles/{id}',
        operationId: 'deleteRole',
        summary: '刪除角色',
        description: '刪除指定的角色，若該角色仍有使用者則無法刪除',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '角色 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '角色刪除成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '角色刪除成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '角色不存在'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '角色仍被使用中，無法刪除',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '角色仍被使用中，無法刪除'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
    public function destroy(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $this->roleManagementService->deleteRole($id);

            $responseData = json_encode([
                'success' => true,
                'message' => '角色刪除成功',
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
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 更新角色的權限.
     *
     * PUT /api/roles/{id}/permissions
     */
    #[OA\Put(
        path: '/api/roles/{id}/permissions',
        operationId: 'updateRolePermissions',
        summary: '更新角色的權限',
        description: '設定角色擁有的權限列表，會覆蓋原有的權限設定',
        tags: ['Roles'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '角色 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '權限 ID 列表',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'permission_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: '權限 ID 陣列',
                        example: [1, 2, 3],
                    ),
                ],
                required: ['permission_ids'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '權限更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '權限更新成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '角色不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '角色不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function updatePermissions(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $data = json_decode((string) $request->getBody(), true) ?? [];
            $permissionIds = $data['permission_ids'] ?? [];

            $this->roleManagementService->setRolePermissions($id, $permissionIds);

            $responseData = json_encode([
                'success' => true,
                'message' => '權限更新成功',
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

    /**
     * 取得所有權限.
     *
     * GET /api/permissions
     */
    #[OA\Get(
        path: '/api/permissions',
        operationId: 'listPermissions',
        summary: '取得所有權限',
        description: '取得系統中所有可用的權限列表',
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
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function permissions(Request $request, Response $response): Response
    {
        $permissions = $this->roleManagementService->listPermissions();

        $responseData = json_encode([
            'success' => true,
            'data' => array_map(fn($p) => $p->toArray(), $permissions),
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得所有權限（按資源分組）.
     *
     * GET /api/permissions/grouped
     */
    #[OA\Get(
        path: '/api/permissions/grouped',
        operationId: 'listPermissionsGrouped',
        summary: '取得所有權限（按資源分組）',
        description: '取得系統中所有權限，並依照資源類型分組顯示',
        tags: ['Permissions'],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得分組權限列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'display_name', type: 'string'),
                                        new OA\Property(property: 'resource', type: 'string'),
                                        new OA\Property(property: 'action', type: 'string'),
                                    ],
                                ),
                            ),
                            example: [
                                'posts' => [
                                    ['id' => 1, 'name' => 'posts.create', 'display_name' => '建立文章'],
                                    ['id' => 2, 'name' => 'posts.update', 'display_name' => '更新文章'],
                                ],
                                'users' => [
                                    ['id' => 10, 'name' => 'users.create', 'display_name' => '建立使用者'],
                                ],
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function permissionsGrouped(Request $request, Response $response): Response
    {
        $grouped = $this->roleManagementService->listPermissionsGroupedByResource();

        $data = [];
        foreach ($grouped as $resource => $permissions) {
            $data[$resource] = array_map(fn($p) => $p->toArray(), $permissions);
        }

        $responseData = json_encode([
            'success' => true,
            'data' => $data,
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
