import { router } from "../../utils/router.js";
import { postsAPI } from "../../api/modules/posts.js";
import { loading } from "../../components/Loading.js";
import { timezoneUtils } from "../../utils/timezoneUtils.js";
import { apiClient } from "../../api/client.js";
import { globalGetters } from "../../store/globalStore.js";

let currentPage = 1;
let currentSearch = "";
let siteSettings = {
  site_name: "AlleyNote",
  site_description: "基於 DDD 架構的企業級應用程式",
  footer_copyright: "© 2024 AlleyNote. All rights reserved.",
  footer_description: "基於 Domain-Driven Design 的企業級公布欄系統",
};

/**
 * 渲染首頁
 */
export async function renderHome() {
  // 載入網站設定
  await loadSiteSettings();

  // 檢查使用者登入狀態
  const isAuthenticated = globalGetters.isAuthenticated();
  const user = globalGetters.getUser();
  const safeSiteName = String(siteSettings.site_name || "AlleyNote");
  const safeSiteDescription = String(siteSettings.site_description || "");
  const safeFooterCopyright = String(siteSettings.footer_copyright || "");
  const safeUsername = String(user?.username || "管理後台");

  const app = document.getElementById("app");

  app.innerHTML = `
    <div class="min-h-screen bg-modern-50">
      <!-- 導航列 -->
      <nav class="bg-white border-b border-modern-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-20 items-center">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-accent-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-accent-600/20">A</div>
              <a href="/" data-navigo class="text-2xl font-bold text-modern-900 hover:text-accent-600 transition-colors">
                <span id="site-name-nav"></span>
              </a>
            </div>
            <div class="flex items-center gap-6">
              <div class="relative hidden lg:block">
                <input
                  type="text"
                  id="home-search"
                  placeholder="探索感興趣的文章..."
                  class="w-72 px-4 py-2.5 pl-11 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
                />
                <span class="absolute left-4 top-3 text-modern-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
              </div>
              ${
                isAuthenticated
                  ? `
                <div class="flex items-center">
                  <a
                    href="/admin/dashboard"
                    data-navigo
                    class="flex items-center gap-2 px-5 py-2.5 bg-modern-900 text-white rounded-xl hover:bg-black transition-all shadow-lg shadow-modern-200"
                  >
                    <svg class="w-5 h-5 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span id="admin-username" class="hidden sm:inline font-bold"></span>
                    <span class="sm:hidden font-bold">後台</span>
                  </a>
                </div>
              `
                  : `
                <a
                  href="/login"
                  data-navigo
                  class="px-6 py-2.5 bg-accent-600 text-white font-bold rounded-xl hover:bg-accent-700 shadow-lg shadow-accent-600/20 transition-all"
                >
                  成員登入
                </a>
              `
              }
            </div>
          </div>
        </div>
      </nav>

      <!-- 主要內容 -->
      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Hero Section -->
        <div class="text-center mb-20 animate-fade-in">
          <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-accent-50 text-accent-700 rounded-full text-xs font-bold uppercase tracking-widest mb-6">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-500"></span>
            </span>
            企業級公布欄系統
          </div>
          <h2 class="text-5xl md:text-6xl font-bold text-modern-900 mb-6 tracking-tight">
            <span id="site-name-hero"></span>
          </h2>
          <p class="max-w-2xl mx-auto text-xl text-modern-500 leading-relaxed">
            <span id="site-description-hero"></span>
          </p>
        </div>

        <!-- 搜尋列 (手機版) -->
        <div class="mb-12 lg:hidden px-4">
          <div class="relative">
            <input
              type="text"
              id="home-search-mobile"
              placeholder="搜尋文章關鍵字..."
              class="w-full px-4 py-4 pl-12 bg-white border border-modern-200 rounded-2xl shadow-sm focus:ring-2 focus:ring-accent-500 outline-none transition-all"
            />
            <span class="absolute left-4 top-4.5 text-modern-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>
        </div>

        <!-- 最新文章標題 -->
        <div class="flex items-end justify-between mb-10 border-b border-modern-200 pb-6">
          <div>
            <h3 class="text-3xl font-bold text-modern-900">最新發布內容</h3>
            <p class="text-sm text-modern-400 mt-1 uppercase tracking-widest font-bold" id="posts-count"></p>
          </div>
          <div class="hidden md:flex items-center gap-2 text-accent-600 font-bold text-sm">
            向下探索
            <svg class="w-4 h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
          </div>
        </div>

        <!-- 文章列表 -->
        <div id="posts-container" class="min-h-[400px]">
          <div class="flex items-center justify-center py-24">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600"></div>
          </div>
        </div>

        <!-- 分頁 -->
        <div id="pagination-container" class="mt-12"></div>

        <!-- 特色卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-24">
          <div class="p-8 bg-white border border-modern-200 rounded-3xl shadow-sm hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 group">
            <div class="w-14 h-14 bg-accent-50 text-accent-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-accent-600 group-hover:text-white transition-colors">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-modern-900 mb-2 tracking-tight">現代化技術棧</h3>
            <p class="text-modern-500 leading-relaxed">採用最新 Vite 工具鏈與 Tailwind CSS 打造極致流暢的使用體驗。</p>
          </div>

          <div class="p-8 bg-white border border-modern-200 rounded-3xl shadow-sm hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 group">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-modern-900 mb-2 tracking-tight">軍規級安全性</h3>
            <p class="text-modern-500 leading-relaxed">內建 JWT 多重認證與嚴密的 XSS/CSRF 防護網，守護您的數據安全。</p>
          </div>

          <div class="p-8 bg-white border border-modern-200 rounded-3xl shadow-sm hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 group">
            <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <h3 class="text-xl font-bold text-modern-900 mb-2 tracking-tight">極致效能表現</h3>
            <p class="text-modern-500 leading-relaxed">優化的前端渲染與資源載入策略，確保在任何設備上都能秒速開啟。</p>
          </div>
        </div>
      </main>

      <!-- 頁腳 -->
      <footer class="bg-modern-900 text-white mt-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-accent-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
                <span id="site-name-footer" class="text-2xl font-bold tracking-tight"></span>
              </div>
              <div id="footer-description" class="text-modern-400 text-sm leading-relaxed max-w-md"></div>
            </div>
            <div class="text-left md:text-right">
              <p class="text-modern-500 text-xs font-bold uppercase tracking-widest mb-2">Copyright Control</p>
              <p id="footer-copyright" class="text-modern-300 text-sm"></p>
            </div>
          </div>
        </div>
      </footer>
    </div>
  `;

  // 載入文章
  await loadPosts();

  const siteNameNav = document.getElementById("site-name-nav");
  const siteNameHero = document.getElementById("site-name-hero");
  const siteNameFooter = document.getElementById("site-name-footer");
  const siteDescriptionHero = document.getElementById("site-description-hero");
  const footerCopyright = document.getElementById("footer-copyright");
  const adminUsername = document.getElementById("admin-username");

  if (siteNameNav) siteNameNav.textContent = safeSiteName;
  if (siteNameHero) siteNameHero.textContent = safeSiteName;
  if (siteNameFooter) siteNameFooter.textContent = safeSiteName;
  if (siteDescriptionHero)
    siteDescriptionHero.textContent = safeSiteDescription;
  if (footerCopyright) footerCopyright.textContent = safeFooterCopyright;
  if (adminUsername) adminUsername.textContent = safeUsername;

  // 設置頁腳描述（純文字，避免 XSS）
  const footerDescElement = document.getElementById("footer-description");
  if (footerDescElement && siteSettings.footer_description) {
    footerDescElement.textContent = String(siteSettings.footer_description);
  }

  // 綁定搜尋事件
  const searchInput = document.getElementById("home-search");
  const searchInputMobile = document.getElementById("home-search-mobile");

  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        currentSearch = searchInput.value;
        currentPage = 1;
        loadPosts();
      }
    });
  }

  if (searchInputMobile) {
    searchInputMobile.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
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
  const container = document.getElementById("posts-container");
  const countEl = document.getElementById("posts-count");

  try {
    const result = await postsAPI.list({
      status: "published",
      search: currentSearch,
      page: currentPage,
      per_page: 9,
      sort: "-created_at",
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
            ${currentSearch ? "找不到符合條件的文章" : "目前沒有文章"}
          </p>
          ${
            currentSearch
              ? `
            <button
              onclick="window.clearSearch()"
              class="mt-4 text-accent-600 hover:text-accent-700"
            >
              清除搜尋條件
            </button>
          `
              : ""
          }
        </div>
      `;

      // 清空分頁
      document.getElementById("pagination-container").innerHTML = "";
      return;
    }

    // 渲染文章卡片（使用 DOM API，避免注入風險）
    const postCards = await Promise.all(
      posts.map((post) => renderPostCard(post)),
    );
    container.innerHTML = "";
    const grid = document.createElement("div");
    grid.className = "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6";
    postCards.forEach((card) => {
      grid.appendChild(card);
    });
    container.appendChild(grid);

    // 渲染分頁
    renderPagination(pagination);
  } catch (error) {
    container.innerHTML = `
      <div class="text-center py-16">
        <div class="text-6xl mb-4">⚠️</div>
        <p id="home-load-error-message" class="text-xl text-red-600 mb-4"></p>
        <button
          onclick="location.reload()"
          class="btn-primary"
        >
          重新載入
        </button>
      </div>
    `;
    const errorMessageElement = document.getElementById(
      "home-load-error-message",
    );
    if (errorMessageElement) {
      const message = error instanceof Error ? error.message : "未知錯誤";
      errorMessageElement.textContent = `載入失敗：${message}`;
    }
  }
}

/**
 * 渲染文章卡片
 */
async function renderPostCard(post) {
  const excerpt = post.excerpt || extractExcerpt(post.content);
  // 優先使用 publish_date，若無則使用 created_at
  const dateString = post.publish_date || post.created_at;

  // 使用時區工具格式化時間
  const formattedDate = await timezoneUtils.utcToSiteTimezone(
    dateString,
    "datetime",
  );

  const article = document.createElement("article");
  article.className =
    "group bg-white border border-modern-200 rounded-3xl p-8 hover:shadow-xl hover:shadow-modern-200/50 transition-all duration-300 relative overflow-hidden";

  const postId = String(post.id ?? "");
  const postHref = `/posts/${encodeURIComponent(postId)}`;
  const postTitle = String(post.title ?? "");
  const postExcerpt = String(excerpt ?? "");
  const postDate = String(formattedDate.split(" ")[0] ?? "");
  const postAuthor = String(post.author?.username || post.author || "Guest");

  // 背景裝飾
  article.innerHTML = `
    <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
      <svg class="w-24 h-24 text-modern-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v5h5M7 8h5M7 12h10M7 16h10"/></svg>
    </div>

    <div class="relative z-10 flex flex-col h-full">
      <div class="flex items-center gap-2 mb-4" data-role="tag-container">
      </div>

      <h4 class="text-xl font-bold text-modern-900 mb-3 group-hover:text-accent-600 transition-colors line-clamp-2">
        <a data-role="title-link" data-navigo></a>
      </h4>

      <p class="text-modern-500 text-sm mb-8 line-clamp-3 leading-relaxed flex-1" data-role="excerpt"></p>

      <div class="pt-6 border-t border-modern-100 flex items-center justify-between mt-auto">
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-modern-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <time class="text-xs font-bold text-modern-500 tabular-nums" data-role="date"></time>
          </div>
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-modern-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-xs font-bold text-modern-500" data-role="author"></span>
          </div>
        </div>
        <a data-role="action-link" data-navigo class="w-8 h-8 rounded-full bg-modern-50 text-modern-400 flex items-center justify-center group-hover:bg-accent-600 group-hover:text-white transition-all shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  `;

  const tagContainer = article.querySelector('[data-role="tag-container"]');
  const titleLink = article.querySelector('[data-role="title-link"]');
  const excerptElement = article.querySelector('[data-role="excerpt"]');
  const dateElement = article.querySelector('[data-role="date"]');
  const authorElement = article.querySelector('[data-role="author"]');
  const actionLink = article.querySelector('[data-role="action-link"]');

  if (tagContainer) {
    const rawTags = Array.isArray(post.tags) ? post.tags.slice(0, 2) : [];
    const tags = rawTags.length > 0 ? rawTags : ["General"];

    tags.forEach((tag, index) => {
      const span = document.createElement("span");
      span.className =
        index < rawTags.length
          ? "px-2.5 py-1 bg-accent-50 text-accent-600 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-accent-100"
          : "px-2.5 py-1 bg-modern-50 text-modern-400 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-modern-100";
      span.textContent = String(tag ?? "");
      tagContainer.appendChild(span);
    });
  }

  if (titleLink) {
    titleLink.setAttribute("href", postHref);
    titleLink.textContent = postTitle;
  }

  if (excerptElement) {
    excerptElement.textContent = postExcerpt;
  }

  if (dateElement) {
    dateElement.textContent = postDate;
  }

  if (authorElement) {
    authorElement.textContent = postAuthor;
  }

  if (actionLink) {
    actionLink.setAttribute("href", postHref);
  }

  return article;
}

/**
 * 提取摘要
 */
function extractExcerpt(html, length = 150) {
  let text = "";

  if (typeof document !== "undefined") {
    const template = document.createElement("template");
    template.innerHTML = String(html ?? "");
    text = template.content.textContent ?? "";
  } else {
    text = String(html ?? "");
  }

  if (text.length <= length) {
    return text;
  }

  return text.substring(0, length) + "...";
}

/**
 * 渲染分頁
 */
function renderPagination(pagination) {
  const container = document.getElementById("pagination-container");

  if (!pagination || pagination.total_pages <= 1) {
    container.innerHTML = "";
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
    } else if (pages[pages.length - 1] !== "...") {
      pages.push("...");
    }
  }

  container.innerHTML = `
    <div class="flex items-center justify-center gap-2 p-2 bg-modern-100/50 rounded-2xl w-fit mx-auto shadow-inner">
      <button
        onclick="window.homeGoToPage(${current_page - 1})"
        ${current_page === 1 ? "disabled" : ""}
        class="px-4 py-2 text-sm font-bold text-modern-600 rounded-xl hover:bg-white hover:shadow-sm disabled:opacity-30 disabled:cursor-not-allowed transition-all"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>
      ${pages
        .map((page) => {
          if (page === "...") {
            return '<span class="px-3 text-modern-400 font-bold">...</span>';
          }
          return `
          <button
            onclick="window.homeGoToPage(${page})"
            class="w-10 h-10 flex items-center justify-center text-sm font-bold rounded-xl transition-all ${
              page === current_page
                ? "bg-accent-600 text-white shadow-lg shadow-accent-600/30"
                : "text-modern-500 hover:bg-white hover:shadow-sm"
            }"
          >
            ${page}
          </button>
        `;
        })
        .join("")}
      <button
        onclick="window.homeGoToPage(${current_page + 1})"
        ${current_page === total_pages ? "disabled" : ""}
        class="px-4 py-2 text-sm font-bold text-modern-600 rounded-xl hover:bg-white hover:shadow-sm disabled:opacity-30 disabled:cursor-not-allowed transition-all"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
  window.scrollTo({ top: 0, behavior: "smooth" });
};

/**
 * 清除搜尋
 */
window.clearSearch = function () {
  currentSearch = "";
  currentPage = 1;

  const searchInput = document.getElementById("home-search");
  const searchInputMobile = document.getElementById("home-search-mobile");

  if (searchInput) searchInput.value = "";
  if (searchInputMobile) searchInputMobile.value = "";

  loadPosts();
};

/**
 * 載入網站設定
 */
async function loadSiteSettings() {
  try {
    const response = await apiClient.get("/settings", { silent: true });
    const settingsData = response.data || {};

    // 提取設定值
    const extractValue = (item) => {
      return item && typeof item === "object" && "value" in item
        ? item.value
        : item;
    };

    siteSettings.site_name =
      extractValue(settingsData.site_name) || "AlleyNote";
    siteSettings.site_description =
      extractValue(settingsData.site_description) ||
      "基於 DDD 架構的企業級應用程式";
    siteSettings.footer_copyright =
      extractValue(settingsData.footer_copyright) ||
      "© 2024 AlleyNote. All rights reserved.";
    siteSettings.footer_description =
      extractValue(settingsData.footer_description) ||
      "基於 Domain-Driven Design 的企業級公布欄系統";
  } catch (error) {
    console.error("載入網站設定失敗:", error);
    // 使用預設值
  }
}
