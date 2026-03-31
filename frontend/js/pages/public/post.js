import { postsAPI } from "../../api/modules/posts.js?v=20251014";
import { router } from "../../utils/router.js";
import { notification } from "../../utils/notification.js";
import { loading } from "../../components/Loading.js";
import { timezoneUtils } from "../../utils/timezoneUtils.js";
import { escapeHtml } from "../../utils/security.js";

// DOMPurify 從 CDN 全域載入
const DOMPurify = window.DOMPurify;

/**
 * 渲染文章內頁
 */
export async function renderPost(postId) {
  loading.show("載入文章中...");

  try {
    const response = await postsAPI.get(postId);
    const post = response.data; // 從響應中提取 data
    loading.hide();

    // 記錄文章瀏覽（非阻塞式調用）
    trackPostView(postId).catch((error) => {
      console.error("Failed to track view:", error);
    });

    // 格式化時間
    const dateString = post.publish_date || post.created_at;
    const formattedDate = await timezoneUtils.utcToSiteTimezone(
      dateString,
      "datetime",
    );
    const siteTimezone = await timezoneUtils.getSiteTimezone();

    // 格式化時區顯示名稱
    let timezoneDisplay = "";
    try {
      const timezoneInfo = await timezoneUtils.getTimezoneInfo();
      if (
        timezoneInfo &&
        timezoneInfo.common_timezones &&
        timezoneInfo.common_timezones[siteTimezone]
      ) {
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
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "p",
        "br",
        "strong",
        "em",
        "u",
        "s",
        "a",
        "img",
        "ul",
        "ol",
        "li",
        "blockquote",
        "pre",
        "code",
        "table",
        "thead",
        "tbody",
        "tr",
        "th",
        "td",
        "div",
        "span",
      ],
      ALLOWED_ATTR: [
        "href",
        "src",
        "alt",
        "title",
        "class",
        "id",
        "target",
        "rel",
      ],
    });

    const app = document.getElementById("app");
    app.innerHTML = `
      <div class="min-h-screen bg-modern-50">
        <!-- 導航列 -->
        <nav class="bg-white border-b border-modern-200 sticky top-0 z-30">
          <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-accent-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-accent-600/20">A</div>
                <a href="/" data-navigo class="text-2xl font-bold text-modern-900">AlleyNote</a>
              </div>
              <div>
                <a href="/" data-navigo class="flex items-center gap-2 px-4 py-2 bg-modern-50 text-modern-600 font-bold rounded-xl hover:bg-modern-100 transition-all">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                  返回首頁
                </a>
              </div>
            </div>
          </div>
        </nav>

        <!-- 文章內容 -->
        <article class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <!-- 文章標頭 -->
          <header class="mb-12">
            <div class="flex items-center gap-2 mb-6 animate-fade-in">
              ${
                post.tags && post.tags.length > 0
                  ? post.tags
                      .map(
                        (tag) => `
                  <span class="px-3 py-1 bg-accent-50 text-accent-600 rounded-lg text-xs font-bold uppercase tracking-widest border border-accent-100">${tag}</span>
                `,
                      )
                      .join("")
                  : '<span class="px-3 py-1 bg-modern-50 text-modern-400 rounded-lg text-xs font-bold uppercase tracking-widest border border-modern-100">General News</span>'
              }
            </div>

            <h1 class="text-4xl md:text-5xl font-bold text-modern-900 mb-8 tracking-tight leading-tight animate-fade-in">
              ${escapeHtml(post.title)}
            </h1>

            <div class="flex flex-wrap items-center gap-y-4 gap-x-8 text-sm font-bold text-modern-500 uppercase tracking-widest border-b border-modern-200 pb-8">
              <div class="flex items-center gap-2 text-accent-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>${post.author?.username || post.author?.name || "Anonymous"}</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-modern-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <time datetime="${dateString}" class="tabular-nums">${formattedDate}</time>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-modern-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h1.5a2.5 2.5 0 012.5 2.5v.5m-9-11a9 9 0 1118 0 9 9 0 01-18 0z"/></svg>
                <span title="${siteTimezone}">${timezoneDisplay}</span>
              </div>
              ${
                post.views
                  ? `
                <div class="flex items-center gap-2 text-blue-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span class="tabular-nums">${post.views.toLocaleString()} VIEWS</span>
                </div>
              `
                  : ""
              }
            </div>
          </header>

          <!-- 文章內容 -->
          <div class="card bg-white border border-modern-200 shadow-xl shadow-modern-200/50 p-8 md:p-12 animate-slide-up">
            <div class="prose prose-modern prose-lg max-w-none prose-headings:text-modern-900 prose-p:text-modern-700 prose-a:text-accent-600 hover:prose-a:text-accent-700 transition-all">
              ${cleanContent}
            </div>
          </div>

          <!-- 文章導航 -->
          <div class="mt-20">
            <div class="flex items-center gap-3 mb-8">
              <div class="w-1.5 h-6 bg-accent-600 rounded-full"></div>
              <h2 class="text-2xl font-bold text-modern-900">延展閱讀導航</h2>
            </div>
            <div id="post-navigation" class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="col-span-full flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent-600"></div>
              </div>
            </div>
          </div>
        </article>

        <!-- 頁腳 -->
        <footer class="bg-modern-900 text-white mt-32">
          <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <div class="flex flex-col items-center gap-6">
              <div class="w-12 h-12 bg-accent-600 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-accent-600/20">A</div>
              <div>
                <p class="text-modern-400 text-sm mb-2 uppercase tracking-widest font-bold">Official Communication Board</p>
                <p class="text-modern-300">© 2024 AlleyNote. All rights reserved.</p>
              </div>
            </div>
          </div>
        </footer>
      </div>
    `;

    // 載入上一篇/下一篇文章
    loadPostNavigation(postId, post);
  } catch (error) {
    loading.hide();
    notification.error("載入文章失敗：" + error.message);
    router.navigate("/");
  }
}

