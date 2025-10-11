# API 版本控制策略

**版本**: 1.0.0  
**最後更新**: 2025-10-11

---

## 📖 目錄

1. [概述](#概述)
2. [版本策略](#版本策略)
3. [當前版本](#當前版本)
4. [版本遷移指南](#版本遷移指南)
5. [廢棄政策](#廢棄政策)
6. [變更記錄](#變更記錄)

---

## 概述

AlleyNote API 採用 URL 路徑版本控制策略，確保向後相容性並提供平穩的升級路徑。

### 版本控制原則

1. **URL 路徑版本控制**: 版本號包含在 URL 路徑中
2. **語義化版本**: 遵循 [Semantic Versioning](https://semver.org/) 原則
3. **向後相容**: 次版本更新保持向後相容
4. **廢棄通知**: 提前至少 6 個月通知廢棄
5. **文件完整**: 每個版本都有完整的文件

---

## 版本策略

### URL 格式

```
https://api.alleynote.com/api/v{major}/resource
```

**範例**:
- `https://api.alleynote.com/api/v1/users`
- `https://api.alleynote.com/api/v2/users` (未來版本)

### 當前狀態

目前 API 處於過渡期，同時支援以下格式：

| 格式 | 範例 | 狀態 | 說明 |
|-----|------|------|------|
| 有版本號 | `/api/v1/users` | ✅ 建議使用 | 明確指定版本 |
| 無版本號 | `/api/users` | ⚠️ 過渡期 | 預設對應到 v1 |

**重要通知**: 
- 無版本號的端點將在 **2026 年 1 月 1 日** 後廢棄
- 請盡快遷移到有版本號的端點
- 新開發的應用請直接使用有版本號的端點

### 版本命名規則

遵循 `v{major}` 格式：

- `v1`: 第一個主要版本
- `v2`: 第二個主要版本（包含破壞性變更）
- `v1.1`: 不使用（次版本在同一 v1 下保持相容）

---

## 當前版本

### Version 1 (v1)

**發布日期**: 2025-01-01  
**狀態**: 穩定（Stable）  
**支援期限**: 至少到 2026-12-31

#### 支援的端點格式

✅ **建議使用** (有版本號):
```
POST   /api/v1/auth/login
GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{id}
PUT    /api/v1/users/{id}
DELETE /api/v1/users/{id}
GET    /api/v1/roles
GET    /api/v1/permissions
GET    /api/v1/settings
```

⚠️ **過渡期支援** (無版本號，將於 2026-01-01 廢棄):
```
POST   /api/auth/login
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
```

#### 核心功能

- ✅ JWT 認證
- ✅ 使用者管理 (CRUD)
- ✅ 角色與權限管理
- ✅ 文章管理
- ✅ 標籤管理
- ✅ 系統設定
- ✅ 附件上傳
- ✅ 統計數據
- ✅ 活動日誌

---

## 版本遷移指南

### 從無版本號遷移到 v1

#### 步驟 1: 識別現有端點

檢查您的程式碼中所有 API 呼叫：

```javascript
// 舊的方式（無版本號）
const response = await fetch('https://api.alleynote.com/api/users');
```

#### 步驟 2: 更新端點 URL

在所有 `/api/` 後面加入 `/v1`：

```javascript
// 新的方式（有版本號）
const response = await fetch('https://api.alleynote.com/api/v1/users');
```

#### 步驟 3: 測試

確保所有功能正常運作：

```javascript
// 測試腳本範例
const endpoints = [
  '/api/v1/users',
  '/api/v1/roles',
  '/api/v1/permissions',
  '/api/v1/settings'
];

for (const endpoint of endpoints) {
  const response = await fetch(`https://api.alleynote.com${endpoint}`);
  console.log(`${endpoint}: ${response.status}`);
}
```

#### 步驟 4: 更新環境變數

集中管理 API 基礎 URL：

```javascript
// config.js
const config = {
  apiBaseUrl: 'https://api.alleynote.com/api/v1',
  // 或使用環境變數
  apiBaseUrl: process.env.API_BASE_URL || 'https://api.alleynote.com/api/v1'
};

// 使用
const response = await fetch(`${config.apiBaseUrl}/users`);
```

### 自動化遷移

#### 使用 Shell 腳本批次替換

```bash
#!/bin/bash
# replace-api-urls.sh

# 遞迴搜尋並替換所有檔案中的 API URL
find ./src -type f -name "*.js" -o -name "*.ts" -o -name "*.jsx" -o -name "*.tsx" | \
  xargs sed -i '' 's|/api/\([^v]\)|/api/v1/\1|g'

echo "API URLs updated to v1"
```

#### 使用正則表達式

```javascript
// 在編輯器中搜尋
/api/([^v])

// 替換為
/api/v1/$1
```

---

## 廢棄政策

### 廢棄流程

1. **通知階段** (T-6個月)
   - 在文件中標記為已廢棄
   - API 回應中加入 `X-API-Deprecated` 標頭
   - 發送電子郵件通知

2. **警告階段** (T-3個月)
   - 在 API 回應中加入 `X-API-Deprecated-Date` 標頭
   - 記錄警告訊息
   - 在開發者控制台顯示警告

3. **移除階段** (T-0)
   - 停止支援舊端點
   - 回傳 410 Gone 狀態碼
   - 提供遷移指引

### 廢棄通知範例

#### 回應標頭

```http
X-API-Deprecated: true
X-API-Deprecated-Date: 2026-01-01
X-API-Deprecated-Alternative: /api/v1/users
Warning: 299 - "This endpoint is deprecated and will be removed on 2026-01-01. Please use /api/v1/users instead."
```

#### 回應訊息

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "deprecation": {
      "deprecated": true,
      "removal_date": "2026-01-01",
      "alternative": "/api/v1/users",
      "message": "此端點將於 2026-01-01 停止支援，請改用 /api/v1/users"
    }
  }
}
```

### 當前廢棄時間表

| 端點模式 | 廢棄日期 | 移除日期 | 替代方案 |
|---------|---------|---------|---------|
| `/api/users` | 2025-07-01 | 2026-01-01 | `/api/v1/users` |
| `/api/roles` | 2025-07-01 | 2026-01-01 | `/api/v1/roles` |
| `/api/permissions` | 2025-07-01 | 2026-01-01 | `/api/v1/permissions` |
| `/api/settings` | 2025-07-01 | 2026-01-01 | `/api/v1/settings` |
| `/api/posts` | 2025-07-01 | 2026-01-01 | `/api/v1/posts` |
| `/api/tags` | 2025-07-01 | 2026-01-01 | `/api/v1/tags` |

---

## 變更記錄

### v1.0.0 (2025-01-01)

**初始發布**

#### 新增
- ✨ 使用者管理 API
- ✨ 角色與權限管理 API
- ✨ 文章管理 API
- ✨ 標籤管理 API
- ✨ 系統設定 API
- ✨ JWT 認證機制
- ✨ 附件上傳功能
- ✨ 統計數據 API
- ✨ 活動日誌 API

#### 技術細節
- OpenAPI 3.0 規格
- JWT Token 認證
- 使用率限制
- 完整的錯誤處理
- 繁體中文文件

---

### v1.1.0 (預計 2025-03-01)

**計劃中的次要更新**

#### 計劃新增
- 🔄 批次操作 API
- 🔄 匯出/匯入功能
- 🔄 進階搜尋功能
- 🔄 WebSocket 支援（即時通知）
- 🔄 OAuth2 認證支援

#### 向後相容
- ✅ 所有 v1.0.0 的端點保持不變
- ✅ 只新增功能，不修改現有行為

---

### v2.0.0 (預計 2026-01-01)

**下一個主要版本**

#### 計劃變更（破壞性）
- 🔧 統一回應格式
- 🔧 簡化錯誤碼系統
- 🔧 優化分頁機制
- 🔧 改進查詢參數命名
- 🔧 GraphQL API 支援

#### 廢棄
- ❌ 無版本號端點將被完全移除
- ❌ 部分舊的查詢參數格式

---

## 最佳實踐

### 1. 明確指定版本

**建議**:
```javascript
const API_VERSION = 'v1';
const baseUrl = `https://api.alleynote.com/api/${API_VERSION}`;
```

**不建議**:
```javascript
const baseUrl = 'https://api.alleynote.com/api'; // 沒有版本號
```

### 2. 使用環境變數

```javascript
// .env
API_BASE_URL=https://api.alleynote.com/api/v1

// config.js
const apiBaseUrl = process.env.API_BASE_URL;
```

### 3. 建立 API 客戶端類別

```javascript
class AlleyNoteAPI {
  constructor(version = 'v1') {
    this.baseUrl = `https://api.alleynote.com/api/${version}`;
    this.token = null;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(url, {
      ...options,
      headers
    });

    // 檢查廢棄警告
    if (response.headers.has('X-API-Deprecated')) {
      console.warn(
        'API Deprecation Warning:',
        response.headers.get('Warning')
      );
    }

    return response.json();
  }

  async getUsers(params = {}) {
    return this.request('/users', { params });
  }
}

// 使用
const api = new AlleyNoteAPI('v1');
const users = await api.getUsers({ page: 1, per_page: 10 });
```

### 4. 監控廢棄警告

```javascript
async function fetchWithDeprecationCheck(url, options) {
  const response = await fetch(url, options);
  
  if (response.headers.has('X-API-Deprecated')) {
    const alternative = response.headers.get('X-API-Deprecated-Alternative');
    const removalDate = response.headers.get('X-API-Deprecated-Date');
    
    // 記錄到監控系統
    logger.warn('API Deprecation', {
      url,
      alternative,
      removalDate
    });
    
    // 發送通知
    sendDeprecationAlert({
      endpoint: url,
      alternative,
      removalDate
    });
  }
  
  return response;
}
```

---

## 常見問題

### Q: 我應該使用哪個版本？

**A**: 目前請使用 `v1`。新開發的應用請直接使用 `/api/v1/` 格式的端點。

### Q: 舊的無版本號端點還能用嗎？

**A**: 可以，但只支援到 2026-01-01。強烈建議盡快遷移。

### Q: 如何知道我使用的端點是否被廢棄？

**A**: 檢查 API 回應的 `X-API-Deprecated` 標頭，或查看本文件的廢棄時間表。

### Q: v1 和 v2 可以同時使用嗎？

**A**: 可以。您可以在過渡期同時使用兩個版本，逐步遷移。

### Q: 次版本更新需要修改程式碼嗎？

**A**: 不需要。次版本更新（如 v1.0 到 v1.1）保持向後相容，只新增功能不修改現有行為。

---

## 相關資源

- [API 使用指南](./API_USAGE_GUIDE.md)
- [開發者指南](./DEVELOPER_GUIDE.md)
- [變更日誌](../CHANGELOG.md)

---

**最後更新**: 2025-10-11  
**維護者**: AlleyNote 開發團隊
