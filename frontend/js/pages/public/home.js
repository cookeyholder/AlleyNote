/**
 * 首頁
 */

import { postsApi } from '../../api/posts.js';
import { authApi } from '../../api/auth.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';

export async function renderHomePage() {
    loading.show();
    
    try {
        const response = await postsApi.getPublicPosts({
            page: 1,
            per_page: 10,
            status: 'published'
        });

        const posts = response.data || [];
        const isAuthenticated = authApi.isAuthenticated();

        const content = document.getElementById('content');
        content.innerHTML = `
            <div class="min-h-screen bg-gray-50">
                <!-- 導航欄 -->
                <nav class="bg-white shadow-sm">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16">
                            <div class="flex items-center">
                                <h1 class="text-2xl font-bold text-gray-900">AlleyNote</h1>
                            </div>
                            <div class="flex items-center gap-4">
                                ${isAuthenticated ? `
                                    <a href="/admin/dashboard" data-link class="btn btn-primary">
                                        進入管理後台
                                    </a>
                                    <button id="logout-btn" class="btn btn-outline">
                                        登出
                                    </button>
                                ` : `
                                    <a href="/login" data-link class="btn btn-primary">
                                        登入
                                    </a>
                                `}
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- 主要內容 -->
                <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div class="px-4 py-6 sm:px-0">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">最新公告</h2>
                        
                        ${posts.length === 0 ? `
                            <div class="text-center py-12">
                                <p class="text-gray-500">目前沒有公告</p>
                            </div>
                        ` : `
                            <div class="space-y-4">
                                ${posts.map(post => renderPostCard(post)).join('')}
                            </div>
                        `}

                        <!-- 分頁 -->
                        ${renderPagination(response.meta)}
                    </div>
                </main>
            </div>
        `;

        // 綁定登出按鈕
        if (isAuthenticated) {
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', handleLogout);
            }
        }

        // 綁定文章卡片點擊
        bindPostCards();

    } catch (error) {
        toast.error('載入公告失敗');
        console.error(error);
    } finally {
        loading.hide();
    }
}

function renderPostCard(post) {
    const publishDate = new Date(post.publish_date).toLocaleDateString('zh-TW');
    
    return `
        <div class="card hover:shadow-lg transition-shadow cursor-pointer" data-post-id="${post.uuid}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        ${post.is_pinned ? '<span class="badge badge-warning mr-2">置頂</span>' : ''}
                        ${post.title}
                    </h3>
                    <p class="text-gray-600 mb-4">${truncate(stripHtml(post.content), 200)}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>發布日期：${publishDate}</span>
                        <span>瀏覽次數：${post.views || 0}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderPagination(meta) {
    if (!meta || meta.last_page <= 1) return '';

    const pages = [];
    for (let i = 1; i <= meta.last_page; i++) {
        pages.push(i);
    }

    return `
        <div class="mt-8 flex justify-center">
            <div class="pagination">
                <button class="btn" ${meta.current_page === 1 ? 'disabled' : ''} data-page="${meta.current_page - 1}">
                    上一頁
                </button>
                ${pages.map(page => `
                    <button class="btn ${page === meta.current_page ? 'active' : ''}" data-page="${page}">
                        ${page}
                    </button>
                `).join('')}
                <button class="btn" ${meta.current_page === meta.last_page ? 'disabled' : ''} data-page="${meta.current_page + 1}">
                    下一頁
                </button>
            </div>
        </div>
    `;
}

function bindPostCards() {
    const postCards = document.querySelectorAll('[data-post-id]');
    postCards.forEach(card => {
        card.addEventListener('click', () => {
            const postId = card.getAttribute('data-post-id');
            window.location.href = `/post/${postId}`;
        });
    });
}

async function handleLogout() {
    try {
        await authApi.logout();
        toast.success('已登出');
        window.location.reload();
    } catch (error) {
        toast.error('登出失敗');
    }
}

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}

function truncate(text, length) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}