/**
 * 載入上一篇/下一篇文章
 */
async function loadPostNavigation(currentPostId, currentPost) {
  const container = document.getElementById("post-navigation");

  try {
    // 獲取所有已發布的文章，按發布日期排序
    const result = await postsAPI.list({
      status: "published",
      sort: "-publish_date,-created_at", // 優先使用 publish_date 排序
      per_page: 100, // 取得足夠的文章來找到上一篇和下一篇
    });

    const posts = result.data || [];

    // 找到當前文章的索引
    const currentIndex = posts.findIndex(
      (post) => post.id === parseInt(currentPostId),
    );

    if (currentIndex === -1) {
      container.innerHTML = `
        <div class="col-span-2 text-center py-8 text-modern-600">
          無法載入文章導航
        </div>
      `;
      return;
    }

    const prevPost =
      currentIndex < posts.length - 1 ? posts[currentIndex + 1] : null;
    const nextPost = currentIndex > 0 ? posts[currentIndex - 1] : null;

    // 格式化相關文章的日期
    let prevDate = "";
    let nextDate = "";
    if (prevPost) {
      prevDate = await timezoneUtils.utcToSiteTimezone(
        prevPost.publish_date || prevPost.created_at,
        "date",
      );
    }
    if (nextPost) {
      nextDate = await timezoneUtils.utcToSiteTimezone(
        nextPost.publish_date || nextPost.created_at,
        "date",
      );
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
      ${
        prevPost
          ? `
        <a href="/posts/${prevPost.id}" data-navigo class="group card bg-white border border-modern-200 p-8 rounded-3xl hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 relative overflow-hidden">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-modern-50 text-modern-400 rounded-xl flex items-center justify-center group-hover:bg-accent-600 group-hover:text-white transition-all shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">Previous Post</p>
              <h3 class="text-lg font-bold text-modern-900 group-hover:text-accent-600 transition-colors line-clamp-2 leading-snug">
                ${prevPost.title}
              </h3>
              <div class="mt-4 flex items-center gap-2 text-modern-400 font-bold text-[10px] tabular-nums">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                ${prevDate}
              </div>
            </div>
          </div>
        </a>
      `
          : `
        <div class="card bg-modern-50/50 border border-dashed border-modern-200 p-8 rounded-3xl flex flex-col items-center justify-center text-center opacity-60">
          <p class="text-xs font-bold text-modern-400 uppercase tracking-widest">No earlier articles</p>
        </div>
      `
      }

      ${
        nextPost
          ? `
        <a href="/posts/${nextPost.id}" data-navigo class="group card bg-white border border-modern-200 p-8 rounded-3xl hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 relative overflow-hidden text-right">
          <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-1">Next Post</p>
              <h3 class="text-lg font-bold text-modern-900 group-hover:text-accent-600 transition-colors line-clamp-2 leading-snug">
                ${nextPost.title}
              </h3>
              <div class="mt-4 flex items-center justify-end gap-2 text-modern-400 font-bold text-[10px] tabular-nums">
                ${nextDate}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
            </div>
            <div class="w-10 h-10 bg-modern-50 text-modern-400 rounded-xl flex items-center justify-center group-hover:bg-accent-600 group-hover:text-white transition-all shrink-0 order-last">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>
        </a>
      `
          : `
        <div class="card bg-modern-50/50 border border-dashed border-modern-200 p-8 rounded-3xl flex flex-col items-center justify-center text-center opacity-60">
          <p class="text-xs font-bold text-modern-400 uppercase tracking-widest">No newer articles</p>
        </div>
      `
      }
    `;
  } catch (error) {
    console.error("載入文章導航失敗:", error);
    container.innerHTML = `
      <div class="col-span-2 text-center py-8 text-modern-600">
        載入文章導航失敗
      </div>
    `;
  }
}

/**
 * 追蹤文章瀏覽
 * 非阻塞式調用，不影響頁面載入
 */
async function trackPostView(postId) {
  try {
    await postsAPI.recordView(postId);
  } catch (error) {
    // 靜默失敗，不影響使用者體驗
    console.error("Failed to track post view:", error);
  }
}
