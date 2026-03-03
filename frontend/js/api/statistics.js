/**
 * 統計 API 模組（舊版 - 相容性保留）
 * 
 * 注意：此檔案為舊版 API，建議使用 modules/statistics.js
 * 保留此檔案以確保向後相容性
 */

import { apiClient } from './client.js';

export const statisticsApi = {
    async getDashboard() {
        // 修正：使用 /statistics/overview
        return await apiClient.get('/statistics/overview');
    },

    async getPostStats(params = {}) {
        // 修正：使用 /statistics/posts
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/statistics/posts${queryString ? '?' + queryString : ''}`);
    },

    async getUserStats(params = {}) {
        // 修正：使用 /statistics/users
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/statistics/users${queryString ? '?' + queryString : ''}`);
    }
};
