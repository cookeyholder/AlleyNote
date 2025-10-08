/**
 * 統計 API 模組
 */

import { apiClient } from './client.js';

export const statisticsApi = {
    async getDashboard() {
        return await apiClient.get('/statistics/dashboard');
    },

    async getPostStats(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/statistics/posts${queryString ? '?' + queryString : ''}`);
    },

    async getUserStats(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await apiClient.get(`/statistics/users${queryString ? '?' + queryString : ''}`);
    }
};
