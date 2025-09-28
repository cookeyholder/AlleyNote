<?php

declare(strict_types=1);

namespace App\Shared\Schemas;

use App\Domains\Security\Enums\ActivitySeverity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Post',
    type: 'object',
    title: '貼文',
    description: '貼文資料結構',
    required: ['id', 'title', 'content', 'status', 'created_at'],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            description: '貼文ID',
            example: 1,
        ),
        new OA\Property(
            property: 'title',
            type: 'string',
            description: '貼文標題',
            maxLength: 255,
            example: '重要公告',
        ),
        new OA\Property(
            property: 'content',
            type: 'string',
            description: '貼文內容',
            example: '這是一則重要公告內容',
        ),
        new OA\Property(
            property: 'category',
            type: 'string',
            description: '貼文分類',
            enum: ['general', 'announcement', 'urgent', 'notice'],
            example: 'announcement',
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            description: '貼文狀態',
            enum: ['draft', 'published', 'archived'],
            example: 'published',
        ),
        new OA\Property(
            property: 'priority',
            type: 'string',
            description: '優先級',
            enum: [ActivitySeverity::LOW->value, ActivitySeverity::NORMAL->value, ActivitySeverity::HIGH->value, ActivitySeverity::URGENT->value],
            example: 'normal',
        ),
        new OA\Property(
            property: 'author_id',
            type: 'integer',
            description: '作者ID',
            example: 1,
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            description: '建立時間',
            example: '2025-01-15T10:30:00Z',
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            description: '更新時間',
            example: '2025-01-15T11:00:00Z',
        ),
        new OA\Property(
            property: 'expires_at',
            type: 'string',
            format: 'date-time',
            description: '過期時間',
            nullable: true,
            example: '2025-12-31T23:59:59Z',
        ),
    ],
)]
class PostSchema
{
    // 這個類別只是用來放置 Schema 註解
}
