/**
 * 文章 API 模組（舊版 - 相容性保留）
 * 
 * 注意：此檔案為舊版 API，建議使用 modules/posts.js
 * 保留此檔案以確保向後相容性
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
        // 修正：使用 PATCH 而不是 PUT
        return await apiClient.patch(`/posts/${id}/pin`);
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
