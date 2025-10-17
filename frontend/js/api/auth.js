/**
 * 認證 API 模組（舊版 - 相容性保留）
 *
 * 注意：此檔案為舊版 API，建議使用 modules/auth.js
 * 保留此檔案以確保向後相容性
 */

import { apiClient } from "./client.js";

export const authApi = {
    async login(email, password) {
        const response = await apiClient.post("/auth/login", {
            email, // 修正：使用 email 而不是 username
            password,
        });

        if (response.access_token) {
            apiClient.setToken(response.access_token);
        }

        return response;
    },

    async logout() {
        try {
            await apiClient.post("/auth/logout");
        } finally {
            apiClient.setToken(null);
        }
    },

    async getCurrentUser() {
        // 修正：使用 /auth/me 而不是 /auth/user
        const response = await apiClient.get("/auth/me");
        return response.data?.user || response.user || response;
    },

    async updateProfile(data) {
        // 注意：此端點尚未實作
        console.warn("個人資料更新端點尚未實作");
        return await apiClient.put("/auth/profile", data);
    },

    async changePassword(currentPassword, newPassword) {
        // 注意：此端點尚未實作
        console.warn("密碼變更端點尚未實作");
        return await apiClient.post("/auth/change-password", {
            current_password: currentPassword,
            new_password: newPassword,
        });
    },

    isAuthenticated() {
        return !!apiClient.getToken();
    },
};
