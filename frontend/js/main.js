/**
 * AlleyNote 前端應用程式主入口
 *
 * 初始化應用程式的核心功能：
 * - 路由系統
 * - 使用者狀態恢復
 * - API 客戶端設定
 * - 全域事件監聽
 */

import { initRouter, router } from "./utils/router.js";
import { globalActions, globalGetters } from "./store/globalStore.js";
import { authAPI } from "./api/modules/auth.js";
import { loading } from "./components/Loading.js";
import { notification } from "./utils/notification.js";

/**
 * 初始化應用程式
 */
async function initApp() {
  try {
    // 顯示載入指示器
    loading.show("應用程式初始化中...");

    // 1. 恢復使用者狀態
    globalActions.restoreUser();

    // 2. 檢查並刷新使用者資訊
    if (globalGetters.isAuthenticated()) {
      try {
        const user = await authAPI.me();
        globalActions.setUser(user);
      } catch (error) {
        console.warn("⚠️ 使用者驗證失敗，清除登入狀態");
        globalActions.clearUser();
      }
    }

    // 3. 初始化路由系統
    initRouter();

    // 4. 設定全域事件監聽
    setupGlobalListeners();

    // 5. 隱藏載入指示器
    setTimeout(() => {
      loading.hide();
    }, 300);
  } catch (error) {
    console.error("❌ 應用程式啟動失敗:", error);
    loading.hide();
    notification.error("應用程式啟動失敗，請重新整理頁面");
  }
}

/**
 * 設定全域事件監聽
 */
function setupGlobalListeners() {
  // 監聽登出事件
  window.addEventListener("auth:logout", () => {
    globalActions.clearUser();

    // 重導向到首頁
    if (window.location.pathname.startsWith("/admin")) {
      window.location.href = "/";
    }
  });

  // 監聽線上/離線狀態
  window.addEventListener("online", () => {
    notification.banner.hide();
    notification.success("網路連線已恢復");
  });

  window.addEventListener("offline", () => {
    notification.banner.show(
      "網路連線已中斷，部分功能可能暫時無法使用。",
      "warning",
      {
        title: "連線狀態",
        dismissible: false,
      },
    );
  });

  // 監聽視窗大小變化（用於響應式調整）
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {}, 250);
  });

  // 全域錯誤處理
  window.addEventListener("error", (event) => {
    console.error("全域錯誤:", event.error);
  });

  window.addEventListener("unhandledrejection", (event) => {
    console.error("未處理的 Promise 拒絕:", event.reason);
  });
}

/**
 * 頁面載入完成後啟動應用程式
 */
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initApp);
} else {
  initApp();
}

/**
 * 匯出全域函數供 HTML 使用
 */
window.navigateTo = (path) => {
  router.navigate(path);
};
