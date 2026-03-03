<?php

declare(strict_types=1);

/**
 * 管理員路由配置
 * 
 * 這個檔案包含所有管理員功能相關的路由定義
 */

use App\Application\Controllers\Api\V1\UserController;
use App\Application\Controllers\Api\V1\PostController;

return [
    // 管理員儀表板
    'admin.dashboard' => [
        'methods' => ['GET'],
        'path' => '/api/admin/dashboard',
        'handler' => function () {
            // TODO: 實作管理員儀表板
            return [
                'message' => '管理員儀表板',
                'stats' => [
                    'total_posts' => 0,
                    'total_users' => 0,
                    'active_sessions' => 0
                ],
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.dashboard',
        'middleware' => ['auth', 'admin']
    ],

    // 使用者管理
    'admin.users.index' => [
        'methods' => ['GET'],
        'path' => '/api/admin/users',
        'handler' => [UserController::class, 'index'],
        'name' => 'admin.users.index',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.show' => [
        'methods' => ['GET'],
        'path' => '/api/admin/users/{id}',
        'handler' => [UserController::class, 'show'],
        'name' => 'admin.users.show',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.store' => [
        'methods' => ['POST'],
        'path' => '/api/admin/users',
        'handler' => [UserController::class, 'store'],
        'name' => 'admin.users.store',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.update' => [
        'methods' => ['PUT', 'PATCH'],
        'path' => '/api/admin/users/{id}',
        'handler' => [UserController::class, 'update'],
        'name' => 'admin.users.update',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.destroy' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/users/{id}',
        'handler' => [UserController::class, 'destroy'],
        'name' => 'admin.users.destroy',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.activate' => [
        'methods' => ['POST'],
        'path' => '/api/admin/users/{id}/activate',
        'handler' => [UserController::class, 'activate'],
        'name' => 'admin.users.activate',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.deactivate' => [
        'methods' => ['POST'],
        'path' => '/api/admin/users/{id}/deactivate',
        'handler' => [UserController::class, 'deactivate'],
        'name' => 'admin.users.deactivate',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.reset-password' => [
        'methods' => ['POST'],
        'path' => '/api/admin/users/{id}/reset-password',
        'handler' => [UserController::class, 'resetPassword'],
        'name' => 'admin.users.reset-password',
        'middleware' => ['auth', 'admin']
    ],

    // 文章發布管理
    'admin.posts.publish' => [
        'methods' => ['POST'],
        'path' => '/api/posts/{id}/publish',
        'handler' => [PostController::class, 'publish'],
        'name' => 'admin.posts.publish',
        'middleware' => ['auth', 'admin']
    ],

    'admin.posts.unpublish' => [
        'methods' => ['POST'],
        'path' => '/api/posts/{id}/unpublish',
        'handler' => [PostController::class, 'unpublish'],
        'name' => 'admin.posts.unpublish',
        'middleware' => ['auth', 'admin']
    ],

    'admin.posts.unpin' => [
        'methods' => ['DELETE'],
        'path' => '/api/posts/{id}/pin',
        'handler' => [PostController::class, 'unpin'],
        'name' => 'admin.posts.unpin',
        'middleware' => ['auth', 'admin']
    ],

    // 系統資訊
    'admin.info.system' => [
        'methods' => ['GET'],
        'path' => '/api/admin/info/system',
        'handler' => function () {
            return [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'server_software' => ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
                'system_time' => date('c'),
                'uptime' => 'N/A'
            ];
        },
        'name' => 'admin.info.system',
        'middleware' => ['auth', 'admin']
    ],

    // 日誌管理
    'admin.logs.index' => [
        'methods' => ['GET'],
        'path' => '/api/admin/logs',
        'handler' => function () {
            // TODO: 實作日誌清單
            return [
                'message' => '日誌管理功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.logs.index',
        'middleware' => ['auth', 'admin']
    ]
];
