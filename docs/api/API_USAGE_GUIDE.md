# AlleyNote API 使用指南

**版本**: 1.0.0
**最後更新**: 2025-10-11

---

## 📖 目錄

1. [簡介](#簡介)
2. [快速開始](#快速開始)
3. [認證授權](#認證授權)
4. [API 端點總覽](#api-端點總覽)
5. [Users Management API](#users-management-api)
6. [Roles Management API](#roles-management-api)
7. [Permissions Management API](#permissions-management-api)
8. [Settings Management API](#settings-management-api)
9. [錯誤處理](#錯誤處理)
10. [最佳實踐](#最佳實踐)

---

## 簡介

AlleyNote API 是一個基於 RESTful 架構的論壇系統 API，提供完整的使用者管理、角色權限控制、內容管理等功能。

### 特色

- ✅ 符合 RESTful API 設計原則
- ✅ 完整的 OpenAPI 3.0 規格文件
- ✅ JWT Token 認證機制
- ✅ 詳細的錯誤訊息
- ✅ 支援分頁查詢
- ✅ 豐富的篩選和搜尋功能

### API 端點

- **基礎 URL**: `http://localhost:8081` (DevContainer 本機模式)
- **Production-like URL**: `http://localhost:8080`
- **API 前綴**: `/api`
- **API 文件**: `/api/docs`
- **Swagger UI**: `/api/docs/ui`

> 建議先設定主機位址（雙模式）：
>
> ```bash
> # DevContainer 本機模式
> export API_HOST=http://localhost:8081
>
> # Production-like 覆寫模式
> # export API_HOST=http://localhost:8080
> ```

---

## 快速開始

### 1. 檢查 API 健康狀態

```bash
curl $API_HOST/api/health
```

**回應範例**:

```json
{
  "status": "ok",
  "timestamp": "2025-10-11T08:00:00+00:00",
  "service": "AlleyNote API"
}
```

### 2. 查看 API 資訊

```bash
curl $API_HOST/api
```

### 3. 訪問 Swagger UI

在瀏覽器中開啟：

```
$API_HOST/api/docs/ui
```

---

## 認證授權

### JWT Token 認證

大多數 API 端點需要 JWT Token 認證。

#### 1. 取得 Token

```bash
curl -X POST $API_HOST/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "Example#Pass123!"
  }'
```

**回應範例**:

```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### 2. 使用 Token

在後續請求中，將 Token 加入 Authorization Header：

```bash
curl $API_HOST/api/users \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## API 端點總覽

### 核心功能 API

| 分類           | 端點數 | 說明                                |
| -------------- | ------ | ----------------------------------- |
| **認證授權**   | 5      | 登入、登出、註冊、Token 刷新        |
| **使用者管理** | 9      | 使用者 CRUD、角色分配、帳號狀態管理 |
| **角色管理**   | 6      | 角色 CRUD、權限管理                 |
| **權限管理**   | 3      | 權限查詢、分組查詢                  |
| **設定管理**   | 5      | 系統設定 CRUD、時區管理             |
| **文章管理**   | 5      | 文章 CRUD、發布控制                 |
| **標籤管理**   | 5      | 標籤 CRUD                           |
| **附件管理**   | 2      | 檔案上傳、下載                      |
| **統計數據**   | 3      | 文章統計、使用者統計                |
| **活動日誌**   | 2      | 日誌查詢                            |

---

## Users Management API

### 概述

使用者管理 API 提供完整的使用者生命週期管理功能。

### 端點列表

| 功能           | 方法   | 端點                                   | 認證     |
| -------------- | ------ | -------------------------------------- | -------- |
| 取得使用者列表 | GET    | `/api/users`                           | ✅       |
| 取得單一使用者 | GET    | `/api/users/{id}`                      | ✅       |
| 建立使用者     | POST   | `/api/users`                           | ✅       |
| 更新使用者     | PUT    | `/api/users/{id}`                      | ✅       |
| 刪除使用者     | DELETE | `/api/users/{id}`                      | ✅       |
| 分配角色       | PUT    | `/api/users/{id}/roles`                | ✅       |
| 啟用使用者     | POST   | `/api/admin/users/{id}/activate`       | ✅ Admin |
| 停用使用者     | POST   | `/api/admin/users/{id}/deactivate`     | ✅ Admin |
| 重設密碼       | POST   | `/api/admin/users/{id}/reset-password` | ✅ Admin |

### 使用範例

#### 1. 取得使用者列表（支援分頁和搜尋）

```bash
curl -X GET "$API_HOST/api/users?page=1&per_page=10&search=john" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**回應**:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "johndoe",
      "email": "john@example.com",
      "status": "active",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 100,
    "page": 1,
    "per_page": 10,
    "last_page": 10
  }
}
```

#### 2. 建立新使用者

```bash
curl -X POST $API_HOST/api/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "Example#Pass123!"
  }'
```

**回應**:

```json
{
  "success": true,
  "message": "使用者建立成功",
  "data": {
    "id": 101,
    "username": "newuser",
    "email": "newuser@example.com"
  }
}
```

#### 3. 分配角色給使用者

```bash
curl -X PUT $API_HOST/api/users/101/roles \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "role_ids": [1, 2, 3]
  }'
```

#### 4. 管理員操作：停用使用者

```bash
curl -X POST $API_HOST/api/admin/users/101/deactivate \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

---

## Roles Management API

### 概述

角色管理 API 提供完整的 RBAC（Role-Based Access Control）角色管理功能。

### 端點列表

| 功能         | 方法   | 端點                          | 認證 |
| ------------ | ------ | ----------------------------- | ---- |
| 取得角色列表 | GET    | `/api/roles`                  | ✅   |
| 取得單一角色 | GET    | `/api/roles/{id}`             | ✅   |
| 建立角色     | POST   | `/api/roles`                  | ✅   |
| 更新角色     | PUT    | `/api/roles/{id}`             | ✅   |
| 刪除角色     | DELETE | `/api/roles/{id}`             | ✅   |
| 更新角色權限 | PUT    | `/api/roles/{id}/permissions` | ✅   |

### 使用範例

#### 1. 取得所有角色

```bash
curl -X GET $API_HOST/api/roles \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**回應**:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "admin",
      "display_name": "管理員",
      "description": "系統管理員角色",
      "created_at": "2025-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "name": "editor",
      "display_name": "編輯者",
      "description": "可以編輯內容的使用者",
      "created_at": "2025-01-01T00:00:00Z"
    }
  ]
}
```

#### 2. 建立新角色

```bash
curl -X POST $API_HOST/api/roles \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "moderator",
    "display_name": "版主",
    "description": "論壇版主角色",
    "permission_ids": [1, 2, 5, 8]
  }'
```

#### 3. 更新角色權限

```bash
curl -X PUT $API_HOST/api/roles/3/permissions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [1, 2, 3, 5, 8, 10]
  }'
```

#### 4. 取得角色詳細資訊（包含權限）

```bash
curl -X GET $API_HOST/api/roles/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**回應**:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "admin",
    "display_name": "管理員",
    "description": "系統管理員角色",
    "permissions": [
      {
        "id": 1,
        "name": "posts.create",
        "display_name": "建立文章"
      },
      {
        "id": 2,
        "name": "posts.update",
        "display_name": "更新文章"
      }
    ]
  }
}
```

---

## Permissions Management API

### 概述

權限管理 API 提供系統權限的查詢功能。

### 端點列表

| 功能         | 方法 | 端點                       | 認證 |
| ------------ | ---- | -------------------------- | ---- |
| 取得所有權限 | GET  | `/api/permissions`         | ✅   |
| 取得單一權限 | GET  | `/api/permissions/{id}`    | ✅   |
| 取得分組權限 | GET  | `/api/permissions/grouped` | ✅   |

### 使用範例

#### 1. 取得所有權限

```bash
curl -X GET $API_HOST/api/permissions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**回應**:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "posts.create",
      "display_name": "建立文章",
      "resource": "posts",
      "action": "create",
      "description": "允許建立新文章"
    },
    {
      "id": 2,
      "name": "posts.update",
      "display_name": "更新文章",
      "resource": "posts",
      "action": "update"
    }
  ]
}
```

#### 2. 取得分組權限（按資源分類）

```bash
curl -X GET $API_HOST/api/permissions/grouped \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**回應**:

