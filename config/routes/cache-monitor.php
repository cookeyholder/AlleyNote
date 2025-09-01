<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Application\Controllers\Admin\CacheMonitorController;

return function (App $app) {
    $app->group('/api/admin/cache', function (RouteCollectorProxy $group) {
        // 快取統計資料
        $group->get('/stats', [CacheMonitorController::class, 'getStats']);

        // 詳細快取指標
        $group->get('/metrics', [CacheMonitorController::class, 'getMetrics']);

        // 快取健康狀況
        $group->get('/health', [CacheMonitorController::class, 'getHealth']);

        // 快取驅動資訊
        $group->get('/drivers', [CacheMonitorController::class, 'getDriverInfo']);

        // 重設統計資料
        $group->post('/reset', [CacheMonitorController::class, 'resetStats']);

        // 清空所有快取
        $group->delete('/flush', [CacheMonitorController::class, 'flushCache']);
    });
};
