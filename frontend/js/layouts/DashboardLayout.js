/**
 * 管理後台布局組件
 */

import { globalGetters } from "../store/globalStore.js";
import { authAPI } from "../api/modules/auth.js";
import { notification } from "../utils/notification.js";
import { router } from "../utils/router.js";

/**
 * 渲染管理後台布局
 */
export function renderDashboardLayout(content, options = {}) {
  const { title = "管理後台", headerActions = "" } = options;

  const user = globalGetters.getUser();
  const app = document.getElementById("app");

  app.innerHTML = `
    <div class="flex h-screen bg-modern-50">
      <!-- 側邊欄 -->
      ${renderSidebar(user)}

      <!-- 主要內容區 -->
      <div class="flex-1 flex flex-col overflow-hidden main-content-with-sidebar">
        <!-- 頂部導航列 -->
        <header class="bg-white shadow-sm">
          <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
              <button
                id="sidebar-toggle"
                class="mr-4 lg:hidden text-modern-600 hover:text-modern-900"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
              <h1 class="text-2xl font-semibold text-modern-900">${title}</h1>
            </div>
            <div class="flex items-center gap-4">
              ${headerActions}
              <div class="relative">
                <button
                  id="user-menu-btn"
                  class="flex items-center gap-2 text-modern-700 hover:text-modern-900 transition-colors"
                >
                  <div class="w-8 h-8 bg-accent-600 text-white rounded-full flex items-center justify-center font-semibold">
                    ${user?.username ? user.username.charAt(0).toUpperCase() : "U"}
                  </div>
                  <span class="hidden md:inline">${user?.username || "使用者"}</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- 使用者選單 -->
                <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-modern-200 z-50">
                  <a href="/admin/profile" data-navigo class="block px-4 py-2 text-modern-700 hover:bg-modern-50 rounded-t-lg">
                    個人資料
                  </a>
                  <button
                    id="logout-btn"
                    class="w-full text-left px-4 py-2 text-modern-700 hover:bg-modern-50 rounded-b-lg"
                  >
                    登出
                  </button>
                </div>
              </div>
            </div>
          </div>
        </header>

        <!-- 主要內容 -->
        <main class="flex-1 overflow-y-auto p-6">
          ${content}
        </main>
      </div>
    </div>
  `;

  // 移除這裡的 bindDashboardEvents() 呼叫
  // 改為由頁面明確呼叫 bindDashboardLayoutEvents()
}

/**
 * 渲染側邊欄
 */
function renderSidebar(user) {
  const currentPath = window.location.pathname;

  const menuItems = [
    {
      path: "/admin/dashboard",
      label: "儀表板",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
    },
    {
      path: "/admin/posts",
      label: "文章管理",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3v5h5M7 8h5M7 12h10M7 16h10"/></svg>`,
    },
    {
      path: "/admin/users",
      label: "使用者管理",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`,
    },
    {
      path: "/admin/roles",
      label: "角色管理",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>`,
    },
    {
      path: "/admin/tags",
      label: "標籤管理",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>`,
    },
    {
      path: "/admin/statistics",
      label: "系統統計",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>`,
    },
    {
      path: "/admin/settings",
      label: "系統設定",
      icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
    },
  ];

  return `
    <aside id="sidebar" class="sidebar bg-white border-r border-modern-200">
      <div class="p-6">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-accent-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
          <div>
            <h2 class="text-lg font-bold text-modern-900 leading-tight">AlleyNote</h2>
            <p class="text-xs font-medium text-modern-500 tracking-wider uppercase">Admin System</p>
          </div>
        </div>
      </div>

      <nav class="flex-1 px-4 py-4 space-y-1">
        ${menuItems
          .map((item) => {
            const isActive = currentPath === item.path;
            return `
            <a
              href="${item.path}"
              data-navigo
              class="flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-xl group ${
                isActive
                  ? "bg-accent-50 text-accent-700"
                  : "text-modern-600 hover:bg-modern-50 hover:text-modern-900"
              }"
            >
              <span class="mr-3 ${isActive ? "text-accent-600" : "text-modern-400 group-hover:text-modern-600"}">
                ${item.icon}
              </span>
              ${item.label}
              ${isActive ? '<div class="ml-auto w-1.5 h-1.5 bg-accent-600 rounded-full"></div>' : ""}
            </a>
          `;
          })
          .join("")}

        <div class="pt-6 pb-2 px-4">
          <div class="h-px bg-modern-100"></div>
        </div>

        <a
          href="/"
          data-navigo
          class="flex items-center px-4 py-2.5 text-sm font-medium text-modern-500 hover:bg-modern-50 hover:text-modern-900 transition-all duration-200 rounded-xl group"
        >
          <span class="mr-3 text-modern-400 group-hover:text-modern-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
          </span>
          返回首頁
        </a>
      </nav>

      <div class="p-4 mt-auto">
        <div class="bg-modern-50 rounded-2xl p-4 border border-modern-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white border border-modern-200 rounded-xl flex items-center justify-center text-accent-600 font-bold shadow-sm">
              ${user?.username ? user.username.charAt(0).toUpperCase() : "U"}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-modern-900 truncate">${user?.username || "訪客"}</p>
              <p class="text-xs text-modern-500 truncate">${user?.email || "未登入"}</p>
            </div>
          </div>
        </div>
      </div>
    </aside>
  `;
}

