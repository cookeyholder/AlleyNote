<?php

declare(strict_types=1);

use App\Application\Controllers\Admin\CacheMonitorController;

return [
    // 快取統計資料
    'admin.cache.stats' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/stats',
        'handler' => [CacheMonitorController::class, 'getStats'],
        'name' => 'admin.cache.stats',
        'middleware' => ['auth', 'admin']
    ],

    // 詳細快取指標
    'admin.cache.metrics' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/metrics',
        'handler' => [CacheMonitorController::class, 'getMetrics'],
        'name' => 'admin.cache.metrics',
        'middleware' => ['auth', 'admin']
    ],

    // 快取健康狀況
    'admin.cache.health' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/health',
        'handler' => [CacheMonitorController::class, 'getHealth'],
        'name' => 'admin.cache.health',
        'middleware' => ['auth', 'admin']
    ],

    // 快取驅動資訊
    'admin.cache.drivers' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/drivers',
        'handler' => [CacheMonitorController::class, 'getDriverInfo'],
        'name' => 'admin.cache.drivers',
        'middleware' => ['auth', 'admin']
    ],

    // 重設統計資料
    'admin.cache.reset' => [
        'methods' => ['POST'],
        'path' => '/api/admin/cache/reset',
        'handler' => [CacheMonitorController::class, 'resetStats'],
        'name' => 'admin.cache.reset',
        'middleware' => ['auth', 'admin', 'csrf']
    ],

    // 清空所有快取
    'admin.cache.flush' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/cache/flush',
        'handler' => [CacheMonitorController::class, 'flushCache'],
        'name' => 'admin.cache.flush',
        'middleware' => ['auth', 'admin', 'csrf']
    ],
];
