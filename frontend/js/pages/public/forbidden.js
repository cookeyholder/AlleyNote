/**
 * 403 禁止訪問頁面
 */

export function render403() {
    const app = document.getElementById('app');

    app.innerHTML = `
        <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50 flex items-center justify-center px-4">
            <div class="max-w-2xl w-full text-center">
                <!-- 錯誤圖示 -->
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-red-100 rounded-full mb-6 animate-bounce">
                        <i class="fas fa-lock text-6xl text-red-500"></i>
                    </div>
                    <h1 class="text-8xl font-bold text-red-600 mb-4">403</h1>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">
                        禁止訪問
                    </h2>
                </div>

                <!-- 錯誤訊息 -->
                <div class="bg-white rounded-2xl border border-red-200 p-8 mb-8 shadow-xl">
                    <p class="text-xl text-gray-700 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        您沒有權限訪問此頁面
                    </p>
                    <p class="text-gray-600 mb-6">
                        這個頁面需要特定的權限才能訪問。如果您認為這是一個錯誤，請聯繫系統管理員。
                    </p>
                    
                    <!-- 可能的原因 -->
                    <div class="text-left bg-gray-50 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            可能的原因：
                        </h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>您的帳號權限不足</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>此頁面僅限主管理員訪問</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs text-gray-400 mt-1.5 mr-2"></i>
                                <span>您的登入狀態已過期</span>
                            </li>
                        </ul>
                    </div>

                    <!-- 操作按鈕 -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button
                            onclick="history.back()"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            返回上一頁
                        </button>
                        <a
                            href="/"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium inline-block"
                        >
                            <i class="fas fa-home mr-2"></i>
                            返回首頁
                        </a>
                        <a
                            href="/login"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium inline-block"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            重新登入
                        </a>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <p class="text-gray-600">
                    <i class="fas fa-envelope mr-2"></i>
                    需要協助？請聯絡
                    <a href="mailto:support@alleynote.com" class="text-blue-600 hover:text-blue-700 font-medium">
                        support@alleynote.com
                    </a>
                </p>
            </div>
        </div>
    `;
}
