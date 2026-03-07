import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { apiClient } from "../../api/client.js";
import { router } from "../../utils/router.js";
import { toast } from "../../utils/toast.js";
import { CKEditorWrapper } from "../../components/CKEditorWrapper.js";
import { confirmDiscard } from "../../components/ConfirmationDialog.js";
import { loading } from "../../components/Loading.js";
import { timezoneUtils } from "../../utils/timezoneUtils.js";

let editorInstance = null;
let hasUnsavedChanges = false;
let autoSaveTimer = null;
let originalPostData = null; // 保存原始文章數據用於取消操作
let siteTimezone = "Asia/Taipei"; // 網站時區快取
let availableTags = []; // 可用標籤列表
let selectedTagIds = []; // 選中的標籤 ID

/**
 * 格式化日期時間為 datetime-local 輸入格式
 * 使用網站時區
 */
async function formatDateTimeLocal(dateString) {
  if (!dateString) return "";

  // 使用時區工具轉換
  return await timezoneUtils.toDateTimeLocalFormat(dateString);
}

/**
 * 渲染文章編輯器
 */
export async function renderPostEditor(postId = null) {
  let post = null;

  // 清理舊的編輯器
  cleanupEditor();

  // 載入網站時區
  siteTimezone = await timezoneUtils.getSiteTimezone();

  // 載入標籤列表
  await loadTags();

  // 如果是編輯模式，載入文章
  let formattedPublishDate = "";
  if (postId) {
    loading.show("載入文章中...");
    try {
      // 管理員編輯頁面需要能夠訪問未來的文章
      const result = await apiClient.get(`/posts/${postId}`, {
        params: { include_future: true },
      });
      post = result.data;
      // 保存原始數據的深拷貝
      originalPostData = JSON.parse(JSON.stringify(post));

      // 設定選中的標籤
      selectedTagIds = (post.tags || []).map((tag) => tag.id);

      // 格式化發布日期
      if (post.publish_date) {
        formattedPublishDate = await formatDateTimeLocal(post.publish_date);
      }
    } catch (error) {
      loading.hide();
      toast.error("載入文章失敗");
      router.navigate("/admin/posts");
      return;
    }
    loading.hide();
  } else {
    originalPostData = null;
    selectedTagIds = [];
  }

  const content = `
    <div class="max-w-5xl mx-auto pb-12">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-modern-900">
            ${postId ? "編輯現有文章" : "撰寫新文章"}
          </h1>
          <p class="text-sm text-modern-500 mt-1">創作出色的內容並精確設定發布參數</p>
        </div>
        <a href="/admin/posts" data-navigo class="flex items-center gap-2 px-4 py-2 bg-white border border-modern-200 text-modern-600 font-bold rounded-xl hover:bg-modern-50 transition-all">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          返回列表
        </a>
      </div>
      
      <form id="post-form" class="space-y-8">
        <!-- 標題 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8">
          <label for="title" class="block text-xs font-bold text-modern-400 uppercase tracking-widest mb-3">
            文章標題內容 *
          </label>
          <input
            type="text"
            id="title"
            name="title"
            required
            value="${post?.title || ""}"
            class="w-full px-0 py-2 text-3xl font-bold text-modern-900 border-b-2 border-modern-100 focus:border-accent-600 focus:outline-none bg-transparent transition-all placeholder:text-modern-200"
            placeholder="請輸入文章標題..."
          />
          <p class="text-red-500 text-xs font-bold mt-2 hidden" data-error-for="title"></p>
        </div>
        
        <!-- 內容編輯器 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8">
          <div class="flex items-center justify-between mb-4">
            <label class="block text-xs font-bold text-modern-400 uppercase tracking-widest">
              正文內容編輯 *
            </label>
            <div class="flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
              </span>
              <span class="text-[10px] font-bold uppercase tracking-wider">Auto Save Active</span>
            </div>
          </div>
          <div id="editor-container" class="prose-modern">
            <div id="content"></div>
          </div>
          <p class="text-red-500 text-xs font-bold mt-2 hidden" data-error-for="content"></p>
        </div>
        
        <!-- 設定 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2 space-y-8">
            <div class="card bg-white border-modern-200 shadow-sm p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-accent-50 text-accent-600 rounded-lg">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </div>
                <h3 class="text-lg font-bold text-modern-900">內容摘要與分類</h3>
              </div>
              
              <div class="space-y-6">
                <div>
                  <label for="excerpt" class="block text-sm font-bold text-modern-700 mb-2">
                    文章摘要內容 (選填)
                  </label>
                  <textarea
                    id="excerpt"
                    name="excerpt"
                    rows="4"
                    class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
                    placeholder="簡單介紹這篇文章，將顯示在列表與搜尋結果中..."
                  >${post?.excerpt || ""}</textarea>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-modern-700 mb-3">
                    關聯標籤設定
                  </label>
                  <div id="tags-container" class="flex flex-wrap gap-2 mb-4 p-4 bg-modern-50 rounded-2xl border border-modern-100 min-h-[60px]">
                    <!-- 已選標籤 -->
                  </div>
                  <div class="relative">
                    <select id="tag-selector" class="w-full px-4 py-3 bg-white border border-modern-200 rounded-xl focus:ring-2 focus:ring-accent-500 transition-all outline-none appearance-none">
                      <option value="">從現有標籤中選擇...</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-modern-400">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-8">
            <div class="card bg-white border-modern-200 shadow-sm p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-modern-900">發布參數</h3>
              </div>
              
              <div class="space-y-6">
                <div>
                  <label for="status" class="block text-sm font-bold text-modern-700 mb-2">發布狀態</label>
                  <select id="status" name="status" class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none appearance-none font-bold">
                    <option value="draft" ${post?.status === "draft" ? "selected" : ""}>草稿 (僅限內部可見)</option>
                    <option value="published" ${post?.status === "published" ? "selected" : ""}>已發布 (對大眾公開)</option>
                  </select>
                </div>
                
                <div>
                  <label for="publish_date" class="block text-sm font-bold text-modern-700 mb-2">排程發布時間</label>
                  <input
                    type="datetime-local"
                    id="publish_date"
                    name="publish_date"
                    class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none tabular-nums"
                    value="${formattedPublishDate}"
                  />
                  <div class="mt-3 p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-tight">Timezone: ${siteTimezone}</span>
                  </div>
                </div>

                ${
                  postId && post?.created_at
                    ? `
                  <div class="pt-4 border-t border-modern-100">
                    <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">文章最初建立於</p>
                    <p class="text-sm font-bold text-modern-700 tabular-nums">
                      ${new Date(post.created_at).toLocaleString("zh-TW", {
                        year: "numeric",
                        month: "2-digit",
                        day: "2-digit",
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                      })}
                    </p>
                  </div>
                `
                    : ""
                }
              </div>
            </div>
          </div>
        </div>
        
        <!-- 操作按鈕列 -->
        <div class="flex flex-col md:flex-row items-center justify-between p-6 bg-modern-900 rounded-3xl shadow-xl shadow-modern-200 animate-slide-up">
          <button type="button" id="cancel-btn" class="w-full md:w-auto px-8 py-3 text-modern-400 font-bold hover:text-white transition-colors">
            捨棄並返回
          </button>
          <div class="flex gap-3 w-full md:w-auto mt-4 md:mt-0">
            <button type="button" id="save-draft-btn" class="flex-1 md:flex-none px-8 py-3 bg-modern-800 text-modern-300 font-bold rounded-2xl hover:bg-modern-700 hover:text-white transition-all">
              儲存為草稿
            </button>
            <button type="submit" id="submit-btn" class="flex-1 md:flex-none px-10 py-3 bg-accent-600 text-white font-bold rounded-2xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all">
              ${postId ? "更新並發布" : "立即發布文章"}
            </button>
          </div>
        </div>
      </form>
    </div>
  `;

  const app = document.getElementById("app");
  renderDashboardLayout(content, { title: postId ? "編輯文章" : "新增文章" });
  bindDashboardLayoutEvents();

  // 初始化 CKEditor
  await initCKEditor(post);

  // 初始化標籤選擇器
  initTagSelector();

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
    await editorInstance.init("content", post?.content || "");

    // 監聽內容變化
    if (editorInstance.editor) {
      editorInstance.editor.model.document.on("change:data", () => {
        hasUnsavedChanges = true;
      });
    }
  } catch (error) {
    console.error("[PostEditor] CKEditor 初始化失敗:", error);
    toast.error("編輯器初始化失敗，請重新整理頁面");
  }
}

