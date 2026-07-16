# API Modules 模式

## 目錄結構

API 層分為兩層：核心客戶端與領域模組。

```
frontend/js/api/
├── client.js     # ApiClient 核心類別，封裝 fetch
├── config.js     # API 設定（baseURL, timeout, headers）
└── modules/
    ├── auth.js       # 認證 API
    ├── posts.js      # 文章 API
    ├── roles.js      # 角色管理 API
    ├── statistics.js # 統計資料 API
    ├── tags.js       # 標籤管理 API
    └── users.js      # 使用者管理 API
```

## ApiClient 核心

`api/client.js` 定義 `ApiClient` 類別，所有模組共用同一個實例 `apiClient`。

### 設定

```js
// config.js
export const API_CONFIG = {
  baseURL: "/api",          // 同源代理
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  withCredentials: true,    // 使用 HttpOnly Cookie
};
```

### HTTP 方法

```js
apiClient.get(url, options)
apiClient.post(url, body, options)
apiClient.put(url, body, options)
apiClient.patch(url, body, options)
apiClient.delete(url, options)
```

### 認證流程

1. 請求發送前，自動附加 `Authorization: Bearer {token}`（從 localStorage 讀取）
2. 瀏覽器端自動附加 session cookie（`withCredentials: true`）
3. 若收到 401，自動嘗試 `POST /auth/refresh` 刷新 Token
4. 刷新成功 → 更新本地 token → 重新發送原始請求
5. 刷新失敗 → 清除 token → 觸發 `auth:logout` 事件 → 導向登入頁

### CSRF 保護

- `POST/PUT/PATCH/DELETE` 自動附加 `X-CSRF-TOKEN` header
- Token 來源：Cookie `csrf_token` 或 `<meta name="csrf-token">`
- 若收到 403 + `CSRF_INVALID`，自動重新取得 CSRF token 後重試

### 錯誤處理

`ApiError` 類別：

```js
class ApiError extends Error {
  constructor(message, status, data = null) {
    super(message);
    this.status = status;   // HTTP 狀態碼
    this.data = data;       // API 回傳的 JSON
  }
}
```

錯誤處理流程：

```
fetch 失敗
  ├── HTTP error (4xx/5xx)
  │   ├── 401 → 嘗試 refresh → 成功: 重試 / 失敗: auth:logout
  │   ├── 403 + CSRF_INVALID → 重取 CSRF token → 重試
  │   └── 其他 → notification.error(message)
  ├── Network failure (TypeError) → "網路連線失敗"
  ├── Timeout (AbortError) → "請求逾時"
  └── 未知錯誤 → "發生未知錯誤"
```

選項 `silent: true` 可抑制錯誤通知，用於背景請求或非關鍵請求。

## CRUD 範本

每個 API Module 遵循一致的 CRUD 模式。以 `posts.js` 為例：

```js
class PostsAPI {
  // Read - 列表
  async list(params = {}) {
    return await apiClient.get("/posts", { params });
  }

  // Read - 單筆
  async get(id) {
    return await apiClient.get(`/posts/${id}`);
  }

  // Create
  async create(data) {
    return await apiClient.post("/posts", data);
  }

  // Update
  async update(id, data) {
    return await apiClient.put(`/posts/${id}`, data);
  }

  // Delete
  async delete(id) {
    return await apiClient.delete(`/posts/${id}`);
  }
}

export const postsAPI = new PostsAPI();
```

使用方式：

```js
import { postsAPI } from "../api/modules/posts.js";

// 列表
const { data, meta } = await postsAPI.list({ page: 2, status: "published" });

// 單筆
const { data: post } = await postsAPI.get(42);

// 新增
const { data: newPost } = await postsAPI.create({
  title: "新公告", content: "<p>內容</p>", status: "draft",
});

// 更新
await postsAPI.update(42, { title: "修改標題" });

// 刪除
await postsAPI.delete(42);
```

## 各模組一覽

### auth.js — `AuthAPI`

| 方法 | 端點 | 說明 |
|------|------|------|
| `login(credentials)` | `POST /auth/login` | 登入，自動儲存 token + 載入使用者 |
| `logout()` | `POST /auth/logout` | 登出，清除本地狀態（即使 API 失敗） |
| `me()` | `GET /auth/me` | 取得當前使用者資訊 |
| `forgotPassword(email)` | `POST /auth/forgot-password` | 忘記密碼 |
| `resetPassword(token, pw, confirm)` | `POST /auth/reset-password` | 重設密碼 |
| `changePassword(current, new, confirm)` | `POST /auth/change-password` | 變更密碼 |
| `updateProfile(data)` | `PUT /auth/profile` | 更新個人資料 |
| `refresh()` | `POST /auth/refresh` | 手動刷新 Token |

### posts.js — `PostsAPI`

| 方法 | 端點 | 說明 |
|------|------|------|
| `list(params)` | `GET /posts` | 文章列表（支援 page, status, search 等） |
| `get(id)` | `GET /posts/:id` | 單筆文章 |
| `create(data)` | `POST /posts` | 建立文章 |
| `update(id, data)` | `PUT /posts/:id` | 更新文章 |
| `delete(id)` | `DELETE /posts/:id` | 刪除文章 |
| `pin(id)` | `PATCH /posts/:id/pin` | 置頂文章 |
| `unpin(id)` | `PATCH /posts/:id/pin` | 取消置頂 |
| `recordView(id)` | `POST /posts/:id/view` | 記錄瀏覽 |
| `getAttachments(postId)` | `GET /posts/:id/attachments` | 附件列表 |
| `uploadAttachment(postId, file)` | `POST /posts/:id/attachments` | 上傳附件（FormData） |
| `deleteAttachment(id)` | `DELETE /attachments/:id` | 刪除附件 |

## 認證流程圖

```
頁面操作
  ↓
API Module 呼叫 (e.g., postsAPI.list())
  ↓
apiClient.request()
  ├── 附加 Authorization header
  ├── 附加 X-CSRF-TOKEN（寫入操作）
  ↓
fetch() 發送請求
  ↓
handleResponse()
  ├── JSON response → 回傳 data
  └── HTTP error → handleError()
       ├── 401 → refresh token → 成功重試 / 失敗登出
       ├── 403 CSRF → 重取 CSRF token → 重試
       └── 其他 → 顯示錯誤通知 → throw
```
