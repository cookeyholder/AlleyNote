/**
 * 管理後台布局組件
 */

import { globalGetters } from '../store/globalStore.js';
import { authAPI } from '../api/modules/auth.js';
import { toast } from '../utils/toast.js';
import { router } from '../utils/router.js';

/**
 * 渲染管理後台布局
 */
export function renderDashboardLayout(content, options = {}) {
  const {
    title = '管理後台',
    headerActions = '',
  } = options;

  const user = globalGetters.getUser();
  const app = document.getElementById('app');

  app.innerHTML = `
    <div class="flex h-screen bg-modern-50">
      <!-- 側邊欄 -->
      ${renderSidebar(user)}
      
      <!-- 主要內容區 -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- 頂部導航列 -->
        <header class="bg-white shadow-sm">
          <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
              <button
                id="sidebar-toggle"
                class="mr-4 lg:hidden text-modern-600 hover:text-modern-900"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
              <h1 class="text-2xl font-semibold text-modern-900">${title}</h1>
            </div>
            <div class="flex items-center gap-4">
              ${headerActions}
              <div class="relative">
                <button
                  id="user-menu-btn"
                  class="flex items-center gap-2 text-modern-700 hover:text-modern-900 transition-colors"
                >
                  <div class="w-8 h-8 bg-accent-600 text-white rounded-full flex items-center justify-center font-semibold">
                    ${user?.username ? user.username.charAt(0).toUpperCase() : 'U'}
                  </div>
                  <span class="hidden md:inline">${user?.username || '使用者'}</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                
                <!-- 使用者選單 -->
                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-modern-200 z-50">
                  <a href="/admin/profile" data-navigo class="block px-4 py-2 text-modern-700 hover:bg-modern-50 rounded-t-lg">
                    個人資料
                  </a>
                  <button
                    id="logout-btn"
                    class="w-full text-left px-4 py-2 text-modern-700 hover:bg-modern-50 rounded-b-lg"
                  >
                    登出
                  </button>
                </div>
              </div>
            </div>
          </div>
        </header>
        
        <!-- 主要內容 -->
        <main class="flex-1 overflow-y-auto p-6">
          ${content}
        </main>
      </div>
    </div>
  `;

  // 綁定事件
  bindDashboardEvents();
}

/**
 * 渲染側邊欄
 */
function renderSidebar(user) {
  const currentPath = window.location.pathname;
  
  const menuItems = [
    { path: '/admin/dashboard', label: '儀表板', icon: '📊' },
    { path: '/admin/posts', label: '文章管理', icon: '📝' },
    { path: '/admin/users', label: '使用者管理', icon: '👥' },
    { path: '/admin/roles', label: '角色管理', icon: '🔐' },
    { path: '/admin/tags', label: '標籤管理', icon: '🏷️' },
    { path: '/admin/statistics', label: '系統統計', icon: '📈' },
    { path: '/admin/settings', label: '系統設定', icon: '⚙️' },
  ];

  return `
    <aside id="sidebar" class="sidebar">
      <div class="p-4 border-b border-modern-700">
        <h2 class="text-xl font-bold text-white">AlleyNote</h2>
        <p class="text-sm text-modern-400">管理後台</p>
      </div>
      
      <nav class="flex-1 p-4 space-y-1">
        ${menuItems.map(item => `
          <a
            href="${item.path}"
            data-navigo
            class="sidebar-link ${currentPath === item.path ? 'active' : ''}"
          >
            <span class="mr-3">${item.icon}</span>
            ${item.label}
          </a>
        `).join('')}
        
        <div class="border-t border-modern-700 my-4"></div>
        
        <a
          href="/"
          data-navigo
          class="sidebar-link"
        >
          <span class="mr-3">🏠</span>
          返回首頁
        </a>
      </nav>
      
      <div class="p-4 border-t border-modern-700">
        <p class="text-xs text-modern-400">登入身分</p>
        <p class="text-white font-medium">${user?.username || '訪客'}</p>
        <p class="text-xs text-modern-400">${user?.email || ''}</p>
      </div>
    </aside>
  `;
}

/**
 * 綁定管理後台事件
 */
export function bindDashboardLayoutEvents() {
  bindDashboardEvents();
}

/**
 * 綁定管理後台事件（內部使用）
 */
function bindDashboardEvents() {
  // 側邊欄切換（手機版）
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  // 使用者選單切換
  const userMenuBtn = document.getElementById('user-menu-btn');
  const userMenu = document.getElementById('user-menu');
  
  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenu.classList.toggle('hidden');
    });

    // 點擊外部關閉選單
    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target) && !userMenuBtn.contains(e.target)) {
        userMenu.classList.add('hidden');
      }
    });
  }

  // 登出按鈕
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        await authAPI.logout();
        toast.success('已成功登出');
        router.navigate('/');
      } catch (error) {
        console.error('Logout error:', error);
        toast.error('登出失敗');
      }
    });
  }
}

/**
 * 公開頁面布局
 */
export function renderPublicLayout(content, options = {}) {
  const {
    showHeader = true,
    showFooter = true,
  } = options;

  const app = document.getElementById('app');

  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex flex-col">
      ${showHeader ? renderPublicHeader() : ''}
      
      <main class="flex-1">
        ${content}
      </main>
      
      ${showFooter ? renderPublicFooter() : ''}
    </div>
  `;
}

/**
 * 渲染公開頁面標頭
 */
function renderPublicHeader() {
  return `
    <nav class="bg-white shadow-sm sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center">
            <a href="/" data-navigo class="text-2xl font-bold text-accent-600">
              AlleyNote
            </a>
          </div>
          <div class="flex items-center gap-4">
            <a
              href="/login"
              data-navigo
              class="btn-primary"
            >
              登入
            </a>
          </div>
        </div>
      </div>
    </nav>
  `;
}

/**
 * 渲染公開頁面頁腳
 */
function renderPublicFooter() {
  return `
    <footer class="bg-white border-t border-modern-200 mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <p class="text-modern-600 mb-2">
            © ${new Date().getFullYear()} AlleyNote. All rights reserved.
          </p>
          <p class="text-sm text-modern-500">
            基於 Domain-Driven Design 的企業級公布欄系統
          </p>
        </div>
      </div>
    </footer>
  `;
}
