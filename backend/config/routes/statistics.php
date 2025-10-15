<?php

declare(strict_types=1);

/**
 * 統計功能路由配置
 *
 * 包含統計查詢和統計管理的 API 路由定義
 */

use App\Application\Controllers\Api\V1\StatisticsController;
use App\Application\Controllers\Api\V1\StatisticsAdminController;
use App\Application\Controllers\Api\V1\StatisticsChartController;
use App\Application\Controllers\Api\V1\PostViewController;
use App\Application\Controllers\Api\V1\AdvancedAnalyticsController;
use App\Application\Controllers\Api\V1\StatisticsExportController;

return [
    // =========================================
    // 文章瀏覽追蹤 API 路由 (允許匿名存取)
    // =========================================

    // 記錄文章瀏覽
    'posts.view.record' => [
        'methods' => ['POST'],
        'path' => '/api/posts/{id}/view',
        'handler' => [PostViewController::class, 'recordView'],
        'name' => 'posts.view.record',
        'middleware' => ['post_view_rate_limit'] // 專用的速率限制中介軟體
    ],

    // =========================================
    // 統計查詢 API 路由 (需要認證)
    // =========================================

    // 取得統計概覽
    'statistics.overview' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/overview',
        'handler' => [StatisticsController::class, 'getOverview'],
        'name' => 'statistics.overview',
        'middleware' => ['jwt.auth']
    ],

    // 取得文章統計
    'statistics.posts' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/posts',
        'handler' => [StatisticsController::class, 'getPosts'],
        'name' => 'statistics.posts',
        'middleware' => ['jwt.auth']
    ],

    // 取得來源統計
    'statistics.sources' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/sources',
        'handler' => [StatisticsController::class, 'getSources'],
        'name' => 'statistics.sources',
        'middleware' => ['jwt.auth']
    ],

    // 取得使用者統計
    'statistics.users' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/users',
        'handler' => [StatisticsController::class, 'getUsers'],
        'name' => 'statistics.users',
        'middleware' => ['jwt.auth']
    ],

    // 取得熱門內容統計
    'statistics.popular' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/popular',
        'handler' => [StatisticsController::class, 'getPopular'],
        'name' => 'statistics.popular',
        'middleware' => ['jwt.auth']
    ],

    // =========================================
    // 統計管理 API 路由 (需要管理員權限)
    // =========================================

    // 手動刷新統計資料
    'statistics.admin.refresh' => [
        'methods' => ['POST'],
        'path' => '/api/admin/statistics/refresh',
        'handler' => [StatisticsAdminController::class, 'refresh'],
        'name' => 'statistics.admin.refresh',
        'middleware' => ['jwt.auth']
    ],

    // 清除統計快取
    'statistics.admin.cache.clear' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/statistics/cache',
        'handler' => [StatisticsAdminController::class, 'clearCache'],
        'name' => 'statistics.admin.cache.clear',
        'middleware' => ['jwt.auth']
    ],

    // 統計系統健康檢查
    'statistics.admin.health' => [
        'methods' => ['GET'],
        'path' => '/api/admin/statistics/health',
        'handler' => [StatisticsAdminController::class, 'health'],
        'name' => 'statistics.admin.health',
        'middleware' => ['jwt.auth']
    ],

    // =========================================
    // 圖表統計 API 路由 (需要認證)
    // =========================================

    // 取得文章發布時間序列統計
    'statistics.charts.posts.timeseries' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/charts/posts/timeseries',
        'handler' => [StatisticsChartController::class, 'getPostsTimeSeries'],
        'name' => 'statistics.charts.posts.timeseries',
        'middleware' => ['jwt.auth']
    ],

    // 取得使用者活動時間序列統計
    'statistics.charts.users.timeseries' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/charts/users/timeseries',
        'handler' => [StatisticsChartController::class, 'getUserActivityTimeSeries'],
        'name' => 'statistics.charts.users.timeseries',
        'middleware' => ['jwt.auth']
    ],

    // 取得瀏覽量時間序列統計
    'statistics.charts.views.timeseries' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/charts/views/timeseries',
        'handler' => [StatisticsChartController::class, 'getViewsTimeSeries'],
        'name' => 'statistics.charts.views.timeseries',
        'middleware' => ['jwt.auth']
    ],

    // 取得標籤分布統計
    'statistics.charts.tags.distribution' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/charts/tags/distribution',
        'handler' => [StatisticsChartController::class, 'getTagsDistribution'],
        'name' => 'statistics.charts.tags.distribution',
        'middleware' => ['jwt.auth']
    ],

    // 取得來源分布統計
    'statistics.charts.sources.distribution' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/charts/sources/distribution',
        'handler' => [StatisticsChartController::class, 'getSourcesDistribution'],
        'name' => 'statistics.charts.sources.distribution',
        'middleware' => ['jwt.auth']
    ],

    // =========================================
    // 進階分析 API 路由 (需要認證)
    // =========================================

    // 取得裝置類型統計
    'statistics.analytics.device_types' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/device-types',
        'handler' => [AdvancedAnalyticsController::class, 'getDeviceTypes'],
        'name' => 'statistics.analytics.device_types',
        'middleware' => ['jwt.auth']
    ],

    // 取得瀏覽器統計
    'statistics.analytics.browsers' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/browsers',
        'handler' => [AdvancedAnalyticsController::class, 'getBrowsers'],
        'name' => 'statistics.analytics.browsers',
        'middleware' => ['jwt.auth']
    ],

    // 取得操作系統統計
    'statistics.analytics.os' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/operating-systems',
        'handler' => [AdvancedAnalyticsController::class, 'getOperatingSystems'],
        'name' => 'statistics.analytics.os',
        'middleware' => ['jwt.auth']
    ],

    // 取得來源統計
    'statistics.analytics.referrers' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/referrers',
        'handler' => [AdvancedAnalyticsController::class, 'getReferrers'],
        'name' => 'statistics.analytics.referrers',
        'middleware' => ['jwt.auth']
    ],

    // 取得時段分布統計
    'statistics.analytics.hourly' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/hourly-distribution',
        'handler' => [AdvancedAnalyticsController::class, 'getHourlyDistribution'],
        'name' => 'statistics.analytics.hourly',
        'middleware' => ['jwt.auth']
    ],

    // 取得綜合分析報告
    'statistics.analytics.comprehensive' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/analytics/comprehensive',
        'handler' => [AdvancedAnalyticsController::class, 'getComprehensiveReport'],
        'name' => 'statistics.analytics.comprehensive',
        'middleware' => ['jwt.auth']
    ],

    // =========================================
    // 報表匯出 API 路由 (需要認證)
    // =========================================

    // 匯出文章瀏覽統計為 CSV
    'statistics.export.views_csv' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/export/views/csv',
        'handler' => [StatisticsExportController::class, 'exportViewsCSV'],
        'name' => 'statistics.export.views_csv',
        'middleware' => ['jwt.auth']
    ],

    // 匯出綜合報告為 CSV
    'statistics.export.comprehensive_csv' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/export/comprehensive/csv',
        'handler' => [StatisticsExportController::class, 'exportComprehensiveCSV'],
        'name' => 'statistics.export.comprehensive_csv',
        'middleware' => ['jwt.auth']
    ],

    // 匯出綜合報告為 JSON
    'statistics.export.comprehensive_json' => [
        'methods' => ['GET'],
        'path' => '/api/statistics/export/comprehensive/json',
        'handler' => [StatisticsExportController::class, 'exportJSON'],
        'name' => 'statistics.export.comprehensive_json',
        'middleware' => ['jwt.auth']
    ]
];
