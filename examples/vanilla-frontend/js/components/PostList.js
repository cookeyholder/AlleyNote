// 公告組件
class PostComponent {
    constructor(post) {
        this.post = post;
    }

    render() {
        return `
            <article class="post-card" data-id="${this.post.id}">
                <header class="post-header">
                    <h3 class="post-title">${this.escapeHtml(
                        this.post.title
                    )}</h3>
                    <div class="post-meta">
                        <span class="post-author">作者: ${this.escapeHtml(
                            this.post.author_name
                        )}</span>
                        <span class="post-date">${this.formatDate(
                            this.post.created_at
                        )}</span>
                    </div>
                </header>
                <div class="post-content">
                    <p>${this.escapeHtml(this.post.content)}</p>
                </div>
                <footer class="post-actions">
                    <button class="btn btn-sm btn-outline edit-btn" data-id="${
                        this.post.id
                    }">
                        編輯
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${
                        this.post.id
                    }">
                        刪除
                    </button>
                </footer>
            </article>
        `;
    }

    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString("zh-TW", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }
}

// 公告列表組件
class PostList {
    constructor(container, apiClient) {
        this.container = container;
        this.apiClient = apiClient;
        this.posts = [];
        this.currentPage = 1;
    }

    async loadPosts(page = 1) {
        try {
            const response = await this.apiClient.getPosts(page, 10);
            if (response.success) {
                this.posts = response.data.posts;
                this.currentPage = page;
                this.render();
            }
        } catch (error) {
            this.showError("載入公告失敗: " + error.message);
        }
    }

    render() {
        if (this.posts.length === 0) {
            this.container.innerHTML = `
                <div class="empty-state">
                    <p>目前沒有任何公告</p>
                </div>
            `;
            return;
        }

        const postsHtml = this.posts
            .map((post) => new PostComponent(post).render())
            .join("");

        this.container.innerHTML = `
            <div class="posts-grid">
                ${postsHtml}
            </div>
            <div class="pagination">
                <button class="btn btn-outline" id="prev-page" ${
                    this.currentPage <= 1 ? "disabled" : ""
                }>
                    上一頁
                </button>
                <span class="page-info">第 ${this.currentPage} 頁</span>
                <button class="btn btn-outline" id="next-page">
                    下一頁
                </button>
            </div>
        `;

        this.bindEvents();
    }

    bindEvents() {
        // 編輯按鈕事件
        this.container.querySelectorAll(".edit-btn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const postId = e.target.dataset.id;
                this.editPost(postId);
            });
        });

        // 刪除按鈕事件
        this.container.querySelectorAll(".delete-btn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const postId = e.target.dataset.id;
                this.deletePost(postId);
            });
        });

        // 分頁事件
        const prevBtn = this.container.querySelector("#prev-page");
        const nextBtn = this.container.querySelector("#next-page");

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                if (this.currentPage > 1) {
                    this.loadPosts(this.currentPage - 1);
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                this.loadPosts(this.currentPage + 1);
            });
        }
    }

    async editPost(postId) {
        // 實作編輯功能
        console.log("編輯公告:", postId);
    }

    async deletePost(postId) {
        if (!confirm("確定要刪除這則公告嗎？")) {
            return;
        }

        try {
            const response = await this.apiClient.deletePost(postId);
            if (response.success) {
                this.showSuccess("公告已刪除");
                this.loadPosts(this.currentPage);
            }
        } catch (error) {
            this.showError("刪除失敗: " + error.message);
        }
    }

    showSuccess(message) {
        this.showNotification(message, "success");
    }

    showError(message) {
        this.showNotification(message, "error");
    }

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

export { PostComponent, PostList };
