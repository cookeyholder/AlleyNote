import { API_CONFIG } from '../api/config.js';

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
