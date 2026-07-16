# AlleyNote 前端 API 整合指南

## 📋 目錄

1. [概述](#概述)
2. [API Client 架構](#api-client-架構)
3. [環境配置](#環境配置)
4. [認證與授權](#認證與授權)
5. [請求攔截器](#請求攔截器)
6. [回應攔截器](#回應攔截器)
7. [錯誤處理](#錯誤處理)
8. [API 模組化](#api-模組化)
9. [使用範例](#使用範例)
10. [最佳實踐](#最佳實踐)

---

## 概述

本文件說明 AlleyNote 前端如何與後端 API 進行整合，包括認證流程、請求/回應處理、錯誤處理等核心機制。

### 技術棧

- **HTTP Client**: Axios 1.6.0+
- **認證方式**: JWT (JSON Web Token)
- **安全機制**: CSRF Token, XSS 防護
- **資料格式**: JSON

### API 基礎資訊

- **基礎 URL**: 由環境變數決定
  - 開發環境（DevContainer）: `$API_HOST/api`（`API_HOST=http://localhost:8081`）
  - Production-like: `$API_HOST/api`（`API_HOST=http://localhost:8080`）
  - 測試環境: `http://test.alleynote.com/api`
  - 生產環境: `https://alleynote.com/api`
- **API 版本**: v4.0
- **編碼**: UTF-8
- **日期格式**: ISO 8601 (YYYY-MM-DDTHH:mm:ssZ)

---

## API Client 架構

### 檔案結構

```
src/api/
├── client.js           # API Client 核心（Axios 實例）
├── interceptors/       # 攔截器
│   ├── request.js      # 請求攔截器
│   └── response.js     # 回應攔截器
├── modules/            # API 模組（按功能分類）
│   ├── auth.js         # 認證相關 API
│   ├── posts.js        # 文章相關 API
│   ├── attachments.js  # 附件相關 API
│   ├── users.js        # 使用者相關 API
│   └── statistics.js   # 統計相關 API
├── config.js           # API 配置
└── errors.js           # 錯誤定義與處理
```

### 核心設計原則

1. **單一職責**: 每個 API 模組只負責一個領域
2. **可測試性**: 所有 API 函式可獨立測試
3. **錯誤透明**: 統一的錯誤格式與處理
4. **類型安全**: 使用 JSDoc 提供型別提示
5. **可擴展性**: 易於新增新的 API 端點

---

## 環境配置

### 1. 環境變數設定

建立 `.env` 檔案（根目錄）：

```bash
# 雙模式擇一
# API_HOST=http://localhost:8081   # DevContainer
# API_HOST=http://localhost:8080   # Production-like

# API 配置
VITE_API_BASE_URL=$API_HOST/api
VITE_API_TIMEOUT=30000

# 功能開關
VITE_ENABLE_API_MOCK=false
VITE_ENABLE_API_LOGGER=true

# JWT 配置
VITE_JWT_STORAGE_KEY=alleynote_token
VITE_JWT_REFRESH_THRESHOLD=300

# CSRF 配置
VITE_CSRF_HEADER_NAME=X-CSRF-TOKEN
VITE_CSRF_COOKIE_NAME=csrf_token
```

不同環境的配置：

- `.env.development` - 開發環境
- `.env.test` - 測試環境
- `.env.production` - 生產環境

### 2. API 配置檔案

**`src/api/config.js`**

```javascript
/**
 * API 配置
 */
export const API_CONFIG = {
  // 基礎 URL
  baseURL: import.meta.env.VITE_API_BASE_URL || "/api",

  // 請求超時時間（毫秒）
  timeout: parseInt(import.meta.env.VITE_API_TIMEOUT) || 30000,

  // 請求標頭
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },

  // 是否攜帶憑證（Cookie）
  withCredentials: true,

  // JWT 配置
  jwt: {
    storageKey: import.meta.env.VITE_JWT_STORAGE_KEY || "alleynote_token",
    refreshThreshold:
      parseInt(import.meta.env.VITE_JWT_REFRESH_THRESHOLD) || 300, // 5分鐘
  },

  // CSRF 配置
  csrf: {
    headerName: import.meta.env.VITE_CSRF_HEADER_NAME || "X-CSRF-TOKEN",
    cookieName: import.meta.env.VITE_CSRF_COOKIE_NAME || "csrf_token",
  },

  // 功能開關
  features: {
    enableMock: import.meta.env.VITE_ENABLE_API_MOCK === "true",
    enableLogger: import.meta.env.VITE_ENABLE_API_LOGGER === "true",
  },
};

/**
 * API 端點定義
 */
export const API_ENDPOINTS = {
  // 認證
  AUTH: {
    LOGIN: "/auth/login",
    LOGOUT: "/auth/logout",
    REFRESH: "/auth/refresh",
    ME: "/auth/me",
  },

  // 文章
  POSTS: {
    LIST: "/posts",
    DETAIL: (id) => `/posts/${id}`,
    CREATE: "/posts",
    UPDATE: (id) => `/posts/${id}`,
    DELETE: (id) => `/posts/${id}`,
    PUBLISH: (id) => `/posts/${id}/publish`,
    DRAFT: (id) => `/posts/${id}/draft`,
  },

  // 附件
  ATTACHMENTS: {
    UPLOAD: "/attachments/upload",
    DELETE: (id) => `/attachments/${id}`,
  },

  // 使用者
  USERS: {
    LIST: "/users",
    DETAIL: (id) => `/users/${id}`,
    CREATE: "/users",
    UPDATE: (id) => `/users/${id}`,
    DELETE: (id) => `/users/${id}`,
  },

  // 統計
  STATISTICS: {
    OVERVIEW: "/statistics/overview",
    POSTS: "/statistics/posts",
    VIEWS: "/statistics/views",
  },
};
```

---

## 認證與授權

### JWT Token 管理

**`src/utils/tokenManager.js`**

```javascript
import { API_CONFIG } from "../api/config.js";

/**
 * JWT Token 管理器
 */
class TokenManager {
  constructor() {
    this.storageKey = API_CONFIG.jwt.storageKey;
    this.refreshThreshold = API_CONFIG.jwt.refreshThreshold;
  }

  /**
   * 儲存 Token
   * @param {string} token - JWT Token
   * @param {number} expiresIn - 過期時間（秒）
   */
  setToken(token, expiresIn = 3600) {
    const expiresAt = Date.now() + expiresIn * 1000;

    const tokenData = {
      token,
      expiresAt,
    };

    // 使用 SessionStorage（更安全，關閉瀏覽器即清除）
    sessionStorage.setItem(this.storageKey, JSON.stringify(tokenData));
  }

  /**
   * 取得 Token
   * @returns {string|null}
   */
  getToken() {
    const tokenData = this._getTokenData();
    return tokenData ? tokenData.token : null;
  }

  /**
   * 移除 Token
   */
  removeToken() {
    sessionStorage.removeItem(this.storageKey);
  }

  /**
   * 檢查 Token 是否有效
   * @returns {boolean}
   */
  isValid() {
    const tokenData = this._getTokenData();
    if (!tokenData) return false;

    return Date.now() < tokenData.expiresAt;
  }

  /**
   * 檢查 Token 是否即將過期
   * @returns {boolean}
   */
  shouldRefresh() {
    const tokenData = this._getTokenData();
    if (!tokenData) return false;

    const timeRemaining = (tokenData.expiresAt - Date.now()) / 1000;
    return timeRemaining > 0 && timeRemaining < this.refreshThreshold;
  }

  /**
   * 取得 Token 資料
   * @private
   */
  _getTokenData() {
    try {
      const data = sessionStorage.getItem(this.storageKey);
      return data ? JSON.parse(data) : null;
    } catch (error) {
      console.error("Failed to parse token data:", error);
      return null;
    }
  }
}

export const tokenManager = new TokenManager();
```

### CSRF Token 管理

**`src/utils/csrfManager.js`**

```javascript
import { API_CONFIG } from "../api/config.js";

/**
 * CSRF Token 管理器
 */
class CSRFManager {
  constructor() {
    this.cookieName = API_CONFIG.csrf.cookieName;
  }

  /**
   * 從 Cookie 取得 CSRF Token
   * @returns {string|null}
   */
  getToken() {
    const cookies = document.cookie.split(";");

    for (const cookie of cookies) {
      const [name, value] = cookie.trim().split("=");
      if (name === this.cookieName) {
        return decodeURIComponent(value);
      }
    }

    return null;
  }

  /**
   * 檢查是否有 CSRF Token
   * @returns {boolean}
   */
  hasToken() {
    return this.getToken() !== null;
  }
}

export const csrfManager = new CSRFManager();
```

---

## 請求攔截器

**`src/api/interceptors/request.js`**

```javascript
import { tokenManager } from "../../utils/tokenManager.js";
import { csrfManager } from "../../utils/csrfManager.js";
import { API_CONFIG } from "../config.js";

/**
 * 請求攔截器 - 自動加入認證 Token
 */
export function requestInterceptor(config) {
  // 加入 JWT Token
  const token = tokenManager.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // 加入 CSRF Token（POST, PUT, PATCH, DELETE）
  const needsCsrf = ["post", "put", "patch", "delete"].includes(
    config.method?.toLowerCase(),
  );

  if (needsCsrf) {
    const csrfToken = csrfManager.getToken();
    if (csrfToken) {
      config.headers[API_CONFIG.csrf.headerName] = csrfToken;
    }
  }

  // 開發環境記錄請求
  if (API_CONFIG.features.enableLogger) {
    console.log("[API Request]", {
      method: config.method?.toUpperCase(),
      url: config.url,
      data: config.data,
      headers: config.headers,
    });
  }

  return config;
}

/**
 * 請求錯誤攔截器
 */
export function requestErrorInterceptor(error) {
  if (API_CONFIG.features.enableLogger) {
    console.error("[API Request Error]", error);
  }

  return Promise.reject(error);
}
```

---

## 回應攔截器

**`src/api/interceptors/response.js`**

```javascript
import { tokenManager } from "../../utils/tokenManager.js";
import { API_CONFIG } from "../config.js";
import { APIError, handleAPIError } from "../errors.js";

/**
 * 回應攔截器 - 統一處理回應格式
 */
export function responseInterceptor(response) {
  // 開發環境記錄回應
  if (API_CONFIG.features.enableLogger) {
    console.log("[API Response]", {
      status: response.status,
      url: response.config.url,
      data: response.data,
    });
  }

  // 檢查 Token 是否需要刷新
  if (tokenManager.shouldRefresh()) {
    console.warn("[API] Token is about to expire, should refresh");
    // 可以在這裡觸發 Token 刷新邏輯
  }

  // 統一回應格式
  return response.data;
}

/**
 * 回應錯誤攔截器 - 統一錯誤處理
 */
export function responseErrorInterceptor(error) {
  if (API_CONFIG.features.enableLogger) {
    console.error("[API Response Error]", error);
  }

  // 沒有回應（網路錯誤）
  if (!error.response) {
    const networkError = new APIError(
      "NETWORK_ERROR",
      "網路連線失敗，請檢查您的網路連線",
      0,
    );
    return Promise.reject(networkError);
  }

  const { status, data } = error.response;

  // 401 未授權 - Token 過期或無效
  if (status === 401) {
    tokenManager.removeToken();

    // 如果不是登入頁面，導向登入
    if (!window.location.pathname.includes("/login")) {
      window.location.href =
        "/login?redirect=" + encodeURIComponent(window.location.pathname);
    }

    return Promise.reject(
      new APIError("UNAUTHORIZED", "登入已過期，請重新登入", status),
    );
  }

  // 403 禁止訪問
  if (status === 403) {
    return Promise.reject(
      new APIError("FORBIDDEN", "您沒有權限執行此操作", status),
    );
  }

  // 404 找不到資源
  if (status === 404) {
    return Promise.reject(
      new APIError("NOT_FOUND", "請求的資源不存在", status),
    );
  }

  // 422 驗證錯誤
  if (status === 422) {
    const validationErrors = data.errors || {};
    return Promise.reject(
      new APIError(
        "VALIDATION_ERROR",
        "資料驗證失敗",
        status,
        validationErrors,
      ),
    );
  }

  // 429 請求過於頻繁
  if (status === 429) {
    return Promise.reject(
      new APIError("RATE_LIMIT", "請求過於頻繁，請稍後再試", status),
    );
  }

  // 500+ 伺服器錯誤
  if (status >= 500) {
    return Promise.reject(
      new APIError("SERVER_ERROR", "伺服器錯誤，請稍後再試", status),
    );
  }

  // 其他錯誤
  const message = data.message || "發生未知錯誤";
  return Promise.reject(new APIError("UNKNOWN_ERROR", message, status));
}
```

---

## 錯誤處理

**`src/api/errors.js`**

```javascript
/**
 * API 錯誤類別
 */
export class APIError extends Error {
  constructor(code, message, status, details = null) {
    super(message);
    this.name = "APIError";
    this.code = code;
    this.status = status;
    this.details = details;
  }

  /**
   * 是否為驗證錯誤
   */
  isValidationError() {
    return this.code === "VALIDATION_ERROR" && this.details !== null;
  }

  /**
   * 是否為網路錯誤
   */
  isNetworkError() {
    return this.code === "NETWORK_ERROR";
  }

  /**
   * 是否為認證錯誤
   */
  isAuthError() {
    return this.code === "UNAUTHORIZED";
  }

  /**
   * 取得使用者友善的錯誤訊息
   */
  getUserMessage() {
    return this.message;
  }

  /**
   * 取得驗證錯誤欄位
   */
  getValidationErrors() {
    return this.details || {};
  }
}

/**
 * 錯誤代碼對照表
 */
export const ERROR_CODES = {
  // 網路相關
  NETWORK_ERROR: "NETWORK_ERROR",
  TIMEOUT: "TIMEOUT",

  // 認證相關
  UNAUTHORIZED: "UNAUTHORIZED",
  FORBIDDEN: "FORBIDDEN",

  // 資源相關
  NOT_FOUND: "NOT_FOUND",
  CONFLICT: "CONFLICT",

  // 驗證相關
  VALIDATION_ERROR: "VALIDATION_ERROR",

  // 伺服器相關
  SERVER_ERROR: "SERVER_ERROR",
  RATE_LIMIT: "RATE_LIMIT",

  // 其他
  UNKNOWN_ERROR: "UNKNOWN_ERROR",
};

/**
 * 處理 API 錯誤
 * @param {APIError} error
 * @param {Object} options - 處理選項
 */
export function handleAPIError(error, options = {}) {
  const {
    showToast = true,
    logError = true,
    onUnauthorized = null,
    onValidationError = null,
  } = options;

  // 記錄錯誤
  if (logError) {
    console.error("[API Error]", {
      code: error.code,
      message: error.message,
      status: error.status,
      details: error.details,
    });
  }

  // 顯示提示
  if (showToast && window.toast) {
    window.toast.error(error.getUserMessage());
  }

  // 特定錯誤處理
  if (error.isAuthError() && onUnauthorized) {
    onUnauthorized(error);
  }

  if (error.isValidationError() && onValidationError) {
    onValidationError(error.getValidationErrors());
  }

  return error;
}
```

---

## API 模組化

### API Client 核心

**`src/api/client.js`**

```javascript
import axios from "axios";
import { API_CONFIG } from "./config.js";
import {
  requestInterceptor,
  requestErrorInterceptor,
} from "./interceptors/request.js";
import {
  responseInterceptor,
  responseErrorInterceptor,
} from "./interceptors/response.js";

/**
 * 建立 Axios 實例
 */
const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: API_CONFIG.headers,
  withCredentials: API_CONFIG.withCredentials,
});

/**
 * 註冊請求攔截器
 */
apiClient.interceptors.request.use(requestInterceptor, requestErrorInterceptor);

/**
 * 註冊回應攔截器
 */
apiClient.interceptors.response.use(
  responseInterceptor,
  responseErrorInterceptor,
);

export default apiClient;
```

### 認證 API

**`src/api/modules/auth.js`**

```javascript
import apiClient from "../client.js";
import { API_ENDPOINTS } from "../config.js";
import { tokenManager } from "../../utils/tokenManager.js";

/**
 * 認證 API
 */
export const authAPI = {
  /**
   * 登入
   * @param {Object} credentials
   * @param {string} credentials.email - 電子郵件
   * @param {string} credentials.password - 密碼
   * @returns {Promise<Object>}
   */
  async login(credentials) {
    const response = await apiClient.post(
      API_ENDPOINTS.AUTH.LOGIN,
      credentials,
    );

    // 儲存 Token
    if (response.data && response.data.token) {
      tokenManager.setToken(response.data.token, response.data.expires_in);
    }

    return response.data;
  },

  /**
   * 登出
   * @returns {Promise<void>}
   */
  async logout() {
    try {
      await apiClient.post(API_ENDPOINTS.AUTH.LOGOUT);
    } finally {
      // 無論請求成功與否，都清除本地 Token
      tokenManager.removeToken();
    }
  },

  /**
   * 取得當前使用者資訊
   * @returns {Promise<Object>}
   */
  async me() {
    const response = await apiClient.get(API_ENDPOINTS.AUTH.ME);
    return response.data;
  },

  /**
   * 刷新 Token
   * @returns {Promise<Object>}
   */
  async refresh() {
    const response = await apiClient.post(API_ENDPOINTS.AUTH.REFRESH);

    if (response.data && response.data.token) {
      tokenManager.setToken(response.data.token, response.data.expires_in);
    }

    return response.data;
  },

  /**
   * 檢查是否已登入
   * @returns {boolean}
   */
  isAuthenticated() {
    return tokenManager.isValid();
  },
};
```

### 文章 API

**`src/api/modules/posts.js`**

```javascript
import apiClient from "../client.js";
import { API_ENDPOINTS } from "../config.js";

/**
 * 文章 API
 */
export const postsAPI = {
  /**
   * 取得文章列表
   * @param {Object} params - 查詢參數
   * @param {number} params.page - 頁碼
   * @param {number} params.per_page - 每頁筆數
   * @param {string} params.status - 狀態篩選
   * @param {string} params.search - 搜尋關鍵字
   * @returns {Promise<Object>}
   */
  async list(params = {}) {
    const response = await apiClient.get(API_ENDPOINTS.POSTS.LIST, { params });
    return response.data;
  },

  /**
   * 取得單一文章
   * @param {number} id - 文章 ID
   * @returns {Promise<Object>}
   */
  async get(id) {
    const response = await apiClient.get(API_ENDPOINTS.POSTS.DETAIL(id));
    return response.data;
  },

  /**
   * 建立文章
   * @param {Object} data - 文章資料
   * @param {string} data.title - 標題
   * @param {string} data.content - 內容
   * @param {string} data.status - 狀態
   * @returns {Promise<Object>}
   */
  async create(data) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.CREATE, data);
    return response.data;
  },

  /**
   * 更新文章
   * @param {number} id - 文章 ID
   * @param {Object} data - 文章資料
   * @returns {Promise<Object>}
   */
  async update(id, data) {
    const response = await apiClient.put(API_ENDPOINTS.POSTS.UPDATE(id), data);
    return response.data;
  },

  /**
   * 刪除文章
   * @param {number} id - 文章 ID
   * @returns {Promise<void>}
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.POSTS.DELETE(id));
  },

  /**
   * 發布文章
   * @param {number} id - 文章 ID
   * @returns {Promise<Object>}
   */
  async publish(id) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.PUBLISH(id));
    return response.data;
  },

  /**
   * 將文章改為草稿
   * @param {number} id - 文章 ID
   * @returns {Promise<Object>}
   */
  async draft(id) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.DRAFT(id));
    return response.data;
  },
};
```

### 附件 API

**`src/api/modules/attachments.js`**

```javascript
import apiClient from "../client.js";
import { API_ENDPOINTS } from "../config.js";

/**
 * 附件 API
 */
export const attachmentsAPI = {
  /**
   * 上傳檔案
   * @param {File} file - 檔案物件
   * @param {Function} onProgress - 上傳進度回呼
   * @returns {Promise<Object>}
   */
  async upload(file, onProgress = null) {
    const formData = new FormData();
    formData.append("file", file);

    const config = {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    };

    // 如果有進度回呼，加入配置
    if (onProgress) {
      config.onUploadProgress = (progressEvent) => {
        const percentCompleted = Math.round(
          (progressEvent.loaded * 100) / progressEvent.total,
        );
        onProgress(percentCompleted);
      };
    }

    const response = await apiClient.post(
      API_ENDPOINTS.ATTACHMENTS.UPLOAD,
      formData,
      config,
    );

    return response.data;
  },

  /**
   * 刪除檔案
   * @param {number} id - 檔案 ID
   * @returns {Promise<void>}
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.ATTACHMENTS.DELETE(id));
  },
};
```

---

## 使用範例

### 登入流程

```javascript
import { authAPI } from "./api/modules/auth.js";
import { handleAPIError } from "./api/errors.js";

async function handleLogin(email, password) {
  try {
    const result = await authAPI.login({ email, password });

    console.log("登入成功", result.user);

    // 導向後台
    window.location.href = "/admin/dashboard";
  } catch (error) {
    handleAPIError(error, {
      onValidationError: (errors) => {
        // 顯示驗證錯誤
        Object.keys(errors).forEach((field) => {
          showFieldError(field, errors[field][0]);
        });
      },
    });
  }
}
```

### 取得文章列表

```javascript
import { postsAPI } from "./api/modules/posts.js";

async function loadPosts(page = 1) {
  try {
    const result = await postsAPI.list({
      page,
      per_page: 10,
      status: "published",
    });

    renderPosts(result.data);
    renderPagination(result.pagination);
  } catch (error) {
    console.error("載入文章失敗", error);
    showToast("載入文章失敗，請稍後再試", "error");
  }
}
```

### 建立文章

```javascript
import { postsAPI } from "./api/modules/posts.js";
import { handleAPIError } from "./api/errors.js";

async function createPost(formData) {
  try {
    const result = await postsAPI.create({
      title: formData.title,
      content: formData.content,
      status: "draft",
    });

    showToast("文章建立成功", "success");

    // 導向編輯頁
    window.location.href = `/admin/posts/${result.id}/edit`;
  } catch (error) {
    handleAPIError(error, {
      onValidationError: (errors) => {
        displayValidationErrors(errors);
      },
    });
  }
}
```

### 上傳圖片（含進度）

```javascript
import { attachmentsAPI } from "./api/modules/attachments.js";

async function uploadImage(file) {
  const progressBar = document.getElementById("upload-progress");

  try {
    const result = await attachmentsAPI.upload(file, (progress) => {
      // 更新進度條
      progressBar.style.width = `${progress}%`;
      progressBar.textContent = `${progress}%`;
    });

    console.log("上傳成功", result.url);
    return result.url;
  } catch (error) {
    console.error("上傳失敗", error);
    showToast("圖片上傳失敗", "error");
    throw error;
  }
}
```

---

## 最佳實踐

### 1. 使用 async/await

```javascript
// ✅ 好的做法
async function loadData() {
  try {
    const posts = await postsAPI.list();
    const stats = await statisticsAPI.overview();
    return { posts, stats };
  } catch (error) {
    handleError(error);
  }
}

// ❌ 避免的做法
function loadData() {
  postsAPI
    .list()
    .then((posts) => {
      statisticsAPI.overview().then((stats) => {
        // 巢狀 Promise
      });
    })
    .catch((error) => {
      // 錯誤處理
    });
}
```

### 2. 統一錯誤處理

```javascript
// ✅ 好的做法 - 使用統一的錯誤處理器
try {
  await postsAPI.create(data);
} catch (error) {
  handleAPIError(error, {
    onValidationError: displayErrors,
  });
}

// ❌ 避免的做法 - 每個地方都重複處理
try {
  await postsAPI.create(data);
} catch (error) {
  if (error.status === 422) {
    // 重複的驗證錯誤處理
  } else if (error.status === 401) {
    // 重複的認證錯誤處理
  }
}
```

### 3. 善用 JSDoc 提供型別提示

```javascript
/**
 * 建立文章
 * @param {Object} data - 文章資料
 * @param {string} data.title - 標題
 * @param {string} data.content - 內容
 * @param {'draft'|'published'} data.status - 狀態
 * @returns {Promise<{id: number, title: string, created_at: string}>}
 */
async create(data) {
  // ...
}
```

### 4. 請求去重與快取

```javascript
// 避免重複請求
let postsPromise = null;

export async function getPosts() {
  if (postsPromise) {
    return postsPromise;
  }

  postsPromise = postsAPI.list();

  try {
    const result = await postsPromise;
    return result;
  } finally {
    postsPromise = null;
  }
}
```

### 5. 請求取消

```javascript
import axios from "axios";

// 建立可取消的請求
const controller = new AbortController();

async function searchPosts(keyword) {
  try {
    const result = await apiClient.get("/posts", {
      params: { search: keyword },
      signal: controller.signal,
    });
    return result;
  } catch (error) {
    if (axios.isCancel(error)) {
      console.log("請求已取消");
    }
    throw error;
  }
}

// 取消請求
controller.abort();
```

### 6. 錯誤重試機制

```javascript
/**
 * 帶重試的請求
 * @param {Function} requestFn - 請求函式
 * @param {number} maxRetries - 最大重試次數
 */
async function requestWithRetry(requestFn, maxRetries = 3) {
  let lastError;

  for (let i = 0; i < maxRetries; i++) {
    try {
      return await requestFn();
    } catch (error) {
      lastError = error;

      // 只重試網路錯誤或 5xx 錯誤
      if (!error.isNetworkError() && error.status < 500) {
        throw error;
      }

      // 等待後重試（指數退避）
      await new Promise((resolve) =>
        setTimeout(resolve, 1000 * Math.pow(2, i)),
      );
    }
  }

  throw lastError;
}

// 使用範例
const posts = await requestWithRetry(() => postsAPI.list());
```

---

## 總結

本指南提供了完整的 API 整合架構，包括：

- ✅ 統一的 API Client 配置
- ✅ JWT 與 CSRF Token 自動管理
- ✅ 請求/回應攔截器
- ✅ 統一的錯誤處理機制
- ✅ 模組化的 API 組織
- ✅ 豐富的使用範例

遵循本指南的架構，可以確保前端 API 整合的**一致性**、**可維護性**與**可測試性**。
