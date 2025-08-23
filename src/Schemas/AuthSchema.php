<?php

declare(strict_types=1);

namespace App\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    type: "object",
    title: "使用者",
    description: "使用者資料結構",
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            description: "使用者ID",
            example: 1
        ),
        new OA\Property(
            property: "username",
            type: "string",
            description: "使用者名稱",
            example: "admin"
        ),
        new OA\Property(
            property: "email",
            type: "string",
            format: "email",
            description: "電子郵件",
            example: "admin@example.com"
        ),
        new OA\Property(
            property: "role",
            type: "string",
            description: "使用者角色",
            enum: ["admin", "moderator", "user"],
            example: "admin"
        ),
        new OA\Property(
            property: "created_at",
            type: "string",
            format: "date-time",
            description: "建立時間",
            example: "2025-01-15T10:30:00Z"
        )
    ]
)]
#[OA\Schema(
    schema: "LoginRequest",
    type: "object",
    title: "登入請求",
    description: "使用者登入請求",
    required: ["username", "password"],
    properties: [
        new OA\Property(
            property: "username",
            type: "string",
            description: "使用者名稱",
            example: "admin"
        ),
        new OA\Property(
            property: "password",
            type: "string",
            format: "password",
            description: "密碼",
            example: "password123"
        ),
        new OA\Property(
            property: "remember_me",
            type: "boolean",
            description: "記住我",
            default: false,
            example: false
        )
    ]
)]
#[OA\Schema(
    schema: "LoginResponse",
    type: "object",
    title: "登入回應",
    description: "登入成功回應",
    properties: [
        new OA\Property(
            property: "success",
            type: "boolean",
            description: "登入是否成功",
            example: true
        ),
        new OA\Property(
            property: "message",
            type: "string",
            description: "回應訊息",
            example: "登入成功"
        ),
        new OA\Property(
            property: "token",
            type: "string",
            description: "JWT Token",
            example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
        ),
        new OA\Property(
            property: "user",
            ref: "#/components/schemas/User",
            description: "使用者資訊"
        ),
        new OA\Property(
            property: "expires_at",
            type: "string",
            format: "date-time",
            description: "Token 過期時間",
            example: "2025-01-16T10:30:00Z"
        )
    ]
)]
class AuthSchema
{
    // 這個類別只是用來放置 Schema 註解
}
