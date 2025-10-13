/**
 * API 客戶端封裝
 * 提供統一的 HTTP 請求介面，支援認證、錯誤處理等功能
 */

import { API_CONFIG } from './config.js';
import { storage } from '../utils/storage.js';
import { toast } from '../utils/toast.js';

/**
 * API 錯誤類別
 */
export class ApiError extends Error {
  constructor(message, status, data = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

/**
 * API 客戶端類別
 */
class ApiClient {
  constructor(config = {}) {
    this.baseURL = config.baseURL || API_CONFIG.baseURL;
    this.timeout = config.timeout || API_CONFIG.timeout;
    this.headers = { ...API_CONFIG.headers, ...config.headers };
    this.withCredentials = config.withCredentials ?? API_CONFIG.withCredentials;
    this._refreshing = false; // Token 刷新中標誌
    this._refreshPromise = null; // Token 刷新 Promise
  }

  /**
   * 取得認證 Token
   */
  getAuthToken() {
    return storage.get('access_token');
  }

  /**
   * 設定認證 Token
   */
  setAuthToken(token) {
    storage.set('access_token', token);
  }

  /**
   * 移除認證 Token
   */
  removeAuthToken() {
    storage.remove('access_token');
  }

  /**
   * 建構請求標頭
   */
  buildHeaders(customHeaders = {}) {
    const headers = { ...this.headers };
    const token = this.getAuthToken();

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    // 合併自訂標頭
    Object.assign(headers, customHeaders);

    return headers;
  }

  /**
   * 處理回應
   */
  async handleResponse(response) {
    const contentType = response.headers.get('content-type');
    
    // 處理 JSON 回應
    if (contentType && contentType.includes('application/json')) {
      const data = await response.json();
      
      if (!response.ok) {
        throw new ApiError(
          data.message || data.error || `HTTP ${response.status}`,
          response.status,
          data
        );
      }
      
      return data;
    }
    
    // 處理其他類型回應
    if (!response.ok) {
      const text = await response.text();
      throw new ApiError(
        text || `HTTP ${response.status}`,
        response.status
      );
    }
    
    return response;
  }

  /**
   * 處理錯誤
   */
  async handleError(error, originalRequest = null) {
    console.error('API Error:', error);

    if (error instanceof ApiError) {
      // 處理認證錯誤 - 嘗試刷新 Token
      if (error.status === 401 && originalRequest && !originalRequest.options?._retry) {
        try {
          // 標記為已重試，避免無限循環
          originalRequest.options = originalRequest.options || {};
          originalRequest.options._retry = true;
          
          // 如果已經在刷新中，等待刷新完成
          if (this._refreshing && this._refreshPromise) {
            await this._refreshPromise;
            // 刷新完成後重新發送請求
            return this.request(originalRequest.url, originalRequest.options);
          }
          
          // 標記為刷新中
          this._refreshing = true;
          
          // 嘗試刷新 Token
          const refreshToken = storage.get('refresh_token');
          if (refreshToken) {
            this._refreshPromise = fetch(`${this.baseURL}/auth/refresh`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ refresh_token: refreshToken }),
            });
            
            const response = await this._refreshPromise;
            
            if (response.ok) {
              const data = await response.json();
              if (data.success && data.access_token) {
                // 更新 Token
                this.setAuthToken(data.access_token);
                if (data.refresh_token) {
                  storage.set('refresh_token', data.refresh_token);
                }
                
                // 重置刷新狀態
                this._refreshing = false;
                this._refreshPromise = null;
                
                // 重新發送原始請求（帶上重試標記）
                return this.request(originalRequest.url, originalRequest.options);
              }
            }
          }
          
          // Token 刷新失敗，重置狀態
          this._refreshing = false;
          this._refreshPromise = null;
        } catch (refreshError) {
          console.error('Token 刷新失敗:', refreshError);
          this._refreshing = false;
          this._refreshPromise = null;
        }
        
        // Token 刷新失敗，執行登出
        this.removeAuthToken();
        storage.remove('refresh_token');
        window.dispatchEvent(new CustomEvent('auth:logout'));
      } else if (error.status === 401) {
        // 已經重試過或沒有原始請求，直接登出
        this.removeAuthToken();
        storage.remove('refresh_token');
        window.dispatchEvent(new CustomEvent('auth:logout'));
      }
      
      // 顯示錯誤訊息（某些情況下不顯示）
      if (!error.silent && error.status === 401) {
        // 401 錯誤不顯示，因為會自動處理登出
      } else if (!error.silent) {
        toast.error(error.message);
      }
      
      throw error;
    }

