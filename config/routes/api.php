<?php

declare(strict_types=1);

/**
 * API 路由配置
 * 
 * 這個檔案包含所有 API 相關的路由定義
 */

use App\Application\Controllers\PostController;

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
        'handler' => function () {
            return [
                'name' => 'AlleyNote API',
                'version' => '1.0.0',
                'description' => '公布欄系統 API',
                'endpoints' => [
                    'health' => '/api/health',
                    'posts' => '/api/posts',
                    'docs' => '/api/docs'
                ]
            ];
        },
        'name' => 'api.info'
    ],

    'api.docs' => [
        'methods' => ['GET'],
        'path' => '/api/docs',
        'handler' => function () {
            // 重定向到 Swagger 文件
            return [
                'message' => 'API 文件',
                'swagger_ui' => '/swagger-ui',
                'openapi_spec' => '/api-docs.json'
            ];
        },
        'name' => 'api.docs'
    ]
];
