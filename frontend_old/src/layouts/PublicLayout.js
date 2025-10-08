/**
 * 公開頁面佈局
 */
export function renderPublicLayout(content) {
  return `
    <div class="min-h-screen bg-modern-50 flex flex-col">
      <!-- 導航列 -->
      <nav class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
              <a href="/" class="text-2xl font-bold text-accent-600 hover:text-accent-700 transition-colors">
                AlleyNote
              </a>
            </div>
            <div class="flex items-center gap-4">
              <a 
                href="/" 
                class="text-modern-600 hover:text-modern-900 transition-colors hidden sm:block"
              >
                首頁
              </a>
              <button 
                onclick="window.location.href='/login'"
                class="btn-primary"
              >
                登入
              </button>
            </div>
          </div>
        </div>
      </nav>
      
      <!-- 主要內容 -->
      <main class="flex-1">
        ${content}
      </main>
      
      <!-- 頁腳 -->
      <footer class="bg-white border-t border-modern-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="text-center">
            <p class="text-modern-600 mb-2">
              © 2024 AlleyNote. All rights reserved.
            </p>
            <p class="text-sm text-modern-500">
              基於 Domain-Driven Design 的企業級公布欄系統
            </p>
          </div>
        </div>
      </footer>
    </div>
  `;
}
