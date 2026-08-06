<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Statistics\Contracts\SystemMonitoringServiceInterface;
use App\Shared\Exceptions\ValidationException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class SystemMonitoringController extends BaseController
{
    public function __construct(
        private readonly SystemMonitoringServiceInterface $systemMonitoringService,
    ) {}

    /**
     * 取得主機與系統監控狀態數據.
     *
     * GET /api/admin/statistics/system
     */
    #[OA\Get(
        path: '/api/admin/statistics/system',
        summary: '取得主機與系統監控狀態',
        description: '取得主機 CPU、記憶體、磁碟、PHP 運作環境、SQLite 資料庫與 Redis 健康狀態數據',
        operationId: 'getSystemStatus',
        tags: ['statistics-admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得系統與主機健康狀態',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: '未授權訪問'),
            new OA\Response(response: 403, description: '權限不足'),
            new OA\Response(response: 500, description: '伺服器內部錯誤'),
        ],
    )]
    public function getSystemStatus(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $this->checkAdminPermission($request);
            $healthStatus = $this->systemMonitoringService->getSystemHealthStatus();

            return $this->json($response, [
                'success' => true,
                'data'    => $healthStatus,
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error'   => [
                    'type'    => 'permission_error',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'error'   => [
                    'type'    => 'internal_error',
                    'message' => '取得系統與主機監控狀態失敗: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * 檢查管理員權限.
     *
     * @throws ValidationException
     */
    private function checkAdminPermission(ServerRequestInterface $request): void
    {
        $userRole = $request->getAttribute('role', '');
        if ($userRole === 'super_admin' || $userRole === 'admin') {
            return;
        }

        $userPermissions = $request->getAttribute('permissions', []);
        if (is_array($userPermissions)) {
            $hasPermission = in_array('*', $userPermissions, true)
                || in_array('admin.*', $userPermissions, true)
                || in_array('statistics.*', $userPermissions, true)
                || in_array('statistics.admin', $userPermissions, true);
            if ($hasPermission) {
                return;
            }
        }

        throw ValidationException::fromSingleError('permission', '沒有管理員權限');
    }
}
