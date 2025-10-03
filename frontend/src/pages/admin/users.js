import DashboardLayout from '../../layouts/DashboardLayout.js';
import { usersAPI } from '../../api/modules/users.js';
import { toast } from '../../utils/toast.js';
import Modal from '../../components/Modal.js';

/**
 * 使用者管理頁面
 */
export default class UsersPage {
  constructor() {
    this.users = [];
    this.loading = false;
    this.currentPage = 1;
    this.totalPages = 1;
    this.editingUser = null;
    this.modal = null;
  }

  async init() {
    await this.loadUsers();
  }

  async loadUsers(page = 1) {
    try {
      this.loading = true;
      this.currentPage = page;
      this.render();

      const response = await usersAPI.list({ page });
      this.users = response.data || [];
      this.totalPages = response.pagination?.total_pages || 1;
      this.loading = false;
      this.render();
    } catch (error) {
      console.error('載入使用者列表失敗:', error);
      toast.error('載入使用者列表失敗');
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-3xl font-bold text-modern-900">使用者管理</h1>
          <button 
            id="addUserBtn" 
            class="px-6 py-3 bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition-colors font-medium"
          >
            <i class="fas fa-plus mr-2"></i>
            新增使用者
          </button>
        </div>

        ${this.renderUsersList()}
      </div>

      <!-- Modal 容器 -->
      <div id="modal-container"></div>
    `;

    const dashboardLayout = new DashboardLayout();
    dashboardLayout.render(content);
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
              ${this.users.map((user) => this.renderUserRow(user)).join('')}
            </tbody>
          </table>
        </div>

        ${this.renderPagination()}
      </div>
    `;
  }

  renderUserRow(user) {
    return `
      <tr class="hover:bg-modern-50 transition-colors">
        <td class="px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-accent-500 flex items-center justify-center text-white font-semibold">
              ${(user.username || 'U')[0].toUpperCase()}
            </div>
            <span class="font-medium text-modern-900">${this.escapeHtml(user.username)}</span>
          </div>
        </td>
        <td class="px-6 py-4 text-modern-700">${this.escapeHtml(user.email)}</td>
        <td class="px-6 py-4">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
            user.role === 'super_admin' 
              ? 'bg-purple-100 text-purple-800' 
              : 'bg-blue-100 text-blue-800'
          }">
            ${user.role === 'super_admin' ? '主管理員' : '管理員'}
          </span>
        </td>
        <td class="px-6 py-4 text-modern-700">${this.formatDate(user.created_at)}</td>
        <td class="px-6 py-4 text-modern-700">${this.formatDate(user.last_login)}</td>
        <td class="px-6 py-4">
          <div class="flex items-center justify-end gap-2">
            <button 
              class="edit-user-btn px-4 py-2 text-sm font-medium text-accent-700 border-2 border-accent-600 rounded-lg hover:bg-accent-50 transition-colors"
              data-user-id="${user.id}"
            >
              編輯
            </button>
            <button 
              class="delete-user-btn px-4 py-2 text-sm font-medium text-red-700 border-2 border-red-600 rounded-lg hover:bg-red-50 transition-colors"
              data-user-id="${user.id}"
            >
              刪除
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  renderPagination() {
    if (this.totalPages <= 1) return '';

    return `
      <div class="flex items-center justify-between px-6 py-4 border-t border-modern-200">
        <div class="text-sm text-modern-600">
          第 ${this.currentPage} / ${this.totalPages} 頁
        </div>
        <div class="flex gap-2">
          <button 
            id="prevPageBtn" 
            class="px-4 py-2 text-sm font-medium text-modern-700 border-2 border-modern-300 rounded-lg hover:bg-modern-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            ${this.currentPage <= 1 ? 'disabled' : ''}
          >
            上一頁
          </button>
          <button 
            id="nextPageBtn" 
            class="px-4 py-2 text-sm font-medium text-modern-700 border-2 border-modern-300 rounded-lg hover:bg-modern-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            ${this.currentPage >= this.totalPages ? 'disabled' : ''}
          >
            下一頁
          </button>
        </div>
      </div>
    `;
  }

  attachEventListeners() {
    // 新增使用者按鈕
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
      addUserBtn.addEventListener('click', () => this.showUserModal());
    }

    // 編輯使用者按鈕
    const editBtns = document.querySelectorAll('.edit-user-btn');
    editBtns.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const userId = parseInt(btn.dataset.userId);
        const user = this.users.find((u) => u.id === userId);
        if (user) {
          this.showUserModal(user);
        }
      });
    });

    // 刪除使用者按鈕
    const deleteBtns = document.querySelectorAll('.delete-user-btn');
    deleteBtns.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const userId = parseInt(btn.dataset.userId);
        await this.handleDeleteUser(userId);
      });
    });

    // 分頁按鈕
    const prevPageBtn = document.getElementById('prevPageBtn');
    if (prevPageBtn) {
      prevPageBtn.addEventListener('click', () => {
        if (this.currentPage > 1) {
          this.loadUsers(this.currentPage - 1);
        }
      });
    }

    const nextPageBtn = document.getElementById('nextPageBtn');
    if (nextPageBtn) {
      nextPageBtn.addEventListener('click', () => {
        if (this.currentPage < this.totalPages) {
          this.loadUsers(this.currentPage + 1);
        }
      });
    }
  }

  showUserModal(user = null) {
    const isEdit = !!user;
    const modalTitle = isEdit ? '編輯使用者' : '新增使用者';

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
            value="${user ? this.escapeHtml(user.username) : ''}"
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
            value="${user ? this.escapeHtml(user.email) : ''}"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          />
        </div>

        <div>
          <label for="role" class="block text-sm font-medium text-modern-700 mb-2">
            角色 *
          </label>
          <select
            id="role"
            name="role"
            class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            required
          >
            <option value="admin" ${!user || user.role === 'admin' ? 'selected' : ''}>管理員</option>
            <option value="super_admin" ${user && user.role === 'super_admin' ? 'selected' : ''}>主管理員</option>
          </select>
        </div>

        ${!isEdit ? `
          <div>
            <label for="password" class="block text-sm font-medium text-modern-700 mb-2">
              密碼 *
            </label>
            <input
              type="password"
              id="password"
              name="password"
              class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
              required
              minlength="8"
            />
            <p class="mt-1 text-sm text-modern-500">密碼長度至少 8 個字元</p>
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-modern-700 mb-2">
              確認密碼 *
            </label>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              class="w-full px-4 py-3 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
              required
              minlength="8"
            />
          </div>
        ` : ''}

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
            ${isEdit ? '儲存變更' : '新增使用者'}
          </button>
        </div>
      </form>
    `;

    this.modal = new Modal(modalTitle, modalContent);
    this.modal.show();

    // 綁定表單事件
    const userForm = document.getElementById('userForm');
    if (userForm) {
      userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (isEdit) {
          await this.handleUpdateUser(user.id, new FormData(userForm));
        } else {
          await this.handleCreateUser(new FormData(userForm));
        }
      });
    }

