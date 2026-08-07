<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Notification\Contracts\NotificationServiceInterface;
use App\Domains\Notification\DTOs\CreateNotificationDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    #[OA\Get(
        path: '/api/notifications',
        operationId: 'getNotifications',
        summary: '取得使用者的站內通知列表',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'unread_only', in: 'query', schema: new OA\Schema(type: 'boolean', default: false)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功取得通知列表'),
            new OA\Response(response: 401, description: '未認證'),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;

            $params = $request->getQueryParams();
            $unreadOnly = !empty($params['unread_only']) && filter_var($params['unread_only'], FILTER_VALIDATE_BOOLEAN);
            $limitParam = $params['limit'] ?? 20;
            $offsetParam = $params['offset'] ?? 0;
            $limit = is_numeric($limitParam) ? max(1, min(100, (int) $limitParam)) : 20;
            $offset = is_numeric($offsetParam) ? max(0, (int) $offsetParam) : 0;

            $result = $this->notificationService->getUserNotifications($userId, $unreadOnly, $limit, $offset);

            return $this->json($response, [
                'success' => true,
                'message' => '取得通知列表成功',
                'data'    => $result['notifications'],
                'meta'    => [
                    'unread_count' => $result['unread_count'],
                    'limit'        => $result['limit'],
                    'offset'       => $result['offset'],
                ],
            ]);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/notifications/unread-count',
        operationId: 'getUnreadNotificationCount',
        summary: '取得未讀通知數量',
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: '成功取得未讀通知數量'),
        ],
    )]
    public function unreadCount(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;
            $count = $this->notificationService->getUnreadCount($userId);

            return $this->json($response, [
                'success' => true,
                'data'    => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/notifications/{id}/read',
        operationId: 'markNotificationAsRead',
        summary: '標記單筆通知為已讀',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功標記為已讀'),
            new OA\Response(response: 400, description: '標記失敗'),
        ],
    )]
    public function markAsRead(Request $request, Response $response): Response
    {
        try {
            $idAttr = $request->getAttribute('id');
            $id = is_numeric($idAttr) ? (int) $idAttr : 0;
            $user = $request->getAttribute('user');
            $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;

            $this->notificationService->markAsRead($id, $userId);

            return $this->json($response, [
                'success' => true,
                'message' => '標記已讀成功',
            ]);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Patch(
        path: '/api/notifications/read-all',
        operationId: 'markAllNotificationsAsRead',
        summary: '標記所有通知為已讀',
        tags: ['Notifications'],
        responses: [
            new OA\Response(response: 200, description: '成功將所有通知標記為已讀'),
        ],
    )]
    public function markAllAsRead(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;
            $count = $this->notificationService->markAllAsRead($userId);

            return $this->json($response, [
                'success' => true,
                'message' => '所有通知已標記為已讀',
                'data'    => [
                    'updated_count' => $count,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/notifications/broadcast',
        operationId: 'broadcastNotification',
        summary: '廣播發送系統通知 (管理員專用)',
        tags: ['Notifications'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', default: 'system'),
                    new OA\Property(property: 'user_id', type: 'integer', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: '廣播通知成功'),
            new OA\Response(response: 400, description: '請求資料錯誤'),
        ],
    )]
    public function broadcast(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->json($response, [
                    'success' => false,
                    'message' => '無效的請求資料',
                ], 400);
            }

            $dto = CreateNotificationDTO::fromArray($data);
            $notification = $this->notificationService->broadcastNotification($dto);

            return $this->json($response, [
                'success' => true,
                'message' => '廣播通知發送成功',
                'data'    => $notification->toArray(),
            ], 201);
        } catch (Throwable $e) {
            return $this->json($response, [
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
