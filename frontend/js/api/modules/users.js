/**
 * 使用者 API 模組
 * 
 * 注意：大部分端點尚未在後端實作
 * 這些方法為未來實作預留介面
 */

import { apiClient } from '../client.js';

class UsersAPI {
  /**
   * 取得所有使用者
   * 注意：後端尚未實作此端點
   */
  async getAll(params = {}) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.get('/admin/users', { params });
  }

  /**
   * 取得單一使用者
   * 注意：後端尚未實作此端點
   */
  async getById(id) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.get(`/admin/users/${id}`);
  }

  /**
   * 建立使用者
   * 注意：後端尚未實作此端點，使用 auth/register 代替
   */
  async create(data) {
    console.warn('管理員建立使用者端點尚未實作，請使用註冊端點');
    return await apiClient.post('/auth/register', data);
  }

  /**
   * 更新使用者
   * 注意：後端尚未實作此端點
   */
  async update(id, data) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.put(`/admin/users/${id}`, data);
  }

  /**
   * 刪除使用者
   * 注意：後端尚未實作此端點
   */
  async delete(id) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.delete(`/admin/users/${id}`);
  }

  /**
   * 啟用使用者
   * 注意：後端尚未實作此端點
   */
  async activate(id) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.post(`/admin/users/${id}/activate`);
  }

  /**
   * 停用使用者
   * 注意：後端尚未實作此端點
   */
  async deactivate(id) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.post(`/admin/users/${id}/deactivate`);
  }

  /**
   * 重設使用者密碼
   * 注意：後端尚未實作此端點
   */
  async resetPassword(id, password) {
    console.warn('使用者管理端點尚未實作');
    return await apiClient.post(`/admin/users/${id}/reset-password`, { password });
  }

  /**
   * 取得角色列表
   * 使用已實作的角色端點
   */
  async getRoles() {
    return await apiClient.get('/api/v1/roles');
  }

  /**
   * 建立角色
   */
  async createRole(data) {
    return await apiClient.post('/api/v1/roles', data);
  }

  /**
   * 更新角色
   */
  async updateRole(id, data) {
    return await apiClient.put(`/api/v1/roles/${id}`, data);
  }

  /**
   * 刪除角色
   */
  async deleteRole(id) {
    return await apiClient.delete(`/api/v1/roles/${id}`);
  }

  /**
   * 取得角色權限
   */
  async getRolePermissions(id) {
    return await apiClient.get(`/api/v1/roles/${id}/permissions`);
  }

  /**
   * 更新角色權限
   */
  async updateRolePermissions(id, permissions) {
    return await apiClient.put(`/api/v1/roles/${id}/permissions`, { permissions });
  }
}

export const usersAPI = new UsersAPI();