/**
 * 綁定表單事件
 */
function bindFormEvents(postId) {
  const form = document.getElementById("post-form");
  const submitBtn = document.getElementById("submit-btn");
  const saveDraftBtn = document.getElementById("save-draft-btn");
  const cancelBtn = document.getElementById("cancel-btn");

  // 提交表單
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    await savePost(postId, "published");
  });

  // 儲存草稿
  saveDraftBtn.addEventListener("click", async () => {
    await savePost(postId, "draft");
  });

  // 取消
  cancelBtn.addEventListener("click", async () => {
    if (hasUnsavedChanges) {
      const confirmed = await confirmDiscard();

      if (!confirmed) return;
    }

    // 如果是編輯模式且有原始數據，恢復原始數據
    if (postId && originalPostData) {
      const form = document.getElementById("post-form");
      form.title.value = originalPostData.title || "";
      form.status.value = originalPostData.status || "draft";
      form.excerpt.value = originalPostData.excerpt || "";
      // 使用時區工具格式化
      form.publish_date.value = originalPostData.publish_date
        ? await formatDateTimeLocal(originalPostData.publish_date)
        : "";

      // 恢復編輯器內容
      if (editorInstance && editorInstance.editor) {
        editorInstance.editor.setData(originalPostData.content || "");
      }

      hasUnsavedChanges = false;
      toast.info("已恢復原始內容");
      return;
    }

    cleanupEditor();
    router.navigate("/admin/posts");
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
      const form = document.getElementById("post-form");
      const data = {
        title: form.title.value,
        content: editorInstance.getData(),
        status: form.status.value,
        excerpt: form.excerpt.value,
      };

      await apiClient.put(`/posts/${postId}`, data);
      hasUnsavedChanges = false;
    } catch (error) {
      console.error("Auto-save failed:", error);
    }
  }, 30000); // 每 30 秒
}

