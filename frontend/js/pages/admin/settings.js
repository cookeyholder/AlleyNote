/**
 * 系統設定頁面
 * 僅主管理員可訪問
 */
import { globalStore } from '../../store/globalStore.js';
import { toast } from '../../utils/toast.js';
import { apiClient } from '../../api/client.js';
import { loading } from '../../components/Loading.js';

export async function renderSettingsPage() {
    const container = document.getElementById('app-content');

    // 檢查權限
    const currentUser = globalStore.getState('currentUser');
    if (currentUser.role !== 'super_admin') {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center min-h-screen">
                <i class="fas fa-lock text-6xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">權限不足</h2>
                <p class="text-gray-600">您沒有權限訪問此頁面</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="p-6">
            <!-- 頁面標題 -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-cog text-gray-600 mr-2"></i>
                    系統設定
                </h1>
                <p class="text-gray-600">管理網站全域設定與系統參數</p>
            </div>

            <!-- 設定表單 -->
            <div class="max-w-4xl">
                <form id="settings-form" class="space-y-6">
                    <!-- 基本設定 -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-globe text-blue-500 mr-2"></i>
                            基本設定
                        </h2>

                        <div class="space-y-4">
                            <!-- 網站標題 -->
                            <div>
                                <label for="site-title" class="block text-sm font-medium text-gray-700 mb-2">
                                    網站標題
                                </label>
                                <input
                                    type="text"
                                    id="site-title"
                                    name="site_title"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="輸入網站標題"
                                >
                            </div>

                            <!-- 網站描述 -->
                            <div>
                                <label for="site-description" class="block text-sm font-medium text-gray-700 mb-2">
                                    網站描述
                                </label>
                                <textarea
                                    id="site-description"
                                    name="site_description"
                                    rows="3"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="輸入網站描述"
                                ></textarea>
                            </div>

                            <!-- 網站關鍵字 -->
                            <div>
                                <label for="site-keywords" class="block text-sm font-medium text-gray-700 mb-2">
                                    網站關鍵字
                                </label>
                                <input
                                    type="text"
                                    id="site-keywords"
                                    name="site_keywords"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="輸入關鍵字，以逗號分隔"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 外觀設定 -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-palette text-purple-500 mr-2"></i>
                            外觀設定
                        </h2>

                        <div class="space-y-4">
                            <!-- Logo 上傳 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    網站 Logo
                                </label>
                                <div class="flex items-center gap-4">
                                    <div id="logo-preview" class="w-32 h-32 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border border-gray-200">
                                        <i class="fas fa-image text-4xl text-gray-400"></i>
                                    </div>
                                    <div class="flex-1">
                                        <input
                                            type="file"
                                            id="logo-input"
                                            accept="image/*"
                                            class="hidden"
                                        >
                                        <button
                                            type="button"
                                            id="upload-logo-btn"
                                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            <i class="fas fa-upload mr-2"></i>
                                            上傳 Logo
                                        </button>
                                        <p class="text-sm text-gray-500 mt-2">
                                            建議尺寸：200x200 像素，格式：PNG, JPG, SVG
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 主題色彩 -->
                            <div>
                                <label for="theme-color" class="block text-sm font-medium text-gray-700 mb-2">
                                    主題色彩
                                </label>
                                <input
                                    type="color"
                                    id="theme-color"
                                    name="theme_color"
                                    class="h-10 w-20 border border-gray-300 rounded-lg cursor-pointer"
                                    value="#3b82f6"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 功能設定 -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-toggle-on text-green-500 mr-2"></i>
                            功能設定
                        </h2>

                        <div class="space-y-4">
                            <!-- 維護模式 -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h3 class="font-medium text-gray-900">維護模式</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        啟用後，訪客將無法訪問網站，僅管理員可登入
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        id="maintenance-mode"
                                        name="maintenance_mode"
                                        class="sr-only peer"
                                    >
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- 允許註冊 -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h3 class="font-medium text-gray-900">允許使用者註冊</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        啟用後，訪客可以註冊新帳號
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        id="allow-registration"
                                        name="allow_registration"
                                        class="sr-only peer"
                                    >
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- 允許留言 -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h3 class="font-medium text-gray-900">允許文章留言</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        啟用後，訪客可以在文章下方留言
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        id="allow-comments"
                                        name="allow_comments"
                                        class="sr-only peer"
                                    >
                                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 內容設定 -->
                    <div class="bg-white rounded-2xl border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-file-alt text-orange-500 mr-2"></i>
                            內容設定
                        </h2>

                        <div class="space-y-4">
                            <!-- 每頁文章數 -->
                            <div>
                                <label for="posts-per-page" class="block text-sm font-medium text-gray-700 mb-2">
                                    每頁文章數
                                </label>
                                <input
                                    type="number"
                                    id="posts-per-page"
                                    name="posts_per_page"
                                    min="5"
                                    max="50"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    value="10"
                                >
                            </div>

                            <!-- 摘要長度 -->
                            <div>
                                <label for="excerpt-length" class="block text-sm font-medium text-gray-700 mb-2">
                                    文章摘要長度（字元）
                                </label>
                                <input
                                    type="number"
                                    id="excerpt-length"
                                    name="excerpt_length"
                                    min="50"
                                    max="500"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    value="200"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 儲存按鈕 -->
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            id="reset-btn"
                            class="px-6 py-2.5 text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <i class="fas fa-undo mr-2"></i>
                            重置
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <i class="fas fa-save mr-2"></i>
                            儲存設定
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // 載入設定資料
    await loadSettings();

    // 綁定事件
    bindSettingsEvents();
}

/**
 * 載入設定資料
 */
async function loadSettings() {
    try {
        loading.show();

        // 這裡應該從後端 API 載入設定
        // 目前先使用模擬資料
        const settings = {
            site_title: 'AlleyNote',
            site_description: '一個簡潔優雅的部落格系統',
            site_keywords: '部落格,技術文章,分享',
            theme_color: '#3b82f6',
            maintenance_mode: false,
            allow_registration: false,
            allow_comments: true,
            posts_per_page: 10,
            excerpt_length: 200,
        };

        // 填充表單
        fillSettingsForm(settings);
    } catch (error) {
        console.error('載入設定失敗:', error);
        toast.error('載入設定失敗');
    } finally {
        loading.hide();
    }
}

/**
 * 填充設定表單
 */
function fillSettingsForm(settings) {
    const form = document.getElementById('settings-form');
    if (!form) return;

    // 填充文字欄位
    Object.keys(settings).forEach((key) => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = settings[key];
            } else {
                input.value = settings[key];
            }
        }
    });
}

