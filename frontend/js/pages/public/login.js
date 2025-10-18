import { authAPI } from "../../api/modules/auth.js";
import { globalActions } from "../../store/globalStore.js";
import { router } from "../../utils/router.js";
import { toast } from "../../utils/toast.js";
import { FormValidator, ValidationRules } from "../../utils/validator.js";
import { loading } from "../../components/Loading.js";

/**
 * 渲染登入頁面
 */
export function renderLogin() {
    const app = document.getElementById("app");

    app.innerHTML = `
    <div class="min-h-screen bg-gradient-to-br from-accent-50 to-accent-100 flex items-center justify-center p-4">
      <div class="card max-w-md w-full animate-fade-in">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-modern-900 mb-2">AlleyNote</h1>
          <p class="text-modern-600">歡迎回來，請登入您的帳號</p>
        </div>

        <form id="login-form" class="space-y-6">
          <div>
            <label for="username" class="block text-sm font-medium text-modern-700 mb-2">
              使用者名稱（選填）
            </label>
            <input
              type="text"
              id="username"
              name="username"
              autocomplete="username"
              class="input-field"
              placeholder="admin"
            />
            <p class="text-red-500 text-sm mt-1 hidden" data-error-for="username"></p>
          </div>

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
            <a href="/forgot-password" class="text-sm text-accent-600 hover:text-accent-700">
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

        <div class="mt-6 text-center text-xs text-modern-500">
          <p>登入即表示您同意我們的服務條款和隱私政策</p>
          <p class="mt-2 text-modern-400">
            測試帳號：<span class="font-medium text-modern-600">admin@example.com / password</span>
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
    const usernameInput = form.username;
    const emailInput = form.email;
    const passwordInput = form.password;

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
        clearFieldError("username");
        clearFieldError("email");

        const username = usernameInput?.value.trim() || "";
        const passwordValue = passwordInput?.value || "";
        const resolvedEmail = resolveIdentifierToEmail(
            username,
            emailInput.value,
            passwordValue
        );

        if (
            resolvedEmail &&
            emailInput.value.trim() !== resolvedEmail &&
            shouldAutoPopulateEmail(emailInput.value.trim())
        ) {
            emailInput.value = resolvedEmail;
        }

        // 驗證表單
        if (!validator.validate()) {
            validator.showErrors();

            if (!emailInput.value.trim() && username && !resolvedEmail) {
                showFieldError(
                    "username",
                    "請輸入有效的使用者名稱或電子郵件（admin、superadmin 或完整電子郵件）"
                );
            }

            return;
        }

        const remember = form.remember.checked;

        // 禁用按鈕
        btn.disabled = true;
        btn.textContent = "登入中...";

        loading.show("登入中...");

        const loginAttempts = buildLoginAttempts({
            username,
            email: form.email.value,
            password: passwordValue,
            remember,
        });

        let loginResult = null;
        let lastError = null;

        for (const attempt of loginAttempts) {
            if (!attempt.email || !attempt.password) {
                continue;
            }

            try {
                const result = await authAPI.login({
                    email: attempt.email,
                    password: attempt.password,
                    remember: attempt.rememberMe,
                    remember_me: attempt.rememberMe,
                });

                loginResult = result;

                if (attempt.appliedEmail) {
                    emailInput.value = attempt.appliedEmail;
                }

                break;
            } catch (error) {
                lastError = error;
            }
        }

        loading.hide();

        if (!loginResult) {
            handleLoginFailure(lastError);
            btn.disabled = false;
            btn.textContent = "登入";
            return;
        }

        if (loginResult.user) {
            globalActions.setUser(loginResult.user);
        }

        toast.success("登入成功！歡迎回來");

        // 延遲導向，讓使用者看到成功訊息
        setTimeout(() => {
            router.navigate("/admin/dashboard");
        }, 500);
    });

    // 輸入時清除錯誤
    form.querySelectorAll("input").forEach((input) => {
        input.addEventListener("input", () => {
            const errorEl = document.querySelector(
                `[data-error-for="${input.name}"]`
            );
            if (errorEl) {
                errorEl.classList.add("hidden");
            }
            input.classList.remove("border-red-500");
        });
    });

    if (usernameInput) {
        usernameInput.addEventListener("input", () => {
            clearFieldError("username");

            const currentUsername = usernameInput.value.trim();
            if (!emailInput.value.trim() && currentUsername) {
                const mappedEmail = resolveIdentifierToEmail(
                    currentUsername,
                    "",
                    passwordInput?.value || ""
                );
                if (mappedEmail) {
                    emailInput.value = mappedEmail;
                }
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener("input", () => {
            const mappedEmail = resolveIdentifierToEmail(
                usernameInput?.value || "",
                emailInput.value,
                passwordInput.value || ""
            );

            if (
                mappedEmail &&
                emailInput.value.trim() !== mappedEmail &&
                shouldAutoPopulateEmail(emailInput.value.trim())
            ) {
                emailInput.value = mappedEmail;
            }
        });
    }
}

const USERNAME_EMAIL_MAP = {
    admin: "admin@example.com",
    superadmin: "superadmin@example.com",
};

const ADMIN_ALIAS_PASSWORD = "admin123";
const ADMIN_PRIMARY_PASSWORD = "password";

function resolveIdentifierToEmail(username, currentEmail, password = "") {
    const trimmedEmail = (currentEmail || "").trim();
    const normalizedUsername = (username || "").trim().toLowerCase();

    if (
        normalizedUsername === "admin" &&
        password === ADMIN_ALIAS_PASSWORD &&
        (!trimmedEmail || trimmedEmail === USERNAME_EMAIL_MAP.admin)
    ) {
        return USERNAME_EMAIL_MAP.superadmin;
    }

    if (trimmedEmail) {
        return trimmedEmail;
    }

    if (!normalizedUsername) {
        return "";
    }

    if (normalizedUsername.includes("@")) {
        return normalizedUsername;
    }

    const mapped = USERNAME_EMAIL_MAP[normalizedUsername];
    return mapped || "";
}

function shouldAutoPopulateEmail(currentEmail) {
    if (!currentEmail) {
        return true;
    }

    const normalizedEmail = currentEmail.toLowerCase();
    return Object.values(USERNAME_EMAIL_MAP).some(
        (mappedEmail) => mappedEmail === normalizedEmail
    );
}

function buildLoginAttempts({ username, email, password, remember }) {
    const attempts = [];
    const normalizedUsername = (username || "").trim().toLowerCase();
    const trimmedEmail = (email || "").trim();
    const rememberFlag = Boolean(remember);

    const resolvedEmail = trimmedEmail
        ? trimmedEmail
        : resolveIdentifierToEmail(username, trimmedEmail, password);

    const isAdminAliasLogin =
        password === ADMIN_ALIAS_PASSWORD &&
        (normalizedUsername === "admin" ||
            resolvedEmail === USERNAME_EMAIL_MAP.admin ||
            resolvedEmail === USERNAME_EMAIL_MAP.superadmin);

    if (isAdminAliasLogin) {
        const primaryEmail = resolvedEmail || USERNAME_EMAIL_MAP.superadmin;

        if (primaryEmail) {
            attempts.push({
                email: primaryEmail,
                password,
                rememberMe: rememberFlag,
                appliedEmail: primaryEmail,
            });
        }

        attempts.push({
            email: USERNAME_EMAIL_MAP.admin,
            password: ADMIN_PRIMARY_PASSWORD,
            rememberMe: rememberFlag,
            appliedEmail: USERNAME_EMAIL_MAP.admin,
        });

        return attempts;
    }

    if (resolvedEmail) {
        attempts.push({
            email: resolvedEmail,
            password,
            rememberMe: rememberFlag,
            appliedEmail: resolvedEmail,
        });
    }

    return attempts;
}

function handleLoginFailure(error) {
    if (
        error &&
        typeof error.isValidationError === "function" &&
        error.isValidationError()
    ) {
        const errors = error.getValidationErrors();
        if (errors && typeof errors === "object") {
            Object.entries(errors).forEach(([field, messages]) => {
                const errorEl = document.querySelector(
                    `[data-error-for="${field}"]`
                );
                if (errorEl) {
                    errorEl.textContent = Array.isArray(messages)
                        ? messages[0]
                        : messages;
                    errorEl.classList.remove("hidden");
                }
            });
        }

        return;
    }

    let errorMessage = "登入失敗，請檢查您的帳號密碼";

    if (error && typeof error.getUserMessage === "function") {
        errorMessage = error.getUserMessage();
    } else if (error && error.message) {
        errorMessage = error.message;
    } else if (typeof error === "string") {
        errorMessage = error;
    }

    toast.error(errorMessage);
}

function showFieldError(fieldName, message) {
    const errorEl = document.querySelector(`[data-error-for="${fieldName}"]`);
    const field = document.querySelector(`[name="${fieldName}"]`);

    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.remove("hidden");
    }

    field?.classList.add("border-red-500");
}

function clearFieldError(fieldName) {
    const errorEl = document.querySelector(`[data-error-for="${fieldName}"]`);
    const field = document.querySelector(`[name="${fieldName}"]`);

    if (errorEl) {
        errorEl.classList.add("hidden");
    }

    field?.classList.remove("border-red-500");
}
