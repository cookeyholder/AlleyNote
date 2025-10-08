/**
 * 500 伺服器錯誤頁面
 */

export function render500(errorMessage = null) {
    const app = document.getElementById('app');

    app.innerHTML = `
        <div class="min-h-screen bg-gradient-to-br from-red-50 to-pink-50 flex items-center justify-center px-4">
            <div class="max-w-2xl w-full text-center">
                <!-- 錯誤圖示 -->
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-red-100 rounded-full mb-6 animate-pulse">
                        <i class="fas fa-server text-6xl text-red-500"></i>
                    </div>
                    <h1 class="text-8xl font-bold text-red-600 mb-4">500</h1>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        伺服器錯誤
                    </h2>
                </div>

                <!-- 錯誤訊息 -->
                <div class="bg-white rounded-2xl border border-red-200 p-8 mb-8 shadow-xl">
                    <p class="text-xl text-gray-700 mb-4">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        伺服器發生了一些問題
                    </p>
                    <p class="text-gray-600 mb-6">
                        我們正在努力修復這個問題。請稍後再試，或聯繫系統管理員。
                    </p>
                    
                    ${
                        errorMessage
                            ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-left">
                            <h3 class="font-semibold text-red-900 mb-2">
                                <i class="fas fa-bug text-red-500 mr-2"></i>
                                錯誤詳情：
                            </h3>
                            <code class="text-sm text-red-800 break-all">${escapeHtml(errorMessage)}</code>
                        </div>
                    `
                            : ''
                    }
                    
                    <!-- 建議操作 -->
                    <div class="text-left bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                            您可以嘗試：
                        </h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>重新整理頁面</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>清除瀏覽器快取後再試</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>稍後再訪問此頁面</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>聯繫技術支援團隊</span>
                            </li>
                        </ul>
                    </div>

                    <!-- 操作按鈕 -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button
                            onclick="window.location.reload()"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                        >
                            <i class="fas fa-sync-alt mr-2"></i>
                            重新整理頁面
                        </button>
                        <button
                            onclick="history.back()"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            返回上一頁
                        </button>
                        <a
                            href="/"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium inline-block"
                        >
                            <i class="fas fa-home mr-2"></i>
                            返回首頁
                        </a>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <p class="text-gray-600">
                    <i class="fas fa-headset mr-2"></i>
                    問題持續發生？請聯絡
                    <a href="mailto:support@alleynote.com" class="text-blue-600 hover:text-blue-700 font-medium">
                        技術支援團隊
                    </a>
                </p>

                <!-- 錯誤代碼 -->
                <p class="text-gray-500 text-sm mt-4">
                    錯誤代碼：500 | 時間：${new Date().toLocaleString('zh-TW')}
                </p>
            </div>
        </div>
    `;
}

/**
 * 轉義 HTML 字元
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
