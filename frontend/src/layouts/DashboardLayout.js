import { globalGetters, globalActions, globalStore } from '../store/globalStore.js';
import { authAPI } from '../api/modules/auth.js';
import { router } from '../router/index.js';
import { toast } from '../utils/toast.js';

/**
 * 渲染後台佈局
 */
export function renderDashboardLayout(content) {
  const user = globalGetters.getCurrentUser();
  const sidebarCollapsed = globalStore.get('sidebarCollapsed');
  
  return `
    <div class="min-h-screen bg-modern-50">
      <!-- 側邊欄 -->
      <aside class="${sidebarCollapsed ? 'w-20' : 'w-64'} fixed left-0 top-0 h-full bg-white border-r border-modern-200 transition-all duration-300 z-40">
        <div class="p-4 border-b border-modern-200">
          <div class="flex items-center ${sidebarCollapsed ? 'justify-center' : 'justify-between'}">
            ${sidebarCollapsed ? '' : '<h1 class="text-xl font-bold text-accent-600">AlleyNote</h1>'}
            <button 
              id="sidebar-toggle"
              class="p-2 hover:bg-modern-100 rounded-lg transition-colors"
            >
              ${sidebarCollapsed ? '→' : '←'}
            </button>
          </div>
        </div>
        
        <nav class="p-4 space-y-2">
          <a href="/admin/dashboard" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
            <span>📊</span>
            ${sidebarCollapsed ? '' : '<span>儀表板</span>'}
          </a>
          <a href="/admin/posts" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
            <span>📝</span>
            ${sidebarCollapsed ? '' : '<span>文章管理</span>'}
          </a>
          <a href="/admin/tags" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
            <span>🏷️</span>
            ${sidebarCollapsed ? '' : '<span>標籤管理</span>'}
          </a>
          ${globalGetters.isAdmin() ? `
            <a href="/admin/users" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
              <span>👥</span>
              ${sidebarCollapsed ? '' : '<span>使用者管理</span>'}
            </a>
            <a href="/admin/roles" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
              <span>🔐</span>
              ${sidebarCollapsed ? '' : '<span>角色管理</span>'}
            </a>
            <a href="/admin/statistics" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
              <span>📈</span>
              ${sidebarCollapsed ? '' : '<span>系統統計</span>'}
            </a>
            <a href="/admin/settings" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
              <span>⚙️</span>
              ${sidebarCollapsed ? '' : '<span>系統設定</span>'}
            </a>
          ` : ''}
          <a href="/admin/profile" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
            <span>👤</span>
            ${sidebarCollapsed ? '' : '<span>個人資料</span>'}
          </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-modern-200">
          <div class="flex items-center ${sidebarCollapsed ? 'justify-center' : 'gap-3'}">
            <div class="w-10 h-10 rounded-full bg-accent-500 flex items-center justify-center text-white font-semibold">
              ${(user?.name || 'U')[0].toUpperCase()}
            </div>
            ${sidebarCollapsed ? '' : `
              <div class="flex-1">
                <p class="text-sm font-medium text-modern-900">${user?.name || 'User'}</p>
                <p class="text-xs text-modern-500">${user?.role || 'user'}</p>
              </div>
            `}
          </div>
          <button 
            id="logout-btn"
            class="mt-3 w-full btn-secondary text-sm"
          >
            ${sidebarCollapsed ? '🚪' : '登出'}
          </button>
        </div>
      </aside>
      
      <!-- 主要內容區 -->
      <main class="${sidebarCollapsed ? 'ml-20' : 'ml-64'} transition-all duration-300">
        <div class="p-8">
          ${content}
        </div>
      </main>
    </div>
  `;
}

/**
 * 綁定佈局事件
 */
export function bindDashboardLayoutEvents() {
  // 側邊欄切換
  const sidebarToggle = document.getElementById('sidebar-toggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      globalActions.toggleSidebar();
      // 重新渲染（這裡需要通知當前頁面重新渲染）
      window.location.reload();
    });
  }
  
  // 登出按鈕
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        await authAPI.logout();
        globalActions.clearUser();
        toast.success('已成功登出');
        router.navigate('/login');
      } catch (error) {
        toast.error('登出失敗');
      }
    });
  }
  
  // 更新路由連結，讓 Navigo 攔截所有內部連結點擊
  router.updatePageLinks();
}