/**
 * 綁定管理後台事件
 */
export function bindDashboardLayoutEvents() {
  bindDashboardEvents();
}

// 用於追蹤是否已綁定全域事件監聽器
let globalEventsAttached = false;

/**
 * 綁定管理後台事件（內部使用）
 */
function bindDashboardEvents() {
  // 側邊欄切換（手機版）
  const sidebarToggle = document.getElementById("sidebar-toggle");
  const sidebar = document.getElementById("sidebar");

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("open");
    });
  }

  // 使用者選單切換
  const userMenuBtn = document.getElementById("user-menu-btn");
  const userMenu = document.getElementById("user-menu");

  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      userMenu.classList.toggle("hidden");
    });
  }

  // 登出按鈕
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      try {
        await authAPI.logout();
        notification.success("已成功登出");
        router.navigate("/");
      } catch (error) {
        console.error("Logout error:", error);
        notification.error("登出失敗");
      }
    });
  }

  // 只綁定一次全域事件監聽器
  if (!globalEventsAttached) {
    // 點擊外部關閉選單（使用事件委派）
    document.addEventListener("click", (e) => {
      const userMenu = document.getElementById("user-menu");
      const userMenuBtn = document.getElementById("user-menu-btn");

      if (userMenu && !userMenu.classList.contains("hidden")) {
        // 檢查點擊是否在選單或按鈕內部
        const clickedInsideMenu = userMenu.contains(e.target);
        const clickedInsideButton =
          userMenuBtn && userMenuBtn.contains(e.target);

        if (!clickedInsideMenu && !clickedInsideButton) {
          userMenu.classList.add("hidden");
        }
      }
    });

    globalEventsAttached = true;
  }
}

/**
 * 公開頁面布局
 */
export function renderPublicLayout(content, options = {}) {
  const { showHeader = true, showFooter = true } = options;

  const app = document.getElementById("app");

  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex flex-col">
      ${showHeader ? renderPublicHeader() : ""}

      <main class="flex-1">
        ${content}
      </main>

      ${showFooter ? renderPublicFooter() : ""}
    </div>
  `;
}

/**
 * 渲染公開頁面標頭
 */
function renderPublicHeader() {
  return `
    <nav class="bg-white shadow-sm sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center">
            <a href="/" data-navigo class="text-2xl font-bold text-accent-600">
              AlleyNote
            </a>
          </div>
          <div class="flex items-center gap-4">
            <a
              href="/login"
              data-navigo
              class="btn-primary"
            >
              登入
            </a>
          </div>
        </div>
      </div>
    </nav>
  `;
}

/**
 * 渲染公開頁面頁腳
 */
function renderPublicFooter() {
  return `
    <footer class="bg-white border-t border-modern-200 mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <p class="text-modern-600 mb-2">
            © ${new Date().getFullYear()} AlleyNote. All rights reserved.
          </p>
          <p class="text-sm text-modern-500">
            基於 Domain-Driven Design 的企業級公布欄系統
          </p>
        </div>
      </div>
    </footer>
  `;
}
