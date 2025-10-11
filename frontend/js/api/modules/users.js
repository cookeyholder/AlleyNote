/**
 * 使用者 API 模組
 */

import { apiClient } from '../client.js';

class UsersAPI {
  /**
   * 取得所有使用者
   */
  async getAll(params = {}) {
    return await apiClient.get('/users', { params });
  }

  /**
   * 取得單一使用者
   */
  async getById(id) {
    return await apiClient.get(`/users/${id}`);
  }

  /**
   * 建立使用者（管理員）
   */
  async create(data) {
    return await apiClient.post('/users', data);
  }

  /**
   * 更新使用者
   */
  async update(id, data) {
    return await apiClient.put(`/users/${id}`, data);
  }

  /**
   * 刪除使用者
   */
  async delete(id) {
    return await apiClient.delete(`/users/${id}`);
  }

  /**
   * 啟用使用者
   */
  async activate(id) {
    return await apiClient.post(`/users/${id}/activate`);
  }

  /**
   * 停用使用者
   */
  async deactivate(id) {
    return await apiClient.post(`/users/${id}/deactivate`);
  }

  /**
   * 重設使用者密碼
   */
  async resetPassword(id, password) {
    return await apiClient.post(`/users/${id}/reset-password`, { password });
  }

  /**
   * 取得角色列表
   */
  async getRoles() {
    return await apiClient.get('/roles');
  }

  /**
   * 建立角色
   */
  async createRole(data) {
    return await apiClient.post('/roles', data);
  }

  /**
   * 更新角色
   */
  async updateRole(id, data) {
    return await apiClient.put(`/roles/${id}`, data);
  }

  /**
   * 刪除角色
   */
  async deleteRole(id) {
    return await apiClient.delete(`/roles/${id}`);
  }

  /**
   * 取得角色權限
   */
  async getRolePermissions(id) {
    return await apiClient.get(`/roles/${id}/permissions`);
  }

  /**
   * 更新角色權限
   */
  async updateRolePermissions(id, permissions) {
    return await apiClient.put(`/roles/${id}/permissions`, { permissions });
  }
}

export const usersAPI = new UsersAPI();
