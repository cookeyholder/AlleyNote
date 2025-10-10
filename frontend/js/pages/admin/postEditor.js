import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { apiClient } from '../../api/client.js';
import { router } from '../../utils/router.js';
import { toast } from '../../utils/toast.js';
import { CKEditorWrapper } from '../../components/CKEditorWrapper.js';
import { confirmDiscard } from '../../components/ConfirmationDialog.js';
import { loading } from '../../components/Loading.js';

let editorInstance = null;
let hasUnsavedChanges = false;
let autoSaveTimer = null;
let originalPostData = null; // 保存原始文章數據用於取消操作

/**
 * 格式化日期時間為 datetime-local 輸入格式
 * 確保使用本地時區而不是 UTC
 */
function formatDateTimeLocal(dateString) {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  // 取得本地時間的年月日時分
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

/**
 * 渲染文章編輯器
 */
export async function renderPostEditor(postId = null) {
  let post = null;
  
  // 清理舊的編輯器
  cleanupEditor();
  
  // 如果是編輯模式，載入文章
  if (postId) {
    loading.show('載入文章中...');
    try {
      const result = await apiClient.get(`/posts/${postId}`);
      post = result.data;
      // 保存原始數據的深拷貝
      originalPostData = JSON.parse(JSON.stringify(post));
    } catch (error) {
      loading.hide();
      toast.error('載入文章失敗');
      router.navigate('/admin/posts');
      return;
    }
    loading.hide();
  } else {
    originalPostData = null;
  }
  
  const content = `
    <div>
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">
          ${postId ? '編輯文章' : '新增文章'}
        </h1>
        <a href="/admin/posts" class="text-modern-600 hover:text-modern-900">
          ← 返回列表
        </a>
      </div>
      
      <form id="post-form" class="space-y-6">
        <!-- 標題 -->
        <div class="card">
          <label for="title" class="block text-sm font-medium text-modern-700 mb-2">
            文章標題 *
          </label>
          <input
            type="text"
            id="title"
            name="title"
            required
            value="${post?.title || ''}"
            class="input-field text-2xl"
            placeholder="輸入文章標題..."
          />
          <p class="text-red-500 text-sm mt-1 hidden" data-error-for="title"></p>
        </div>
        
        <!-- 內容編輯器 -->
        <div class="card">
          <label class="block text-sm font-medium text-modern-700 mb-2">
            文章內容 *
          </label>
          <div id="editor-container">
            <div id="content"></div>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" data-error-for="content"></p>
          <p class="text-sm text-modern-500 mt-2 flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            自動儲存已啟用（每 30 秒）
          </p>
        </div>
        
        <!-- 設定 -->
        <div class="card">
          <h3 class="text-lg font-semibold text-modern-900 mb-4">發布設定</h3>
          
          <div class="space-y-4">
            <div>
              <label for="status" class="block text-sm font-medium text-modern-700 mb-2">
                狀態
              </label>
              <select id="status" name="status" class="input-field">
                <option value="draft" ${post?.status === 'draft' ? 'selected' : ''}>草稿</option>
                <option value="published" ${post?.status === 'published' ? 'selected' : ''}>已發布</option>
              </select>
            </div>
            
            <div>
              <label for="publish_date" class="block text-sm font-medium text-modern-700 mb-2">
                發布日期與時間
              </label>
              <input
                type="datetime-local"
                id="publish_date"
                name="publish_date"
                class="input-field"
                value="${post?.publish_date ? formatDateTimeLocal(post.publish_date) : ''}"
              />
              <p class="text-sm text-modern-500 mt-1">
                留空則使用當前時間。設定未來時間可實現定時發布。
              </p>
            </div>
            
            <div>
              <label for="excerpt" class="block text-sm font-medium text-modern-700 mb-2">
                摘要（選填）
              </label>
              <textarea
                id="excerpt"
                name="excerpt"
                rows="3"
                class="input-field"
                placeholder="文章摘要，將顯示在列表中..."
              >${post?.excerpt || ''}</textarea>
            </div>
          </div>
        </div>
        
        <!-- 操作按鈕 -->
        <div class="flex items-center justify-between">
          <button type="button" id="cancel-btn" class="btn-secondary">
            取消
          </button>
          <div class="flex gap-3">
            <button type="button" id="save-draft-btn" class="btn-secondary">
              儲存草稿
            </button>
            <button type="submit" id="submit-btn" class="btn-primary">
              ${postId ? '更新文章' : '發布文章'}
            </button>
          </div>
        </div>
      </form>
    </div>
  `;
  
  const app = document.getElementById('app');
  renderDashboardLayout(content, { title: postId ? '編輯文章' : '新增文章' });
  bindDashboardLayoutEvents();
  
  // 初始化 CKEditor
  await initCKEditor(post);
  
  // 綁定表單事件
  bindFormEvents(postId);
  
  // 啟動自動儲存
  startAutoSave(postId);
  
  // 離開頁面前提示
  setupBeforeUnload();
}

/**
 * 初始化 CKEditor
 */
async function initCKEditor(post) {
  try {
    editorInstance = new CKEditorWrapper();
    await editorInstance.init('content', post?.content || '');
    
    // 監聽內容變化
    if (editorInstance.editor) {
      editorInstance.editor.model.document.on('change:data', () => {
        hasUnsavedChanges = true;
      });
    }
    
    console.log('[PostEditor] CKEditor 已初始化');
  } catch (error) {
    console.error('[PostEditor] CKEditor 初始化失敗:', error);
    toast.error('編輯器初始化失敗，請重新整理頁面');
  }
}

/**
 * 綁定表單事件
 */
function bindFormEvents(postId) {
  const form = document.getElementById('post-form');
  const submitBtn = document.getElementById('submit-btn');
  const saveDraftBtn = document.getElementById('save-draft-btn');
  const cancelBtn = document.getElementById('cancel-btn');
  
  // 提交表單
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    await savePost(postId, 'published');
  });
  
  // 儲存草稿
  saveDraftBtn.addEventListener('click', async () => {
    await savePost(postId, 'draft');
  });
  
  // 取消
  cancelBtn.addEventListener('click', async () => {
    if (hasUnsavedChanges) {
      const confirmed = await confirmDiscard();
      
      if (!confirmed) return;
    }
    
    // 如果是編輯模式且有原始數據，恢復原始數據
    if (postId && originalPostData) {
      const form = document.getElementById('post-form');
      form.title.value = originalPostData.title || '';
      form.status.value = originalPostData.status || 'draft';
      form.excerpt.value = originalPostData.excerpt || '';
      form.publish_date.value = originalPostData.publish_date ? formatDateTimeLocal(originalPostData.publish_date) : '';
      
      // 恢復編輯器內容
      if (editorInstance && editorInstance.editor) {
        editorInstance.editor.setData(originalPostData.content || '');
      }
      
      hasUnsavedChanges = false;
      toast.info('已恢復原始內容');
      return;
    }
    
    cleanupEditor();
    router.navigate('/admin/posts');
  });
}

