import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { postsAPI } from '../../api/modules/posts.js';
import { toast } from '../../utils/toast.js';

/**
 * 渲染文章列表頁面
 */
export async function renderPostsList() {
  const content = `
    <div>
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">文章管理</h1>
        <a href="/admin/posts/create" class="btn-primary">
          ✏️ 新增文章
        </a>
      </div>
      
      <!-- 搜尋與篩選 -->
      <div class="card mb-6">
        <div class="flex gap-4">
          <input
            type="text"
            id="search-input"
            placeholder="搜尋文章..."
            class="input-field flex-1"
          />
          <select id="status-filter" class="input-field w-48">
            <option value="">所有狀態</option>
            <option value="published">已發布</option>
            <option value="draft">草稿</option>
          </select>
          <button id="search-btn" class="btn-primary">搜尋</button>
        </div>
      </div>
      
      <!-- 文章列表 -->
      <div class="card">
        <div id="posts-table-container">
          <div class="text-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto"></div>
            <p class="mt-4 text-modern-600">載入中...</p>
          </div>
        </div>
      </div>
    </div>
  `;
  
  const app = document.getElementById('app');
  app.innerHTML = renderDashboardLayout(content);
  bindDashboardLayoutEvents();
  
  // 載入文章列表
  await loadPosts();
  
  // 綁定搜尋事件
  document.getElementById('search-btn')?.addEventListener('click', loadPosts);
}

/**
 * 載入文章列表
 */
async function loadPosts() {
  const container = document.getElementById('posts-table-container');
  const search = document.getElementById('search-input')?.value || '';
  const status = document.getElementById('status-filter')?.value || '';
  
  try {
    const result = await postsAPI.list({ search, status });
    const posts = result.data || [];
    
    if (posts.length === 0) {
      container.innerHTML = `
        <div class="text-center py-12">
          <p class="text-modern-600">目前沒有文章</p>
          <a href="/admin/posts/create" class="btn-primary mt-4 inline-block">新增第一篇文章</a>
        </div>
      `;
      return;
    }
    
    container.innerHTML = `
      <table class="w-full">
        <thead class="bg-modern-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">標題</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">作者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">建立時間</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-modern-500 uppercase tracking-wider">操作</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-modern-200">
          ${posts.map((post) => `
            <tr class="hover:bg-modern-50">
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-modern-900">${post.title}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                  post.status === 'published'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-yellow-100 text-yellow-800'
                }">
                  ${post.status === 'published' ? '已發布' : '草稿'}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-modern-600">${post.author || 'Unknown'}</td>
              <td class="px-6 py-4 text-sm text-modern-600">${new Date(post.created_at).toLocaleDateString('zh-TW')}</td>
              <td class="px-6 py-4 text-right text-sm">
                <a href="/admin/posts/${post.id}/edit" class="text-accent-600 hover:text-accent-900 mr-3">編輯</a>
                <button onclick="deletePost(${post.id})" class="text-red-600 hover:text-red-900">刪除</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (error) {
    container.innerHTML = `
      <div class="text-center py-12">
        <p class="text-red-600">載入失敗：${error.message}</p>
        <button onclick="location.reload()" class="btn-primary mt-4">重試</button>
      </div>
    `;
  }
}

/**
 * 刪除文章
 */
window.deletePost = async function (postId) {
  if (!confirm('確定要刪除這篇文章嗎？此操作無法復原。')) {
    return;
  }
  
  try {
    await postsAPI.delete(postId);
    toast.success('文章已刪除');
    await loadPosts();
  } catch (error) {
    toast.error('刪除失敗：' + error.message);
  }
};
