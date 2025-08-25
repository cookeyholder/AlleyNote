<?php

declare(strict_types=1);

/**
 * 認證路由配置
 * 
 * 這個檔案包含所有認證相關的路由定義
 */

return [
    // 登入
    'auth.login' => [
        'methods' => ['POST'],
        'path' => '/api/auth/login',
        'handler' => function () {
            // TODO: 實作登入邏輯
            return [
                'message' => '登入功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.login'
    ],

    // 登出
    'auth.logout' => [
        'methods' => ['POST'],
        'path' => '/api/auth/logout',
        'handler' => function () {
            // TODO: 實作登出邏輯
            return [
                'message' => '登出功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.logout',
        'middleware' => ['auth'] // 需要認證的中間件
    ],

    // 註冊
    'auth.register' => [
        'methods' => ['POST'],
        'path' => '/api/auth/register',
        'handler' => function () {
            // TODO: 實作註冊邏輯
            return [
                'message' => '註冊功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.register'
    ],

    // 取得目前使用者資訊
    'auth.user' => [
        'methods' => ['GET'],
        'path' => '/api/auth/user',
        'handler' => function () {
            // TODO: 實作取得使用者資訊邏輯
            return [
                'message' => '取得使用者資訊功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.user',
        'middleware' => ['auth'] // 需要認證的中間件
    ],

    // 重設密碼請求
    'auth.password.reset.request' => [
        'methods' => ['POST'],
        'path' => '/api/auth/password/reset',
        'handler' => function () {
            // TODO: 實作密碼重設請求邏輯
            return [
                'message' => '密碼重設請求功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.reset.request'
    ],

    // 重設密碼確認
    'auth.password.reset.confirm' => [
        'methods' => ['POST'],
        'path' => '/api/auth/password/reset/confirm',
        'handler' => function () {
            // TODO: 實作密碼重設確認邏輯
            return [
                'message' => '密碼重設確認功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.reset.confirm'
    ],

    // 更新密碼
    'auth.password.update' => [
        'methods' => ['PUT'],
        'path' => '/api/auth/password',
        'handler' => function () {
            // TODO: 實作更新密碼邏輯
            return [
                'message' => '更新密碼功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.password.update',
        'middleware' => ['auth'] // 需要認證的中間件
    ],

    // 重新整理 Token
    'auth.refresh' => [
        'methods' => ['POST'],
        'path' => '/api/auth/refresh',
        'handler' => function () {
            // TODO: 實作 Token 重新整理邏輯
            return [
                'message' => 'Token 重新整理功能尚未實作',
                'status' => 'not_implemented'
            ];
        },
        'name' => 'auth.refresh',
        'middleware' => ['auth'] // 需要認證的中間件
    ]
];
