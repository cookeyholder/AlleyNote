# AlleyNote API 文件

**版本**: v2.0  
**基礎 URL**: `https://your-domain.com/api`  
**API 版本**: RESTful API v2.0

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
9. [錯誤代碼](#錯誤代碼)
10. [速率限制](#速率限制)

---

## 概述

AlleyNote API v2.0 提供完整的公布欄網站功能，包含文章管理、使用者認證、附件上傳、IP 控制等功能。

### 新版本特色（v2.0）

- ✅ **強型別驗證**: 29 種內建驗證規則，繁體中文錯誤訊息
- ✅ **統一錯誤格式**: 標準化的 API 錯誤回應
- ✅ **DTO 驗證**: 所有輸入透過 DTO 進行驗證
- ✅ **增強安全性**: CSRF 防護、XSS 過濾、SQL 注入防護
- ✅ **效能優化**: 快取機制、查詢優化

### 支援的格式

- **請求格式**: JSON, Form Data (檔案上傳)
- **回應格式**: JSON
- **編碼**: UTF-8
- **日期格式**: ISO 8601 (YYYY-MM-DDTHH:mm:ssZ)

---

## 認證機制

### 支援的認證方式

1. **Session 認證**: 基於 PHP Session
2. **CSRF Token**: 表單提交需要 CSRF Token
3. **API Key**: 可選支援

### Session 認證

```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "admin",
    "password": "password123"
}
```

### CSRF Token

所有 POST, PUT, DELETE 請求都需要包含 CSRF Token：

```http
POST /api/posts
Content-Type: application/json
X-CSRF-TOKEN: abc123def456

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
        "title": [
            "此欄位為必填",
            "最少需要 5 個字元"
        ],
        "email": [
            "請輸入有效的電子郵件地址"
        ],
        "content": [
            "此欄位為必填"
        ]
    }
}
```

### 支援的驗證規則

| 規則 | 說明 | 錯誤訊息範例 |
|------|------|-------------|
| `required` | 必填欄位 | "此欄位為必填" |
| `email` | 電子郵件格式 | "請輸入有效的電子郵件地址" |
| `min_length:5` | 最少字元數 | "最少需要 5 個字元" |
| `max_length:255` | 最多字元數 | "最多只能 255 個字元" |
| `integer` | 整數型別 | "必須為整數" |
| `unique:table,column` | 唯一性檢查 | "此電子郵件已被使用" |
| `exists:table,column` | 存在性檢查 | "指定的文章不存在" |

---

## 文章 API

### 取得文章列表

```http
GET /api/posts?page=1&limit=20&search=關鍵字&category=announcement
```

**查詢參數:**

| 參數 | 類型 | 必填 | 說明 | 預設值 |
|------|------|------|------|--------|
| `page` | integer | 否 | 頁碼 | 1 |
| `limit` | integer | 否 | 每頁筆數 (1-100) | 20 |
| `search` | string | 否 | 搜尋關鍵字 | - |
| `category` | string | 否 | 分類篩選 | - |
| `status` | string | 否 | 狀態篩選 (published, draft, archived) | - |

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

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `title` | string | 是 | required, string, min_length:5, max_length:255 | 文章標題 |
| `content` | string | 是 | required, string, min_length:10 | 文章內容 |
| `category` | string | 否 | sometimes, string, in:announcement,news,general | 文章分類 |
| `is_pinned` | boolean | 否 | sometimes, boolean | 是否置頂 |

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
        "title": [
            "此欄位為必填",
            "最少需要 5 個字元"
        ],
        "content": [
            "此欄位為必填"
        ]
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

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `title` | string | 否 | sometimes, string, min_length:5, max_length:255 | 文章標題 |
| `content` | string | 否 | sometimes, string, min_length:10 | 文章內容 |
| `category` | string | 否 | sometimes, string, in:announcement,news,general | 文章分類 |
| `is_pinned` | boolean | 否 | sometimes, boolean | 是否置頂 |

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
    "password": "password123",
    "remember_me": false
}
```

**請求欄位:**

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `username` | string | 是 | required, string, min_length:3 | 使用者名稱或電子郵件 |
| `password` | string | 是 | required, string, min_length:6 | 密碼 |
| `remember_me` | boolean | 否 | sometimes, boolean | 記住登入狀態 |

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
    "password": "password123",
    "password_confirmation": "password123"
}
```

**請求欄位:**

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `username` | string | 是 | required, string, min_length:3, max_length:50, unique:users,username | 使用者名稱 |
| `email` | string | 是 | required, email, unique:users,email | 電子郵件 |
| `password` | string | 是 | required, string, min_length:8 | 密碼 |
| `password_confirmation` | string | 是 | required, confirmed | 確認密碼 |

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

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `file` | file | 是 | file_required, file_max_size:10240, file_mime_types:image/*,application/pdf | 上傳檔案 |
| `description` | string | 否 | sometimes, string, max_length:500 | 檔案說明 |

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
        "file": [
            "請選擇檔案",
            "檔案大小不能超過 10MB",
            "只允許 PDF 和圖片檔案"
        ]
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

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| `type` | string | 否 | 規則類型 (blacklist, whitelist) |
| `page` | integer | 否 | 頁碼 |
| `limit` | integer | 否 | 每頁筆數 |

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

| 欄位 | 類型 | 必填 | 驗證規則 | 說明 |
|------|------|------|----------|------|
| `ip_address` | string | 是 | required, ip | IP 位址 |
| `type` | string | 是 | required, in:blacklist,whitelist | 規則類型 |
| `reason` | string | 否 | sometimes, string, max_length:255 | 規則原因 |

---

## 錯誤代碼

### 通用錯誤代碼

| 代碼 | 說明 | HTTP 狀態 |
|------|------|-----------|
| `VALIDATION_FAILED` | 資料驗證失敗 | 400 |
| `UNAUTHORIZED` | 未授權存取 | 401 |
| `FORBIDDEN` | 權限不足 | 403 |
| `NOT_FOUND` | 資源不存在 | 404 |
| `METHOD_NOT_ALLOWED` | 方法不允許 | 405 |
| `CONFLICT` | 資源衝突 | 409 |
| `UNPROCESSABLE_ENTITY` | 無法處理的實體 | 422 |
| `TOO_MANY_REQUESTS` | 請求過於頻繁 | 429 |
| `INTERNAL_ERROR` | 伺服器內部錯誤 | 500 |

### 業務邏輯錯誤代碼

| 代碼 | 說明 | HTTP 狀態 |
|------|------|-----------|
| `INVALID_CREDENTIALS` | 登入憑證無效 | 401 |
| `ACCOUNT_LOCKED` | 帳號被鎖定 | 423 |
| `EMAIL_ALREADY_EXISTS` | 電子郵件已存在 | 409 |
| `USERNAME_ALREADY_EXISTS` | 使用者名稱已存在 | 409 |
| `POST_NOT_FOUND` | 文章不存在 | 404 |
| `ATTACHMENT_NOT_FOUND` | 附件不存在 | 404 |
| `FILE_TOO_LARGE` | 檔案過大 | 413 |
| `INVALID_FILE_TYPE` | 檔案類型無效 | 415 |
| `IP_BLOCKED` | IP 被封鎖 | 403 |

---

## 速率限制

AlleyNote API 實施速率限制以防止濫用：

### 限制規則

| 端點類型 | 限制 | 範圍 |
|----------|------|------|
| 認證相關 | 10 次/分鐘 | 每個 IP |
| 文章操作 | 60 次/分鐘 | 每個使用者 |
| 檔案上傳 | 5 次/分鐘 | 每個使用者 |
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

- **GitHub**: [https://github.com/your-org/alleynote](https://github.com/your-org/alleynote)
- **Issues**: [GitHub Issues](https://github.com/your-org/alleynote/issues)
- **Wiki**: [專案 Wiki](https://github.com/your-org/alleynote/wiki)

---

## 聯絡支援

如有 API 相關問題，請聯絡：

- **Bug 回報**: [GitHub Issues](https://github.com/your-org/alleynote/issues)
- **功能請求**: [GitHub Discussions](https://github.com/your-org/alleynote/discussions)

---

*API 版本: v2.0*  
*文件版本: v2.0*  
*維護者: AlleyNote 開發團隊*