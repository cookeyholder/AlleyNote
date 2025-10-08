/**
 * API 客戶端模組
 * 處理所有與後端 API 的通訊
 */

class ApiClient {
    constructor() {
        this.baseURL = this.getBaseURL();
        this.token = this.getToken();
    }

    getBaseURL() {
        // 根據環境設定 API 基礎 URL
        const protocol = window.location.protocol;
        const hostname = window.location.hostname;
        const port = window.location.port ? `:${window.location.port}` : '';
        return `${protocol}//${hostname}:8080/api`;
    }

    getToken() {
        return localStorage.getItem('token');
    }

    setToken(token) {
        if (token) {
            localStorage.setItem('token', token);
            this.token = token;
        } else {
            localStorage.removeItem('token');
            this.token = null;
        }
    }

    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const config = {
            method,
            headers,
            ...options
        };

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            
            // 處理 401 未授權
            if (response.status === 401) {
                this.setToken(null);
                window.location.href = '/';
                throw new Error('未授權，請重新登入');
            }

            // 處理其他錯誤狀態
            if (!response.ok) {
                const error = await response.json().catch(() => ({
                    message: '請求失敗'
                }));
                throw new Error(error.message || '請求失敗');
            }

            return await response.json();
        } catch (error) {
            console.error('API 請求錯誤:', error);
            throw error;
        }
    }

    async get(endpoint, options = {}) {
        return this.request('GET', endpoint, null, options);
    }

    async post(endpoint, data, options = {}) {
        return this.request('POST', endpoint, data, options);
    }

    async put(endpoint, data, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }

    async delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }

    async uploadFile(endpoint, file, additionalData = {}) {
        const formData = new FormData();
        formData.append('file', file);
        
        Object.keys(additionalData).forEach(key => {
            formData.append(key, additionalData[key]);
        });

        const headers = {};
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'POST',
                headers,
                body: formData
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({
                    message: '上傳失敗'
                }));
                throw new Error(error.message || '上傳失敗');
            }

            return await response.json();
        } catch (error) {
            console.error('檔案上傳錯誤:', error);
            throw error;
        }
    }
}

// 匯出單例
export const apiClient = new ApiClient();
