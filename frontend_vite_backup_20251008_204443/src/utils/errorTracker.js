/**
 * 錯誤追蹤工具
 * 整合 Sentry 進行錯誤監控
 * 
 * @author AlleyNote Team
 * @version 1.0.0
 */

/**
 * ErrorTracker 類別
 * 管理錯誤追蹤和報告
 */
class ErrorTracker {
  constructor() {
    this.enabled = false;
    this.environment = import.meta.env.MODE;
    this.release = import.meta.env.VITE_APP_VERSION || '1.0.0';
    this.sentryDSN = import.meta.env.VITE_SENTRY_DSN;
    this.sampleRate = parseFloat(import.meta.env.VITE_SENTRY_SAMPLE_RATE || '1.0');
    this.breadcrumbs = [];
    this.maxBreadcrumbs = 50;
    this.user = null;
  }

  /**
   * 初始化錯誤追蹤
   * @param {Object} options - 配置選項
   */
  async init(options = {}) {
    // 只在生產環境啟用
    if (this.environment !== 'production' && !options.forceEnable) {
      console.log('[ErrorTracker] Not enabled in development');
      return;
    }

    if (!this.sentryDSN) {
      console.warn('[ErrorTracker] Sentry DSN not configured');
      return;
    }

    try {
      // 動態載入 Sentry SDK
      const Sentry = await import('@sentry/browser');
      
      Sentry.init({
        dsn: this.sentryDSN,
        environment: this.environment,
        release: this.release,
        
        // 取樣率
        tracesSampleRate: this.sampleRate,
        
        // 整合
        integrations: [
          new Sentry.BrowserTracing(),
          new Sentry.Replay({
            maskAllText: true,
            blockAllMedia: true
          })
        ],
        
        // Session Replay
        replaysSessionSampleRate: 0.1, // 10% 的正常 session
        replaysOnErrorSampleRate: 1.0, // 100% 的錯誤 session
        
        // 忽略特定錯誤
        ignoreErrors: [
          // 瀏覽器擴充套件錯誤
          'top.GLOBALS',
          'chrome-extension://',
          'moz-extension://',
          // 網路錯誤
          'NetworkError',
          'Network request failed',
          // 使用者取消
          'AbortError',
          'The user aborted a request'
        ],
        
        // 忽略特定 URL
        denyUrls: [
          /extensions\//i,
          /^chrome:\/\//i,
          /^moz-extension:\/\//i
        ],
        
        // 在發送前處理事件
        beforeSend(event, hint) {
          // 過濾敏感資訊
          if (event.request) {
            delete event.request.cookies;
            if (event.request.headers) {
              delete event.request.headers['Authorization'];
              delete event.request.headers['Cookie'];
            }
          }
          
          return event;
        },
        
        ...options
      });

      this.Sentry = Sentry;
      this.enabled = true;
      
      console.log('[ErrorTracker] Initialized successfully');
      
      // 設定全域錯誤處理
      this.setupGlobalErrorHandlers();
    } catch (error) {
      console.error('[ErrorTracker] Initialization failed:', error);
    }
  }

  /**
   * 設定全域錯誤處理
   */
  setupGlobalErrorHandlers() {
    // 捕獲未處理的 Promise rejection
    window.addEventListener('unhandledrejection', (event) => {
      this.captureException(event.reason, {
        type: 'unhandledRejection',
        promise: event.promise
      });
    });

    // 捕獲全域錯誤
    window.addEventListener('error', (event) => {
      this.captureException(event.error, {
        type: 'globalError',
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      });
    });
  }

  /**
   * 捕獲例外
   * @param {Error} error - 錯誤物件
   * @param {Object} context - 額外上下文
   */
  captureException(error, context = {}) {
    if (!this.enabled || !this.Sentry) {
      console.error('[ErrorTracker] Exception:', error, context);
      return;
    }

    this.Sentry.captureException(error, {
      contexts: {
        custom: context
      }
    });
  }

  /**
   * 捕獲訊息
   * @param {string} message - 訊息內容
   * @param {string} level - 嚴重程度 (info, warning, error)
   * @param {Object} context - 額外上下文
   */
  captureMessage(message, level = 'info', context = {}) {
    if (!this.enabled || !this.Sentry) {
      console.log(`[ErrorTracker] ${level.toUpperCase()}:`, message, context);
      return;
    }

    this.Sentry.captureMessage(message, {
      level,
      contexts: {
        custom: context
      }
    });
  }

