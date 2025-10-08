/**
 * Web Vitals 監控工具
 * 追蹤核心 Web 性能指標
 * 
 * @author AlleyNote Team
 * @version 1.0.0
 */

import { getAnalytics } from './analytics.js';
import { captureMessage } from './errorTracker.js';

/**
 * WebVitals 類別
 * 監控和報告 Web Vitals 指標
 */
class WebVitals {
  constructor() {
    this.enabled = import.meta.env.PROD;
    this.metrics = {
      CLS: null,  // Cumulative Layout Shift
      FID: null,  // First Input Delay
      FCP: null,  // First Contentful Paint
      LCP: null,  // Largest Contentful Paint
      TTFB: null, // Time to First Byte
      INP: null   // Interaction to Next Paint
    };
    this.thresholds = {
      CLS: { good: 0.1, needsImprovement: 0.25 },
      FID: { good: 100, needsImprovement: 300 },
      FCP: { good: 1800, needsImprovement: 3000 },
      LCP: { good: 2500, needsImprovement: 4000 },
      TTFB: { good: 800, needsImprovement: 1800 },
      INP: { good: 200, needsImprovement: 500 }
    };
  }

  /**
   * 初始化 Web Vitals 監控
   */
  async init() {
    if (!this.enabled) {
      console.log('[WebVitals] Disabled in development');
      return;
    }

    try {
      // 動態載入 web-vitals 庫
      const webVitals = await import('web-vitals');
      
      // 監控各項指標
      webVitals.onCLS(this.handleMetric.bind(this, 'CLS'));
      webVitals.onFID(this.handleMetric.bind(this, 'FID'));
      webVitals.onFCP(this.handleMetric.bind(this, 'FCP'));
      webVitals.onLCP(this.handleMetric.bind(this, 'LCP'));
      webVitals.onTTFB(this.handleMetric.bind(this, 'TTFB'));
      webVitals.onINP(this.handleMetric.bind(this, 'INP'));
      
      console.log('[WebVitals] Monitoring started');
    } catch (error) {
      console.error('[WebVitals] Initialization failed:', error);
    }
  }

  /**
   * 處理指標回報
   * @param {string} name - 指標名稱
   * @param {Object} metric - 指標物件
   */
  handleMetric(name, metric) {
    // 儲存指標
    this.metrics[name] = {
      value: metric.value,
      rating: metric.rating,
      delta: metric.delta,
      id: metric.id,
      entries: metric.entries
    };

    // 評估指標
    const assessment = this.assessMetric(name, metric.value);
    
    // 記錄到 console
    console.log(`[WebVitals] ${name}:`, {
      value: metric.value,
      rating: assessment,
      threshold: this.thresholds[name]
    });

    // 發送到 Google Analytics
    this.reportToAnalytics(name, metric, assessment);
    
    // 如果指標不佳，發送警告
    if (assessment === 'poor') {
      this.reportPoorMetric(name, metric);
    }
  }

  /**
   * 評估指標表現
   * @param {string} name - 指標名稱
   * @param {number} value - 指標值
   * @returns {string} 評估結果 (good, needs-improvement, poor)
   */
  assessMetric(name, value) {
    const threshold = this.thresholds[name];
    
    if (value <= threshold.good) {
      return 'good';
    } else if (value <= threshold.needsImprovement) {
      return 'needs-improvement';
    } else {
      return 'poor';
    }
  }

  /**
   * 報告到 Google Analytics
   * @param {string} name - 指標名稱
   * @param {Object} metric - 指標物件
   * @param {string} assessment - 評估結果
   */
  reportToAnalytics(name, metric, assessment) {
    const analytics = getAnalytics();
    
    if (!analytics.enabled) return;

    // 發送自訂事件
    analytics.trackEvent('web_vitals', {
      event_category: 'Web Vitals',
      event_label: name,
      value: Math.round(metric.value),
      metric_id: metric.id,
      metric_rating: assessment,
      metric_delta: Math.round(metric.delta)
    });

    // 發送計時事件
    analytics.trackTiming(
      'Web Vitals',
      name,
      Math.round(metric.value),
      assessment
    );
  }

