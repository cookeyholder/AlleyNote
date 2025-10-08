/**
 * 文章 API 模組
 */

import { apiClient } from './client.js';

export const postsApi = {
    async getAll(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/posts${queryString ? '?' + queryString : ''}`);
    },

    async getById(id) {
        return await apiClient.get(`/posts/${id}`);
    },

    async create(data) {
        return await apiClient.post('/posts', data);
    },

    async update(id, data) {
        return await apiClient.put(`/posts/${id}`, data);
    },

    async delete(id) {
        return await apiClient.delete(`/posts/${id}`);
    },

    async togglePin(id) {
        return await apiClient.put(`/posts/${id}/pin`);
    },

    async getPublicPosts(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/public/posts${queryString ? '?' + queryString : ''}`);
    },

    async getPublicPost(id) {
        return await apiClient.get(`/public/posts/${id}`);
    }
};
