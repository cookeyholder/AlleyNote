/**
 * 載入中組件
 */

export const loading = {
    show(message = '載入中...') {
        const existing = document.getElementById('loading');
        if (existing) {
            existing.classList.remove('hidden');
            return;
        }

        const loadingElement = document.createElement('div');
        loadingElement.id = 'loading';
        loadingElement.className = 'fixed inset-0 bg-white bg-opacity-90 z-50 flex items-center justify-center';
        loadingElement.innerHTML = `
            <div class="text-center">
                <div class="spinner mx-auto mb-4"></div>
                <p class="text-gray-600">${message}</p>
            </div>
        `;

        document.body.appendChild(loadingElement);
    },

    hide() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.classList.add('hidden');
        }
    }
};
