/**
 * 載入指示器組件
 */

class LoadingComponent {
  constructor() {
    this.container = null;
    this.isVisible = false;
  }

  /**
   * 顯示載入指示器
   */
  show(message = '載入中...') {
    if (this.isVisible) return;

    this.isVisible = true;

    // 建立載入容器
    this.container = document.createElement('div');
    this.container.id = 'loading-overlay';
    this.container.className = 'fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center';
    
    this.container.innerHTML = `
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent-600 mx-auto mb-4"></div>
        <p class="text-modern-600">${message}</p>
      </div>
    `;

    document.body.appendChild(this.container);
  }

  /**
   * 隱藏載入指示器
   */
  hide() {
    if (!this.isVisible) return;

    this.isVisible = false;

    if (this.container && this.container.parentNode) {
      this.container.remove();
      this.container = null;
    }
  }

  /**
   * 檢查是否可見
   */
  visible() {
    return this.isVisible;
  }
}

// 建立並匯出單例
export const loading = new LoadingComponent();
