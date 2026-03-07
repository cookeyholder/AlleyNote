import { router } from "../../utils/router.js";

/**
 * 渲染 404 頁面
 */
export function render404() {
  const app = document.getElementById("app");

  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center p-6 relative overflow-hidden">
      <!-- 品牌裝飾背景 -->
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-accent-100 rounded-full blur-3xl opacity-20"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-20"></div>

      <div class="text-center relative z-10 animate-fade-in">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-white border border-modern-200 rounded-3xl shadow-xl mb-10 rotate-12">
          <span class="text-4xl font-bold text-accent-600">?</span>
        </div>
        
        <h1 class="text-[10rem] font-bold text-modern-900 leading-none tracking-tighter mb-4 opacity-10 select-none">404</h1>
        
        <div class="-mt-24 mb-12">
          <h2 class="text-4xl font-bold text-modern-900 mb-4 tracking-tight">頁面已進入虛無邊界</h2>
          <p class="text-lg text-modern-500 max-w-md mx-auto leading-relaxed">
            抱歉，您所尋找的頁面可能已被移除、更改名稱，或是暫時無法使用。
          </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a 
            href="/" 
            data-navigo
            class="px-10 py-4 bg-modern-900 text-white font-bold rounded-2xl hover:bg-black shadow-xl shadow-modern-200 transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            返回系統首頁
          </a>
          <button 
            onclick="history.back()"
            class="px-10 py-4 bg-white border border-modern-200 text-modern-700 font-bold rounded-2xl hover:bg-modern-50 transition-all"
          >
            返回上一頁
          </button>
        </div>
      </div>
    </div>
  `;
}
