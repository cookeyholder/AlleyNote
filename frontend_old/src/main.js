/**
 * AlleyNote 前端應用程式主入口
 */

import './style.css';
import { initRouter } from './router/index.js';
import { globalActions } from './store/globalStore.js';
import { authAPI } from './api/modules/auth.js';
import { offlineDetector } from './utils/offlineDetector.js';
import { initServiceWorker, showUpdatePrompt } from './utils/serviceWorkerManager.js';
import { initLazyLoad } from './utils/lazyLoad.js';
import { initErrorTracking } from './utils/errorTracker.js';
import { initAnalytics } from './utils/analytics.js';
import { initWebVitals } from './utils/webVitals.js';

/**
 * 初始化應用程式
 */
async function initApp() {
  console.log('🚀 AlleyNote 前端應用程式啟動中...');

  // ⚡ 優先恢復使用者狀態（修復路由守衛問題）
  console.log('🔄 恢復使用者狀態...');
  globalActions.restoreUser();
  console.log('✅ 使用者狀態已恢復');

  // 初始化錯誤追蹤（Sentry）
  try {
    await initErrorTracking({
      forceEnable: false // 僅生產環境啟用
    });
    console.log('✅ 錯誤追蹤已初始化');
  } catch (error) {
    console.warn('⚠️ 錯誤追蹤初始化失敗:', error);
  }

  // 初始化 Google Analytics
  try {
    initAnalytics({
      forceEnable: false // 僅生產環境啟用
    });
    console.log('✅ Google Analytics 已初始化');
  } catch (error) {
    console.warn('⚠️ Google Analytics 初始化失敗:', error);
  }

  // 初始化 Web Vitals 監控
  try {
    await initWebVitals();
    console.log('✅ Web Vitals 監控已啟動');
  } catch (error) {
    console.warn('⚠️ Web Vitals 監控失敗:', error);
  }

  // 初始化離線偵測
  offlineDetector.init();

  // 初始化 Service Worker（PWA 支援）
  if ('serviceWorker' in navigator && import.meta.env.PROD) {
    try {
      const swManager = await initServiceWorker({
        onUpdateAvailable: (worker) => {
          console.log('🔄 發現新版本');
          showUpdatePrompt(worker);
        },
        autoCheckUpdates: true,
        updateInterval: 60 * 60 * 1000 // 每小時檢查一次
      });
      console.log('✅ Service Worker 已註冊');
    } catch (error) {
      console.warn('⚠️ Service Worker 註冊失敗:', error);
    }
  }

  // 初始化圖片懶加載
  const lazyLoad = initLazyLoad({
    rootMargin: '50px',
    threshold: 0.01
  });
  
  // 監聽路由變化，重新掃描圖片
  window.addEventListener('popstate', () => {
    setTimeout(() => {
      lazyLoad.observe('img[data-src]');
    }, 100);
  });

  // 檢查登入狀態並刷新使用者資訊
  if (authAPI.isAuthenticated()) {
    try {
      const user = await authAPI.me();
      globalActions.setUser(user);
      console.log('✅ 使用者已登入:', user);
      
      // 設定錯誤追蹤的使用者資訊
      const { getErrorTracker } = await import('./utils/errorTracker.js');
      getErrorTracker().setUser(user);
      
      // 設定 Analytics 的使用者資訊
      const { getAnalytics } = await import('./utils/analytics.js');
      getAnalytics().setUserId(user.id);
      getAnalytics().setUserProperties({
        role: user.role,
        created_at: user.createdAt
      });
    } catch (error) {
      console.warn('⚠️ 驗證失敗，清除登入狀態');
      globalActions.clearUser();
    }
  }

  // 初始化路由
  initRouter();

  console.log('✅ AlleyNote 前端應用程式啟動完成');
  
  // 追蹤應用程式啟動
  const { trackEvent } = await import('./utils/analytics.js');
  trackEvent('app_start', {
    environment: import.meta.env.MODE,
    version: import.meta.env.VITE_APP_VERSION || '1.0.0'
  });
}

// 啟動應用程式
initApp().catch((error) => {
  console.error('❌ 應用程式啟動失敗:', error);
  
  // 捕獲啟動錯誤
  import('./utils/errorTracker.js').then(({ captureException }) => {
    captureException(error, {
      context: 'app_initialization'
    });
  });
});
