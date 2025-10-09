/**
 * Toast 通知組件
 * 提供輕量級的通知訊息顯示功能
 */

class ToastManager {
  constructor() {
    this.container = null;
    this.toasts = [];
    this.init();
  }

  /**
   * 初始化 Toast 容器
   */
  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(this.container);
    }
  }

  /**
   * 顯示 Toast
   */
  show(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast-item bg-white border-l-4 p-4 rounded-lg shadow-lg flex items-center gap-3 animate-fade-in min-w-[320px]`;
    
    const colors = {
      success: ['border-green-500', 'text-green-700'],
      error: ['border-red-500', 'text-red-700'],
      warning: ['border-yellow-500', 'text-yellow-700'],
      info: ['border-blue-500', 'text-blue-700'],
    };

    const icons = {
      success: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
      error: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
      warning: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`,
      info: `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
    };

    toast.classList.add(...(colors[type] || colors.info));

    toast.innerHTML = `
      <div class="flex-shrink-0">
        ${icons[type] || icons.info}
      </div>
      <div class="flex-1 text-sm font-medium">
        ${message}
      </div>
      <button type="button" class="flex-shrink-0 text-modern-400 hover:text-modern-600 transition-colors" data-toast-close>
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    `;

    // 添加到容器
    this.container.appendChild(toast);
    this.toasts.push(toast);

    // 綁定關閉按鈕
    const closeBtn = toast.querySelector('[data-toast-close]');
    const removeToast = () => {
      toast.classList.add('opacity-0', 'transform', 'translate-x-full');
      setTimeout(() => {
        toast.remove();
        this.toasts = this.toasts.filter(t => t !== toast);
      }, 300);
    };

    closeBtn.addEventListener('click', removeToast);

    // 自動關閉
    if (duration > 0) {
      setTimeout(removeToast, duration);
    }

    return toast;
  }

  /**
   * 成功訊息
   */
  success(message, duration) {
    return this.show(message, 'success', duration);
  }

  /**
   * 錯誤訊息
   */
  error(message, duration) {
    return this.show(message, 'error', duration);
  }

  /**
   * 警告訊息
   */
  warning(message, duration) {
    return this.show(message, 'warning', duration);
  }

  /**
   * 資訊訊息
   */
  info(message, duration) {
    return this.show(message, 'info', duration);
  }

  /**
   * 清除所有 Toast
   */
  clearAll() {
    this.toasts.forEach(toast => toast.remove());
    this.toasts = [];
  }
}

// 建立並匯出單例
export const toast = new ToastManager();
