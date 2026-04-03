/**
 * 系統設定頁面
 */
import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { globalGetters } from "../../store/globalStore.js";
import { notification } from "../../utils/notification.js";
import { loading } from "../../components/Loading.js";
import { timezoneUtils } from "../../utils/timezoneUtils.js";
import { apiClient } from "../../api/client.js";
import {
  initRichTextEditor,
  destroyRichTextEditor,
  getRichTextEditorContent,
} from "../../components/RichTextEditor.js";

// 儲存原始設定值
let originalSettings = {};

// 儲存 setInterval ID 以便清理
let currentTimeIntervalId = null;
let currentSettings = {};
let footerDescriptionEditor = null;

export async function renderSettings() {
  const content = `
    <div class="max-w-4xl mx-auto pb-12">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-modern-900">系統設定</h1>
        <p class="text-sm text-modern-500 mt-1">調整全站運行的核心參數與顯示規則</p>
      </div>

      <!-- 基本設定 -->
      <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-accent-50 text-accent-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h1.5a2.5 2.5 0 012.5 2.5v.5m-9-11a9 9 0 1118 0 9 9 0 01-18 0z"/></svg>
          </div>
          <h2 class="text-xl font-bold text-modern-900">基本設定</h2>
        </div>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">網站名稱</label>
            <input
              type="text"
              id="site-name"
              placeholder="例如：AlleyNote 公布欄"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 focus:border-transparent transition-all outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">網站描述</label>
            <textarea
              id="site-description"
              rows="3"
              placeholder="簡單介紹您的網站內容..."
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 focus:border-transparent transition-all outline-none"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- 文章與互動 -->
      <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
          </div>
          <h2 class="text-xl font-bold text-modern-900">文章與互動</h2>
        </div>
        <div class="space-y-6">
          <div class="flex items-center justify-between p-4 bg-modern-50 rounded-2xl">
            <div>
              <label class="block text-sm font-bold text-modern-800">允許留言</label>
              <p class="text-xs text-modern-500 mt-0.5">啟用後，訪客可在文章下方進行互動討論</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="enable-comments" class="sr-only peer">
              <div class="w-12 h-6 bg-modern-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-modern-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>

          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">每頁顯示文章數</label>
            <div class="flex items-center gap-4">
              <input
                type="number"
                id="posts-per-page"
                min="5"
                max="50"
                class="w-32 px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
              />
              <span class="text-sm text-modern-500">建議設置在 10 至 30 之間</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 國際化與時區 -->
      <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h2 class="text-xl font-bold text-modern-900">國際化與時區</h2>
        </div>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">網站主要時區</label>
            <select
              id="site-timezone"
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none appearance-none"
            >
              <option value="">載入中...</option>
            </select>
          </div>

          <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-blue-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">當前網站伺服器時間</p>
              <p id="current-site-time" class="text-xl font-bold text-blue-900 tabular-nums">--:--:--</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 安全與上傳 -->
      <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <h2 class="text-xl font-bold text-modern-900">安全與上傳</h2>
        </div>
        <div class="space-y-8">
          <div class="flex items-center justify-between p-4 bg-modern-50 rounded-2xl">
            <div>
              <label class="block text-sm font-bold text-modern-800">開放訪客註冊</label>
              <p class="text-xs text-modern-500 mt-0.5">關閉此項後，僅能由管理員手動建立帳號</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="enable-registration" class="sr-only peer">
              <div class="w-12 h-6 bg-modern-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-modern-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
            </label>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-bold text-modern-700 mb-2">單檔上傳上限 (MB)</label>
              <input
                type="number"
                id="max-upload-size"
                min="1"
                max="100"
                class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-bold text-modern-700 mb-2">單篇文章附件上限</label>
              <input
                type="number"
                id="max-attachments-per-post"
                min="1"
                max="50"
                class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-modern-700 mb-4">允許上傳的檔案副檔名</label>
            <div id="file-types-container" class="space-y-6">
              <!-- 動態生成 -->
            </div>
          </div>

          <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-5 flex items-start gap-4">
            <div class="p-2 bg-white rounded-xl shadow-sm text-amber-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <h4 class="text-sm font-bold text-amber-900 mb-1">系統安全性備註</h4>
              <p class="text-xs text-amber-700 leading-relaxed">
                系統會對上傳檔案進行二進位特徵碼驗證，禁止所有可執行格式 (.exe, .bat, .sh)。
                建議只勾選必要的媒體或文件格式以確保系統安全。
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- 頁腳客製化 -->
      <div class="card bg-white border-modern-200 shadow-sm p-8 mb-10">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-purple-50 text-purple-600 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          </div>
          <h2 class="text-xl font-bold text-modern-900">頁腳客製化</h2>
        </div>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">版權聲明文字</label>
            <input
              type="text"
              id="footer-copyright"
              placeholder="© 2024 AlleyNote. All rights reserved."
              class="w-full px-4 py-3 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-modern-700 mb-2">頁腳描述內容 (富文本)</label>
            <div id="footer-description-editor" class="border border-modern-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-accent-500 transition-all"></div>
          </div>
        </div>
      </div>

      <!-- 控制列 -->
      <div class="flex items-center justify-between p-6 bg-modern-900 rounded-2xl shadow-xl shadow-modern-200 animate-slide-up">
        <div class="text-modern-400 text-sm hidden md:block">
          所有變更在點擊「儲存」前不會生效
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
          <button
            id="reset-btn"
            class="flex-1 md:flex-none px-8 py-3 bg-modern-800 text-modern-300 font-bold rounded-xl hover:bg-modern-700 hover:text-white transition-all"
          >
            重置回原始值
          </button>
          <button
            id="save-btn"
            class="flex-1 md:flex-none px-10 py-3 bg-accent-600 text-white font-bold rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all"
          >
            儲存所有設定
          </button>
        </div>
      </div>
    </div>
  `;

  renderDashboardLayout(content, { title: "系統設定" });
  bindDashboardLayoutEvents();

  // 載入設定
  await loadSettings();

  // 載入時區設定
  await loadTimezoneSettings();

  // 初始化富文本編輯器
  await initFooterDescriptionEditor();

  // 綁定事件
  bindSettingsEvents();
}

