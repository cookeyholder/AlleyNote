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

/**
 * 初始化應用程式
 */
async function initApp() {
  console.log('🚀 AlleyNote 前端應用程式啟動中...');

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

  // 檢查登入狀態
  if (authAPI.isAuthenticated()) {
    try {
      const user = await authAPI.me();
      globalActions.setUser(user);
      console.log('✅ 使用者已登入:', user);
    } catch (error) {
      console.warn('⚠️ 驗證失敗，清除登入狀態');
      globalActions.clearUser();
    }
  }

  // 初始化路由
  initRouter();

  console.log('✅ AlleyNote 前端應用程式啟動完成');
}

// 啟動應用程式
initApp().catch((error) => {
  console.error('❌ 應用程式啟動失敗:', error);
});
