/**
 * Modal 組件
 */
export class Modal {
  constructor(options = {}) {
    this.id = options.id || `modal-${Date.now()}`;
    this.title = options.title || '';
    this.content = options.content || '';
    this.confirmText = options.confirmText || '確定';
    this.cancelText = options.cancelText || '取消';
    this.onConfirm = options.onConfirm || null;
    this.onCancel = options.onCancel || null;
    this.showCancel = options.showCancel !== false;
    this.showFooter = options.showFooter !== false; // 新增：是否顯示 footer
    this.size = options.size || 'md'; // sm, md, lg, xl
    this.modalElement = null;
  }

  /**
   * 顯示 Modal
   */
  show() {
    this._render();
    this._bindEvents();
    
    // 動畫顯示
    setTimeout(() => {
      this.modalElement.classList.add('modal-show');
    }, 10);
    
    // Focus trap
    this._setupFocusTrap();
  }

  /**
   * 隱藏 Modal
   */
  hide() {
    if (!this.modalElement) return;
    
    this.modalElement.classList.remove('modal-show');
    
    setTimeout(() => {
      if (this.modalElement && this.modalElement.parentNode) {
        this.modalElement.parentNode.removeChild(this.modalElement);
      }
      this.modalElement = null;
    }, 300);
  }

  /**
   * 渲染 Modal
   * @private
   */
  _render() {
    const sizeClasses = {
      sm: 'max-w-sm',
      md: 'max-w-md',
      lg: 'max-w-lg',
      xl: 'max-w-xl',
      '2xl': 'max-w-2xl',
    };

    const html = `
      <div id="${this.id}" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 opacity-0 transition-opacity duration-300">
        <div class="modal-content bg-white rounded-lg shadow-xl ${sizeClasses[this.size]} w-full mx-4 transform scale-95 transition-transform duration-300">
          ${this.title ? `
            <div class="modal-header px-6 py-4 border-b border-modern-200">
              <h3 class="text-xl font-semibold text-modern-900">${this.title}</h3>
            </div>
          ` : ''}
          
          <div class="modal-body px-6 py-4">
            ${this.content}
          </div>
          
          ${this.showFooter ? `
            <div class="modal-footer px-6 py-4 border-t border-modern-200 flex justify-end gap-3">
              ${this.showCancel ? `
                <button class="modal-cancel btn-secondary">
                  ${this.cancelText}
                </button>
              ` : ''}
              <button class="modal-confirm btn-primary">
                ${this.confirmText}
              </button>
            </div>
          ` : ''}
        </div>
      </div>
    `;

    const div = document.createElement('div');
    div.innerHTML = html;
    this.modalElement = div.firstElementChild;
    document.body.appendChild(this.modalElement);
  }

  /**
   * 綁定事件
   * @private
   */
  _bindEvents() {
    // 點擊遮罩關閉
    this.modalElement.addEventListener('click', (e) => {
      if (e.target === this.modalElement) {
        this._handleCancel();
      }
    });

    // 確定按鈕
    const confirmBtn = this.modalElement.querySelector('.modal-confirm');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => this._handleConfirm());
    }

    // 取消按鈕
    const cancelBtn = this.modalElement.querySelector('.modal-cancel');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => this._handleCancel());
    }

    // ESC 鍵關閉
    this._escHandler = (e) => {
      if (e.key === 'Escape') {
        this._handleCancel();
      }
    };
    document.addEventListener('keydown', this._escHandler);
  }

  /**
   * 處理確定
   * @private
   */
  async _handleConfirm() {
    if (this.onConfirm) {
      const result = await this.onConfirm();
      if (result !== false) {
        this.hide();
      }
    } else {
      this.hide();
    }
  }

  /**
   * 處理取消
   * @private
   */
  _handleCancel() {
    if (this.onCancel) {
      this.onCancel();
    }
    this.hide();
  }

  /**
   * 設定 Focus Trap
   * @private
   */
  _setupFocusTrap() {
    const focusableElements = this.modalElement.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    firstElement.focus();
    
    this.modalElement.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') {
        if (e.shiftKey && document.activeElement === firstElement) {
          e.preventDefault();
          lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    });
  }
}

/**
 * 確認對話框
 */
export function confirm(options) {
  return new Promise((resolve) => {
    const modal = new Modal({
      title: options.title || '確認',
      content: options.message || '確定要執行此操作嗎？',
      confirmText: options.confirmText || '確定',
      cancelText: options.cancelText || '取消',
      size: options.size || 'sm',
      onConfirm: () => {
        resolve(true);
      },
      onCancel: () => {
        resolve(false);
      },
    });
    
    modal.show();
  });
}
