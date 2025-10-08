/**
 * Google Analytics 整合工具
 * 追蹤使用者行為和事件
 * 
 * @author AlleyNote Team
 * @version 1.0.0
 */

/**
 * Analytics 類別
 * 管理 Google Analytics 整合
 */
class Analytics {
  constructor() {
    this.enabled = false;
    this.initialized = false;
    this.trackingId = import.meta.env.VITE_GA_TRACKING_ID;
    this.debug = import.meta.env.MODE === 'development';
    this.queue = [];
  }

  /**
   * 初始化 Google Analytics
   * @param {Object} options - 配置選項
   */
  init(options = {}) {
    if (!this.trackingId) {
      console.warn('[Analytics] Tracking ID not configured');
      return;
    }

    // 開發環境預設不啟用
    if (this.debug && !options.forceEnable) {
      console.log('[Analytics] Disabled in development mode');
      return;
    }

    try {
      // 載入 gtag.js
      this.loadGtagScript();
      
      // 初始化 gtag
      window.dataLayer = window.dataLayer || [];
      window.gtag = function() {
        window.dataLayer.push(arguments);
      };
      
      // 設定初始配置
      gtag('js', new Date());
      gtag('config', this.trackingId, {
        send_page_view: false, // 手動追蹤頁面瀏覽
        anonymize_ip: true, // 匿名 IP
        cookie_flags: 'SameSite=None;Secure',
        ...options.config
      });

      this.enabled = true;
      this.initialized = true;
      
      console.log('[Analytics] Initialized successfully');
      
      // 處理佇列中的事件
      this.processQueue();
    } catch (error) {
      console.error('[Analytics] Initialization failed:', error);
    }
  }

