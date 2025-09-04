<?php

declare(strict_types=1);

/**
 * 認證路由配置
 * 
 * 這個檔案包含所有認證相關的路由定義
 */

use App\Application\Controllers\Api\V1\AuthController;

return [
    // 登入
    'auth.login' => [
        'methods' => ['POST'],
        'path' => '/api/auth/login',
        'handler' => [AuthController::class, 'login'],
        'name' => 'auth.login'
    ],

    // 登出
    'auth.logout' => [
        'methods' => ['POST'],
        'path' => '/api/auth/logout',
        'handler' => [AuthController::class, 'logout'],
        'name' => 'auth.logout',
        'middleware' => ['auth'] // 需要認證的中間件
    ],

    // 註冊
    'auth.register' => [
        'methods' => ['POST'],
        'path' => '/api/auth/register',
        'handler' => [AuthController::class, 'register'],
        'name' => 'auth.register'
    ],

    // 取得目前使用者資訊
    'auth.user' => [
        'methods' => ['GET'],
        'path' => '/api/auth/me',
        'handler' => [AuthController::class, 'me'],
        'name' => 'auth.user',
        'middleware' => ['auth'] // 需要認證的中間件
    ],

    // 重新整理 Token
    'auth.refresh' => [
        'methods' => ['POST'],
        'path' => '/api/auth/refresh',
        'handler' => [AuthController::class, 'refresh'],
        'name' => 'auth.refresh'
    ],

    // 密碼重設請求 (TODO: 實作)
    'auth.password.reset.request' => [
        'methods' => ['POST'],
        'path' => '/api/auth/password/reset',
        'handler' => function () {
            return [
                'message' => '密碼重設請求功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.reset.request'
    ],

    // 密碼重設確認 (TODO: 實作)
    'auth.password.reset.confirm' => [
        'methods' => ['POST'],
        'path' => '/api/auth/password/reset/confirm',
        'handler' => function () {
            return [
                'message' => '密碼重設確認功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.reset.confirm'
    ],

    // 更新密碼 (TODO: 實作)
    'auth.password.update' => [
        'methods' => ['PUT'],
        'path' => '/api/auth/password',
        'handler' => function () {
            return [
                'message' => '更新密碼功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.update',
        'middleware' => ['auth'] // 需要認證的中間件
    ]
];
