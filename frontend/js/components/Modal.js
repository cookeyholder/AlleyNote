/**
 * Modal 對話框組件
 */

export class Modal {
    constructor(options = {}) {
        this.title = options.title || '';
        this.content = options.content || '';
        this.onConfirm = options.onConfirm || null;
        this.onCancel = options.onCancel || null;
        this.showCancel = options.showCancel !== false;
        this.confirmText = options.confirmText || '確認';
        this.cancelText = options.cancelText || '取消';
        this.size = options.size || 'md'; // sm, md, lg, xl
    }

    render() {
        const sizeClasses = {
            sm: 'max-w-sm',
            md: 'max-w-md',
            lg: 'max-w-lg',
            xl: 'max-w-xl'
        };

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content ${sizeClasses[this.size]} w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold">${this.title}</h3>
                    <button class="text-gray-400 hover:text-gray-600" data-close>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-6">
                    ${this.content}
                </div>
                <div class="flex justify-end gap-2">
                    ${this.showCancel ? `
                        <button class="btn btn-outline" data-cancel>
                            ${this.cancelText}
                        </button>
                    ` : ''}
                    <button class="btn btn-primary" data-confirm>
                        ${this.confirmText}
                    </button>
                </div>
            </div>
        `;

        // 綁定事件
        const closeBtn = modal.querySelector('[data-close]');
        const cancelBtn = modal.querySelector('[data-cancel]');
        const confirmBtn = modal.querySelector('[data-confirm]');

        closeBtn.addEventListener('click', () => this.close());
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (this.onCancel) this.onCancel();
                this.close();
            });
        }

        confirmBtn.addEventListener('click', async () => {
            if (this.onConfirm) {
                const result = await this.onConfirm();
                if (result !== false) {
                    this.close();
                }
            } else {
                this.close();
            }
        });

        // 點擊背景關閉
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close();
            }
        });

        this.element = modal;
        return modal;
    }

    show() {
        document.body.appendChild(this.render());
    }

    close() {
        if (this.element && this.element.parentElement) {
            this.element.remove();
        }
    }

    static confirm(title, message, onConfirm) {
        return new Modal({
            title,
            content: `<p>${message}</p>`,
            onConfirm,
            size: 'sm'
        }).show();
    }

    static alert(title, message) {
        return new Modal({
            title,
            content: `<p>${message}</p>`,
            showCancel: false,
            size: 'sm'
        }).show();
    }
}
