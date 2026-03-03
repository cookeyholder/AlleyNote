/**
 * 統計 API 模組
 */

import { apiClient } from '../client.js';

class StatisticsAPI {
  /**
   * 取得統計概覽（儀表板）
   */
  async getDashboard() {
    // 使用統計概覽端點
    return await apiClient.get('/statistics/overview');
  }

  /**
   * 取得統計概覽
   */
  async getOverview() {
    return await apiClient.get('/statistics/overview');
  }

  /**
   * 取得文章統計資料
   */
  async getPosts(params = {}) {
    return await apiClient.get('/statistics/posts', { params });
  }

  /**
   * 取得使用者統計資料
   */
  async getUsers(params = {}) {
    return await apiClient.get('/statistics/users', { params });
  }

  /**
   * 取得來源統計資料
   */
  async getSources(params = {}) {
    return await apiClient.get('/statistics/sources', { params });
  }

  /**
   * 取得熱門內容統計
   */
  async getPopular(params = {}) {
    return await apiClient.get('/statistics/popular', { params });
  }

  /**
   * 取得系統統計資料
   * 注意：此端點尚未完全實作
   */
  async getSystem() {
    console.warn('系統統計端點尚未完全實作');
    // 可以組合多個端點的資料
    return await this.getOverview();
  }

  /**
   * 刷新統計資料（需管理員權限）
   */
  async refresh() {
    return await apiClient.post('/admin/statistics/refresh');
  }

  /**
   * 清除統計快取（需管理員權限）
   */
  async clearCache() {
    return await apiClient.delete('/admin/statistics/cache');
  }

  /**
   * 統計系統健康檢查（需管理員權限）
   */
  async checkHealth() {
    return await apiClient.get('/admin/statistics/health');
  }
}

export const statisticsAPI = new StatisticsAPI();
