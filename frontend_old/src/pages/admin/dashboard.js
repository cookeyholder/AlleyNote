import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { globalGetters } from '../../store/globalStore.js';
import { router } from '../../router/index.js';
import { postsAPI } from '../../api/modules/posts.js';
import { loading } from '../../components/Loading.js';

/**
 * 渲染儀表板頁面
 */
export async function renderDashboard() {
  const user = globalGetters.getCurrentUser();
  
  // 先顯示基本架構
  const content = `
    <div>
      <h1 class="text-3xl font-bold text-modern-900 mb-8">儀表板</h1>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="stats-cards">
        <!-- 統計卡片載入中 -->
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 最近文章 -->
        <div class="card">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">最近發布的文章</h2>
          <div id="recent-posts" class="space-y-3">
            <!-- 載入中 -->
          </div>
          <div class="mt-4">
            <a href="/admin/posts" data-navigo class="text-accent-600 hover:text-accent-700 text-sm font-medium">
              查看所有文章 →
            </a>
          </div>
        </div>
        
        <!-- 快速操作 -->
        <div class="card">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">快速操作</h2>
          <div class="space-y-3">
            <a href="/admin/posts/create" data-navigo class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
              <div class="flex items-center gap-3">
                <span class="text-2xl">✏️</span>
                <div>
                  <h3 class="font-medium text-modern-900">新增文章</h3>
                  <p class="text-sm text-modern-600">建立新的公告或文章</p>
                </div>
              </div>
            </a>
            
            <a href="/admin/posts" data-navigo class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
              <div class="flex items-center gap-3">
                <span class="text-2xl">📋</span>
                <div>
                  <h3 class="font-medium text-modern-900">管理文章</h3>
                  <p class="text-sm text-modern-600">編輯或刪除現有文章</p>
                </div>
              </div>
            </a>
            
            ${globalGetters.isAdmin() ? `
              <a href="/admin/users" data-navigo class="block p-4 border-2 border-modern-200 rounded-lg hover:border-accent-500 hover:bg-accent-50 transition-all">
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
  router.updatePageLinks();
  
  // 載入統計資料和最近文章
  await loadDashboardData();
}

/**
 * 載入儀表板資料
 */
async function loadDashboardData() {
  try {
    // 載入文章列表以計算統計資料
    const result = await postsAPI.list({ page: 1, per_page: 100 });
    const posts = result.data || [];
    const total = result.pagination?.total || 0;
    
    // 計算統計資料
    const publishedCount = posts.filter(p => p.status === 'published').length;
    const draftCount = posts.filter(p => p.status === 'draft').length;
    const totalViews = posts.reduce((sum, p) => sum + (parseInt(p.views) || 0), 0);
    
    // 更新統計卡片
    const statsContainer = document.getElementById('stats-cards');
    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總文章數</h3>
            <span class="text-2xl">📝</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">${total}</p>
          <p class="text-sm text-modern-500 mt-2">已發布 ${publishedCount} 篇</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總瀏覽量</h3>
            <span class="text-2xl">👁️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">${totalViews.toLocaleString()}</p>
          <p class="text-sm text-modern-500 mt-2">全部文章累計</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">草稿數</h3>
            <span class="text-2xl">✏️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">${draftCount}</p>
          <p class="text-sm text-modern-600 mt-2">待發布</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">已發布</h3>
            <span class="text-2xl">✅</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">${publishedCount}</p>
          <p class="text-sm text-modern-500 mt-2">線上中的文章</p>
        </div>
      `;
    }
    
    // 更新最近文章列表（只顯示最近 5 篇）
    const recentPosts = posts
      .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
      .slice(0, 5);
    
    const recentPostsContainer = document.getElementById('recent-posts');
    if (recentPostsContainer) {
      if (recentPosts.length === 0) {
        recentPostsContainer.innerHTML = `
          <div class="text-center py-8 text-modern-500">
            <p>尚無文章</p>
            <a href="/admin/posts/create" data-navigo class="text-accent-600 hover:text-accent-700 mt-2 inline-block">
              立即新增第一篇文章
            </a>
          </div>
        `;
      } else {
        recentPostsContainer.innerHTML = recentPosts.map((post, index) => {
          const date = new Date(post.created_at);
          const formattedDate = date.toLocaleDateString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
          });
          
          return `
            <div class="flex items-center justify-between py-3 ${index < recentPosts.length - 1 ? 'border-b border-modern-100' : ''}">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-modern-900 truncate">${post.title}</h3>
                <div class="flex items-center gap-2 mt-1">
                  <p class="text-sm text-modern-500">${formattedDate}</p>
                  ${post.author ? `<span class="text-sm text-modern-400">·</span><p class="text-sm text-modern-500">${post.author}</p>` : ''}
                </div>
              </div>
              <span class="ml-4 px-3 py-1 ${
                post.status === 'published'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-yellow-100 text-yellow-700'
              } text-sm rounded-full whitespace-nowrap">
                ${post.status === 'published' ? '已發布' : '草稿'}
              </span>
            </div>
          `;
        }).join('');
      }
      
      // 更新連結
      router.updatePageLinks();
    }
  } catch (error) {
    console.error('載入儀表板資料失敗:', error);
    
    // 顯示錯誤訊息
    const statsContainer = document.getElementById('stats-cards');
    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="col-span-4 card">
          <p class="text-red-600">載入統計資料失敗，請重新整理頁面</p>
        </div>
      `;
    }
  }
}
