import { router } from '../../utils/router.js';

/**
 * 渲染 404 頁面
 */
export function render404() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center p-4">
      <div class="text-center">
        <h1 class="text-9xl font-bold text-accent-600 mb-4">404</h1>
        <h2 class="text-3xl font-semibold text-modern-900 mb-4">頁面不存在</h2>
        <p class="text-modern-600 mb-8">抱歉，您訪問的頁面找不到了。</p>
        <button 
          onclick="window.location.href='/'"
          class="btn-primary"
        >
          返回首頁
        </button>
      </div>
    </div>
  `;
}
