/**
 * 使用者 API 模組（舊版 - 相容性保留）
 * 
 * 注意：此檔案為舊版 API，建議使用 modules/users.js
 * 保留此檔案以確保向後相容性
 * 大部分端點尚未在後端實作
 */

import { apiClient } from './client.js';

export const usersApi = {
    async getAll(params = {}) {
        console.warn('使用者管理端點尚未實作');
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/admin/users${queryString ? '?' + queryString : ''}`);
    },

    async getById(id) {
        console.warn('使用者管理端點尚未實作');
        return await apiClient.get(`/admin/users/${id}`);
    },

    async create(data) {
        console.warn('管理員建立使用者端點尚未實作，使用註冊端點');
        return await apiClient.post('/auth/register', data);
    },

    async update(id, data) {
        console.warn('使用者管理端點尚未實作');
        return await apiClient.put(`/admin/users/${id}`, data);
    },

    async delete(id) {
        console.warn('使用者管理端點尚未實作');
        return await apiClient.delete(`/admin/users/${id}`);
    },

    async getRoles() {
        // 修正：使用完整的 API 路徑
        return await apiClient.get('/api/v1/roles');
    },

    async createRole(data) {
        return await apiClient.post('/api/v1/roles', data);
    },

    async updateRole(id, data) {
        return await apiClient.put(`/api/v1/roles/${id}`, data);
    },

    async deleteRole(id) {
        return await apiClient.delete(`/api/v1/roles/${id}`);
    },

    async getRolePermissions(id) {
        return await apiClient.get(`/api/v1/roles/${id}/permissions`);
    },

    async updateRolePermissions(id, permissions) {
        return await apiClient.put(`/api/v1/roles/${id}/permissions`, { permissions });
    }
};
