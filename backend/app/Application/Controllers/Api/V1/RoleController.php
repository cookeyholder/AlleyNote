<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\Services\RoleManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 角色管理 Controller
 */
class RoleController
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
    ) {
    }

    /**
     * 取得角色列表
     * 
     * GET /api/roles
     */
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
     * 取得單一角色（包含權限）
     * 
     * GET /api/roles/{id}
     */
    public function show(Request $request, Response $response): Response
    {
        try {
            $id = (int) $args['id'];
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
     * 建立角色
     * 
     * POST /api/roles
     */
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
     * 更新角色
     * 
     * PUT /api/roles/{id}
     */
    public function update(Request $request, Response $response): Response
    {
        try {
            $id = (int) $args['id'];
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
     * 刪除角色
     * 
     * DELETE /api/roles/{id}
     */
    public function destroy(Request $request, Response $response): Response
    {
        try {
            $id = (int) $args['id'];
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
     * 更新角色的權限
     * 
     * PUT /api/roles/{id}/permissions
     */
    public function updatePermissions(Request $request, Response $response): Response
    {
        try {
            $id = (int) $args['id'];
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
     * 取得所有權限
     * 
     * GET /api/permissions
     */
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
     * 取得所有權限（按資源分組）
     * 
     * GET /api/permissions/grouped
     */
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
