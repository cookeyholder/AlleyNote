<?php

declare(strict_types=1);

use App\Application\Controllers\Admin\TagManagementController;

return [
    // 標籤管理主頁面
    'admin.cache.tags.html' => [
        'methods' => ['GET'],
        'path' => '/admin/cache/tags',
        'handler' => [TagManagementController::class, 'renderTagPage'],
        'name' => 'admin.cache.tags.html',
        'middleware' => ['auth', 'admin']
    ],

    // 取得所有標籤列表
    'admin.cache.tags.list' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/tags',
        'handler' => [TagManagementController::class, 'listTags'],
        'name' => 'admin.cache.tags.list',
        'middleware' => ['auth', 'admin']
    ],

    // 取得標籤統計資訊
    'admin.cache.tags.statistics' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/tags/statistics',
        'handler' => [TagManagementController::class, 'getTagStatistics'],
        'name' => 'admin.cache.tags.statistics',
        'middleware' => ['auth', 'admin']
    ],

    // 批量清空多個標籤
    'admin.cache.tags.flush_multiple' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/cache/tags',
        'handler' => [TagManagementController::class, 'flushTags'],
        'name' => 'admin.cache.tags.flush_multiple',
        'middleware' => ['auth', 'admin', 'csrf']
    ],

    // 取得特定標籤詳細資訊
    'admin.cache.tags.show' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/tags/{tag}',
        'handler' => [TagManagementController::class, 'getTag'],
        'name' => 'admin.cache.tags.show',
        'middleware' => ['auth', 'admin']
    ],

    // 清空特定標籤的所有快取
    'admin.cache.tags.flush_single' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/cache/tags/{tag}',
        'handler' => [TagManagementController::class, 'flushTag'],
        'name' => 'admin.cache.tags.flush_single',
        'middleware' => ['auth', 'admin', 'csrf']
    ],

    // 取得所有分組列表
    'admin.cache.groups.list' => [
        'methods' => ['GET'],
        'path' => '/api/admin/cache/groups',
        'handler' => [TagManagementController::class, 'listGroups'],
        'name' => 'admin.cache.groups.list',
        'middleware' => ['auth', 'admin']
    ],

    // 建立快取分組
    'admin.cache.groups.create' => [
        'methods' => ['POST'],
        'path' => '/api/admin/cache/groups',
        'handler' => [TagManagementController::class, 'createGroup'],
        'name' => 'admin.cache.groups.create',
        'middleware' => ['auth', 'admin', 'csrf']
    ],

    // 清空特定分組
    'admin.cache.groups.flush' => [
        'methods' => ['DELETE'],
        'path' => '/api/admin/cache/groups/{group}',
        'handler' => [TagManagementController::class, 'flushGroup'],
        'name' => 'admin.cache.groups.flush',
        'middleware' => ['auth', 'admin', 'csrf']
    ],
];