    // 處理網路錯誤
    if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
      const networkError = new ApiError('網路連線失敗，請檢查您的網路設定', 0);
      toast.error(networkError.message);
      throw networkError;
    }

    // 處理逾時錯誤
    if (error.name === 'AbortError') {
      const timeoutError = new ApiError('請求逾時，請稍後再試', 0);
      toast.error(timeoutError.message);
      throw timeoutError;
    }

    // 其他錯誤
    const unknownError = new ApiError(error.message || '發生未知錯誤', 0);
    toast.error(unknownError.message);
    throw unknownError;
  }

  /**
   * 發送 HTTP 請求
   */
  async request(url, options = {}) {
    const {
      method = 'GET',
      headers = {},
      body,
      params,
      timeout = this.timeout,
      silent = false,
    } = options;

    try {
      // 建構完整 URL
      let fullUrl = `${this.baseURL}${url}`;
      
      console.log('[API Client] 請求 URL:', {
        baseURL: this.baseURL,
        path: url,
        fullUrl: fullUrl,
        method: method
      });
      
      // 處理查詢參數
      if (params) {
        const searchParams = new URLSearchParams();
        Object.keys(params).forEach(key => {
          if (params[key] !== null && params[key] !== undefined) {
            searchParams.append(key, params[key]);
          }
        });
        const queryString = searchParams.toString();
        if (queryString) {
          fullUrl += `?${queryString}`;
        }
      }

      // 建構請求設定
      const fetchOptions = {
        method,
        headers: this.buildHeaders(headers),
        credentials: this.withCredentials ? 'include' : 'same-origin',
      };

      // 處理請求主體
      if (body) {
        if (body instanceof FormData) {
          fetchOptions.body = body;
          // FormData 會自動設定 Content-Type，移除手動設定的
          delete fetchOptions.headers['Content-Type'];
        } else if (typeof body === 'object') {
          fetchOptions.body = JSON.stringify(body);
        } else {
          fetchOptions.body = body;
        }
      }

      // 設定逾時控制
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);
      fetchOptions.signal = controller.signal;

      // 發送請求
      const response = await fetch(fullUrl, fetchOptions);
      clearTimeout(timeoutId);

      // 處理回應
      return await this.handleResponse(response);
    } catch (error) {
      error.silent = silent;
      // 傳遞原始請求資訊以便重試
      return this.handleError(error, { url, options });
    }
  }

  /**
   * GET 請求
   */
  get(url, options = {}) {
    return this.request(url, { ...options, method: 'GET' });
  }

  /**
   * POST 請求
   */
  post(url, body, options = {}) {
    return this.request(url, { ...options, method: 'POST', body });
  }

  /**
   * PUT 請求
   */
  put(url, body, options = {}) {
    return this.request(url, { ...options, method: 'PUT', body });
  }

  /**
   * PATCH 請求
   */
  patch(url, body, options = {}) {
    return this.request(url, { ...options, method: 'PATCH', body });
  }

  /**
   * DELETE 請求
   */
  delete(url, options = {}) {
    return this.request(url, { ...options, method: 'DELETE' });
  }
}

// 建立並匯出預設實例
export const apiClient = new ApiClient();

// 匯出類別供其他模組使用
export default ApiClient;
