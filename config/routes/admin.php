<?php

declare(strict_types=1);

/**
 * 管理員路由配置
 * 
 * 這個檔案包含所有管理員功能相關的路由定義
 */

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
        'middleware' => ['auth', 'admin'] // 需要認證和管理員權限
    ],

    // 使用者管理
    'admin.users.index' => [
        'methods' => ['GET'],
        'path' => '/api/admin/users',
        'handler' => function () {
            // TODO: 實作使用者清單
            return [
                'message' => '使用者清單功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.users.index',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.show' => [
        'methods' => ['GET'],
        'path' => '/api/admin/users/{id}',
        'handler' => function ($id) {
            // TODO: 實作使用者詳細資訊
            return [
                'message' => '使用者詳細資訊功能尚未實作',
                'user_id' => $id,
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.users.show',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.update' => [
        'methods' => ['PUT', 'PATCH'],
        'path' => '/api/admin/users/{id}',
        'handler' => function ($id) {
            // TODO: 實作使用者更新
            return [
                'message' => '使用者更新功能尚未實作',
                'user_id' => $id,
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.users.update',
        'middleware' => ['auth', 'admin']
    ],

    'admin.users.delete' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/users/{id}',
        'handler' => function ($id) {
            // TODO: 實作使用者刪除
            return [
                'message' => '使用者刪除功能尚未實作',
                'user_id' => $id,
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.users.delete',
        'middleware' => ['auth', 'admin']
    ],

    // 貼文管理
    'admin.posts.index' => [
        'methods' => ['GET'],
        'path' => '/api/admin/posts',
        'handler' => function () {
            // TODO: 實作管理員貼文清單
            return [
                'message' => '管理員貼文清單功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.posts.index',
        'middleware' => ['auth', 'admin']
    ],

    'admin.posts.moderate' => [
        'methods' => ['POST'],
        'path' => '/api/admin/posts/{id}/moderate',
        'handler' => function ($id) {
            // TODO: 實作貼文審查
            return [
                'message' => '貼文審查功能尚未實作',
                'post_id' => $id,
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.posts.moderate',
        'middleware' => ['auth', 'admin']
    ],

    // 系統設定
    'admin.settings.show' => [
        'methods' => ['GET'],
        'path' => '/api/admin/settings',
        'handler' => function () {
            // TODO: 實作系統設定顯示
            return [
                'message' => '系統設定功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.settings.show',
        'middleware' => ['auth', 'admin']
    ],

    'admin.settings.update' => [
        'methods' => ['PUT', 'PATCH'],
        'path' => '/api/admin/settings',
        'handler' => function () {
            // TODO: 實作系統設定更新
            return [
                'message' => '系統設定更新功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'admin.settings.update',
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
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'system_time' => date('c'),
                'uptime' => 'N/A' // TODO: 實作系統運行時間
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
