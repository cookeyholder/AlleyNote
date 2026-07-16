# AlleyNote API 文件

**版本**: v4.0
**基礎 URL**: `https://your-domain.com/api`
**API 版本**: RESTful API v4.0
**更新日期**: 2025-09-27
**前後端分離**: Vue.js 3 + PHP 8.4.12 DDD 後端

---

## 📋 目錄

1. [概述](#概述)
2. [認證機制](#認證機制)
3. [API 回應格式](#api-回應格式)
4. [驗證錯誤格式](#驗證錯誤格式)
5. [文章 API](#文章-api)
6. [認證 API](#認證-api)
7. [附件 API](#附件-api)
8. [IP 規則 API](#ip-規則-api)
9. [使用者活動記錄 API](#使用者活動記錄-api)
10. [統計 API](#統計-api)
11. [統計管理 API](#統計管理-api)
12. [API 文件產生](#api-文件產生)
13. [錯誤代碼](#錯誤代碼)
14. [速率限制](#速率限制)

---

## 概述

AlleyNote API v4.0 提供完整的前後端分離公布欄網站功能，基於 PHP 8.4.12 DDD 架構設計，包含文章管理、使用者認證、附件上傳、IP 控制等功能。

### 版本 4.0 特色

- ✅ **前後端分離**: JavaScript Composition API + PHP 8.4.12 DDD 後端
- ✅ **DDD 架構**: 領域驅動設計，採用最新 PHP 8.4 語法特性
- ✅ **完整測試覆蓋**: 148 個測試檔案，1,393 個通過測試
- ✅ **統計模組**: 新增 5 個查詢端點、3 個管理端點與瀏覽追蹤 API
- ✅ **現代化容器**: Docker 28.3.3 & Docker Compose v2.39.2
- ✅ **強型別驗證**: PHP 8.4 型別系統，繁體中文錯誤訊息
- ✅ **統一錯誤格式**: 標準化的 API 錯誤回應
- ✅ **自動 API 文件**: Swagger 整合，自動產生 OpenAPI 規格
- ✅ **增強安全性**: CSRF 防護、XSS 過濾、SQL 注入防護
- ✅ **效能優化**: OPcache v8.4.12、快取機制、查詢優化

### 技術堆疊

- **後端**: PHP 8.4.12 (Xdebug 3.4.5, Zend OPcache v8.4.12)
- **前端**: JavaScript Composition API
- **測試**: PHPUnit 11.5.34
- **容器**: Docker 28.3.3 & Docker Compose v2.39.2
- **資料庫**: SQLite3 (預設推薦) / PostgreSQL 16 (大型部署)

### 支援的格式

- **請求格式**: JSON, Form Data (檔案上傳)
- **回應格式**: JSON
- **編碼**: UTF-8
- **日期格式**: ISO 8601 (YYYY-MM-DDTHH:mm:ssZ)

---

## 認證機制

### 支援的認證方式

1. **JWT 認證**: JSON Web Token (建議用於前後端分離)
2. **Session 認證**: 基於 PHP Session (向後相容)
3. **CSRF Token**: 表單提交需要 CSRF Token

### JWT 認證 (推薦)

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "Example#Pass123!"
}
```

回應：

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

使用 JWT Token：

```http
GET /api/posts
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### CSRF Token

所有 POST, PUT, DELETE 請求都需要包含 CSRF Token：

```http
POST /api/posts
Content-Type: application/json
X-CSRF-TOKEN: abc123def456
Authorization: Bearer your-jwt-token

{
    "title": "文章標題",
    "content": "文章內容"
}
```

---

## API 回應格式

### 成功回應格式

```json
{
  "success": true,
  "message": "操作成功",
  "data": {
    // 回應資料
  },
  "meta": {
    "timestamp": "YYYY-MM-DDTHH:mm:ssZ",
    "request_id": "req_123456"
  }
}
```

### 分頁回應格式

```json
{
  "success": true,
  "data": [
    // 資料項目
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5,
    "has_next": true,
    "has_prev": false
  }
}
```

### 錯誤回應格式

```json
{
  "success": false,
  "message": "操作失敗",
  "error": "ERROR_CODE",
  "errors": {
    "field": ["錯誤訊息"]
  },
  "meta": {
    "timestamp": "YYYY-MM-DDTHH:mm:ssZ",
    "request_id": "req_123456"
  }
}
```

---

## 驗證錯誤格式（v2.0 新增）

AlleyNote v2.0 使用新的驗證系統，提供詳細的驗證錯誤訊息。

### 驗證錯誤回應

```json
{
  "success": false,
  "message": "資料驗證失敗",
  "error": "VALIDATION_FAILED",
  "errors": {
    "title": ["此欄位為必填", "最少需要 5 個字元"],
    "email": ["請輸入有效的電子郵件地址"],
    "content": ["此欄位為必填"]
  }
}
```

### 支援的驗證規則

| 規則                  | 說明         | 錯誤訊息範例               |
| --------------------- | ------------ | -------------------------- |
| `required`            | 必填欄位     | "此欄位為必填"             |
| `email`               | 電子郵件格式 | "請輸入有效的電子郵件地址" |
| `min_length:5`        | 最少字元數   | "最少需要 5 個字元"        |
| `max_length:255`      | 最多字元數   | "最多只能 255 個字元"      |
| `integer`             | 整數型別     | "必須為整數"               |
| `unique:table,column` | 唯一性檢查   | "此電子郵件已被使用"       |
| `exists:table,column` | 存在性檢查   | "指定的文章不存在"         |

---

## 文章 API

### 取得文章列表

```http
GET /api/posts?page=1&limit=20&search=關鍵字&category=announcement
```

**查詢參數:**

| 參數       | 類型    | 必填 | 說明                                  | 預設值 |
| ---------- | ------- | ---- | ------------------------------------- | ------ |
| `page`     | integer | 否   | 頁碼                                  | 1      |
| `limit`    | integer | 否   | 每頁筆數 (1-100)                      | 20     |
| `search`   | string  | 否   | 搜尋關鍵字                            | -      |
| `category` | string  | 否   | 分類篩選                              | -      |
| `status`   | string  | 否   | 狀態篩選 (published, draft, archived) | -      |

**回應範例:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "重要公告",
      "content": "這是一則重要公告...",
      "category": "announcement",
      "status": "published",
      "author_id": 1,
      "author_name": "管理員",
      "created_at": "YYYY-MM-DDTHH:mm:ssZ",
      "updated_at": "YYYY-MM-DDTHH:mm:ssZ",
      "is_pinned": false,
      "attachments_count": 2
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "total_pages": 3,
    "has_next": true,
    "has_prev": false
  }
}
```

### 取得單一文章

```http
GET /api/posts/{id}
```

**路徑參數:**

- `id` (integer): 文章 ID

**回應範例:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "重要公告",
    "content": "這是一則重要公告的完整內容...",
    "category": "announcement",
    "status": "published",
    "author_id": 1,
    "author_name": "管理員",
    "created_at": "YYYY-MM-DDTHH:mm:ssZ",
    "updated_at": "YYYY-MM-DDTHH:mm:ssZ",
    "is_pinned": false,
    "view_count": 156,
    "attachments": [
      {
        "id": "uuid-123",
        "filename": "document.pdf",
        "size": 2048,
        "mime_type": "application/pdf",
        "download_url": "/api/attachments/uuid-123/download"
      }
    ]
  }
}
```

### 建立文章

```http
POST /api/posts
Content-Type: application/json
X-CSRF-TOKEN: token_here

{
    "title": "新文章標題",
    "content": "文章內容...",
    "category": "announcement",
    "is_pinned": false
}
```

**請求欄位:**

| 欄位        | 類型    | 必填 | 驗證規則                                        | 說明     |
| ----------- | ------- | ---- | ----------------------------------------------- | -------- |
| `title`     | string  | 是   | required, string, min_length:5, max_length:255  | 文章標題 |
| `content`   | string  | 是   | required, string, min_length:10                 | 文章內容 |
| `category`  | string  | 否   | sometimes, string, in:announcement,news,general | 文章分類 |
| `is_pinned` | boolean | 否   | sometimes, boolean                              | 是否置頂 |

**成功回應 (201):**

```json
{
  "success": true,
  "message": "文章建立成功",
  "data": {
    "id": 123,
    "title": "新文章標題",
    "content": "文章內容...",
    "category": "announcement",
    "status": "published",
    "author_id": 1,
    "created_at": "YYYY-MM-DDTHH:mm:ssZ",
    "is_pinned": false
  }
}
```

**驗證錯誤回應 (400):**

```json
{
  "success": false,
  "message": "資料驗證失敗",
  "error": "VALIDATION_FAILED",
  "errors": {
    "title": ["此欄位為必填", "最少需要 5 個字元"],
    "content": ["此欄位為必填"]
  }
}
```

### 更新文章

```http
PUT /api/posts/{id}
Content-Type: application/json
X-CSRF-TOKEN: token_here

{
    "title": "更新後的標題",
    "content": "更新後的內容..."
}
```

**請求欄位:**

| 欄位        | 類型    | 必填 | 驗證規則                                        | 說明     |
| ----------- | ------- | ---- | ----------------------------------------------- | -------- |
| `title`     | string  | 否   | sometimes, string, min_length:5, max_length:255 | 文章標題 |
| `content`   | string  | 否   | sometimes, string, min_length:10                | 文章內容 |
| `category`  | string  | 否   | sometimes, string, in:announcement,news,general | 文章分類 |
| `is_pinned` | boolean | 否   | sometimes, boolean                              | 是否置頂 |

### 刪除文章

```http
DELETE /api/posts/{id}
X-CSRF-TOKEN: token_here
```

**成功回應 (200):**

```json
{
  "success": true,
  "message": "文章刪除成功"
}
```

---

## 認證 API

### 使用者登入

```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "admin",
    "password": "Example#Pass123!",
    "remember_me": false
}
```

**請求欄位:**

| 欄位          | 類型    | 必填 | 驗證規則                       | 說明                 |
| ------------- | ------- | ---- | ------------------------------ | -------------------- |
| `username`    | string  | 是   | required, string, min_length:3 | 使用者名稱或電子郵件 |
| `password`    | string  | 是   | required, string, min_length:6 | 密碼                 |
| `remember_me` | boolean | 否   | sometimes, boolean             | 記住登入狀態         |

**成功回應 (200):**

```json
{
  "success": true,
  "message": "登入成功",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "role": "admin"
    },
    "session_id": "sess_123456",
    "csrf_token": "csrf_abc123"
  }
}
```

**登入失敗回應 (401):**

```json
{
  "success": false,
  "message": "登入失敗",
  "error": "INVALID_CREDENTIALS"
}
```

### 使用者註冊

```http
POST /api/auth/register
Content-Type: application/json

{
    "username": "newuser",
    "email": "user@example.com",
    "password": "Example#Pass123!",
    "password_confirmation": "Example#Pass123!"
}
```

**請求欄位:**

| 欄位                    | 類型   | 必填 | 驗證規則                                                             | 說明       |
| ----------------------- | ------ | ---- | -------------------------------------------------------------------- | ---------- |
| `username`              | string | 是   | required, string, min_length:3, max_length:50, unique:users,username | 使用者名稱 |
| `email`                 | string | 是   | required, email, unique:users,email                                  | 電子郵件   |
| `password`              | string | 是   | required, string, min_length:8                                       | 密碼       |
| `password_confirmation` | string | 是   | required, confirmed                                                  | 確認密碼   |

### 使用者登出

```http
POST /api/auth/logout
X-CSRF-TOKEN: token_here
```

### 取得當前使用者資訊

```http
GET /api/auth/me
```

**成功回應 (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "role": "admin",
    "created_at": "YYYY-MM-DDTHH:mm:ssZ",
    "last_login": "YYYY-MM-DDTHH:mm:ssZ"
  }
}
```

---

## 附件 API

### 上傳附件

```http
POST /api/posts/{post_id}/attachments
Content-Type: multipart/form-data
X-CSRF-TOKEN: token_here

file: [檔案]
description: "檔案說明"
```

**請求欄位:**

| 欄位          | 類型   | 必填 | 驗證規則                                                                     | 說明     |
| ------------- | ------ | ---- | ---------------------------------------------------------------------------- | -------- |
| `file`        | file   | 是   | file_required, file_max_size:10240, file_mime_types:image/\*,application/pdf | 上傳檔案 |
| `description` | string | 否   | sometimes, string, max_length:500                                            | 檔案說明 |

**成功回應 (201):**

```json
{
  "success": true,
  "message": "檔案上傳成功",
  "data": {
    "id": "uuid-123",
    "filename": "document.pdf",
    "original_name": "重要文件.pdf",
    "size": 2048,
    "mime_type": "application/pdf",
    "description": "檔案說明",
    "download_url": "/api/attachments/uuid-123/download",
    "created_at": "YYYY-MM-DDTHH:mm:ssZ"
  }
}
```

**檔案驗證錯誤 (400):**

```json
{
  "success": false,
  "message": "檔案驗證失敗",
  "error": "VALIDATION_FAILED",
  "errors": {
    "file": ["請選擇檔案", "檔案大小不能超過 10MB", "只允許 PDF 和圖片檔案"]
  }
}
```

### 下載附件

```http
GET /api/attachments/{id}/download
```

**成功回應 (200):**

```http
Content-Type: application/pdf
Content-Disposition: attachment; filename="document.pdf"
Content-Length: 2048

[檔案二進位內容]
```

### 刪除附件

```http
DELETE /api/attachments/{id}
X-CSRF-TOKEN: token_here
```

---

## IP 規則 API

### 取得 IP 規則列表

```http
GET /api/ip-rules?type=blacklist&page=1&limit=20
```

**查詢參數:**

| 參數    | 類型    | 必填 | 說明                            |
| ------- | ------- | ---- | ------------------------------- |
| `type`  | string  | 否   | 規則類型 (blacklist, whitelist) |
| `page`  | integer | 否   | 頁碼                            |
| `limit` | integer | 否   | 每頁筆數                        |

### 新增 IP 規則

```http
POST /api/ip-rules
Content-Type: application/json
X-CSRF-TOKEN: token_here

{
    "ip_address": "192.168.1.100",
    "type": "blacklist",
    "reason": "惡意訪問"
}
```

**請求欄位:**

| 欄位         | 類型   | 必填 | 驗證規則                          | 說明     |
| ------------ | ------ | ---- | --------------------------------- | -------- |
| `ip_address` | string | 是   | required, ip                      | IP 位址  |
| `type`       | string | 是   | required, in:blacklist,whitelist  | 規則類型 |
| `reason`     | string | 否   | sometimes, string, max_length:255 | 規則原因 |

---

## 使用者活動記錄 API

使用者活動記錄 API 提供完整的使用者行為監控和分析功能，支援實時記錄、批次處理和異常檢測。

### 🔍 基礎資訊

- **基礎路徑**: `/api/v1/activity-logs`
- **認證要求**: Session 認證
- **支援格式**: JSON
- **版本**: v1.0

### 📝 記錄新活動

```http
POST /api/v1/activity-logs
Content-Type: application/json

{
    "action_type": "auth.login.success",
    "user_id": 123,
    "description": "使用者登入成功",
    "metadata": {
        "login_method": "password",
        "remember_me": true,
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    }
}
```

**回應範例:**

```json
{
  "success": true,
  "message": "Activity logged successfully",
  "data": {
    "id": 12345,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "action_type": "auth.login.success",
    "action_category": "authentication",
    "user_id": 123,
    "status": "success",
    "description": "使用者登入成功",
    "created_at": "2024-12-27T10:30:00Z"
  }
}
```

**請求欄位:**

| 欄位          | 類型    | 必填 | 驗證規則                           | 說明                           |
| ------------- | ------- | ---- | ---------------------------------- | ------------------------------ |
| `action_type` | string  | 是   | required, valid_activity_type      | 活動類型 (21 種預定義類型)     |
| `user_id`     | integer | 否   | sometimes, integer                 | 使用者 ID，匿名活動可省略      |
| `target_type` | string  | 否   | sometimes, string, max_length:50   | 目標類型 (如 post, user, file) |
| `target_id`   | string  | 否   | sometimes, string, max_length:255  | 目標 ID                        |
| `description` | string  | 否   | sometimes, string, max_length:1000 | 活動描述                       |
| `metadata`    | object  | 否   | sometimes, array                   | 額外的元資料                   |

### 📦 批次記錄活動

```http
POST /api/v1/activity-logs/batch
Content-Type: application/json

{
    "logs": [
        {
            "action_type": "post.viewed",
            "user_id": 123,
            "target_type": "post",
            "target_id": "456",
            "metadata": {"view_duration": 30}
        },
        {
            "action_type": "attachment.downloaded",
            "user_id": 123,
            "target_type": "attachment",
            "target_id": "789",
            "metadata": {"file_size": 1024000}
        }
    ]
}
```

**回應範例:**

```json
{
  "success": true,
  "message": "Batch logging completed",
  "data": {
    "processed": 2,
    "successful": 2,
    "failed": 0,
    "results": [
      {
        "index": 0,
        "success": true,
        "id": 12346,
        "uuid": "550e8400-e29b-41d4-a716-446655440001"
      },
      {
        "index": 1,
        "success": true,
        "id": 12347,
        "uuid": "550e8400-e29b-41d4-a716-446655440002"
      }
    ]
  }
}
```

### 🔍 查詢活動記錄

```http
GET /api/v1/activity-logs?user_id=123&limit=50&page=1&action_category=authentication
```

**查詢參數:**

| 參數              | 類型    | 必填 | 說明                                                              |
| ----------------- | ------- | ---- | ----------------------------------------------------------------- |
| `user_id`         | integer | 否   | 過濾特定使用者的活動                                              |
| `action_type`     | string  | 否   | 過濾特定活動類型                                                  |
| `action_category` | string  | 否   | 過濾活動類別 (authentication, content, file_management, security) |
| `status`          | string  | 否   | 過濾狀態 (success, failed, error, blocked)                        |
| `date_from`       | string  | 否   | 起始日期 (YYYY-MM-DD)                                             |
| `date_to`         | string  | 否   | 結束日期 (YYYY-MM-DD)                                             |
| `limit`           | integer | 否   | 每頁記錄數 (預設 20，最大 100)                                    |
| `page`            | integer | 否   | 頁碼 (預設 1)                                                     |
| `order_by`        | string  | 否   | 排序欄位 (occurred_at, created_at)                                |
| `order`           | string  | 否   | 排序方向 (asc, desc)                                              |

**回應範例:**

````json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 12345,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "action_type": "auth.login.success",
                "action_category": "authentication",
                "user_id": 123,
                "status": "success",
                "description": "使用者登入成功",
                "ip_address": "192.168.1.100",
                "occurred_at": "2024-12-27T10:30:00Z",
                "created_at": "2024-12-27T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,

        ## 統計 API

        統計 API 提供多維度統計查詢，包括概覽、文章、來源、使用者與熱門內容等資料。所有端點都會回傳標準化的 JSON 結構，並在 `meta` 欄位提供查詢期間與快取資訊。

        ### 🔐 基礎資訊

        - **基礎路徑**: `/api/v1/statistics`
        - **認證要求**: JWT + `statistics.read` 權限（或 `statistics.*` / 全域權限）
        - **支援格式**: JSON
        - **版本**: v1.0

        ### 📈 可用端點

        | Method | Path | 說明 | 權限 |
        |--------|------|------|------|
        | GET | `/api/v1/statistics/overview` | 取得統計概覽（文章、使用者、互動指標） | `statistics.read` |
        | GET | `/api/v1/statistics/posts` | 取得文章統計（狀態、來源、長度、熱門作者等） | `statistics.read` |
        | GET | `/api/v1/statistics/sources` | 取得文章來源分布 | `statistics.read` |
        | GET | `/api/v1/statistics/users` | 取得使用者活躍度統計 | `statistics.read` |
        | GET | `/api/v1/statistics/popular` | 取得熱門內容排行榜（文章、使用者） | `statistics.read` |

        ### 🔍 查詢參數

        | 參數 | 類型 | 適用端點 | 說明 | 預設值 |
        |------|------|-----------|------|--------|
        | `start_date` | string (date) | all | 查詢起始日期 (YYYY-MM-DD) | 依服務自動計算 |
        | `end_date` | string (date) | all | 查詢結束日期 (YYYY-MM-DD) | 依服務自動計算 |
        | `page` | integer (≥1) | posts, users | 分頁頁碼 | 1 |
        | `limit` | integer (1-100) | posts, users, popular | 每頁筆數／列表數量 | 20 (posts/users)、10 (popular) |

        > ⚠️ 日期範圍超過 `config/statistics.php` 中 `performance.api_limits.max_date_range`（預設 90 天）會觸發 400 錯誤。

        ### 📊 範例：取得統計概覽

        ```http
        GET /api/v1/statistics/overview?start_date=2025-09-01&end_date=2025-09-27
        Authorization: Bearer <JWT>
        ```

        **回應範例：**

        ```json
        {
            "success": true,
            "data": {
                "total_posts": 1250,
                "active_users": 328,
                "new_users": 42,
                "post_activity": {
                    "published": 1100,
                    "draft": 120,
                    "archived": 30
                },
                "user_activity": {
                    "logins": 1640,
                    "views": 15620
                },
                "engagement_metrics": {
                    "posts_per_active_user": 3.81,
                    "user_growth_rate": 12.5,
                    "content_velocity": 42.6
                },
                "period_summary": {
                    "type": "custom",
                    "start": "2025-09-01T00:00:00+00:00",
                    "end": "2025-09-27T23:59:59+00:00"
                }
            },
            "meta": {
                "start_date": "2025-09-01",
                "end_date": "2025-09-27",
                "cache_hit": true
            }
        }
        ```

        ### 📰 範例：取得文章統計

        ```http
        GET /api/v1/statistics/posts?page=1&limit=20&start_date=2025-09-20&end_date=2025-09-27
        Authorization: Bearer <JWT>
        ```

        **回應欄位重點：**

        - `data.by_status`：文章狀態分布（published、draft、archived...）
        - `data.by_source`：文章來源統計（web、api、import、migration）
        - `data.top_authors`：依發文量排序的前五名作者
        - `data.time_distribution`：每日／每小時發佈趨勢
        - `pagination`：包含 `current_page`、`per_page`、`total_count`、`total_pages`

        ### 🔥 範例：取得熱門內容

        ```http
        GET /api/v1/statistics/popular?limit=10&start_date=2025-09-21&end_date=2025-09-27
        Authorization: Bearer <JWT>
        ```

        **回應欄位重點：**

        - `data.top_posts.by_views`：依瀏覽數排名的文章
        - `data.top_posts.by_comments`：依留言數排名的文章
        - `data.top_users.by_activity`：依活躍度排名的使用者
        - `data.trending_sources`：文章來源趨勢
        - `meta.cache_hit`：標記是否命中統計快照

        > 💡 所有統計查詢功能都支援快取標籤（`statistics:*`），成功生成快照會自動預熱快取。

        ---

        ## 統計管理 API

        統計管理 API 為管理員專用，用於手動刷新統計資料、清除快取與檢查系統健康狀態，建議僅在後台或維運腳本中使用。

        ### 🔐 基礎資訊

        - **基礎路徑**: `/api/admin/statistics`
        - **認證要求**: JWT + `statistics.admin` / `admin.*` 權限
        - **支援格式**: JSON
        - **版本**: v1.0

        ### 🛠️ 可用端點

        | Method | Path | 說明 | 權限 |
        |--------|------|------|------|
        | POST | `/api/admin/statistics/refresh` | 強制重新計算統計並預熱快取 | `statistics.admin` |
        | DELETE | `/api/admin/statistics/cache` | 清除統計相關快取標籤 | `statistics.admin` |
        | GET | `/api/admin/statistics/health` | 檢查快取、資料庫、快照狀態 | `statistics.admin` |

        ### 🚀 手動刷新統計

        ```http
        POST /api/admin/statistics/refresh
        Authorization: Bearer <ADMIN_JWT>
        Content-Type: application/json

        {
            "types": ["overview", "posts", "users"],
            "force_recalculate": true
        }
        ```

        **回應範例：**

        ```json
        {
            "success": true,
            "message": "統計資料刷新成功",
            "data": {
                "refreshed_types": ["overview", "posts", "users"],
                "snapshots_created": 3,
                "cache_cleared": true,
                "execution_time": 1.82,
                "timestamp": "2025-09-27T09:15:04+00:00"
            }
        }
        ```

        ### 🧹 清除統計快取

        ```http
        DELETE /api/admin/statistics/cache?tags=statistics,overview,posts
        Authorization: Bearer <ADMIN_JWT>
        ```

        - 預設會清除 `statistics`, `statistics:*` 標籤。
        - 可透過 `tags` query 參數指定其他標籤（逗號分隔）。

        ### ❤️ 健康檢查

        ```http
        GET /api/admin/statistics/health
        Authorization: Bearer <ADMIN_JWT>
        ```

        **回應欄位重點：**

        - `cache.status` / `cache.hit_rate`：快取狀態與命中率
        - `database.status` / `database.slow_query_count`：資料庫連線與慢查詢指標
        - `snapshots.latest`：各統計快照最新時間戳
        - `warnings`：若超出告警閾值會列出對應訊息

        > 📌 建議將此端點接入監控系統（如 Prometheus、Grafana）以自動化追蹤統計模組健康度。

        ---
            "total": 1,
            "total_pages": 1,
            "has_more": false
        }
    }
}
````

### 📊 活動統計分析

```http
GET /api/v1/activity-logs/stats?user_id=123&period=7d
```

**回應範例:**

```json
{
  "success": true,
  "data": {
    "period": "7d",
    "total_activities": 1250,
    "success_rate": 98.4,
    "categories": {
      "authentication": 125,
      "content": 800,
      "file_management": 250,
      "security": 75
    },
    "daily_trend": [
      { "date": "2024-12-21", "count": 150 },
      { "date": "2024-12-22", "count": 180 },
      { "date": "2024-12-23", "count": 200 }
    ],
    "top_activities": [
      { "type": "post.viewed", "count": 400 },
      { "type": "attachment.downloaded", "count": 200 }
    ]
  }
}
```

### 🚨 可疑活動檢測

```http
POST /api/v1/activity-logs/analyze-suspicious
Content-Type: application/json

{
    "user_id": 123,
    "time_window_minutes": 60,
    "include_patterns": ["frequency", "failure_rate", "ip_behavior"]
}
```

**回應範例:**

```json
{
  "success": true,
  "data": {
    "is_suspicious": true,
    "risk_score": 85,
    "analysis_time": "2024-12-27T10:30:00Z",
    "detected_patterns": [
      {
        "type": "high_failure_rate",
        "description": "登入失敗率異常 (60% 在過去 1 小時)",
        "risk_score": 75,
        "details": {
          "failure_rate": 0.6,
          "threshold": 0.3,
          "failed_attempts": 12,
          "total_attempts": 20
        }
      },
      {
        "type": "unusual_activity_frequency",
        "description": "活動頻率異常高",
        "risk_score": 65,
        "details": {
          "current_rate": "5 actions/minute",
          "normal_rate": "1 action/minute",
          "deviation": "400%"
        }
      }
    ],
    "recommendations": [
      "考慮暫時限制該使用者的操作",
      "增強身份驗證要求",
      "監控後續活動模式"
    ]
  }
}
```

### 📋 支援的活動類型

| 類型                           | 類別            | 描述      |
| ------------------------------ | --------------- | --------- |
| `auth.login.success`           | authentication  | 登入成功  |
| `auth.login.failed`            | authentication  | 登入失敗  |
| `auth.logout`                  | authentication  | 登出      |
| `auth.password.changed`        | authentication  | 密碼變更  |
| `post.created`                 | content         | 文章建立  |
| `post.updated`                 | content         | 文章更新  |
| `post.deleted`                 | content         | 文章刪除  |
| `post.viewed`                  | content         | 文章檢視  |
| `attachment.uploaded`          | file_management | 附件上傳  |
| `attachment.downloaded`        | file_management | 附件下載  |
| `attachment.deleted`           | file_management | 附件刪除  |
| `security.access_denied`       | security        | 存取被拒  |
| `security.ip_blocked`          | security        | IP 被封鎖 |
| `security.suspicious_activity` | security        | 可疑活動  |

### ⚠️ 錯誤處理

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "action_type": ["活動類型必須是有效的預定義類型之一"]
  },
  "error_code": 422
}
```

**常見錯誤代碼:**

- `400` - 請求格式錯誤
- `401` - 未認證
- `403` - 權限不足
- `422` - 驗證失敗
- `429` - 請求過於頻繁
- `500` - 伺服器內部錯誤

---

## API 文件產生

### 🚀 使用統一腳本產生 API 文件

AlleyNote 整合 Swagger/OpenAPI 規格，可自動產生完整的 API 文件：

```bash
# 產生 Swagger API 文件
docker compose exec web php scripts/unified-scripts.php swagger:generate

# 驗證 Swagger 設定
docker compose exec web php scripts/unified-scripts.php swagger:test

# 產生並開啟文件預覽
docker compose exec web php scripts/unified-scripts.php swagger:serve
```

### 文件存取

產生的 API 文件可透過以下方式存取：

- **JSON 格式**: `http://localhost/api-docs.json`
- **YAML 格式**: `http://localhost/api-docs.yaml`
- **Swagger UI**: `http://localhost/docs` (如果有啟用)

### 自動化整合

API 文件會在以下情況自動更新：

- CI/CD 流程執行時
- 執行完整測試套件時
- 手動執行文件產生指令時

### API 規格資訊

- **OpenAPI 版本**: 3.0.3
- **支援格式**: JSON, YAML
- **包含內容**:
  - 完整端點清單
  - 請求/回應範例
  - 資料模型定義
  - 認證機制說明
  - 錯誤碼對照表

---

## 錯誤代碼

### 通用錯誤代碼

| 代碼                   | 說明           | HTTP 狀態 |
| ---------------------- | -------------- | --------- |
| `VALIDATION_FAILED`    | 資料驗證失敗   | 400       |
| `UNAUTHORIZED`         | 未授權存取     | 401       |
| `FORBIDDEN`            | 權限不足       | 403       |
| `NOT_FOUND`            | 資源不存在     | 404       |
| `METHOD_NOT_ALLOWED`   | 方法不允許     | 405       |
| `CONFLICT`             | 資源衝突       | 409       |
| `UNPROCESSABLE_ENTITY` | 無法處理的實體 | 422       |
| `TOO_MANY_REQUESTS`    | 請求過於頻繁   | 429       |
| `INTERNAL_ERROR`       | 伺服器內部錯誤 | 500       |

### 業務邏輯錯誤代碼

| 代碼                      | 說明             | HTTP 狀態 |
| ------------------------- | ---------------- | --------- |
| `INVALID_CREDENTIALS`     | 登入憑證無效     | 401       |
| `ACCOUNT_LOCKED`          | 帳號被鎖定       | 423       |
| `EMAIL_ALREADY_EXISTS`    | 電子郵件已存在   | 409       |
| `USERNAME_ALREADY_EXISTS` | 使用者名稱已存在 | 409       |
| `POST_NOT_FOUND`          | 文章不存在       | 404       |
| `ATTACHMENT_NOT_FOUND`    | 附件不存在       | 404       |
| `FILE_TOO_LARGE`          | 檔案過大         | 413       |
| `INVALID_FILE_TYPE`       | 檔案類型無效     | 415       |
| `IP_BLOCKED`              | IP 被封鎖        | 403       |

---

## 速率限制

AlleyNote API 實施速率限制以防止濫用：

### 限制規則

| 端點類型 | 限制        | 範圍       |
| -------- | ----------- | ---------- |
| 認證相關 | 10 次/分鐘  | 每個 IP    |
| 文章操作 | 60 次/分鐘  | 每個使用者 |
| 檔案上傳 | 5 次/分鐘   | 每個使用者 |
| 一般 API | 120 次/分鐘 | 每個使用者 |

### 速率限制標頭

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642234567
```

### 超過限制回應

```json
{
  "success": false,
  "message": "請求過於頻繁，請稍後再試",
  "error": "TOO_MANY_REQUESTS",
  "retry_after": 60
}
```

---

## 安全性考量

### CSRF 防護

所有修改性操作 (POST, PUT, DELETE) 都需要有效的 CSRF Token：

```http
X-CSRF-TOKEN: abc123def456
```

### XSS 防護

- 所有輸出都經過適當編碼
- Content-Security-Policy 標頭設定
- 輸入驗證與清理

### SQL 注入防護

- 使用參數化查詢
- 輸入驗證
- 最小權限原則

### 檔案上傳安全

- 檔案類型白名單
- 檔案大小限制
- 檔案內容掃描
- 隔離儲存

---

## 版本資訊

### v2.0

**新增功能:**

- 🔍 新驗證系統（29 種驗證規則）
- 🏗️ DTO 資料傳輸物件
- 🧪 100% 測試通過率
- ⚡ 效能優化與快取

**改進項目:**

- 📝 統一錯誤訊息格式
- 🔒 增強安全性防護
- 📊 詳細的驗證錯誤回應
- 🌏 繁體中文錯誤訊息

**重大變更:**

- 驗證錯誤回應格式更新
- 新增更多驗證規則
- CSRF Token 成為必需

### v1.0

**初始版本:**

- 基本文章 CRUD 操作
- 使用者認證系統
- 附件上傳功能
- IP 黑白名單

---

## 開發資源

### 相關文件

- **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)**: 開發者指南
- **[VALIDATOR_GUIDE.md](VALIDATOR_GUIDE.md)**: 驗證器使用指南
- **[DI_CONTAINER_GUIDE.md](DI_CONTAINER_GUIDE.md)**: DI 容器指南

### 工具與測試

- **API 測試**: `tests/Integration/` 目錄
- **Postman Collection**: 可從 `/api/docs/postman` 下載
- **OpenAPI Spec**: `/api/docs/openapi.yaml`

### 社群資源

- **GitHub**: [https://github.com/cookeyholder/AlleyNote](https://github.com/cookeyholder/AlleyNote)
- **Issues**: [GitHub Issues](https://github.com/cookeyholder/AlleyNote/issues)
- **Wiki**: [專案 Wiki](https://github.com/cookeyholder/AlleyNote/wiki)

---

## 聯絡支援

如有 API 相關問題，請聯絡：

- **Bug 回報**: [GitHub Issues](https://github.com/cookeyholder/AlleyNote/issues)
- **功能請求**: [GitHub Discussions](https://github.com/cookeyholder/AlleyNote/discussions)

---

_API 版本: v2.0_
_文件版本: v2.0_
_維護者: AlleyNote 開發團隊_
