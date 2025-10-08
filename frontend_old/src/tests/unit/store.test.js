/**
 * Store 單元測試
 * 測試狀態管理功能
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Store } from '../../store/Store.js';

describe('Store', () => {
  let store;

  beforeEach(() => {
    store = new Store({
      count: 0,
      user: { name: 'Test User' },
      items: []
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('基本狀態操作', () => {
    it('應該能夠取得初始狀態', () => {
      expect(store.getState()).toEqual({
        count: 0,
        user: { name: 'Test User' },
        items: []
      });
    });

    it('應該能夠取得特定狀態', () => {
      expect(store.get('count')).toBe(0);
      expect(store.get('user')).toEqual({ name: 'Test User' });
    });

    it('應該能夠設定狀態', () => {
      store.set('count', 1);
      expect(store.get('count')).toBe(1);
    });

    it('應該能夠更新巢狀狀態', () => {
      store.set('user.name', 'New Name');
      expect(store.get('user').name).toBe('New Name');
    });

    it('應該能夠重置狀態', () => {
      store.set('count', 10);
      store.reset();
      expect(store.get('count')).toBe(0);
    });
  });

  describe('訂閱機制', () => {
    it('應該能夠訂閱狀態變化', () => {
      const listener = vi.fn();
      store.subscribe(listener);
      
      store.set('count', 1);
      expect(listener).toHaveBeenCalledWith({
        count: 1,
        user: { name: 'Test User' },
        items: []
      });
    });

    it('應該能夠訂閱特定狀態變化', () => {
      const listener = vi.fn();
      store.subscribe('count', listener);
      
      store.set('count', 1);
      expect(listener).toHaveBeenCalledWith(1, 0);
    });

    it('應該能夠取消訂閱', () => {
      const listener = vi.fn();
      const unsubscribe = store.subscribe(listener);
      
      unsubscribe();
      store.set('count', 1);
      
      expect(listener).not.toHaveBeenCalled();
    });

    it('訂閱特定狀態時，其他狀態變化不應觸發', () => {
      const listener = vi.fn();
      store.subscribe('count', listener);
      
      store.set('user', { name: 'Another Name' });
      expect(listener).not.toHaveBeenCalled();
    });

    it('應該能夠有多個訂閱者', () => {
      const listener1 = vi.fn();
      const listener2 = vi.fn();
      
      store.subscribe(listener1);
      store.subscribe(listener2);
      
      store.set('count', 1);
      
      expect(listener1).toHaveBeenCalled();
      expect(listener2).toHaveBeenCalled();
    });
  });

  describe('批次更新', () => {
    it('應該能夠批次更新狀態', () => {
      const listener = vi.fn();
      store.subscribe(listener);
      
      store.setState({
        count: 5,
        user: { name: 'Batch Update' }
      });
      
      expect(store.get('count')).toBe(5);
      expect(store.get('user').name).toBe('Batch Update');
      expect(listener).toHaveBeenCalledTimes(1);
    });
  });

  describe('計算屬性', () => {
    beforeEach(() => {
      store = new Store({
        firstName: 'John',
        lastName: 'Doe'
      });
    });

    it('應該能夠註冊計算屬性', () => {
      store.computed('fullName', (state) => {
        return `${state.firstName} ${state.lastName}`;
      });
      
      expect(store.get('fullName')).toBe('John Doe');
    });

    it('計算屬性應該自動更新', () => {
      store.computed('fullName', (state) => {
        return `${state.firstName} ${state.lastName}`;
      });
      
      store.set('firstName', 'Jane');
      expect(store.get('fullName')).toBe('Jane Doe');
    });

    it('應該快取計算結果', () => {
      const compute = vi.fn((state) => {
        return `${state.firstName} ${state.lastName}`;
      });
      
      store.computed('fullName', compute);
      
      // 多次取得
      store.get('fullName');
      store.get('fullName');
      store.get('fullName');
      
      // 計算函式只應該被呼叫一次
      expect(compute).toHaveBeenCalledTimes(1);
    });

    it('狀態改變後應該重新計算', () => {
      const compute = vi.fn((state) => {
        return `${state.firstName} ${state.lastName}`;
      });
      
      store.computed('fullName', compute);
      
      store.get('fullName');
      store.set('firstName', 'Jane');
      store.get('fullName');
      
      expect(compute).toHaveBeenCalledTimes(2);
    });
  });

  describe('Actions', () => {
    beforeEach(() => {
      store = new Store({
        count: 0
      });
    });

    it('應該能夠註冊 action', () => {
      store.action('increment', (state) => {
        state.count += 1;
      });
      
      store.dispatch('increment');
      expect(store.get('count')).toBe(1);
    });

    it('應該能夠傳遞參數給 action', () => {
      store.action('incrementBy', (state, amount) => {
        state.count += amount;
      });
      
      store.dispatch('incrementBy', 5);
      expect(store.get('count')).toBe(5);
    });

    it('應該能夠執行非同步 action', async () => {
      store.action('incrementAsync', async (state, amount) => {
        await new Promise(resolve => setTimeout(resolve, 10));
        state.count += amount;
      });
      
      await store.dispatch('incrementAsync', 3);
      expect(store.get('count')).toBe(3);
    });

    it('不存在的 action 應該拋出錯誤', () => {
      expect(() => {
        store.dispatch('nonexistent');
      }).toThrow();
    });
  });

  describe('中介軟體', () => {
    it('應該能夠註冊中介軟體', () => {
      const middleware = vi.fn((action, state, next) => next());
      store.use(middleware);
      
      store.action('test', (state) => {
        state.count += 1;
      });
      
      store.dispatch('test');
      expect(middleware).toHaveBeenCalled();
    });

    it('中介軟體應該能夠攔截 action', () => {
      store.use((action, state, next) => {
        if (action === 'blocked') {
          return;
        }
        next();
      });
      
      store.action('blocked', (state) => {
        state.count += 1;
      });
      
      store.dispatch('blocked');
      expect(store.get('count')).toBe(0);
    });

    it('中介軟體應該能夠修改狀態', () => {
      store.use((action, state, next) => {
        console.log(`Action: ${action}`);
        next();
      });
      
      const consoleSpy = vi.spyOn(console, 'log');
      
      store.action('test', (state) => {
        state.count += 1;
      });
      
      store.dispatch('test');
      expect(consoleSpy).toHaveBeenCalledWith('Action: test');
    });

    it('應該能夠串聯多個中介軟體', () => {
      const order = [];
      
      store.use((action, state, next) => {
        order.push('middleware1-before');
        next();
        order.push('middleware1-after');
      });
      
      store.use((action, state, next) => {
        order.push('middleware2-before');
        next();
        order.push('middleware2-after');
      });
      
      store.action('test', () => {
        order.push('action');
      });
      
      store.dispatch('test');
      
      expect(order).toEqual([
        'middleware1-before',
        'middleware2-before',
        'action',
        'middleware2-after',
        'middleware1-after'
      ]);
    });
  });

  describe('持久化', () => {
    let mockStorage;

    beforeEach(() => {
      mockStorage = {};
      
      global.localStorage = {
        getItem: vi.fn((key) => mockStorage[key] || null),
        setItem: vi.fn((key, value) => {
          mockStorage[key] = value;
        }),
        removeItem: vi.fn((key) => {
          delete mockStorage[key];
        })
      };
      
      store = new Store({
        count: 0
      }, {
        persist: true,
        storageKey: 'test-store'
      });
    });

    it('應該從 storage 載入初始狀態', () => {
      mockStorage['test-store'] = JSON.stringify({ count: 5 });
      
      store = new Store({
        count: 0
      }, {
        persist: true,
        storageKey: 'test-store'
      });
      
      expect(store.get('count')).toBe(5);
    });

    it('狀態變化應該同步到 storage', () => {
      store.set('count', 10);
      
      expect(localStorage.setItem).toHaveBeenCalledWith(
        'test-store',
        JSON.stringify({ count: 10 })
      );
    });

    it('應該能夠選擇性持久化', () => {
      store = new Store({
        count: 0,
        temp: 'temporary'
      }, {
        persist: true,
        storageKey: 'test-store',
        persistKeys: ['count']
      });
      
      store.set('count', 5);
      store.set('temp', 'changed');
      
      const saved = JSON.parse(mockStorage['test-store']);
      expect(saved.count).toBe(5);
      expect(saved.temp).toBeUndefined();
    });
  });

  describe('錯誤處理', () => {
    it('取得不存在的狀態應該返回 undefined', () => {
      expect(store.get('nonexistent')).toBeUndefined();
    });

    it('訂閱不存在的狀態應該正常運作', () => {
      const listener = vi.fn();
      store.subscribe('nonexistent', listener);
      
      store.set('nonexistent', 'value');
      expect(listener).toHaveBeenCalled();
    });

    it('action 拋出錯誤應該被捕獲', () => {
      store.action('error', () => {
        throw new Error('Test error');
      });
      
      expect(() => {
        store.dispatch('error');
      }).toThrow('Test error');
    });
  });

  describe('效能', () => {
    it('大量狀態更新應該高效', () => {
      const start = Date.now();
      
      for (let i = 0; i < 1000; i++) {
        store.set('count', i);
      }
      
      const duration = Date.now() - start;
      expect(duration).toBeLessThan(100); // 應該在 100ms 內完成
    });

    it('大量訂閱者不應該明顯降低效能', () => {
      // 註冊 100 個訂閱者
      for (let i = 0; i < 100; i++) {
        store.subscribe(() => {});
      }
      
      const start = Date.now();
      store.set('count', 1);
      const duration = Date.now() - start;
      
      expect(duration).toBeLessThan(50);
    });
  });
});
