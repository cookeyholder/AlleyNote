/**
 * AlleyNote 前端應用程式主入口
 */

import './style.css';
import { initRouter } from './router/index.js';
import { globalActions } from './store/globalStore.js';
import { authAPI } from './api/modules/auth.js';
import { offlineDetector } from './utils/offlineDetector.js';

/**
 * 初始化應用程式
 */
async function initApp() {
  console.log('🚀 AlleyNote 前端應用程式啟動中...');

  // 初始化離線偵測
  offlineDetector.init();

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
