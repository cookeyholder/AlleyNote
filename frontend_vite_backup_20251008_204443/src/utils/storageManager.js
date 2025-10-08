/**
 * Storage Manager
 * 統一管理 LocalStorage 和 SessionStorage
 */
export class StorageManager {
  constructor(storageType = 'session') {
    this.storage = storageType === 'local' ? localStorage : sessionStorage;
  }

  /**
   * 設定值
   */
  set(key, value) {
    try {
      const data = JSON.stringify(value);
      this.storage.setItem(key, data);
      return true;
    } catch (error) {
      console.error('Storage set error:', error);
      return false;
    }
  }

  /**
   * 取得值
   */
  get(key, defaultValue = null) {
    try {
      const data = this.storage.getItem(key);
      return data ? JSON.parse(data) : defaultValue;
    } catch (error) {
      console.error('Storage get error:', error);
      return defaultValue;
    }
  }

  /**
   * 移除值
   */
  remove(key) {
    try {
      this.storage.removeItem(key);
      return true;
    } catch (error) {
      console.error('Storage remove error:', error);
      return false;
    }
  }

  /**
   * 清空所有值
   */
  clear() {
    try {
      this.storage.clear();
      return true;
    } catch (error) {
      console.error('Storage clear error:', error);
      return false;
    }
  }

  /**
   * 檢查 key 是否存在
   */
  has(key) {
    return this.storage.getItem(key) !== null;
  }

  /**
   * 取得所有 keys
   */
  keys() {
    return Object.keys(this.storage);
  }

  /**
   * 取得儲存空間大小（bytes）
   */
  getSize() {
    let size = 0;
    for (const key of this.keys()) {
      size += key.length + (this.storage.getItem(key)?.length || 0);
    }
    return size;
  }
}

/**
 * 全域實例
 */
export const sessionStorage = new StorageManager('session');
export const localStorage = new StorageManager('local');
