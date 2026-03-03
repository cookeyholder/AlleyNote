/**
 * Modal 對話框組件
 */

class ModalComponent {
  constructor() {
    this.modals = [];
  }

  /**
   * 顯示 Modal
   */
  show(title, content, options = {}) {
    const {
      size = 'md', // sm, md, lg, xl
      onClose = null,
      showCloseButton = true,
      closeOnBackdrop = true,
    } = options;

    // 建立 Modal 容器
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in';
    
    const sizeClasses = {
      sm: 'max-w-md',
      md: 'max-w-lg',
      lg: 'max-w-2xl',
      xl: 'max-w-4xl',
      full: 'max-w-full',
    };

    modal.innerHTML = `
      <div class="absolute inset-0 bg-black bg-opacity-50" data-modal-backdrop></div>
      <div class="relative bg-white rounded-lg shadow-xl ${sizeClasses[size]} w-full max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-modern-200">
          <h3 class="text-xl font-semibold text-modern-900">${title}</h3>
          ${showCloseButton ? `
            <button type="button" class="text-modern-400 hover:text-modern-600 transition-colors" data-modal-close>
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          ` : ''}
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          ${content}
        </div>
      </div>
    `;

    // 關閉函數
    const closeModal = () => {
      modal.remove();
      this.modals = this.modals.filter(m => m !== modal);
      if (onClose) onClose();
    };

    // 綁定關閉事件
    if (showCloseButton) {
      const closeButton = modal.querySelector('[data-modal-close]');
      if (closeButton) {
        closeButton.addEventListener('click', closeModal);
      }
    }

    // 點擊背景關閉
    if (closeOnBackdrop) {
      const backdrop = modal.querySelector('[data-modal-backdrop]');
      if (backdrop) {
        backdrop.addEventListener('click', closeModal);
      }
    }

    // 添加到頁面
    document.body.appendChild(modal);
    this.modals.push(modal);

    return {
      close: closeModal,
      element: modal,
    };
  }

  /**
   * 確認對話框
   */
  confirm(title, message, onConfirm, onCancel = null) {
    const content = `
      <div class="text-modern-700 mb-6">${message}</div>
      <div class="flex justify-end gap-3">
        <button type="button" class="btn-secondary px-6 py-2" data-action="cancel">
          取消
        </button>
        <button type="button" class="btn-primary px-6 py-2" data-action="confirm">
          確定
        </button>
      </div>
    `;

    const modalInstance = this.show(title, content, {
      size: 'sm',
      showCloseButton: false,
      closeOnBackdrop: false,
    });

    // 綁定按鈕事件
    const cancelBtn = modalInstance.element.querySelector('[data-action="cancel"]');
    const confirmBtn = modalInstance.element.querySelector('[data-action="confirm"]');

    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        modalInstance.close();
        if (onCancel) onCancel();
      });
    }

    if (confirmBtn) {
      confirmBtn.addEventListener('click', async () => {
        try {
          if (onConfirm) await onConfirm();
          modalInstance.close();
        } catch (error) {
          console.error('Confirm action error:', error);
        }
      });
    }

    return modalInstance;
  }

  /**
   * 警告對話框
   */
  alert(title, message, onClose = null) {
    const content = `
      <div class="text-modern-700 mb-6">${message}</div>
      <div class="flex justify-end">
        <button type="button" class="btn-primary px-6 py-2" data-action="ok">
          確定
        </button>
      </div>
    `;

    const modalInstance = this.show(title, content, {
      size: 'sm',
      showCloseButton: false,
      closeOnBackdrop: false,
    });

    // 綁定按鈕事件
    const okBtn = modalInstance.element.querySelector('[data-action="ok"]');
    if (okBtn) {
      okBtn.addEventListener('click', () => {
        modalInstance.close();
        if (onClose) onClose();
      });
    }

    return modalInstance;
  }

  /**
   * 關閉所有 Modal
   */
  closeAll() {
    this.modals.forEach(modal => modal.remove());
    this.modals = [];
  }
}

/**
 * Modal 類別（用於實例化）
 */
export class Modal {
  constructor(options = {}) {
    this.options = options;
    this.modalInstance = null;
    this.modalComponent = new ModalComponent();
  }

  show() {
    const { title, content, size = 'md', showFooter = true, onClose } = this.options;
    
    this.modalInstance = this.modalComponent.show(title, content, {
      size,
      onClose,
      showCloseButton: true,
      closeOnBackdrop: true,
    });

    return this.modalInstance;
  }

  hide() {
    if (this.modalInstance) {
      this.modalInstance.close();
      this.modalInstance = null;
    }
  }
}

// 建立並匯出單例
export const modal = new ModalComponent();
