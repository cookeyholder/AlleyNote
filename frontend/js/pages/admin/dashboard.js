import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { globalGetters } from "../../store/globalStore.js";
import { router } from "../../utils/router.js";
import { apiClient } from "../../api/client.js";
import { loading } from "../../components/Loading.js";
import { timezoneUtils } from "../../utils/timezoneUtils.js";
import { escapeHtml } from "../../utils/security.js";

/**
 * 渲染儀表板頁面
 */
export async function renderDashboard() {
  // 確保隱藏載入指示器
  loading.hide();

  try {
    // 先顯示基本架構
    const content = `
    <div>
      <h1 class="text-3xl font-bold text-modern-900 mb-8">儀表板</h1>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="stats-cards">
        <div class="animate-pulse card h-32 bg-modern-50 border-none"></div>
        <div class="animate-pulse card h-32 bg-modern-50 border-none"></div>
        <div class="animate-pulse card h-32 bg-modern-50 border-none"></div>
        <div class="animate-pulse card h-32 bg-modern-50 border-none"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- 最近文章 -->
        <div class="lg:col-span-2 card shadow-sm border-modern-200">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-modern-900">最近發布的文章</h2>
            <a href="/admin/posts" data-navigo class="text-accent-600 hover:text-accent-700 text-sm font-bold flex items-center gap-1">
              查看全部
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
          </div>
          <div id="recent-posts" class="space-y-1">
            <!-- 載入中 -->
          </div>
        </div>

        <!-- 快速操作 -->
        <div class="space-y-6">
          <h2 class="text-xl font-bold text-modern-900">快速操作</h2>
          <div class="grid grid-cols-1 gap-4">
            <a href="/admin/posts/create" data-navigo class="group p-4 bg-white border border-modern-200 rounded-2xl hover:border-accent-600 hover:shadow-md transition-all duration-300">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-accent-50 text-accent-600 rounded-xl flex items-center justify-center group-hover:bg-accent-600 group-hover:text-white transition-colors duration-300">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                  <h3 class="font-bold text-modern-900">新增文章</h3>
                  <p class="text-xs text-modern-500">發布新的公告資訊</p>
                </div>
              </div>
            </a>

            <a href="/admin/posts" data-navigo class="group p-4 bg-white border border-modern-200 rounded-2xl hover:border-accent-600 hover:shadow-md transition-all duration-300">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-modern-50 text-modern-500 rounded-xl flex items-center justify-center group-hover:bg-modern-900 group-hover:text-white transition-colors duration-300">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v5h5M7 8h5M7 12h10M7 16h10"/></svg>
                </div>
                <div>
                  <h3 class="font-bold text-modern-900">文章管理</h3>
                  <p class="text-xs text-modern-500">編輯或刪除現有內容</p>
                </div>
              </div>
            </a>

            ${
              globalGetters.isAdmin()
                ? `
                const result = await apiClient.get("/posts", {
                  params: { page: 1, per_page: 100 },
                  silent: true,
                });
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-modern-50 text-modern-500 rounded-xl flex items-center justify-center group-hover:bg-modern-900 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                  </div>
                  <div>
                    <h3 class="font-bold text-modern-900">成員管理</h3>
                    <p class="text-xs text-modern-500">權限設定與帳號管理</p>
                  </div>
                </div>
              </a>
            `
                : ""
            }
          </div>
        </div>
      </div>
    </div>
  `;

    renderDashboardLayout(content, {
      title: "儀表板",
    });
    bindDashboardLayoutEvents();
    router.updatePageLinks();

    // 載入統計資料和最近文章
    await loadDashboardData();
  } catch (error) {
    console.error("渲染儀表板頁面失敗:", error);
    const app = document.getElementById("app");
    if (app) {
      app.innerHTML = `
        <div class="min-h-screen bg-modern-50 p-6">
          <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-modern-900 mb-4">儀表板</h1>
            <div class="bg-white border border-modern-200 rounded-2xl p-6">
              <p class="text-modern-700 mb-2">已登入，但儀表板完整內容暫時無法載入。</p>
              <p class="text-modern-500 text-sm">請重新整理頁面，或稍後再試。</p>
              <div class="mt-4">
                <a href="/admin/posts" data-navigo class="text-accent-600 hover:text-accent-700 font-medium">前往文章管理</a>
              </div>
            </div>
          </div>
        </div>
      `;
      router.updatePageLinks();
    }
  }
}

/**
 * 載入儀表板資料
 */
