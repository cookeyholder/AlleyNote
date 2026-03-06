/**
 * 500 伺服器錯誤頁面
 */

export function render500(errorMessage = null) {
    const app = document.getElementById('app');

    app.innerHTML = `
        <div class="min-h-screen bg-modern-50 flex items-center justify-center p-6 relative overflow-hidden">
            <!-- 品牌裝飾背景 -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-rose-100 rounded-full blur-3xl opacity-20"></div>

            <div class="max-w-2xl w-full text-center relative z-10 animate-fade-in">
                <!-- 錯誤圖示 -->
                <div class="mb-10">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-white border border-modern-200 rounded-3xl shadow-xl mb-8 rotate-[8deg]">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </div>
                    <h1 class="text-[8rem] font-bold text-modern-900 leading-none tracking-tighter opacity-10 select-none">500</h1>
                    <div class="-mt-16">
                        <h2 class="text-4xl font-bold text-modern-900 mb-4 tracking-tight">系統連線中斷</h2>
                        <p class="text-lg text-modern-500 max-w-md mx-auto leading-relaxed">
                            伺服器目前無法處理您的請求，核心模組可能正在進行自動化維護。
                        </p>
                    </div>
                </div>

                <!-- 錯誤訊息卡片 -->
                <div class="bg-white rounded-3xl border border-modern-200 p-10 mb-10 shadow-2xl shadow-modern-200/50">
                    <div class="flex items-center justify-center gap-2 text-red-600 font-bold uppercase tracking-widest text-xs mb-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Critical System Error
                    </div>
                    
                    ${
                        errorMessage
                            ? `
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-6 mb-8 text-left relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-10">
                                <svg class="w-12 h-12 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </div>
                            <h3 class="font-bold text-red-900 mb-2 text-xs uppercase tracking-widest">Debug Info:</h3>
                            <code class="block text-sm text-red-700 break-all font-mono">${escapeHtml(errorMessage)}</code>
                        </div>
                    `
                            : ''
                    }
                    
                    <div class="text-left bg-modern-50 rounded-2xl p-6 mb-10 border border-modern-100">
                        <h3 class="font-bold text-modern-900 mb-4 text-xs uppercase tracking-widest">建議採取的恢復操作：</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-accent-600"></div>
                                <span class="text-sm font-medium text-modern-600">強制重新整理當前瀏覽頁面。</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-accent-600"></div>
                                <span class="text-sm font-medium text-modern-600">嘗試清理瀏覽器快取數據後重新載入。</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-accent-600"></div>
                                <span class="text-sm font-medium text-modern-600">檢查您的網路連線是否穩定。</span>
                            </li>
                        </ul>
                    </div>

                    <!-- 操作按鈕 -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button
                            onclick="window.location.reload()"
                            class="px-8 py-3 bg-accent-600 text-white rounded-xl hover:bg-accent-700 transition-all font-bold text-sm shadow-lg shadow-accent-600/20 flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            重新整理頁面
                        </button>
                        <a
                            href="/"
                            class="px-8 py-3 bg-modern-900 text-white rounded-xl hover:bg-black transition-all font-bold text-sm shadow-lg shadow-modern-200 flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            返回系統首頁
                        </a>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-4">
                    <p class="text-modern-400 text-sm font-medium">
                        問題持續發生？請聯繫技術維護小組：
                        <a href="mailto:support@alleynote.com" class="text-accent-600 hover:text-accent-700 font-bold transition-colors">
                            support@alleynote.com
                        </a>
                    </p>
                    <div class="px-4 py-1.5 bg-modern-100 rounded-full text-[10px] font-bold text-modern-500 uppercase tracking-widest tabular-nums">
                        Error Code: 500 | Timestamp: ${new Date().toISOString()}
                    </div>
                </div>
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
