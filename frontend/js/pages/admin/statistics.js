import {
  renderDashboardLayout,
  bindDashboardLayoutEvents,
} from "../../layouts/DashboardLayout.js";
import { statisticsAPI } from "../../api/modules/statistics.js";
import { apiClient } from "../../api/client.js";
import { notification } from "../../utils/notification.js";

/**
 * 系統統計頁面（使用 Chart.js）
 */
export default class StatisticsPage {
  constructor() {
    this.stats = null;
    this.loading = false;
    this.charts = {
      traffic: null,
      loginFailures: null,
    };
    this.timeRange = "week"; // day, week, month
  }

  async init() {
    await this.loadStatistics();
  }

  async loadStatistics() {
    try {
      this.loading = true;
      this.render();
      this.bindEvents();

      // 使用實際 API 取得統計資料
      const [overview, popularPosts, loginFailures, trafficData] =
        await Promise.all([
          this.loadOverviewFromAPI(),
          this.loadPopularPosts(),
          this.loadLoginFailures(),
          this.loadTrafficData(),
        ]);

      this.stats = {
        overview,
        popularPosts,
        loginFailures,
        trafficData,
      };

      this.loading = false;
      this.render();
      this.bindEvents();
      this.initCharts();
    } catch (error) {
      console.error("載入統計資料失敗:", error);
      notification.error("載入統計資料失敗");
      this.loading = false;
      this.render();
      this.bindEvents();
    }
  }

  async loadOverviewFromAPI() {
    // 計算時間範圍
    const endDate = new Date();
    const startDate = new Date();

    if (this.timeRange === "day") {
      startDate.setDate(startDate.getDate() - 1);
    } else if (this.timeRange === "week") {
      startDate.setDate(startDate.getDate() - 7);
    } else {
      startDate.setDate(startDate.getDate() - 30);
    }

    const params = new URLSearchParams({
      start_date: startDate.toISOString().split("T")[0],
      end_date: endDate.toISOString().split("T")[0],
    });

    try {
      const response = await apiClient.get(`/statistics/overview?${params}`, {
        silent: true,
      });

      if (response.success && response.data) {
        return response.data;
      }
    } catch (error) {
      console.warn("載入概覽統計失敗，使用預設值:", error);
    }

    return {
      total_posts: 0,
      active_users: 0,
      new_users: 0,
      total_views: 0,
    };
  }

  async loadPopularPosts() {
    // 計算時間範圍
    const endDate = new Date();
    const startDate = new Date();

    if (this.timeRange === "day") {
      startDate.setDate(startDate.getDate() - 1);
    } else if (this.timeRange === "week") {
      startDate.setDate(startDate.getDate() - 7);
    } else {
      startDate.setDate(startDate.getDate() - 30);
    }

    const params = new URLSearchParams({
      start_date: startDate.toISOString().split("T")[0],
      end_date: endDate.toISOString().split("T")[0],
      limit: "10",
    });

    const response = await apiClient.get(`/statistics/popular?${params}`, {
      silent: true,
    });

    if (response.success && Array.isArray(response.data)) {
      return response.data;
    }

    // 如果沒有資料，返回空陣列
    return [];
  }

  async loadLoginFailures() {
    // 計算時間範圍
    const endDate = new Date();
    const startDate = new Date();

    if (this.timeRange === "day") {
      startDate.setDate(startDate.getDate() - 1);
    } else if (this.timeRange === "week") {
      startDate.setDate(startDate.getDate() - 7);
    } else {
      startDate.setDate(startDate.getDate() - 30);
    }

    const params = new URLSearchParams({
      start_date: startDate.toISOString(),
      end_date: endDate.toISOString(),
      limit: "10",
    });

    try {
      const response = await apiClient.get(
        `/v1/activity-logs/login-failures?${params}`,
        { silent: true },
      );

      if (response.success && response.data) {
        return response.data;
      }

      // 如果沒有資料，返回空結果
      return { total: 0, accounts: [], trend: [] };
    } catch (error) {
      console.warn("載入登入失敗記錄失敗:", error);
      // 返回空結果，不中斷整個載入流程
      return { total: 0, accounts: [], trend: [] };
    }
  }

  async loadTrafficData() {
    // 計算時間範圍
    const endDate = new Date();
    const startDate = new Date();
    const days =
      this.timeRange === "day" ? 1 : this.timeRange === "week" ? 7 : 30;

    startDate.setDate(startDate.getDate() - days);

    const params = new URLSearchParams({
      start_date: startDate.toISOString().split("T")[0],
      end_date: endDate.toISOString().split("T")[0],
    });

    const response = await apiClient.get(
      `/statistics/charts/views/timeseries?${params}`,
      { silent: true },
    );

    if (response.success && Array.isArray(response.data)) {
      return response.data;
    }

    // 如果沒有資料，返回空陣列
    return [];
  }

