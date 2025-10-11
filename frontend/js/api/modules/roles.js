/**
 * 角色 API 模組
 */

import { apiClient } from '../client.js';

class RolesAPI {
  /**
   * 取得所有角色
   */
  async getAll(params = {}) {
    return await apiClient.get('/roles', { params });
  }

  /**
   * 取得單一角色（包含權限）
   */
  async get(id) {
    return await apiClient.get(`/roles/${id}`);
  }

  /**
   * 建立角色
   */
  async create(data) {
    return await apiClient.post('/roles', data);
  }

  /**
   * 更新角色
   */
  async update(id, data) {
    return await apiClient.put(`/roles/${id}`, data);
  }

  /**
   * 刪除角色
   */
  async delete(id) {
    return await apiClient.delete(`/roles/${id}`);
  }

  /**
   * 更新角色的權限
   */
  async updatePermissions(id, permissionIds) {
    return await apiClient.put(`/roles/${id}/permissions`, {
      permission_ids: permissionIds
    });
  }

  /**
   * 取得所有權限
   */
  async getPermissions() {
    return await apiClient.get('/permissions');
  }

  /**
   * 取得分組的權限
   */
  async getGroupedPermissions() {
    return await apiClient.get('/permissions/grouped');
  }
}

export const rolesAPI = new RolesAPI();
