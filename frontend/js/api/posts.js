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
        // 公開文章使用相同的 /posts API，但預設只取已發布的文章
        const publicParams = {
            status: 'published',
            ...params
        };
        const queryString = new URLSearchParams(publicParams).toString();
        return await apiClient.get(`/posts${queryString ? '?' + queryString : ''}`);
    },

    async getPublicPost(id) {
        // 公開文章詳情使用相同的 /posts/:id API
        return await apiClient.get(`/posts/${id}`);
    }
};
