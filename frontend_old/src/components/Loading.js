/**
 * Loading 組件
 */
export class Loading {
  constructor() {
    this.element = null;
  }

  /**
   * 顯示 Loading
   */
  show(message = '載入中...') {
    if (this.element) {
      this.hide();
    }

    const html = `
      <div class="loading-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4 shadow-xl">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600"></div>
          <p class="text-modern-700">${message}</p>
        </div>
      </div>
    `;

    const div = document.createElement('div');
    div.innerHTML = html;
    this.element = div.firstElementChild;
    document.body.appendChild(this.element);
  }

  /**
   * 隱藏 Loading
   */
  hide() {
    if (this.element && this.element.parentNode) {
      this.element.parentNode.removeChild(this.element);
      this.element = null;
    }
  }
}

/**
 * 全域 Loading 實例
 */
export const loading = new Loading();
