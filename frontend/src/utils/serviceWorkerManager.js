/**
 * Service Worker 管理器
 * 管理 PWA 的 Service Worker 註冊和更新
 * 
 * @author AlleyNote Team
 * @version 1.0.0
 */

/**
 * ServiceWorkerManager 類別
 */
class ServiceWorkerManager {
  constructor() {
    this.registration = null;
    this.updateAvailable = false;
    this.listeners = new Map();
  }

  /**
   * 註冊 Service Worker
   * @param {string} scriptURL - Service Worker 腳本路徑
   * @param {Object} options - 註冊選項
   * @returns {Promise<ServiceWorkerRegistration>}
   */
  async register(scriptURL = '/sw.js', options = {}) {
    // 檢查瀏覽器支援
    if (!('serviceWorker' in navigator)) {
      console.warn('Service Worker not supported');
      return null;
    }

    try {
      console.log('[SW Manager] Registering Service Worker...');
      
      this.registration = await navigator.serviceWorker.register(scriptURL, {
        scope: '/',
        ...options
      });

      console.log('[SW Manager] Service Worker registered:', this.registration);

      // 監聽更新
      this.registration.addEventListener('updatefound', () => {
        this.handleUpdateFound();
      });

      // 檢查是否有等待中的 Service Worker
      if (this.registration.waiting) {
        this.updateAvailable = true;
        this.emit('updateAvailable', this.registration.waiting);
      }

      // 監聽狀態變化
      if (this.registration.installing) {
        this.trackInstalling(this.registration.installing);
      }

      // 檢查更新
      this.checkForUpdates();

      return this.registration;
    } catch (error) {
      console.error('[SW Manager] Registration failed:', error);
      throw error;
    }
  }

  /**
   * 處理找到更新
   */
  handleUpdateFound() {
    console.log('[SW Manager] Update found');
    const newWorker = this.registration.installing;
    
    this.trackInstalling(newWorker);
  }

  /**
   * 追蹤安裝狀態
   * @param {ServiceWorker} worker - Service Worker 實例
   */
  trackInstalling(worker) {
    worker.addEventListener('statechange', () => {
      console.log('[SW Manager] State changed:', worker.state);
      
      if (worker.state === 'installed') {
        if (navigator.serviceWorker.controller) {
          // 有新版本可用
          this.updateAvailable = true;
          this.emit('updateAvailable', worker);
          console.log('[SW Manager] New version available');
        } else {
          // 首次安裝
          this.emit('installed', worker);
          console.log('[SW Manager] Service Worker installed');
        }
      }
      
      if (worker.state === 'activated') {
        this.emit('activated', worker);
        console.log('[SW Manager] Service Worker activated');
      }
    });
  }

  /**
   * 檢查更新
   * @returns {Promise<void>}
   */
  async checkForUpdates() {
    if (!this.registration) {
      return;
    }

    try {
      await this.registration.update();
      console.log('[SW Manager] Checked for updates');
    } catch (error) {
      console.error('[SW Manager] Update check failed:', error);
    }
  }

