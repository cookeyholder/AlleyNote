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
    ]
];