    // 取消按鈕
    const cancelBtn = document.getElementById('cancelModalBtn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => this.modal.hide());
    }
  }

  async handleCreateUser(formData) {
    try {
      const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        role: formData.get('role'),
        password: formData.get('password'),
        password_confirmation: formData.get('password_confirmation'),
      };

      // 驗證
      if (!data.username || data.username.length < 3) {
        toast.error('使用者名稱至少需要 3 個字元');
        return;
      }

      if (!data.email || !this.isValidEmail(data.email)) {
        toast.error('請輸入有效的電子郵件');
        return;
      }

      if (!data.password || data.password.length < 8) {
        toast.error('密碼長度至少需要 8 個字元');
        return;
      }

      if (data.password !== data.password_confirmation) {
        toast.error('密碼與確認密碼不符');
        return;
      }

      await usersAPI.create(data);
      toast.success('使用者建立成功');
      this.modal.hide();
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error('建立使用者失敗:', error);
      toast.error(error.message || '建立使用者失敗');
    }
  }

  async handleUpdateUser(userId, formData) {
    try {
      const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        role: formData.get('role'),
      };

      // 驗證
      if (!data.username || data.username.length < 3) {
        toast.error('使用者名稱至少需要 3 個字元');
        return;
      }

      if (!data.email || !this.isValidEmail(data.email)) {
        toast.error('請輸入有效的電子郵件');
        return;
      }

      await usersAPI.update(userId, data);
      toast.success('使用者更新成功');
      this.modal.hide();
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error('更新使用者失敗:', error);
      toast.error(error.message || '更新使用者失敗');
    }
  }

  async handleDeleteUser(userId) {
    const user = this.users.find((u) => u.id === userId);
    if (!user) return;

    if (!confirm(`確定要刪除使用者「${user.username}」嗎？此操作無法復原。`)) {
      return;
    }

    try {
      await usersAPI.delete(userId);
      toast.success('使用者刪除成功');
      await this.loadUsers(this.currentPage);
    } catch (error) {
      console.error('刪除使用者失敗:', error);
      toast.error(error.message || '刪除使用者失敗');
    }
  }

  // 工具函式
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

  formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  }

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
}
