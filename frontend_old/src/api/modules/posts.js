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
    return await apiClient.get(API_ENDPOINTS.POSTS.LIST, { params });
  },

  /**
   * 取得單一文章
   */
  async get(id) {
    return await apiClient.get(API_ENDPOINTS.POSTS.DETAIL(id));
  },

  /**
   * 建立文章
   */
  async create(data) {
    return await apiClient.post(API_ENDPOINTS.POSTS.CREATE, data);
  },

  /**
   * 更新文章
   */
  async update(id, data) {
    return await apiClient.put(API_ENDPOINTS.POSTS.UPDATE(id), data);
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
    return await apiClient.post(API_ENDPOINTS.POSTS.PUBLISH(id));
  },

  /**
   * 將文章改為草稿
   */
  async draft(id) {
    return await apiClient.post(API_ENDPOINTS.POSTS.DRAFT(id));
  },
};
