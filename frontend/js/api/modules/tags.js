/**
 * 標籤 API 模組
 */

import { apiClient } from "../client.js";

class TagsAPI {
  /**
   * 取得所有標籤
   */
  async getAll(params = {}) {
    return await apiClient.get("/tags", { params });
  }

  /**
   * 取得單一標籤
   */
  async get(id) {
    return await apiClient.get(`/tags/${id}`);
  }

  /**
   * 建立標籤
   */
  async create(data) {
    return await apiClient.post("/tags", data);
  }

  /**
   * 更新標籤
   */
  async update(id, data) {
    return await apiClient.put(`/tags/${id}`, data);
  }

  /**
   * 刪除標籤
   */
  async delete(id) {
    return await apiClient.delete(`/tags/${id}`);
  }
}

export const tagsAPI = new TagsAPI();
