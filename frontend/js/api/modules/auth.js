/**
 * 認證 API 模組
 */

import { apiClient } from "../client.js";
import { globalActions } from "../../store/globalStore.js";
import { storage } from "../../utils/storage.js";

class AuthAPI {
    /**
     * 登入
     */
    async login(credentials) {
        const response = await apiClient.post("/auth/login", credentials);

        // 儲存 Token
        if (response.access_token) {
            apiClient.setAuthToken(response.access_token);
        }

        // 儲存 Refresh Token（如果有的話）
        if (response.refresh_token) {
            storage.set("refresh_token", response.refresh_token);
        }

        // 回傳回應，讓呼叫方處理使用者資訊
        return response;
    }

    /**
     * 登出
     */
    async logout() {
        try {
            const accessToken = apiClient.getAuthToken();
            const refreshToken = storage.get("refresh_token");

            await apiClient.post(
                "/auth/logout",
                {
                    access_token: accessToken,
                    refresh_token: refreshToken,
                },
                { silent: true }
            );
        } catch (error) {
            console.warn("Logout API call failed:", error);
        } finally {
            // 無論 API 呼叫是否成功，都清除本地狀態
            apiClient.removeAuthToken();
            storage.remove("refresh_token");
            globalActions.clearUser();
        }
    }

    /**
     * 取得當前使用者資訊
     */
    async me() {
        const response = await apiClient.get("/auth/me");
        // API 回應結構: { success: true, data: { user: {...}, token_info: {...} } }
        return response.data?.user || response.user || response;
    }

    /**
     * 檢查是否已認證
     */
    isAuthenticated() {
        return !!apiClient.getAuthToken();
    }

    /**
     * 忘記密碼
     */
    async forgotPassword(email) {
        return await apiClient.post("/auth/forgot-password", { email });
    }

    /**
     * 重設密碼
     */
    async resetPassword(token, password, passwordConfirmation) {
        return await apiClient.post("/auth/reset-password", {
            token,
            password,
            password_confirmation: passwordConfirmation,
        });
    }

    /**
     * 變更密碼
     */
    async changePassword(
        currentPassword,
        newPassword,
        newPasswordConfirmation
    ) {
        return await apiClient.post("/auth/change-password", {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: newPasswordConfirmation,
        });
    }

    /**
     * 更新個人資料
     */
    async updateProfile(data) {
        const response = await apiClient.put("/auth/profile", data);

        // 更新儲存的使用者資訊
        if (response.user) {
            globalActions.setUser(response.user);
        }

        return response;
    }

    /**
     * 刷新 Token
     */
    async refresh() {
        const refreshToken = storage.get("refresh_token");
        if (!refreshToken) {
            throw new Error("無可用的 refresh token");
        }

        const response = await apiClient.post("/auth/refresh", {
            refresh_token: refreshToken,
        });

        if (response.access_token) {
            apiClient.setAuthToken(response.access_token);
        }

        if (response.refresh_token) {
            storage.set("refresh_token", response.refresh_token);
        }

        return response;
    }
}

export const authAPI = new AuthAPI();
