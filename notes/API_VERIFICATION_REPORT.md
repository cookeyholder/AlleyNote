# API 端點驗證報告

**日期**: 2025-10-11
**測試環境**: Docker ($API_HOST)

> 埠對照補充：
>
> - DevContainer 本機模式：`http://localhost:8081`
> - Production-like：`http://localhost:8080`
> - 本報告中的連線資訊屬於 historical / production-like 驗證語境

## 測試摘要

已驗證 AlleyNote API 的所有主要端點。以下是發現的問題和確認有效的端點。

## ✅ 有效的 API 端點

### 公開端點（無需認證）

| 方法 | 路徑                 | 說明            | 狀態碼  |
| ---- | -------------------- | --------------- | ------- |
| GET  | `/api/health`        | 健康檢查        | 200     |
| GET  | `/api/docs`          | API 文件 (JSON) | 200     |
| GET  | `/api/docs/ui`       | API 文件 (UI)   | 200     |
| GET  | `/api/posts`         | 文章列表        | 200     |
| GET  | `/api/posts/{id}`    | 文章詳情        | 200/404 |
| POST | `/api/auth/login`    | 使用者登入      | 200/400 |
| POST | `/api/auth/register` | 使用者註冊      | 200/400 |
| POST | `/api/auth/refresh`  | 刷新 Token      | 200/400 |

### 需要認證的端點

| 方法 | 路徑                        | 說明               | 狀態碼 |
| ---- | --------------------------- | ------------------ | ------ |
| GET  | `/api/auth/me`              | 取得當前使用者資訊 | 200    |
| POST | `/api/auth/logout`          | 使用者登出         | 200    |
| PUT  | `/api/auth/profile`         | 更新個人資料       | 200    |
| POST | `/api/auth/change-password` | 變更密碼           | 200    |

### 文章管理（需要認證）

| 方法   | 路徑                        | 說明              | 狀態碼  |
| ------ | --------------------------- | ----------------- | ------- |
| GET    | `/api/posts`                | 文章列表          | 200     |
| GET    | `/api/posts/{id}`           | 文章詳情          | 200     |
| POST   | `/api/posts`                | 建立文章          | 201/422 |
| PUT    | `/api/posts/{id}`           | 更新文章          | 200     |
| DELETE | `/api/posts/{id}`           | 刪除文章          | 204     |
| POST   | `/api/posts/{id}/publish`   | 發布文章          | 200     |
| POST   | `/api/posts/{id}/unpublish` | 取消發布文章      | 200     |
| PATCH  | `/api/posts/{id}/pin`       | 置頂/取消置頂文章 | 200     |

### 使用者管理（需要管理員權限）

| 方法   | 路徑                             | 說明       | 狀態碼 |
| ------ | -------------------------------- | ---------- | ------ |
| GET    | `/api/users`                     | 使用者列表 | 200    |
| GET    | `/api/users/{id}`                | 使用者詳情 | 200    |
| POST   | `/api/users`                     | 建立使用者 | 201    |
| PUT    | `/api/users/{id}`                | 更新使用者 | 200    |
| DELETE | `/api/users/{id}`                | 刪除使用者 | 204    |
| POST   | `/api/users/{id}/activate`       | 啟用使用者 | 200    |
| POST   | `/api/users/{id}/deactivate`     | 停用使用者 | 200    |
| POST   | `/api/users/{id}/reset-password` | 重設密碼   | 200    |

### 角色與權限管理（需要管理員權限）

| 方法   | 路徑                          | 說明         | 狀態碼 |
| ------ | ----------------------------- | ------------ | ------ |
| GET    | `/api/roles`                  | 角色列表     | 200    |
| GET    | `/api/roles/{id}`             | 角色詳情     | 200    |
| POST   | `/api/roles`                  | 建立角色     | 201    |
| PUT    | `/api/roles/{id}`             | 更新角色     | 200    |
| DELETE | `/api/roles/{id}`             | 刪除角色     | 204    |
| PUT    | `/api/roles/{id}/permissions` | 更新角色權限 | 200    |
| GET    | `/api/permissions`            | 權限列表     | 200    |
| GET    | `/api/permissions/{id}`       | 權限詳情     | 200    |
| GET    | `/api/permissions/grouped`    | 分組權限列表 | 200    |

### 系統設定（需要管理員權限）

| 方法 | 路徑                  | 說明         | 狀態碼 |
| ---- | --------------------- | ------------ | ------ |
| GET  | `/api/settings`       | 取得系統設定 | 200    |
| PUT  | `/api/settings`       | 更新系統設定 | 200    |
| GET  | `/api/settings/{key}` | 取得單一設定 | 200    |
| PUT  | `/api/settings/{key}` | 更新單一設定 | 200    |

