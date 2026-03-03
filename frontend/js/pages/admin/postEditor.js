import {
    renderDashboardLayout,
    bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { apiClient } from "../../api/client.js";
import { router } from "../../utils/router.js";
import { toast } from "../../utils/toast.js";
import {
    initRichTextEditor,
    destroyRichTextEditor,
    getRichTextEditorContent,
} from "../../components/RichTextEditor.js";
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
                formattedPublishDate = await formatDateTimeLocal(
                    post.publish_date
                );
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
    <div>
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">
          ${postId ? "編輯文章" : "新增文章"}
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
            value="${post?.title || ""}"
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
            ${
                postId && post?.created_at
                    ? `
              <div>
                <label class="block text-sm font-medium text-modern-700 mb-2">
                  建立時間
                </label>
                <div class="px-3 py-2 bg-modern-50 border border-modern-200 rounded-lg text-sm text-modern-700">
                  ${new Date(post.created_at).toLocaleString("zh-TW", {
                      year: "numeric",
                      month: "2-digit",
                      day: "2-digit",
                      hour: "2-digit",
                      minute: "2-digit",
                      second: "2-digit",
                      hour12: false, // 使用 24 時制
                  })}
                </div>
                <p class="text-sm text-modern-500 mt-1">
                  此為文章首次建立的時間，無法修改
                </p>
              </div>
            `
                    : ""
            }

            <div>
              <label for="status" class="block text-sm font-medium text-modern-700 mb-2">
                狀態
              </label>
              <select id="status" name="status" class="input-field">
                <option value="draft" ${
                    post?.status === "draft" ? "selected" : ""
                }>草稿</option>
                <option value="published" ${
                    post?.status === "published" ? "selected" : ""
                }>已發布</option>
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
                value="${formattedPublishDate}"
              />
              <p class="text-sm text-modern-500 mt-1">
                留空則使用當前時間。設定未來時間可實現定時發布。<br>
                <span class="text-accent-600">時區：${siteTimezone}</span>
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
              >${post?.excerpt || ""}</textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-modern-700 mb-2">
                標籤
              </label>
              <div id="tags-container" class="flex flex-wrap gap-2 mb-2">
                <!-- 已選標籤將顯示在這裡 -->
              </div>
              <select id="tag-selector" class="input-field">
                <option value="">選擇標籤...</option>
                <!-- 標籤選項將動態加載 -->
              </select>
              <p class="text-sm text-modern-500 mt-1">
                選擇文章的分類標籤
              </p>
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
              ${postId ? "更新文章" : "發布文章"}
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
 * 初始化富文本編輯器
 */
async function initCKEditor(post) {
    try {
        editorInstance = await initRichTextEditor("content", {
            placeholder: "開始撰寫文章內容...",
            initialValue: post?.content || "",
            minHeight: "400px",
        });

        // 監聽內容變化
        if (editorInstance) {
            editorInstance.model.document.on("change:data", () => {
                hasUnsavedChanges = true;
            });
        }

        console.log("[PostEditor] 富文本編輯器已初始化");
    } catch (error) {
        console.error("[PostEditor] 編輯器初始化失敗:", error);
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
            if (editorInstance) {
                editorInstance.setData(originalPostData.content || "");
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
            const content = getRichTextEditorContent("content");
            const data = {
                title: form.title.value,
                content: content || "",
                status: form.status.value,
                excerpt: form.excerpt.value,
            };

            await apiClient.put(`/posts/${postId}`, data);
            hasUnsavedChanges = false;
            console.log("Auto-saved at", new Date().toLocaleTimeString());
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
async function cleanupEditor() {
    if (editorInstance) {
        await destroyRichTextEditor("content");
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
    const content = getRichTextEditorContent("content") || "";

    console.log("[PostEditor] 儲存文章:", {
        title: form.title.value,
        contentLength: content.length,
        status: status || form.status.value,
    });

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
            form.publish_date.value
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
                })
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
            errorEl.textContent = Array.isArray(messages)
                ? messages[0]
                : messages;
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
            "inline-flex items-center gap-1 px-3 py-1 bg-accent-100 text-accent-700 rounded-full text-sm";
        tagEl.innerHTML = `
      ${tag.name}
      <button type="button" class="ml-1 w-5 h-5 flex items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors" data-remove-tag="${tagId}" title="移除標籤">
        <span class="text-xs font-bold leading-none">✕</span>
      </button>
    `;

        container.appendChild(tagEl);
    });
}