/**
 * 設定離開頁面前提示
 */
function setupBeforeUnload() {
  window.addEventListener("beforeunload", (e) => {
    if (hasUnsavedChanges) {
      e.preventDefault();
      e.returnValue = "";
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
  selectedTagIds = []; // 清除標籤選擇
  availableTags = []; // 清除標籤列表
}

/**
 * 儲存文章
 */
async function savePost(postId, status) {
  const form = document.getElementById("post-form");
  const submitBtn = document.getElementById("submit-btn");

  // 取得編輯器內容
  let content = "";
  if (editorInstance && editorInstance.editor) {
    content = editorInstance.editor.getData();
  }

  const data = {
    title: form.title.value.trim(),
    content: content,
    status: status || form.status.value,
    excerpt: form.excerpt.value.trim(),
    tag_ids: selectedTagIds, // 添加標籤 ID
  };

  // 添加發布日期時間 - 使用時區工具轉換為 UTC
  if (form.publish_date.value) {
    // datetime-local 輸入的值是網站時區，需要轉換為 UTC ISO
    data.publish_date = await timezoneUtils.fromDateTimeLocalFormat(
      form.publish_date.value,
    );
  }

  // 清除舊錯誤
  clearErrors();

  // 禁用按鈕
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = "儲存中...";

  try {
    if (postId) {
      await apiClient.put(`/posts/${postId}`, data);
      hasUnsavedChanges = false;
      // 更新原始數據為當前保存的數據
      originalPostData = JSON.parse(
        JSON.stringify({
          ...originalPostData,
          title: data.title,
          content: data.content,
          status: data.status,
          excerpt: data.excerpt,
          publish_date: data.publish_date,
        }),
      );
      toast.success("文章已更新");
    } else {
      const result = await apiClient.post("/posts", data);
      hasUnsavedChanges = false;
      toast.success("文章已建立");
      // 清理編輯器並返回列表頁面，讓列表重新載入
      cleanupEditor();
      setTimeout(() => {
        router.navigate("/admin/posts");
      }, 1000);
      return; // 提早返回，避免重新啟用按鈕
    }
  } catch (error) {
    console.error("[PostEditor] 儲存失敗:", error);

    // 檢查是否為驗證錯誤 (422)
    if (error.status === 422 && error.data && error.data.errors) {
      showValidationErrors(error.data.errors);
    } else {
      toast.error(error.message || "儲存失敗");
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
  document.querySelectorAll("[data-error-for]").forEach((el) => {
    el.textContent = "";
    el.classList.add("hidden");
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
      errorEl.classList.remove("hidden");
    }
  });
}

/**
 * 載入標籤列表
 */
async function loadTags() {
  try {
    const result = await apiClient.get("/tags");
    if (result.success && result.data) {
      availableTags = result.data;
    }
  } catch (error) {
    console.error("[PostEditor] 載入標籤失敗:", error);
    availableTags = [];
  }
}

/**
 * 初始化標籤選擇器
 */
function initTagSelector() {
  const selector = document.getElementById("tag-selector");
  const container = document.getElementById("tags-container");

  if (!selector || !container) return;

  // 填充標籤選項
  selector.innerHTML = '<option value="">選擇標籤...</option>';
  availableTags.forEach((tag) => {
    const option = document.createElement("option");
    option.value = tag.id;
    option.textContent = tag.name;
    selector.appendChild(option);
  });

  // 渲染已選標籤
  renderSelectedTags();

  // 綁定選擇事件
  selector.addEventListener("change", (e) => {
    const tagId = parseInt(e.target.value);
    if (tagId && !selectedTagIds.includes(tagId)) {
      selectedTagIds.push(tagId);
      renderSelectedTags();
      hasUnsavedChanges = true;
    }
    selector.value = "";
  });

  // 使用事件委派處理移除標籤按鈕
  container.addEventListener("click", (e) => {
    const removeBtn = e.target.closest("[data-remove-tag]");
    if (removeBtn) {
      const tagId = parseInt(removeBtn.getAttribute("data-remove-tag"));
      selectedTagIds = selectedTagIds.filter((id) => id !== tagId);
      renderSelectedTags();
      hasUnsavedChanges = true;
    }
  });
}

/**
 * 渲染已選標籤
 */
function renderSelectedTags() {
  const container = document.getElementById("tags-container");
  if (!container) return;

  container.innerHTML = "";

  if (selectedTagIds.length === 0) {
    container.innerHTML =
      '<span class="text-sm text-modern-500">尚未選擇標籤</span>';
    return;
  }

  selectedTagIds.forEach((tagId) => {
    const tag = availableTags.find((t) => t.id === tagId);
    if (!tag) return;

    const tagEl = document.createElement("span");
    tagEl.className =
      "inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-accent-200 text-accent-700 rounded-xl text-xs font-bold shadow-sm animate-fade-in";
    tagEl.innerHTML = `
      ${tag.name}
      <button type="button" class="w-4 h-4 flex items-center justify-center rounded-full bg-accent-100 text-accent-600 hover:bg-red-500 hover:text-white transition-all" data-remove-tag="${tagId}" title="移除此標籤">
        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    `;

    container.appendChild(tagEl);
  });
}