```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 1,
        "name": "posts.create",
        "display_name": "建立文章"
      },
      {
        "id": 2,
        "name": "posts.update",
        "display_name": "更新文章"
      }
    ],
    "users": [
      {
        "id": 10,
        "name": "users.create",
        "display_name": "建立使用者"
      }
    ]
  }
}
```

---

## Settings Management API

### 概述

設定管理 API 提供系統設定的管理功能。

### 端點列表

| 功能         | 方法 | 端點                          | 認證 |
| ------------ | ---- | ----------------------------- | ---- |
| 取得所有設定 | GET  | `/api/settings`               | ❌   |
| 取得單一設定 | GET  | `/api/settings/{key}`         | ❌   |
| 批量更新設定 | PUT  | `/api/settings`               | ✅   |
| 更新單一設定 | PUT  | `/api/settings/{key}`         | ✅   |
| 取得時區資訊 | GET  | `/api/settings/timezone/info` | ❌   |

### 使用範例

#### 1. 取得所有系統設定

```bash
curl -X GET $API_HOST/api/settings
```

**回應**:

```json
{
  "success": true,
  "data": {
    "site_name": "AlleyNote",
    "site_timezone": "Asia/Taipei",
    "maintenance_mode": "false",
    "posts_per_page": "10"
  }
}
```

