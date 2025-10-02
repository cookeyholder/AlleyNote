import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';
import { tokenManager } from '../../utils/tokenManager.js';

/**
 * 認證 API
 */
export const authAPI = {
  /**
   * 登入
   */
  async login(credentials) {
    const response = await apiClient.post(API_ENDPOINTS.AUTH.LOGIN, credentials);
    
    // 儲存 Token
    if (response.data && response.data.token) {
      tokenManager.setToken(response.data.token, response.data.expires_in);
    }
    
    return response.data;
  },

  /**
   * 登出
   */
  async logout() {
    try {
      await apiClient.post(API_ENDPOINTS.AUTH.LOGOUT);
    } finally {
      tokenManager.removeToken();
    }
  },

  /**
   * 取得當前使用者資訊
   */
  async me() {
    const response = await apiClient.get(API_ENDPOINTS.AUTH.ME);
    return response.data;
  },

  /**
   * 檢查是否已登入
   */
  isAuthenticated() {
    return tokenManager.isValid();
  },
};
