import { renderDashboardLayout, bindDashboardLayoutEvents } from '../../layouts/DashboardLayout.js';
import { statisticsAPI } from '../../api/modules/statistics.js';
import { toast } from '../../utils/toast.js';

/**
 * 系統統計頁面（簡化版，不使用 Chart.js）
 */
export default class StatisticsPage {
  constructor() {
    this.stats = null;
    this.loading = false;
  }

  async init() {
    await this.loadStatistics();
  }

  async loadStatistics() {
    try {
      this.loading = true;
      this.render();

      // 載入統計資料
      const response = await statisticsAPI.getOverview();
      this.stats = response.data || response;

      this.loading = false;
      this.render();
    } catch (error) {
      console.error('載入統計資料失敗:', error);
      toast.error('載入統計資料失敗');
      this.loading = false;
      this.render();
    }
  }

  render() {
    const content = this.loading ? this.renderLoading() : this.renderContent();
    renderDashboardLayout(content, { title: '系統統計' });
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
        <div class="text-center py-12">
          <p class="text-modern-600">暫無統計資料</p>
        </div>
      `;
    }

    return `
      <div class="max-w-7xl mx-auto">
        <!-- 統計卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          ${this.renderStatCard('總文章數', this.stats.posts?.total || 0, '📝', 'accent')}
          ${this.renderStatCard('已發布', this.stats.posts?.published || 0, '✅', 'success')}
          ${this.renderStatCard('草稿', this.stats.posts?.draft || 0, '✏️', 'warning')}
          ${this.renderStatCard('總瀏覽量', this.stats.views?.total || 0, '👁️', 'info')}
        </div>

        <!-- 熱門文章 -->
        <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6 mb-6">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">熱門文章 Top 5</h2>
          ${this.renderPopularPosts()}
        </div>

        <!-- 系統資訊 -->
        <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6">
          <h2 class="text-xl font-semibold text-modern-900 mb-4">系統資訊</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-modern-600">資料庫大小</p>
              <p class="text-lg font-semibold text-modern-900">N/A</p>
            </div>
            <div>
              <p class="text-sm text-modern-600">快取使用率</p>
              <p class="text-lg font-semibold text-modern-900">N/A</p>
            </div>
            <div>
              <p class="text-sm text-modern-600">API 請求數（今日）</p>
              <p class="text-lg font-semibold text-modern-900">N/A</p>
            </div>
            <div>
              <p class="text-sm text-modern-600">系統運行時間</p>
              <p class="text-lg font-semibold text-modern-900">N/A</p>
            </div>
          </div>
          <div class="mt-4 p-4 bg-info-50 border border-info-200 rounded-lg">
            <p class="text-sm text-info-800">
              <strong>提示：</strong>詳細的統計圖表功能將在未來版本中添加。
            </p>
          </div>
        </div>
      </div>
    `;
  }

  renderStatCard(title, value, icon, color = 'accent') {
    const colors = {
      accent: 'from-accent-500 to-accent-600',
      success: 'from-green-500 to-green-600',
      warning: 'from-yellow-500 to-yellow-600',
      info: 'from-blue-500 to-blue-600',
    };

    return `
      <div class="bg-white rounded-lg shadow-sm border border-modern-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-modern-600 mb-1">${title}</p>
            <p class="text-3xl font-bold text-modern-900">${value}</p>
          </div>
          <div class="text-4xl">${icon}</div>
        </div>
      </div>
    `;
  }

  renderPopularPosts() {
    const posts = this.stats.popular_posts || [];
    
    if (posts.length === 0) {
      return '<p class="text-modern-600 text-center py-4">暫無熱門文章資料</p>';
    }

    return `
      <div class="space-y-3">
        ${posts.slice(0, 5).map((post, index) => `
          <div class="flex items-center justify-between p-3 bg-modern-50 rounded-lg">
            <div class="flex items-center gap-3">
              <span class="text-2xl font-bold text-modern-400">#${index + 1}</span>
              <div>
                <h3 class="font-medium text-modern-900">${this.escapeHtml(post.title)}</h3>
                <p class="text-sm text-modern-600">${post.views || 0} 次瀏覽</p>
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text ? String(text).replace(/[&<>"']/g, (m) => map[m]) : '';
  }
}

/**
 * 渲染系統統計頁面（wrapper 函數）
 */
export async function renderStatistics() {
  const page = new StatisticsPage();
  await page.init();
}
