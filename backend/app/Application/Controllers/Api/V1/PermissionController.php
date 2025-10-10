<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Auth\Services\PermissionManagementService;
use App\Shared\Exceptions\NotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 權限管理 Controller.
 */
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
