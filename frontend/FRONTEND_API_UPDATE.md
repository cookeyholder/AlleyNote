# 前端 API 更新記錄

**日期**: 2025-10-11
**版本**: v1.0.1

---

## 📋 更新總結

前端已更新為使用最新的 API v1 端點，確保與後端 API 規範一致。

> 連線埠對照（補充）：
>
> - DevContainer 本機模式：`API_HOST=http://localhost:8081`
> - Production-like 覆寫模式：`API_HOST=http://localhost:8080`
> - 本文件中的 URL 以 `$API_HOST` 表示，依模式切換為 `8081/8080`

### 主要變更

1. **API 基礎 URL 更新**
   - 舊: `$API_HOST/api`
   - 新: `$API_HOST/api/v1`

2. **統一 API 版本控制**
   - 所有 API 呼叫現在都經過 `/api/v1` 前綴
   - 符合後端 API 版本控制策略

3. **移除重複路徑**
   - 修正了 `baseURL` 已包含 `/api/v1` 但程式碼中又重複加入的問題

---

## 🔄 修改的檔案

### 1. `js/api/config.js`

**變更內容**:

```javascript
// 舊的配置
const API_BASE_URL = "$API_HOST/api";

// 新的配置
const API_VERSION = "v1";
const API_BASE_URL = `${API_HOST}/api/${API_VERSION}`;
```

**影響範圍**: 所有 API 呼叫的基礎 URL

---

### 2. `js/api/modules/users.js`

**變更內容**:

```javascript
// 舊的呼叫方式（錯誤 - 重複路徑）
async getRoles() {
  return await apiClient.get('/api/v1/roles');
}

// 新的呼叫方式（正確）
async getRoles() {
  return await apiClient.get('/roles');
}
```

**修正的方法**:

- `getRoles()`
- `createRole()`
- `updateRole()`
- `deleteRole()`
- `getRolePermissions()`
- `updateRolePermissions()`

---

### 3. `js/api/users.js` (舊版相容性檔案)

**變更內容**: 同上，修正了角色相關的 API 路徑

---

## 📊 當前 API 端點對應

### 認證 API

| 前端路徑         | 完整 URL                         | 狀態 |
| ---------------- | -------------------------------- | ---- |
| `/auth/login`    | `$API_HOST/api/v1/auth/login`    | ✅   |
| `/auth/logout`   | `$API_HOST/api/v1/auth/logout`   | ✅   |
| `/auth/me`       | `$API_HOST/api/v1/auth/me`       | ✅   |
| `/auth/register` | `$API_HOST/api/v1/auth/register` | ✅   |
| `/auth/refresh`  | `$API_HOST/api/v1/auth/refresh`  | ✅   |

### 使用者 API

| 前端路徑                     | 完整 URL                                     | 狀態          |
| ---------------------------- | -------------------------------------------- | ------------- |
| `/users`                     | `$API_HOST/api/v1/users`                     | ⚠️ 待後端實作 |
| `/users/{id}`                | `$API_HOST/api/v1/users/{id}`                | ⚠️ 待後端實作 |
| `/admin/users/{id}/activate` | `$API_HOST/api/v1/admin/users/{id}/activate` | ⚠️ 待後端實作 |

### 角色 API

| 前端路徑                  | 完整 URL                                  | 狀態 |
| ------------------------- | ----------------------------------------- | ---- |
| `/roles`                  | `$API_HOST/api/v1/roles`                  | ✅   |
| `/roles/{id}`             | `$API_HOST/api/v1/roles/{id}`             | ✅   |
| `/roles/{id}/permissions` | `$API_HOST/api/v1/roles/{id}/permissions` | ✅   |

### 文章 API

| 前端路徑          | 完整 URL                          | 狀態 |
| ----------------- | --------------------------------- | ---- |
| `/posts`          | `$API_HOST/api/v1/posts`          | ✅   |
| `/posts/{id}`     | `$API_HOST/api/v1/posts/{id}`     | ✅   |
| `/posts/{id}/pin` | `$API_HOST/api/v1/posts/{id}/pin` | ✅   |

### 統計 API

| 前端路徑               | 完整 URL                               | 狀態 |
| ---------------------- | -------------------------------------- | ---- |
| `/statistics/overview` | `$API_HOST/api/v1/statistics/overview` | ✅   |
| `/statistics/posts`    | `$API_HOST/api/v1/statistics/posts`    | ✅   |
| `/statistics/users`    | `$API_HOST/api/v1/statistics/users`    | ✅   |

---

## ✅ 驗證步驟

### 1. 開發環境測試

```bash
# 確認 Docker 容器運行中
docker compose ps

# 開啟前端頁面
open http://localhost:3000

# 開啟瀏覽器開發者工具 (F12)
# 切換到 Network 標籤
# 執行登入操作
# 確認 API 請求使用正確的 URL 格式
```

