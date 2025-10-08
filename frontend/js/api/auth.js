/**
 * 認證 API 模組
 */

import { apiClient } from './client.js';

export const authApi = {
    async login(username, password) {
        const response = await apiClient.post('/auth/login', {
            username,
            password
        });
        
        if (response.token) {
            apiClient.setToken(response.token);
        }
        
        return response;
    },

    async logout() {
        try {
            await apiClient.post('/auth/logout');
        } finally {
            apiClient.setToken(null);
        }
    },

    async getCurrentUser() {
        return await apiClient.get('/auth/user');
    },

    async updateProfile(data) {
        return await apiClient.put('/auth/user', data);
    },

    async changePassword(currentPassword, newPassword) {
        return await apiClient.post('/auth/password/change', {
            current_password: currentPassword,
            new_password: newPassword
        });
    },

    isAuthenticated() {
        return !!apiClient.getToken();
    }
};
