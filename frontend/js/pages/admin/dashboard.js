/**
 * 管理後台儀表板
 */

import { authApi } from '../../api/auth.js';
import { statisticsApi } from '../../api/statistics.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';
import { router } from '../../utils/router.js';

export async function renderDashboard() {
    // 檢查是否已登入
    if (!authApi.isAuthenticated()) {
        router.navigate('/login');
        return;
    }

    loading.show();

    try {
        const [user, stats] = await Promise.all([
            authApi.getCurrentUser(),
            statisticsApi.getDashboard().catch(() => ({
                totalPosts: 0,
                totalUsers: 0,
                totalViews: 0
            }))
        ]);

        const content = document.getElementById('content');
        content.innerHTML = `
            <div class="flex h-screen bg-gray-100">
                <!-- 側邊欄 -->
                ${renderSidebar(user)}

                <!-- 主要內容 -->
                <div class="flex-1 overflow-y-auto">
                    <!-- 頂部導航 -->
                    <header class="bg-white shadow-sm">
                        <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                            <h1 class="text-2xl font-semibold text-gray-900">儀表板</h1>
                            <div class="flex items-center gap-4">
                                <span class="text-gray-700">歡迎，${user.username}</span>
                                <button id="logout-btn" class="btn btn-outline btn-sm">登出</button>
                            </div>
                        </div>
                    </header>

                    <!-- 內容區域 -->
                    <main class="p-6">
                        <!-- 統計卡片 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="card">
                                <h3 class="text-gray-500 text-sm font-medium mb-2">文章總數</h3>
                                <p class="text-3xl font-bold text-gray-900">${stats.totalPosts || 0}</p>
                            </div>
                            <div class="card">
                                <h3 class="text-gray-500 text-sm font-medium mb-2">使用者總數</h3>
                                <p class="text-3xl font-bold text-gray-900">${stats.totalUsers || 0}</p>
                            </div>
                            <div class="card">
                                <h3 class="text-gray-500 text-sm font-medium mb-2">總瀏覽次數</h3>
                                <p class="text-3xl font-bold text-gray-900">${stats.totalViews || 0}</p>
                            </div>
                        </div>

                        <!-- 快速操作 -->
                        <div class="card">
                            <h2 class="text-xl font-semibold mb-4">快速操作</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <a href="/admin/posts/new" data-link class="btn btn-primary">新增文章</a>
                                <a href="/admin/posts" data-link class="btn btn-secondary">文章管理</a>
                                <a href="/admin/users" data-link class="btn btn-secondary">使用者管理</a>
                                <a href="/admin/settings" data-link class="btn btn-secondary">系統設定</a>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        `;

        // 綁定登出按鈕
        document.getElementById('logout-btn').addEventListener('click', handleLogout);

    } catch (error) {
        toast.error('載入失敗');
        console.error(error);
        router.navigate('/login');
    } finally {
        loading.hide();
    }
}

function renderSidebar(user) {
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
                <p class="text-white">${user.username}</p>
            </div>
        </aside>
    `;
}

async function handleLogout() {
    try {
        await authApi.logout();
        toast.success('已登出');
        router.navigate('/login');
    } catch (error) {
        toast.error('登出失敗');
    }
}