/**
 * 綁定設定頁面事件
 */
function bindSettingsEvents() {
    const form = document.getElementById('settings-form');
    const uploadLogoBtn = document.getElementById('upload-logo-btn');
    const logoInput = document.getElementById('logo-input');
    const resetBtn = document.getElementById('reset-btn');

    // 表單提交
    form?.addEventListener('submit', handleSettingsSubmit);

    // Logo 上傳
    uploadLogoBtn?.addEventListener('click', () => {
        logoInput?.click();
    });

    logoInput?.addEventListener('change', handleLogoUpload);

    // 重置
    resetBtn?.addEventListener('click', () => {
        loadSettings();
        toast.info('已重置為原始設定');
    });
}

/**
 * 處理設定表單提交
 */
async function handleSettingsSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // 轉換為物件
    const settings = {};
    for (const [key, value] of formData.entries()) {
        const input = form.querySelector(`[name="${key}"]`);
        if (input?.type === 'checkbox') {
            settings[key] = input.checked;
        } else {
            settings[key] = value;
        }
    }

    try {
        loading.show();

        // 這裡應該呼叫後端 API 儲存設定
        // await apiClient.put('/api/settings', settings);

        // 模擬 API 呼叫
        await new Promise((resolve) => setTimeout(resolve, 1000));

        toast.success('設定已儲存');
    } catch (error) {
        console.error('儲存設定失敗:', error);
        toast.error('儲存設定失敗');
    } finally {
        loading.hide();
    }
}

/**
 * 處理 Logo 上傳
 */
async function handleLogoUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    // 驗證檔案
    if (!file.type.startsWith('image/')) {
        toast.error('請上傳圖片檔案');
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        toast.error('圖片大小不能超過 2MB');
        return;
    }

    try {
        loading.show();

        // 預覽圖片
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('logo-preview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-contain" alt="Logo">`;
            }
        };
        reader.readAsDataURL(file);

        // 這裡應該上傳到後端
        // const response = await apiClient.post('/api/settings/logo', { file });

        toast.success('Logo 上傳成功');
    } catch (error) {
        console.error('上傳 Logo 失敗:', error);
        toast.error('上傳 Logo 失敗');
    } finally {
        loading.hide();
    }
}
