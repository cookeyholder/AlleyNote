import { API_CONFIG } from '../api/config.js';

/**
 * JWT Token 管理器
 */
class TokenManager {
  constructor() {
    this.storageKey = API_CONFIG.jwt.storageKey;
    this.refreshThreshold = API_CONFIG.jwt.refreshThreshold;
    this.refreshPromise = null; // 防止重複刷新
  }

  /**
   * 儲存 Token
   */
  setToken(token, expiresIn = 3600) {
    const expiresAt = Date.now() + expiresIn * 1000;
    const tokenData = { token, expiresAt };
    sessionStorage.setItem(this.storageKey, JSON.stringify(tokenData));
  }

  /**
   * 取得 Token
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
    this.refreshPromise = null;
  }

  /**
   * 檢查 Token 是否有效
   */
  isValid() {
    const tokenData = this._getTokenData();
    if (!tokenData) return false;
    return Date.now() < tokenData.expiresAt;
  }

  /**
   * 檢查 Token 是否即將過期
   */
  shouldRefresh() {
    const tokenData = this._getTokenData();
    if (!tokenData) return false;
    const timeRemaining = (tokenData.expiresAt - Date.now()) / 1000;
    return timeRemaining > 0 && timeRemaining < this.refreshThreshold;
  }

  /**
   * 刷新 Token
   */
  async refreshToken() {
    // 如果已經有刷新請求正在進行中，返回同一個 Promise
    if (this.refreshPromise) {
      return this.refreshPromise;
    }

    this.refreshPromise = this._doRefreshToken()
      .then((result) => {
        this.refreshPromise = null;
        return result;
      })
      .catch((error) => {
        this.refreshPromise = null;
        throw error;
      });

    return this.refreshPromise;
  }

  /**
   * 執行 Token 刷新
   * @private
   */
  async _doRefreshToken() {
    try {
      const currentToken = this.getToken();
      if (!currentToken) {
        throw new Error('No token to refresh');
      }

      // 呼叫刷新 API
      const response = await fetch(`${API_CONFIG.baseURL}/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${currentToken}`,
        },
      });

      if (!response.ok) {
        throw new Error('Token refresh failed');
      }

      const data = await response.json();
      
      if (data.success && data.data?.token) {
        this.setToken(data.data.token, data.data.expires_in);
        return data.data.token;
      }

      throw new Error('Invalid refresh response');
    } catch (error) {
      console.error('Failed to refresh token:', error);
      this.removeToken();
      throw error;
    }
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
      console.error('Failed to parse token data:', error);
      return null;
    }
  }
}

export const tokenManager = new TokenManager();
