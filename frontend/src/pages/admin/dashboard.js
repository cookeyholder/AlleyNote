import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { globalGetters } from '../../store/globalStore.js';

/**
 * 渲染儀表板頁面
 */
export function renderDashboard() {
  const user = globalGetters.getCurrentUser();
  
  const content = `
    <div>
      <h1 class="text-3xl font-bold text-modern-900 mb-8">儀表板</h1>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- 統計卡片 -->
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總文章數</h3>
            <span class="text-2xl">📝</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">42</p>
          <p class="text-sm text-green-600 mt-2">↑ 12% 較上週</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總瀏覽量</h3>
            <span class="text-2xl">👁️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">1,234</p>
          <p class="text-sm text-green-600 mt-2">↑ 8% 較上週</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">草稿數</h3>
            <span class="text-2xl">✏️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">5</p>
          <p class="text-sm text-modern-600 mt-2">待發布</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">今日訪客</h3>
            <span class="text-2xl">👥</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">89</p>
          <p class="text-sm text-green-600 mt-2">↑ 5% 較昨天</p>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 最近文章 -->
        <div class="card">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">最近發布的文章</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-3 border-b border-modern-100">
              <div>
                <h3 class="font-medium text-modern-900">範例文章標題</h3>
                <p class="text-sm text-modern-500">2024-10-03</p>
              </div>
              <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full">已發布</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-modern-100">
              <div>
                <h3 class="font-medium text-modern-900">另一篇文章</h3>
                <p class="text-sm text-modern-500">2024-10-02</p>
              </div>
              <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full">草稿</span>
            </div>
            <div class="flex items-center justify-between py-3">
              <div>
                <h3 class="font-medium text-modern-900">技術分享</h3>
                <p class="text-sm text-modern-500">2024-10-01</p>
              </div>
              <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full">已發布</span>
            </div>
          </div>
          <div class="mt-4">
            <a href="/admin/posts" class="text-accent-600 hover:text-accent-700 text-sm font-medium">
              查看所有文章 →
            </a>
          </div>
        </div>
        
        <!-- 快速操作 -->
        <div class="card">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">快速操作</h2>
          <div class="space-y-3">
            <a href="/admin/posts/create" class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
              <div class="flex items-center gap-3">
                <span class="text-2xl">✏️</span>
                <div>
                  <h3 class="font-medium text-modern-900">新增文章</h3>
                  <p class="text-sm text-modern-600">建立新的公告或文章</p>
                </div>
              </div>
            </a>
            
            <a href="/admin/posts" class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
              <div class="flex items-center gap-3">
                <span class="text-2xl">📋</span>
                <div>
                  <h3 class="font-medium text-modern-900">管理文章</h3>
                  <p class="text-sm text-modern-600">編輯或刪除現有文章</p>
                </div>
              </div>
            </a>
            
            ${globalGetters.isAdmin() ? `
              <a href="/admin/users" class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
                <div class="flex items-center gap-3">
                  <span class="text-2xl">👥</span>
                  <div>
                    <h3 class="font-medium text-modern-900">使用者管理</h3>
                    <p class="text-sm text-modern-600">管理系統使用者</p>
                  </div>
                </div>
              </a>
            ` : ''}
          </div>
        </div>
      </div>
    </div>
  `;
  
  const app = document.getElementById('app');
  app.innerHTML = renderDashboardLayout(content);
  bindDashboardLayoutEvents();
}
