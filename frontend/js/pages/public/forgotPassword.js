import { authAPI } from "../../api/modules/auth.js";
import { ApiError } from "../../api/client.js";
import { toast } from "../../utils/toast.js";
import { FormValidator, ValidationRules } from "../../utils/validator.js";
import { loading } from "../../components/Loading.js";

export function renderForgotPassword() {
    const app = document.getElementById("app");

    app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full animate-fade-in">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-accent-100 rounded-full mb-4">
            <svg class="w-8 h-8 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-modern-900 mb-2">忘記密碼</h1>
          <p class="text-modern-600">請輸入您的電子郵件地址，我們將發送重設密碼連結給您</p>
        </div>

        <form id="forgot-password-form" class="space-y-6">
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

          <button
            type="submit"
            class="btn-primary w-full"
            id="submit-btn"
          >
            發送重設連結
          </button>
        </form>

        <div class="mt-6 text-center">
          <a href="/login" class="text-sm text-accent-600 hover:text-accent-700">
            ← 返回登入頁面
          </a>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
          <p class="text-sm text-blue-800 mb-2">
            <strong>注意：</strong>
          </p>
          <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
            <li>重設連結將在 24 小時內有效</li>
            <li>如果沒有收到郵件，請檢查垃圾郵件資料夾</li>
            <li>如需協助，請聯繫系統管理員</li>
          </ul>
        </div>
      </div>
    </div>
  `;

    bindForgotPasswordForm();
}

function bindForgotPasswordForm() {
    const form = document.getElementById("forgot-password-form");
    const btn = document.getElementById("submit-btn");

    if (!form || !btn) {
        return;
    }

    const validator = new FormValidator(form, {
        email: [
            ValidationRules.required("請輸入電子郵件"),
            ValidationRules.email("請輸入有效的電子郵件格式"),
        ],
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        validator.clearErrors();

        if (!validator.validate()) {
            validator.showErrors();
            return;
        }

        const email = form.email.value.trim();

        btn.disabled = true;
        btn.textContent = "發送中...";

        loading.show("發送重設連結中...");

        let succeeded = false;

        try {
            const response = await authAPI.forgotPassword(email);
            succeeded = true;

            toast.success("重設密碼連結已發送至您的信箱，請查收");

            showSuccessMessage(email, {
                expiresAt: response?.expires_at ?? null,
                debugToken: response?.debug_token ?? null,
            });
        } catch (error) {
            if (
                error &&
                typeof error.isValidationError === "function" &&
                error.isValidationError()
            ) {
                applyFieldErrors(form, error.getValidationErrors());
            } else if (error instanceof ApiError && error.data?.errors) {
                applyFieldErrors(form, error.data.errors);
            } else {
                let errorMessage = "發送失敗，請稍後再試";

                if (error && typeof error.getUserMessage === "function") {
                    errorMessage = error.getUserMessage();
                } else if (error && error.message) {
                    errorMessage = error.message;
                }

                toast.error(errorMessage);
            }
        } finally {
            loading.hide();

            if (!succeeded) {
                btn.disabled = false;
                btn.textContent = "發送重設連結";
            }
        }
    });

    form.querySelectorAll("input").forEach((input) => {
        input.addEventListener("input", () => {
            const errorEl = form.querySelector(
                `[data-error-for="${input.name}"]`
            );
            if (errorEl) {
                errorEl.classList.add("hidden");
                errorEl.textContent = "";
            }

            input.classList.remove("border-red-500");
        });
    });
}

function applyFieldErrors(form, errors) {
    if (!errors || typeof errors !== "object") {
        return;
    }

    Object.entries(errors).forEach(([field, messages]) => {
        const errorEl = form.querySelector(`[data-error-for="${field}"]`);
        const input = form.querySelector(`[name="${field}"]`);

        if (errorEl) {
            errorEl.textContent = Array.isArray(messages)
                ? messages[0]
                : String(messages);
            errorEl.classList.remove("hidden");
        }

        if (input) {
            input.classList.add("border-red-500");
        }
    });
}

function showSuccessMessage(
    email,
    { expiresAt = null, debugToken = null } = {}
) {
    const app = document.getElementById("app");

    app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full animate-fade-in text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <h2 class="text-2xl font-bold text-modern-900 mb-4">郵件已發送</h2>

        <p class="text-modern-600 mb-4">
          我們已將密碼重設連結發送至
        </p>

        <p class="text-accent-600 font-semibold mb-6">
          ${escapeHtml(email)}
        </p>

        ${
            expiresAt
                ? `
          <p class="text-sm text-modern-500 mb-6">
            連結有效期限至 <span class="font-medium">${escapeHtml(
                new Date(expiresAt).toLocaleString()
            )}</span>
          </p>
        `
                : ""
        }

        ${
            debugToken
                ? `
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-left">
            <p class="text-xs text-yellow-700 mb-2">
              目前處於非正式環境，我們將直接顯示重設 Token 以方便測試：
            </p>
            <code class="block text-xs text-yellow-900 break-all">${escapeHtml(
                debugToken
            )}</code>
          </div>
        `
                : ""
        }

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

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text ?? "";
    return div.innerHTML;
}
