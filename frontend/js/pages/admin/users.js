import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { usersAPI } from "../../api/modules/users.js";
import { notification } from "../../utils/notification.js";
import { Modal, modal } from "../../components/Modal.js";
import { PasswordStrengthIndicator } from "../../components/PasswordStrengthIndicator.js";
import { PasswordGenerator } from "../../utils/passwordGenerator.js";

/**
 * 使用者管理頁面
 */
export default class UsersPage {
  constructor() {
    this.users = [];
    this.roles = [];
    this.loading = false;
    this.currentPage = 1;
    this.totalPages = 1;
    this.editingUser = null;
    this.modal = null;
    this.passwordIndicator = null;
  }

  async init() {
    await Promise.all([this.loadUsers(), this.loadRoles()]);
  }

  async loadRoles() {
    try {
      const response = await usersAPI.getRoles();
      if (response.success && response.data) {
        this.roles = response.data;
      } else {
        // 使用預設角色
        this.roles = [
          { id: 1, name: "admin", display_name: "管理員" },
          { id: 2, name: "editor", display_name: "編輯者" },
          { id: 3, name: "viewer", display_name: "訪客" },
        ];
      }
    } catch (error) {
      console.error("載入角色列表失敗:", error);
      // 使用預設角色
      this.roles = [
        { id: 1, name: "admin", display_name: "管理員" },
        { id: 2, name: "editor", display_name: "編輯者" },
        { id: 3, name: "viewer", display_name: "訪客" },
      ];
    }
  }

