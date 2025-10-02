import { router } from '../router/index.js';

/**
 * 渲染首頁
 */
export function renderHome() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50">
      <!-- 導航列 -->
      <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
              <h1 class="text-2xl font-bold text-accent-600">AlleyNote</h1>
            </div>
            <div>
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
      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-modern-900 mb-4 animate-fade-in">
            現代化公布欄系統
          </h2>
          <p class="text-xl text-modern-600 animate-slide-up">
            基於 DDD 架構的企業級應用程式
          </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
          <div class="card card-hover text-center">
            <div class="text-4xl mb-4">🚀</div>
            <h3 class="text-xl font-semibold mb-2">現代化技術</h3>
            <p class="text-modern-600">Vite + Tailwind CSS + 原生 JavaScript</p>
          </div>
          
          <div class="card card-hover text-center">
            <div class="text-4xl mb-4">🛡️</div>
            <h3 class="text-xl font-semibold mb-2">安全可靠</h3>
            <p class="text-modern-600">JWT 認證 + XSS/CSRF 防護</p>
          </div>
          
          <div class="card card-hover text-center">
            <div class="text-4xl mb-4">⚡</div>
            <h3 class="text-xl font-semibold mb-2">高效能</h3>
            <p class="text-modern-600">快速載入 + 響應式設計</p>
          </div>
        </div>
        
        <div class="text-center">
          <h3 class="text-2xl font-bold text-modern-900 mb-8">最新文章</h3>
          <div id="posts-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="card card-hover">
              <div class="h-2 bg-accent-500 rounded-t-lg -mt-8 -mx-8 mb-4"></div>
              <h4 class="text-lg font-semibold mb-2">文章標題範例</h4>
              <p class="text-modern-600 text-sm mb-4">這是文章內容的簡短摘要...</p>
              <div class="flex items-center justify-between text-sm text-modern-500">
                <span>2024-10-03</span>
                <span>👤 Admin</span>
              </div>
            </div>
            
            <div class="card card-hover">
              <div class="h-2 bg-accent-500 rounded-t-lg -mt-8 -mx-8 mb-4"></div>
              <h4 class="text-lg font-semibold mb-2">另一篇文章</h4>
              <p class="text-modern-600 text-sm mb-4">更多精彩內容等您來探索...</p>
              <div class="flex items-center justify-between text-sm text-modern-500">
                <span>2024-10-02</span>
                <span>👤 Editor</span>
              </div>
            </div>
            
            <div class="card card-hover">
              <div class="h-2 bg-accent-500 rounded-t-lg -mt-8 -mx-8 mb-4"></div>
              <h4 class="text-lg font-semibold mb-2">技術分享</h4>
              <p class="text-modern-600 text-sm mb-4">DDD 架構實作心得...</p>
              <div class="flex items-center justify-between text-sm text-modern-500">
                <span>2024-10-01</span>
                <span>👤 Developer</span>
              </div>
            </div>
          </div>
        </div>
      </main>
      
      <!-- 頁腳 -->
      <footer class="bg-white border-t border-modern-200 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <p class="text-center text-modern-600">
            © 2024 AlleyNote. All rights reserved.
          </p>
        </div>
      </footer>
    </div>
  `;
}
