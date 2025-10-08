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
    return response; // 直接回傳完整回應物件，包含 success, data, pagination
  },

  /**
   * 取得單一使用者
   */
  async get(id) {
    const response = await apiClient.get(API_ENDPOINTS.USERS.DETAIL(id));
    return response; // 直接回傳完整回應物件
  },

  /**
   * 建立使用者
   */
  async create(data) {
    const response = await apiClient.post(API_ENDPOINTS.USERS.CREATE, data);
    return response; // 直接回傳完整回應物件
  },

  /**
   * 更新使用者
   */
  async update(id, data) {
    const response = await apiClient.put(API_ENDPOINTS.USERS.UPDATE(id), data);
    return response; // 直接回傳完整回應物件
  },

  /**
   * 刪除使用者
   */
  async delete(id) {
    await apiClient.delete(API_ENDPOINTS.USERS.DELETE(id));
  },

  /**
   * 分配角色給使用者
   */
  async assignRoles(id, roleIds) {
    const response = await apiClient.put(`/api/users/${id}/roles`, { role_ids: roleIds });
    return response; // 直接回傳完整回應物件
  }
};

/**
 * 角色管理 API
 */
export const rolesAPI = {
  /**
   * 取得角色列表
   */
  async list() {
    const response = await apiClient.get('/api/roles');
    return response; // 直接回傳完整回應物件
  },

  /**
   * 取得單一角色（包含權限）
   */
  async get(id) {
    const response = await apiClient.get(`/api/roles/${id}`);
    return response; // 直接回傳完整回應物件
  },

  /**
   * 建立角色
   */
  async create(data) {
    const response = await apiClient.post('/api/roles', data);
    return response; // 直接回傳完整回應物件
  },

  /**
   * 更新角色
   */
  async update(id, data) {
    const response = await apiClient.put(`/api/roles/${id}`, data);
    return response; // 直接回傳完整回應物件
  },

  /**
   * 刪除角色
   */
  async delete(id) {
    await apiClient.delete(`/api/roles/${id}`);
  },

  /**
   * 更新角色的權限
   */
  async updatePermissions(id, permissionIds) {
    const response = await apiClient.put(`/api/roles/${id}/permissions`, { permission_ids: permissionIds });
    return response; // 直接回傳完整回應物件
  }
};

/**
 * 權限 API
 */
export const permissionsAPI = {
  /**
   * 取得所有權限
   */
  async list() {
    const response = await apiClient.get('/api/permissions');
    return response; // 直接回傳完整回應物件
  },

  /**
   * 取得所有權限（按資源分組）
   */
  async listGrouped() {
    const response = await apiClient.get('/api/permissions/grouped');
    return response; // 直接回傳完整回應物件
  }
};