/**
 * 啟動自動儲存
 */
function startAutoSave(postId) {
  if (!postId) return; // 新建文章不自動儲存
  
  autoSaveTimer = setInterval(async () => {
    if (!hasUnsavedChanges) return;
    
    try {
      const form = document.getElementById('post-form');
      const data = {
        title: form.title.value,
        content: editorInstance.getData(),
        status: form.status.value,
        excerpt: form.excerpt.value,
      };
      
      await apiClient.put(`/posts/${postId}`, data);
      hasUnsavedChanges = false;
      console.log('Auto-saved at', new Date().toLocaleTimeString());
    } catch (error) {
      console.error('Auto-save failed:', error);
    }
  }, 30000); // 每 30 秒
}

/**
 * 設定離開頁面前提示
 */
function setupBeforeUnload() {
  window.addEventListener('beforeunload', (e) => {
    if (hasUnsavedChanges) {
      e.preventDefault();
      e.returnValue = '';
    }
  });
}

/**
 * 清理編輯器
 */
function cleanupEditor() {
  if (editorInstance) {
    editorInstance.destroy();
    editorInstance = null;
  }
  
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer);
    autoSaveTimer = null;
  }
  
  hasUnsavedChanges = false;
  originalPostData = null; // 清除原始數據
}

/**
 * 儲存文章
 */
async function savePost(postId, status) {
  const form = document.getElementById('post-form');
  const submitBtn = document.getElementById('submit-btn');
  
  // 取得編輯器內容
  let content = '';
  if (editorInstance && editorInstance.editor) {
    content = editorInstance.editor.getData();
  }
  
  console.log('[PostEditor] 儲存文章:', {
    title: form.title.value,
    contentLength: content.length,
    status: status || form.status.value
  });
  
  const data = {
    title: form.title.value.trim(),
    content: content,
    status: status || form.status.value,
    excerpt: form.excerpt.value.trim(),
  };
  
  // 添加發布日期時間 - 確保正確轉換為 UTC ISO 字串
  if (form.publish_date.value) {
    // datetime-local 輸入的值是本地時間，需要轉換為 UTC ISO
    const localDate = new Date(form.publish_date.value);
    data.publish_date = localDate.toISOString();
  }
  
  // 清除舊錯誤
  clearErrors();
  
  // 禁用按鈕
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = '儲存中...';
  
  try {
    if (postId) {
      await apiClient.put(`/posts/${postId}`, data);
      hasUnsavedChanges = false;
      // 更新原始數據為當前保存的數據
      originalPostData = JSON.parse(JSON.stringify({
        ...originalPostData,
        title: data.title,
        content: data.content,
        status: data.status,
        excerpt: data.excerpt,
        publish_date: data.publish_date
      }));
      toast.success('文章已更新');
    } else {
      const result = await apiClient.post('/posts', data);
      hasUnsavedChanges = false;
      toast.success('文章已建立');
      // 清理編輯器並返回列表頁面，讓列表重新載入
      cleanupEditor();
      setTimeout(() => {
        router.navigate('/admin/posts');
      }, 1000);
      return; // 提早返回，避免重新啟用按鈕
    }
  } catch (error) {
    console.error('[PostEditor] 儲存失敗:', error);
    
    // 檢查是否為驗證錯誤 (422)
    if (error.status === 422 && error.data && error.data.errors) {
      showValidationErrors(error.data.errors);
    } else {
      toast.error(error.message || '儲存失敗');
    }
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
  }
}

/**
 * 清除錯誤訊息
 */
function clearErrors() {
  document.querySelectorAll('[data-error-for]').forEach((el) => {
    el.textContent = '';
    el.classList.add('hidden');
  });
}

/**
 * 顯示驗證錯誤
 */
function showValidationErrors(errors) {
  Object.entries(errors).forEach(([field, messages]) => {
    const errorEl = document.querySelector(`[data-error-for="${field}"]`);
    if (errorEl) {
      errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
      errorEl.classList.remove('hidden');
    }
  });
}
