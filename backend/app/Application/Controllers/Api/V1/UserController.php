<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use App\Domains\Auth\Services\UserManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 使用者管理 Controller.
 */
#[OA\Tag(
    name: 'Users',
    description: 'User management endpoints',
)]
class UserController
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    /**
     * 取得使用者列表.
     *
     * GET /api/users
     */
    #[OA\Get(
        path: '/api/users',
        operationId: 'listUsers',
        summary: '取得使用者列表',
        description: '取得系統中所有使用者的分頁列表，支援搜尋功能',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: '頁碼',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: '每頁筆數',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10),
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: '搜尋關鍵字（用於搜尋使用者名稱或電子郵件）',
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得使用者列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                            ),
                        ),
                        new OA\Property(
                            property: 'pagination',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 10)));
        $search = $params['search'] ?? '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $result = $this->userManagementService->listUsers($page, $perPage, $filters);

        $responseData = json_encode([
            'success' => true,
            'data' => $result['items'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'last_page' => $result['last_page'],
            ],
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得單一使用者.
     *
     * GET /api/users/{id}
     */
    #[OA\Get(
        path: '/api/users/{id}',
        operationId: 'getUserById',
        summary: '取得單一使用者',
        description: '根據使用者 ID 取得使用者詳細資訊',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得使用者資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'status', type: 'string', example: 'active'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function show(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $user = $this->userManagementService->getUser($id);

            $responseData = json_encode([
                'success' => true,
                'data' => $user,
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
     * 建立使用者.
     *
     * POST /api/users
     */
    #[OA\Post(
        path: '/api/users',
        operationId: 'createUser',
        summary: '建立使用者',
        description: '建立新的使用者帳號',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            description: '使用者資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string', description: '使用者名稱', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: '電子郵件', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', description: '密碼', example: 'password123'),
                ],
                required: ['username', 'email', 'password'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '使用者建立成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '使用者建立成功'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                            ],
                        ),
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

            $dto = CreateUserDTO::fromArray($data);
            $user = $this->userManagementService->createUser($dto);

            $responseData = json_encode([
                'success' => true,
                'message' => '使用者建立成功',
                'data' => $user,
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
     * 更新使用者.
     *
     * PUT /api/users/{id}
     */
    #[OA\Put(
        path: '/api/users/{id}',
        operationId: 'updateUser',
        summary: '更新使用者',
        description: '更新使用者資訊',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '更新資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string', description: '使用者名稱', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', description: '電子郵件', example: 'john@example.com'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '使用者更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '使用者更新成功'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
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
    public function update(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $data = json_decode((string) $request->getBody(), true) ?? [];

            $dto = UpdateUserDTO::fromArray($data);
            $user = $this->userManagementService->updateUser($id, $dto);

            $responseData = json_encode([
                'success' => true,
                'message' => '使用者更新成功',
                'data' => $user,
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
     * 刪除使用者.
     *
     * DELETE /api/users/{id}
     */
    #[OA\Delete(
        path: '/api/users/{id}',
        operationId: 'deleteUser',
        summary: '刪除使用者',
        description: '刪除指定的使用者',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '使用者刪除成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '使用者刪除成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function destroy(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $this->userManagementService->deleteUser($id);

            $responseData = json_encode([
                'success' => true,
                'message' => '使用者刪除成功',
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
     * 分配角色給使用者.
     *
     * PUT /api/users/{id}/roles
     */
    #[OA\Put(
        path: '/api/users/{id}/roles',
        operationId: 'assignRolesToUser',
        summary: '分配角色給使用者',
        description: '為指定使用者分配一或多個角色',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '角色 ID 列表',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'role_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: '角色 ID 陣列',
                        example: [1, 2, 3],
                    ),
                ],
                required: ['role_ids'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '角色分配成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '角色分配成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function assignRoles(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $data = json_decode((string) $request->getBody(), true) ?? [];
            $roleIds = $data['role_ids'] ?? [];

            $this->userManagementService->assignRoles($id, $roleIds);

            $responseData = json_encode([
                'success' => true,
                'message' => '角色分配成功',
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
     * 啟用使用者.
     *
     * POST /api/admin/users/{id}/activate
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/activate',
        operationId: 'activateUser',
        summary: '啟用使用者',
        description: '啟用已停用的使用者帳號',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '使用者已啟用',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '使用者已啟用'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function activate(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $user = $this->userManagementService->activateUser($id);

            $responseData = json_encode([
                'success' => true,
                'message' => '使用者已啟用',
                'data' => $user,
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
     * 停用使用者.
     *
     * POST /api/admin/users/{id}/deactivate
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/deactivate',
        operationId: 'deactivateUser',
        summary: '停用使用者',
        description: '停用使用者帳號，使其無法登入系統',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '使用者已停用',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '使用者已停用'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
                    ],
                ),
            ),
        ],
    )]
    public function deactivate(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $user = $this->userManagementService->deactivateUser($id);

            $responseData = json_encode([
                'success' => true,
                'message' => '使用者已停用',
                'data' => $user,
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
     * 重設使用者密碼（管理員）.
     *
     * POST /api/admin/users/{id}/reset-password
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/reset-password',
        operationId: 'resetUserPassword',
        summary: '重設使用者密碼',
        description: '管理員重設使用者的密碼',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '使用者 ID',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '新密碼',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        description: '新密碼',
                        minLength: 8,
                        example: 'newpassword123',
                    ),
                ],
                required: ['password'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '密碼重設成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '密碼重設成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '使用者不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '使用者不存在'),
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
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getAttribute('id');
            $data = json_decode((string) $request->getBody(), true) ?? [];

            if (empty($data['password'])) {
                throw ValidationException::fromSingleError('password', '密碼欄位為必填');
            }

            $this->userManagementService->resetPassword($id, $data['password']);

            $responseData = json_encode([
                'success' => true,
                'message' => '密碼重設成功',
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
}