### 2. 檢查點

- [ ] API 請求 URL 包含 `/api/v1`
- [ ] 登入功能正常
- [ ] 文章列表載入正常
- [ ] 使用者資訊顯示正常
- [ ] 無 404 錯誤
- [ ] 無 CORS 錯誤

### 3. 預期結果

所有 API 請求應該使用以下格式：

```
$API_HOST/api/v1/{endpoint}
```

例如：

- ✅ `$API_HOST/api/v1/auth/login`
- ✅ `$API_HOST/api/v1/posts`
- ✅ `$API_HOST/api/v1/roles`
- ❌ `$API_HOST/api/auth/login` (舊格式)
- ❌ `$API_HOST/api/api/v1/roles` (重複錯誤)

---

## 🔍 已知問題

### 待後端實作的端點

以下端點在前端已預留介面，但後端尚未完全實作：

1. **使用者管理 API**
   - `GET /api/v1/users` - 取得使用者列表
   - `GET /api/v1/users/{id}` - 取得單一使用者
   - `POST /api/v1/users` - 建立使用者（目前使用 `/auth/register`）
   - `PUT /api/v1/users/{id}` - 更新使用者
   - `DELETE /api/v1/users/{id}` - 刪除使用者

2. **使用者狀態管理**
   - `POST /api/v1/admin/users/{id}/activate` - 啟用使用者
   - `POST /api/v1/admin/users/{id}/deactivate` - 停用使用者
   - `POST /api/v1/admin/users/{id}/reset-password` - 重設密碼

3. **個人資料**
   - `PUT /api/v1/auth/profile` - 更新個人資料
   - `POST /api/v1/auth/change-password` - 變更密碼

**注意**: 這些端點在 OpenAPI 文件中已定義，但可能尚未完全實作或測試。

---

## 📝 遷移注意事項

### 對於開發者

1. **新功能開發**
   - 使用 `js/api/modules/` 下的新版 API 模組
   - 不要使用 `js/api/` 根目錄下的舊版檔案（僅供相容性保留）

2. **API 路徑規則**
   - `baseURL` 已包含 `/api/v1`
   - 端點路徑不需要再加 `/api/v1` 前綴
   - 直接使用 `/users`, `/posts`, `/roles` 等

3. **錯誤處理**
   - 統一使用後端定義的錯誤碼（見 `docs/api/ERROR_CODES.md`）
   - 處理 429 Too Many Requests（使用率限制）
   - 處理 401 Unauthorized（Token 過期）

### 範例

```javascript
// ✅ 正確的使用方式
import { apiClient } from "./api/client.js";

// baseURL = '$API_HOST/api/v1'
const users = await apiClient.get("/users");
// 實際請求: $API_HOST/api/v1/users

// ❌ 錯誤的使用方式
const users = await apiClient.get("/api/v1/users");
// 實際請求: $API_HOST/api/v1/api/v1/users (重複!)
```

---

## 🚀 後續工作

### 短期 (1-2 週)

1. [ ] 完整測試所有 API 端點
2. [ ] 實作前端錯誤處理（使用新的錯誤碼系統）
3. [ ] 添加使用率限制提示
4. [ ] 實作 Token 自動刷新機制

### 中期 (1 個月)

1. [ ] 移除舊版 API 檔案（`js/api/*.js`，保留 `config.js` 和 `client.js`）
2. [ ] 統一所有頁面使用新版 API
3. [ ] 添加 API 版本選擇機制（為未來 v2 做準備）
4. [ ] 實作 API 快取策略

### 長期 (3 個月)

1. [ ] 整合 OpenAPI 自動生成的 TypeScript 型別定義
2. [ ] 實作完整的離線支援
3. [ ] 優化 API 請求效能
4. [ ] 添加 GraphQL 支援（可選）

---

## 📚 相關文件

- [API 使用指南](../docs/api/API_USAGE_GUIDE.md)
- [API 錯誤碼說明](../docs/api/ERROR_CODES.md)
- [API 使用率限制](../docs/api/RATE_LIMITS.md)
- [API 版本控制策略](../docs/api/API_VERSIONING.md)
- [OpenAPI 驗證報告](../docs/api/OPENAPI_VERIFICATION_REPORT.md)

---

## 🔗 快速連結

- **Swagger UI**: $API_HOST/api/docs/ui
- **OpenAPI JSON**: $API_HOST/api/docs
- **API 健康檢查**: $API_HOST/api/health
- **前端開發伺服器**: http://localhost:3000

---

**最後更新**: 2025-10-11
**更新者**: GitHub Copilot CLI
**狀態**: ✅ 已完成
