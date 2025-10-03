/**
 * 確認對話框組件
 * 用於危險操作的二次確認
 */
export class ConfirmationDialog {
    constructor() {
        this.container = null;
        this.isOpen = false;
        this.onConfirm = null;
        this.onCancel = null;
    }

    /**
     * 顯示確認對話框
     * @param {Object} options - 對話框選項
     * @param {string} options.title - 對話框標題
     * @param {string} options.message - 對話框訊息
     * @param {string} [options.confirmText='確認'] - 確認按鈕文字
     * @param {string} [options.cancelText='取消'] - 取消按鈕文字
     * @param {string} [options.type='warning'] - 對話框類型 (warning, danger, info)
     * @returns {Promise<boolean>} - 使用者確認結果
     */
    show({
        title,
        message,
        confirmText = '確認',
        cancelText = '取消',
        type = 'warning',
    }) {
        return new Promise((resolve) => {
            this.isOpen = true;
            this.render({ title, message, confirmText, cancelText, type });

            this.onConfirm = () => {
                this.close();
                resolve(true);
            };

            this.onCancel = () => {
                this.close();
                resolve(false);
            };

            // 綁定事件
            this.bindEvents();
        });
    }

    /**
     * 渲染對話框
     */
    render({ title, message, confirmText, cancelText, type }) {
        // 移除舊的對話框
        if (this.container) {
            this.container.remove();
        }

        // 決定圖示和顏色
        let icon, iconColor, confirmButtonClass;
        switch (type) {
            case 'danger':
                icon = 'fa-exclamation-triangle';
                iconColor = 'text-red-500';
                confirmButtonClass =
                    'bg-red-600 hover:bg-red-700 focus:ring-red-500';
                break;
            case 'info':
                icon = 'fa-info-circle';
                iconColor = 'text-blue-500';
                confirmButtonClass =
                    'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500';
                break;
            case 'warning':
            default:
                icon = 'fa-exclamation-circle';
                iconColor = 'text-yellow-500';
                confirmButtonClass =
                    'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500';
                break;
        }

        // 建立對話框
        this.container = document.createElement('div');
        this.container.id = 'confirmation-dialog';
        this.container.className =
            'fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4';
        this.container.innerHTML = `
            <!-- 背景遮罩 -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" data-backdrop></div>
            
            <!-- 對話框內容 -->
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all animate-fade-in-up">
                <!-- 圖示 -->
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-gray-100">
                    <i class="fas ${icon} text-2xl ${iconColor}"></i>
                </div>
                
                <!-- 標題 -->
                <h3 class="text-xl font-bold text-center text-gray-900 mb-2">
                    ${this.escapeHtml(title)}
                </h3>
                
                <!-- 訊息 -->
                <p class="text-gray-600 text-center mb-6">
                    ${this.escapeHtml(message)}
                </p>
                
                <!-- 按鈕 -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                        data-cancel
                    >
                        ${this.escapeHtml(cancelText)}
                    </button>
                    <button
                        type="button"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white ${confirmButtonClass} rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                        data-confirm
                        autofocus
                    >
                        ${this.escapeHtml(confirmText)}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(this.container);

        // 防止背景滾動
        document.body.style.overflow = 'hidden';
    }

    /**
     * 綁定事件
     */
    bindEvents() {
        if (!this.container) return;

        const confirmBtn = this.container.querySelector('[data-confirm]');
        const cancelBtn = this.container.querySelector('[data-cancel]');
        const backdrop = this.container.querySelector('[data-backdrop]');

        confirmBtn?.addEventListener('click', () => this.onConfirm?.());
        cancelBtn?.addEventListener('click', () => this.onCancel?.());
        backdrop?.addEventListener('click', () => this.onCancel?.());

        // ESC 鍵關閉
        this.handleKeyDown = (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.onCancel?.();
            }
        };
        document.addEventListener('keydown', this.handleKeyDown);
    }

    /**
     * 關閉對話框
     */
    close() {
        this.isOpen = false;

        // 移除事件監聽器
        if (this.handleKeyDown) {
            document.removeEventListener('keydown', this.handleKeyDown);
        }

        // 移除對話框
        if (this.container) {
            this.container.remove();
            this.container = null;
        }

        // 恢復背景滾動
        document.body.style.overflow = '';
    }

    /**
     * 轉義 HTML 字元
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 建立全域實例
export const confirmationDialog = new ConfirmationDialog();

// 便捷方法
export function confirm(options) {
    return confirmationDialog.show(options);
}

export function confirmDelete(itemName = '此項目') {
    return confirmationDialog.show({
        title: '確認刪除',
        message: `您確定要刪除「${itemName}」嗎？此操作無法復原。`,
        confirmText: '刪除',
        cancelText: '取消',
        type: 'danger',
    });
}

export function confirmDiscard() {
    return confirmationDialog.show({
        title: '放棄變更',
        message: '您有未儲存的變更，確定要離開此頁面嗎？',
        confirmText: '放棄',
        cancelText: '繼續編輯',
        type: 'warning',
    });
}
