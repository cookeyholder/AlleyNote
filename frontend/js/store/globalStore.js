/**
 * 全域狀態管理
 * 提供應用程式級別的狀態管理，包括使用者認證狀態等
 */

import { storage } from "../utils/storage.js";
import {
    cacheSiteSettings,
    getDefaultSiteSettings,
    loadCachedSiteSettings,
} from "../utils/siteSettings.js";

/**
 * 全域狀態
 */
class GlobalStore {
    constructor() {
        this.state = {
            user: null,
            isAuthenticated: false,
            loading: false,
            error: null,
            settings: getDefaultSiteSettings(),
        };

        this.listeners = [];

        // 從 localStorage 恢復狀態
        this.restoreState();
    }

    /**
     * 恢復狀態
     */
    restoreState() {
        const user = storage.get("user");
        const token = storage.get("access_token");
        const cachedSettings = loadCachedSiteSettings();

        if (user && token) {
            this.state.user = user;
            this.state.isAuthenticated = true;
        }

        this.state.settings = cachedSettings;
    }

    /**
     * 取得狀態
     */
    getState() {
        return { ...this.state };
    }

    /**
     * 更新狀態
     */
    setState(updates) {
        this.state = { ...this.state, ...updates };
        this.notify();
    }

    /**
     * 訂閱狀態變化
     */
    subscribe(listener) {
        this.listeners.push(listener);

        // 返回取消訂閱函數
        return () => {
            this.listeners = this.listeners.filter((l) => l !== listener);
        };
    }

    /**
     * 通知所有監聽者
     */
    notify() {
        this.listeners.forEach((listener) => {
            listener(this.state);
        });
    }

    /**
     * 設定使用者
     */
    setUser(user) {
        this.state.user = user;
        this.state.isAuthenticated = !!user;
        storage.set("user", user);
        this.notify();
    }

    /**
     * 清除使用者
     */
    clearUser() {
        this.state.user = null;
        this.state.isAuthenticated = false;
        storage.remove("user");
        storage.remove("access_token");
        this.notify();
    }

    /**
     * 取得當前使用者
     */
    getUser() {
        return this.state.user;
    }

    /**
     * 檢查是否已認證
     */
    isAuthenticated() {
        return this.state.isAuthenticated;
    }

    /**
     * 設定載入狀態
     */
    setLoading(loading) {
        this.state.loading = loading;
        this.notify();
    }

    /**
     * 設定錯誤
     */
    setError(error) {
        this.state.error = error;
        this.notify();
    }

    /**
     * 清除錯誤
     */
    clearError() {
        this.state.error = null;
        this.notify();
    }

    /**
     * 設定站台設定
     */
    setSettings(settings) {
        if (!settings) {
            return;
        }

        this.state.settings = {
            ...this.state.settings,
            ...settings,
        };

        cacheSiteSettings(this.state.settings);
        this.notify();
    }

    /**
     * 取得站台設定
     */
    getSettings() {
        return this.state.settings;
    }
}

// 建立單例
const globalStore = new GlobalStore();

/**
 * 全域 Getters
 */
export const globalGetters = {
    getUser: () => globalStore.getUser(),
    getCurrentUser: () => globalStore.getUser(),
    isAuthenticated: () => globalStore.isAuthenticated(),
    isAdmin: () => {
        const user = globalStore.getUser();
        if (!user) return false;
        return user.role === "super_admin" || user.role === "admin";
    },
    getState: () => globalStore.getState(),
    getSettings: () => globalStore.getSettings(),
};

/**
 * 全域 Actions
 */
export const globalActions = {
    setUser: (user) => globalStore.setUser(user),
    clearUser: () => globalStore.clearUser(),
    setLoading: (loading) => globalStore.setLoading(loading),
    setError: (error) => globalStore.setError(error),
    clearError: () => globalStore.clearError(),
    restoreUser: () => globalStore.restoreState(),
    setSettings: (settings) => globalStore.setSettings(settings),
};

// 匯出 store 實例供訂閱使用
export default globalStore;
