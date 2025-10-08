/**
 * API 配置
 */
export const API_CONFIG = {
  // 基礎 URL
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  
  // 請求超時時間（毫秒）
  timeout: parseInt(import.meta.env.VITE_API_TIMEOUT) || 30000,
  
  // 請求標頭
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  
  // 是否攜帶憑證（Cookie）
  withCredentials: true,
  
  // JWT 配置
  jwt: {
    storageKey: import.meta.env.VITE_JWT_STORAGE_KEY || 'alleynote_token',
    refreshThreshold: parseInt(import.meta.env.VITE_JWT_REFRESH_THRESHOLD) || 300,
  },
  
  // CSRF 配置
  csrf: {
    headerName: import.meta.env.VITE_CSRF_HEADER_NAME || 'X-CSRF-TOKEN',
    cookieName: import.meta.env.VITE_CSRF_COOKIE_NAME || 'csrf_token',
  },
  
  // 功能開關
  features: {
    enableMock: import.meta.env.VITE_ENABLE_API_MOCK === 'true',
    enableLogger: import.meta.env.VITE_ENABLE_API_LOGGER === 'true',
  },
};

/**
 * API 端點定義
 */
export const API_ENDPOINTS = {
  // 認證
  AUTH: {
    LOGIN: '/auth/login',
    LOGOUT: '/auth/logout',
    REFRESH: '/auth/refresh',
    ME: '/auth/me',
  },
  
  // 文章
  POSTS: {
    LIST: '/posts',
    DETAIL: (id) => `/posts/${id}`,
    CREATE: '/posts',
    UPDATE: (id) => `/posts/${id}`,
    DELETE: (id) => `/posts/${id}`,
    PUBLISH: (id) => `/posts/${id}/publish`,
    DRAFT: (id) => `/posts/${id}/draft`,
  },
  
  // 附件
  ATTACHMENTS: {
    UPLOAD: '/attachments/upload',
    DELETE: (id) => `/attachments/${id}`,
  },
  
  // 使用者
  USERS: {
    LIST: '/users',
    DETAIL: (id) => `/users/${id}`,
    CREATE: '/users',
    UPDATE: (id) => `/users/${id}`,
    DELETE: (id) => `/users/${id}`,
  },
  
  // 統計
  STATISTICS: {
    OVERVIEW: '/statistics/overview',
    POSTS: '/statistics/posts',
    VIEWS: '/statistics/views',
  },
};
