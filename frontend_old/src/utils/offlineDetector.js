/**
 * 網路離線偵測器
 * 監控網路狀態並顯示提示
 */

class OfflineDetector {
    constructor() {
        this.isOnline = navigator.onLine;
        this.banner = null;
        this.retryTimer = null;
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    /**
     * 初始化離線偵測
     */
    init() {
        // 監聽網路狀態變化
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // 檢查初始狀態
        if (!navigator.onLine) {
            this.handleOffline();
        }
    }

    /**
     * 處理網路連線
     */
    handleOnline() {
        this.isOnline = true;
        this.retryCount = 0;

        // 隱藏離線提示
        this.hideBanner();

        // 顯示已恢復連線的提示
        this.showToast('網路連線已恢復', 'success');

        // 清除重試計時器
        if (this.retryTimer) {
            clearTimeout(this.retryTimer);
            this.retryTimer = null;
        }

        // 重新整理頁面（可選）
        // window.location.reload();
    }

    /**
     * 處理網路離線
     */
    handleOffline() {
        this.isOnline = false;

        // 顯示離線提示
        this.showBanner();

        // 開始重試連線
        this.startRetrying();
    }

    /**
     * 顯示離線提示橫幅
     */
    showBanner() {
        // 移除舊的橫幅
        this.hideBanner();

        // 建立新的橫幅
        this.banner = document.createElement('div');
        this.banner.id = 'offline-banner';
        this.banner.className =
            'fixed top-0 left-0 right-0 z-[10000] bg-red-600 text-white py-3 px-4 shadow-lg animate-slide-down';
        this.banner.innerHTML = `
            <div class="container mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-wifi-slash text-xl animate-pulse"></i>
                    <div>
                        <p class="font-semibold">無法連線到網路</p>
                        <p class="text-sm opacity-90">請檢查您的網路連線</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm" id="retry-status"></span>
                    <button
                        id="retry-connection-btn"
                        class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition-colors font-medium"
                    >
                        <i class="fas fa-sync-alt mr-2"></i>
                        重試
                    </button>
                    <button
                        id="close-offline-banner"
                        class="p-2 hover:bg-red-700 rounded-lg transition-colors"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(this.banner);

        // 綁定事件
        const retryBtn = document.getElementById('retry-connection-btn');
        const closeBtn = document.getElementById('close-offline-banner');

        retryBtn?.addEventListener('click', () => this.checkConnection());
        closeBtn?.addEventListener('click', () => this.hideBanner());
    }

    /**
     * 隱藏離線提示橫幅
     */
    hideBanner() {
        if (this.banner) {
            this.banner.remove();
            this.banner = null;
        }
    }

    /**
     * 開始重試連線
     */
    startRetrying() {
        this.retryCount = 0;
        this.scheduleRetry();
    }

    /**
     * 排程重試
     */
    scheduleRetry() {
        if (this.retryCount >= this.maxRetries) {
            this.updateRetryStatus('已停止自動重試');
            return;
        }

        const delay = Math.min(5000 * Math.pow(2, this.retryCount), 30000); // 指數退避，最多 30 秒
        this.updateRetryStatus(`${delay / 1000} 秒後自動重試...`);

        this.retryTimer = setTimeout(() => {
            this.checkConnection();
        }, delay);
    }

    /**
     * 檢查網路連線
     */
    async checkConnection() {
        this.updateRetryStatus('正在檢查連線...');

        try {
            // 嘗試發送請求到伺服器
            const response = await fetch('/api/health', {
                method: 'HEAD',
                cache: 'no-cache',
            });

            if (response.ok) {
                this.handleOnline();
            } else {
                this.handleRetryFailure();
            }
        } catch (error) {
            this.handleRetryFailure();
        }
    }

    /**
     * 處理重試失敗
     */
    handleRetryFailure() {
        this.retryCount++;
        this.updateRetryStatus('連線失敗');

        if (this.retryCount < this.maxRetries) {
            setTimeout(() => this.scheduleRetry(), 1000);
        } else {
            this.updateRetryStatus('已達最大重試次數');
        }
    }

    /**
     * 更新重試狀態文字
     */
    updateRetryStatus(message) {
        const statusEl = document.getElementById('retry-status');
        if (statusEl) {
            statusEl.textContent = message;
        }
    }

    /**
     * 顯示 Toast 提示
     */
    showToast(message, type = 'info') {
        // 如果有全域的 toast 組件，使用它
        if (window.toast) {
            window.toast[type](message);
            return;
        }

        // 否則建立簡單的 toast
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 z-[10001] px-6 py-3 rounded-lg shadow-lg text-white animate-fade-in ${
            type === 'success' ? 'bg-green-600' : 'bg-blue-600'
        }`;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    /**
     * 取得當前網路狀態
     */
    getStatus() {
        return {
            isOnline: this.isOnline,
            retryCount: this.retryCount,
        };
    }

    /**
     * 銷毀偵測器
     */
    destroy() {
        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
        this.hideBanner();

        if (this.retryTimer) {
            clearTimeout(this.retryTimer);
            this.retryTimer = null;
        }
    }
}

// 建立全域實例
export const offlineDetector = new OfflineDetector();

// 自動初始化
if (typeof window !== 'undefined') {
    // 等待 DOM 載入完成
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            offlineDetector.init();
        });
    } else {
        offlineDetector.init();
    }
}
