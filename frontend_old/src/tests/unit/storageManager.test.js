/**
 * StorageManager 單元測試
 * 測試 LocalStorage 和 SessionStorage 管理功能
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { StorageManager } from '../../utils/storageManager.js';

describe('StorageManager', () => {
  let storageManager;
  let mockStorage;

  beforeEach(() => {
    // 建立 mock storage
    mockStorage = {};
    
    // Mock localStorage
    global.localStorage = {
      getItem: vi.fn((key) => mockStorage[key] || null),
      setItem: vi.fn((key, value) => {
        mockStorage[key] = value;
      }),
      removeItem: vi.fn((key) => {
        delete mockStorage[key];
      }),
      clear: vi.fn(() => {
        mockStorage = {};
      })
    };

    storageManager = new StorageManager('local');
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('基本操作', () => {
    it('應該能夠儲存字串', () => {
      storageManager.set('test', 'value');
      expect(localStorage.setItem).toHaveBeenCalledWith('test', '"value"');
    });

    it('應該能夠儲存物件', () => {
      const obj = { name: 'test', value: 123 };
      storageManager.set('test', obj);
      expect(localStorage.setItem).toHaveBeenCalled();
    });

    it('應該能夠取得字串', () => {
      mockStorage['test'] = '"value"';
      const result = storageManager.get('test');
      expect(result).toBe('value');
    });

    it('應該能夠取得物件', () => {
      const obj = { name: 'test', value: 123 };
      mockStorage['test'] = JSON.stringify(obj);
      const result = storageManager.get('test');
      expect(result).toEqual(obj);
    });

    it('不存在的 key 應該返回 null', () => {
      const result = storageManager.get('nonexistent');
      expect(result).toBeNull();
    });

    it('應該能夠刪除項目', () => {
      storageManager.remove('test');
      expect(localStorage.removeItem).toHaveBeenCalledWith('test');
    });

    it('應該能夠清空所有項目', () => {
      storageManager.clear();
      expect(localStorage.clear).toHaveBeenCalled();
    });
  });

  describe('預設值', () => {
    it('不存在的 key 應該返回預設值', () => {
      const result = storageManager.get('nonexistent', 'default');
      expect(result).toBe('default');
    });

    it('存在的 key 不應該返回預設值', () => {
      mockStorage['test'] = '"value"';
      const result = storageManager.get('test', 'default');
      expect(result).toBe('value');
    });
  });

  describe('錯誤處理', () => {
    it('無效的 JSON 應該返回 null', () => {
      mockStorage['test'] = 'invalid json';
      const result = storageManager.get('test');
      expect(result).toBeNull();
    });

    it('儲存失敗應該不拋出錯誤', () => {
      localStorage.setItem = vi.fn(() => {
        throw new Error('Storage full');
      });
      
      expect(() => {
        storageManager.set('test', 'value');
      }).not.toThrow();
    });

    it('讀取失敗應該返回預設值', () => {
      localStorage.getItem = vi.fn(() => {
        throw new Error('Storage error');
      });
      
      const result = storageManager.get('test', 'default');
      expect(result).toBe('default');
    });
  });

  describe('TTL (存活時間)', () => {
    it('應該儲存帶有 TTL 的資料', () => {
      storageManager.setWithTTL('test', 'value', 3600);
      expect(localStorage.setItem).toHaveBeenCalled();
    });

    it('未過期的資料應該能取得', () => {
      const now = Date.now();
      const data = {
        value: 'test',
        expiry: now + 3600000 // 1小時後過期
      };
      mockStorage['test'] = JSON.stringify(data);
      
      const result = storageManager.getWithTTL('test');
      expect(result).toBe('test');
    });

    it('已過期的資料應該返回 null', () => {
      const now = Date.now();
      const data = {
        value: 'test',
        expiry: now - 1000 // 已過期
      };
      mockStorage['test'] = JSON.stringify(data);
      
      const result = storageManager.getWithTTL('test');
      expect(result).toBeNull();
    });

    it('過期的資料應該被刪除', () => {
      const now = Date.now();
      const data = {
        value: 'test',
        expiry: now - 1000
      };
      mockStorage['test'] = JSON.stringify(data);
      
      storageManager.getWithTTL('test');
      expect(localStorage.removeItem).toHaveBeenCalledWith('test');
    });
  });

  describe('前綴功能', () => {
    beforeEach(() => {
      storageManager = new StorageManager('local', 'app_');
    });

    it('應該使用前綴儲存', () => {
      storageManager.set('test', 'value');
      expect(localStorage.setItem).toHaveBeenCalledWith('app_test', '"value"');
    });

    it('應該使用前綴讀取', () => {
      mockStorage['app_test'] = '"value"';
      const result = storageManager.get('test');
      expect(result).toBe('value');
      expect(localStorage.getItem).toHaveBeenCalledWith('app_test');
    });

    it('應該使用前綴刪除', () => {
      storageManager.remove('test');
      expect(localStorage.removeItem).toHaveBeenCalledWith('app_test');
    });
  });

  describe('批次操作', () => {
    it('應該能夠取得所有 keys', () => {
      mockStorage['test1'] = '"value1"';
      mockStorage['test2'] = '"value2"';
      mockStorage['other'] = '"value3"';
      
      Object.defineProperty(global.localStorage, 'length', {
        get: () => Object.keys(mockStorage).length
      });
      
      global.localStorage.key = vi.fn((index) => {
        return Object.keys(mockStorage)[index] || null;
      });
      
      const keys = storageManager.keys();
      expect(keys.length).toBeGreaterThan(0);
    });

    it('應該能夠檢查 key 是否存在', () => {
      mockStorage['test'] = '"value"';
      expect(storageManager.has('test')).toBe(true);
      expect(storageManager.has('nonexistent')).toBe(false);
    });
  });

  describe('SessionStorage', () => {
    beforeEach(() => {
      mockStorage = {};
      
      global.sessionStorage = {
        getItem: vi.fn((key) => mockStorage[key] || null),
        setItem: vi.fn((key, value) => {
          mockStorage[key] = value;
        }),
        removeItem: vi.fn((key) => {
          delete mockStorage[key];
        }),
        clear: vi.fn(() => {
          mockStorage = {};
        })
      };

      storageManager = new StorageManager('session');
    });

    it('應該使用 sessionStorage', () => {
      storageManager.set('test', 'value');
      expect(sessionStorage.setItem).toHaveBeenCalled();
    });

    it('應該從 sessionStorage 讀取', () => {
      mockStorage['test'] = '"value"';
      const result = storageManager.get('test');
      expect(sessionStorage.getItem).toHaveBeenCalled();
      expect(result).toBe('value');
    });
  });

  describe('特殊資料類型', () => {
    it('應該能夠儲存數字', () => {
      storageManager.set('test', 123);
      mockStorage['test'] = '123';
      const result = storageManager.get('test');
      expect(result).toBe(123);
    });

    it('應該能夠儲存布林值', () => {
      storageManager.set('test', true);
      mockStorage['test'] = 'true';
      const result = storageManager.get('test');
      expect(result).toBe(true);
    });

    it('應該能夠儲存陣列', () => {
      const arr = [1, 2, 3];
      storageManager.set('test', arr);
      mockStorage['test'] = JSON.stringify(arr);
      const result = storageManager.get('test');
      expect(result).toEqual(arr);
    });

    it('應該能夠儲存 null', () => {
      storageManager.set('test', null);
      mockStorage['test'] = 'null';
      const result = storageManager.get('test');
      expect(result).toBeNull();
    });

    it('應該能夠儲存巢狀物件', () => {
      const obj = {
        name: 'test',
        nested: {
          value: 123,
          array: [1, 2, 3]
        }
      };
      storageManager.set('test', obj);
      mockStorage['test'] = JSON.stringify(obj);
      const result = storageManager.get('test');
      expect(result).toEqual(obj);
    });
  });

  describe('容量限制', () => {
    it('應該處理儲存空間不足的情況', () => {
      localStorage.setItem = vi.fn(() => {
        const error = new Error('QuotaExceededError');
        error.name = 'QuotaExceededError';
        throw error;
      });
      
      const result = storageManager.set('test', 'value');
      expect(result).toBe(false);
    });
  });
});
