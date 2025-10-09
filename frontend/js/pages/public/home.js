import { router } from '../../utils/router.js';
import { postsAPI } from '../../api/modules/posts.js';
import { loading } from '../../components/Loading.js';

let currentPage = 1;
let currentSearch = '';

/**
 * 渲染首頁
 */
export async function renderHome() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50">
      <!-- 導航列 -->
      <nav class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16 items-center">
            <div class="flex items-center">
              <h1 class="text-2xl font-bold text-accent-600">AlleyNote</h1>
            </div>
            <div class="flex items-center gap-4">
              <div class="relative hidden md:block">
                <input
                  type="text"
                  id="home-search"
                  placeholder="搜尋文章..."
                  class="w-64 px-4 py-2 pl-10 border border-modern-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-500"
                />
                <span class="absolute left-3 top-2.5 text-modern-400">🔍</span>
              </div>
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
        <!-- Hero Section -->
        <div class="text-center mb-12 animate-fade-in">
          <h2 class="text-4xl md:text-5xl font-bold text-modern-900 mb-4">
            現代化公布欄系統
          </h2>
          <p class="text-xl text-modern-600 mb-8">
            基於 DDD 架構的企業級應用程式
          </p>
        </div>
        
        <!-- 特色卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 animate-slide-up">
          <div class="card card-hover text-center">
            <div class="text-4xl mb-4">🚀</div>
            <h3 class="text-xl font-semibold mb-2">現代化技術</h3>
            <p class="text-modern-600">Vite + Tailwind CSS + JavaScript</p>
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
        
        <!-- 搜尋列 (手機版) -->
        <div class="mb-6 md:hidden">
          <div class="relative">
            <input
              type="text"
              id="home-search-mobile"
              placeholder="搜尋文章..."
              class="w-full px-4 py-3 pl-10 border border-modern-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-500"
            />
            <span class="absolute left-3 top-3.5 text-modern-400">🔍</span>
          </div>
        </div>
        
        <!-- 最新文章標題 -->
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-modern-900">最新文章</h3>
          <div class="text-sm text-modern-600" id="posts-count">
            <!-- 文章數量 -->
          </div>
        </div>
        
        <!-- 文章列表 -->
        <div id="posts-container">
          <div class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto"></div>
            <p class="mt-4 text-modern-600">載入中...</p>
          </div>
        </div>
        
        <!-- 分頁 -->
        <div id="pagination-container" class="mt-8"></div>
      </main>
      
      <!-- 頁腳 -->
      <footer class="bg-white border-t border-modern-200 mt-20">
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
  
  // 載入文章
  await loadPosts();
  
  // 綁定搜尋事件
  const searchInput = document.getElementById('home-search');
  const searchInputMobile = document.getElementById('home-search-mobile');
  
  if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        currentSearch = searchInput.value;
        currentPage = 1;
        loadPosts();
      }
    });
  }
  
  if (searchInputMobile) {
    searchInputMobile.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        currentSearch = searchInputMobile.value;
        currentPage = 1;
        loadPosts();
      }
    });
  }
}

/**
 * 載入文章列表
 */
