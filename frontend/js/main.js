/**
 * AlleyNote 主程式入口
 */

import { router } from './utils/router.js';
import { loading } from './components/Loading.js';
import { renderHomePage } from './pages/public/home.js';
import { renderLoginPage } from './pages/public/login.js';
import { renderDashboard } from './pages/admin/dashboard.js';

// 初始化應用程式
document.addEventListener('DOMContentLoaded', async () => {
    console.log('AlleyNote 應用程式初始化中...');

    // 註冊路由
    registerRoutes();

    // 隱藏初始載入畫面
    setTimeout(() => {
        loading.hide();
    }, 500);
});

function registerRoutes() {
    // 公開路由
    router.addRoute('/', renderHomePage);
    router.addRoute('/login', renderLoginPage);
    router.addRoute('/post/:id', renderPostDetail);

    // 管理後台路由
    router.addRoute('/admin/dashboard', renderDashboard);
    router.addRoute('/admin/posts', renderPostsManagement);
    router.addRoute('/admin/posts/new', renderPostEditor);
    router.addRoute('/admin/posts/:id/edit', renderPostEditor);
    router.addRoute('/admin/users', renderUsersManagement);
    router.addRoute('/admin/users/:id', renderUserDetail);
    router.addRoute('/admin/roles', renderRolesManagement);
    router.addRoute('/admin/statistics', renderStatistics);
    router.addRoute('/admin/settings', renderSettings);

    // 404 路由
    router.addRoute('*', render404);
}

// 文章詳情頁面（簡化版）
async function renderPostDetail(params) {
    loading.show();
    
    try {
        const { postsApi } = await import('./api/posts.js');
        const post = await postsApi.getPublicPost(params.id);
        
        const content = document.getElementById('content');
        content.innerHTML = `
            <div class="min-h-screen bg-gray-50">
                <nav class="bg-white shadow-sm">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between h-16">
                            <div class="flex items-center">
                                <a href="/" data-link class="text-2xl font-bold text-gray-900">AlleyNote</a>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div class="card">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">${post.title}</h1>
                        <div class="flex items-center gap-4 text-sm text-gray-500 mb-6 pb-6 border-b">
                            <span>發布日期：${new Date(post.publish_date).toLocaleDateString('zh-TW')}</span>
                            <span>瀏覽次數：${post.views || 0}</span>
                        </div>
                        <div class="prose max-w-none">
                            ${post.content}
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="/" data-link class="btn btn-outline">← 返回列表</a>
                    </div>
                </main>
            </div>
        `;
    } catch (error) {
        const { toast } = await import('./utils/toast.js');
        toast.error('載入文章失敗');
        console.error(error);
    } finally {
        loading.hide();
    }
}

