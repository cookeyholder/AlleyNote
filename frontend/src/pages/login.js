import { authAPI } from '../api/modules/auth.js';
import { globalActions } from '../store/globalStore.js';
import { router } from '../router/index.js';
import { toast } from '../utils/toast.js';

/**
 * 渲染登入頁面
 */
export function renderLogin() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-modern-900 mb-2">AlleyNote</h1>
          <p class="text-modern-600">歡迎回來，請登入您的帳號</p>
        </div>
        
        <form id="login-form" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-medium text-modern-700 mb-2">
              電子郵件
            </label>
            <input
              type="email"
              id="email"
              name="email"
              required
              class="input-field"
              placeholder="your@email.com"
            />
            <p class="text-red-500 text-sm mt-1 hidden" data-error-for="email"></p>
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-modern-700 mb-2">
              密碼
            </label>
            <input
              type="password"
              id="password"
              name="password"
              required
              class="input-field"
              placeholder="••••••••"
            />
            <p class="text-red-500 text-sm mt-1 hidden" data-error-for="password"></p>
          </div>
          
          <button
            type="submit"
            class="btn-primary w-full"
            id="login-btn"
          >
            登入
          </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-modern-600">
          <p>測試帳號：admin@example.com / password</p>
        </div>
      </div>
    </div>
  `;

  // 綁定表單提交事件
  const form = document.getElementById('login-form');
  const btn = document.getElementById('login-btn');
  
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = form.email.value;
    const password = form.password.value;
    
    // 清除舊錯誤
    clearErrors();
    
    // 禁用按鈕
    btn.disabled = true;
    btn.textContent = '登入中...';
    
    try {
      const result = await authAPI.login({ email, password });
      globalActions.setUser(result.user);
      
      toast.success('登入成功！');
      
      // 導向後台
      router.navigate('/admin/dashboard');
    } catch (error) {
      if (error.isValidationError()) {
        showValidationErrors(error.getValidationErrors());
      } else {
        toast.error(error.getUserMessage());
      }
    } finally {
      btn.disabled = false;
      btn.textContent = '登入';
    }
  });
}

/**
 * 清除錯誤訊息
 */
function clearErrors() {
  document.querySelectorAll('[data-error-for]').forEach((el) => {
    el.textContent = '';
    el.classList.add('hidden');
  });
}

/**
 * 顯示驗證錯誤
 */
function showValidationErrors(errors) {
  Object.entries(errors).forEach(([field, messages]) => {
    const errorEl = document.querySelector(`[data-error-for="${field}"]`);
    if (errorEl) {
      errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
      errorEl.classList.remove('hidden');
    }
  });
}