  /**
   * 新增麵包屑
   * @param {Object} breadcrumb - 麵包屑物件
   */
  addBreadcrumb(breadcrumb) {
    if (!this.enabled || !this.Sentry) {
      this.breadcrumbs.push({
        timestamp: Date.now(),
        ...breadcrumb
      });
      
      // 限制麵包屑數量
      if (this.breadcrumbs.length > this.maxBreadcrumbs) {
        this.breadcrumbs.shift();
      }
      return;
    }

    this.Sentry.addBreadcrumb(breadcrumb);
  }

  /**
   * 設定使用者資訊
   * @param {Object} user - 使用者資訊
   */
  setUser(user) {
    this.user = user;
    
    if (!this.enabled || !this.Sentry) {
      return;
    }

    this.Sentry.setUser({
      id: user.id,
      email: user.email,
      username: user.name,
      role: user.role
    });
  }

  /**
   * 清除使用者資訊
   */
  clearUser() {
    this.user = null;
    
    if (!this.enabled || !this.Sentry) {
      return;
    }

    this.Sentry.setUser(null);
  }

  /**
   * 設定標籤
   * @param {Object} tags - 標籤物件
   */
  setTags(tags) {
    if (!this.enabled || !this.Sentry) {
      return;
    }

    this.Sentry.setTags(tags);
  }

  /**
   * 設定上下文
   * @param {string} name - 上下文名稱
   * @param {Object} context - 上下文資料
   */
  setContext(name, context) {
    if (!this.enabled || !this.Sentry) {
      return;
    }

    this.Sentry.setContext(name, context);
  }

  /**
   * 建立交易（效能監控）
   * @param {Object} transactionContext - 交易上下文
   * @returns {Transaction}
   */
  startTransaction(transactionContext) {
    if (!this.enabled || !this.Sentry) {
      return {
        startChild: () => ({ finish: () => {} }),
        finish: () => {}
      };
    }

    return this.Sentry.startTransaction(transactionContext);
  }

  /**
   * 監控 API 請求
   * @param {string} method - HTTP 方法
   * @param {string} url - 請求 URL
   * @param {Function} request - 請求函式
   * @returns {Promise}
   */
  async trackAPIRequest(method, url, request) {
    const transaction = this.startTransaction({
      name: `${method} ${url}`,
      op: 'http.client'
    });

    try {
      const result = await request();
      transaction.setStatus('ok');
      return result;
    } catch (error) {
      transaction.setStatus('internal_error');
      this.captureException(error, {
        type: 'apiRequest',
        method,
        url
      });
      throw error;
    } finally {
      transaction.finish();
    }
  }

  /**
   * 監控頁面載入
   * @param {string} routeName - 路由名稱
   */
  trackPageLoad(routeName) {
    this.addBreadcrumb({
      category: 'navigation',
      message: `Navigated to ${routeName}`,
      level: 'info'
    });

    // 記錄效能指標
    if (window.performance && window.performance.timing) {
      const perfData = window.performance.timing;
      const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
      const domReadyTime = perfData.domContentLoadedEventEnd - perfData.navigationStart;
      
      this.setContext('performance', {
        pageLoadTime,
        domReadyTime,
        route: routeName
      });
    }
  }

  /**
   * 監控使用者互動
   * @param {string} action - 動作名稱
   * @param {Object} data - 相關資料
   */
  trackInteraction(action, data = {}) {
    this.addBreadcrumb({
      category: 'user',
      message: action,
      level: 'info',
      data
    });
  }
}

// 全域實例
let errorTracker = null;

/**
 * 取得錯誤追蹤器實例
 * @returns {ErrorTracker}
 */
export function getErrorTracker() {
  if (!errorTracker) {
    errorTracker = new ErrorTracker();
  }
  return errorTracker;
}

/**
 * 初始化錯誤追蹤
 * @param {Object} options - 配置選項
 * @returns {Promise<ErrorTracker>}
 */
export async function initErrorTracking(options = {}) {
  const tracker = getErrorTracker();
  await tracker.init(options);
  return tracker;
}

// 便捷方法
export const captureException = (error, context) => {
  getErrorTracker().captureException(error, context);
};

export const captureMessage = (message, level, context) => {
  getErrorTracker().captureMessage(message, level, context);
};

export const addBreadcrumb = (breadcrumb) => {
  getErrorTracker().addBreadcrumb(breadcrumb);
};

export default {
  ErrorTracker,
  getErrorTracker,
  initErrorTracking,
  captureException,
  captureMessage,
  addBreadcrumb
};