// 文章管理頁面（簡化版）
async function renderPostsManagement() {
    loading.show();
    
    try {
        const { postsApi } = await import('./api/posts.js');
        const { authApi } = await import('./api/auth.js');
        const { toast } = await import('./utils/toast.js');
        
        if (!authApi.isAuthenticated()) {
            router.navigate('/login');
            return;
        }

        const [user, response] = await Promise.all([
            authApi.getCurrentUser(),
            postsApi.getAll({ page: 1, per_page: 10 })
        ]);

        const posts = response.data || [];

        const content = document.getElementById('content');
        content.innerHTML = `
            <div class="flex h-screen bg-gray-100">
                ${renderAdminSidebar(user)}
                
                <div class="flex-1 overflow-y-auto">
                    <header class="bg-white shadow-sm">
                        <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                            <h1 class="text-2xl font-semibold text-gray-900">文章管理</h1>
                            <a href="/admin/posts/new" data-link class="btn btn-primary">新增文章</a>
                        </div>
                    </header>
                    
                    <main class="p-6">
                        <div class="card">
                            ${posts.length === 0 ? `
                                <div class="text-center py-12 text-gray-500">
                                    目前沒有文章
                                </div>
                            ` : `
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>標題</th>
                                            <th>狀態</th>
                                            <th>發布日期</th>
                                            <th>瀏覽次數</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${posts.map(post => `
                                            <tr>
                                                <td>${post.title}</td>
                                                <td>
                                                    ${post.status === 'published' ? '<span class="badge badge-success">已發布</span>' : '<span class="badge badge-warning">草稿</span>'}
                                                    ${post.is_pinned ? '<span class="badge badge-primary ml-2">置頂</span>' : ''}
                                                </td>
                                                <td>${new Date(post.publish_date).toLocaleDateString('zh-TW')}</td>
                                                <td>${post.views || 0}</td>
                                                <td>
                                                    <div class="flex gap-2">
                                                        <a href="/admin/posts/${post.uuid}/edit" data-link class="btn btn-sm btn-secondary">編輯</a>
                                                        <button class="btn btn-sm btn-danger" data-delete-post="${post.uuid}">刪除</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `}
                        </div>
                    </main>
                </div>
            </div>
        `;

        // 綁定刪除按鈕
        document.querySelectorAll('[data-delete-post]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const postId = e.target.getAttribute('data-delete-post');
                const { Modal } = await import('./components/Modal.js');
                
                Modal.confirm('刪除文章', '確定要刪除這篇文章嗎？', async () => {
                    try {
                        await postsApi.delete(postId);
                        toast.success('文章已刪除');
                        renderPostsManagement();
                    } catch (error) {
                        toast.error('刪除失敗');
                    }
                });
            });
        });

    } catch (error) {
        const { toast } = await import('./utils/toast.js');
        toast.error('載入失敗');
        console.error(error);
    } finally {
        loading.hide();
    }
}

// 文章編輯器（簡化版）
async function renderPostEditor(params) {
    const content = document.getElementById('content');
    content.innerHTML = `
        <div class="min-h-screen bg-gray-100 p-6">
            <div class="max-w-4xl mx-auto">
                <div class="card">
                    <h1 class="text-2xl font-bold mb-6">${params?.id ? '編輯文章' : '新增文章'}</h1>
                    <p class="text-gray-600">編輯器功能開發中...</p>
                    <div class="mt-6">
                        <a href="/admin/posts" data-link class="btn btn-outline">返回列表</a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// 使用者管理頁面（簡化版）
async function renderUsersManagement() {
    const content = document.getElementById('content');
    content.innerHTML = `
        <div class="min-h-screen bg-gray-100 p-6">
            <div class="max-w-7xl mx-auto">
                <div class="card">
                    <h1 class="text-2xl font-bold mb-6">使用者管理</h1>
                    <p class="text-gray-600">使用者管理功能開發中...</p>
                    <div class="mt-6">
                        <a href="/admin/dashboard" data-link class="btn btn-outline">返回儀表板</a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// 其他簡化的頁面渲染函數
async function renderUserDetail() {
    const content = document.getElementById('content');
    content.innerHTML = `<div class="p-6"><p>使用者詳情頁面開發中...</p></div>`;
}

async function renderRolesManagement() {
    const content = document.getElementById('content');
    content.innerHTML = `<div class="p-6"><p>角色管理頁面開發中...</p></div>`;
}

async function renderStatistics() {
    const content = document.getElementById('content');
    content.innerHTML = `<div class="p-6"><p>統計頁面開發中...</p></div>`;
}

async function renderSettings() {
    const content = document.getElementById('content');
    content.innerHTML = `<div class="p-6"><p>設定頁面開發中...</p></div>`;
}

function render404() {
    const content = document.getElementById('content');
    content.innerHTML = `
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <p class="text-xl text-gray-600 mb-8">找不到頁面</p>
                <a href="/" data-link class="btn btn-primary">返回首頁</a>
            </div>
        </div>
    `;
}

// 輔助函數：渲染管理後台側邊欄
function renderAdminSidebar(user) {
    const menuItems = [
        { path: '/admin/dashboard', label: '儀表板', icon: '📊' },
        { path: '/admin/posts', label: '文章管理', icon: '📝' },
        { path: '/admin/users', label: '使用者管理', icon: '👥' },
        { path: '/admin/roles', label: '角色管理', icon: '🔐' },
        { path: '/admin/statistics', label: '系統統計', icon: '📈' },
        { path: '/admin/settings', label: '系統設定', icon: '⚙️' },
        { path: '/', label: '返回首頁', icon: '🏠' }
    ];

    return `
        <aside class="sidebar">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-bold">AlleyNote</h2>
                <p class="text-sm text-gray-400">管理後台</p>
            </div>
            <nav class="flex-1 p-4">
                ${menuItems.map(item => `
                    <a href="${item.path}" data-link class="sidebar-link ${window.location.pathname === item.path ? 'active' : ''}">
                        <span class="mr-3">${item.icon}</span>
                        ${item.label}
                    </a>
                `).join('')}
            </nav>
            <div class="p-4 border-t border-gray-700">
                <p class="text-sm text-gray-400">登入身分</p>
                <p class="text-white">${user?.username || '訪客'}</p>
            </div>
        </aside>
    `;
}