/**
 * 清理設定頁面資源（導航離開時呼叫）
 */
export function cleanupSettings() {
  if (currentTimeIntervalId) {
    clearInterval(currentTimeIntervalId);
    currentTimeIntervalId = null;
  }
  if (footerDescriptionEditor) {
    destroyRichTextEditor("footer-description-editor");
    footerDescriptionEditor = null;
  }
}

/**
 * 初始化頁腳描述富文本編輯器
 */
async function initFooterDescriptionEditor() {
  try {
    // 先銷毀舊的編輯器實例（如果存在）
    if (footerDescriptionEditor) {
      await destroyRichTextEditor("footer-description-editor");
      footerDescriptionEditor = null;
    }

    // 創建新的編輯器實例
    footerDescriptionEditor = await initRichTextEditor(
      "footer-description-editor",
      {
        placeholder: "請輸入頁腳描述...",
        initialValue:
          originalSettings.footer_description ||
          "基於 Domain-Driven Design 的企業級公布欄系統",
        config: {
          toolbar: {
            items: [
              "heading",
              "|",
              "bold",
              "italic",
              "underline",
              "|",
              "fontSize",
              "fontColor",
              "|",
              "alignment",
              "|",
              "link",
              "bulletedList",
              "numberedList",
              "|",
              "removeFormat",
              "|",
              "undo",
              "redo",
            ],
          },
        },
      },
    );
  } catch (error) {
    console.error("初始化頁腳描述編輯器失敗:", error);
    notification.error("初始化編輯器失敗");
  }
}

/**
 * 載入所有設定
 */
