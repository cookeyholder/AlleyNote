import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';

/**
 * 文章 API
 */
export const postsAPI = {
  /**
   * 取得文章列表
   */
  async list(params = {}) {
    const response = await apiClient.get(API_ENDPOINTS.POSTS.LIST, { params });
    return response.data;
  },

  /**
   * 取得單一文章
   */
  async get(id) {
    const response = await apiClient.get(API_ENDPOINTS.POSTS.DETAIL(id));
    return response.data;
  },

  /**
   * 建立文章
   */
  async create(data) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.CREATE, data);
    return response.data;
  },

  /**
   * 更新文章
   */
  async update(id, data) {
    const response = await apiClient.put(API_ENDPOINTS.POSTS.UPDATE(id), data);
    return response.data;
  },

  /**
   * 刪除文章
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.POSTS.DELETE(id));
  },

  /**
   * 發布文章
   */
  async publish(id) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.PUBLISH(id));
    return response.data;
  },

  /**
   * 將文章改為草稿
   */
  async draft(id) {
    const response = await apiClient.post(API_ENDPOINTS.POSTS.DRAFT(id));
    return response.data;
  },
};
