import {
    renderDashboardLayout,
    bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { authAPI } from "../../api/modules/auth.js";
import { usersAPI } from "../../api/modules/users.js";
import { toast } from "../../utils/toast.js";

/**
 * 個人資料頁面
 */
export default class ProfilePage {
    constructor() {
        this.user = null;
        this.isEditingInfo = false;
        this.isEditingPassword = false;
    }

    async init() {
        // 載入使用者資訊
        await this.loadUserInfo();
    }

    async loadUserInfo() {
        try {
            const response = await authAPI.me();
            this.user = response;
            this.render();
        } catch (error) {
            console.error("載入使用者資訊失敗:", error);
            toast.error("載入使用者資訊失敗");
        }
    }

    render() {
        const content = `
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-modern-900 mb-8">個人資料</h1>

        <!-- 個人資訊卡片 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-8 mb-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-modern-900">基本資訊</h2>
            <button
              id="editInfoBtn"
              class="px-4 py-2 text-sm font-medium text-accent-700 border-2 border-accent-600 rounded-lg hover:bg-accent-50 transition-colors"
            >
              ${this.isEditingInfo ? "取消編輯" : "編輯資訊"}
            </button>
          </div>

          ${
              this.isEditingInfo
                  ? this.renderEditInfoForm()
                  : this.renderInfoDisplay()
          }
        </div>

        <!-- 修改密碼卡片 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-8">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-modern-900">修改密碼</h2>
            <button
              id="editPasswordBtn"
              class="px-4 py-2 text-sm font-medium text-accent-700 border-2 border-accent-600 rounded-lg hover:bg-accent-50 transition-colors"
            >
              ${this.isEditingPassword ? "取消" : "修改密碼"}
            </button>
          </div>

          ${
              this.isEditingPassword
                  ? this.renderPasswordForm()
                  : this.renderPasswordPlaceholder()
          }
        </div>
      </div>
    `;

        const app = document.getElementById("app");
        renderDashboardLayout(content, { title: "個人資料" });
        bindDashboardLayoutEvents();
        this.attachEventListeners();
    }

    renderInfoDisplay() {
        if (!this.user) {
            return '<div class="text-center text-modern-500">載入中...</div>';
        }

        return `
      <div class="space-y-4">
        <div class="flex items-center justify-between py-3 border-b border-modern-100">
          <span class="text-modern-600 font-medium">使用者名稱</span>
          <span class="text-modern-900">${this.escapeHtml(
              this.user.username || "-"
          )}</span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-modern-100">
          <span class="text-modern-600 font-medium">電子郵件</span>
          <span class="text-modern-900">${this.escapeHtml(
              this.user.email || "-"
          )}</span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-modern-100">
          <span class="text-modern-600 font-medium">角色</span>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
              this.user.role === "super_admin"
                  ? "bg-purple-100 text-purple-800"
                  : "bg-blue-100 text-blue-800"
          }">
            ${this.user.role === "super_admin" ? "主管理員" : "管理員"}
          </span>
        </div>
        <div class="flex items-center justify-between py-3 border-b border-modern-100">
          <span class="text-modern-600 font-medium">註冊日期</span>
          <span class="text-modern-900">${this.formatDate(
              this.user.created_at
          )}</span>
        </div>
        <div class="flex items-center justify-between py-3">
          <span class="text-modern-600 font-medium">上次登入</span>
          <span class="text-modern-900">${this.formatDate(
              this.user.last_login
          )}</span>
        </div>
      </div>
    `;
    }

    renderEditInfoForm() {
        if (!this.user) return "";

        return `
      <form id="editInfoForm" class="space-y-6">
        <div>
          <label for="username" class="block text-sm font-medium text-modern-700 mb-2">
            使用者名稱
          </label>
          <input
            type="text"
            id="username"
            name="username"
            value="${this.escapeHtml(this.user.username || "")}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-modern-700 mb-2">
            電子郵件
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value="${this.escapeHtml(this.user.email || "")}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            id="cancelInfoBtn"
            class="px-6 py-3 text-sm font-medium text-modern-700 border-2 border-modern-300 rounded-lg hover:bg-modern-50 transition-colors"
          >
            取消
          </button>
          <button
            type="submit"
            class="px-6 py-3 text-sm font-medium text-white bg-accent-600 rounded-lg hover:bg-accent-700 transition-colors"
          >
            儲存變更
          </button>
        </div>
      </form>
    `;
    }

    renderPasswordPlaceholder() {
        return `
      <div class="text-center text-modern-500 py-8">
        <i class="fas fa-lock text-4xl mb-4"></i>
        <p>點擊「修改密碼」按鈕開始修改您的密碼</p>
      </div>
    `;
    }

    renderPasswordForm() {
        return `
      <form id="changePasswordForm" class="space-y-6">
        <div>
          <label for="current_password" class="block text-sm font-medium text-modern-700 mb-2">
            目前密碼
          </label>
          <input
            type="password"
            id="current_password"
            name="current_password"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="new_password" class="block text-sm font-medium text-modern-700 mb-2">
            新密碼
          </label>
          <input
            type="password"
            id="new_password"
            name="new_password"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            minlength="8"
            required
          />
          <p class="mt-1 text-sm text-modern-500">密碼長度至少 8 個字元</p>
        </div>

        <div>
          <label for="new_password_confirmation" class="block text-sm font-medium text-modern-700 mb-2">
            確認新密碼
          </label>
          <input
            type="password"
            id="new_password_confirmation"
            name="new_password_confirmation"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            minlength="8"
            required
          />
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            id="cancelPasswordBtn"
            class="px-6 py-3 text-sm font-medium text-modern-700 border-2 border-modern-300 rounded-lg hover:bg-modern-50 transition-colors"
          >
            取消
          </button>
          <button
            type="submit"
            class="px-6 py-3 text-sm font-medium text-white bg-accent-600 rounded-lg hover:bg-accent-700 transition-colors"
          >
            修改密碼
          </button>
        </div>
      </form>
    `;
    }

    attachEventListeners() {
        // 編輯資訊按鈕
        const editInfoBtn = document.getElementById("editInfoBtn");
        if (editInfoBtn) {
            editInfoBtn.addEventListener("click", () => {
                this.isEditingInfo = !this.isEditingInfo;
                this.render();
            });
        }

        // 取消編輯資訊
        const cancelInfoBtn = document.getElementById("cancelInfoBtn");
        if (cancelInfoBtn) {
            cancelInfoBtn.addEventListener("click", () => {
                this.isEditingInfo = false;
                this.render();
            });
        }

        // 提交編輯資訊表單
        const editInfoForm = document.getElementById("editInfoForm");
        if (editInfoForm) {
            editInfoForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                await this.handleUpdateInfo(new FormData(editInfoForm));
            });
        }

        // 修改密碼按鈕
        const editPasswordBtn = document.getElementById("editPasswordBtn");
        if (editPasswordBtn) {
            editPasswordBtn.addEventListener("click", () => {
                this.isEditingPassword = !this.isEditingPassword;
                this.render();
            });
        }

        // 取消修改密碼
        const cancelPasswordBtn = document.getElementById("cancelPasswordBtn");
        if (cancelPasswordBtn) {
            cancelPasswordBtn.addEventListener("click", () => {
                this.isEditingPassword = false;
                this.render();
            });
        }

        // 提交修改密碼表單
        const changePasswordForm =
            document.getElementById("changePasswordForm");
        if (changePasswordForm) {
            changePasswordForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                await this.handleChangePassword(
                    new FormData(changePasswordForm)
                );
            });
        }
    }

    async handleUpdateInfo(formData) {
        try {
            const data = {
                username: formData.get("username"),
                email: formData.get("email"),
            };

            // 驗證
            if (!data.username || data.username.length < 3) {
                toast.error("使用者名稱至少需要 3 個字元");
                return;
            }

            if (!data.email || !this.isValidEmail(data.email)) {
                toast.error("請輸入有效的電子郵件");
                return;
            }

            // 更新使用者資訊
            await usersAPI.update(this.user.id, data);

            toast.success("個人資訊更新成功");
            this.isEditingInfo = false;

            // 重新載入使用者資訊
            await this.loadUserInfo();
        } catch (error) {
            console.error("更新個人資訊失敗:", error);
            toast.error(error.message || "更新個人資訊失敗");
        }
    }

    async handleChangePassword(formData) {
        try {
            const currentPassword = formData.get("current_password");
            const newPassword = formData.get("new_password");
            const newPasswordConfirmation = formData.get(
                "new_password_confirmation"
            );

            // 驗證
            if (!currentPassword) {
                toast.error("請輸入目前密碼");
                return;
            }

            if (!newPassword || newPassword.length < 8) {
                toast.error("新密碼長度至少需要 8 個字元");
                return;
            }

            if (newPassword !== newPasswordConfirmation) {
                toast.error("新密碼與確認密碼不符");
                return;
            }

            if (currentPassword === newPassword) {
                toast.error("新密碼不能與目前密碼相同");
                return;
            }

            // 修改密碼
            await usersAPI.update(this.user.id, {
                current_password: currentPassword,
                password: newPassword,
                password_confirmation: newPasswordConfirmation,
            });

            toast.success("密碼修改成功");
            this.isEditingPassword = false;
            this.render();
        } catch (error) {
            console.error("修改密碼失敗:", error);
            toast.error(error.message || "修改密碼失敗");
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

    formatDate(dateString) {
        if (!dateString) return "-";
        const date = new Date(dateString);
        return new Intl.DateTimeFormat("zh-TW", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
        }).format(date);
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
}

/**
 * 渲染個人資料頁面（wrapper 函數）
 */
export async function renderProfile() {
    const page = new ProfilePage();
    await page.init();
}
