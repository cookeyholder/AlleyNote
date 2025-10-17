/**
 * AlleyNote 前端應用程式主入口
 *
 * 初始化應用程式的核心功能：
 * - 路由系統
 * - 使用者狀態恢復
 * - API 客戶端設定
 * - 全域事件監聽
 */

import { initRouter } from "./utils/router.js";
import globalStore, {
    globalActions,
    globalGetters,
} from "./store/globalStore.js";
import { authAPI } from "./api/modules/auth.js";
import { loading } from "./components/Loading.js";
import { toast } from "./utils/toast.js";
import { applySiteBranding } from "./utils/siteSettings.js";

function setupCKEditorBridge() {
    const assignClassicEditor = () => {
        if (window.CKEDITOR?.ClassicEditor) {
            if (!window.ClassicEditor) {
                window.ClassicEditor = window.CKEDITOR.ClassicEditor;
            }
            return true;
        }
        return false;
    };

    if (assignClassicEditor()) {
        return;
    }

    let attempts = 0;
    const maxAttempts = 80; // 80 * 250ms = 20 秒
    const intervalId = window.setInterval(() => {
        attempts += 1;
        if (assignClassicEditor() || attempts >= maxAttempts) {
            clearInterval(intervalId);
            if (attempts >= maxAttempts) {
                console.warn(
                    "ClassicEditor 尚未就緒，請確認 CKEditor 腳本是否載入成功"
                );
            }
        }
    }, 250);
}

setupCKEditorBridge();

/**
 * 初始化應用程式
 */
async function initApp() {
    console.log("🚀 AlleyNote 前端應用程式啟動中...");

    try {
        // 顯示載入指示器
        loading.show("應用程式初始化中...");

        // 1. 恢復使用者狀態
        console.log("🔄 恢復使用者狀態...");
        globalActions.restoreUser();
        console.log("✅ 使用者狀態已恢復");

        // 2. 檢查並刷新使用者資訊
        if (globalGetters.isAuthenticated()) {
            try {
                console.log("🔐 驗證使用者身份...");
                const user = await authAPI.me();
                globalActions.setUser(user);
                console.log("✅ 使用者已登入:", user.username);
            } catch (error) {
                console.warn("⚠️ 使用者驗證失敗，清除登入狀態");
                globalActions.clearUser();
            }
        }

        // 3. 初始化路由系統
        console.log("🛣️ 初始化路由系統...");
        initRouter();
        console.log("✅ 路由系統已初始化");

        // 4. 設定全域事件監聽
        setupGlobalListeners();
        setupSettingsSync();

        // 5. 隱藏載入指示器
        setTimeout(() => {
            loading.hide();
        }, 300);

        console.log("✅ AlleyNote 前端應用程式啟動完成");
    } catch (error) {
        console.error("❌ 應用程式啟動失敗:", error);
        loading.hide();
        toast.error("應用程式啟動失敗，請重新整理頁面");
    }
}

/**
 * 設定全域事件監聽
 */
function setupGlobalListeners() {
    // 監聽登出事件
    window.addEventListener("auth:logout", () => {
        console.log("👋 使用者登出");
        globalActions.clearUser();

        // 重導向到首頁
        if (window.location.pathname.startsWith("/admin")) {
            window.location.href = "/";
        }
    });

    // 監聽線上/離線狀態
    window.addEventListener("online", () => {
        toast.success("網路連線已恢復");
    });

    window.addEventListener("offline", () => {
        toast.warning("網路連線已中斷");
    });

    // 監聽視窗大小變化（用於響應式調整）
    let resizeTimer;
    window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            console.log("視窗大小已變化");
        }, 250);
    });

    // 全域錯誤處理
    window.addEventListener("error", (event) => {
        console.error("全域錯誤:", event.error);
    });

    window.addEventListener("unhandledrejection", (event) => {
        console.error("未處理的 Promise 拒絕:", event.reason);
    });

    console.log("✅ 全域事件監聽已設定");
}

function setupSettingsSync() {
    const applySettings = (state) => {
        if (state?.settings) {
            applySiteBranding(state.settings);
        }
    };

    applySettings(globalStore.getState());
    const unsubscribe = globalStore.subscribe(applySettings);

    window.addEventListener("beforeunload", () => {
        unsubscribe();
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
    const { router } = require("./utils/router.js");
    router.navigate(path);
};
