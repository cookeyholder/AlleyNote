/**
 * 角色管理頁面
 */

import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { toast } from "../../utils/toast.js";
import { rolesAPI } from "../../api/modules/roles.js";

/**
 * 角色管理頁面類別
 */
export default class RolesPage {
  constructor() {
    this.roles = [];
    this.permissions = [];
    this.groupedPermissions = {};
    this.selectedRole = null;
    this.loading = false;
  }

  async init() {
    await this.loadRolesAndPermissions();
  }

  /**
   * 載入角色和權限資料
   */
  async loadRolesAndPermissions() {
    try {
      this.loading = true;
      this.render();

      // 載入角色列表和權限列表
      const [rolesResponse, permissionsResponse] = await Promise.all([
        rolesAPI.getAll(),
        rolesAPI.getGroupedPermissions(),
      ]);

      this.roles = rolesResponse.data || [];
      this.groupedPermissions = permissionsResponse.data || {};

      this.loading = false;
      this.render();
    } catch (error) {
      console.error("載入資料失敗:", error);
      toast.error("載入資料失敗");
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto pb-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h1 class="text-3xl font-bold text-modern-900">角色與權限</h1>
            <p class="text-sm text-modern-500 mt-1">定義系統職能角色並分配精細的操作權限範圍</p>
          </div>
          <button
            id="addRoleBtn"
            class="flex items-center justify-center gap-2 px-6 py-3 bg-accent-600 text-white rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all font-bold"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            新增系統角色
          </button>
        </div>

        ${this.loading ? this.renderLoading() : this.renderContent()}
      </div>

      <!-- Modal 容器 -->
      <div id="modal-container"></div>
    `;

    const app = document.getElementById("app");
    renderDashboardLayout(content, { title: "角色管理" });
    bindDashboardLayoutEvents();
    this.attachEventListeners();
  }

  renderLoading() {
    return `
      <div class="flex items-center justify-center py-24">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto mb-4"></div>
          <p class="text-modern-500 font-medium">載入角色權限中...</p>
        </div>
      </div>
    `;
  }

  renderContent() {
    return `
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- 角色列表 -->
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center gap-2 px-2 mb-2">
            <div class="w-1 h-4 bg-accent-600 rounded-full"></div>
            <h2 class="text-sm font-bold text-modern-700 uppercase tracking-widest">角色清單</h2>
          </div>
          <div id="rolesListContainer" class="space-y-3">
            ${this.renderRolesList()}
          </div>
        </div>

        <!-- 權限管理 -->
        <div class="lg:col-span-3">
          <div class="flex items-center gap-2 px-2 mb-4">
            <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
            <h2 class="text-sm font-bold text-modern-700 uppercase tracking-widest">權限配置</h2>
          </div>
          <div id="permissionsContainer" class="card bg-white border-modern-200 shadow-sm p-8 min-h-[400px]">
            ${
              this.selectedRole
                ? this.renderPermissionsEditor()
                : `
              <div class="flex flex-col items-center justify-center h-full py-12 text-center">
                <div class="w-16 h-16 bg-modern-50 rounded-2xl flex items-center justify-center text-modern-300 mb-4">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                </div>
                <p class="text-modern-500 font-bold">請先從左側選擇一個角色</p>
                <p class="text-xs text-modern-400 mt-1">選擇後即可在此處調整該角色的詳細操作權限</p>
              </div>
            `
            }
          </div>
        </div>
      </div>
    `;
  }

  renderRolesList() {
    if (this.roles.length === 0) {
      return `
        <div class="card bg-modern-50 border-dashed border-modern-300 p-8 text-center">
          <p class="text-modern-500 font-medium">目前尚無定義任何角色</p>
        </div>
      `;
    }

    return this.roles
      .map((role) => {
        const isSelected =
          this.selectedRole && this.selectedRole.role.id === role.id;
        const isSystemRole = ["超級管理員", "super_admin"].includes(role.name);

        return `
        <div class="card group cursor-pointer transition-all duration-200 ${
          isSelected
            ? "bg-accent-600 border-accent-600 shadow-lg shadow-accent-600/20"
            : "bg-white border-modern-200 hover:border-accent-400 hover:shadow-md"
        } role-item" data-role-id="${role.id}">
          <div class="flex items-center justify-between p-5">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center ${
                isSelected
                  ? "bg-white/20 text-white"
                  : "bg-modern-50 text-modern-500 group-hover:text-accent-600"
              }">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <div>
                <h3 class="font-bold ${isSelected ? "text-white" : "text-modern-900"}">${this.escapeHtml(role.display_name || role.name)}</h3>
                <p class="text-xs ${isSelected ? "text-accent-100" : "text-modern-500"}">${this.escapeHtml(role.description || "系統預設角色")}</p>
              </div>
            </div>
            ${
              !isSystemRole
                ? `
              <button
                class="delete-role-btn p-2 rounded-lg transition-all ${
                  isSelected
                    ? "text-white/60 hover:text-white hover:bg-white/10"
                    : "text-modern-400 hover:text-red-600 hover:bg-red-50"
                }"
                data-role-id="${role.id}"
                data-role-name="${this.escapeHtml(role.display_name || role.name)}"
                title="刪除角色"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            `
                : `
              <span class="px-2 py-1 bg-white/10 text-white/80 text-[10px] font-bold rounded uppercase tracking-tighter">System</span>
            `
            }
          </div>
        </div>
      `;
      })
      .join("");
  }

  renderPermissionsEditor() {
    if (!this.selectedRole) return "";

    const { role, permission_ids } = this.selectedRole;

    let permissionsHTML = "";

    for (const [resource, perms] of Object.entries(this.groupedPermissions)) {
      permissionsHTML += `
        <div class="mb-8">
          <div class="flex items-center gap-2 mb-4">
            <h3 class="font-bold text-modern-800">${resource}</h3>
            <div class="h-px flex-1 bg-modern-100"></div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            ${perms
              .map(
                (perm) => `
              <label class="flex items-center gap-3 p-3 bg-modern-50 border border-modern-100 rounded-xl hover:bg-white hover:border-accent-300 hover:shadow-sm cursor-pointer transition-all">
                <input
                  type="checkbox"
                  value="${perm.id}"
                  ${permission_ids && permission_ids.includes(perm.id) ? "checked" : ""}
                  class="permission-checkbox w-5 h-5 rounded-lg border-modern-300 text-accent-600 focus:ring-accent-500 transition-all"
                />
                <div>
                  <span class="block text-sm font-bold text-modern-800">${this.escapeHtml(perm.display_name || perm.name)}</span>
                  ${perm.description ? `<span class="block text-[10px] text-modern-500 font-medium uppercase tracking-tight mt-0.5">${this.escapeHtml(perm.description)}</span>` : ""}
                </div>
              </label>
            `,
              )
              .join("")}
          </div>
        </div>
      `;
    }

    return `
      <div class="animate-fade-in">
        <div class="mb-8 pb-6 border-b border-modern-100 flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="px-2 py-0.5 bg-accent-600 text-white text-[10px] font-bold rounded uppercase">Editing</span>
              <h3 class="text-2xl font-bold text-modern-900">${this.escapeHtml(role.display_name || role.name)}</h3>
            </div>
            <p class="text-sm text-modern-500">${this.escapeHtml(role.description || "目前正在設定此角色的操作權限")}</p>
          </div>
          <div class="flex gap-2">
            <button
              id="cancelEditBtn"
              class="px-4 py-2 text-sm font-bold text-modern-500 hover:text-modern-800 transition-colors"
            >
              取消
            </button>
            <button
              id="savePermissionsBtn"
              class="px-6 py-2 bg-accent-600 text-white text-sm font-bold rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all flex items-center gap-2"
              data-role-id="${role.id}"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              儲存權限設定
            </button>
          </div>
        </div>

        <div class="max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
          ${permissionsHTML}
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    // 新增角色按鈕
    const addRoleBtn = document.getElementById("addRoleBtn");
    if (addRoleBtn) {
      addRoleBtn.addEventListener("click", () => this.showRoleModal());
    }

    // 選擇角色
    const roleItems = document.querySelectorAll(".role-item");
    roleItems.forEach((item) => {
      item.addEventListener("click", async (e) => {
        // 如果點擊的是刪除按鈕，不要選擇角色
        if (e.target.closest(".delete-role-btn")) return;

        const roleId = parseInt(item.dataset.roleId);
        await this.selectRole(roleId);
      });
    });

    // 刪除角色按鈕
    const deleteRoleBtns = document.querySelectorAll(".delete-role-btn");
    deleteRoleBtns.forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        e.stopPropagation();
        const roleId = parseInt(btn.dataset.roleId);
        const roleName = btn.dataset.roleName;
        await this.deleteRole(roleId, roleName);
      });
    });

    // 取消編輯按鈕
    const cancelEditBtn = document.getElementById("cancelEditBtn");
    if (cancelEditBtn) {
      cancelEditBtn.addEventListener("click", () => {
        this.selectedRole = null;
        this.render();
      });
    }

    // 儲存權限按鈕
    const savePermissionsBtn = document.getElementById("savePermissionsBtn");
    if (savePermissionsBtn) {
      savePermissionsBtn.addEventListener("click", async () => {
        const roleId = parseInt(savePermissionsBtn.dataset.roleId);
        await this.savePermissions(roleId);
      });
    }
  }

  async selectRole(roleId) {
    try {
      const result = await rolesAPI.get(roleId);
      this.selectedRole = result.data;
      this.render();
    } catch (error) {
      console.error("載入角色權限失敗:", error);
      toast.error("載入角色權限失敗");
    }
  }

  async savePermissions(roleId) {
    const checkboxes = document.querySelectorAll(
      ".permission-checkbox:checked",
    );
    const permissionIds = Array.from(checkboxes).map((cb) =>
      parseInt(cb.value),
    );

    try {
      await rolesAPI.updatePermissions(roleId, permissionIds);
      toast.success("權限已更新");
    } catch (error) {
      console.error("更新權限失敗:", error);
      toast.error("更新權限失敗");
    }
  }

  showRoleModal(role = null) {
    const isEdit = !!role;
    const modalTitle = isEdit ? "編輯角色" : "新增角色";

    const modalContent = `
      <form id="roleForm" class="space-y-6">
        <div>
          <label for="role_name" class="block text-sm font-bold text-modern-700 mb-2">
            角色代碼 (System Key) *
          </label>
          <input
            type="text"
            id="role_name"
            name="name"
            value="${role ? this.escapeHtml(role.name) : ""}"
            placeholder="例如：editor"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all ${isEdit ? "opacity-60 cursor-not-allowed" : ""}"
            required
            ${isEdit ? "readonly" : ""}
          />
          ${isEdit ? '<p class="mt-2 text-[10px] font-bold text-amber-600 uppercase tracking-widest">系統代碼在建立後無法修改</p>' : '<p class="mt-2 text-xs text-modern-400">僅限英文字母與底線，用於系統內部識別</p>'}
        </div>

        <div>
          <label for="role_display_name" class="block text-sm font-bold text-modern-700 mb-2">
            角色顯示名稱 *
          </label>
          <input
            type="text"
            id="role_display_name"
            name="display_name"
            value="${role ? this.escapeHtml(role.display_name || "") : ""}"
            placeholder="例如：內容編輯專員"
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
            required
          />
        </div>

        <div>
          <label for="role_description" class="block text-sm font-bold text-modern-700 mb-2">
            角色功能描述
          </label>
          <textarea
            id="role_description"
            name="description"
            rows="3"
            placeholder="描述此角色的職責與權限範圍..."
            class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500 transition-all"
          >${role ? this.escapeHtml(role.description || "") : ""}</textarea>
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
            ${isEdit ? "儲存角色變更" : "建立角色"}
          </button>
        </div>
      </form>
    `;

    // 創建 Modal
    this.currentModal = this.createModal(modalTitle, modalContent);

    // 綁定表單事件
    const roleForm = document.getElementById("roleForm");
    if (roleForm) {
      roleForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (isEdit) {
          await this.handleUpdateRole(role.id, new FormData(roleForm));
        } else {
          await this.handleCreateRole(new FormData(roleForm));
        }
        this.closeModal();
      });
    }

    // 取消按鈕
    const cancelBtn = document.getElementById("cancelModalBtn");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => this.closeModal());
    }
  }

  /**
   * 創建 Modal
   */
  createModal(title, content) {
    const modal = document.createElement("div");
    modal.className =
      "fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in";
    modal.innerHTML = `
      <div class="absolute inset-0 bg-black bg-opacity-50" data-modal-backdrop></div>
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-modern-200">
          <h3 class="text-xl font-semibold text-modern-900">${title}</h3>
          <button type="button" class="text-modern-400 hover:text-modern-600 transition-colors" data-modal-close>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          ${content}
        </div>
      </div>
    `;

    // 綁定關閉事件
    const closeButton = modal.querySelector("[data-modal-close]");
    if (closeButton) {
      closeButton.addEventListener("click", () => this.closeModal());
    }

    const backdrop = modal.querySelector("[data-modal-backdrop]");
    if (backdrop) {
      backdrop.addEventListener("click", () => this.closeModal());
    }

    document.body.appendChild(modal);
    return modal;
  }

  /**
   * 關閉 Modal
   */
  closeModal() {
    if (this.currentModal) {
      this.currentModal.remove();
      this.currentModal = null;
    }
  }

  async handleCreateRole(formData) {
    try {
      const data = {
        name: formData.get("name"),
        display_name: formData.get("display_name"),
        description: formData.get("description"),
      };

      await rolesAPI.create(data);
      toast.success("角色建立成功");
      await this.loadRolesAndPermissions();
    } catch (error) {
      console.error("建立角色失敗:", error);
      toast.error(error.message || "建立角色失敗");
    }
  }

  async handleUpdateRole(roleId, formData) {
    try {
      const data = {
        name: formData.get("name"),
        display_name: formData.get("display_name"),
        description: formData.get("description"),
      };

      await rolesAPI.update(roleId, data);
      toast.success("角色更新成功");
      await this.loadRolesAndPermissions();
    } catch (error) {
      console.error("更新角色失敗:", error);
      toast.error(error.message || "更新角色失敗");
    }
  }

  async deleteRole(id, name) {
    if (!confirm(`確定要刪除角色「${name}」嗎？此操作無法復原。`)) {
      return;
    }

    try {
      await rolesAPI.delete(id);
      toast.success("角色已刪除");
      await this.loadRolesAndPermissions();
      this.selectedRole = null;
    } catch (error) {
      console.error("刪除角色失敗:", error);
      toast.error(error.response?.data?.message || "刪除角色失敗");
    }
  }

  /**
   * HTML 轉義
   */
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
 * 渲染角色管理頁面（wrapper 函數）
 */
export async function renderRoles() {
  const page = new RolesPage();
  await page.init();
}
