import { authAPI } from '../../api/modules/auth.js';
import { router } from '../../utils/router.js';
import { toast } from '../../utils/toast.js';
import { FormValidator, ValidationRules } from '../../utils/validator.js';
import { loading } from '../../components/Loading.js';

/**
 * 渲染忘記密碼頁面
 */
export function renderForgotPassword() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center p-6 relative overflow-hidden">
      <!-- 品牌裝飾背景 -->
      <div class="absolute top-0 left-0 w-full h-1 bg-accent-600"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-accent-100 rounded-full blur-3xl opacity-20"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-20"></div>

      <div class="max-w-md w-full animate-fade-in relative z-10">
        <!-- Logo 簡化版 -->
        <div class="flex flex-col items-center mb-8">
          <a href="/login" data-navigo class="w-12 h-12 bg-white border border-modern-200 rounded-xl flex items-center justify-center text-accent-600 font-bold text-xl shadow-sm hover:shadow-md transition-all mb-4">A</a>
        </div>

        <div class="bg-white border border-modern-200 rounded-3xl shadow-2xl shadow-modern-200/50 p-10">
          <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-accent-50 text-accent-600 rounded-2xl mb-6">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-modern-900 mb-2">忘記密碼？</h1>
            <p class="text-sm text-modern-500 leading-relaxed">請輸入您的註冊電子郵件，我們將為您發送安全的密碼重設連結</p>
          </div>
          
          <form id="forgot-password-form" class="space-y-6">
            <div>
              <label for="email" class="block text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-2">
                電子郵件地址 *
              </label>
              <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                class="w-full px-4 py-3.5 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
                placeholder="your@email.com"
              />
              <p class="text-red-500 text-xs font-bold mt-2 hidden" data-error-for="email"></p>
            </div>
            
            <button
              type="submit"
              class="w-full py-4 bg-accent-600 text-white font-bold rounded-xl hover:bg-accent-700 shadow-xl shadow-accent-600/20 transform transition-all active:scale-95 flex items-center justify-center gap-2"
              id="submit-btn"
            >
              發送重設指令
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </button>
          </form>
          
          <div class="mt-8 pt-8 border-t border-modern-100 text-center">
            <a href="/login" data-navigo class="text-sm font-bold text-modern-500 hover:text-accent-600 transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              返回成員登入
            </a>
          </div>
        </div>

        <div class="mt-10 p-6 bg-blue-50/50 border border-blue-100 rounded-2xl flex items-start gap-4">
          <div class="p-2 bg-white rounded-xl shadow-sm text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <h4 class="text-sm font-bold text-blue-900 mb-1">安全性提示</h4>
            <p class="text-xs text-blue-700 leading-relaxed opacity-80">
              重設連結有效期為 24 小時。若未收到郵件，請檢查垃圾信箱或聯繫系統管理員。
            </p>
          </div>
        </div>
      </div>
    </div>
  `;

  // 綁定表單提交事件
  bindForgotPasswordForm();
}

/**
 * 綁定忘記密碼表單
 */
function bindForgotPasswordForm() {
  const form = document.getElementById('forgot-password-form');
  const btn = document.getElementById('submit-btn');
  
  // 定義驗證規則
  const validator = new FormValidator(form, {
    email: [
      ValidationRules.required('請輸入電子郵件'),
      ValidationRules.email('請輸入有效的電子郵件格式'),
    ],
  });
  
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // 清除舊錯誤
    validator.clearErrors();
    
    // 驗證表單
    if (!validator.validate()) {
      validator.showErrors();
      return;
    }
    
    const email = form.email.value;
    
    // 禁用按鈕
    btn.disabled = true;
    btn.textContent = '發送中...';
    
    loading.show('發送重設連結中...');
    
    try {
      // 模擬 API 呼叫（實際應該調用真實的 API）
      // await authAPI.forgotPassword({ email });
      
      // 暫時使用 setTimeout 模擬
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      loading.hide();
      toast.success('重設密碼連結已發送至您的信箱，請查收');
      
      // 顯示成功訊息
      showSuccessMessage(email);
      
    } catch (error) {
      loading.hide();
      
      if (error && typeof error.isValidationError === 'function' && error.isValidationError()) {
        const errors = error.getValidationErrors();
        Object.entries(errors).forEach(([field, messages]) => {
          const errorEl = document.querySelector(`[data-error-for="${field}"]`);
          if (errorEl) {
            errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
            errorEl.classList.remove('hidden');
          }
        });
      } else {
        let errorMessage = '發送失敗，請稍後再試';
        
        if (error && typeof error.getUserMessage === 'function') {
          errorMessage = error.getUserMessage();
        } else if (error && error.message) {
          errorMessage = error.message;
        }
        
        toast.error(errorMessage);
      }
      
      // 重置按鈕
      btn.disabled = false;
      btn.textContent = '發送重設連結';
    }
  });
  
  // 輸入時清除錯誤
  form.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', () => {
      const errorEl = document.querySelector(`[data-error-for="${input.name}"]`);
      if (errorEl) {
        errorEl.classList.add('hidden');
      }
      input.classList.remove('border-red-500');
    });
  });
}

/**
 * 顯示成功訊息
 */
function showSuccessMessage(email) {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full animate-fade-in text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        
        <h2 class="text-2xl font-bold text-modern-900 mb-4">郵件已發送</h2>
        
        <p class="text-modern-600 mb-4">
          我們已將密碼重設連結發送至
        </p>
        
        <p class="text-accent-600 font-semibold mb-6">
          ${escapeHtml(email)}
        </p>
        
        <div class="bg-modern-50 p-4 rounded-lg mb-6 text-left">
          <p class="text-sm text-modern-700 mb-2">
            <strong>下一步：</strong>
          </p>
          <ol class="text-sm text-modern-600 space-y-2 list-decimal list-inside">
            <li>檢查您的電子郵件收件匣</li>
            <li>點擊郵件中的重設密碼連結</li>
            <li>設定新密碼並完成重設</li>
          </ol>
        </div>
        
        <div class="space-y-3">
          <button
            onclick="window.location.href='/login'"
            class="btn-primary w-full"
          >
            返回登入頁面
          </button>
          
          <button
            onclick="window.location.href='/forgot-password'"
            class="btn-secondary w-full"
          >
            重新發送郵件
          </button>
        </div>
      </div>
    </div>
  `;
}

/**
 * HTML 轉義
 */
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