#### 2. 批量更新設定

```bash
curl -X PUT $API_HOST/api/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "site_name": "My Forum",
    "site_timezone": "Asia/Tokyo",
    "posts_per_page": "20"
  }'
```

#### 3. 更新單一設定

```bash
curl -X PUT $API_HOST/api/settings/site_name \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "value": "New Site Name"
  }'
```

#### 4. 取得時區資訊

```bash
curl -X GET $API_HOST/api/settings/timezone/info
```

**回應**:

```json
{
  "success": true,
  "data": {
    "timezone": "Asia/Taipei",
    "offset": "+08:00",
    "current_time": "2025-10-11T16:00:00+08:00",
    "common_timezones": [
      "Asia/Taipei",
      "Asia/Tokyo",
      "America/New_York",
      "Europe/London"
    ]
  }
}
```

---

## 錯誤處理

### 標準錯誤格式

所有錯誤回應都遵循統一的格式：

```json
{
  "success": false,
  "message": "錯誤訊息",
  "errors": {
    "field_name": ["驗證錯誤訊息"]
  }
}
```

### 常見 HTTP 狀態碼

| 狀態碼 | 說明         | 處理建議                   |
| ------ | ------------ | -------------------------- |
| 200    | 成功         | -                          |
| 201    | 建立成功     | -                          |
| 400    | 請求格式錯誤 | 檢查請求資料格式           |
| 401    | 未授權       | 檢查 Token 是否有效        |
| 403    | 禁止訪問     | 檢查權限設定               |
| 404    | 資源不存在   | 確認資源 ID 是否正確       |
| 422    | 資料驗證失敗 | 檢查 errors 欄位的詳細訊息 |
| 500    | 伺服器錯誤   | 聯繫系統管理員             |

### 錯誤處理範例

#### 資料驗證失敗（422）

```json
{
  "success": false,
  "message": "資料驗證失敗",
  "errors": {
    "username": ["使用者名稱已存在"],
    "email": ["電子郵件格式不正確"],
    "password": ["密碼長度不足8個字元"]
  }
}
```

#### 資源不存在（404）

```json
{
  "success": false,
  "message": "使用者不存在"
}
```

---

## 最佳實踐

### 1. 使用 Token 認證

- 將 Token 存儲在安全的地方（不要存在 localStorage）
- Token 過期時及時刷新
- 不要在 URL 中傳遞 Token

### 2. 錯誤處理

- 始終檢查 `success` 欄位
- 根據 HTTP 狀態碼進行適當的錯誤處理
- 向使用者顯示友好的錯誤訊息

### 3. 分頁查詢

- 使用 `page` 和 `per_page` 參數控制分頁
- 不要一次請求過多資料
- 快取分頁結果以提升效能

### 4. 搜尋和篩選

- 使用適當的查詢參數
- 避免過於複雜的查詢條件
- 考慮使用防抖（debounce）減少請求頻率

### 5. API 版本控制

- 當前版本：v1
- 未來版本變更時會在端點路徑中體現（如 `/api/v2/users`）
- 舊版本會在一段時間內保持支援

---

## 相關資源

- **Swagger UI**: $API_HOST/api/docs/ui
- **OpenAPI JSON**: $API_HOST/api/docs
- **健康檢查**: $API_HOST/api/health
- **驗證報告**: [OPENAPI_VERIFICATION_REPORT.md](./OPENAPI_VERIFICATION_REPORT.md)
- **開發者指南**: [DEVELOPER_GUIDE.md](./DEVELOPER_GUIDE.md)

---

## 更新日誌

### 2025-10-11

- ✅ 新增 Users Management API 文件
- ✅ 新增 Roles Management API 文件
- ✅ 新增 Permissions Management API 文件
- ✅ 新增 Settings Management API 文件
- ✅ 完整的 OpenAPI 3.0 註解
- ✅ 完整的使用範例

---

## 支援與回饋

如有問題或建議，請透過以下方式聯繫：

- 建立 GitHub Issue
- 發送郵件至開發團隊
- 查閱系統文件

---

**AlleyNote API** - 強大、靈活、易用的論壇系統 API
