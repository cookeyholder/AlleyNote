import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { apiClient } from '../../api/client.js';
import { toast } from '../../utils/toast.js';
import { confirmDelete } from '../../components/ConfirmationDialog.js';
import { loading } from '../../components/Loading.js';
import { router } from '../../utils/router.js';

let currentPage = 1;
let currentFilters = {};
let currentState = { posts: [] };

/**
 * 渲染文章列表頁面
 */
export async function renderPostsList() {
  const content = `
    <div>
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">文章管理</h1>
        <button id="create-post-btn" class="btn-primary">
          ✏️ 新增文章
        </button>
      </div>
      
      <!-- 搜尋與篩選 -->
      <div class="card mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <input
            type="text"
            id="search-input"
            placeholder="搜尋文章標題..."
            class="input-field flex-1"
          />
          <select id="status-filter" class="input-field md:w-48">
            <option value="">所有狀態</option>
            <option value="published">已發布</option>
            <option value="draft">草稿</option>
          </select>
          <select id="sort-filter" class="input-field md:w-48">
            <option value="-created_at">最新優先</option>
            <option value="created_at">最舊優先</option>
            <option value="title">標題 A-Z</option>
            <option value="-title">標題 Z-A</option>
          </select>
          <button id="search-btn" class="btn-primary md:w-auto">搜尋</button>
          <button id="reset-btn" class="btn-secondary md:w-auto">重置</button>
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
  renderDashboardLayout(content, { title: '文章管理' });
  bindDashboardLayoutEvents();
  
  // 綁定新增文章按鈕
  document.getElementById('create-post-btn')?.addEventListener('click', () => {
    router.navigate('/admin/posts/create');
  });
  
  // 載入文章列表
  await loadPosts();
  
  // 綁定搜尋事件
  document.getElementById('search-btn')?.addEventListener('click', () => {
    currentPage = 1;
    loadPosts();
  });
  
  // 綁定重置事件
  document.getElementById('reset-btn')?.addEventListener('click', () => {
    document.getElementById('search-input').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('sort-filter').value = '-created_at';
    currentPage = 1;
    currentFilters = {};
    loadPosts();
  });
  
  // Enter 鍵搜尋
  document.getElementById('search-input')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      currentPage = 1;
      loadPosts();
    }
  });
}

/**
 * 載入文章列表
 */
async function loadPosts() {
  const container = document.getElementById('posts-table-container');
  const search = document.getElementById('search-input')?.value || '';
  const status = document.getElementById('status-filter')?.value || '';
  const sort = document.getElementById('sort-filter')?.value || '-created_at';
  
  loading.show('載入中...');
  
  try {
    currentFilters = { 
      search, 
      status,
      sort,
      page: currentPage,
      per_page: 10,
      include_future: true, // 文章管理頁面顯示所有文章，包括未來的文章
    };
    
    // 直接使用 apiClient 避免模組緩存問題
    const result = await apiClient.get('/posts', { params: currentFilters });
    const posts = result.data || [];
    const pagination = result.pagination || {};
    
    // Store posts in current state for deletion
    currentState.posts = posts;
    
    loading.hide();
    
    if (posts.length === 0) {
      container.innerHTML = `
        <div class="text-center py-12">
          <div class="text-6xl mb-4">📝</div>
          <p class="text-modern-600 mb-4">
            ${search || status ? '找不到符合條件的文章' : '目前沒有文章'}
          </p>
          ${!search && !status ? `
            <a href="/admin/posts/create" data-navigo class="btn-primary inline-block">新增第一篇文章</a>
          ` : ''}
        </div>
      `;
      router.updatePageLinks();
      return;
    }
    
    container.innerHTML = `
      <table class="w-full">
        <thead class="bg-modern-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">標題</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">作者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">發布時間</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-modern-500 uppercase tracking-wider">操作</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-modern-200">
          ${posts.map((post) => {
            // 優先使用 publish_date，若無則使用 created_at
            const dateString = post.publish_date || post.created_at;
            const dateObj = new Date(dateString);
            const formattedDate = dateObj.toLocaleDateString('zh-TW', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
            });
            const formattedTime = dateObj.toLocaleTimeString('zh-TW', {
              hour: '2-digit',
              minute: '2-digit',
              hour12: false, // 使用 24 時制
            });
            
            return `
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
              <td class="px-6 py-4 text-sm text-modern-600">
                <div>${formattedDate}</div>
                <div class="text-xs text-modern-500">${formattedTime}</div>
              </td>
              <td class="px-6 py-4 text-right text-sm">
                <div class="flex justify-end gap-2">
                  <button 
                    class="px-3 py-1 text-accent-600 hover:bg-accent-50 rounded transition-colors"
                    data-action="edit"
                    data-post-id="${post.id}"
                  >
                    編輯
                  </button>
                  <button 
                    class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                    data-action="toggle-status"
                    data-post-id="${post.id}"
                    data-current-status="${post.status}"
                  >
                    ${post.status === 'published' ? '轉草稿' : '發布'}
                  </button>
                  <button 
                    class="px-3 py-1 text-red-600 hover:bg-red-50 rounded transition-colors"
                    data-action="delete"
                    data-post-id="${post.id}"
                    data-post-title="${post.title}"
                  >
                    刪除
                  </button>
                </div>
              </td>
            </tr>
          `;
          }).join('')}
        </tbody>
      </table>
      
      <!-- 分頁 -->
      ${renderPagination(pagination)}
    `;
    
    // 綁定操作按鈕事件
    bindPostActions(container);
    
    // Update Navigo to handle new links
    router.updatePageLinks();
  } catch (error) {
    loading.hide();
    container.innerHTML = `
      <div class="text-center py-12">
        <div class="text-6xl mb-4">⚠️</div>
        <p class="text-red-600 mb-4">載入失敗：${error.message}</p>
        <button onclick="location.reload()" class="btn-primary">重試</button>
      </div>
    `;
  }
}

/**
 * 綁定文章操作按鈕事件
 */
function bindPostActions(container) {
  container.addEventListener('click', async (e) => {
    const button = e.target.closest('[data-action]');
    if (!button) return;
    
    const action = button.dataset.action;
    const postId = button.dataset.postId;
    
    switch (action) {
      case 'edit':
        router.navigate(`/admin/posts/${postId}/edit`);
        break;
        
      case 'toggle-status':
        await togglePostStatus(postId, button.dataset.currentStatus);
        break;
        
      case 'delete':
        await deletePost(postId, button.dataset.postTitle);
        break;
    }
  });
}

/**
 * 渲染分頁
 */
function renderPagination(pagination) {
  if (!pagination || pagination.total_pages <= 1) return '';
  
  const { page: current_page, total_pages } = pagination;
  const pages = [];
  
  // 生成頁碼
  for (let i = 1; i <= total_pages; i++) {
    if (
      i === 1 ||
      i === total_pages ||
      (i >= current_page - 2 && i <= current_page + 2)
    ) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...');
    }
  }
  
  return `
    <div class="flex items-center justify-between px-6 py-4 border-t border-modern-200">
      <div class="text-sm text-modern-600">
        第 ${current_page} 頁，共 ${total_pages} 頁
      </div>
      <div class="flex gap-2">
        <button 
          onclick="window.goToPage(${current_page - 1})"
          ${current_page === 1 ? 'disabled' : ''}
          class="px-3 py-1 border border-modern-300 rounded hover:bg-modern-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>
        ${pages.map(page => {
          if (page === '...') {
            return '<span class="px-3 py-1">...</span>';
          }
          return `
            <button 
              onclick="window.goToPage(${page})"
              class="px-3 py-1 border border-modern-300 rounded hover:bg-modern-50 ${
                page === current_page ? 'bg-accent-600 text-white border-accent-600' : ''
              }"
            >
              ${page}
            </button>
          `;
        }).join('')}
        <button 
          onclick="window.goToPage(${current_page + 1})"
          ${current_page === total_pages ? 'disabled' : ''}
          class="px-3 py-1 border border-modern-300 rounded hover:bg-modern-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </div>
    </div>
  `;
}

/**
 * 刪除文章
 */
async function deletePost(postId, postTitle) {
  // 先詢問使用者確認，只有確認後才刪除
  const confirmed = await confirmDelete(postTitle || '此文章');
  
  // 如果用戶取消，直接返回
  if (!confirmed) {
    return;
  }
  
  // 用戶確認後才執行刪除
  loading.show('刪除中...');
  
  try {
    await apiClient.delete(`/posts/${postId}`);
    loading.hide();
    toast.success('文章已刪除');
    await loadPosts();
  } catch (error) {
    loading.hide();
    toast.error('刪除失敗：' + error.message);
  }
}

/**
 * 切換文章狀態
 */
async function togglePostStatus(postId, currentStatus) {
  const newStatus = currentStatus === 'published' ? 'draft' : 'published';
  const action = newStatus === 'published' ? '發布' : '轉為草稿';
  
  loading.show(`${action}中...`);
  
  try {
    await apiClient.put(`/posts/${postId}`, { status: newStatus });
    loading.hide();
    toast.success(`文章已${action}`);
    await loadPosts();
  } catch (error) {
    loading.hide();
    toast.error(`${action}失敗：` + error.message);
  }
}

/**
 * 跳轉到指定頁碼
 */
function goToPage(page) {
  currentPage = page;
  loadPosts();
}

// 保留舊的全域函數供分頁使用
window.goToPage = goToPage;
