import { postsAPI } from '../../api/modules/posts.js';
import { router } from '../../utils/router.js';
import { toast } from '../../utils/toast.js';
import { loading } from '../../components/Loading.js';
import { timezoneUtils } from '../../utils/timezoneUtils.js';

// DOMPurify 從 CDN 全域載入
const DOMPurify = window.DOMPurify;

/**
 * 渲染文章內頁
 */
export async function renderPost(postId) {
  loading.show('載入文章中...');
  
  try {
    const response = await postsAPI.get(postId);
    const post = response.data; // 從響應中提取 data
    loading.hide();
    
    // 格式化時間
    const dateString = post.publish_date || post.created_at;
    const formattedDate = await timezoneUtils.utcToSiteTimezone(dateString, 'datetime');
    const siteTimezone = await timezoneUtils.getSiteTimezone();
    
    // 格式化時區顯示名稱
    let timezoneDisplay = '';
    try {
      const timezoneInfo = await timezoneUtils.getTimezoneInfo();
      if (timezoneInfo && timezoneInfo.common_timezones && timezoneInfo.common_timezones[siteTimezone]) {
        timezoneDisplay = timezoneInfo.common_timezones[siteTimezone];
      } else {
        timezoneDisplay = siteTimezone;
      }
    } catch (e) {
      timezoneDisplay = siteTimezone;
    }
    
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
                <time datetime="${dateString}">
                  ${formattedDate}
                </time>
              </div>
              <div class="flex items-center gap-2">
                <span>🌏</span>
                <span title="${siteTimezone}">${timezoneDisplay}</span>
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
          
          <!-- 上一篇/下一篇 -->
          <div class="mt-12">
            <h2 class="text-2xl font-bold text-modern-900 mb-6">文章導航</h2>
            <div id="post-navigation" class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
    
    // 載入上一篇/下一篇文章
    loadPostNavigation(postId, post);
    
  } catch (error) {
    loading.hide();
    toast.error('載入文章失敗：' + error.message);
    router.navigate('/');
  }
}

/**
 * 載入上一篇/下一篇文章
 */
async function loadPostNavigation(currentPostId, currentPost) {
  const container = document.getElementById('post-navigation');
  
  try {
    // 獲取所有已發布的文章，按發布日期排序
    const result = await postsAPI.list({ 
      status: 'published',
      sort: '-publish_date,-created_at', // 優先使用 publish_date 排序
      per_page: 100, // 取得足夠的文章來找到上一篇和下一篇
    });
    
    const posts = result.data || [];
    
    // 找到當前文章的索引
    const currentIndex = posts.findIndex(post => post.id === parseInt(currentPostId));
    
    if (currentIndex === -1) {
      container.innerHTML = `
        <div class="col-span-2 text-center py-8 text-modern-600">
          無法載入文章導航
        </div>
      `;
      return;
    }
    
    const prevPost = currentIndex < posts.length - 1 ? posts[currentIndex + 1] : null;
    const nextPost = currentIndex > 0 ? posts[currentIndex - 1] : null;
    
    // 格式化相關文章的日期
    let prevDate = '';
    let nextDate = '';
    if (prevPost) {
      prevDate = await timezoneUtils.utcToSiteTimezone(prevPost.publish_date || prevPost.created_at, 'date');
    }
    if (nextPost) {
      nextDate = await timezoneUtils.utcToSiteTimezone(nextPost.publish_date || nextPost.created_at, 'date');
    }
    
    // 如果沒有上一篇和下一篇
    if (!prevPost && !nextPost) {
      container.innerHTML = `
        <div class="col-span-2 text-center py-8 text-modern-600">
          這是唯一的文章
        </div>
      `;
      return;
    }
    
    container.innerHTML = `
      ${prevPost ? `
        <a href="/posts/${prevPost.id}" class="card card-hover block group">
          <div class="flex items-start gap-3 mb-3">
            <span class="text-2xl">←</span>
            <div class="flex-1">
              <p class="text-sm text-modern-500 mb-1">上一篇</p>
              <h3 class="text-lg font-semibold text-modern-900 group-hover:text-accent-600 transition-colors line-clamp-2">
                ${prevPost.title}
              </h3>
            </div>
          </div>
          ${prevPost.excerpt ? `
            <p class="text-modern-600 text-sm line-clamp-2 ml-9">
              ${prevPost.excerpt}
            </p>
          ` : ''}
          <div class="mt-3 ml-9 text-sm text-modern-500">
            ${prevDate}
          </div>
        </a>
      ` : `
        <div class="card opacity-50">
          <div class="text-center py-8 text-modern-500">
            沒有更早的文章了
          </div>
        </div>
      `}
      
      ${nextPost ? `
        <a href="/posts/${nextPost.id}" class="card card-hover block group">
          <div class="flex items-start gap-3 mb-3">
            <div class="flex-1 text-right">
              <p class="text-sm text-modern-500 mb-1">下一篇</p>
              <h3 class="text-lg font-semibold text-modern-900 group-hover:text-accent-600 transition-colors line-clamp-2">
                ${nextPost.title}
              </h3>
            </div>
            <span class="text-2xl">→</span>
          </div>
          ${nextPost.excerpt ? `
            <p class="text-modern-600 text-sm line-clamp-2 mr-9 text-right">
              ${nextPost.excerpt}
            </p>
          ` : ''}
          <div class="mt-3 mr-9 text-sm text-modern-500 text-right">
            ${nextDate}
          </div>
        </a>
      ` : `
        <div class="card opacity-50">
          <div class="text-center py-8 text-modern-500">
            沒有更新的文章了
          </div>
        </div>
      `}
    `;
  } catch (error) {
    console.error('載入文章導航失敗:', error);
    container.innerHTML = `
      <div class="col-span-2 text-center py-8 text-modern-600">
        載入文章導航失敗
      </div>
    `;
  }
}
