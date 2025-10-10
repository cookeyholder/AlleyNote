import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { statisticsAPI } from '../../api/modules/statistics.js';
import { toast } from '../../utils/toast.js';
import {
  Chart,
  LineController,
  BarController,
  DoughnutController,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

// 註冊 Chart.js 組件
Chart.register(
  LineController,
  BarController,
  DoughnutController,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

/**
 * 系統統計頁面
 */
export default class StatisticsPage {
  constructor() {
    this.overview = null;
    this.postsStats = null;
    this.popularPosts = null;
    this.loading = false;
    this.charts = {};
    this.dateRange = 'last_30_days';
  }

  async init() {
    await this.loadStatistics();
  }

  async loadStatistics() {
    try {
      this.loading = true;
      this.render();

      // 載入統計資料
      const [overview, postsStats, popular] = await Promise.all([
        statisticsAPI.getOverview({ period: this.dateRange }),
        statisticsAPI.getPostsStats({ period: this.dateRange }),
        statisticsAPI.getPopular({ period: this.dateRange, limit: 10 }),
      ]);

      this.overview = overview;
      this.postsStats = postsStats;
      this.popularPosts = popular?.data?.top_posts?.by_views || [];

      this.loading = false;
      this.render();
      this.initCharts();
    } catch (error) {
      console.error('載入統計資料失敗:', error);
      toast.error('載入統計資料失敗');
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = `
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
          <h1 class="text-3xl font-bold text-modern-900">系統統計</h1>
          
          <div class="flex items-center gap-3">
            <label for="dateRange" class="text-sm font-medium text-modern-700">時間範圍：</label>
            <select 
              id="dateRange" 
              class="px-4 py-2 rounded-lg border border-modern-300 focus:outline-none focus:ring-2 focus:ring-accent-500"
            >
              <option value="last_7_days" ${this.dateRange === 'last_7_days' ? 'selected' : ''}>最近 7 天</option>
              <option value="last_30_days" ${this.dateRange === 'last_30_days' ? 'selected' : ''}>最近 30 天</option>
              <option value="last_90_days" ${this.dateRange === 'last_90_days' ? 'selected' : ''}>最近 90 天</option>
              <option value="this_year" ${this.dateRange === 'this_year' ? 'selected' : ''}>今年</option>
            </select>
          </div>
        </div>

        ${this.loading ? this.renderLoading() : this.renderContent()}
      </div>
    `;

    const app = document.getElementById("app");
    renderDashboardLayout(content, { title: "系統統計" }); bindDashboardLayoutEvents();
    this.attachEventListeners();
  }

  renderLoading() {
    return `
      <div class="bg-white rounded-2xl border border-modern-200 p-8">
        <div class="text-center text-modern-500">
          <i class="fas fa-spinner fa-spin text-4xl mb-4"></i>
          <p>載入中...</p>
        </div>
      </div>
    `;
  }

  renderContent() {
    return `
      <!-- 概覽卡片 -->
      ${this.renderOverviewCards()}

      <!-- 圖表區域 -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 文章發布趨勢 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-6">
          <h3 class="text-lg font-semibold text-modern-900 mb-4">文章發布趨勢</h3>
          <canvas id="postsChart"></canvas>
        </div>

        <!-- 文章狀態分佈 -->
        <div class="bg-white rounded-2xl border border-modern-200 p-6">
          <h3 class="text-lg font-semibold text-modern-900 mb-4">文章狀態分佈</h3>
          <canvas id="statusChart"></canvas>
        </div>
      </div>

      <!-- 熱門文章排行榜 -->
      ${this.renderPopularPosts()}
    `;
  }

  renderOverviewCards() {
    if (!this.overview) return '';

    const stats = [
      {
        title: '總文章數',
        value: this.overview.posts?.total || 0,
        icon: '📝',
        color: 'blue',
        change: this.overview.posts?.growth || 0,
      },
      {
        title: '已發布',
        value: this.overview.posts?.published || 0,
        icon: '✅',
        color: 'green',
      },
      {
        title: '草稿',
        value: this.overview.posts?.draft || 0,
        icon: '📋',
        color: 'yellow',
      },
      {
        title: '總瀏覽數',
        value: this.overview.interactions?.views || 0,
        icon: '👁️',
        color: 'purple',
        change: this.overview.interactions?.views_growth || 0,
      },
    ];

    return `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        ${stats.map((stat) => this.renderStatCard(stat)).join('')}
      </div>
    `;
  }

  renderStatCard(stat) {
    const colors = {
      blue: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200' },
      green: { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-200' },
      yellow: { bg: 'bg-yellow-50', text: 'text-yellow-700', border: 'border-yellow-200' },
      purple: { bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-200' },
    };

    const color = colors[stat.color] || colors.blue;
    const hasChange = typeof stat.change !== 'undefined';
    const isPositive = stat.change > 0;

    return `
      <div class="bg-white rounded-2xl border ${color.border} p-6">
        <div class="flex items-center justify-between mb-4">
          <span class="text-3xl">${stat.icon}</span>
          ${hasChange ? `
            <span class="text-sm font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}">
              ${isPositive ? '↑' : '↓'} ${Math.abs(stat.change)}%
            </span>
          ` : ''}
        </div>
        <h3 class="text-sm font-medium text-modern-600 mb-1">${stat.title}</h3>
        <p class="text-3xl font-bold ${color.text}">${this.formatNumber(stat.value)}</p>
      </div>
    `;
  }

  renderPopularPosts() {
    if (!this.popularPosts || this.popularPosts.length === 0) {
      return `
        <div class="bg-white rounded-2xl border border-modern-200 p-8">
          <h3 class="text-lg font-semibold text-modern-900 mb-4">熱門文章排行榜</h3>
          <div class="text-center text-modern-500">
            <p>暫無資料</p>
          </div>
        </div>
      `;
    }

    return `
      <div class="bg-white rounded-2xl border border-modern-200 p-6">
        <h3 class="text-lg font-semibold text-modern-900 mb-4">熱門文章排行榜（依瀏覽數）</h3>
        <div class="space-y-3">
          ${this.popularPosts.map((post, index) => this.renderPopularPostItem(post, index)).join('')}
        </div>
      </div>
    `;
  }

  renderPopularPostItem(post, index) {
    const medals = ['🥇', '🥈', '🥉'];
    const medal = medals[index] || `${index + 1}.`;

    return `
      <div class="flex items-center justify-between p-4 rounded-lg hover:bg-modern-50 transition-colors">
        <div class="flex items-center gap-4 flex-1">
          <span class="text-2xl w-8 text-center">${medal}</span>
          <div class="flex-1">
            <h4 class="font-medium text-modern-900">${this.escapeHtml(post.title)}</h4>
            <p class="text-sm text-modern-500">作者：${this.escapeHtml(post.author_name || '未知')}</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-lg font-semibold text-accent-600">${this.formatNumber(post.views)}</p>
          <p class="text-xs text-modern-500">瀏覽次數</p>
        </div>
      </div>
    `;
  }

  initCharts() {
    // 銷毀舊圖表
    Object.values(this.charts).forEach((chart) => chart.destroy());
    this.charts = {};

    // 文章發布趨勢圖
    this.initPostsChart();

    // 文章狀態分佈圖
    this.initStatusChart();
  }

  initPostsChart() {
    const canvas = document.getElementById('postsChart');
    if (!canvas || !this.postsStats?.timeline) return;

    const timeline = this.postsStats.timeline;
    const labels = Object.keys(timeline);
    const data = Object.values(timeline);

    this.charts.postsChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: '文章發布數',
            data,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            mode: 'index',
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
  }

  initStatusChart() {
    const canvas = document.getElementById('statusChart');
    if (!canvas || !this.overview) return;

    const data = [
      this.overview.posts?.published || 0,
      this.overview.posts?.draft || 0,
      this.overview.posts?.archived || 0,
    ];

    this.charts.statusChart = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['已發布', '草稿', '已封存'],
        datasets: [
          {
            data,
            backgroundColor: [
              'rgba(34, 197, 94, 0.8)',
              'rgba(251, 191, 36, 0.8)',
              'rgba(156, 163, 175, 0.8)',
            ],
            borderColor: [
              'rgb(34, 197, 94)',
              'rgb(251, 191, 36)',
              'rgb(156, 163, 175)',
            ],
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
    });
  }

  attachEventListeners() {
    // 時間範圍選擇
    const dateRangeSelect = document.getElementById('dateRange');
    if (dateRangeSelect) {
      dateRangeSelect.addEventListener('change', async (e) => {
        this.dateRange = e.target.value;
        await this.loadStatistics();
      });
    }
  }

  // 工具函式
  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };
    return text ? String(text).replace(/[&<>"']/g, (m) => map[m]) : '';
  }

  formatNumber(num) {
    if (num >= 1000000) {
      return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
      return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
  }
}
