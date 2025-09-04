<?php

declare(strict_types=1);

/**
 * Web 路由配置
 * 
 * 這個檔案包含所有 Web 介面相關的路由定義
 */

return [
    // 首頁
    'home' => [
        'methods' => ['GET'],
        'path' => '/',
        'handler' => function () {
            return [
                'message' => '歡迎來到 AlleyNote 公布欄系統',
                'version' => '1.0.0',
                'api_base' => '/api'
            ];
        },
        'name' => 'home'
    ],

    // Swagger UI
    'swagger.ui' => [
        'methods' => ['GET'],
        'path' => '/swagger-ui',
        'handler' => function () {
            // 這裡將來可以實作 Swagger UI 的回應
            return [
                'message' => 'Swagger UI',
                'spec_url' => '/api-docs.json'
            ];
        },
        'name' => 'swagger.ui'
    ],

    // OpenAPI 規格檔案
    'openapi.json' => [
        'methods' => ['GET'],
        'path' => '/api-docs.json',
        'handler' => function () {
            // 讀取 OpenAPI 規格檔案
            $specFile = __DIR__ . '/../../public/api-docs.json';
            if (file_exists($specFile)) {
                return json_decode(file_get_contents($specFile), true);
            }

            return [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'AlleyNote API',
                    'version' => '1.0.0'
                ]
            ];
        },
        'name' => 'openapi.json'
    ],

    'openapi.yaml' => [
        'methods' => ['GET'],
        'path' => '/api-docs.yaml',
        'handler' => function () {
            // 讀取 OpenAPI YAML 規格檔案
            $specFile = __DIR__ . '/../../public/api-docs.yaml';
            if (file_exists($specFile)) {
                return file_get_contents($specFile);
            }

            return "openapi: 3.0.0\ninfo:\n  title: AlleyNote API\n  version: 1.0.0\n";
        },
        'name' => 'openapi.yaml'
    ],

    // 靜態檔案處理（開發環境用）
    'assets' => [
        'methods' => ['GET'],
        'path' => '/assets/{path}',
        'handler' => function ($path) {
            // 簡單的靜態檔案處理
            $filePath = __DIR__ . '/../../public/assets/' . $path;

            if (!file_exists($filePath) || !is_file($filePath)) {
                return [
                    'error' => 'File not found',
                    'path' => $path
                ];
            }

            return [
                'file' => $filePath,
                'type' => mime_content_type($filePath) ?: 'application/octet-stream'
            ];
        },
        'name' => 'assets'
    ]
];
