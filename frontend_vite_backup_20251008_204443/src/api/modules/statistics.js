import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';

/**
 * 統計 API
 */
export const statisticsAPI = {
  /**
   * 取得總覽統計
   */
  async overview() {
    const response = await apiClient.get(API_ENDPOINTS.STATISTICS.OVERVIEW);
    return response.data;
  },

  /**
   * 取得文章統計
   */
  async posts(params = {}) {
    const response = await apiClient.get(API_ENDPOINTS.STATISTICS.POSTS, { params });
    return response.data;
  },

  /**
   * 取得瀏覽量統計
   */
  async views(params = {}) {
    const response = await apiClient.get(API_ENDPOINTS.STATISTICS.VIEWS, { params });
    return response.data;
  },
};
