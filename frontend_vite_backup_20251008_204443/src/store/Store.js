/**
 * 簡易狀態管理 Store
 */
export class Store {
  constructor(initialState = {}) {
    this.state = { ...initialState };
    this.listeners = new Map();
  }

  /**
   * 取得狀態
   */
  get(key) {
    return this.state[key];
  }

  /**
   * 設定狀態
   */
  set(key, value) {
    const oldValue = this.state[key];
    this.state[key] = value;
    this.notify(key, value, oldValue);
  }

  /**
   * 更新狀態（支援深層更新）
   */
  update(key, updater) {
    const oldValue = this.state[key];
    const newValue = updater(oldValue);
    this.set(key, newValue);
  }

  /**
   * 訂閱狀態變更
   */
  subscribe(key, callback) {
    if (!this.listeners.has(key)) {
      this.listeners.set(key, new Set());
    }
    
    this.listeners.get(key).add(callback);
    
    // 回傳取消訂閱函式
    return () => {
      this.listeners.get(key).delete(callback);
    };
  }

  /**
   * 通知訂閱者
   * @private
   */
  notify(key, newValue, oldValue) {
    const listeners = this.listeners.get(key);
    if (listeners) {
      listeners.forEach((callback) => {
        callback(newValue, oldValue);
      });
    }
  }

  /**
   * 重置狀態
   */
  reset() {
    this.state = {};
  }

  /**
   * 取得所有狀態
   */
  getAll() {
    return { ...this.state };
  }
}
