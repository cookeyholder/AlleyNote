import { Store } from './Store.js';
import { tokenManager } from '../utils/tokenManager.js';

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
    
    // 同步儲存到 localStorage 以持久化
    if (user) {
      localStorage.setItem('alleynote_user', JSON.stringify(user));
    } else {
      localStorage.removeItem('alleynote_user');
    }
  },

  /**
   * 清除使用者資訊
   */
  clearUser() {
    globalStore.set('user', null);
    globalStore.set('isAuthenticated', false);
    localStorage.removeItem('alleynote_user');
  },

  /**
   * 從儲存中恢復使用者狀態
   */
  restoreUser() {
    try {
      const userData = localStorage.getItem('alleynote_user');
      if (userData && tokenManager.isValid()) {
        const user = JSON.parse(userData);
        globalStore.set('user', user);
        globalStore.set('isAuthenticated', true);
        return user;
      }
    } catch (error) {
      console.error('Failed to restore user:', error);
    }
    return null;
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
    // 先檢查記憶體中的狀態
    const isAuth = globalStore.get('isAuthenticated');
    if (isAuth) {
      return true;
    }
    
    // 如果記憶體中沒有，嘗試從儲存中恢復
    if (tokenManager.isValid()) {
      globalActions.restoreUser();
      return globalStore.get('isAuthenticated');
    }
    
    return false;
  },

  getUserRole() {
    const user = globalStore.get('user');
    // 嘗試從 user 物件的多個可能位置取得角色
    if (user?.role) {
      return user.role;
    }
    if (user?.roles && Array.isArray(user.roles) && user.roles.length > 0) {
      // 如果 roles 是陣列，檢查是否包含管理員角色
      const roles = user.roles;
      for (const role of roles) {
        if (typeof role === 'object' && role.id) {
          // 檢查角色 ID，1 通常是超級管理員
          if (role.id === 1) {
            return 'super_admin';
          }
        }
      }
      // 回傳第一個角色名稱
      return typeof roles[0] === 'object' ? roles[0].name : roles[0];
    }
    return null;
  },

  isAdmin() {
    const user = globalStore.get('user');
    // 多種方式檢查是否為管理員
    
    // 方式 1: 檢查 role 欄位
    const role = this.getUserRole();
    if (role === 'admin' || role === 'super_admin' || role === '超級管理員') {
      return true;
    }
    
    // 方式 2: 檢查 roles 陣列
    if (user?.roles && Array.isArray(user.roles)) {
      for (const r of user.roles) {
        if (typeof r === 'object') {
          // 檢查角色 ID (1 = 超級管理員)
          if (r.id === 1) {
            return true;
          }
          // 檢查角色名稱
          if (r.name === '超級管理員' || r.name === 'admin' || r.name === 'super_admin') {
            return true;
          }
        }
      }
    }
    
    return false;
  },
};

// 初始化時嘗試恢復使用者狀態
globalActions.restoreUser();
