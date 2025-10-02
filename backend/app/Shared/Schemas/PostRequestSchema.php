<?php

declare(strict_types=1);

namespace App\Shared\Schemas;

use App\Domains\Security\Enums\ActivitySeverity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreatePostRequest',
    type: 'object',
    title: '建立貼文請求',
    description: '建立新貼文的請求資料結構，包含標題、內容、分類等必要資訊',
    required: ['title', 'content'],
    properties: [
        new OA\Property(
            property: 'title',
            type: 'string',
            description: '貼文標題，用於簡要描述貼文主題',
            minLength: 1,
            maxLength: 255,
            example: '系統維護通知 - 2025年1月20日',
        ),
        new OA\Property(
            property: 'content',
            type: 'string',
            description: '貼文內容，支援 Markdown 格式',
            minLength: 1,
            maxLength: 10000,
            example: "親愛的使用者，\n\n系統將於 **2025年1月20日 02:00-04:00** 進行維護，期間服務可能暫時中斷。\n\n維護內容：\n- 資料庫效能優化\n- 安全性更新\n- 新功能部署\n\n造成不便敬請見諒。\n\n---\n技術團隊",
        ),
        new OA\Property(
            property: 'category',
            type: 'string',
            description: '貼文分類，用於組織和篩選貼文',
            enum: ['general', 'announcement', 'urgent', 'notice'],
            default: 'general',
            example: 'announcement',
        ),
        new OA\Property(
            property: 'priority',
            type: 'string',
            description: '優先級，影響貼文在列表中的排序和顯示樣式',
            enum: [ActivitySeverity::LOW->value, ActivitySeverity::NORMAL->value, ActivitySeverity::MEDIUM->value, ActivitySeverity::HIGH->value, ActivitySeverity::CRITICAL->value],
            default: 'normal',
            example: 'high',
        ),
        new OA\Property(
            property: 'expires_at',
            type: 'string',
            format: 'date-time',
            description: '過期時間（ISO 8601 格式），過期後貼文將自動隱藏',
            nullable: true,
            example: '2025-12-31T23:59:59+08:00',
        ),
        new OA\Property(
            property: 'tags',
            type: 'array',
            description: '標籤列表，用於標記和搜尋',
            items: new OA\Items(type: 'string', maxLength: 50),
            maxItems: 10,
            example: ['維護', '系統更新', '重要通知'],
        ),
        new OA\Property(
            property: 'is_pinned',
            type: 'boolean',
            description: '是否置頂顯示',
            default: false,
            example: true,
        ),
        new OA\Property(
            property: 'allow_comments',
            type: 'boolean',
            description: '是否允許留言',
            default: true,
            example: false,
        ),
    ],
    example: [
        'title' => '系統維護通知 - 2025年1月20日',
        'content' => "親愛的使用者，\n\n系統將於 **2025年1月20日 02:00-04:00** 進行維護...",
        'category' => 'announcement',
        'priority' => 'high',
        'expires_at' => '2025-12-31T23:59:59+08:00',
        'tags' => ['維護', '系統更新', '重要通知'],
        'is_pinned' => true,
        'allow_comments' => false,
    ],
)]
#[OA\Schema(
    schema: 'UpdatePostRequest',
    type: 'object',
    title: '更新貼文請求',
    description: '更新既有貼文的請求資料，所有欄位皆為可選，僅更新提供的欄位',
    properties: [
        new OA\Property(
            property: 'title',
            type: 'string',
            description: '更新貼文標題',
            minLength: 1,
            maxLength: 255,
            example: '【更新】系統維護延期通知',
        ),
        new OA\Property(
            property: 'content',
            type: 'string',
            description: '更新貼文內容',
            minLength: 1,
            maxLength: 10000,
            example: "**重要更新**\n\n原定於 2025年1月20日 的系統維護將延期至 **2025年1月25日**。\n\n詳細資訊請參考最新公告。",
        ),
        new OA\Property(
            property: 'category',
            type: 'string',
            description: '更新貼文分類',
            enum: ['general', 'announcement', 'urgent', 'notice'],
            example: 'urgent',
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            description: '更新貼文狀態',
            enum: ['draft', 'published', 'archived'],
            example: 'published',
        ),
        new OA\Property(
            property: 'priority',
            type: 'string',
            description: '更新優先級',
            enum: [ActivitySeverity::LOW->value, ActivitySeverity::NORMAL->value, ActivitySeverity::MEDIUM->value, ActivitySeverity::HIGH->value, ActivitySeverity::CRITICAL->value],
            example: 'urgent',
        ),
        new OA\Property(
            property: 'expires_at',
            type: 'string',
            format: 'date-time',
            description: '更新過期時間',
            nullable: true,
            example: '2025-02-28T23:59:59+08:00',
        ),
        new OA\Property(
            property: 'tags',
            type: 'array',
            description: '更新標籤列表',
            items: new OA\Items(type: 'string', maxLength: 50),
            maxItems: 10,
            example: ['維護延期', '重要更新', '緊急通知'],
        ),
        new OA\Property(
            property: 'is_pinned',
            type: 'boolean',
            description: '更新置頂狀態',
            example: true,
        ),
        new OA\Property(
            property: 'allow_comments',
            type: 'boolean',
            description: '更新留言允許狀態',
            example: true,
        ),
    ],
    example: [
        'title' => '【更新】系統維護延期通知',
        'content' => "**重要更新**\n\n原定於 2025年1月20日 的系統維護將延期...",
        'category' => 'urgent',
        'status' => 'published',
        'priority' => 'urgent',
        'expires_at' => '2025-02-28T23:59:59+08:00',
        'tags' => ['維護延期', '重要更新', '緊急通知'],
        'is_pinned' => true,
        'allow_comments' => true,
    ],
)]
#[OA\Schema(
    schema: 'ApiResponse',
    type: 'object',
    title: 'API 回應',
    description: '標準 API 回應格式，用於包裝所有 API 的成功回應',
    required: ['success'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            description: '操作是否成功',
            example: true,
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            description: '操作結果訊息',
            example: '貼文建立成功',
        ),
        new OA\Property(
            property: 'data',
            description: '回應資料內容，根據不同 API 會有不同結構',
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/Post'),
                new OA\Schema(type: 'array', items: new OA\Items(ref: '#/components/schemas/Post')),
                new OA\Schema(type: 'object', description: '其他類型的資料'),
            ],
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            description: '額外的元資料',
            properties: [
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00+08:00'),
                new OA\Property(property: 'request_id', type: 'string', example: 'req_550e8400e29b41d4a716446655440000'),
                new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
            ],
        ),
    ],
    example: [
        'success' => true,
        'message' => '貼文建立成功',
        'data' => [
            'id' => 123,
            'title' => '新的貼文標題',
            'content' => '貼文內容...',
            'status' => 'published',
            'created_at' => '2025-01-15T10:30:00+08:00',
        ],
        'meta' => [
            'timestamp' => '2025-01-15T10:30:00+08:00',
            'request_id' => 'req_550e8400e29b41d4a716446655440000',
            'version' => '1.0.0',
        ],
    ],
)]
#[OA\Schema(
    schema: 'PaginatedResponse',
    type: 'object',
    title: '分頁回應',
    description: '分頁資料的標準回應格式，包含資料項目和詳細的分頁資訊',
    required: ['data', 'pagination'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            description: '請求是否成功',
            example: true,
        ),
        new OA\Property(
            property: 'data',
            type: 'array',
            description: '當前頁的資料項目清單',
            items: new OA\Items(ref: '#/components/schemas/Post'),
        ),
        new OA\Property(
            property: 'pagination',
            type: 'object',
            description: '詳細的分頁資訊',
            required: ['current_page', 'per_page', 'total', 'total_pages'],
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', description: '目前頁碼（從 1 開始）', minimum: 1, example: 2),
                new OA\Property(property: 'per_page', type: 'integer', description: '每頁顯示筆數', minimum: 1, maximum: 100, example: 10),
                new OA\Property(property: 'total', type: 'integer', description: '總資料筆數', minimum: 0, example: 157),
                new OA\Property(property: 'total_pages', type: 'integer', description: '總頁數', minimum: 0, example: 16),
                new OA\Property(property: 'has_next', type: 'boolean', description: '是否有下一頁', example: true),
                new OA\Property(property: 'has_prev', type: 'boolean', description: '是否有上一頁', example: true),
                new OA\Property(property: 'first_page', type: 'integer', description: '第一頁頁碼', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', description: '最後一頁頁碼', example: 16),
                new OA\Property(property: 'from', type: 'integer', description: '當前頁第一筆資料的序號', example: 11),
                new OA\Property(property: 'to', type: 'integer', description: '當前頁最後一筆資料的序號', example: 20),
            ],
        ),
        new OA\Property(
            property: 'links',
            type: 'object',
            description: '分頁導航連結',
            properties: [
                new OA\Property(property: 'first', type: 'string', nullable: true, example: '/api/posts?page=1&limit=10'),
                new OA\Property(property: 'last', type: 'string', nullable: true, example: '/api/posts?page=16&limit=10'),
                new OA\Property(property: 'prev', type: 'string', nullable: true, example: '/api/posts?page=1&limit=10'),
                new OA\Property(property: 'next', type: 'string', nullable: true, example: '/api/posts?page=3&limit=10'),
                new OA\Property(property: 'self', type: 'string', example: '/api/posts?page=2&limit=10'),
            ],
        ),
    ],
    example: [
        'success' => true,
        'data' => [
            [
                'id' => 11,
                'title' => '範例貼文標題 11',
                'content' => '貼文內容...',
                'category' => 'general',
                'status' => 'published',
                'priority' => 'normal',
                'created_at' => '2025-01-15T10:30:00+08:00',
            ],
        ],
        'pagination' => [
            'current_page' => 2,
            'per_page' => 10,
            'total' => 157,
            'total_pages' => 16,
            'has_next' => true,
            'has_prev' => true,
            'first_page' => 1,
            'last_page' => 16,
            'from' => 11,
            'to' => 20,
        ],
        'links' => [
            'first' => '/api/posts?page=1&limit=10',
            'last' => '/api/posts?page=16&limit=10',
            'prev' => '/api/posts?page=1&limit=10',
            'next' => '/api/posts?page=3&limit=10',
            'self' => '/api/posts?page=2&limit=10',
        ],
    ],
)]
#[OA\Schema(
    schema: 'PostRequest',
    type: 'object',
    title: '貼文請求',
    description: '建立或更新貼文的通用請求資料',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/CreatePostRequest'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    title: '驗證錯誤',
    description: '表單或 API 請求資料驗證失敗時的詳細錯誤回應格式',
    required: ['success', 'error'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            description: '請求是否成功',
            example: false,
        ),
        new OA\Property(
            property: 'error',
            type: 'string',
            description: '主要錯誤訊息',
            example: '資料驗證失敗',
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            description: '詳細錯誤描述',
            example: '提交的資料包含驗證錯誤，請檢查以下欄位並重新提交',
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            description: '各欄位的具體驗證錯誤訊息',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
                description: '該欄位的所有錯誤訊息列表',
            ),
            example: [
                'title' => ['標題不能為空', '標題長度不能超過 255 字元'],
                'content' => ['內容不能為空', '內容長度不能超過 10000 字元'],
                'email' => ['電子郵件格式不正確'],
                'password' => ['密碼長度至少需要 8 字元', '密碼必須包含至少一個數字'],
                'category' => ['分類必須是以下值之一：general, announcement, urgent, notice'],
            ],
        ),
        new OA\Property(
            property: 'code',
            type: 'integer',
            description: 'HTTP 狀態碼',
            example: 400,
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            description: '額外的錯誤資訊',
            properties: [
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00+08:00'),
                new OA\Property(property: 'request_id', type: 'string', example: 'req_550e8400e29b41d4a716446655440000'),
                new OA\Property(property: 'validation_rules', type: 'object', description: '驗證規則參考'),
            ],
        ),
    ],
    example: [
        'success' => false,
        'error' => '資料驗證失敗',
        'message' => '提交的資料包含驗證錯誤，請檢查以下欄位並重新提交',
        'errors' => [
            'title' => ['標題不能為空', '標題長度不能超過 255 字元'],
            'content' => ['內容不能為空'],
            'email' => ['電子郵件格式不正確'],
            'password' => ['密碼長度至少需要 8 字元', '密碼必須包含至少一個數字'],
        ],
        'code' => 400,
        'meta' => [
            'timestamp' => '2025-01-15T10:30:00+08:00',
            'request_id' => 'req_550e8400e29b41d4a716446655440000',
        ],
    ],
)]
class PostRequestSchema
{
    // 這個類別只是用來放置 Schema 註解
}
