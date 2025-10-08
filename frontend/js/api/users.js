/**
 * 使用者 API 模組
 */

import { apiClient } from './client.js';

export const usersApi = {
    async getAll(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/users${queryString ? '?' + queryString : ''}`);
    },

    async getById(id) {
        return await apiClient.get(`/users/${id}`);
    },

    async create(data) {
        return await apiClient.post('/users', data);
    },

    async update(id, data) {
        return await apiClient.put(`/users/${id}`, data);
    },

    async delete(id) {
        return await apiClient.delete(`/users/${id}`);
    },

    async getRoles() {
        return await apiClient.get('/roles');
    },

    async createRole(data) {
        return await apiClient.post('/roles', data);
    },

    async updateRole(id, data) {
        return await apiClient.put(`/roles/${id}`, data);
    },

    async deleteRole(id) {
        return await apiClient.delete(`/roles/${id}`);
    },

    async getRolePermissions(id) {
        return await apiClient.get(`/roles/${id}/permissions`);
    },

    async updateRolePermissions(id, permissions) {
        return await apiClient.put(`/roles/${id}/permissions`, { permissions });
    }
};
