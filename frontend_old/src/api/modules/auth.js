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
    
    // 儲存 Token - 支援多種回應格式
    if (response && response.data) {
      const data = response.data;
      
      // 檢查 access_token (JWT 格式)
      if (data.access_token) {
        tokenManager.setToken(data.access_token, data.expires_in || 3600);
        
        // 儲存 refresh token
        if (data.refresh_token) {
          localStorage.setItem('alleynote_refresh_token', data.refresh_token);
        }
        
        // 同時儲存到 alleynote_user 中，以確保前端可以取得 token
        const userData = JSON.parse(localStorage.getItem('alleynote_user') || '{}');
        userData.access_token = data.access_token;
        localStorage.setItem('alleynote_user', JSON.stringify(userData));
      }
      // 檢查 token (舊格式)
      else if (data.token) {
        tokenManager.setToken(data.token, data.expires_in || 3600);
        
        // 同時儲存到 alleynote_user 中
        const userData = JSON.parse(localStorage.getItem('alleynote_user') || '{}');
        userData.access_token = data.token;
        localStorage.setItem('alleynote_user', JSON.stringify(userData));
      }
      
      return data;
    } else if (response && response.access_token) {
      // 直接在 response 層級
      tokenManager.setToken(response.access_token, response.expires_in || 3600);
      
      // 同時儲存到 alleynote_user 中
      const userData = JSON.parse(localStorage.getItem('alleynote_user') || '{}');
      userData.access_token = response.access_token;
      localStorage.setItem('alleynote_user', JSON.stringify(userData));
      
      return response;
    }
    
    return response;
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
