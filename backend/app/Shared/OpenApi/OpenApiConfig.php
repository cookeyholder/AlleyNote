<?php

declare(strict_types=1);

/**
 * OpenAPI 規格配置.
 *
 * 此檔案定義 AlleyNote API 的基本 OpenAPI 規格資訊
 */

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: '
AlleyNote 公布欄系統 API 文件

這是一個現代化的公布欄系統 API，提供完整的貼文管理、使用者認證、檔案上傳等功能。

## 認證方式
- **JWT Bearer Token**: 使用 JWT Token 進行 API 認證
- **Session Auth**: 傳統 Session 認證 (向下相容)

## 主要功能
- 📝 **貼文管理**: 建立、編輯、刪除、查看貼文
- 👤 **使用者認證**: 登入、登出、註冊、密碼管理
- 📎 **檔案上傳**: 支援附件上傳和管理
- 🔐 **權限控制**: 基於角色的存取控制

## API 版本
當前版本: v1.0.0
支援的回應格式: JSON
字元編碼: UTF-8

## 錯誤處理
所有錯誤回應都遵循統一格式：
```json
{
  "success": false,
  "error": "錯誤訊息",
  "errors": {
    "field": ["具體錯誤說明"]
  }
}
```

## 聯絡資訊
如有問題或建議，請聯繫開發團隊。
    ',
    title: 'AlleyNote API',
)]
#[OA\Server(
    url: 'http://localhost',
    description: '開發環境伺服器',
)]
#[OA\Server(
    url: 'https://api.alleynote.example.com',
    description: '正式環境伺服器',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    name: 'Authorization',
    in: 'header',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\SecurityScheme(
    securityScheme: 'sessionAuth',
    type: 'apiKey',
    name: 'PHPSESSID',
    in: 'cookie',
)]
#[OA\Tag(
    name: 'auth',
    description: '認證相關 API - 登入、登出、註冊、Token 管理',
)]
#[OA\Tag(
    name: 'posts',
    description: '貼文管理 API - CRUD 操作、置頂、搜尋',
)]
#[OA\Tag(
    name: 'attachments',
    description: '附件管理 API - 檔案上傳、下載、刪除',
)]
#[OA\Tag(
    name: 'admin',
    description: '管理員 API - 系統管理、使用者管理',
)]
#[OA\Response(
    response: 'Unauthorized',
    description: '未授權存取',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'error', type: 'string', example: '未授權存取'),
        ],
    ),
)]
#[OA\Response(
    response: 'Forbidden',
    description: '權限不足',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'error', type: 'string', example: '權限不足'),
        ],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: '資源不存在',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'error', type: 'string', example: '資源不存在'),
        ],
    ),
)]
#[OA\Response(
    response: 'ValidationError',
    description: '資料驗證失敗',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'success', type: 'boolean', example: false),
            new OA\Property(property: 'error', type: 'string', example: '資料驗證失敗'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                ),
                example: [
                    'email' => ['電子郵件格式不正確'],
                    'password' => ['密碼長度不足'],
                ],
            ),
        ],
    ),
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            description: '電子郵件地址',
            example: 'user@example.com',
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            description: '密碼',
            minLength: 8,
            example: 'password123',
        ),
        new OA\Property(
            property: 'device_name',
            type: 'string',
            description: '裝置名稱 (可選)',
            example: 'iPhone 13 Pro',
        ),
    ],
)]
#[OA\Schema(
    schema: 'LoginResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: '登入成功'),
        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.'),
        new OA\Property(property: 'refresh_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', description: 'Token 有效期（秒）', example: 3600),
        new OA\Property(property: 'expires_at', type: 'integer', description: 'Token 過期時間戳', example: 1704067200),
        new OA\Property(
            property: 'user',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                new OA\Property(property: 'name', type: 'string', example: 'User Name'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'role', type: 'string', example: 'user'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
    ],
)]
class OpenApiConfig
{
    // 這個類別只是為了存放 OpenAPI 註解，不包含實際功能
}
