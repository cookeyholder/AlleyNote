<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use App\Domains\Auth\Services\UserManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 使用者管理 Controller.
 */
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