  /**
   * 載入 gtag.js 腳本
   */
  loadGtagScript() {
    if (document.querySelector('script[src*="googletagmanager"]')) {
      return;
    }

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${this.trackingId}`;
    document.head.appendChild(script);
  }

  /**
   * 處理佇列中的事件
   */
  processQueue() {
    while (this.queue.length > 0) {
      const { method, args } = this.queue.shift();
      this[method](...args);
    }
  }

  /**
   * 加入佇列（未初始化時）
   * @param {string} method - 方法名稱
   * @param {Array} args - 參數
   */
  enqueue(method, args) {
    this.queue.push({ method, args });
  }

  /**
   * 追蹤頁面瀏覽
   * @param {Object} options - 頁面資訊
   */
  trackPageView(options = {}) {
    if (!this.initialized) {
      this.enqueue('trackPageView', [options]);
      return;
    }

    if (!this.enabled) {
      this.logDebug('Page view', options);
      return;
    }

    const { path, title, ...customParams } = options;

    gtag('event', 'page_view', {
      page_path: path || window.location.pathname,
      page_title: title || document.title,
      page_location: window.location.href,
      ...customParams
    });
  }

  /**
   * 追蹤事件
   * @param {string} action - 事件動作
   * @param {Object} params - 事件參數
   */
  trackEvent(action, params = {}) {
    if (!this.initialized) {
      this.enqueue('trackEvent', [action, params]);
      return;
    }

    if (!this.enabled) {
      this.logDebug('Event', { action, ...params });
      return;
    }

    gtag('event', action, params);
  }

  /**
   * 追蹤使用者互動
   * @param {string} category - 類別
   * @param {string} action - 動作
   * @param {string} label - 標籤
   * @param {number} value - 數值
   */
  trackInteraction(category, action, label, value) {
    this.trackEvent(action, {
      event_category: category,
      event_label: label,
      value: value
    });
  }

  /**
   * 追蹤點擊事件
   * @param {string} elementName - 元素名稱
   * @param {Object} data - 額外資料
   */
  trackClick(elementName, data = {}) {
    this.trackEvent('click', {
      element_name: elementName,
      ...data
    });
  }

  /**
   * 追蹤搜尋
   * @param {string} searchTerm - 搜尋詞
   * @param {Object} data - 額外資料
   */
  trackSearch(searchTerm, data = {}) {
    this.trackEvent('search', {
      search_term: searchTerm,
      ...data
    });
  }

  /**
   * 追蹤登入
   * @param {string} method - 登入方法
   */
  trackLogin(method = 'email') {
    this.trackEvent('login', {
      method: method
    });
  }

  /**
   * 追蹤登出
   */
  trackLogout() {
    this.trackEvent('logout');
  }

  /**
   * 追蹤註冊
   * @param {string} method - 註冊方法
   */
  trackSignUp(method = 'email') {
    this.trackEvent('sign_up', {
      method: method
    });
  }

  /**
   * 追蹤表單提交
   * @param {string} formName - 表單名稱
   * @param {Object} data - 額外資料
   */
  trackFormSubmit(formName, data = {}) {
    this.trackEvent('form_submit', {
      form_name: formName,
      ...data
    });
  }

  /**
   * 追蹤檔案下載
   * @param {string} fileName - 檔案名稱
   * @param {string} fileType - 檔案類型
   */
  trackDownload(fileName, fileType) {
    this.trackEvent('file_download', {
      file_name: fileName,
      file_type: fileType
    });
  }

  /**
   * 追蹤影片播放
   * @param {string} videoTitle - 影片標題
   * @param {string} videoUrl - 影片 URL
   */
  trackVideoPlay(videoTitle, videoUrl) {
    this.trackEvent('video_start', {
      video_title: videoTitle,
      video_url: videoUrl
    });
  }

  /**
   * 追蹤錯誤
   * @param {string} description - 錯誤描述
   * @param {boolean} fatal - 是否為致命錯誤
   */
  trackError(description, fatal = false) {
    this.trackEvent('exception', {
      description: description,
      fatal: fatal
    });
  }

  /**
   * 追蹤計時
   * @param {string} category - 類別
   * @param {string} variable - 變數名稱
   * @param {number} value - 時間（毫秒）
   * @param {string} label - 標籤
   */
  trackTiming(category, variable, value, label) {
    this.trackEvent('timing_complete', {
      name: variable,
      value: value,
      event_category: category,
      event_label: label
    });
  }

  /**
   * 設定使用者 ID
   * @param {string} userId - 使用者 ID
   */
  setUserId(userId) {
    if (!this.enabled) return;

    gtag('config', this.trackingId, {
      user_id: userId
    });
  }

  /**
   * 設定使用者屬性
   * @param {Object} properties - 使用者屬性
   */
  setUserProperties(properties) {
    if (!this.enabled) return;

    gtag('set', 'user_properties', properties);
  }

  /**
   * 設定自訂維度
   * @param {Object} dimensions - 自訂維度
   */
  setCustomDimensions(dimensions) {
    if (!this.enabled) return;

    Object.entries(dimensions).forEach(([key, value]) => {
      gtag('set', key, value);
    });
  }

  /**
   * 啟用/停用追蹤
   * @param {boolean} enabled - 是否啟用
   */
  setEnabled(enabled) {
    this.enabled = enabled;
    
    if (!this.initialized) return;

    // Google Analytics 選擇退出
    window[`ga-disable-${this.trackingId}`] = !enabled;
  }

  /**
   * 除錯日誌
   * @param {string} action - 動作
   * @param {Object} data - 資料
   */
  logDebug(action, data) {
    if (this.debug) {
      console.log(`[Analytics] ${action}:`, data);
    }
  }

  /**
   * 取得 Client ID
   * @returns {Promise<string>}
   */
  async getClientId() {
    if (!this.enabled) return null;

    return new Promise((resolve) => {
      gtag('get', this.trackingId, 'client_id', resolve);
    });
  }
}

// 全域實例
let analytics = null;

/**
 * 取得 Analytics 實例
 * @returns {Analytics}
 */
export function getAnalytics() {
  if (!analytics) {
    analytics = new Analytics();
  }
  return analytics;
}

/**
 * 初始化 Analytics
 * @param {Object} options - 配置選項
 * @returns {Analytics}
 */
export function initAnalytics(options = {}) {
  const instance = getAnalytics();
  instance.init(options);
  return instance;
}

// 便捷方法
export const trackPageView = (options) => {
  getAnalytics().trackPageView(options);
};

export const trackEvent = (action, params) => {
  getAnalytics().trackEvent(action, params);
};

export const trackClick = (elementName, data) => {
  getAnalytics().trackClick(elementName, data);
};

export const trackSearch = (searchTerm, data) => {
  getAnalytics().trackSearch(searchTerm, data);
};

export default {
  Analytics,
  getAnalytics,
  initAnalytics,
  trackPageView,
  trackEvent,
  trackClick,
  trackSearch
};
