/**
 * 文章 API 模組
 */

import { apiClient } from '../client.js';
import { globalGetters } from '../../store/globalStore.js';

class PostsAPI {
  /**
   * 取得文章列表
   * 注意：後端 /posts 端點可以處理認證和非認證請求
   */
  async list(params = {}) {
    return await apiClient.get('/posts', { params });
  }

  /**
   * 取得單一文章
   */
  async get(id) {
    return await apiClient.get(`/posts/${id}`);
  }

  /**
   * 取得所有文章（別名，為了相容性）
   */
  async getAll(params = {}) {
    return await this.list(params);
  }

  /**
   * 取得單一文章（別名，為了相容性）
   */
  async getById(id) {
    return await this.get(id);
  }

  /**
   * 取得公開文章列表（別名，為了相容性）
   */
  async getPublicPosts(params = {}) {
    return await this.list(params);
  }

  /**
   * 取得單一公開文章（別名，為了相容性）
   */
  async getPublicPost(id) {
    return await this.get(id);
  }

  /**
   * 建立文章
   */
  async create(data) {
    return await apiClient.post('/posts', data);
  }

  /**
   * 更新文章
   */
  async update(id, data) {
    return await apiClient.put(`/posts/${id}`, data);
  }

  /**
   * 刪除文章
   */
  async delete(id) {
    return await apiClient.delete(`/posts/${id}`);
  }

  /**
   * 置頂文章
   */
  async pin(id) {
    return await apiClient.patch(`/posts/${id}/pin`);
  }

  /**
   * 取消置頂文章
   * 注意：後端尚未實作此端點，使用 unpin 參數
   */
  async unpin(id) {
    // TODO: 待後端實作 DELETE /posts/{id}/pin 或 PATCH /posts/{id}/unpin
    console.warn('取消置頂功能尚未完全實作，嘗試使用 pin 端點');
    return await apiClient.patch(`/posts/${id}/pin`, { pinned: false });
  }

  /**
   * 發布文章
   * 注意：後端尚未實作獨立的發布端點，使用 update 端點
   */
  async publish(id) {
    console.warn('發布功能尚未實作為獨立端點，使用 update 端點');
    return await this.update(id, { status: 'published' });
  }

  /**
   * 取消發布文章
   * 注意：後端尚未實作獨立的取消發布端點，使用 update 端點
   */
  async unpublish(id) {
    console.warn('取消發布功能尚未實作為獨立端點，使用 update 端點');
    return await this.update(id, { status: 'draft' });
  }

  /**
   * 記錄文章瀏覽
   */
  async recordView(id) {
    return await apiClient.post(`/posts/${id}/view`);
  }

  /**
   * 取得文章附件列表
   */
  async getAttachments(postId) {
    return await apiClient.get(`/posts/${postId}/attachments`);
  }

  /**
   * 上傳附件
   */
  async uploadAttachment(postId, file) {
    const formData = new FormData();
    formData.append('file', file);
    
    return await apiClient.post(`/posts/${postId}/attachments`, formData);
  }

  /**
   * 刪除附件
   */
  async deleteAttachment(attachmentId) {
    return await apiClient.delete(`/attachments/${attachmentId}`);
  }

  /**
   * 下載附件
   */
  async downloadAttachment(attachmentId) {
    return await apiClient.get(`/attachments/${attachmentId}/download`);
  }
}

export const postsAPI = new PostsAPI();
