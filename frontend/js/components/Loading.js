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
    this.container.className = 'fixed inset-0 bg-white/60 backdrop-blur-md z-[100] flex items-center justify-center animate-fade-in';
    
    this.container.innerHTML = `
      <div class="text-center p-10 bg-white/40 rounded-3xl border border-white shadow-2xl">
        <div class="relative w-20 h-20 mx-auto mb-6">
          <div class="absolute inset-0 border-4 border-modern-100 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-accent-600 rounded-full border-t-transparent animate-spin"></div>
          <div class="absolute inset-0 flex items-center justify-center text-accent-600 font-bold text-xs uppercase tracking-widest">
            A
          </div>
        </div>
        <p class="text-modern-900 font-bold text-sm tracking-widest uppercase animate-pulse">${message}</p>
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
