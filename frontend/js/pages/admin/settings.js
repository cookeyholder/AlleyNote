/**
 * 系統設定頁面
 */
import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { globalGetters } from '../../store/globalStore.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';
import { timezoneUtils } from '../../utils/timezoneUtils.js';
import { apiClient } from '../../api/client.js';

// 儲存原始設定值
let originalSettings = {};
let currentSettings = {};

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
              value=""
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
            ></textarea>
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
                允許留言
              </label>
              <p class="text-sm text-modern-500">啟用此選項後，文章下方會顯示留言功能</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="enable-comments" class="sr-only peer">
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
              value=""
              min="5"
              max="50"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
            <p class="text-sm text-modern-500 mt-1">建議範圍：5-50 篇</p>
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
      
      <!-- 使用者設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">使用者設定</h2>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-sm font-medium text-modern-700">
                允許使用者註冊
              </label>
              <p class="text-sm text-modern-500">啟用後，訪客可以自行註冊新帳號</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="enable-registration" class="sr-only peer">
              <div class="w-11 h-6 bg-modern-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-modern-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent-600"></div>
            </label>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              最大上傳檔案大小（MB）
            </label>
            <input
              type="number"
              id="max-upload-size"
              value=""
              min="1"
              max="100"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
            <p class="text-sm text-modern-500 mt-1">建議範圍：1-100 MB</p>
          </div>
        </div>
      </div>
      
      <!-- 檔案上傳設定 -->
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
        <h2 class="text-xl font-semibold text-modern-900 mb-4">檔案上傳設定</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              允許的檔案類型
            </label>
            <div id="file-types-container" class="space-y-2">
              <!-- 動態生成的 checkbox 會插入這裡 -->
            </div>
            <p class="text-sm text-modern-500 mt-2">
              選擇允許使用者上傳的檔案類型
            </p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-modern-700 mb-2">
              單篇文章附件數量上限
            </label>
            <input
              type="number"
              id="max-attachments-per-post"
              value=""
              min="1"
              max="50"
              class="w-full px-4 py-2 border border-modern-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-transparent"
            />
            <p class="text-sm text-modern-500 mt-1">建議範圍：1-50 個附件</p>
          </div>
          
          <div class="bg-info-50 border border-info-200 rounded-lg p-4">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-info-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <div class="text-sm text-info-800">
                <p class="font-medium mb-1">安全提示</p>
                <p>建議只允許必要的檔案類型，以降低安全風險。可執行檔案（如 .exe、.bat）已被系統禁止。檔案內容會使用 magic numbers 驗證，無法透過修改副檔名繞過檢查。</p>
              </div>
            </div>
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
    </div>
  `;

  renderDashboardLayout(content, { title: '系統設定' });
  bindDashboardLayoutEvents();
  
  // 載入設定
  await loadSettings();
  
  // 載入時區設定
  await loadTimezoneSettings();
  
  // 綁定事件
  bindSettingsEvents();
}

/**
 * 載入所有設定
 */
async function loadSettings() {
  try {
    loading.show('載入設定中...');
    
    const response = await apiClient.get('/settings');
    const settingsData = response.data || {};
    
    // 提取設定值（API 回傳的是 {value, type, description} 結構）
    const settings = {};
    Object.keys(settingsData).forEach(key => {
      const item = settingsData[key];
      settings[key] = item && typeof item === 'object' && 'value' in item ? item.value : item;
    });
    
    // 儲存原始設定
    originalSettings = { ...settings };
    currentSettings = { ...settings };
    
    // 填充表單
    const siteNameInput = document.getElementById('site-name');
    const siteDescInput = document.getElementById('site-description');
    const postsPerPageInput = document.getElementById('posts-per-page');
    const enableCommentsInput = document.getElementById('enable-comments');
    const enableRegistrationInput = document.getElementById('enable-registration');
    const maxUploadSizeInput = document.getElementById('max-upload-size');
    const maxAttachmentsInput = document.getElementById('max-attachments-per-post');
    
    if (siteNameInput) siteNameInput.value = settings.site_name || 'AlleyNote';
    if (siteDescInput) siteDescInput.value = settings.site_description || '';
    if (postsPerPageInput) postsPerPageInput.value = settings.posts_per_page || '20';
    if (enableCommentsInput) enableCommentsInput.checked = settings.enable_comments === true || settings.enable_comments === 1;
    if (enableRegistrationInput) enableRegistrationInput.checked = settings.enable_registration === true || settings.enable_registration === 1;
    
    // 最大上傳檔案大小（轉換為 MB）
    if (maxUploadSizeInput) {
      const sizeInBytes = parseInt(settings.max_upload_size || '10485760');
      const sizeInMB = Math.floor(sizeInBytes / 1048576);
      maxUploadSizeInput.value = sizeInMB.toString();
    }
    
    // 單篇文章附件數量上限
    if (maxAttachmentsInput) {
      maxAttachmentsInput.value = settings.max_attachments_per_post || '10';
    }
    
    // 載入檔案類型設定
    await loadFileTypesSettings(settings.allowed_file_types || []);
    
    loading.hide();
  } catch (error) {
    loading.hide();
    console.error('載入設定失敗:', error);
    toast.error('載入設定失敗');
  }
}

/**
 * 載入檔案類型設定
 */
async function loadFileTypesSettings(allowedTypes) {
  const container = document.getElementById('file-types-container');
  if (!container) return;
  
  // 所有可能的檔案類型（根據 API 文檔）
  const allFileTypes = [
    // 圖片
    { value: 'jpg', label: 'JPG 圖片', category: '圖片' },
    { value: 'jpeg', label: 'JPEG 圖片', category: '圖片' },
    { value: 'png', label: 'PNG 圖片', category: '圖片' },
    { value: 'gif', label: 'GIF 動圖', category: '圖片' },
    { value: 'webp', label: 'WebP 圖片', category: '圖片' },
    { value: 'svg', label: 'SVG 向量圖', category: '圖片' },
    
    // PDF
    { value: 'pdf', label: 'PDF 文件', category: '文件' },
    
    // Microsoft Office
    { value: 'doc', label: 'Word (.doc)', category: 'Microsoft Office' },
    { value: 'docx', label: 'Word (.docx)', category: 'Microsoft Office' },
    { value: 'xls', label: 'Excel (.xls)', category: 'Microsoft Office' },
    { value: 'xlsx', label: 'Excel (.xlsx)', category: 'Microsoft Office' },
    { value: 'ppt', label: 'PowerPoint (.ppt)', category: 'Microsoft Office' },
    { value: 'pptx', label: 'PowerPoint (.pptx)', category: 'Microsoft Office' },
    
    // LibreOffice
    { value: 'odt', label: 'Writer 文件 (.odt)', category: 'LibreOffice' },
    { value: 'ott', label: 'Writer 範本 (.ott)', category: 'LibreOffice' },
    { value: 'ods', label: 'Calc 試算表 (.ods)', category: 'LibreOffice' },
    { value: 'ots', label: 'Calc 範本 (.ots)', category: 'LibreOffice' },
    { value: 'odp', label: 'Impress 簡報 (.odp)', category: 'LibreOffice' },
    { value: 'otp', label: 'Impress 範本 (.otp)', category: 'LibreOffice' },
    { value: 'odg', label: 'Draw 繪圖 (.odg)', category: 'LibreOffice' },
    
    // 純文字
    { value: 'txt', label: '純文字檔', category: '文件' },
    
    // 壓縮檔
    { value: 'zip', label: 'ZIP 壓縮檔', category: '壓縮檔' },
    { value: 'rar', label: 'RAR 壓縮檔', category: '壓縮檔' },
    { value: '7z', label: '7Z 壓縮檔', category: '壓縮檔' },
    
    // 媒體
    { value: 'mp3', label: 'MP3 音訊', category: '媒體' },
    { value: 'mp4', label: 'MP4 影片', category: '媒體' },
    { value: 'avi', label: 'AVI 影片', category: '媒體' },
    { value: 'mov', label: 'MOV 影片', category: '媒體' }
  ];
  
  // 按類別分組
  const categories = {};
  allFileTypes.forEach(type => {
    if (!categories[type.category]) {
      categories[type.category] = [];
    }
    categories[type.category].push(type);
  });
  
  // 生成 HTML
  let html = '';
  Object.entries(categories).forEach(([category, types]) => {
    html += `
      <div class="mb-3">
        <h4 class="text-sm font-medium text-modern-700 mb-2">${category}</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
          ${types.map(type => `
            <label class="flex items-center gap-2 p-2 border border-modern-200 rounded hover:bg-modern-50 cursor-pointer">
              <input 
                type="checkbox" 
                name="file-type" 
                value="${type.value}"
                ${allowedTypes.includes(type.value) ? 'checked' : ''}
                class="w-4 h-4 text-accent-600 border-modern-300 rounded focus:ring-accent-500"
              />
              <span class="text-sm text-modern-700">${type.label}</span>
            </label>
          `).join('')}
        </div>
      </div>
    `;
  });
  
  container.innerHTML = html;
}

/**
 * 載入時區設定
 */
async function loadTimezoneSettings() {
  try {
    // 從 API 載入所有時區列表
    const response = await apiClient.get('/settings/timezone/info');
    
    const timezoneSelect = document.getElementById('site-timezone');
    if (timezoneSelect && response.success && response.data) {
      const timezones = response.data.common_timezones || {};
      
      // 轉換物件為陣列並排序
      const timezoneArray = Object.entries(timezones).map(([value, label]) => ({
        value,
        label
      }));
      
      // 按標籤排序
      timezoneArray.sort((a, b) => a.label.localeCompare(b.label));
      
      // 生成選項
      timezoneSelect.innerHTML = timezoneArray.map(tz => 
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
      resetSettings();
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
 * 重置設定
 */
function resetSettings() {
  const siteNameInput = document.getElementById('site-name');
  const siteDescInput = document.getElementById('site-description');
  const postsPerPageInput = document.getElementById('posts-per-page');
  const enableCommentsInput = document.getElementById('enable-comments');
  const enableRegistrationInput = document.getElementById('enable-registration');
  const maxUploadSizeInput = document.getElementById('max-upload-size');
  const maxAttachmentsInput = document.getElementById('max-attachments-per-post');
  const timezoneSelect = document.getElementById('site-timezone');
  
  if (siteNameInput) siteNameInput.value = originalSettings.site_name || 'AlleyNote';
  if (siteDescInput) siteDescInput.value = originalSettings.site_description || '';
  if (postsPerPageInput) postsPerPageInput.value = originalSettings.posts_per_page || '20';
  if (enableCommentsInput) enableCommentsInput.checked = originalSettings.enable_comments === '1' || originalSettings.enable_comments === true;
  if (enableRegistrationInput) enableRegistrationInput.checked = originalSettings.enable_registration === '1' || originalSettings.enable_registration === true;
  
  if (maxUploadSizeInput) {
    const sizeInMB = Math.floor((parseInt(originalSettings.max_upload_size || '10485760')) / 1048576);
    maxUploadSizeInput.value = sizeInMB.toString();
  }
  
  if (maxAttachmentsInput) {
    maxAttachmentsInput.value = originalSettings.max_attachments_per_post || '10';
  }
  
  if (timezoneSelect && originalSettings.site_timezone) {
    timezoneSelect.value = originalSettings.site_timezone;
  }
  
  // 重置檔案類型
  const allowedTypes = originalSettings.allowed_file_types || [];
  const fileTypeCheckboxes = document.querySelectorAll('input[name="file-type"]');
  fileTypeCheckboxes.forEach(cb => {
    cb.checked = allowedTypes.includes(cb.value);
  });
  
  toast.info('設定已重置為原始值');
}

/**
 * 儲存設定
 */
async function saveSettings() {
  try {
    loading.show('儲存設定中...');
    
    const siteNameInput = document.getElementById('site-name');
    const siteDescInput = document.getElementById('site-description');
    const postsPerPageInput = document.getElementById('posts-per-page');
    const enableCommentsInput = document.getElementById('enable-comments');
    const enableRegistrationInput = document.getElementById('enable-registration');
    const maxUploadSizeInput = document.getElementById('max-upload-size');
    const maxAttachmentsInput = document.getElementById('max-attachments-per-post');
    const timezoneSelect = document.getElementById('site-timezone');
    
    // 收集設定
    const settings = {};
    
    if (siteNameInput?.value) settings.site_name = siteNameInput.value;
    if (siteDescInput?.value !== undefined) settings.site_description = siteDescInput.value;
    if (postsPerPageInput?.value) settings.posts_per_page = postsPerPageInput.value;
    if (enableCommentsInput !== null) settings.enable_comments = enableCommentsInput.checked ? '1' : '0';
    if (enableRegistrationInput !== null) settings.enable_registration = enableRegistrationInput.checked ? '1' : '0';
    if (timezoneSelect?.value) settings.site_timezone = timezoneSelect.value;
    
    // 最大上傳檔案大小（轉換為 bytes）
    if (maxUploadSizeInput?.value) {
      const sizeInBytes = parseInt(maxUploadSizeInput.value) * 1048576;
      settings.max_upload_size = sizeInBytes.toString();
    }
    
    // 單篇文章附件數量上限
    if (maxAttachmentsInput?.value) {
      settings.max_attachments_per_post = maxAttachmentsInput.value;
    }
    
    // 收集檔案類型設定
    const fileTypeCheckboxes = document.querySelectorAll('input[name="file-type"]:checked');
    const allowedFileTypes = Array.from(fileTypeCheckboxes).map(cb => cb.value);
    if (allowedFileTypes.length > 0) {
      settings.allowed_file_types = JSON.stringify(allowedFileTypes);
    }
    
    // 批量更新設定
    await apiClient.put('/settings', settings);
    
    // 清除時區快取
    timezoneUtils.clearCache();
    
    // 更新原始設定
    originalSettings = { ...settings, allowed_file_types: allowedFileTypes };
    currentSettings = { ...settings, allowed_file_types: allowedFileTypes };
    
    loading.hide();
    toast.success('設定已儲存');
  } catch (error) {
    loading.hide();
    console.error('儲存設定失敗:', error);
    toast.error('儲存設定失敗：' + (error.message || '未知錯誤'));
  }
}
