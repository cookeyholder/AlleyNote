/**
 * 角色管理頁面
 */

import { rolesAPI, permissionsAPI } from '../../api/modules/users.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';
import { ConfirmationDialog } from '../../components/ConfirmationDialog.js';

let roles = [];
let permissions = [];
let groupedPermissions = {};

/**
 * 渲染角色管理頁面
 */
export async function renderRoles() {
  const content = `
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-modern-900">角色管理</h1>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 角色列表 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">角色列表</h2>
          <div id="rolesListContainer" class="space-y-3">
            <!-- 動態渲染 -->
          </div>
        </div>

        <!-- 權限管理 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">權限設定</h2>
          <div id="permissionsContainer">
            <p class="text-gray-500">請選擇一個角色來管理權限</p>
          </div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('app').innerHTML = content;

  // 載入資料
  await loadRolesAndPermissions();
}

/**
 * 載入角色和權限資料
 */
async function loadRolesAndPermissions() {
  loading.show('載入資料中...');
  
  try {
    const [rolesResult, permissionsResult] = await Promise.all([
      rolesAPI.list(),
      permissionsAPI.listGrouped()
    ]);

    roles = rolesResult.data || [];
    groupedPermissions = permissionsResult.data || {};

    renderRolesList();
  } catch (error) {
    console.error('載入資料失敗:', error);
    toast.error('載入資料失敗');
  } finally {
    loading.hide();
  }
}

/**
 * 渲染角色列表
 */
function renderRolesList() {
  const container = document.getElementById('rolesListContainer');
  
  if (roles.length === 0) {
    container.innerHTML = '<p class="text-gray-500">尚無角色資料</p>';
    return;
  }

  container.innerHTML = roles.map(role => `
    <div class="border border-gray-200 rounded-lg p-4 hover:border-modern-500 cursor-pointer transition-colors"
         onclick="window.selectRole(${role.id})">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold text-lg">${escapeHtml(role.display_name || role.name)}</h3>
          <p class="text-sm text-gray-600">${escapeHtml(role.description || '')}</p>
        </div>
        ${!['super_admin', 'admin'].includes(role.name) ? `
          <button 
            onclick="event.stopPropagation(); window.deleteRole(${role.id}, '${escapeHtml(role.display_name || role.name)}')"
            class="text-red-600 hover:text-red-800 text-sm"
          >
            刪除
          </button>
        ` : ''}
      </div>
    </div>
  `).join('');
}

/**
 * 選擇角色
 */
window.selectRole = async function(roleId) {
  loading.show('載入角色權限...');
  
  try {
    const result = await rolesAPI.get(roleId);
    const roleData = result.data;
    
    renderPermissionsEditor(roleData);
  } catch (error) {
    console.error('載入角色權限失敗:', error);
    toast.error('載入角色權限失敗');
  } finally {
    loading.hide();
  }
};

/**
 * 渲染權限編輯器
 */
function renderPermissionsEditor(roleData) {
  const container = document.getElementById('permissionsContainer');
  const { role, permission_ids } = roleData;

  let permissionsHTML = '';
  
  for (const [resource, perms] of Object.entries(groupedPermissions)) {
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
                class="permission-checkbox rounded border-gray-300 text-modern-600 focus:ring-modern-500"
              />
              <span class="text-sm">${escapeHtml(perm.display_name || perm.name)}</span>
              ${perm.description ? `<span class="text-xs text-gray-500">(${escapeHtml(perm.description)})</span>` : ''}
            </label>
          `).join('')}
        </div>
      </div>
    `;
  }

  container.innerHTML = `
    <div>
      <div class="mb-4 pb-4 border-b">
        <h3 class="text-lg font-bold">${escapeHtml(role.display_name || role.name)}</h3>
        <p class="text-sm text-gray-600">${escapeHtml(role.description || '')}</p>
      </div>
      
      <div class="max-h-96 overflow-y-auto mb-4">
        ${permissionsHTML}
      </div>
      
      <div class="flex justify-end space-x-3">
        <button
          onclick="window.cancelEdit()"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          取消
        </button>
        <button
          onclick="window.savePermissions(${role.id})"
          class="px-4 py-2 bg-modern-600 text-white rounded-lg hover:bg-modern-700"
        >
          儲存權限
        </button>
      </div>
    </div>
  `;
}

/**
 * 儲存權限
 */
window.savePermissions = async function(roleId) {
  const checkboxes = document.querySelectorAll('.permission-checkbox:checked');
  const permissionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

  loading.show('儲存中...');
  
  try {
    await rolesAPI.updatePermissions(roleId, permissionIds);
    toast.success('權限已更新');
  } catch (error) {
    console.error('更新權限失敗:', error);
    toast.error('更新權限失敗');
  } finally {
    loading.hide();
  }
};

/**
 * 取消編輯
 */
window.cancelEdit = function() {
  document.getElementById('permissionsContainer').innerHTML = '<p class="text-gray-500">請選擇一個角色來管理權限</p>';
};

/**
 * 刪除角色
 */
window.deleteRole = async function(id, name) {
  const confirmed = await ConfirmationDialog.show({
    title: '確認刪除',
    message: `確定要刪除角色「${name}」嗎？此操作無法復原。`,
    confirmText: '刪除',
    confirmClass: 'bg-red-600 hover:bg-red-700'
  });

  if (!confirmed) return;

  loading.show('刪除中...');
  
  try {
    await rolesAPI.delete(id);
    toast.success('角色已刪除');
    await loadRolesAndPermissions();
    window.cancelEdit();
  } catch (error) {
    console.error('刪除角色失敗:', error);
    toast.error(error.response?.data?.message || '刪除角色失敗');
  } finally {
    loading.hide();
  }
};

/**
 * HTML 轉義
 */
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