  bindEvents() {
    // 時間範圍切換
    document.querySelectorAll(".time-range-btn").forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        const range = e.target.dataset.range;
        if (range !== this.timeRange) {
          this.timeRange = range;

          // 更新按鈕狀態
          document
            .querySelectorAll(".time-range-btn")
            .forEach((b) => b.classList.remove("active"));
          e.target.classList.add("active");

          await this.loadStatistics();
        }
      });
    });

    // 刷新按鈕 - 呼叫後端刷新 API
    const refreshBtn = document.getElementById("refresh-stats-btn");
    if (refreshBtn) {
      refreshBtn.addEventListener("click", async () => {
        try {
          // 顯示載入中
          refreshBtn.disabled = true;
          refreshBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            刷新中...
          `;

          // 呼叫後端刷新 API
          const response = await apiClient.post("/admin/statistics/refresh", {
            force_recalculate: true,
          });

          if (response.success) {
            // 重新載入統計資料
            await this.loadStatistics();
            notification.success("統計資料已刷新");
          } else {
            notification.error(response.error || "刷新統計資料失敗");
          }
        } catch (error) {
          console.error("刷新統計失敗:", error);
          notification.error(
            "刷新統計資料失敗：" + (error.message || "未知錯誤"),
          );
        } finally {
          // 恢復按鈕狀態
          refreshBtn.disabled = false;
          refreshBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            刷新
          `;
        }
      });
    }
  }

  render() {
    const content = this.loading ? this.renderLoading() : this.renderContent();
    renderDashboardLayout(content, { title: "系統統計" });
    bindDashboardLayoutEvents();
  }

  renderLoading() {
    return `
      <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto mb-4"></div>
          <p class="text-modern-600">載入統計資料中...</p>
        </div>
      </div>
    `;
  }

  renderContent() {
    if (!this.stats) {
      return `
        <div class="text-center py-24 bg-white rounded-3xl border border-modern-200">
          <div class="w-20 h-20 bg-modern-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-modern-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <p class="text-modern-500 font-bold">目前暫無可用的統計資料</p>
          <p class="text-sm text-modern-400 mt-2">請稍後再試，或嘗試點擊右上角的刷新按鈕</p>
        </div>
      `;
    }

    const overview = this.stats.overview || {};

    return `
      <div class="max-w-7xl mx-auto pb-12">
        <!-- 工具列 -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
          <div class="flex p-1 bg-modern-100 rounded-xl">
            <button data-range="day" class="time-range-btn px-6 py-2 rounded-lg text-sm font-bold transition-all ${this.timeRange === "day" ? "bg-white text-accent-700 shadow-sm active" : "text-modern-500 hover:text-modern-900"}">
              今日
            </button>
            <button data-range="week" class="time-range-btn px-6 py-2 rounded-lg text-sm font-bold transition-all ${this.timeRange === "week" ? "bg-white text-accent-700 shadow-sm active" : "text-modern-500 hover:text-modern-900"}">
              本週
            </button>
            <button data-range="month" class="time-range-btn px-6 py-2 rounded-lg text-sm font-bold transition-all ${this.timeRange === "month" ? "bg-white text-accent-700 shadow-sm active" : "text-modern-500 hover:text-modern-900"}">
              本月
            </button>
          </div>
          <button id="refresh-stats-btn" class="px-5 py-2.5 bg-modern-900 text-white rounded-xl text-sm font-bold hover:bg-black transition-all flex items-center gap-2 shadow-lg shadow-modern-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            更新數據
          </button>
        </div>

        <!-- 統計卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          ${this.renderStatCard("總文章數", overview.total_posts || 0, `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2z"/>`, "accent")}
          ${this.renderStatCard("活躍使用者", overview.active_users || 0, `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>`, "emerald")}
          ${this.renderStatCard("新註冊", overview.new_users || 0, `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>`, "amber")}
          ${this.renderStatCard("總瀏覽量", overview.total_views || 0, `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`, "blue")}
        </div>

        <!-- 流量趨勢圖 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8 mb-8">
          <div class="flex items-center gap-3 mb-8">
            <div class="w-1.5 h-6 bg-accent-600 rounded-full"></div>
            <h2 class="text-xl font-bold text-modern-900">全站流量趨勢分析</h2>
          </div>
          <div class="h-96">
            <canvas id="trafficChart"></canvas>
          </div>
        </div>

        <!-- 熱門文章與登入失敗 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- 熱門文章 -->
          <div class="card bg-white border-modern-200 shadow-sm p-8">
            <div class="flex items-center justify-between mb-8">
              <h2 class="text-xl font-bold text-modern-900">熱門文章 Top 10</h2>
              <div class="p-2 bg-accent-50 text-accent-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
              </div>
            </div>
            ${this.renderPopularPosts()}
          </div>

          <!-- 登入失敗統計 -->
          <div class="card bg-white border-modern-200 shadow-sm p-8">
            <div class="flex items-center justify-between mb-8">
              <h2 class="text-xl font-bold text-modern-900 text-red-900">異常登入統計</h2>
              <div class="p-2 bg-red-50 text-red-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              </div>
            </div>
            ${this.renderLoginFailures()}
          </div>
        </div>

        <!-- 登入失敗趨勢圖 -->
        <div class="card bg-white border-modern-200 shadow-sm p-8 mb-6">
          <div class="flex items-center gap-3 mb-8">
            <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
            <h2 class="text-xl font-bold text-modern-900">登入失敗趨勢</h2>
          </div>
          <div class="h-64">
            <canvas id="loginFailuresChart"></canvas>
          </div>
        </div>
      </div>
    `;
  }

  renderStatCard(title, value, iconPath, colorKey = "accent") {
    const colorConfigs = {
      accent: { bg: "bg-accent-50", text: "text-accent-600" },
      emerald: { bg: "bg-emerald-50", text: "text-emerald-600" },
      amber: { bg: "bg-amber-50", text: "text-amber-600" },
      blue: { bg: "bg-blue-50", text: "text-blue-600" },
    };

    const config = colorConfigs[colorKey] || colorConfigs.accent;

    return `
      <div class="card bg-white border-modern-200 shadow-sm hover:shadow-md transition-all p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-modern-500 uppercase tracking-widest mb-2">${title}</p>
            <p class="text-3xl font-bold text-modern-900">${value.toLocaleString()}</p>
          </div>
          <div class="w-12 h-12 ${config.bg} ${config.text} rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              ${iconPath}
            </svg>
          </div>
        </div>
      </div>
    `;
  }

  renderPopularPosts() {
    const posts = this.stats.popularPosts || [];

    if (posts.length === 0) {
      return '<p class="text-modern-500 text-center py-12">暫無熱門文章資料</p>';
    }

    return `
      <div class="space-y-1">
        ${posts
          .slice(0, 10)
          .map(
            (post, index) => `
          <div class="flex items-center justify-between p-3 rounded-xl hover:bg-modern-50 group transition-all">
            <div class="flex items-center gap-4 flex-1 min-w-0">
              <span class="flex items-center justify-center w-8 h-8 rounded-lg ${index < 3 ? "bg-accent-600 text-white" : "bg-modern-100 text-modern-500"} font-bold text-sm shrink-0">
                ${index + 1}
              </span>
              <div class="flex-1 min-w-0">
                <h3 class="font-bold text-modern-900 truncate group-hover:text-accent-700 transition-colors">${this.escapeHtml(post.title)}</h3>
              </div>
            </div>
            <div class="text-right ml-4">
              <p class="text-lg font-bold text-modern-900 tabular-nums">${post.views || 0}</p>
              <p class="text-[10px] font-bold text-modern-400 uppercase tracking-tighter">VIEWS</p>
            </div>
          </div>
        `,
          )
          .join("")}
      </div>
    `;
  }

  renderLoginFailures() {
    const failures = this.stats.loginFailures || { total: 0, accounts: [] };

    return `
      <div>
        <div class="mb-8 p-6 bg-red-50/50 border border-red-100 rounded-2xl flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-red-500 uppercase tracking-widest mb-1">總失敗次數</p>
            <p class="text-4xl font-bold text-red-900 tabular-nums">${failures.total || 0}</p>
          </div>
          <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-red-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
        </div>

        ${
          failures.accounts && failures.accounts.length > 0
            ? `
          <div>
            <div class="flex items-center gap-2 mb-4 px-2">
              <div class="w-1 h-4 bg-red-600 rounded-full"></div>
              <h3 class="text-sm font-bold text-modern-700">高風險帳號警告</h3>
            </div>
            <div class="space-y-1">
              ${failures.accounts
                .slice(0, 10)
                .map(
                  (account, index) => `
                <div class="flex items-center justify-between p-3 rounded-xl hover:bg-red-50 transition-all">
                  <div class="flex items-center gap-3">
                    <span class="text-modern-400 font-bold text-xs tabular-nums">${index + 1}.</span>
                    <span class="text-modern-900 font-bold">${this.escapeHtml(account.username || account.email || "未知")}</span>
                  </div>
                  <div class="px-3 py-1 bg-white border border-red-100 rounded-lg shadow-sm">
                    <span class="text-red-600 font-bold tabular-nums">${account.count || 0}</span>
                    <span class="text-[10px] text-red-400 font-bold uppercase ml-1">Hits</span>
                  </div>
                </div>
              `,
                )
                .join("")}
            </div>
          </div>
        `
            : `
          <div class="text-center py-12">
            <svg class="w-12 h-12 text-emerald-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-emerald-600 font-bold">目前無登入失敗記錄</p>
          </div>
        `
        }
      </div>
    `;
  }

  initCharts() {
    // 等待下一個事件循環，確保 DOM 已經渲染
    setTimeout(() => {
      // 銷毀舊圖表
      if (this.charts.traffic) {
        this.charts.traffic.destroy();
      }
      if (this.charts.loginFailures) {
        this.charts.loginFailures.destroy();
      }

      // 初始化流量趨勢圖
      this.initTrafficChart();

      // 初始化登入失敗趨勢圖
      this.initLoginFailuresChart();
    }, 100);
  }

  initTrafficChart() {
    const canvas = document.getElementById("trafficChart");
    if (!canvas) {
      console.error("找不到 trafficChart canvas 元素");
      return;
    }

    // 檢查 Chart.js 是否載入
    if (typeof Chart === "undefined") {
      console.error("Chart.js 尚未載入");
      return;
    }

    const ctx = canvas.getContext("2d");
    const trafficData = this.stats.trafficData || [];

    // 如果沒有資料，使用空資料集
    const labels = trafficData.length > 0 ? trafficData.map((d) => d.date) : [];
    const viewsData =
      trafficData.length > 0 ? trafficData.map((d) => d.views || 0) : [];
    const visitorsData =
      trafficData.length > 0 ? trafficData.map((d) => d.visitors || 0) : [];

    try {
      this.charts.traffic = new Chart(ctx, {
        type: "line",
        data: {
          labels: labels,
          datasets: [
            {
              label: "瀏覽量",
              data: viewsData,
              borderColor: "#3b82f6",
              backgroundColor: "rgba(59, 130, 246, 0.1)",
              tension: 0.4,
              fill: true,
            },
            {
              label: "訪客數",
              data: visitorsData,
              borderColor: "#10b981",
              backgroundColor: "rgba(16, 185, 129, 0.1)",
              tension: 0.4,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
            title: {
              display: trafficData.length === 0,
              text: trafficData.length === 0 ? "暫無流量資料" : "",
            },
            tooltip: {
              mode: "index",
              intersect: false,
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
          },
        },
      });
    } catch (error) {
      console.error("初始化流量趨勢圖失敗:", error);
    }
  }

  initLoginFailuresChart() {
    const canvas = document.getElementById("loginFailuresChart");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    const failures = this.stats.loginFailures || {};
    const trend = failures.trend || [];

    // 如果沒有趨勢資料，生成模擬資料
    const chartData =
      trend.length > 0 ? trend : this.generateMockFailureTrend();

    this.charts.loginFailures = new Chart(ctx, {
      type: "bar",
      data: {
        labels: chartData.map((d) => d.date),
        datasets: [
          {
            label: "登入失敗次數",
            data: chartData.map((d) => d.count),
            backgroundColor: "rgba(239, 68, 68, 0.5)",
            borderColor: "rgba(239, 68, 68, 1)",
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `失敗次數: ${context.parsed.y}`;
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
            },
          },
        },
      },
    });
  }

  generateMockFailureTrend() {
    const days =
      this.timeRange === "day" ? 24 : this.timeRange === "week" ? 7 : 30;
    const data = [];
    const now = new Date();

    for (let i = days - 1; i >= 0; i--) {
      const date = new Date(now);
      if (this.timeRange === "day") {
        date.setHours(date.getHours() - i);
        data.push({
          date: `${date.getHours()}:00`,
          count: Math.floor(Math.random() * 10),
        });
      } else {
        date.setDate(date.getDate() - i);
        data.push({
          date: date.toISOString().split("T")[0],
          count: Math.floor(Math.random() * 20),
        });
      }
    }

    return data;
  }

  escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text ? String(text).replace(/[&<>"']/g, (m) => map[m]) : "";
  }
}

/**
 * 渲染系統統計頁面（wrapper 函數）
 */
export async function renderStatistics() {
  const page = new StatisticsPage();
  await page.init();
}
