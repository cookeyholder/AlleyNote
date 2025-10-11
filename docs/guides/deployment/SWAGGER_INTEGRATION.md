# Swagger UI 整合說明

**版本**: v4.0
**更新日期**: 2025-09-03
**架構**: 前後端分離 (原生 HTML/JavaScript/CSS + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

AlleyNote 專案已整合 Swagger UI 來提供完整的 API 文件，專為前後端分離架構設計。

## 功能概述

- ✅ **OpenAPI 3.1 規格** (最新版本)
- ✅ **互動式 API 文件** (支援前後端分離測試)
- ✅ **自動程式碼產生支援** (原生 HTML/JavaScript/CSS + PHP 8.4.12)
- ✅ **多語言 Schema 註解** (繁體中文)
- ✅ **JWT Bearer 授權支援** (API 認證)
- ✅ **CORS 預檢請求支援** (前後端通訊)
- ✅ **原生 HTML/JavaScript/CSS 整合範例** (Composition API)
- ✅ **PHP 8.4.12 屬性註解** (現代化 PHP 語法)

## 檔案結構 (前後端分離)

```
backend/                         # PHP 8.4.12 後端
├── config/
│   ├── swagger.php              # Swagger 設定檔
│   └── swagger-routes.php       # API 路由設定
├── app/
│   ├── Controllers/
│   │   ├── Api/                 # API 控制器目錄
│   │   │   ├── AnnouncementController.php  # 公告 API
│   │   │   └── AuthController.php          # 認證 API
│   │   └── SwaggerController.php           # Swagger UI 控制器
│   └── Schemas/                 # OpenAPI Schema 定義
│       ├── AnnouncementSchema.php
│       ├── AuthRequestSchema.php
│       └── ErrorResponseSchema.php
├── scripts/
│   └── generate-swagger-docs.php # API 文件產生腳本
└── public/
    ├── api-docs.json           # 產生的 JSON 文件
    └── api-docs.yaml           # 產生的 YAML 文件

frontend/                        # 原生 HTML/JavaScript/CSS 前端
├── src/
│   ├── api/
│   │   ├── swagger-client.js   # 自動產生的 API 客戶端
│   │   └── types.d.ts          # TypeScript 型別定義
│   └── composables/
│       └── useApi.js           # TypeScript Composition 整合
```

## 使用說明

### 1. 安裝相依套件

後端已在 `composer.json` 中加入 `zircote/swagger-php`：

```bash
# 後端依賴安裝
cd backend
docker compose exec web composer install

# 前端依賴安裝
cd ../frontend
docker-compose up -d @openapitools/openapi-generator-cli
```

### 2. 設定 API 路由 (後端)

將 Swagger 路由加入你的主要路由檔案中：

```php
// 在你的路由設定檔案中加入：
$routes = require __DIR__ . '/../config/swagger-routes.php';

foreach ($routes as $route) {
    $router->map([$route['method']], $route['path'], $route['handler']);
}
```

### 3. 產生 API 文件

使用提供的腳本產生文件：

```bash
# 在專案根目錄執行
php scripts/generate-swagger-docs.php
```

### 4. 訪問 API 文件

啟動伺服器後，訪問以下網址：

- **Swagger UI 介面**: `http://localhost/api/docs/ui`
- **JSON 格式文件**: `http://localhost/api/docs`
- **靜態 JSON 檔案**: `http://localhost/api-docs.json`

## API 文件功能

### 支援的端點

目前已為以下 API 端點加入文件：

#### Posts API
- `GET /api/posts` - 取得貼文列表（支援分頁、搜尋、篩選）
- `GET /api/posts/{id}` - 取得單一貼文
- `POST /api/posts` - 建立新貼文（需要授權）
- `PUT /api/posts/{id}` - 更新貼文（需要授權）
- `DELETE /api/posts/{id}` - 刪除貼文（需要授權）

### 授權機制

API 支援兩種授權方式：

1. **Session 授權**: 使用 `PHPSESSID` Cookie
2. **Bearer Token**: 使用 JWT Token

在 Swagger UI 中，點擊 "Authorize" 按鈕設定授權資訊。

### CSRF 保護

POST、PUT、DELETE 請求需要 CSRF Token：

```http
X-CSRF-TOKEN: your-csrf-token-here
```

## 新增 API 文件

### 為控制器方法加入註解

```php
/**
 * @OA\Post(
 *     path="/api/posts",
 *     summary="建立新貼文",
 *     tags={"Posts"},
 *     security={{"sessionAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/CreatePostRequest")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="建立成功",
 *         @OA\JsonContent(ref="#/components/schemas/Post")
 *     )
 * )
 */
public function store(Request $request, Response $response): Response
{
    // 實作...
}
```

### 建立新的 Schema

在 `src/Schemas/` 目錄下建立新的 Schema 檔案：

```php
<?php

namespace App\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="YourSchema",
 *     type="object",
 *     title="你的 Schema",
 *     description="Schema 描述",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="名稱")
 * )
 */
class YourSchema
{
    // 這個類別只是用來放置 Schema 註解
}
```

## 開發工作流程

1. **開發 API**: 在控制器中實作 API 邏輯
2. **加入註解**: 為方法加入 Swagger 註解
3. **定義 Schema**: 在 `src/Schemas/` 中定義資料結構
4. **產生文件**: 執行 `php scripts/generate-swagger-docs.php`
5. **測試 API**: 使用 Swagger UI 測試 API 功能

## 疑難排解

### 常見問題

1. **無法存取 Swagger UI**
   - 檢查路由是否正確設定
   - 確認 SwaggerController 已載入

2. **API 文件空白**
   - 檢查 Swagger 註解語法
   - 確認路徑掃描設定正確

3. **Schema 無法載入**
   - 檢查 Schema 檔案路徑
   - 確認 Schema 註解格式正確

### 除錯工具

使用產生腳本檢查錯誤：

```bash
php scripts/generate-swagger-docs.php
```

檢查產生的 JSON 檔案格式：

```bash
cat public/api-docs.json | jq .
```

## 進階設定

### 自訂 Swagger UI 主題

修改 `SwaggerController::ui()` 方法來自訂 UI：

```php
// 在 SwaggerController 中加入自訂 CSS
$customCss = '
.swagger-ui .topbar { background-color: #1976d2; }
.swagger-ui .info .title { color: #1976d2; }
';
```

### 環境特定設定

在不同環境中使用不同的伺服器設定：

```php
// config/swagger.php
'servers' => [
    [
        'url' => $_ENV['API_BASE_URL'] ?? 'http://localhost/api',
        'description' => 'API Server'
    ]
]
```

## 相關資源

- [OpenAPI 3.0 規格](https://swagger.io/specification/)
- [swagger-php 文件](https://zircote.github.io/swagger-php/)
- [Swagger UI 文件](https://swagger.io/tools/swagger-ui/)
