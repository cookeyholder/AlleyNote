import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { globalGetters } from '../../store/globalStore.js';
import { router } from '../../utils/router.js';
import { apiClient } from '../../api/client.js';
import { loading } from '../../components/Loading.js';
import { timezoneUtils } from '../../utils/timezoneUtils.js';

/**
 * 渲染儀表板頁面
 */
export async function renderDashboard() {
  const user = globalGetters.getCurrentUser();
  
  // 確保隱藏載入指示器
  loading.hide();
  
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
  
  renderDashboardLayout(content, {
    title: '儀表板'
  });
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
    // 直接使用 apiClient 以避免模組緩存問題
    const result = await apiClient.get('/posts', { params: { page: 1, per_page: 100 } });
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
    
    // 更新最近文章列表（只顯示最近 5 篇，依照 publish_date 或 created_at 排序）
    const recentPosts = posts
      .sort((a, b) => {
        const dateA = new Date(a.publish_date || a.created_at);
        const dateB = new Date(b.publish_date || b.created_at);
        return dateB - dateA;
      })
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
        // 先格式化所有時間
        const postsWithDates = await Promise.all(recentPosts.map(async (post) => {
          const dateString = post.publish_date || post.created_at;
          const formattedDateTime = await timezoneUtils.utcToSiteTimezone(dateString, 'datetime');
          return { ...post, formattedDateTime };
        }));
        
        recentPostsContainer.innerHTML = postsWithDates.map((post, index) => {
          const statusClass = post.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
          const statusText = post.status === 'published' ? '已發布' : '草稿';
          const borderClass = index < recentPosts.length - 1 ? 'border-b border-modern-100' : '';
          
          return `
            <div class="flex items-center justify-between py-3 ${borderClass}">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-modern-900 truncate">${post.title}</h3>
                <div class="flex items-center gap-2 mt-1">
                  <p class="text-sm text-modern-500">${post.formattedDateTime}</p>
                  ${post.author ? `<span class="text-sm text-modern-400">·</span><p class="text-sm text-modern-500">${post.author}</p>` : ''}
                </div>
              </div>
              <span class="ml-4 px-3 py-1 ${statusClass} text-sm rounded-full whitespace-nowrap">
                ${statusText}
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
    
    // 顯示基本的統計卡片（不依賴 API）
    const statsContainer = document.getElementById('stats-cards');
    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總文章數</h3>
            <span class="text-2xl">📝</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總瀏覽量</h3>
            <span class="text-2xl">👁️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">草稿數</h3>
            <span class="text-2xl">✏️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-600 mt-2">資料載入中...</p>
        </div>
        
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">已發布</h3>
            <span class="text-2xl">✅</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>
      `;
    }
    
    // 顯示最近文章區域的占位符
    const recentPostsContainer = document.getElementById('recent-posts');
    if (recentPostsContainer) {
      recentPostsContainer.innerHTML = `
        <div class="text-center py-8 text-modern-500">
          <p class="mb-2">暫時無法載入文章列表</p>
          <p class="text-sm">請嘗試<a href="/admin/posts" data-navigo class="text-accent-600 hover:text-accent-700">前往文章管理</a>頁面</p>
        </div>
      `;
      
      router.updatePageLinks();
    }
  }
}
