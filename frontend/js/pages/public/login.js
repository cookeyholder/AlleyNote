import { authAPI } from "../../api/modules/auth.js";
import { globalActions } from "../../store/globalStore.js";
import { router } from "../../utils/router.js";
import { notification } from "../../utils/notification.js";
import { FormValidator, ValidationRules } from "../../utils/validator.js";
import { loading } from "../../components/Loading.js";

/**
 * 渲染登入頁面
 */
export function renderLogin() {
  const app = document.getElementById("app");

  app.innerHTML = `
    <div class="min-h-screen bg-modern-50 flex items-center justify-center p-6 relative overflow-hidden">
      <!-- 品牌裝飾背景 -->
      <div class="absolute top-0 left-0 w-full h-1 bg-accent-600"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-accent-100 rounded-full blur-3xl opacity-20"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-20"></div>

      <div class="max-w-md w-full animate-fade-in relative z-10">
        <!-- Logo -->
        <div class="flex flex-col items-center mb-10">
          <div class="w-16 h-16 bg-accent-600 rounded-2xl flex items-center justify-center text-white font-bold text-3xl shadow-xl shadow-accent-600/30 mb-6">A</div>
          <h1 class="text-4xl font-bold text-modern-900 tracking-tight">AlleyNote</h1>
          <p class="text-modern-500 font-bold text-xs uppercase tracking-widest mt-2">Enterprise Access Control</p>
        </div>

        <!-- 登入卡片 -->
        <div class="bg-white border border-modern-200 rounded-3xl shadow-2xl shadow-modern-200/50 p-10">
          <div class="mb-8">
            <h2 class="text-xl font-bold text-modern-900 mb-1">歡迎回來</h2>
            <p class="text-sm text-modern-500">請輸入您的電子郵件與密碼以存取後台系統</p>
          </div>

          <form id="login-form" class="space-y-6">
            <div>
              <label for="email" class="block text-[10px] font-bold text-modern-400 uppercase tracking-widest mb-2">
                電子郵件地址 *
              </label>
              <div class="relative">
                <input
                  type="email"
                  id="email"
                  name="email"
                  required
                  autocomplete="email"
                  class="w-full px-4 py-3.5 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
                  placeholder="your@email.com"
                />
                <span class="absolute right-4 top-3.5 text-modern-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                </span>
              </div>
              <p class="text-red-500 text-xs font-bold mt-2 hidden" data-error-for="email"></p>
            </div>

            <div>
              <div class="flex justify-between items-center mb-2">
                <label for="password" class="block text-[10px] font-bold text-modern-400 uppercase tracking-widest">
                  登入密碼 *
                </label>
                <a href="/forgot-password" class="text-xs font-bold text-accent-600 hover:text-accent-700 transition-colors">
                  忘記密碼？
                </a>
              </div>
              <div class="relative">
                <input
                  type="password"
                  id="password"
                  name="password"
                  required
                  autocomplete="current-password"
                  class="w-full px-4 py-3.5 bg-modern-50 border border-modern-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-accent-500 transition-all outline-none"
                  placeholder="••••••••"
                />
                <span class="absolute right-4 top-3.5 text-modern-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
              </div>
              <p class="text-red-500 text-xs font-bold mt-2 hidden" data-error-for="password"></p>
            </div>

            <div class="flex items-center">
              <label class="flex items-center group cursor-pointer">
                <input
                  type="checkbox"
                  id="remember"
                  name="remember"
                  class="w-5 h-5 text-accent-600 border-modern-300 rounded-lg focus:ring-accent-500 cursor-pointer"
                />
                <span class="ml-2 text-sm font-bold text-modern-600 group-hover:text-modern-900 transition-colors">記住我的登入狀態</span>
              </label>
            </div>

            <button
              type="submit"
              class="w-full py-4 bg-accent-600 text-white font-bold rounded-xl hover:bg-accent-700 shadow-xl shadow-accent-600/20 transform transition-all active:scale-95 flex items-center justify-center gap-2"
              id="login-btn"
            >
              登入系統
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </button>
          </form>
        </div>

        <div class="mt-10 text-center">
          <p class="text-xs text-modern-400 font-bold uppercase tracking-tighter">
            AlleyNote Security Protocol &copy; 2024
          </p>
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
  const form = document.getElementById("login-form");
  const btn = document.getElementById("login-btn");

  // 定義驗證規則
  const validator = new FormValidator(form, {
    email: [
      ValidationRules.required("請輸入電子郵件"),
      ValidationRules.email("請輸入有效的電子郵件格式"),
    ],
    password: [
      ValidationRules.required("請輸入密碼"),
      ValidationRules.min(6, "密碼至少需要 6 個字元"),
    ],
  });

  form.addEventListener("submit", async (e) => {
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
    btn.textContent = "登入中...";

    loading.show("登入中...");

    try {
      await authAPI.login({ email, password, remember });

      loading.hide();
      notification.success("登入成功！歡迎回來");

      // 先恢復按鈕狀態，避免導向失敗時停留在「登入中...」
      btn.disabled = false;
      btn.innerHTML = `
        登入系統
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
      `;

      // 延遲導向，讓使用者看到成功訊息
      setTimeout(async () => {
        try {
          await router.navigate("/admin/dashboard");
        } catch (navigateError) {
          console.error("登入後導向失敗:", navigateError);
          loading.hide();
          notification.error(
            "登入成功，但頁面導向失敗，請重新整理頁面再試一次",
          );
        }
      }, 500);
    } catch (error) {
      loading.hide();

      // 修復：檢查 error 是否有 isValidationError 方法
      if (
        error &&
        typeof error.isValidationError === "function" &&
        error.isValidationError()
      ) {
        const errors = error.getValidationErrors();
        if (errors && typeof errors === "object") {
          Object.entries(errors).forEach(([field, messages]) => {
            const errorEl = document.querySelector(
              `[data-error-for="${field}"]`,
            );
            if (errorEl) {
              errorEl.textContent = Array.isArray(messages)
                ? messages[0]
                : messages;
              errorEl.classList.remove("hidden");
            }
          });
        }
      } else {
        // 處理各種錯誤格式
        let errorMessage = "登入失敗，請檢查您的帳號密碼";

        if (error && typeof error.getUserMessage === "function") {
          errorMessage = error.getUserMessage();
        } else if (error && error.message) {
          errorMessage = error.message;
        } else if (typeof error === "string") {
          errorMessage = error;
        }

        notification.error(errorMessage);
      }

      // 重置按鈕
      btn.disabled = false;
      btn.textContent = "登入";
    }
  });

  // 輸入時清除錯誤
  form.querySelectorAll("input").forEach((input) => {
    input.addEventListener("input", () => {
      const errorEl = document.querySelector(
        `[data-error-for="${input.name}"]`,
      );
      if (errorEl) {
        errorEl.classList.add("hidden");
      }
      input.classList.remove("border-red-500");
    });
  });
}
