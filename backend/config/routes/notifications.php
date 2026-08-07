<?php

declare(strict_types=1);

use App\Application\Controllers\Api\V1\NotificationController;

return [
    'notifications.index' => [
        'methods'    => ['GET'],
        'path'       => '/api/notifications',
        'handler'    => [NotificationController::class, 'index'],
        'name'       => 'notifications.index',
        'middleware' => ['auth'],
    ],

    'notifications.unread_count' => [
        'methods'    => ['GET'],
        'path'       => '/api/notifications/unread-count',
        'handler'    => [NotificationController::class, 'unreadCount'],
        'name'       => 'notifications.unread_count',
        'middleware' => ['auth'],
    ],

    'notifications.mark_as_read' => [
        'methods'    => ['PATCH'],
        'path'       => '/api/notifications/{id}/read',
        'handler'    => [NotificationController::class, 'markAsRead'],
        'name'       => 'notifications.mark_as_read',
        'middleware' => ['auth'],
    ],

    'notifications.mark_all_as_read' => [
        'methods'    => ['PATCH'],
        'path'       => '/api/notifications/read-all',
        'handler'    => [NotificationController::class, 'markAllAsRead'],
        'name'       => 'notifications.mark_all_as_read',
        'middleware' => ['auth'],
    ],

    'notifications.broadcast' => [
        'methods'    => ['POST'],
        'path'       => '/api/notifications/broadcast',
        'handler'    => [NotificationController::class, 'broadcast'],
        'name'       => 'notifications.broadcast',
        'middleware' => ['auth', 'admin'],
    ],
];
