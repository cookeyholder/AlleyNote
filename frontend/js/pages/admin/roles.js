/**
 * 角色管理頁面
 */

import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { rolesAPI, permissionsAPI } from '../../api/modules/users.js';
import { toast } from '../../utils/toast.js';
import { modal } from '../../components/Modal.js';

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

      const [rolesResult, permissionsResult] = await Promise.all([
        rolesAPI.list(),
        permissionsAPI.listGrouped()
      ]);

      // rolesResult 和 permissionsResult 已經是後端回應物件 {success, data}
      this.roles = rolesResult.data || [];
      this.groupedPermissions = permissionsResult.data || {};

      this.loading = false;
      this.render();
    } catch (error) {
      console.error('載入資料失敗:', error);
      toast.error('載入資料失敗');
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-3xl font-bold text-modern-900">角色管理</h1>
          <button 
            id="addRoleBtn" 
            class="px-6 py-3 bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition-colors font-medium"
          >
            <i class="fas fa-plus mr-2"></i>
            新增角色
          </button>
        </div>

        ${this.loading ? this.renderLoading() : this.renderContent()}
      </div>

      <!-- Modal 容器 -->
      <div id="modal-container"></div>
    `;

    const app = document.getElementById("app");
    app.innerHTML = renderDashboardLayout(content);
    bindDashboardLayoutEvents();
    this.attachEventListeners();
  }

  renderLoading() {
    return `
      <div class="bg-white rounded-2xl border border-modern-200 p-8">
        <div class="text-center text-modern-500">
          <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
          <p>載入中...</p>
        </div>
      </div>
    `;
  }

  renderContent() {
    return `
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 角色列表 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-6">
          <h2 class="text-xl font-bold mb-4">角色列表</h2>
          <div id="rolesListContainer" class="space-y-3">
            ${this.renderRolesList()}
          </div>
        </div>

        <!-- 權限管理 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-6">
          <h2 class="text-xl font-bold mb-4">權限設定</h2>
          <div id="permissionsContainer">
            ${this.selectedRole ? this.renderPermissionsEditor() : '<p class="text-modern-500">請選擇一個角色來管理權限</p>'}
          </div>
        </div>
      </div>
    `;
  }

  renderRolesList() {
    if (this.roles.length === 0) {
      return '<p class="text-modern-500">尚無角色資料</p>';
    }

    return this.roles.map(role => `
      <div class="border border-modern-200 rounded-lg p-4 hover:border-accent-500 cursor-pointer transition-colors role-item"
           data-role-id="${role.id}">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-bold text-lg">${this.escapeHtml(role.display_name || role.name)}</h3>
            <p class="text-sm text-modern-600">${this.escapeHtml(role.description || '')}</p>
          </div>
          ${!['超級管理員', 'super_admin'].includes(role.name) ? `
            <button 
              class="delete-role-btn text-red-600 hover:text-red-800 text-sm px-3 py-1"
              data-role-id="${role.id}"
              data-role-name="${this.escapeHtml(role.display_name || role.name)}"
            >
              刪除
            </button>
          ` : ''}
        </div>
      </div>
    `).join('');
  }

  renderPermissionsEditor() {
    if (!this.selectedRole) return '';

    const { role, permission_ids } = this.selectedRole;

    let permissionsHTML = '';

    for (const [resource, perms] of Object.entries(this.groupedPermissions)) {
      permissionsHTML += `
        <div class="mb-6">
          <h3 class="font-bold text-lg mb-3 text-modern-700">${resource}</h3>
          <div class="space-y-2">
            ${perms.map(perm => `
              <label class="flex items-center space-x-2">
                <input
                  type="checkbox"
                  value="${perm.id}"
                  ${permission_ids.includes(perm.id) ? 'checked' : ''}
                  class="permission-checkbox rounded border-gray-300 text-accent-600 focus:ring-accent-500"
                />
                <span class="text-sm">${this.escapeHtml(perm.display_name || perm.name)}</span>
                ${perm.description ? `<span class="text-xs text-gray-500">(${this.escapeHtml(perm.description)})</span>` : ''}
              </label>
            `).join('')}
          </div>
        </div>
      `;
    }

    return `
      <div>
        <div class="mb-4 pb-4 border-b">
          <h3 class="text-lg font-bold">${this.escapeHtml(role.display_name || role.name)}</h3>
          <p class="text-sm text-gray-600">${this.escapeHtml(role.description || '')}</p>
        </div>
        
        <div class="max-h-96 overflow-y-auto mb-4">
          ${permissionsHTML}
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            id="cancelEditBtn"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            取消
          </button>
          <button
            id="savePermissionsBtn"
            class="px-4 py-2 bg-accent-600 text-white rounded-lg hover:bg-accent-700"
            data-role-id="${role.id}"
          >
            儲存權限
          </button>
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    // 新增角色按鈕
    const addRoleBtn = document.getElementById('addRoleBtn');
    if (addRoleBtn) {
      addRoleBtn.addEventListener('click', () => this.showRoleModal());
    }

    // 選擇角色
    const roleItems = document.querySelectorAll('.role-item');
    roleItems.forEach(item => {
      item.addEventListener('click', async (e) => {
        // 如果點擊的是刪除按鈕，不要選擇角色
        if (e.target.closest('.delete-role-btn')) return;
        
        const roleId = parseInt(item.dataset.roleId);
        await this.selectRole(roleId);
      });
    });

    // 刪除角色按鈕
    const deleteRoleBtns = document.querySelectorAll('.delete-role-btn');
    deleteRoleBtns.forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const roleId = parseInt(btn.dataset.roleId);
        const roleName = btn.dataset.roleName;
        await this.deleteRole(roleId, roleName);
      });
    });

    // 取消編輯按鈕
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    if (cancelEditBtn) {
      cancelEditBtn.addEventListener('click', () => {
        this.selectedRole = null;
        this.render();
      });
    }

    // 儲存權限按鈕
    const savePermissionsBtn = document.getElementById('savePermissionsBtn');
    if (savePermissionsBtn) {
      savePermissionsBtn.addEventListener('click', async () => {
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
      console.error('載入角色權限失敗:', error);
      toast.error('載入角色權限失敗');
    }
  }

  async savePermissions(roleId) {
    const checkboxes = document.querySelectorAll('.permission-checkbox:checked');
    const permissionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    try {
      await rolesAPI.updatePermissions(roleId, permissionIds);
      toast.success('權限已更新');
    } catch (error) {
      console.error('更新權限失敗:', error);
      toast.error('更新權限失敗');
    }
  }

  showRoleModal(role = null) {
    const isEdit = !!role;
    const modalTitle = isEdit ? '編輯角色' : '新增角色';

    const modalContent = `
      <form id="roleForm" class="space-y-6">
        <div>
          <label for="role_name" class="block text-sm font-medium text-modern-700 mb-2">
            角色名稱 *
          </label>
          <input
            type="text"
            id="role_name"
            name="name"
            value="${role ? this.escapeHtml(role.name) : ''}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="role_display_name" class="block text-sm font-medium text-modern-700 mb-2">
            顯示名稱 *
          </label>
          <input
            type="text"
            id="role_display_name"
            name="display_name"
            value="${role ? this.escapeHtml(role.display_name || '') : ''}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="role_description" class="block text-sm font-medium text-modern-700 mb-2">
            描述
          </label>
          <textarea
            id="role_description"
            name="description"
            rows="3"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
          >${role ? this.escapeHtml(role.description || '') : ''}</textarea>
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
            ${isEdit ? '儲存變更' : '新增角色'}
          </button>
        </div>
      </form>
    `;

    const modal = new Modal({
      title: modalTitle,
      content: modalContent,
      size: 'lg',
      showCancel: false // 表單內已經有取消按鈕
    });
    modal.show();

    // 綁定表單事件
    const roleForm = document.getElementById('roleForm');
    if (roleForm) {
      roleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isEdit) {
          await this.handleUpdateRole(role.id, new FormData(roleForm));
        } else {
          await this.handleCreateRole(new FormData(roleForm));
        }
        modal.hide();
      });
    }

    // 取消按鈕
    const cancelBtn = document.getElementById('cancelModalBtn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => modal.hide());
    }
  }

  async handleCreateRole(formData) {
    try {
      const data = {
        name: formData.get('name'),
        display_name: formData.get('display_name'),
        description: formData.get('description'),
      };

      await rolesAPI.create(data);
      toast.success('角色建立成功');
      await this.loadRolesAndPermissions();
    } catch (error) {
      console.error('建立角色失敗:', error);
      toast.error(error.message || '建立角色失敗');
    }
  }

  async handleUpdateRole(roleId, formData) {
    try {
      const data = {
        name: formData.get('name'),
        display_name: formData.get('display_name'),
        description: formData.get('description'),
      };

      await rolesAPI.update(roleId, data);
      toast.success('角色更新成功');
      await this.loadRolesAndPermissions();
    } catch (error) {
      console.error('更新角色失敗:', error);
      toast.error(error.message || '更新角色失敗');
    }
  }

  async deleteRole(id, name) {
    if (!confirm(`確定要刪除角色「${name}」嗎？此操作無法復原。`)) {
      return;
    }

    try {
      await rolesAPI.delete(id);
      toast.success('角色已刪除');
      await this.loadRolesAndPermissions();
      this.selectedRole = null;
    } catch (error) {
      console.error('刪除角色失敗:', error);
      toast.error(error.response?.data?.message || '刪除角色失敗');
    }
  }

  /**
   * HTML 轉義
   */
  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };
    return text ? String(text).replace(/[&<>"']/g, (m) => map[m]) : '';
  }
}

