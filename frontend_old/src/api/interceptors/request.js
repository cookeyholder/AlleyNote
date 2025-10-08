import { tokenManager } from '../../utils/tokenManager.js';
import { csrfManager } from '../../utils/csrfManager.js';
import { API_CONFIG } from '../config.js';

/**
 * 請求攔截器 - 自動加入認證 Token 並處理自動刷新
 */
export async function requestInterceptor(config) {
  // 檢查 Token 是否需要刷新
  if (tokenManager.shouldRefresh()) {
    try {
      await tokenManager.refreshToken();
    } catch (error) {
      console.error('Token refresh failed:', error);
      // Token 刷新失敗，讓請求繼續（可能會導致 401）
    }
  }

  // 加入 JWT Token
  const token = tokenManager.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // 加入 CSRF Token（POST, PUT, PATCH, DELETE）
  const needsCsrf = ['post', 'put', 'patch', 'delete'].includes(
    config.method?.toLowerCase()
  );
  
  if (needsCsrf) {
    const csrfToken = csrfManager.getToken();
    if (csrfToken) {
      config.headers[API_CONFIG.csrf.headerName] = csrfToken;
    }
  }

  // 開發環境記錄請求
  if (API_CONFIG.features.enableLogger) {
    console.log('[API Request]', {
      method: config.method?.toUpperCase(),
      url: config.url,
      data: config.data,
    });
  }

  return config;
}

/**
 * 請求錯誤攔截器
 */
export function requestErrorInterceptor(error) {
  if (API_CONFIG.features.enableLogger) {
    console.error('[API Request Error]', error);
  }
  return Promise.reject(error);
}
