<?php

declare(strict_types=1);

/**
 * API 路由配置
 *
 * 這個檔案包含所有 API 相關的路由定義
 */

use App\Application\Controllers\PostController;
use App\Application\Controllers\Api\V1\UserController;
use App\Application\Controllers\Api\V1\RoleController;
use App\Application\Controllers\Api\V1\SettingController;
use App\Application\Controllers\Api\V1\TagController;

return [
    // API 健康檢查
    'api.health' => [
        'methods' => ['GET'],
        'path' => '/api/health',
        'handler' => function () {
            return [
                'status' => 'ok',
                'timestamp' => date('c'),
                'service' => 'AlleyNote API'
            ];
        },
        'name' => 'api.health'
    ],

    // 貼文 CRUD 路由
    'posts.index' => [
        'methods' => ['GET'],
        'path' => '/api/posts',
        'handler' => [PostController::class, 'index'],
        'name' => 'posts.index'
    ],

    'posts.show' => [
        'methods' => ['GET'],
        'path' => '/api/posts/{id}',
        'handler' => [PostController::class, 'show'],
        'name' => 'posts.show'
    ],

    'posts.store' => [
        'methods' => ['POST'],
        'path' => '/api/posts',
        'handler' => [PostController::class, 'store'],
        'name' => 'posts.store'
    ],

    'posts.update' => [
        'methods' => ['PUT', 'PATCH'],
        'path' => '/api/posts/{id}',
        'handler' => [PostController::class, 'update'],
        'name' => 'posts.update'
    ],

    'posts.destroy' => [
        'methods' => ['DELETE'],
        'path' => '/api/posts/{id}',
        'handler' => [PostController::class, 'destroy'],
        'name' => 'posts.destroy'
    ],

    // API 資訊和文件
    'api.info' => [
        'methods' => ['GET'],
        'path' => '/api',
        'handler' => [\App\Application\Controllers\Web\SwaggerController::class, 'info'],
        'name' => 'api.info'
    ],

    // Swagger UI 介面
    'api.docs.ui' => [
        'methods' => ['GET'],
        'path' => '/api/docs/ui',
        'handler' => [\App\Application\Controllers\Web\SwaggerController::class, 'ui'],
        'name' => 'api.docs.ui'
    ],

    // OpenAPI JSON 規格
    'api.docs' => [
        'methods' => ['GET'],
        'path' => '/api/docs',
        'handler' => [\App\Application\Controllers\Web\SwaggerController::class, 'docs'],
        'name' => 'api.docs'
    ],

    // ========================================
    // 使用者管理 API
    // ========================================
    'users.index' => [
        'methods' => ['GET'],
        'path' => '/api/users',
        'handler' => [UserController::class, 'index'],
        'middleware' => ['auth'],
        'name' => 'users.index'
    ],

    'users.show' => [
        'methods' => ['GET'],
        'path' => '/api/users/{id}',
        'handler' => [UserController::class, 'show'],
        'middleware' => ['auth'],
        'name' => 'users.show'
    ],

    'users.store' => [
        'methods' => ['POST'],
        'path' => '/api/users',
        'handler' => [UserController::class, 'store'],
        'middleware' => ['auth'],
        'name' => 'users.store'
    ],

    'users.update' => [
        'methods' => ['PUT'],
        'path' => '/api/users/{id}',
        'handler' => [UserController::class, 'update'],
        'middleware' => ['auth'],
        'name' => 'users.update'
    ],

    'users.destroy' => [
        'methods' => ['DELETE'],
        'path' => '/api/users/{id}',
        'handler' => [UserController::class, 'destroy'],
        'middleware' => ['auth'],
        'name' => 'users.destroy'
    ],

    'users.assign_roles' => [
        'methods' => ['PUT'],
        'path' => '/api/users/{id}/roles',
        'handler' => [UserController::class, 'assignRoles'],
        'middleware' => ['auth'],
        'name' => 'users.assign_roles'
    ],

    // ========================================
    // 角色管理 API
    // ========================================
    'roles.index' => [
        'methods' => ['GET'],
        'path' => '/api/roles',
        'handler' => [RoleController::class, 'index'],
        'middleware' => ['auth'],
        'name' => 'roles.index'
    ],

    'roles.show' => [
        'methods' => ['GET'],
        'path' => '/api/roles/{id}',
        'handler' => [RoleController::class, 'show'],
        'middleware' => ['auth'],
        'name' => 'roles.show'
    ],

    'roles.store' => [
        'methods' => ['POST'],
        'path' => '/api/roles',
        'handler' => [RoleController::class, 'store'],
        'middleware' => ['auth'],
        'name' => 'roles.store'
    ],

    'roles.update' => [
        'methods' => ['PUT'],
        'path' => '/api/roles/{id}',
        'handler' => [RoleController::class, 'update'],
        'middleware' => ['auth'],
        'name' => 'roles.update'
    ],

    'roles.destroy' => [
        'methods' => ['DELETE'],
        'path' => '/api/roles/{id}',
        'handler' => [RoleController::class, 'destroy'],
        'middleware' => ['auth'],
        'name' => 'roles.destroy'
    ],

    'roles.update_permissions' => [
        'methods' => ['PUT'],
        'path' => '/api/roles/{id}/permissions',
        'handler' => [RoleController::class, 'updatePermissions'],
        'middleware' => ['auth'],
        'name' => 'roles.update_permissions'
    ],

    // ========================================
    // 權限 API
    // ========================================
    'permissions.index' => [
        'methods' => ['GET'],
        'path' => '/api/permissions',
        'handler' => [RoleController::class, 'permissions'],
        'middleware' => ['auth'],
        'name' => 'permissions.index'
    ],

    'permissions.grouped' => [
        'methods' => ['GET'],
        'path' => '/api/permissions/grouped',
        'handler' => [RoleController::class, 'permissionsGrouped'],
        'middleware' => ['auth'],
        'name' => 'permissions.grouped'
    ],

    // ========================================
    // 系統設定 API
    // ========================================
    'settings.index' => [
        'methods' => ['GET'],
        'path' => '/api/settings',
        'handler' => [SettingController::class, 'index'],
        'name' => 'settings.index'
    ],

    'settings.show' => [
        'methods' => ['GET'],
        'path' => '/api/settings/{key}',
        'handler' => [SettingController::class, 'show'],
        'name' => 'settings.show'
    ],

    'settings.update' => [
        'methods' => ['PUT'],
        'path' => '/api/settings',
        'handler' => [SettingController::class, 'update'],
        'middleware' => ['auth'],
        'name' => 'settings.update'
    ],

    'settings.update_single' => [
        'methods' => ['PUT'],
        'path' => '/api/settings/{key}',
        'handler' => [SettingController::class, 'updateSingle'],
        'middleware' => ['auth'],
        'name' => 'settings.update_single'
    ],

    'settings.timezone_info' => [
        'methods' => ['GET'],
        'path' => '/api/settings/timezone/info',
        'handler' => [SettingController::class, 'getTimezoneInfo'],
        'name' => 'settings.timezone_info'
    ],

    // ========================================
    // 標籤管理 API
    // ========================================
    'tags.index' => [
        'methods' => ['GET'],
        'path' => '/api/tags',
        'handler' => [TagController::class, 'index'],
        'name' => 'tags.index'
    ],

    'tags.show' => [
        'methods' => ['GET'],
        'path' => '/api/tags/{id}',
        'handler' => [TagController::class, 'show'],
        'name' => 'tags.show'
    ],

    'tags.store' => [
        'methods' => ['POST'],
        'path' => '/api/tags',
        'handler' => [TagController::class, 'store'],
        'middleware' => ['auth'],
        'name' => 'tags.store'
    ],

    'tags.update' => [
        'methods' => ['PUT'],
        'path' => '/api/tags/{id}',
        'handler' => [TagController::class, 'update'],
        'middleware' => ['auth'],
        'name' => 'tags.update'
    ],

    'tags.destroy' => [
        'methods' => ['DELETE'],
        'path' => '/api/tags/{id}',
        'handler' => [TagController::class, 'destroy'],
        'middleware' => ['auth'],
        'name' => 'tags.destroy'
    ]
];
