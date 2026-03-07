import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { notification } from "../../utils/notification.js";
import { Modal } from "../../components/Modal.js";
import { apiClient } from "../../api/client.js";

/**
 * 標籤管理頁面
 */
export default class TagsPage {
  constructor() {
    this.tags = [];
    this.loading = false;
    this.modal = null;
  }

  async init() {
    await this.loadTags();
  }

  async loadTags() {
    try {
      this.loading = true;
      this.render();

      const result = await apiClient.get("/tags");

      if (result.success && result.data) {
        this.tags = result.data;
      } else {
        this.tags = [];
      }

      this.loading = false;
      this.render();
    } catch (error) {
      console.error("載入標籤列表失敗:", error);
      notification.error("載入標籤列表失敗");
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto pb-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h1 class="text-3xl font-bold text-modern-900">標籤管理</h1>
            <p class="text-sm text-modern-500 mt-1">組織與分類文章內容，提升資訊檢索效率</p>
          </div>
          <button
            id="addTagBtn"
            class="flex items-center justify-center gap-2 px-6 py-3 bg-accent-600 text-white rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all font-bold"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            新增內容標籤
          </button>
        </div>

        ${this.renderTagsList()}
      </div>

      <!-- Modal 容器 -->
      <div id="modal-container"></div>
    `;

    const app = document.getElementById("app");
    renderDashboardLayout(content, { title: "標籤管理" });
    bindDashboardLayoutEvents();
    this.attachEventListeners();
  }

  renderTagsList() {
    if (this.loading) {
      return `
        <div class="flex items-center justify-center py-24">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto mb-4"></div>
            <p class="text-modern-500 font-medium">載入標籤中...</p>
          </div>
        </div>
      `;
    }

    if (this.tags.length === 0) {
      return `
        <div class="text-center py-24 bg-white rounded-3xl border border-modern-200">
          <div class="w-20 h-20 bg-modern-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-modern-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          </div>
          <p class="text-modern-500 font-bold">目前尚無任何標籤</p>
          <p class="text-sm text-modern-400 mt-2">請點擊右上角的按鈕來建立第一個標籤</p>
        </div>
      `;
    }

    return `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        ${this.tags.map((tag) => this.renderTagCard(tag)).join("")}
      </div>
    `;
  }

  renderTagCard(tag) {
    const colorThemes = [
      {
        bg: "bg-blue-50/50",
        text: "text-blue-700",
        border: "border-blue-100",
        accent: "bg-blue-600",
      },
      {
        bg: "bg-emerald-50/50",
        text: "text-emerald-700",
        border: "border-emerald-100",
        accent: "bg-emerald-600",
      },
      {
        bg: "bg-amber-50/50",
        text: "text-amber-700",
        border: "border-amber-100",
        accent: "bg-amber-600",
      },
      {
        bg: "bg-purple-50/50",
        text: "text-purple-700",
        border: "border-purple-100",
        accent: "bg-purple-600",
      },
      {
        bg: "bg-rose-50/50",
        text: "text-rose-700",
        border: "border-rose-100",
        accent: "bg-rose-600",
      },
      {
        bg: "bg-indigo-50/50",
        text: "text-indigo-700",
        border: "border-indigo-100",
        accent: "bg-indigo-600",
      },
    ];

    const theme = colorThemes[tag.id % colorThemes.length];

    return `
      <div class="group card ${theme.bg} border ${theme.border} p-6 hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
          <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        </div>

        <div class="relative z-10">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
              <h3 class="text-xl font-bold ${theme.text} truncate mb-1">${this.escapeHtml(tag.name)}</h3>
              <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest">${this.escapeHtml(tag.slug || "no-slug")}</p>
            </div>
            <div class="flex flex-col items-end">
              <span class="px-2 py-1 bg-white border ${theme.border} rounded-lg text-xs font-bold ${theme.text} shadow-sm tabular-nums">
                ${tag.post_count || 0} 篇
              </span>
            </div>
          </div>

          ${
            tag.description
              ? `
            <p class="text-sm text-modern-600 line-clamp-2 mb-6 h-10">${this.escapeHtml(tag.description)}</p>
          `
              : '<div class="mb-6 h-10"></div>'
          }

          <div class="flex items-center gap-2">
            <button
              class="edit-tag-btn flex-1 flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-modern-600 bg-white border border-modern-200 rounded-xl hover:bg-modern-50 hover:text-accent-600 transition-all shadow-sm"
              data-tag-id="${tag.id}"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              編輯
            </button>
            <button
              class="delete-tag-btn p-2 text-modern-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
              data-tag-id="${tag.id}"
              title="刪除標籤"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    // 新增標籤按鈕
    const addTagBtn = document.getElementById("addTagBtn");
    if (addTagBtn) {
      addTagBtn.addEventListener("click", () => this.showTagModal());
    }

    // 編輯標籤按鈕
    const editBtns = document.querySelectorAll(".edit-tag-btn");
    editBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        const tagId = parseInt(btn.dataset.tagId);
        const tag = this.tags.find((t) => t.id === tagId);
        if (tag) {
          this.showTagModal(tag);
        }
      });
    });

    // 刪除標籤按鈕
    const deleteBtns = document.querySelectorAll(".delete-tag-btn");
    deleteBtns.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const tagId = parseInt(btn.dataset.tagId);
        await this.handleDeleteTag(tagId);
      });
    });
  }

  showTagModal(tag = null) {
    const isEdit = !!tag;
    const modalTitle = isEdit ? "編輯標籤" : "新增標籤";

    const modalContent = `
      <form id="tagForm" class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-bold text-modern-700 mb-2">
            標籤名稱 *
          </label>
          <input
            type="text"
            id="name"
            name="name"
            value="${tag ? this.escapeHtml(tag.name) : ""}"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
            required
            maxlength="50"
            placeholder="例如：技術公告"
          />
        </div>

        <div>
          <label for="slug" class="block text-sm font-bold text-modern-700 mb-2">
            URL 別名 (Slug)
          </label>
          <input
            type="text"
            id="slug"
            name="slug"
            value="${tag ? this.escapeHtml(tag.slug || "") : ""}"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
            maxlength="50"
            placeholder="例如：tech-news"
          />
          <p class="mt-2 text-xs text-modern-400">用於網址顯示，若留空系統將根據名稱自動生成</p>
        </div>

        <div>
          <label for="description" class="block text-sm font-bold text-modern-700 mb-2">
            標籤內容描述
          </label>
          <textarea
            id="description"
            name="description"
            rows="3"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
            maxlength="200"
            placeholder="簡單描述此標籤的用途..."
          >${tag ? this.escapeHtml(tag.description || "") : ""}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-modern-100">
          <button
            type="button"
            id="cancelModalBtn"
            class="px-6 py-2.5 text-sm font-bold text-modern-500 hover:text-modern-800 transition-colors"
          >
            取消
          </button>
          <button
            type="submit"
            class="px-8 py-2.5 text-sm font-bold text-white bg-accent-600 rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all"
          >
            ${isEdit ? "儲存變更" : "建立標籤"}
          </button>
        </div>
      </form>
    `;

    this.modal = new Modal({
      title: modalTitle,
      content: modalContent,
      size: "md",
    });
    this.modal.show();

    // 綁定表單事件
    const tagForm = document.getElementById("tagForm");
    if (tagForm) {
      tagForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (isEdit) {
          await this.handleUpdateTag(tag.id, new FormData(tagForm));
        } else {
          await this.handleCreateTag(new FormData(tagForm));
        }
      });
    }

    // 取消按鈕
    const cancelBtn = document.getElementById("cancelModalBtn");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => this.modal.hide());
    }
  }

  async handleCreateTag(formData) {
    try {
      const data = {
        name: formData.get("name"),
        slug: formData.get("slug") || undefined,
        description: formData.get("description") || undefined,
      };

      // 驗證
      if (!data.name || data.name.trim().length === 0) {
        notification.error("請輸入標籤名稱");
        return;
      }

      await apiClient.post("/tags", data);
      notification.success("標籤建立成功");
      this.modal.hide();
      await this.loadTags();
    } catch (error) {
      console.error("建立標籤失敗:", error);
      notification.error(error.message || "建立標籤失敗");
    }
  }

  async handleUpdateTag(tagId, formData) {
    try {
      const data = {
        name: formData.get("name"),
        slug: formData.get("slug") || undefined,
        description: formData.get("description") || undefined,
      };

      // 驗證
      if (!data.name || data.name.trim().length === 0) {
        notification.error("請輸入標籤名稱");
        return;
      }

      await apiClient.put(`/tags/${tagId}`, data);
      notification.success("標籤更新成功");
      this.modal.hide();
      await this.loadTags();
    } catch (error) {
      console.error("更新標籤失敗:", error);
      notification.error(error.message || "更新標籤失敗");
    }
  }

  async handleDeleteTag(tagId) {
    const tag = this.tags.find((t) => t.id === tagId);
    if (!tag) return;

    const postCount = tag.post_count || 0;
    let confirmMessage = `確定要刪除標籤「${tag.name}」嗎？`;

    if (postCount > 0) {
      confirmMessage += ` 刪除後，${postCount} 篇文章將會移除此標籤。`;
    }

    const confirmed = await notification.confirm({
      title: "確認刪除標籤",
      message: `${confirmMessage}\n\n此操作無法復原`,
      confirmText: "確認刪除",
      cancelText: "保留",
      tone: "danger",
    });

    if (!confirmed) {
      return;
    }

    try {
      await apiClient.delete(`/tags/${tagId}`);
      notification.success("標籤刪除成功");
      await this.loadTags();
    } catch (error) {
      console.error("刪除標籤失敗:", error);
      notification.error(error.message || "刪除標籤失敗");
    }
  }

  // 工具函式
  escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text ? String(text).replace(/[&<>"']/g, (m) => map[m]) : "";
  }
}

/**
 * 渲染標籤管理頁面（wrapper 函數）
 */
export async function renderTags() {
  const page = new TagsPage();
  await page.init();
}
