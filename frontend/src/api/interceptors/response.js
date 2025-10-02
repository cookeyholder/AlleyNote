import { tokenManager } from '../../utils/tokenManager.js';
import { API_CONFIG } from '../config.js';
import { APIError } from '../errors.js';

/**
 * 回應攔截器 - 統一處理回應格式
 */
export function responseInterceptor(response) {
  if (API_CONFIG.features.enableLogger) {
    console.log('[API Response]', {
      status: response.status,
      url: response.config.url,
      data: response.data,
    });
  }

  // 檢查 Token 是否需要刷新
  if (tokenManager.shouldRefresh()) {
    console.warn('[API] Token is about to expire, should refresh');
  }

  return response.data;
}

/**
 * 回應錯誤攔截器 - 統一錯誤處理
 */
export function responseErrorInterceptor(error) {
  if (API_CONFIG.features.enableLogger) {
    console.error('[API Response Error]', error);
  }

  // 沒有回應（網路錯誤）
  if (!error.response) {
    return Promise.reject(
      new APIError('NETWORK_ERROR', '網路連線失敗，請檢查您的網路連線', 0)
    );
  }

  const { status, data } = error.response;

  // 401 未授權
  if (status === 401) {
    tokenManager.removeToken();
    if (!window.location.pathname.includes('/login')) {
      window.location.href = '/login';
    }
    return Promise.reject(new APIError('UNAUTHORIZED', '登入已過期，請重新登入', status));
  }

  // 403 禁止訪問
  if (status === 403) {
    return Promise.reject(new APIError('FORBIDDEN', '您沒有權限執行此操作', status));
  }

  // 404 找不到資源
  if (status === 404) {
    return Promise.reject(new APIError('NOT_FOUND', '請求的資源不存在', status));
  }

  // 422 驗證錯誤
  if (status === 422) {
    const validationErrors = data.errors || {};
    return Promise.reject(
      new APIError('VALIDATION_ERROR', '資料驗證失敗', status, validationErrors)
    );
  }

  // 429 請求過於頻繁
  if (status === 429) {
    return Promise.reject(new APIError('RATE_LIMIT', '請求過於頻繁，請稍後再試', status));
  }

  // 500+ 伺服器錯誤
  if (status >= 500) {
    return Promise.reject(new APIError('SERVER_ERROR', '伺服器錯誤，請稍後再試', status));
  }

  // 其他錯誤
  const message = data.message || '發生未知錯誤';
  return Promise.reject(new APIError('UNKNOWN_ERROR', message, status));
}
