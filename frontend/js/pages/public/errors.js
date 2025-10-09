/**
 * 錯誤頁面模組
 * 包含 403, 404, 500 等錯誤頁面
 */

/**
 * 403 禁止訪問頁面
 */
export function render403() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center px-4">
      <div class="text-center animate-fade-in">
        <div class="mb-8">
          <div class="text-8xl mb-4">🚫</div>
          <h1 class="text-6xl font-bold text-modern-900 mb-4">403</h1>
        </div>
        <h2 class="text-2xl font-semibold text-modern-800 mb-4">
          禁止訪問
        </h2>
        <p class="text-lg text-modern-600 mb-8 max-w-md mx-auto">
          您沒有權限訪問此頁面，請聯繫系統管理員。
        </p>
        <div class="flex gap-4 justify-center">
          <button 
            onclick="window.history.back()"
            class="px-6 py-3 bg-modern-600 text-white rounded-lg hover:bg-modern-700 transition-colors"
          >
            返回上一頁
          </button>
          <a 
            href="/"
            data-navigo
            class="px-6 py-3 border-2 border-accent-600 text-accent-600 rounded-lg hover:bg-accent-50 transition-colors inline-block"
          >
            返回首頁
          </a>
        </div>
      </div>
    </div>
  `;
}

/**
 * 404 找不到頁面
 */
export function render404() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center px-4">
      <div class="text-center animate-fade-in">
        <div class="mb-8">
          <div class="text-8xl mb-4">🔍</div>
          <h1 class="text-6xl font-bold text-modern-900 mb-4">404</h1>
        </div>
        <h2 class="text-2xl font-semibold text-modern-800 mb-4">
          找不到頁面
        </h2>
        <p class="text-lg text-modern-600 mb-8 max-w-md mx-auto">
          抱歉，您訪問的頁面不存在或已被移除。
        </p>
        <div class="flex gap-4 justify-center">
          <button 
            onclick="window.history.back()"
            class="px-6 py-3 bg-modern-600 text-white rounded-lg hover:bg-modern-700 transition-colors"
          >
            返回上一頁
          </button>
          <a 
            href="/"
            data-navigo
            class="px-6 py-3 border-2 border-accent-600 text-accent-600 rounded-lg hover:bg-accent-50 transition-colors inline-block"
          >
            返回首頁
          </a>
        </div>
      </div>
    </div>
  `;
}

/**
 * 500 伺服器錯誤頁面
 */
export function render500() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center px-4">
      <div class="text-center animate-fade-in">
        <div class="mb-8">
          <div class="text-8xl mb-4">⚠️</div>
          <h1 class="text-6xl font-bold text-modern-900 mb-4">500</h1>
        </div>
        <h2 class="text-2xl font-semibold text-modern-800 mb-4">
          伺服器錯誤
        </h2>
        <p class="text-lg text-modern-600 mb-8 max-w-md mx-auto">
          伺服器發生錯誤，我們正在努力修復中，請稍後再試。
        </p>
        <div class="flex gap-4 justify-center">
          <button 
            onclick="window.location.reload()"
            class="px-6 py-3 bg-modern-600 text-white rounded-lg hover:bg-modern-700 transition-colors"
          >
            重新載入
          </button>
          <a 
            href="/"
            data-navigo
            class="px-6 py-3 border-2 border-accent-600 text-accent-600 rounded-lg hover:bg-accent-50 transition-colors inline-block"
          >
            返回首頁
          </a>
        </div>
      </div>
    </div>
  `;
}
