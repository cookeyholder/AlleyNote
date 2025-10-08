import { API_CONFIG } from '../api/config.js';

/**
 * CSRF Token 管理器
 */
class CSRFManager {
  constructor() {
    this.cookieName = API_CONFIG.csrf.cookieName;
  }

  /**
   * 從 Cookie 取得 CSRF Token
   */
  getToken() {
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === this.cookieName) {
        return decodeURIComponent(value);
      }
    }
    return null;
  }

  /**
   * 檢查是否有 CSRF Token
   */
  hasToken() {
    return this.getToken() !== null;
  }
}

export const csrfManager = new CSRFManager();
