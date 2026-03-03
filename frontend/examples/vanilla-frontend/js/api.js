// API 通訊類別
class ApiClient {
    constructor() {
        this.baseURL = "http://localhost:8080/api";
        this.token = localStorage.getItem("auth_token");
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                "Content-Type": "application/json",
                ...options.headers,
            },
            ...options,
        };

        // 加入認證標頭
        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "請求失敗");
            }

            return data;
        } catch (error) {
            console.error("API 請求錯誤:", error);
            throw error;
        }
    }

    // 認證相關 API
    async login(email, password) {
        const data = await this.request("/auth/login", {
            method: "POST",
            body: JSON.stringify({ email, password }),
        });

        if (data.success && data.data.access_token) {
            this.token = data.data.access_token;
            localStorage.setItem("auth_token", this.token);
        }

        return data;
    }

    async logout() {
        try {
            await this.request("/auth/logout", { method: "POST" });
        } finally {
            this.token = null;
            localStorage.removeItem("auth_token");
        }
    }

    // 公告相關 API
    async getPosts(page = 1, limit = 10) {
        // 模擬 API 響應用於演示
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    success: true,
                    data: {
                        posts: [
                            {
                                id: 1,
                                title: "歡迎使用 AlleyNote 公告系統",
                                content:
                                    "這是一個使用純 HTML + CSS + JavaScript 實作的前端範例，展示了現代 Web 開發的能力。",
                                author_name: "系統管理員",
                                created_at: "2025-09-03T10:30:00Z",
                            },
                            {
                                id: 2,
                                title: "前端框架比較",
                                content:
                                    "我們比較了 Vite、Webpack、Next.js、Nuxt.js 以及純 JavaScript 的優缺點。",
                                author_name: "開發團隊",
                                created_at: "2025-09-03T09:15:00Z",
                            },
                            {
                                id: 3,
                                title: "DDD 架構設計原則",
                                content:
                                    "本專案採用 Domain-Driven Design 原則，強調高內聚、低耦合的設計。",
                                author_name: "架構師",
                                created_at: "2025-09-03T08:45:00Z",
                            },
                        ],
                        pagination: {
                            current_page: page,
                            total_pages: 1,
                            total_count: 3,
                        },
                    },
                });
            }, 500); // 模擬網路延遲
        });
    }

    async createPost(postData) {
        return this.request("/posts", {
            method: "POST",
            body: JSON.stringify(postData),
        });
    }

    async updatePost(id, postData) {
        return this.request(`/posts/${id}`, {
            method: "PUT",
            body: JSON.stringify(postData),
        });
    }

    async deletePost(id) {
        return this.request(`/posts/${id}`, {
            method: "DELETE",
        });
    }
}

export default ApiClient;