async function loadSettings() {
  try {
    loading.show("載入設定中...");

    const response = await apiClient.get("/settings");
    const settingsData = response.data || {};

    // 提取設定值（API 回傳的是 {value, type, description} 結構）
    const settings = {};
    Object.keys(settingsData).forEach((key) => {
      const item = settingsData[key];
      settings[key] =
        item && typeof item === "object" && "value" in item ? item.value : item;
    });

    // 儲存原始設定
    originalSettings = { ...settings };
    currentSettings = { ...settings };

    // 填充表單
    const siteNameInput = document.getElementById("site-name");
    const siteDescInput = document.getElementById("site-description");
    const postsPerPageInput = document.getElementById("posts-per-page");
    const enableCommentsInput = document.getElementById("enable-comments");
    const enableRegistrationInput = document.getElementById(
      "enable-registration",
    );
    const maxUploadSizeInput = document.getElementById("max-upload-size");
    const maxAttachmentsInput = document.getElementById(
      "max-attachments-per-post",
    );
    const footerCopyrightInput = document.getElementById("footer-copyright");

    if (siteNameInput) siteNameInput.value = settings.site_name || "AlleyNote";
    if (siteDescInput) siteDescInput.value = settings.site_description || "";
    if (postsPerPageInput)
      postsPerPageInput.value = settings.posts_per_page || "20";
    if (enableCommentsInput)
      enableCommentsInput.checked =
        settings.enable_comments === true || settings.enable_comments === 1;
    if (enableRegistrationInput)
      enableRegistrationInput.checked =
        settings.enable_registration === true ||
        settings.enable_registration === 1;

    // Footer 設定
    if (footerCopyrightInput)
      footerCopyrightInput.value =
        settings.footer_copyright || "© 2024 AlleyNote. All rights reserved.";

    // 頁腳描述會在編輯器初始化時設置
    // 但我們需要儲存這個值以供編輯器使用
    if (settings.footer_description) {
      originalSettings.footer_description = settings.footer_description;
    }

    // 最大上傳檔案大小（轉換為 MB）
    if (maxUploadSizeInput) {
      const sizeInBytes = parseInt(settings.max_upload_size || "10485760");
      const sizeInMB = Math.floor(sizeInBytes / 1048576);
      maxUploadSizeInput.value = sizeInMB.toString();
    }

    // 單篇文章附件數量上限
    if (maxAttachmentsInput) {
      maxAttachmentsInput.value = settings.max_attachments_per_post || "10";
    }

    // 載入檔案類型設定
    await loadFileTypesSettings(settings.allowed_file_types || []);

    loading.hide();
  } catch (error) {
    loading.hide();
    console.error("載入設定失敗:", error);
    notification.error("載入設定失敗");
  }
}

/**
 * 載入檔案類型設定
 */