  /**
   * 跳過等待並啟用新的 Service Worker
   * @returns {Promise<void>}
   */
  async skipWaiting() {
    if (!this.registration || !this.registration.waiting) {
      return;
    }

    // 發送訊息給等待中的 Service Worker
    this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });

    // 監聽控制器變更
    return new Promise((resolve) => {
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        console.log('[SW Manager] Controller changed, reloading...');
        this.emit('controllerchange');
        resolve();
      }, { once: true });
    });
  }

  /**
   * 更新並重新載入
   * @returns {Promise<void>}
   */
  async updateAndReload() {
    await this.skipWaiting();
    window.location.reload();
  }

  /**
   * 取消註冊 Service Worker
   * @returns {Promise<boolean>}
   */
  async unregister() {
    if (!this.registration) {
      return false;
    }

    try {
      const result = await this.registration.unregister();
      console.log('[SW Manager] Service Worker unregistered');
      this.registration = null;
      return result;
    } catch (error) {
      console.error('[SW Manager] Unregister failed:', error);
      return false;
    }
  }

  /**
   * 發送訊息給 Service Worker
   * @param {Object} message - 訊息物件
   * @returns {Promise<any>}
   */
  async postMessage(message) {
    if (!this.registration || !this.registration.active) {
      throw new Error('Service Worker not active');
    }

    return new Promise((resolve, reject) => {
      const messageChannel = new MessageChannel();
      
      messageChannel.port1.onmessage = (event) => {
        if (event.data.error) {
          reject(event.data.error);
        } else {
          resolve(event.data);
        }
      };

      this.registration.active.postMessage(message, [messageChannel.port2]);
    });
  }

  /**
   * 清除快取
   * @param {string} cacheName - 快取名稱（可選）
   * @returns {Promise<void>}
   */
  async clearCache(cacheName) {
    await this.postMessage({
      type: 'CLEAR_CACHE',
      payload: { cacheName }
    });
  }

  /**
   * 預快取 URLs
   * @param {string[]} urls - 要快取的 URLs
   * @returns {Promise<void>}
   */
  async cacheUrls(urls) {
    await this.postMessage({
      type: 'CACHE_URLS',
      payload: { urls }
    });
  }

  /**
   * 監聽事件
   * @param {string} event - 事件名稱
   * @param {Function} handler - 處理函式
   * @returns {Function} 取消監聽的函式
   */
  on(event, handler) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    
    this.listeners.get(event).add(handler);
    
    return () => this.off(event, handler);
  }

  /**
   * 取消監聽事件
   * @param {string} event - 事件名稱
   * @param {Function} handler - 處理函式
   */
  off(event, handler) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).delete(handler);
    }
  }

  /**
   * 觸發事件
   * @param {string} event - 事件名稱
   * @param {any} data - 事件資料
   */
  emit(event, data) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).forEach(handler => {
        try {
          handler(data);
        } catch (error) {
          console.error(`[SW Manager] Event handler error (${event}):`, error);
        }
      });
    }
  }

  /**
   * 取得 Service Worker 狀態
   * @returns {Object} 狀態物件
   */
  getStatus() {
    return {
      supported: 'serviceWorker' in navigator,
      registered: !!this.registration,
      active: !!(this.registration && this.registration.active),
      waiting: !!(this.registration && this.registration.waiting),
      installing: !!(this.registration && this.registration.installing),
      updateAvailable: this.updateAvailable
    };
  }
}

// 全域實例
let swManager = null;

/**
 * 取得 Service Worker Manager 實例
 * @returns {ServiceWorkerManager}
 */
export function getServiceWorkerManager() {
  if (!swManager) {
    swManager = new ServiceWorkerManager();
  }
  return swManager;
}

/**
 * 初始化 Service Worker
 * @param {Object} options - 選項
 * @returns {Promise<ServiceWorkerManager>}
 */
export async function initServiceWorker(options = {}) {
  const manager = getServiceWorkerManager();
  
  try {
    await manager.register(options.scriptURL, options.registerOptions);
    
    // 監聽更新
    if (options.onUpdateAvailable) {
      manager.on('updateAvailable', options.onUpdateAvailable);
    }
    
    // 自動檢查更新（每小時一次）
    if (options.autoCheckUpdates !== false) {
      setInterval(() => {
        manager.checkForUpdates();
      }, options.updateInterval || 60 * 60 * 1000);
    }
    
    return manager;
  } catch (error) {
    console.error('[SW Manager] Initialization failed:', error);
    throw error;
  }
}

/**
 * 顯示更新提示
 * @param {ServiceWorker} worker - Service Worker 實例
 */
export function showUpdatePrompt(worker) {
  // 建立更新提示 UI
  const prompt = document.createElement('div');
  prompt.className = 'sw-update-prompt';
  prompt.innerHTML = `
    <div class="sw-update-prompt-content">
      <p>發現新版本！</p>
      <button class="btn-update">立即更新</button>
      <button class="btn-dismiss">稍後再說</button>
    </div>
  `;
  
  // 樣式
  const style = document.createElement('style');
  style.textContent = `
    .sw-update-prompt {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: white;
      padding: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      max-width: 300px;
    }
    
    .sw-update-prompt-content p {
      margin: 0 0 1rem 0;
      color: #374151;
      font-weight: 500;
    }
    
    .sw-update-prompt button {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 0.375rem;
      font-weight: 500;
      cursor: pointer;
      margin-right: 0.5rem;
    }
    
    .btn-update {
      background: #667eea;
      color: white;
    }
    
    .btn-dismiss {
      background: #e5e7eb;
      color: #374151;
    }
  `;
  
  document.head.appendChild(style);
  document.body.appendChild(prompt);
  
  // 事件處理
  prompt.querySelector('.btn-update').addEventListener('click', () => {
    const manager = getServiceWorkerManager();
    manager.updateAndReload();
  });
  
  prompt.querySelector('.btn-dismiss').addEventListener('click', () => {
    prompt.remove();
  });
}

export default {
  ServiceWorkerManager,
  getServiceWorkerManager,
  initServiceWorker,
  showUpdatePrompt
};
