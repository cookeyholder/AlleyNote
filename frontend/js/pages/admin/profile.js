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
      <div class="max-w-4xl mx-auto pb-12">
        <div class="mb-8 px-4 sm:px-0">
          <h1 class="text-3xl font-bold text-modern-900">個人帳戶設定</h1>
          <p class="text-sm text-modern-500 mt-1">維護您的個人基本資訊、電子郵件與登入密碼安全</p>
        </div>

        <!-- 個人資訊卡片 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8 mb-8 relative overflow-hidden">
          <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-32 h-32 text-modern-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          
          <div class="flex items-center justify-between mb-8 relative z-10">
            <div class="flex items-center gap-4">
              <div class="p-2.5 bg-accent-50 text-accent-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
              </div>
              <h2 class="text-xl font-bold text-modern-900">基本通訊資訊</h2>
            </div>
            <button 
              id="editInfoBtn" 
              class="px-5 py-2 text-sm font-bold \${this.isEditingInfo ? 'text-modern-500 hover:text-modern-800' : 'bg-modern-50 text-modern-700 hover:bg-modern-100 rounded-xl transition-all'}"
            >
              \${this.isEditingInfo ? '取消編輯' : '編輯資料'}
            </button>
          </div>

          <div class="relative z-10">
            \${this.isEditingInfo ? this.renderEditInfoForm() : this.renderInfoDisplay()}
          </div>
        </div>

        <!-- 修改密碼卡片 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
          <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </div>
              <h2 class="text-xl font-bold text-modern-900">登入安全與密碼</h2>
            </div>
            <button 
              id="editPasswordBtn" 
              class="px-5 py-2 text-sm font-bold \${this.isEditingPassword ? 'text-modern-500 hover:text-modern-800' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-xl transition-all'}"
            >
              \${this.isEditingPassword ? '取消修改' : '變更密碼'}
            </button>
          </div>

          \${this.isEditingPassword ? this.renderPasswordForm() : this.renderPasswordPlaceholder()}
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
      return '<div class="text-center text-modern-500 py-12">載入使用者資訊中...</div>';
    }

    return `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-6">
          <div>
            <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">使用者名稱</p>
            <p class="text-lg font-bold text-modern-900">${this.escapeHtml(this.user.username || "-")}</p>
          </div>
          <div>
            <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">電子郵件地址</p>
            <p class="text-lg font-bold text-modern-900">${this.escapeHtml(this.user.email || "-")}</p>
          </div>
          <div>
            <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">帳戶權限角色</p>
            <span class="inline-flex items-center px-3 py-1 mt-1 rounded-full text-xs font-bold border ${
              this.user.role === "super_admin"
                ? "bg-purple-50 text-purple-700 border-purple-100"
                : "bg-blue-50 text-blue-700 border-blue-100"
            }">
              ${this.user.role === "super_admin" ? "系統主管理員" : "一般管理員"}
            </span>
          </div>
        </div>
        <div class="space-y-6 md:border-l md:border-modern-100 md:pl-8">
          <div>
            <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">註冊時間</p>
            <p class="text-lg font-bold text-modern-900 tabular-nums">${this.formatDate(this.user.created_at)}</p>
          </div>
          <div>
            <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">最後登入活動</p>
            <p class="text-lg font-bold text-modern-900 tabular-nums">${this.formatDate(this.user.last_login)}</p>
          </div>
        </div>
      </div>
    `;
  }

  renderEditInfoForm() {
    if (!this.user) return "";

    return `
      <form id="editInfoForm" class="space-y-6 animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="username" class="block text-sm font-bold text-modern-700 mb-2">
              使用者名稱
            </label>
            <input
              type="text"
              id="username"
              name="username"
              value="${this.escapeHtml(this.user.username || "")}"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
              required
            />
          </div>

          <div>
            <label for="email" class="block text-sm font-bold text-modern-700 mb-2">
              電子郵件
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value="${this.escapeHtml(this.user.email || "")}"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
              required
            />
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button
            type="submit"
            class="px-10 py-3 bg-accent-600 text-white font-bold rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all"
          >
            儲存變更內容
          </button>
        </div>
      </form>
    `;
  }

  renderPasswordPlaceholder() {
    return `
      <div class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-16 h-16 bg-modern-50 rounded-2xl flex items-center justify-center text-modern-300 mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <p class="text-modern-500 font-bold">點擊「變更密碼」按鈕開始修改</p>
        <p class="text-xs text-modern-400 mt-1">為了您的帳戶安全，建議定期更換強度足夠的密碼</p>
      </div>
    `;
  }

  renderPasswordForm() {
    return `
      <form id="changePasswordForm" class="space-y-6 animate-fade-in">
        <div>
          <label for="current_password" class="block text-sm font-bold text-modern-700 mb-2">
            目前舊密碼
          </label>
          <input
            type="password"
            id="current_password"
            name="current_password"
            placeholder="請輸入現有的登入密碼"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
            required
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="new_password" class="block text-sm font-bold text-modern-700 mb-2">
              設定新密碼
            </label>
            <input
              type="password"
              id="new_password"
              name="new_password"
              placeholder="至少 8 位字元"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
              minlength="8"
              required
            />
          </div>

          <div>
            <label for="new_password_confirmation" class="block text-sm font-bold text-modern-700 mb-2">
              再次確認新密碼
            </label>
            <input
              type="password"
              id="new_password_confirmation"
              name="new_password_confirmation"
              placeholder="請重複輸入新密碼"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
              minlength="8"
              required
            />
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button
            type="submit"
            class="px-10 py-3 bg-amber-600 text-white font-bold rounded-xl hover:bg-amber-700 shadow-lg shadow-amber-600/20 transition-all"
          >
            更新登入密碼
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
    const changePasswordForm = document.getElementById("changePasswordForm");
    if (changePasswordForm) {
      changePasswordForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        await this.handleChangePassword(new FormData(changePasswordForm));
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
      const newPasswordConfirmation = formData.get("new_password_confirmation");

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
