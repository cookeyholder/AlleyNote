/**
 * 403 禁止訪問頁面
 */

export function render403() {
  const app = document.getElementById("app");

  app.innerHTML = `
        <div class="min-h-screen bg-modern-50 flex items-center justify-center p-6 relative overflow-hidden">
            <!-- 品牌裝飾背景 -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-20"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-amber-100 rounded-full blur-3xl opacity-20"></div>

            <div class="max-w-2xl w-full text-center relative z-10 animate-fade-in">
                <!-- 錯誤圖示 -->
                <div class="mb-10">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-white border border-modern-200 rounded-3xl shadow-xl mb-8 rotate-[-12deg]">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h1 class="text-[8rem] font-bold text-modern-900 leading-none tracking-tighter opacity-10 select-none">403</h1>
                    <div class="-mt-16">
                        <h2 class="text-4xl font-bold text-modern-900 mb-4 tracking-tight">存取權限受限</h2>
                        <p class="text-lg text-modern-500 max-w-md mx-auto leading-relaxed">
                            抱歉，您的帳戶目前不具備進入此區域的授權資格。
                        </p>
                    </div>
                </div>

                <!-- 錯誤訊息卡片 -->
                <div class="bg-white rounded-3xl border border-modern-200 p-10 mb-10 shadow-2xl shadow-modern-200/50">
                    <div class="flex items-center justify-center gap-2 text-red-600 font-bold uppercase tracking-widest text-xs mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Access Denied
                    </div>
                    
                    <div class="text-left bg-modern-50 rounded-2xl p-6 mb-8 border border-modern-100">
                        <h3 class="font-bold text-modern-900 mb-4 text-sm uppercase tracking-widest">可能的原因分析：</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-4 h-4 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                    <div class="w-1.5 h-1.5 rounded-full bg-red-600"></div>
                                </div>
                                <span class="text-sm font-medium text-modern-600 leading-relaxed">您的職能角色不包含此項資源的操作權限。</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-4 h-4 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                    <div class="w-1.5 h-1.5 rounded-full bg-red-600"></div>
                                </div>
                                <span class="text-sm font-medium text-modern-600 leading-relaxed">此功能僅限「系統超級管理員」進行維護與設定。</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1 w-4 h-4 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                    <div class="w-1.5 h-1.5 rounded-full bg-red-600"></div>
                                </div>
                                <span class="text-sm font-medium text-modern-600 leading-relaxed">系統認證狀態已失效，請嘗試重新進行身份驗證。</span>
                            </li>
                        </ul>
                    </div>

                    <!-- 操作按鈕 -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button
                            onclick="history.back()"
                            class="px-8 py-3 bg-modern-50 text-modern-700 rounded-xl hover:bg-modern-100 transition-all font-bold text-sm"
                        >
                            返回上一頁
                        </button>
                        <a
                            href="/"
                            class="px-8 py-3 bg-modern-900 text-white rounded-xl hover:bg-black transition-all font-bold text-sm shadow-lg shadow-modern-200"
                        >
                            返回系統首頁
                        </a>
                        <a
                            href="/login"
                            class="px-8 py-3 bg-accent-600 text-white rounded-xl hover:bg-accent-700 transition-all font-bold text-sm shadow-lg shadow-accent-600/20"
                        >
                            重新登入驗證
                        </a>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <p class="text-modern-400 text-sm font-medium">
                    如有疑問請洽系統技術支援：
                    <a href="mailto:support@alleynote.com" class="text-accent-600 hover:text-accent-700 font-bold transition-colors">
                        support@alleynote.com
                    </a>
                </p>
            </div>
        </div>
    `;
}
