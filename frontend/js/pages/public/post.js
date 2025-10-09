import { postsAPI } from '../../api/modules/posts.js';
import { router } from '../../utils/router.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';

// DOMPurify 從 CDN 全域載入
const DOMPurify = window.DOMPurify;

/**
 * 渲染文章內頁
 */
export async function renderPost(postId) {
  loading.show('載入文章中...');
  
  try {
    const post = await postsAPI.get(postId);
    loading.hide();
    
    // 淨化 HTML 內容
    const cleanContent = DOMPurify.sanitize(post.content, {
      ALLOWED_TAGS: [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'strong', 'em', 'u', 's',
        'a', 'img', 'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'div', 'span',
      ],
      ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'id', 'target', 'rel'],
    });
    
    const app = document.getElementById('app');
    app.innerHTML = `
      <div class="min-h-screen bg-modern-50">
        <!-- 導航列 -->
        <nav class="bg-white shadow-sm border-b border-modern-200">
          <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
              <div class="flex items-center">
                <a href="/" class="text-2xl font-bold text-accent-600">AlleyNote</a>
              </div>
              <div>
                <a href="/" class="text-modern-600 hover:text-modern-900">
                  ← 返回首頁
                </a>
              </div>
            </div>
          </div>
        </nav>
        
        <!-- 文章內容 -->
        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <!-- 文章標頭 -->
          <header class="mb-8">
            <h1 class="text-4xl font-bold text-modern-900 mb-4 animate-fade-in">
              ${post.title}
            </h1>
            
            <div class="flex items-center gap-6 text-sm text-modern-600">
              <div class="flex items-center gap-2">
                <span>👤</span>
                <span>${post.author?.name || '匿名'}</span>
              </div>
              <div class="flex items-center gap-2">
                <span>📅</span>
                <time datetime="${post.created_at}">
                  ${new Date(post.created_at).toLocaleDateString('zh-TW', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })}
                </time>
              </div>
              ${post.views ? `
                <div class="flex items-center gap-2">
                  <span>👁️</span>
                  <span>${post.views} 次瀏覽</span>
                </div>
              ` : ''}
            </div>
          </header>
          
          <!-- 文章內容 -->
          <div class="card animate-slide-up">
            <div class="prose prose-modern max-w-none">
              ${cleanContent}
            </div>
          </div>
          
          <!-- 標籤 -->
          ${post.tags && post.tags.length > 0 ? `
            <div class="mt-8 flex flex-wrap gap-2">
              ${post.tags.map(tag => `
                <span class="px-3 py-1 bg-accent-100 text-accent-700 rounded-full text-sm">
                  #${tag}
                </span>
              `).join('')}
            </div>
          ` : ''}
          
          <!-- 相關文章 -->
          <div class="mt-12">
            <h2 class="text-2xl font-bold text-modern-900 mb-6">相關文章</h2>
            <div id="related-posts" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="text-center py-8 text-modern-600">載入中...</div>
            </div>
          </div>
        </article>
        
        <!-- 頁腳 -->
        <footer class="bg-white border-t border-modern-200 mt-20">
          <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-modern-600">
              © 2024 AlleyNote. All rights reserved.
            </p>
          </div>
        </footer>
      </div>
    `;
    
    // 載入相關文章
    loadRelatedPosts(postId);
    
  } catch (error) {
    loading.hide();
    toast.error('載入文章失敗：' + error.message);
    router.navigate('/');
  }
}

/**
 * 載入相關文章
 */
async function loadRelatedPosts(currentPostId) {
  const container = document.getElementById('related-posts');
  
  try {
    const result = await postsAPI.list({ 
      status: 'published',
      per_page: 4,
      exclude: currentPostId,
    });
    
    const posts = result.data || [];
    
    if (posts.length === 0) {
      container.innerHTML = `
        <div class="col-span-2 text-center py-8 text-modern-600">
          目前沒有相關文章
        </div>
      `;
      return;
    }
    
    container.innerHTML = posts.slice(0, 2).map(post => `
      <a href="/posts/${post.id}" class="card card-hover block">
        <h3 class="text-lg font-semibold text-modern-900 mb-2">
          ${post.title}
        </h3>
        ${post.excerpt ? `
          <p class="text-modern-600 text-sm mb-4 line-clamp-2">
            ${post.excerpt}
          </p>
        ` : ''}
        <div class="flex items-center justify-between text-sm text-modern-500">
          <span>${new Date(post.created_at).toLocaleDateString('zh-TW')}</span>
          <span class="text-accent-600 hover:text-accent-700">閱讀更多 →</span>
        </div>
      </a>
    `).join('');
  } catch (error) {
    container.innerHTML = `
      <div class="col-span-2 text-center py-8 text-modern-600">
        載入相關文章失敗
      </div>
    `;
  }
}
