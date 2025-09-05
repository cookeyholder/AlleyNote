<?php

declare(strict_types=1);

/**
 * 統計功能 API 路由配置
 *
 * 這個檔案包含所有統計相關的 API 路由定義
 */

use App\Application\Controllers\Api\Statistics\StatisticsController;
use App\Application\Controllers\Api\Statistics\StatisticsAdminController;

return [
    // === 統計查詢 API (公開或需要基本認證) ===

    // 統計概覽
    'statistics.overview' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/overview',
        'handler' => [StatisticsController::class, 'overview'],
        'name' => 'statistics.overview',
        'middleware' => ['jwt.auth'], // 需要 JWT 認證
    ],

    // 文章統計
    'statistics.posts' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/posts',
        'handler' => [StatisticsController::class, 'posts'],
        'name' => 'statistics.posts',
        'middleware' => ['jwt.auth'],
    ],

    // 來源分佈統計
    'statistics.sources' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/sources',
        'handler' => [StatisticsController::class, 'sources'],
        'name' => 'statistics.sources',
        'middleware' => ['jwt.auth'],
    ],

    // 使用者統計
    'statistics.users' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/users',
        'handler' => [StatisticsController::class, 'users'],
        'name' => 'statistics.users',
        'middleware' => ['jwt.auth'],
    ],

    // 熱門內容
    'statistics.popular' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/popular',
        'handler' => [StatisticsController::class, 'popular'],
        'name' => 'statistics.popular',
        'middleware' => ['jwt.auth'],
    ],

    // 統計趨勢
    'statistics.trends' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/trends',
        'handler' => [StatisticsController::class, 'trends'],
        'name' => 'statistics.trends',
        'middleware' => ['jwt.auth'],
    ],

    // === 統計管理 API (管理員專用) ===

    // 重新整理統計資料
    'statistics.admin.refresh' => [
        'methods' => ['POST'],
        'path' => '/api/admin/statistics/refresh',
        'handler' => [StatisticsAdminController::class, 'refresh'],
        'name' => 'statistics.admin.refresh',
        'middleware' => ['jwt.auth', 'admin.auth'], // 需要管理員權限
    ],

    // 清除統計快取
    'statistics.admin.clear_cache' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/statistics/cache',
        'handler' => [StatisticsAdminController::class, 'clearCache'],
        'name' => 'statistics.admin.clear_cache',
        'middleware' => ['jwt.auth', 'admin.auth'],
    ],

    // 系統健康檢查
    'statistics.admin.health' => [
        'methods' => ['GET'],
        'path' => '/api/admin/statistics/health',
        'handler' => [StatisticsAdminController::class, 'health'],
        'name' => 'statistics.admin.health',
        'middleware' => ['jwt.auth', 'admin.auth'],
    ],

    // 任務狀態查詢
    'statistics.admin.status' => [
        'methods' => ['GET'],
        'path' => '/api/admin/statistics/status',
        'handler' => [StatisticsAdminController::class, 'status'],
        'name' => 'statistics.admin.status',
        'middleware' => ['jwt.auth', 'admin.auth'],
    ],

    // 系統清理
    'statistics.admin.cleanup' => [
        'methods' => ['POST'],
        'path' => '/api/admin/statistics/cleanup',
        'handler' => [StatisticsAdminController::class, 'cleanup'],
        'name' => 'statistics.admin.cleanup',
        'middleware' => ['jwt.auth', 'admin.auth'],
    ],

    // === 統計資料匯出 API (擴展功能) ===

    // 匯出統計報告
    'statistics.export' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/export',
        'handler' => function () {
            // 這個功能可以在後續階段實作
            return [
                'message' => '統計匯出功能即將推出',
                'available_formats' => ['json', 'csv', 'xlsx'],
                'contact' => '請聯繫管理員取得匯出功能存取權限'
            ];
        },
        'name' => 'statistics.export',
        'middleware' => ['jwt.auth'],
    ],

    // === API 文件相關 ===

    // 統計 API 說明
    'statistics.docs' => [
        'methods' => ['GET'],
        'path' => '/api/statistics',
        'handler' => function () {
            return [
                'title' => 'AlleyNote 統計 API',
                'version' => '1.0.0',
                'description' => '提供網站統計資料的 REST API 服務',
                'endpoints' => [
                    'overview' => 'GET /api/statistics/overview - 取得統計概覽',
                    'posts' => 'GET /api/statistics/posts - 取得文章統計',
                    'sources' => 'GET /api/statistics/sources - 取得來源分佈',
                    'users' => 'GET /api/statistics/users - 取得使用者統計',
                    'popular' => 'GET /api/statistics/popular - 取得熱門內容',
                    'trends' => 'GET /api/statistics/trends - 取得統計趨勢',
                ],
                'admin_endpoints' => [
                    'refresh' => 'POST /api/admin/statistics/refresh - 重新整理統計',
                    'clear_cache' => 'DELETE /api/admin/statistics/cache - 清除快取',
                    'health' => 'GET /api/admin/statistics/health - 健康檢查',
                    'status' => 'GET /api/admin/statistics/status - 任務狀態',
                    'cleanup' => 'POST /api/admin/statistics/cleanup - 系統清理',
                ],
                'authentication' => [
                    'type' => 'JWT Bearer Token',
                    'required' => true,
                    'admin_required' => 'Admin endpoints require admin privileges'
                ],
                'parameters' => [
                    'period_type' => 'daily|weekly|monthly|yearly',
                    'start_date' => 'ISO 8601 date format (optional)',
                    'end_date' => 'ISO 8601 date format (optional)',
                    'page' => 'Page number for pagination (default: 1)',
                    'per_page' => 'Items per page (default: 20, max: 100)',
                    'limit' => 'Limit for popular content (default: 20, max: 100)',
                ],
                'response_format' => [
                    'success' => [
                        'success' => true,
                        'data' => '(response data)',
                        'message' => '(success message)'
                    ],
                    'error' => [
                        'success' => false,
                        'error' => [
                            'message' => '(error message)',
                            'code' => '(http status code)'
                        ]
                    ]
                ],
                'documentation' => '/api/docs/ui#/Statistics',
                'contact' => [
                    'support' => 'admin@alleynote.com',
                    'github' => 'https://github.com/alleynote/alleynote'
                ]
            ];
        },
        'name' => 'statistics.docs'
    ],
];