### 統計功能（需要認證）

| 方法 | 路徑                       | 說明         | 狀態碼 |
| ---- | -------------------------- | ------------ | ------ |
| GET  | `/api/statistics/overview` | 統計概覽     | 200    |
| GET  | `/api/statistics/posts`    | 文章統計     | 200    |
| GET  | `/api/statistics/sources`  | 來源統計     | 200    |
| GET  | `/api/statistics/users`    | 使用者統計   | 200    |
| GET  | `/api/statistics/popular`  | 熱門內容統計 | 200    |
| POST | `/api/posts/{id}/view`     | 記錄文章瀏覽 | 200    |

### 統計管理（需要管理員權限）

| 方法   | 路徑                            | 說明         | 狀態碼 |
| ------ | ------------------------------- | ------------ | ------ |
| POST   | `/api/admin/statistics/refresh` | 手動刷新統計 | 200    |
| DELETE | `/api/admin/statistics/cache`   | 清除統計快取 | 200    |

## ❌ 無效或未實作的端點

### Tags API

**問題**: `/api/tags` 相關端點回傳 404

**路由定義存在但未正常工作**:

- GET `/api/tags` - 標籤列表
- GET `/api/tags/{id}` - 標籤詳情
- POST `/api/tags` - 建立標籤
- PUT `/api/tags/{id}` - 更新標籤
- DELETE `/api/tags/{id}` - 刪除標籤

**原因**: TagController 存在但路由可能未正確註冊

**建議**: 檢查 TagController 和路由配置

### Timezone Info API

**問題**: `/api/timezone-info` 回傳 404

**路由定義**: `GET /api/timezone-info` - 取得時區資訊

**原因**: SettingController 中有 `getTimezoneInfo` 方法，但路由未生效

**建議**: 檢查路由註冊

### Activity Logs API

**問題**: `/api/v1/activity-logs` 相關端點回傳 404

**路由定義存在但路徑不正確**:

- GET `/api/v1/activity-logs` - 查詢活動記錄
- POST `/api/v1/activity-logs` - 記錄活動
- GET `/api/v1/activity-logs/stats` - 活動統計
- GET `/api/v1/activity-logs/me` - 當前使用者活動

**原因**: ActivityLogController 可能未正確整合

**建議**: 實作或移除這些端點

## 🔍 Swagger/OpenAPI 文件問題

### 路徑前綴不一致

Swagger 文件中的路徑缺少 `/api` 前綴：

- 文件中: `/posts/{id}`
- 實際路徑: `/api/posts/{id}`

### 統計 API 路徑錯誤

Swagger 文件中顯示:

- `/api/v1/statistics/*`

實際路徑應該是:

- `/api/statistics/*`

## 📋 建議修正

1. **修正或移除 Tags API**
   - 選項 A: 實作完整的 TagController 功能
   - 選項 B: 從文件和路由中移除

2. **修正 Timezone Info API**
   - 檢查路由註冊問題
   - 確認 SettingController::getTimezoneInfo() 可正常呼叫

3. **修正 Activity Logs API**
   - 完成 ActivityLogController 整合
   - 或從文件中移除未實作的端點

4. **更新 Swagger 文件**
   - 統一路徑前綴（加入 `/api`）
   - 修正統計 API 路徑（移除 `/v1`）
   - 移除無效端點的文件

5. **更新 QUICK_START.md**
   - API 文件路徑改為 `/api/docs/ui`（已確認有效）
   - 列出實際可用的主要端點

## ✅ 測試帳號確認

| 角色   | 電子信箱          | 密碼         |
| ------ | ----------------- | ------------ |
| 管理員 | admin@example.com | Admin@123456 |

**登入回應格式**:

```json
{
  "success": true,
  "message": "登入成功",
  "access_token": "eyJ0eXAiOiJKV1Q...",
  "refresh_token": "eyJ0eXAiOiJKV1Q...",
  "token_type": "Bearer",
  "expires_in": 2592000,
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "name": "admin",
    "role": "super_admin"
  }
}
```

## 🎯 結論

大部分核心 API 端點都正常運作：

- ✅ 認證系統完整有效
- ✅ 文章管理功能完整
- ✅ 使用者管理功能完整
- ✅ 角色權限管理完整
- ✅ 統計功能有效（路徑為 `/api/statistics/`）
- ❌ Tags API 需要修正
- ❌ Timezone Info API 需要修正
- ❌ Activity Logs API 可能未完成實作

建議優先修正文件，將無效端點標註或移除，確保使用者不會嘗試呼叫無效的 API。