async function loadDashboardData() {
  try {
    // 載入文章列表以計算統計資料
    // 直接使用 apiClient 以避免模組緩存問題
    const result = await apiClient.get("/posts", {
      params: { page: 1, per_page: 100 },
      silent: true,
    });
    const posts = result.data || [];
    const total = result.pagination?.total || 0;

    // 計算統計資料
    const publishedCount = posts.filter((p) => p.status === "published").length;
    const draftCount = posts.filter((p) => p.status === "draft").length;
    const totalViews = posts.reduce(
      (sum, p) => sum + (parseInt(p.views) || 0),
      0,
    );

    // 更新統計卡片
    const statsContainer = document.getElementById("stats-cards");
    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="card bg-white border-modern-200 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-modern-500 uppercase tracking-wider">總文章數</h3>
            <div class="p-2 bg-accent-50 text-accent-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/></svg>
            </div>
          </div>
          <p class="text-3xl font-bold text-modern-900">${total}</p>
          <div class="flex items-center gap-1 mt-2 text-xs font-medium text-modern-500">
            <span class="text-accent-600">${publishedCount}</span> 已發布
          </div>
        </div>

        <div class="card bg-white border-modern-200 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-modern-500 uppercase tracking-wider">總瀏覽量</h3>
            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
          </div>
          <p class="text-3xl font-bold text-modern-900">${totalViews.toLocaleString()}</p>
          <p class="text-xs font-medium text-modern-500 mt-2">累計數據</p>
        </div>

        <div class="card bg-white border-modern-200 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-modern-500 uppercase tracking-wider">草稿數</h3>
            <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
          </div>
          <p class="text-3xl font-bold text-modern-900">${draftCount}</p>
          <p class="text-xs font-medium text-amber-600 mt-2">待處理</p>
        </div>

        <div class="card bg-white border-modern-200 shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-modern-500 uppercase tracking-wider">已發布</h3>
            <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
          </div>
          <p class="text-3xl font-bold text-modern-900">${publishedCount}</p>
          <p class="text-xs font-medium text-emerald-600 mt-2">線上中</p>
        </div>
      `;
    }

    // 更新最近文章列表（只顯示最近 5 篇，依照 publish_date 或 created_at 排序）
    const recentPosts = posts
      .sort((a, b) => {
        const dateA = new Date(a.publish_date || a.created_at);
        const dateB = new Date(b.publish_date || b.created_at);
        return dateB - dateA;
      })
      .slice(0, 5);

    const recentPostsContainer = document.getElementById("recent-posts");
    if (recentPostsContainer) {
      if (recentPosts.length === 0) {
        recentPostsContainer.innerHTML = `
          <div class="text-center py-8 text-modern-500">
            <p>尚無文章</p>
            <a href="/admin/posts/create" data-navigo class="text-accent-600 hover:text-accent-700 mt-2 inline-block">
              立即新增第一篇文章
            </a>
          </div>
        `;
      } else {
        // 先格式化所有時間
        const postsWithDates = await Promise.all(
          recentPosts.map(async (post) => {
            const dateString = post.publish_date || post.created_at;
            const formattedDateTime = await timezoneUtils.utcToSiteTimezone(
              dateString,
              "datetime",
            );
            return { ...post, formattedDateTime };
          }),
        );

        recentPostsContainer.innerHTML = postsWithDates
          .map((post, index) => {
            const statusClass =
              post.status === "published"
                ? "bg-green-100 text-green-700"
                : "bg-yellow-100 text-yellow-700";
            const statusText = post.status === "published" ? "已發布" : "草稿";
            const borderClass =
              index < recentPosts.length - 1
                ? "border-b border-modern-100"
                : "";

            return `
            <div class="flex items-center justify-between py-3 ${borderClass}">
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-modern-900 truncate">${escapeHtml(post.title)}</h3>
                <div class="flex items-center gap-2 mt-1">
                  <p class="text-sm text-modern-500">${post.formattedDateTime}</p>
                  ${post.author ? `<span class="text-sm text-modern-400">·</span><p class="text-sm text-modern-500">${post.author}</p>` : ""}
                </div>
              </div>
              <span class="ml-4 px-3 py-1 ${statusClass} text-sm rounded-full whitespace-nowrap">
                ${statusText}
              </span>
            </div>
          `;
          })
          .join("");
      }

      // 更新連結
      router.updatePageLinks();
    }
  } catch (error) {
    console.error("載入儀表板資料失敗:", error);

    // 顯示基本的統計卡片（不依賴 API）
    const statsContainer = document.getElementById("stats-cards");
    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總文章數</h3>
            <span class="text-2xl">📝</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>

        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">總瀏覽量</h3>
            <span class="text-2xl">👁️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>

        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">草稿數</h3>
            <span class="text-2xl">✏️</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-600 mt-2">資料載入中...</p>
        </div>

        <div class="card">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-modern-600">已發布</h3>
            <span class="text-2xl">✅</span>
          </div>
          <p class="text-3xl font-bold text-modern-900">--</p>
          <p class="text-sm text-modern-500 mt-2">資料載入中...</p>
        </div>
      `;
    }

    // 顯示最近文章區域的占位符
    const recentPostsContainer = document.getElementById("recent-posts");
    if (recentPostsContainer) {
      recentPostsContainer.innerHTML = `
        <div class="text-center py-8 text-modern-500">
          <p class="mb-2">暫時無法載入文章列表</p>
          <p class="text-sm">請嘗試<a href="/admin/posts" data-navigo class="text-accent-600 hover:text-accent-700">前往文章管理</a>頁面</p>
        </div>
      `;

      router.updatePageLinks();
    }
  }
}
