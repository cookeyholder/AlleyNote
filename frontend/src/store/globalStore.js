import { Store } from './Store.js';

/**
 * 全域狀態初始值
 */
const initialState = {
  user: null,
  isAuthenticated: false,
  theme: 'light',
  sidebarCollapsed: false,
  notifications: [],
};

/**
 * 全域 Store 實例
 */
export const globalStore = new Store(initialState);

/**
 * 全域狀態操作方法
 */
export const globalActions = {
  /**
   * 設定使用者資訊
   */
  setUser(user) {
    globalStore.set('user', user);
    globalStore.set('isAuthenticated', !!user);
  },

  /**
   * 清除使用者資訊
   */
  clearUser() {
    globalStore.set('user', null);
    globalStore.set('isAuthenticated', false);
  },

  /**
   * 切換側邊欄
   */
  toggleSidebar() {
    const collapsed = globalStore.get('sidebarCollapsed');
    globalStore.set('sidebarCollapsed', !collapsed);
  },

  /**
   * 新增通知
   */
  addNotification(notification) {
    globalStore.update('notifications', (notifications) => {
      return [
        ...notifications,
        {
          id: Date.now(),
          timestamp: new Date().toISOString(),
          ...notification,
        },
      ];
    });
  },
};

/**
 * 全域狀態讀取方法
 */
export const globalGetters = {
  getCurrentUser() {
    return globalStore.get('user');
  },

  isAuthenticated() {
    return globalStore.get('isAuthenticated');
  },

  getUserRole() {
    const user = globalStore.get('user');
    return user?.role || null;
  },

  isAdmin() {
    const role = this.getUserRole();
    return role === 'admin' || role === 'super_admin';
  },
};