  /**
   * 報告不佳的指標
   * @param {string} name - 指標名稱
   * @param {Object} metric - 指標物件
   */
  reportPoorMetric(name, metric) {
    const message = `Poor Web Vital: ${name} = ${metric.value}`;
    
    captureMessage(message, 'warning', {
      metric: name,
      value: metric.value,
      rating: metric.rating,
      threshold: this.thresholds[name],
      url: window.location.href
    });
  }

  /**
   * 取得所有指標
   * @returns {Object} 指標物件
   */
  getMetrics() {
    return { ...this.metrics };
  }

  /**
   * 取得指標摘要
   * @returns {Object} 摘要物件
   */
  getSummary() {
    const summary = {};
    
    Object.entries(this.metrics).forEach(([name, metric]) => {
      if (metric) {
        summary[name] = {
          value: metric.value,
          rating: this.assessMetric(name, metric.value),
          threshold: this.thresholds[name]
        };
      }
    });
    
    return summary;
  }

  /**
   * 檢查是否所有指標都達標
   * @returns {boolean}
   */
  isHealthy() {
    return Object.entries(this.metrics).every(([name, metric]) => {
      if (!metric) return true;
      return this.assessMetric(name, metric.value) !== 'poor';
    });
  }

  /**
   * 取得需要改進的指標
   * @returns {Array} 指標列表
   */
  getProblematicMetrics() {
    const problematic = [];
    
    Object.entries(this.metrics).forEach(([name, metric]) => {
      if (!metric) return;
      
      const assessment = this.assessMetric(name, metric.value);
      if (assessment !== 'good') {
        problematic.push({
          name,
          value: metric.value,
          rating: assessment,
          threshold: this.thresholds[name]
        });
      }
    });
    
    return problematic;
  }

  /**
   * 建立效能報告
   * @returns {string} HTML 報告
   */
  createReport() {
    const summary = this.getSummary();
    
    let html = '<div class="web-vitals-report">';
    html += '<h3>Web Vitals 效能報告</h3>';
    html += '<table>';
    html += '<tr><th>指標</th><th>數值</th><th>評級</th><th>門檻</th></tr>';
    
    Object.entries(summary).forEach(([name, data]) => {
      const ratingClass = data.rating === 'good' ? 'good' : 
                          data.rating === 'needs-improvement' ? 'warning' : 'poor';
      
      html += `<tr class="${ratingClass}">`;
      html += `<td>${name}</td>`;
      html += `<td>${data.value.toFixed(2)}</td>`;
      html += `<td>${data.rating}</td>`;
      html += `<td>Good: ${data.threshold.good}, Poor: ${data.threshold.needsImprovement}</td>`;
      html += '</tr>';
    });
    
    html += '</table>';
    html += '</div>';
    
    return html;
  }

  /**
   * 顯示效能報告（開發用）
   */
  showReport() {
    if (this.enabled) return;
    
    const report = this.createReport();
    const container = document.createElement('div');
    container.innerHTML = report;
    container.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: white;
      padding: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 9999;
      max-width: 400px;
      font-family: monospace;
      font-size: 12px;
    `;
    
    document.body.appendChild(container);
    
    // 5 秒後自動關閉
    setTimeout(() => {
      container.remove();
    }, 5000);
  }
}

// 全域實例
let webVitals = null;

/**
 * 取得 WebVitals 實例
 * @returns {WebVitals}
 */
export function getWebVitals() {
  if (!webVitals) {
    webVitals = new WebVitals();
  }
  return webVitals;
}

/**
 * 初始化 Web Vitals 監控
 * @returns {Promise<WebVitals>}
 */
export async function initWebVitals() {
  const instance = getWebVitals();
  await instance.init();
  return instance;
}

/**
 * 取得效能報告
 * @returns {Object}
 */
export function getPerformanceReport() {
  const instance = getWebVitals();
  return {
    metrics: instance.getMetrics(),
    summary: instance.getSummary(),
    healthy: instance.isHealthy(),
    problematic: instance.getProblematicMetrics()
  };
}

export default {
  WebVitals,
  getWebVitals,
  initWebVitals,
  getPerformanceReport
};
