import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { apiClient } from '../../api/client.js';
import { toast } from '../../utils/toast.js';
import { confirmDelete } from '../../components/ConfirmationDialog.js';
import { loading } from '../../components/Loading.js';
import { router } from '../../utils/router.js';
import { timezoneUtils } from '../../utils/timezoneUtils.js';

let currentPage = 1;
let currentFilters = {};
let currentState = { posts: [], batchMode: false, selectedPosts: new Set() };

/**
 * 渲染文章列表頁面
 */
export async function renderPostsList() {
  const content = `
    <div>
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">文章管理</h1>
        <div class="flex gap-2">
          <button id="batch-delete-btn" class="btn-secondary">
            📋 批次刪除
          </button>
          <button id="create-post-btn" class="btn-primary">
            ✏️ 新增文章
          </button>
        </div>
      </div>
      
      <!-- 批次操作工具列 -->
      <div id="batch-toolbar" class="card mb-6 hidden">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <span class="text-modern-700 font-medium">
              已選擇 <span id="selected-count" class="text-accent-600 font-bold">0</span> 篇文章
            </span>
            <button id="select-all-btn" class="text-accent-600 hover:underline text-sm">全選</button>
            <button id="deselect-all-btn" class="text-modern-600 hover:underline text-sm">取消全選</button>
          </div>
          <div class="flex gap-2">
            <button id="confirm-batch-delete-btn" class="btn-danger">
              🗑️ 刪除選中的文章
            </button>
            <button id="cancel-batch-btn" class="btn-secondary">
              取消
            </button>
          </div>
        </div>
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
  
  // 綁定批次刪除按鈕
  document.getElementById('batch-delete-btn')?.addEventListener('click', toggleBatchMode);
  
  // 綁定批次操作按鈕
  document.getElementById('select-all-btn')?.addEventListener('click', selectAllPosts);
  document.getElementById('deselect-all-btn')?.addEventListener('click', deselectAllPosts);
  document.getElementById('confirm-batch-delete-btn')?.addEventListener('click', confirmBatchDelete);
  document.getElementById('cancel-batch-btn')?.addEventListener('click', cancelBatchMode);
  
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
    
    // 先格式化所有文章的時間
    const postsWithFormattedDates = await Promise.all(posts.map(async (post) => {
      const dateString = post.publish_date || post.created_at;
      const formattedDateTime = await timezoneUtils.utcToSiteTimezone(dateString, 'datetime');
      return { ...post, formattedDateTime };
    }));
    
    container.innerHTML = `
      <table class="w-full">
        <thead class="bg-modern-50">
          <tr>
            ${currentState.batchMode ? `
              <th class="px-6 py-3 text-left" style="width: 50px;">
                <input 
                  type="checkbox" 
                  id="select-all-checkbox"
                  class="w-4 h-4 text-accent-600 border-modern-300 rounded focus:ring-accent-500"
                />
              </th>
            ` : ''}
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">標題</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">作者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-modern-500 uppercase tracking-wider">發布時間</th>
            ${!currentState.batchMode ? `
              <th class="px-6 py-3 text-right text-xs font-medium text-modern-500 uppercase tracking-wider">操作</th>
            ` : ''}
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-modern-200">
          ${postsWithFormattedDates.map((post) => `
            <tr class="hover:bg-modern-50 ${currentState.selectedPosts.has(post.id) ? 'bg-accent-50' : ''}">
              ${currentState.batchMode ? `
                <td class="px-6 py-4">
                  <input 
                    type="checkbox" 
                    class="post-checkbox w-4 h-4 text-accent-600 border-modern-300 rounded focus:ring-accent-500"
                    data-post-id="${post.id}"
                    ${currentState.selectedPosts.has(post.id) ? 'checked' : ''}
                  />
                </td>
              ` : ''}
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
                ${post.formattedDateTime}
              </td>
              ${!currentState.batchMode ? `
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
              ` : ''}
            </tr>
          `).join('')}
        </tbody>
      </table>
      
      <!-- 分頁 -->
      ${renderPagination(pagination)}
    `;
    
    // 綁定操作按鈕事件
    bindPostActions(container);
    
    // 綁定批次選擇事件
    bindBatchSelection(container);
    
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
 * 切換批次模式
 */
function toggleBatchMode() {
  currentState.batchMode = !currentState.batchMode;
  currentState.selectedPosts.clear();
  
  const batchToolbar = document.getElementById('batch-toolbar');
  const batchBtn = document.getElementById('batch-delete-btn');
  
  if (currentState.batchMode) {
    batchToolbar?.classList.remove('hidden');
    batchBtn.textContent = '📋 取消批次';
    batchBtn.classList.remove('btn-secondary');
    batchBtn.classList.add('btn-primary');
  } else {
    batchToolbar?.classList.add('hidden');
    batchBtn.textContent = '📋 批次刪除';
    batchBtn.classList.remove('btn-primary');
    batchBtn.classList.add('btn-secondary');
  }
  
  updateSelectedCount();
  loadPosts(); // 重新渲染表格
}

/**
 * 綁定批次選擇事件
 */
function bindBatchSelection(container) {
  if (!currentState.batchMode) return;
  
  // 全選checkbox
  const selectAllCheckbox = document.getElementById('select-all-checkbox');
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', (e) => {
      if (e.target.checked) {
        selectAllPosts();
      } else {
        deselectAllPosts();
      }
    });
  }
  
  // 個別checkbox
  const checkboxes = container.querySelectorAll('.post-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', (e) => {
      const postId = parseInt(e.target.dataset.postId);
      const row = e.target.closest('tr');
      
      if (e.target.checked) {
        currentState.selectedPosts.add(postId);
        // 添加高亮樣式
        if (row && !row.classList.contains('bg-accent-50')) {
          row.classList.add('bg-accent-50');
        }
      } else {
        currentState.selectedPosts.delete(postId);
        // 移除高亮樣式
        if (row) {
          row.classList.remove('bg-accent-50');
        }
      }
      updateSelectedCount();
      updateSelectAllCheckbox();
    });
  });
}

/**
 * 全選文章
 */
function selectAllPosts() {
  currentState.posts.forEach(post => {
    currentState.selectedPosts.add(post.id);
  });
  updateSelectedCount();
  loadPosts(); // 重新渲染以更新checkbox狀態
}

/**
 * 取消全選
 */
function deselectAllPosts() {
  currentState.selectedPosts.clear();
  updateSelectedCount();
  loadPosts(); // 重新渲染以更新checkbox狀態
}

/**
 * 更新選中數量顯示
 */
function updateSelectedCount() {
  const countElement = document.getElementById('selected-count');
  if (countElement) {
    countElement.textContent = currentState.selectedPosts.size;
  }
}

/**
 * 更新全選checkbox狀態
 */
function updateSelectAllCheckbox() {
  const selectAllCheckbox = document.getElementById('select-all-checkbox');
  if (selectAllCheckbox && currentState.posts.length > 0) {
    const allSelected = currentState.posts.every(post => 
      currentState.selectedPosts.has(post.id)
    );
    selectAllCheckbox.checked = allSelected;
  }
}

/**
 * 確認批次刪除
 */
async function confirmBatchDelete() {
  if (currentState.selectedPosts.size === 0) {
    toast.error('請至少選擇一篇文章');
    return;
  }
  
  const count = currentState.selectedPosts.size;
  const confirmed = await confirmDelete(
    `確定要刪除選中的 ${count} 篇文章嗎？`,
    '此操作無法復原'
  );
  
  if (!confirmed) return;
  
  loading.show(`正在刪除 ${count} 篇文章...`);
  
  try {
    const deletePromises = Array.from(currentState.selectedPosts).map(postId =>
      apiClient.delete(`/posts/${postId}`)
    );
    
    await Promise.all(deletePromises);
    
    loading.hide();
    toast.success(`成功刪除 ${count} 篇文章`);
    
    // 退出批次模式並重新載入
    currentState.batchMode = false;
    currentState.selectedPosts.clear();
    cancelBatchMode();
    await loadPosts();
  } catch (error) {
    loading.hide();
    toast.error('批次刪除失敗：' + error.message);
  }
}

/**
 * 取消批次模式
 */
function cancelBatchMode() {
  currentState.batchMode = false;
  currentState.selectedPosts.clear();
  
  const batchToolbar = document.getElementById('batch-toolbar');
  const batchBtn = document.getElementById('batch-delete-btn');
  
  batchToolbar?.classList.add('hidden');
  if (batchBtn) {
    batchBtn.textContent = '📋 批次刪除';
    batchBtn.classList.remove('btn-primary');
    batchBtn.classList.add('btn-secondary');
  }
  
  loadPosts(); // 重新渲染表格
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
