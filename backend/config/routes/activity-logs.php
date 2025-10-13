<?php

declare(strict_types=1);

/**
 * 活動日誌路由配置
 *
 * 包含活動日誌查詢和統計的 API 路由定義
 */

use App\Application\Controllers\Api\V1\ActivityLogController;

return [
    // =========================================
    // 活動日誌查詢 API 路由 (需要認證)
    // =========================================

    // 取得所有活動日誌
    'activity_logs.index' => [
        'methods' => ['GET'],
        'path' => '/api/v1/activity-logs',
        'handler' => [ActivityLogController::class, 'index'],
        'name' => 'activity_logs.index',
        'middleware' => ['jwt.auth']
    ],

    // 記錄新活動
    'activity_logs.store' => [
        'methods' => ['POST'],
        'path' => '/api/v1/activity-logs',
        'handler' => [ActivityLogController::class, 'store'],
        'name' => 'activity_logs.store',
        'middleware' => ['jwt.auth']
    ],

    // 取得活動統計
    'activity_logs.stats' => [
        'methods' => ['GET'],
        'path' => '/api/v1/activity-logs/stats',
        'handler' => [ActivityLogController::class, 'getStats'],
        'name' => 'activity_logs.stats',
        'middleware' => ['jwt.auth']
    ],

    // 取得當前使用者活動日誌
    'activity_logs.me' => [
        'methods' => ['GET'],
        'path' => '/api/v1/activity-logs/me',
        'handler' => [ActivityLogController::class, 'getCurrentUserLogs'],
        'name' => 'activity_logs.me',
        'middleware' => ['jwt.auth']
    ],

    // 取得登入失敗統計
    'activity_logs.login_failures' => [
        'methods' => ['GET'],
        'path' => '/api/v1/activity-logs/login-failures',
        'handler' => [ActivityLogController::class, 'getLoginFailureStats'],
        'name' => 'activity_logs.login_failures',
        'middleware' => ['jwt.auth']
    ],
];
