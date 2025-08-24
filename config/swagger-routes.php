<?php

declare(strict_types=1);

/**
 * Swagger UI 路由設定
 *
 * 將此路由加入到主路由檔案中
 */

use App\Application\Controllers\Web\SwaggerController;

// Swagger API 文件路由
return [
    // API 文件 JSON 格式
    [
        'method' => 'GET',
        'path' => '/api/docs',
        'handler' => [SwaggerController::class, 'docs'],
        'description' => '取得 OpenAPI JSON 格式文件'
    ],

    // Swagger UI 介面
    [
        'method' => 'GET',
        'path' => '/api/docs/ui',
        'handler' => [SwaggerController::class, 'ui'],
        'description' => '顯示 Swagger UI 介面'
    ],

    // 重新導向根路徑到 Swagger UI（可選）
    [
        'method' => 'GET',
        'path' => '/docs',
        'handler' => function ($request, $response) {
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/api/docs/ui');
        },
        'description' => '重新導向到 Swagger UI'
    ]
];
