/**
 * 系統設定頁面
 */
import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { globalGetters } from '../../store/globalStore.js';
import { toast } from '../../utils/toast.js';
import { timezoneUtils } from '../../utils/timezoneUtils.js';
import { apiClient } from '../../api/client.js';

export async function renderSettings() {
  const content = `
    <div class="max-w-4xl mx-auto">
      <!-- 基本設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">基本設定</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              網站名稱
            </label>
            <input
              type="text"
              id="site-name"
              value="AlleyNote"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              網站描述
            </label>
            <textarea
              id="site-description"
              rows="3"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            >現代化公布欄系統</textarea>
          </div>
        </div>
      </div>
      
      <!-- 文章設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">文章設定</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-sm font-medium text-modern-700">
                允許訪客評論
              </label>
              <p class="text-sm text-modern-500">啟用此選項後，未登入使用者也可以留言</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="allow-guest-comments" class="sr-only peer">
              <div class="w-11 h-6 bg-modern-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-modern-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent-600"></div>
            </label>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              每頁顯示文章數
            </label>
            <input
              type="number"
              id="posts-per-page"
              value="10"
              min="5"
              max="50"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>
      
      <!-- 時區設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">時區設定</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              網站時區
            </label>
            <select
              id="site-timezone"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            >
              <option value="">載入中...</option>
            </select>
            <p class="text-sm text-modern-500 mt-2">
              設定網站使用的時區，影響所有時間的顯示與儲存
            </p>
          </div>
          
          <div class="bg-modern-50 border border-modern-200 rounded-lg p-4">
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-5 h-5 text-modern-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-modern-700 font-medium">當前網站時間：</span>
              <span id="current-site-time" class="text-modern-900">--:--:--</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 安全設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">安全設定</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-sm font-medium text-modern-700">
                啟用 IP 封鎖
              </label>
              <p class="text-sm text-modern-500">自動封鎖可疑 IP 地址</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="enable-ip-blocking" class="sr-only peer" checked>
              <div class="w-11 h-6 bg-modern-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-modern-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent-600"></div>
            </label>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              登入失敗限制次數
            </label>
            <input
              type="number"
              id="max-login-attempts"
              value="5"
              min="3"
              max="10"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>
      
      <!-- 儲存按鈕 -->
      <div class="flex justify-end gap-4">
        <button
          id="reset-btn"
          class="px-6 py-2 border border-modern-300 text-modern-700 rounded-lg hover:bg-modern-50 transition-colors"
        >
          重置
        </button>
        <button
          id="save-btn"
          class="px-6 py-2 bg-accent-600 text-white rounded-lg hover:bg-accent-700 transition-colors"
        >
          儲存設定
        </button>
      </div>
      
      <div class="mt-8 p-4 bg-warning-50 border border-warning-200 rounded-lg">
        <p class="text-sm text-warning-800">
          <strong>注意：</strong>系統設定功能尚未完全實現，這些設定目前僅為展示用途。
        </p>
      </div>
    </div>
  `;

  renderDashboardLayout(content, { title: '系統設定' });
  bindDashboardLayoutEvents();
  
  // 載入時區設定
  await loadTimezoneSettings();
  
  // 綁定事件
  bindSettingsEvents();
}

/**
 * 載入時區設定
 */
async function loadTimezoneSettings() {
  try {
    // 載入時區列表
    const timezoneSelect = document.getElementById('site-timezone');
    if (timezoneSelect) {
      const timezones = timezoneUtils.getCommonTimezones();
      timezoneSelect.innerHTML = timezones.map(tz => 
        `<option value="${tz.value}">${tz.label}</option>`
      ).join('');

      // 載入當前時區設定
      const currentTimezone = await timezoneUtils.getSiteTimezone();
      timezoneSelect.value = currentTimezone;
    }

    // 更新當前網站時間
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
  } catch (error) {
    console.error('載入時區設定失敗:', error);
    toast.error('載入時區設定失敗');
  }
}

/**
 * 更新當前時間顯示
 */
function updateCurrentTime() {
  const timeElement = document.getElementById('current-site-time');
  if (timeElement) {
    const now = new Date();
    const timeString = now.toLocaleString('zh-TW', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });
    timeElement.textContent = timeString;
  }
}

function bindSettingsEvents() {
  const saveBtn = document.getElementById('save-btn');
  const resetBtn = document.getElementById('reset-btn');
  const timezoneSelect = document.getElementById('site-timezone');
  
  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      await saveSettings();
    });
  }
  
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      toast.info('系統設定功能尚未實現');
    });
  }

  // 時區變更預覽
  if (timezoneSelect) {
    timezoneSelect.addEventListener('change', () => {
      updateCurrentTime();
    });
  }
}

/**
 * 儲存設定
 */
async function saveSettings() {
  try {
    const timezoneSelect = document.getElementById('site-timezone');
    const newTimezone = timezoneSelect?.value;

    if (newTimezone) {
      // 更新時區設定
      await apiClient.put('/settings/site_timezone', {
        value: newTimezone
      });

      // 清除時區快取
      timezoneUtils.clearCache();

      toast.success('時區設定已儲存');
    } else {
      toast.info('系統設定功能部分尚未實現');
    }
  } catch (error) {
    console.error('儲存設定失敗:', error);
    toast.error('儲存設定失敗');
  }
}
