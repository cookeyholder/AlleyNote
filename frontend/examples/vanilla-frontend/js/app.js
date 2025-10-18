import ApiClient from "./api.js";
import { PostList } from "./components/PostList.js";

// 應用程式主要類別
class AlleyNoteApp {
    constructor() {
        this.apiClient = new ApiClient(this.getApiBaseUrl());
        this.postList = null;
        this.isAuthenticated = false;

        this.init();
    }

    // 取得 API 基礎 URL
    getApiBaseUrl() {
        // 可以根據環境變數或配置檔案來決定
        return window.location.hostname === "localhost"
            ? "http://localhost:8080/api"
            : "/api";
    }

    // 初始化應用程式
    async init() {
        this.setupEventListeners();
        await this.checkAuthentication();
        this.initializeComponents();
        await this.loadInitialData();
    }

    // 設定事件監聽器
    setupEventListeners() {
        // 登入/登出按鈕
        const loginBtn = document.getElementById("login-btn");
        const logoutBtn = document.getElementById("logout-btn");
        const createBtn = document.getElementById("create-post-btn");
        const refreshBtn = document.getElementById("refresh-btn");

        if (loginBtn) {
            loginBtn.addEventListener("click", () => this.showLoginModal());
        }

        if (logoutBtn) {
            logoutBtn.addEventListener("click", () => this.logout());
        }

        if (createBtn) {
            createBtn.addEventListener("click", () =>
                this.showCreatePostModal()
            );
        }

        if (refreshBtn) {
            refreshBtn.addEventListener("click", () => this.refreshPosts());
        }

        // 搜尋功能
        const searchInput = document.getElementById("search-input");
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener("input", (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchPosts(e.target.value);
                }, 500);
            });
        }
    }

    // 檢查驗證狀態
    async checkAuthentication() {
        const token = localStorage.getItem("auth_token");
        if (token) {
            try {
                const response = await this.apiClient.verifyToken();
                if (response.success) {
                    this.isAuthenticated = true;
                    this.updateUIForAuthentication(true);
                    return;
                }
            } catch (error) {
                console.log("Token 驗證失敗:", error.message);
            }
        }

        this.isAuthenticated = false;
        this.updateUIForAuthentication(false);
    }

    // 更新 UI 以反映驗證狀態
    updateUIForAuthentication(isAuthenticated) {
        const loginBtn = document.getElementById("login-btn");
        const logoutBtn = document.getElementById("logout-btn");
        const createBtn = document.getElementById("create-post-btn");
        const authRequiredElements =
            document.querySelectorAll(".auth-required");

        if (isAuthenticated) {
            if (loginBtn) loginBtn.style.display = "none";
            if (logoutBtn) logoutBtn.style.display = "inline-block";
            if (createBtn) createBtn.style.display = "inline-block";
            authRequiredElements.forEach((el) => (el.style.display = "block"));
        } else {
            if (loginBtn) loginBtn.style.display = "inline-block";
            if (logoutBtn) logoutBtn.style.display = "none";
            if (createBtn) createBtn.style.display = "none";
            authRequiredElements.forEach((el) => (el.style.display = "none"));
        }
    }

    // 初始化組件
    initializeComponents() {
        const postsContainer = document.getElementById("posts-container");
        if (postsContainer) {
            this.postList = new PostList(postsContainer, this.apiClient);
        }
    }

    // 載入初始資料
    async loadInitialData() {
        if (this.postList) {
            await this.postList.loadPosts();
        }
    }

    // 重新整理公告
    async refreshPosts() {
        const refreshBtn = document.getElementById("refresh-btn");
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="spinner"></span> 載入中...';
        }

        try {
            if (this.postList) {
                await this.postList.loadPosts();
            }
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = "重新整理";
            }
        }
    }

    // 搜尋公告
    async searchPosts(query) {
        if (this.postList) {
            // 這裡可以實作搜尋邏輯
            console.log("搜尋:", query);
        }
    }

    // 顯示登入對話框
    showLoginModal() {
        // 簡單的登入表單
        const modal = document.createElement("div");
        modal.className = "modal-overlay";
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3>登入</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <form class="login-form">
                    <div class="form-group">
                        <label for="username">使用者名稱：</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">密碼：</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">登入</button>
                        <button type="button" class="btn btn-outline cancel-btn">取消</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // 綁定事件
        const closeBtn = modal.querySelector(".close-btn");
        const cancelBtn = modal.querySelector(".cancel-btn");
        const form = modal.querySelector(".login-form");

        const closeModal = () => modal.remove();

        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            await this.handleLogin(form.username.value, form.password.value);
            closeModal();
        });
    }

    // 處理登入
    async handleLogin(username, password) {
        try {
            const response = await this.apiClient.login(username, password);
            if (response.success) {
                this.isAuthenticated = true;
                this.updateUIForAuthentication(true);
                this.showNotification("登入成功", "success");
                await this.refreshPosts();
            }
        } catch (error) {
            this.showNotification("登入失敗: " + error.message, "error");
        }
    }

    // 登出
    async logout() {
        try {
            await this.apiClient.logout();
        } catch (error) {
            console.log("登出請求失敗:", error.message);
        }

        this.isAuthenticated = false;
        this.updateUIForAuthentication(false);
        this.showNotification("已登出", "success");
        await this.refreshPosts();
    }

    // 顯示建立公告對話框
    showCreatePostModal() {
        const modal = document.createElement("div");
        modal.className = "modal-overlay";
        modal.innerHTML = `
            <div class="modal modal-large">
                <div class="modal-header">
                    <h3>建立新公告</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <form class="create-post-form">
                    <div class="form-group">
                        <label for="post-title">標題：</label>
                        <input type="text" id="post-title" name="title" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label for="post-content">內容：</label>
                        <textarea id="post-content" name="content" required maxlength="2000"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">發布公告</button>
                        <button type="button" class="btn btn-outline cancel-btn">取消</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // 綁定事件
        const closeBtn = modal.querySelector(".close-btn");
        const cancelBtn = modal.querySelector(".cancel-btn");
        const form = modal.querySelector(".create-post-form");

        const closeModal = () => modal.remove();

        closeBtn.addEventListener("click", closeModal);
        cancelBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            await this.handleCreatePost({
                title: form.title.value,
                content: form.content.value,
            });
            closeModal();
        });
    }

    // 處理建立公告
    async handleCreatePost(postData) {
        try {
            const response = await this.apiClient.createPost(postData);
            if (response.success) {
                this.showNotification("公告已發布", "success");
                await this.refreshPosts();
            }
        } catch (error) {
            this.showNotification("發布失敗: " + error.message, "error");
        }
    }

    // 顯示通知
    showNotification(message, type) {
        const notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// 當 DOM 載入完成時啟動應用程式
document.addEventListener("DOMContentLoaded", () => {
    window.alleyNoteApp = new AlleyNoteApp();
});

export { AlleyNoteApp };
