import {
    renderDashboardLayout,
    bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { toast } from "../../utils/toast.js";
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
            toast.error("載入標籤列表失敗");
            this.loading = false;
            this.render();
        }
    }

    render() {
        const content = `
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-3xl font-bold text-modern-900">標籤管理</h1>
          <button
            id="addTagBtn"
            class="px-6 py-3 bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition-colors font-medium"
          >
            <i class="fas fa-plus mr-2"></i>
            新增標籤
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
        <div class="bg-white rounded-2xl border border-modern-200 p-8">
          <div class="text-center text-modern-500">
            <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
            <p>載入中...</p>
          </div>
        </div>
      `;
        }

        if (this.tags.length === 0) {
            return `
        <div class="bg-white rounded-2xl border border-modern-200 p-8">
          <div class="text-center text-modern-500">
            <i class="fas fa-tags text-4xl mb-4"></i>
            <p>尚無標籤資料</p>
          </div>
        </div>
      `;
        }

        return `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        ${this.tags.map((tag) => this.renderTagCard(tag)).join("")}
      </div>
    `;
    }

    renderTagCard(tag) {
        const colors = [
            {
                bg: "bg-blue-100",
                text: "text-blue-800",
                border: "border-blue-300",
            },
            {
                bg: "bg-green-100",
                text: "text-green-800",
                border: "border-green-300",
            },
            {
                bg: "bg-yellow-100",
                text: "text-yellow-800",
                border: "border-yellow-300",
            },
            {
                bg: "bg-purple-100",
                text: "text-purple-800",
                border: "border-purple-300",
            },
            {
                bg: "bg-pink-100",
                text: "text-pink-800",
                border: "border-pink-300",
            },
            {
                bg: "bg-indigo-100",
                text: "text-indigo-800",
                border: "border-indigo-300",
            },
        ];

        // 根據標籤 ID 選擇顏色
        const color = colors[tag.id % colors.length];

        return `
      <div class="bg-white rounded-xl border-2 ${
          color.border
      } p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h3 class="text-lg font-semibold ${
                color.text
            } mb-2">${this.escapeHtml(tag.name)}</h3>
            ${
                tag.description
                    ? `
              <p class="text-sm text-modern-600">${this.escapeHtml(
                  tag.description
              )}</p>
            `
                    : ""
            }
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
              color.bg
          } ${color.text}">
            ${tag.post_count || 0} 篇
          </span>
        </div>

        <div class="flex items-center gap-2 pt-4 border-t border-modern-100">
          <button
            class="edit-tag-btn flex-1 px-4 py-2 text-sm font-medium text-accent-700 border-2 border-accent-600 rounded-lg hover:bg-accent-50 transition-colors"
            data-tag-id="${tag.id}"
          >
            <i class="fas fa-edit mr-1"></i> 編輯
          </button>
          <button
            class="delete-tag-btn flex-1 px-4 py-2 text-sm font-medium text-red-700 border-2 border-red-600 rounded-lg hover:bg-red-50 transition-colors"
            data-tag-id="${tag.id}"
          >
            <i class="fas fa-trash mr-1"></i> 刪除
          </button>
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
          <label for="name" class="block text-sm font-medium text-modern-700 mb-2">
            標籤名稱 *
          </label>
          <input
            type="text"
            id="name"
            name="name"
            value="${tag ? this.escapeHtml(tag.name) : ""}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
            maxlength="50"
            placeholder="例如：前端開發"
          />
        </div>

        <div>
          <label for="slug" class="block text-sm font-medium text-modern-700 mb-2">
            別名 (URL Slug)
          </label>
          <input
            type="text"
            id="slug"
            name="slug"
            value="${tag ? this.escapeHtml(tag.slug || "") : ""}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            maxlength="50"
            placeholder="例如：frontend-dev（選填，自動生成）"
          />
          <p class="mt-1 text-sm text-modern-500">用於 URL，若留空則自動生成</p>
        </div>

        <div>
          <label for="description" class="block text-sm font-medium text-modern-700 mb-2">
            描述
          </label>
          <textarea
            id="description"
            name="description"
            rows="3"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            maxlength="200"
            placeholder="標籤的簡短描述（選填）"
          >${tag ? this.escapeHtml(tag.description || "") : ""}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            id="cancelModalBtn"
            class="px-6 py-3 text-sm font-medium text-modern-700 border-2 border-modern-300 rounded-lg hover:bg-modern-50 transition-colors"
          >
            取消
          </button>
          <button
            type="submit"
            class="px-6 py-3 text-sm font-medium text-white bg-accent-600 rounded-lg hover:bg-accent-700 transition-colors"
          >
            ${isEdit ? "儲存變更" : "新增標籤"}
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
                toast.error("請輸入標籤名稱");
                return;
            }

            await apiClient.post("/tags", data);
            toast.success("標籤建立成功", 5000);
            this.modal.hide();
            await this.loadTags();
        } catch (error) {
            console.error("建立標籤失敗:", error);
            toast.error(error.message || "建立標籤失敗");
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
                toast.error("請輸入標籤名稱");
                return;
            }

            await apiClient.put(`/tags/${tagId}`, data);
            toast.success("標籤更新成功");
            this.modal.hide();
            await this.loadTags();
        } catch (error) {
            console.error("更新標籤失敗:", error);
            toast.error(error.message || "更新標籤失敗");
        }
    }

    async handleDeleteTag(tagId) {
        const tag = this.tags.find((t) => t.id === tagId);
        if (!tag) return;

        const postCount = tag.post_count || 0;
        let confirmMessage = `確定要刪除標籤「${tag.name}」嗎？`;

        if (postCount > 0) {
            confirmMessage += `\n\n此標籤目前有 ${postCount} 篇文章使用，刪除後這些文章將會移除此標籤。`;
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            await apiClient.delete(`/tags/${tagId}`);
            toast.success("標籤刪除成功");
            await this.loadTags();
        } catch (error) {
            console.error("刪除標籤失敗:", error);
            toast.error(error.message || "刪除標籤失敗");
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