  async loadUsers(page = 1) {
    try {
      this.loading = true;
      this.currentPage = page;
      this.render();

      const response = await usersAPI.getAll({
        page,
        per_page: 20,
      });

      if (response.success && response.data) {
        this.users = Array.isArray(response.data)
          ? response.data
          : response.data.items || [];
        this.totalPages =
          response.data.last_page || response.data.total_pages || 1;
      } else {
        this.users = [];
        this.totalPages = 1;
      }

      this.loading = false;
      this.render();
    } catch (error) {
      console.error("載入使用者列表失敗:", error);
      notification.error(
        "載入使用者列表失敗：" + (error.message || "未知錯誤"),
      );
      this.users = [];
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto pb-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h1 class="text-3xl font-bold text-modern-900">使用者管理</h1>
            <p class="text-sm text-modern-500 mt-1">管理系統存取權限與維護使用者帳號資訊</p>
          </div>
          <button
            id="addUserBtn"
            class="flex items-center justify-center gap-2 px-6 py-3 bg-accent-600 text-white rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all font-bold"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            新增使用者
          </button>
        </div>

        ${this.renderUsersList()}
      </div>

      <!-- Modal 容器 -->
      <div id="modal-container"></div>
    `;

    const app = document.getElementById("app");
    renderDashboardLayout(content, { title: "使用者管理" });
    bindDashboardLayoutEvents();
    this.attachEventListeners();
  }

  renderUsersList() {
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

    if (this.users.length === 0) {
      return `
        <div class="bg-white rounded-2xl border border-modern-200 p-8">
          <div class="text-center text-modern-500">
            <i class="fas fa-users text-4xl mb-4"></i>
            <p>尚無使用者資料</p>
          </div>
        </div>
      `;
    }

    return `
      <div class="bg-white rounded-2xl border border-modern-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-modern-50 border-b border-modern-200">
              <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-modern-700">使用者名稱</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-modern-700">電子郵件</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-modern-700">角色</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-modern-700">註冊日期</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-modern-700">上次登入</th>
                <th class="px-6 py-4 text-right text-sm font-semibold text-modern-700">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-modern-100">
              ${this.users.map((user) => this.renderUserRow(user)).join("")}
            </tbody>
          </table>
        </div>

        ${this.renderPagination()}
      </div>
    `;
  }

  renderUserRow(user) {
    // 取得使用者角色
    const userRoles = user.roles || [];
    const primaryRole = userRoles.length > 0 ? userRoles[0] : null;
    const roleName = primaryRole?.display_name || primaryRole?.name || "無角色";
    const isSuperAdmin =
      roleName.includes("超級管理員") || primaryRole?.name === "super_admin";

    return `
      <tr class="group hover:bg-modern-50/80 transition-all duration-150">
        <td class="px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-accent-50 border border-accent-100 flex items-center justify-center text-accent-600 font-bold shadow-sm">
              ${(user.username || "U")[0].toUpperCase()}
            </div>
            <div>
              <div class="text-sm font-bold text-modern-900 group-hover:text-accent-700 transition-colors">${this.escapeHtml(user.username)}</div>
              <div class="text-[10px] text-modern-400 font-bold uppercase tracking-tighter">User ID: #${user.id}</div>
            </div>
          </div>
        </td>
        <td class="px-6 py-4 text-sm font-medium text-modern-600">${this.escapeHtml(user.email)}</td>
        <td class="px-6 py-4">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${
            isSuperAdmin
              ? "bg-purple-50 text-purple-700 border-purple-100"
              : "bg-blue-50 text-blue-700 border-blue-100"
          }">
            ${this.escapeHtml(roleName)}
          </span>
        </td>
        <td class="px-6 py-4 text-sm font-medium text-modern-500 tabular-nums">${this.formatDate(user.created_at)}</td>
        <td class="px-6 py-4 text-sm font-medium text-modern-500 tabular-nums">${this.formatDate(user.last_login)}</td>
        <td class="px-6 py-4 text-right">
          <div class="flex items-center justify-end gap-1">
            <button
              class="edit-user-btn p-2 text-modern-400 hover:text-accent-600 hover:bg-accent-50 rounded-xl transition-all"
              title="編輯資料"
              data-user-id="${user.id}"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button
              class="delete-user-btn p-2 text-modern-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
              title="移除使用者"
              data-user-id="${user.id}"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  renderPagination() {
    if (this.totalPages <= 1) return "";

    return `
      <div class="flex items-center justify-between px-6 py-6 bg-modern-50/30 border-t border-modern-100">
        <div class="text-sm font-bold text-modern-500 uppercase tracking-widest">
          第 ${this.currentPage} / ${this.totalPages} 頁
        </div>
        <div class="flex gap-2">
          <button
            id="prevPageBtn"
            class="px-4 py-2 text-sm font-bold text-modern-600 bg-white border border-modern-200 rounded-xl hover:bg-modern-50 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
            ${this.currentPage <= 1 ? "disabled" : ""}
          >
            上一頁
          </button>
          <button
            id="nextPageBtn"
            class="px-4 py-2 text-sm font-bold text-modern-600 bg-white border border-modern-200 rounded-xl hover:bg-modern-50 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
            ${this.currentPage >= this.totalPages ? "disabled" : ""}
          >
            下一頁
          </button>
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    // 新增使用者按鈕
    const addUserBtn = document.getElementById("addUserBtn");
    if (addUserBtn) {
      addUserBtn.addEventListener("click", () => this.showUserModal());
    }

    // 編輯使用者按鈕
    const editBtns = document.querySelectorAll(".edit-user-btn");
    editBtns.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const userId = parseInt(btn.dataset.userId);
        const user = this.users.find((u) => u.id === userId);
        if (user) {
          this.showUserModal(user);
        }
      });
    });

    // 刪除使用者按鈕
    const deleteBtns = document.querySelectorAll(".delete-user-btn");
    deleteBtns.forEach((btn) => {
      btn.addEventListener("click", async () => {
        const userId = parseInt(btn.dataset.userId);
        await this.handleDeleteUser(userId);
      });
    });

    // 分頁按鈕
    const prevPageBtn = document.getElementById("prevPageBtn");
    if (prevPageBtn) {
      prevPageBtn.addEventListener("click", () => {
        if (this.currentPage > 1) {
          this.loadUsers(this.currentPage - 1);
        }
      });
    }

    const nextPageBtn = document.getElementById("nextPageBtn");
    if (nextPageBtn) {
      nextPageBtn.addEventListener("click", () => {
        if (this.currentPage < this.totalPages) {
          this.loadUsers(this.currentPage + 1);
        }
      });
    }
  }

  showUserModal(user = null) {
    const isEdit = !!user;
    const modalTitle = isEdit ? "編輯使用者" : "新增使用者";

    // 取得使用者當前的角色 ID
    const currentRoleIds = user?.roles?.map((r) => r.id) || [];
    const primaryRoleId =
      currentRoleIds.length > 0 ? currentRoleIds[0] : this.roles[0]?.id || "";

    const modalContent = `
      <form id="userForm" class="space-y-6">
        <div>
          <label for="username" class="block text-sm font-medium text-modern-700 mb-2">
            使用者名稱 *
          </label>
          <input
            type="text"
            id="username"
            name="username"
            value="${user ? this.escapeHtml(user.username) : ""}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
            minlength="3"
            maxlength="50"
          />
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-modern-700 mb-2">
            電子郵件 *
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value="${user ? this.escapeHtml(user.email) : ""}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="role_id" class="block text-sm font-medium text-modern-700 mb-2">
            角色 *
          </label>
          <select
            id="role_id"
            name="role_id"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          >
            ${
              this.roles.length > 0
                ? this.roles
                    .map(
                      (role) => `
                <option value="${role.id}" ${role.id === primaryRoleId ? "selected" : ""}>
                  ${this.escapeHtml(role.display_name || role.name || "未知角色")}
                </option>
              `,
                    )
                    .join("")
                : '<option value="">載入角色中...</option>'
            }
          </select>
        </div>

        ${
          !isEdit
            ? `
          <div class="p-4 bg-modern-50 rounded-2xl border border-modern-100">
            <div class="flex justify-between items-center mb-4">
              <label for="password" class="block text-sm font-bold text-modern-700">
                設定登入密碼 *
              </label>
              <button
                type="button"
                id="generatePasswordBtn"
                class="flex items-center gap-1 text-xs text-accent-600 hover:text-accent-700 font-bold bg-white px-3 py-1.5 rounded-lg shadow-sm border border-accent-100 transition-all"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                自動生成密碼
              </button>
            </div>
            <div class="relative">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="至少 8 位字元"
                class="w-full px-4 py-3 bg-white border border-modern-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
                required
                minlength="8"
              />
              <button
                type="button"
                id="togglePasswordBtn"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-modern-400 hover:text-accent-600 transition-colors p-1"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
            <!-- 密碼強度指示器將自動插入此處 -->
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-bold text-modern-700 mb-2">
              確認新密碼 *
            </label>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              placeholder="請再次輸入密碼以供確認"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
              required
              minlength="8"
            />
          </div>
        `
            : ""
        }

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
            ${isEdit ? "儲存變更內容" : "確認建立帳號"}
          </button>
        </div>
      </form>
    `;

    this.modal = new Modal({
      title: modalTitle,
      content: modalContent,
      size: "lg",
      showFooter: false, // 表單內已經有自己的按鈕
    });
    this.modal.show();

    // 初始化密碼強度指示器（僅在新增模式）
    if (!isEdit) {
      const passwordInput = document.getElementById("password");
      const usernameInput = document.getElementById("username");
      const emailInput = document.getElementById("email");

      if (passwordInput) {
        // 清理舊的指示器
        if (this.passwordIndicator) {
          this.passwordIndicator.destroy();
        }

        // 建立新的指示器
        this.passwordIndicator = new PasswordStrengthIndicator(passwordInput, {
          username: usernameInput?.value,
          email: emailInput?.value,
          showRequirements: true,
          showSuggestions: true,
        });

        // 當使用者名稱或 email 變更時更新指示器
        usernameInput?.addEventListener("input", () => {
          this.passwordIndicator.updateOptions({
            username: usernameInput.value,
          });
        });

        emailInput?.addEventListener("input", () => {
          this.passwordIndicator.updateOptions({
            email: emailInput.value,
          });
        });
      }

      // 密碼顯示/隱藏切換
      const togglePasswordBtn = document.getElementById("togglePasswordBtn");
      if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", () => {
          const type = passwordInput.type === "password" ? "text" : "password";
          passwordInput.type = type;

          // 更新圖標
          const svg = togglePasswordBtn.querySelector("svg");
          if (type === "password") {
            svg.innerHTML = `
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            `;
          } else {
            svg.innerHTML = `
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            `;
          }
        });
      }

      // 密碼生成按鈕
      const generatePasswordBtn = document.getElementById(
        "generatePasswordBtn",
      );
      if (generatePasswordBtn && passwordInput) {
        generatePasswordBtn.addEventListener("click", () => {
          try {
            const generatedPassword = PasswordGenerator.generate({
              length: 12,
              lowercase: true,
              uppercase: true,
              numbers: true,
              special: true,
              username: usernameInput?.value,
              email: emailInput?.value,
            });

            passwordInput.value = generatedPassword;
            passwordInput.type = "text";
            togglePasswordBtn.textContent = "🙈";

            // 觸發 input 事件以更新強度指示器
            passwordInput.dispatchEvent(new Event("input"));

            // 同步到確認密碼
            const confirmPassword = document.getElementById(
              "password_confirmation",
            );
            if (confirmPassword) {
              confirmPassword.value = generatedPassword;
            }

            notification.success("已生成安全密碼！");
          } catch (error) {
            notification.error("生成密碼失敗：" + error.message);
          }
        });
      }
    }

    // 綁定表單事件
    const userForm = document.getElementById("userForm");
    if (userForm) {
      userForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (isEdit) {
          await this.handleUpdateUser(user.id, new FormData(userForm));
        } else {
          await this.handleCreateUser(new FormData(userForm));
        }
      });
    }

    // 取消按鈕
    const cancelBtn = document.getElementById("cancelModalBtn");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => {
        // 清理密碼指示器
        if (this.passwordIndicator) {
          this.passwordIndicator.destroy();
          this.passwordIndicator = null;
        }
        this.modal.hide();
      });
    }
  }

  async handleCreateUser(formData) {
    try {
      const data = {
        username: formData.get("username"),
        email: formData.get("email"),
        role_ids: [parseInt(formData.get("role_id"))], // 使用角色 ID 陣列
        password: formData.get("password"),
        password_confirmation: formData.get("password_confirmation"),
      };

      // 驗證
      if (!data.username || data.username.length < 3) {
        notification.error("使用者名稱至少需要 3 個字元");
        return;
      }

      if (!data.email || !this.isValidEmail(data.email)) {
        notification.error("請輸入有效的電子郵件");
        return;
      }

      if (!data.password || data.password.length < 8) {
        notification.error("密碼長度至少需要 8 個字元");
        return;
      }

      if (data.password !== data.password_confirmation) {
        notification.error("密碼與確認密碼不符");
        return;
      }

      await usersAPI.create(data);
      notification.success("使用者建立成功");
      this.modal.hide();
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error("建立使用者失敗:", error);
      notification.error(error.message || "建立使用者失敗");
    }
  }

  async handleUpdateUser(userId, formData) {
    try {
      const data = {
        username: formData.get("username"),
        email: formData.get("email"),
        role_ids: [parseInt(formData.get("role_id"))], // 使用角色 ID 陣列
      };

      // 驗證
      if (!data.username || data.username.length < 3) {
        notification.error("使用者名稱至少需要 3 個字元");
        return;
      }

      if (!data.email || !this.isValidEmail(data.email)) {
        notification.error("請輸入有效的電子郵件");
        return;
      }

      await usersAPI.update(userId, data);
      notification.success("使用者更新成功");
      this.modal.hide();
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error("更新使用者失敗:", error);
      notification.error(error.message || "更新使用者失敗");
    }
  }

  async handleDeleteUser(userId) {
    const user = this.users.find((u) => u.id === userId);
    if (!user) return;

    const confirmed = await notification.confirmDelete(user.username);
    if (!confirmed) {
      return;
    }

    try {
      await usersAPI.delete(userId);
      notification.success("使用者刪除成功");
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error("刪除使用者失敗:", error);
      notification.error(error.message || "刪除使用者失敗");
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
 * 渲染使用者管理頁面（wrapper 函數）
 */
export async function renderUsers() {
  const page = new UsersPage();
  await page.init();
}
