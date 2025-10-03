import { describe, it, expect, beforeEach, vi } from 'vitest';
import { tokenManager } from '../../utils/tokenManager.js';

describe('TokenManager', () => {
  beforeEach(() => {
    sessionStorage.clear();
    tokenManager.refreshPromise = null;
  });

  describe('setToken', () => {
    it('應該儲存 Token 到 sessionStorage', () => {
      const token = 'test-token';
      const expiresIn = 3600;

      tokenManager.setToken(token, expiresIn);

      const stored = JSON.parse(sessionStorage.getItem('jwt_token'));
      expect(stored).toBeDefined();
      expect(stored.token).toBe(token);
      expect(stored.expiresAt).toBeGreaterThan(Date.now());
    });

    it('應該設定正確的過期時間', () => {
      const token = 'test-token';
      const expiresIn = 3600; // 1 hour
      const now = Date.now();

      tokenManager.setToken(token, expiresIn);

      const stored = JSON.parse(sessionStorage.getItem('jwt_token'));
      const expectedExpiry = now + expiresIn * 1000;

      // 允許 100ms 的誤差
      expect(stored.expiresAt).toBeGreaterThanOrEqual(expectedExpiry - 100);
      expect(stored.expiresAt).toBeLessThanOrEqual(expectedExpiry + 100);
    });
  });

  describe('getToken', () => {
    it('應該回傳儲存的 Token', () => {
      const token = 'test-token';
      tokenManager.setToken(token);

      expect(tokenManager.getToken()).toBe(token);
    });

    it('當沒有 Token 時應該回傳 null', () => {
      expect(tokenManager.getToken()).toBeNull();
    });

    it('當 Token 資料損壞時應該回傳 null', () => {
      sessionStorage.setItem('jwt_token', 'invalid-json');
      expect(tokenManager.getToken()).toBeNull();
    });
  });

  describe('removeToken', () => {
    it('應該從 sessionStorage 移除 Token', () => {
      tokenManager.setToken('test-token');
      expect(tokenManager.getToken()).toBe('test-token');

      tokenManager.removeToken();
      expect(tokenManager.getToken()).toBeNull();
    });

    it('應該清除 refreshPromise', () => {
      tokenManager.refreshPromise = Promise.resolve();
      tokenManager.removeToken();
      expect(tokenManager.refreshPromise).toBeNull();
    });
  });

  describe('isValid', () => {
    it('當 Token 尚未過期時應該回傳 true', () => {
      tokenManager.setToken('test-token', 3600); // 1 hour
      expect(tokenManager.isValid()).toBe(true);
    });

    it('當 Token 已過期時應該回傳 false', () => {
      // 設定一個已過期的 Token
      const expiredData = {
        token: 'test-token',
        expiresAt: Date.now() - 1000, // 1 秒前過期
      };
      sessionStorage.setItem('jwt_token', JSON.stringify(expiredData));

      expect(tokenManager.isValid()).toBe(false);
    });

    it('當沒有 Token 時應該回傳 false', () => {
      expect(tokenManager.isValid()).toBe(false);
    });
  });

  describe('shouldRefresh', () => {
    it('當 Token 即將過期時應該回傳 true', () => {
      // 設定一個 4 分鐘後過期的 Token（小於預設的 5 分鐘閾值）
      tokenManager.setToken('test-token', 240);
      expect(tokenManager.shouldRefresh()).toBe(true);
    });

    it('當 Token 還有很長時間才過期時應該回傳 false', () => {
      // 設定一個 1 小時後過期的 Token
      tokenManager.setToken('test-token', 3600);
      expect(tokenManager.shouldRefresh()).toBe(false);
    });

    it('當 Token 已過期時應該回傳 false', () => {
      const expiredData = {
        token: 'test-token',
        expiresAt: Date.now() - 1000,
      };
      sessionStorage.setItem('jwt_token', JSON.stringify(expiredData));

      expect(tokenManager.shouldRefresh()).toBe(false);
    });

    it('當沒有 Token 時應該回傳 false', () => {
      expect(tokenManager.shouldRefresh()).toBe(false);
    });
  });

  describe('refreshToken', () => {
    it('應該呼叫 refresh API 並更新 Token', async () => {
      // 設定初始 Token
      tokenManager.setToken('old-token', 240);

      // Mock fetch
      const mockResponse = {
        ok: true,
        json: async () => ({
          success: true,
          data: {
            token: 'new-token',
            expires_in: 3600,
          },
        }),
      };
      global.fetch = vi.fn().mockResolvedValue(mockResponse);

      await tokenManager.refreshToken();

      expect(tokenManager.getToken()).toBe('new-token');
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/auth/refresh'),
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            Authorization: 'Bearer old-token',
          }),
        })
      );
    });

    it('當 refresh 失敗時應該移除 Token', async () => {
      tokenManager.setToken('old-token', 240);

      const mockResponse = {
        ok: false,
      };
      global.fetch = vi.fn().mockResolvedValue(mockResponse);

      await expect(tokenManager.refreshToken()).rejects.toThrow();
      expect(tokenManager.getToken()).toBeNull();
    });

    it('應該防止重複的 refresh 請求', async () => {
      tokenManager.setToken('old-token', 240);

      const mockResponse = {
        ok: true,
        json: async () => ({
          success: true,
          data: {
            token: 'new-token',
            expires_in: 3600,
          },
        }),
      };
      global.fetch = vi.fn().mockResolvedValue(mockResponse);

      // 同時發起多個 refresh 請求
      const promises = [
        tokenManager.refreshToken(),
        tokenManager.refreshToken(),
        tokenManager.refreshToken(),
      ];

      await Promise.all(promises);

      // fetch 應該只被呼叫一次
      expect(global.fetch).toHaveBeenCalledTimes(1);
    });

    it('當沒有 Token 時應該拋出錯誤', async () => {
      await expect(tokenManager.refreshToken()).rejects.toThrow('No token to refresh');
    });
  });
});
