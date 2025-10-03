import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';

/**
 * 使用者 API
 */
export const usersAPI = {
  /**
   * 取得使用者列表
   */
  async list(params = {}) {
    const response = await apiClient.get(API_ENDPOINTS.USERS.LIST, { params });
    return response.data;
  },

  /**
   * 取得單一使用者
   */
  async get(id) {
    const response = await apiClient.get(API_ENDPOINTS.USERS.DETAIL(id));
    return response.data;
  },

  /**
   * 建立使用者
   */
  async create(data) {
    const response = await apiClient.post(API_ENDPOINTS.USERS.CREATE, data);
    return response.data;
  },

  /**
   * 更新使用者
   */
  async update(id, data) {
    const response = await apiClient.put(API_ENDPOINTS.USERS.UPDATE(id), data);
    return response.data;
  },

  /**
   * 刪除使用者
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.USERS.DELETE(id));
  },
};