async function loadPosts() {
  const container = document.getElementById('posts-container');
  const countEl = document.getElementById('posts-count');
  
  try {
    const result = await postsAPI.list({
      status: 'published',
      search: currentSearch,
      page: currentPage,
      per_page: 9,
      sort: '-created_at',
    });
    
    const posts = result.data || [];
    const pagination = result.pagination || {};
    
    // 更新文章數量
    if (countEl) {
      countEl.textContent = `共 ${pagination.total || 0} 篇文章`;
    }
    
    if (posts.length === 0) {
      container.innerHTML = `
        <div class="text-center py-16">
          <div class="text-6xl mb-4">📝</div>
          <p class="text-xl text-modern-600 mb-2">
            ${currentSearch ? '找不到符合條件的文章' : '目前沒有文章'}
          </p>
          ${currentSearch ? `
            <button 
              onclick="window.clearSearch()"
              class="mt-4 text-accent-600 hover:text-accent-700"
            >
              清除搜尋條件
            </button>
          ` : ''}
        </div>
      `;
      
      // 清空分頁
      document.getElementById('pagination-container').innerHTML = '';
      return;
    }
    
    // 渲染文章卡片
    container.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        ${posts.map((post) => renderPostCard(post)).join('')}
      </div>
    `;
    
    // 渲染分頁
    renderPagination(pagination);
    
  } catch (error) {
    container.innerHTML = `
      <div class="text-center py-16">
        <div class="text-6xl mb-4">⚠️</div>
        <p class="text-xl text-red-600 mb-4">載入失敗：${error.message}</p>
        <button 
          onclick="location.reload()"
          class="btn-primary"
        >
          重新載入
        </button>
      </div>
    `;
  }
}

/**
 * 渲染文章卡片
 */
function renderPostCard(post) {
  const excerpt = post.excerpt || extractExcerpt(post.content);
  const date = new Date(post.created_at).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
  
  return `
    <article class="card card-hover">
      <div class="h-2 bg-accent-500 rounded-t-lg -mt-8 -mx-8 mb-4"></div>
      <h4 class="text-lg font-semibold mb-2 text-modern-900 line-clamp-2">
        <a href="/posts/${post.id}" class="hover:text-accent-600 transition-colors">
          ${post.title}
        </a>
      </h4>
      <p class="text-modern-600 text-sm mb-4 line-clamp-3">
        ${excerpt}
      </p>
      <div class="flex items-center justify-between text-sm text-modern-500">
        <div class="flex items-center gap-2">
          <span>📅</span>
          <time datetime="${post.created_at}">${date}</time>
        </div>
        <div class="flex items-center gap-2">
          <span>👤</span>
          <span>${post.author?.name || '匿名'}</span>
        </div>
      </div>
      ${post.tags && post.tags.length > 0 ? `
        <div class="mt-4 flex flex-wrap gap-2">
          ${post.tags.slice(0, 3).map(tag => `
            <span class="px-2 py-1 bg-accent-100 text-accent-700 rounded text-xs">
              #${tag}
            </span>
          `).join('')}
        </div>
      ` : ''}
    </article>
  `;
}

/**
 * 提取摘要
 */
function extractExcerpt(html, length = 150) {
  // 移除 HTML 標籤
  const text = html.replace(/<[^>]*>/g, '');
  
  if (text.length <= length) {
    return text;
  }
  
  return text.substring(0, length) + '...';
}

/**
 * 渲染分頁
 */
function renderPagination(pagination) {
  const container = document.getElementById('pagination-container');
  
  if (!pagination || pagination.total_pages <= 1) {
    container.innerHTML = '';
    return;
  }
  
  const { page: current_page, total_pages } = pagination;
  const pages = [];
  
  // 生成頁碼
  for (let i = 1; i <= total_pages; i++) {
    if (
      i === 1 ||
      i === total_pages ||
      (i >= current_page - 2 && i <= current_page + 2)
    ) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...');
    }
  }
  
  container.innerHTML = `
    <div class="flex items-center justify-center gap-2">
      <button 
        onclick="window.homeGoToPage(${current_page - 1})"
        ${current_page === 1 ? 'disabled' : ''}
        class="px-4 py-2 border border-modern-300 rounded-lg hover:bg-modern-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        上一頁
      </button>
      ${pages.map(page => {
        if (page === '...') {
          return '<span class="px-4 py-2 text-modern-500">...</span>';
        }
        return `
          <button 
            onclick="window.homeGoToPage(${page})"
            class="px-4 py-2 border border-modern-300 rounded-lg hover:bg-modern-50 transition-colors ${
              page === current_page ? 'bg-accent-600 text-white border-accent-600' : ''
            }"
          >
            ${page}
          </button>
        `;
      }).join('')}
      <button 
        onclick="window.homeGoToPage(${current_page + 1})"
        ${current_page === total_pages ? 'disabled' : ''}
        class="px-4 py-2 border border-modern-300 rounded-lg hover:bg-modern-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        下一頁
      </button>
    </div>
  `;
}

/**
 * 跳轉到指定頁碼
 */
window.homeGoToPage = function (page) {
  currentPage = page;
  loadPosts();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

/**
 * 清除搜尋
 */
window.clearSearch = function () {
  currentSearch = '';
  currentPage = 1;
  
  const searchInput = document.getElementById('home-search');
  const searchInputMobile = document.getElementById('home-search-mobile');
  
  if (searchInput) searchInput.value = '';
  if (searchInputMobile) searchInputMobile.value = '';
  
  loadPosts();
};

