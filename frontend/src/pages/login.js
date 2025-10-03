import { authAPI } from '../api/modules/auth.js';
import { globalActions } from '../store/globalStore.js';
import { router } from '../router/index.js';
import { toast } from '../utils/toast.js';
import { FormValidator, ValidationRules } from '../utils/formValidator.js';
import { loading } from '../components/Loading.js';

/**
 * 渲染登入頁面
 */
export function renderLogin() {
  const app = document.getElementById('app');
  
  app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full animate-fade-in">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-modern-900 mb-2">AlleyNote</h1>
          <p class="text-modern-600">歡迎回來，請登入您的帳號</p>
        </div>
        
        <form id="login-form" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-medium text-modern-700 mb-2">
              電子郵件 *
            </label>
            <input
              type="email"
              id="email"
              name="email"
              required
              autocomplete="email"
              class="input-field"
              placeholder="your@email.com"
            />
            <p class="text-red-500 text-sm mt-1 hidden" data-error-for="email"></p>
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-modern-700 mb-2">
              密碼 *
            </label>
            <input
              type="password"
              id="password"
              name="password"
              required
              autocomplete="current-password"
              class="input-field"
              placeholder="••••••••"
            />
            <p class="text-red-500 text-sm mt-1 hidden" data-error-for="password"></p>
          </div>
          
          <div class="flex items-center justify-between">
            <label class="flex items-center">
              <input
                type="checkbox"
                id="remember"
                name="remember"
                class="w-4 h-4 text-accent-600 border-modern-300 rounded focus:ring-accent-500"
              />
              <span class="ml-2 text-sm text-modern-700">記住我</span>
            </label>
            <a href="#" class="text-sm text-accent-600 hover:text-accent-700">
              忘記密碼？
            </a>
          </div>
          
          <button
            type="submit"
            class="btn-primary w-full"
            id="login-btn"
          >
            登入
          </button>
        </form>
        
        <div class="mt-6 text-center">
          <p class="text-sm text-modern-600 mb-2">測試帳號：</p>
          <div class="bg-modern-50 p-3 rounded-lg text-sm">
            <p class="font-mono">admin@example.com / password</p>
          </div>
        </div>
        
        <div class="mt-6 text-center text-xs text-modern-500">
          <p>登入即表示您同意我們的服務條款和隱私政策</p>
        </div>
      </div>
    </div>
  `;

  // 綁定表單提交事件
  bindLoginForm();
}

/**
 * 綁定登入表單
 */
function bindLoginForm() {
  const form = document.getElementById('login-form');
  const btn = document.getElementById('login-btn');
  
  // 定義驗證規則
  const validator = new FormValidator(form, {
    email: [
      ValidationRules.required('請輸入電子郵件'),
      ValidationRules.email('請輸入有效的電子郵件格式'),
    ],
    password: [
      ValidationRules.required('請輸入密碼'),
      ValidationRules.min(6, '密碼至少需要 6 個字元'),
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
    const password = form.password.value;
    const remember = form.remember.checked;
    
    // 禁用按鈕
    btn.disabled = true;
    btn.textContent = '登入中...';
    
    loading.show('登入中...');
    
    try {
      const result = await authAPI.login({ email, password, remember });
      globalActions.setUser(result.user);
      
      loading.hide();
      toast.success('登入成功！歡迎回來');
      
      // 延遲導向，讓使用者看到成功訊息
      setTimeout(() => {
        router.navigate('/admin/dashboard');
      }, 500);
      
    } catch (error) {
      loading.hide();
      
      if (error.isValidationError()) {
        const errors = error.getValidationErrors();
        Object.entries(errors).forEach(([field, messages]) => {
          const errorEl = document.querySelector(`[data-error-for="${field}"]`);
          if (errorEl) {
            errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
            errorEl.classList.remove('hidden');
          }
        });
      } else {
        toast.error(error.getUserMessage() || '登入失敗，請檢查您的帳號密碼');
      }
      
      // 重置按鈕
      btn.disabled = false;
      btn.textContent = '登入';
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

