import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { postsAPI } from '../../api/modules/posts.js';
import { router } from '../../router/index.js';
import { toast } from '../../utils/toast.js';

/**
 * 渲染文章編輯器
 */
export async function renderPostEditor(postId = null) {
  let post = null;
  
  // 如果是編輯模式，載入文章
  if (postId) {
    try {
      post = await postsAPI.get(postId);
    } catch (error) {
      toast.error('載入文章失敗');
      router.navigate('/admin/posts');
      return;
    }
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
          <div class="prose max-w-none">
            <textarea
              id="content"
              name="content"
              rows="15"
              class="input-field font-mono text-sm"
              placeholder="輸入文章內容...&#10;&#10;提示：未來版本將整合 CKEditor 5 富文本編輯器"
            >${post?.content || ''}</textarea>
          </div>
          <p class="text-red-500 text-sm mt-1 hidden" data-error-for="content"></p>
          <p class="text-sm text-modern-500 mt-2">
            💡 提示：目前使用純文字編輯器，CKEditor 5 整合將在下一版本完成
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
  app.innerHTML = renderDashboardLayout(content);
  bindDashboardLayoutEvents();
  bindFormEvents(postId);
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
  cancelBtn.addEventListener('click', () => {
    if (confirm('確定要離開嗎？未儲存的變更將會遺失。')) {
      router.navigate('/admin/posts');
    }
  });
}

/**
 * 儲存文章
 */
async function savePost(postId, status) {
  const form = document.getElementById('post-form');
  const submitBtn = document.getElementById('submit-btn');
  
  const data = {
    title: form.title.value,
    content: form.content.value,
    status: status || form.status.value,
    excerpt: form.excerpt.value,
  };
  
  // 清除舊錯誤
  clearErrors();
  
  // 禁用按鈕
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = '儲存中...';
  
  try {
    if (postId) {
      await postsAPI.update(postId, data);
      toast.success('文章已更新');
    } else {
      const result = await postsAPI.create(data);
      toast.success('文章已建立');
      router.navigate(`/admin/posts/${result.id}/edit`);
    }
  } catch (error) {
    if (error.isValidationError()) {
      showValidationErrors(error.getValidationErrors());
    } else {
      toast.error(error.getUserMessage());
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
