/**
 * Toast 通知系統
 */
class Toast {
  constructor() {
    this.container = null;
    this.init();
  }

  init() {
    // 建立容器
    this.container = document.createElement('div');
    this.container.id = 'toast-container';
    this.container.className =
      'fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm';
    document.body.appendChild(this.container);
  }

  /**
   * 顯示 Toast
   */
  show(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `
      px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300
      ${this.getTypeClass(type)}
    `;
    toast.textContent = message;

    this.container.appendChild(toast);

    // 動畫進入
    setTimeout(() => {
      toast.style.transform = 'translateX(0)';
      toast.style.opacity = '1';
    }, 10);

    // 自動移除
    setTimeout(() => {
      toast.style.transform = 'translateX(100%)';
      toast.style.opacity = '0';
      setTimeout(() => {
        this.container.removeChild(toast);
      }, 300);
    }, duration);
  }

  /**
   * 取得類型樣式
   */
  getTypeClass(type) {
    const classes = {
      success: 'bg-green-500 text-white',
      error: 'bg-red-500 text-white',
      warning: 'bg-yellow-500 text-white',
      info: 'bg-blue-500 text-white',
    };
    return classes[type] || classes.info;
  }

  success(message, duration) {
    this.show(message, 'success', duration);
  }

  error(message, duration) {
    this.show(message, 'error', duration);
  }

  warning(message, duration) {
    this.show(message, 'warning', duration);
  }

  info(message, duration) {
    this.show(message, 'info', duration);
  }
}

export const toast = new Toast();