async function loadFileTypesSettings(allowedTypes) {
  const container = document.getElementById("file-types-container");
  if (!container) return;

  // 所有可能的檔案類型（根據 API 文檔）
  const allFileTypes = [
    // 圖片
    { value: "jpg", label: "JPG 圖片", category: "圖片" },
    { value: "jpeg", label: "JPEG 圖片", category: "圖片" },
    { value: "png", label: "PNG 圖片", category: "圖片" },
    { value: "gif", label: "GIF 動圖", category: "圖片" },
    { value: "webp", label: "WebP 圖片", category: "圖片" },
    { value: "svg", label: "SVG 向量圖", category: "圖片" },

    // PDF
    { value: "pdf", label: "PDF 文件", category: "文件" },

    // Microsoft Office
    { value: "doc", label: "Word (.doc)", category: "Microsoft Office" },
    { value: "docx", label: "Word (.docx)", category: "Microsoft Office" },
    { value: "xls", label: "Excel (.xls)", category: "Microsoft Office" },
    { value: "xlsx", label: "Excel (.xlsx)", category: "Microsoft Office" },
    { value: "ppt", label: "PowerPoint (.ppt)", category: "Microsoft Office" },
    {
      value: "pptx",
      label: "PowerPoint (.pptx)",
      category: "Microsoft Office",
    },

    // LibreOffice
    { value: "odt", label: "Writer 文件 (.odt)", category: "LibreOffice" },
    { value: "ott", label: "Writer 範本 (.ott)", category: "LibreOffice" },
    { value: "ods", label: "Calc 試算表 (.ods)", category: "LibreOffice" },
    { value: "ots", label: "Calc 範本 (.ots)", category: "LibreOffice" },
    { value: "odp", label: "Impress 簡報 (.odp)", category: "LibreOffice" },
    { value: "otp", label: "Impress 範本 (.otp)", category: "LibreOffice" },
    { value: "odg", label: "Draw 繪圖 (.odg)", category: "LibreOffice" },

    // 純文字
    { value: "txt", label: "純文字檔", category: "文件" },

    // 壓縮檔
    { value: "zip", label: "ZIP 壓縮檔", category: "壓縮檔" },
    { value: "rar", label: "RAR 壓縮檔", category: "壓縮檔" },
    { value: "7z", label: "7Z 壓縮檔", category: "壓縮檔" },

    // 媒體
    { value: "mp3", label: "MP3 音訊", category: "媒體" },
    { value: "mp4", label: "MP4 影片", category: "媒體" },
    { value: "avi", label: "AVI 影片", category: "媒體" },
    { value: "mov", label: "MOV 影片", category: "媒體" },
  ];

  // 按類別分組
  const categories = {};
  allFileTypes.forEach((type) => {
    if (!categories[type.category]) {
      categories[type.category] = [];
    }
    categories[type.category].push(type);
  });

  // 生成 HTML
  let html = "";
  Object.entries(categories).forEach(([category, types]) => {
    html += `
      <div class="mb-3">
        <h4 class="text-sm font-medium text-modern-700 mb-2">${category}</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
          ${types
            .map(
              (type) => `
            <label class="flex items-center gap-2 p-2 border border-modern-200 rounded hover:bg-modern-50 cursor-pointer">
              <input
                type="checkbox"
                name="file-type"
                value="${type.value}"
                ${allowedTypes.includes(type.value) ? "checked" : ""}
                class="w-4 h-4 text-accent-600 border-modern-300 rounded focus:ring-accent-500"
              />
              <span class="text-sm text-modern-700">${type.label}</span>
            </label>
          `,
            )
            .join("")}
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
    const response = await apiClient.get("/settings/timezone/info");

    const timezoneSelect = document.getElementById("site-timezone");
    if (timezoneSelect && response.success && response.data) {
      const timezones = response.data.common_timezones || {};

      // 轉換物件為陣列並排序
      const timezoneArray = Object.entries(timezones).map(([value, label]) => ({
        value,
        label,
      }));

      // 按標籤排序
      timezoneArray.sort((a, b) => a.label.localeCompare(b.label));

      // 生成選項
      timezoneSelect.innerHTML = timezoneArray
        .map((tz) => `<option value="${tz.value}">${tz.label}</option>`)
        .join("");

      // 載入當前時區設定
      const currentTimezone = await timezoneUtils.getSiteTimezone();
      timezoneSelect.value = currentTimezone;
    }

    // 更新當前網站時間
    updateCurrentTime();
    // 清除舊的 interval 以避免記憶體泄漏
    if (currentTimeIntervalId) {
      clearInterval(currentTimeIntervalId);
    }
    currentTimeIntervalId = setInterval(updateCurrentTime, 1000);
  } catch (error) {
    console.error("載入時區設定失敗:", error);
    notification.error("載入時區設定失敗");
  }
}

/**
 * 更新當前時間顯示
 */
function updateCurrentTime() {
  const timeElement = document.getElementById("current-site-time");
  if (timeElement) {
    const now = new Date();
    const timeString = now.toLocaleString("zh-TW", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
    });
    timeElement.textContent = timeString;
  }
}

function bindSettingsEvents() {
  const saveBtn = document.getElementById("save-btn");
  const resetBtn = document.getElementById("reset-btn");
  const timezoneSelect = document.getElementById("site-timezone");

  if (saveBtn) {
    saveBtn.addEventListener("click", async () => {
      await saveSettings();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      resetSettings();
    });
  }

  // 時區變更預覽
  if (timezoneSelect) {
    timezoneSelect.addEventListener("change", () => {
      updateCurrentTime();
    });
  }
}

/**
 * 重置設定
 */
function resetSettings() {
  const siteNameInput = document.getElementById("site-name");
  const siteDescInput = document.getElementById("site-description");
  const postsPerPageInput = document.getElementById("posts-per-page");
  const enableCommentsInput = document.getElementById("enable-comments");
  const enableRegistrationInput = document.getElementById(
    "enable-registration",
  );
  const maxUploadSizeInput = document.getElementById("max-upload-size");
  const maxAttachmentsInput = document.getElementById(
    "max-attachments-per-post",
  );
  const timezoneSelect = document.getElementById("site-timezone");

  if (siteNameInput)
    siteNameInput.value = originalSettings.site_name || "AlleyNote";
  if (siteDescInput)
    siteDescInput.value = originalSettings.site_description || "";
  if (postsPerPageInput)
    postsPerPageInput.value = originalSettings.posts_per_page || "20";
  if (enableCommentsInput)
    enableCommentsInput.checked =
      originalSettings.enable_comments === "1" ||
      originalSettings.enable_comments === true;
  if (enableRegistrationInput)
    enableRegistrationInput.checked =
      originalSettings.enable_registration === "1" ||
      originalSettings.enable_registration === true;

  if (maxUploadSizeInput) {
    const sizeInMB = Math.floor(
      parseInt(originalSettings.max_upload_size || "10485760") / 1048576,
    );
    maxUploadSizeInput.value = sizeInMB.toString();
  }

  if (maxAttachmentsInput) {
    maxAttachmentsInput.value =
      originalSettings.max_attachments_per_post || "10";
  }

  if (timezoneSelect && originalSettings.site_timezone) {
    timezoneSelect.value = originalSettings.site_timezone;
  }

  // 重置檔案類型
  const allowedTypes = originalSettings.allowed_file_types || [];
  const fileTypeCheckboxes = document.querySelectorAll(
    'input[name="file-type"]',
  );
  fileTypeCheckboxes.forEach((cb) => {
    cb.checked = allowedTypes.includes(cb.value);
  });

  // 重置頁腳描述編輯器
  if (footerDescriptionEditor) {
    footerDescriptionEditor.setData(
      originalSettings.footer_description ||
        "基於 Domain-Driven Design 的企業級公布欄系統",
    );
  }

  notification.info("設定已重置為原始值");
}

/**
 * 儲存設定
 */
async function saveSettings() {
  try {
    loading.show("儲存設定中...");

    const siteNameInput = document.getElementById("site-name");
    const siteDescInput = document.getElementById("site-description");
    const postsPerPageInput = document.getElementById("posts-per-page");
    const enableCommentsInput = document.getElementById("enable-comments");
    const enableRegistrationInput = document.getElementById(
      "enable-registration",
    );
    const maxUploadSizeInput = document.getElementById("max-upload-size");
    const maxAttachmentsInput = document.getElementById(
      "max-attachments-per-post",
    );
    const timezoneSelect = document.getElementById("site-timezone");
    const footerCopyrightInput = document.getElementById("footer-copyright");

    // 收集設定
    const settings = {};

    if (siteNameInput?.value) settings.site_name = siteNameInput.value;
    if (siteDescInput?.value !== undefined)
      settings.site_description = siteDescInput.value;
    if (postsPerPageInput?.value)
      settings.posts_per_page = postsPerPageInput.value;
    if (enableCommentsInput !== null)
      settings.enable_comments = enableCommentsInput.checked ? "1" : "0";
    if (enableRegistrationInput !== null)
      settings.enable_registration = enableRegistrationInput.checked
        ? "1"
        : "0";
    if (timezoneSelect?.value) settings.site_timezone = timezoneSelect.value;

    // Footer 設定
    if (footerCopyrightInput?.value !== undefined)
      settings.footer_copyright = footerCopyrightInput.value;

    // 從富文本編輯器獲取頁腳描述
    const footerDescription = getRichTextEditorContent(
      "footer-description-editor",
    );
    if (footerDescription !== null) {
      settings.footer_description = footerDescription;
    }

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
    const fileTypeCheckboxes = document.querySelectorAll(
      'input[name="file-type"]:checked',
    );
    const allowedFileTypes = Array.from(fileTypeCheckboxes).map(
      (cb) => cb.value,
    );
    if (allowedFileTypes.length > 0) {
      settings.allowed_file_types = JSON.stringify(allowedFileTypes);
    }

    // 批量更新設定
    await apiClient.put("/settings", settings);

    // 清除時區快取
    timezoneUtils.clearCache();

    // 更新原始設定
    originalSettings = { ...settings, allowed_file_types: allowedFileTypes };
    currentSettings = { ...settings, allowed_file_types: allowedFileTypes };

    loading.hide();
    notification.success("設定已儲存");
  } catch (error) {
    loading.hide();
    console.error("儲存設定失敗:", error);
    notification.error("儲存設定失敗：" + (error.message || "未知錯誤"));
  }
}
